<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProjectStorageService
{
    public function getS3Client()
    {
        return new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'http' => [
                'connect_timeout' => 5,  // وقت قصير للاتصال
                'timeout' => 30,         // وقت قصير للعمليات - يفشل بسرعة
            ],
        ]);
    }

    public function createProjectFolderStructure(Project $project)
    {
        // زيادة وقت التنفيذ المسموح - لن يعطل إنشاء المشروع
        @set_time_limit(120);

        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            $project->load('client');
            $clientName = $project->client ? $project->client->name : 'no-client';

            $projectCode = $project->code ? $project->code : 'no-code';

            $projectFolder = 'projects/' . $clientName . '_' . $project->name . '_' . $projectCode;

            // فحص الفولدرات الموجودة مسبقاً
            $existingFolders = $this->listExistingFolders($projectFolder);

            // إنشاء الفولدر الرئيسي فقط إذا لم يكن موجوداً
            if (!$this->folderExists($existingFolders, $projectFolder . '/')) {
                Log::info("إنشاء فولدر المشروع الرئيسي: {$projectFolder}");
                $s3->putObject([
                    'Bucket' => $bucket,
                    'Key'    => $projectFolder . '/',
                    'Body'   => '',
                ]);
            }

            // إنشاء الفولدرات الثابتة
            $fixedTypes = ['مرفقات أولية', 'تقارير مكالمات', 'مرفقات من العميل', 'عقود', 'الدراسه النهائيه'];
            foreach ($fixedTypes as $type) {
                $folderPath = $projectFolder . '/' . $type . '/';
                if (!$this->folderExists($existingFolders, $folderPath)) {
                    Log::info("إنشاء فولدر: {$folderPath}");
                    $s3->putObject([
                        'Bucket' => $bucket,
                        'Key'    => $folderPath,
                        'Body'   => '',
                    ]);
                }
            }

            // إنشاء فولدرات الخدمات
            foreach ($project->services as $service) {
                $folderPath = $projectFolder . '/' . $service->name . '/';
                if (!$this->folderExists($existingFolders, $folderPath)) {
                    Log::info("إنشاء فولدر خدمة: {$folderPath}");
                    $s3->putObject([
                        'Bucket' => $bucket,
                        'Key'    => $folderPath,
                        'Body'   => '',
                    ]);
                }
            }

            Log::info("تم إنشاء هيكل فولدرات المشروع بنجاح", ['project_id' => $project->id]);
            return true;

        } catch (\Aws\S3\Exception\S3Exception $e) {
            $errorCode = $e->getAwsErrorCode();
            $errorMessage = $e->getMessage();

            Log::error("خطأ في Wasabi/S3 عند إنشاء فولدرات المشروع", [
                'project_id' => $project->id,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'suggestion' => 'تحقق من صحة بيانات الاشتراك في Wasabi'
            ]);

            // رسائل خطأ واضحة للمستخدم
            if (strpos($errorMessage, 'InvalidAccessKeyId') !== false) {
                throw new \Exception('بيانات الدخول لـ Wasabi غير صحيحة. تحقق من AWS_ACCESS_KEY_ID');
            } elseif (strpos($errorMessage, 'SignatureDoesNotMatch') !== false) {
                throw new \Exception('بيانات الدخول لـ Wasabi غير صحيحة. تحقق من AWS_SECRET_ACCESS_KEY');
            } elseif (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'timed out') !== false) {
                throw new \Exception('انتهت مهلة الاتصال بـ Wasabi. قد يكون الاشتراك منتهي أو هناك مشكلة في الشبكة');
            } else {
                throw new \Exception('خطأ في الاتصال بـ Wasabi: ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::error("خطأ عام عند إنشاء فولدرات المشروع", [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * فحص الفولدرات الموجودة في S3
     */
    private function listExistingFolders($projectFolder)
    {
        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            $result = $s3->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => $projectFolder . '/',
                'Delimiter' => '/'
            ]);

            $folders = [];

            // الفولدرات الفرعية
            if (isset($result['CommonPrefixes'])) {
                foreach ($result['CommonPrefixes'] as $prefix) {
                    $folders[] = $prefix['Prefix'];
                }
            }

            // الكائنات (التي تمثل فولدرات فارغة)
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    if (substr($object['Key'], -1) === '/') {
                        $folders[] = $object['Key'];
                    }
                }
            }

            return $folders;
        } catch (\Exception $e) {
            Log::warning("خطأ في قراءة الفولدرات الموجودة: " . $e->getMessage());
            return [];
        }
    }

    /**
     * فحص ما إذا كان الفولدر موجود في القائمة
     */
    private function folderExists($existingFolders, $folderPath)
    {
        return in_array($folderPath, $existingFolders);
    }

    public function updateProjectAttachmentPaths(Project $project, string $oldName)
    {
        $s3 = $this->getS3Client();
        $bucket = env('AWS_BUCKET');

        $oldProjectFolder = 'projects/' . $project->id . '_' . $oldName;

        $project->load('client');
        $clientName = $project->client ? $project->client->name : 'no-client';
        $projectCode = $project->code ? $project->code : 'no-code';
        $newProjectFolder = 'projects/' . $clientName . '_' . $project->name . '_' . $projectCode;

        $attachments = $project->attachments;
        foreach ($attachments as $attachment) {
            if (strpos($attachment->file_path, $oldProjectFolder) === 0) {
                $newPath = str_replace($oldProjectFolder, $newProjectFolder, $attachment->file_path);
                $attachment->update(['file_path' => $newPath]);

                try {
                    $s3->copyObject([
                        'Bucket' => $bucket,
                        'CopySource' => $bucket . '/' . $attachment->file_path,
                        'Key' => $newPath
                    ]);

                    $s3->deleteObject([
                        'Bucket' => $bucket,
                        'Key' => $attachment->file_path
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to move attachment: ' . $e->getMessage());
                }
            }
        }
    }

    public function generatePresignedUploadUrl($project, $fileName, $serviceType)
    {
        $project->load('client');
        $clientName = $project->client ? $project->client->name : 'no-client';
        $projectCode = $project->code ? $project->code : 'no-code';
        $projectFolder = 'projects/' . $clientName . '_' . $project->name . '_' . $projectCode;

        $userName = Auth::user() ? Auth::user()->name : 'unknown-user';

        $fileKey = $projectFolder . '/' . $serviceType . '/' . $userName . '_' . $fileName;

        $s3 = $this->getS3Client();
        $bucket = env('AWS_BUCKET');

        $cmd = $s3->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key'    => $fileKey,
        ]);

        $requestObj = $s3->createPresignedRequest($cmd, '+5 minutes');
        $uploadUrl = (string) $requestObj->getUri();

        return [
            'upload_url' => $uploadUrl,
            'file_key' => $fileKey
        ];
    }

    public function generatePresignedViewUrl($filePath)
    {
        try {
            // محاولة التحقق من وجود الملف بطريقة آمنة
            if (!Storage::disk('s3')->exists($filePath)) {
                \Log::warning("File not found in S3: {$filePath}");
                return null;
            }
        } catch (\Exception $e) {
            \Log::warning("Error checking file existence in S3: {$filePath} - " . $e->getMessage());
            // في حالة الخطأ، نتابع محاولة إنشاء الرابط المباشر
        }

        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => $filePath,
            ]);

            $request = $s3->createPresignedRequest($cmd, '+5 minutes');
            return (string) $request->getUri();
        } catch (\Exception $e) {
            \Log::error("Error generating presigned URL for: {$filePath} - " . $e->getMessage());
            return null;
        }
    }

    public function generatePresignedDownloadUrl($filePath, $fileName)
    {
        try {
            // محاولة التحقق من وجود الملف بطريقة آمنة
            if (!Storage::disk('s3')->exists($filePath)) {
                \Log::warning("File not found in S3 for download: {$filePath}");
                return null;
            }
        } catch (\Exception $e) {
            \Log::warning("Error checking file existence in S3 for download: {$filePath} - " . $e->getMessage());
            // في حالة الخطأ، نتابع محاولة إنشاء الرابط المباشر
        }

        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => $filePath,
                'ResponseContentDisposition' => 'attachment; filename="' . $fileName . '"'
            ]);

            $request = $s3->createPresignedRequest($cmd, '+5 minutes');
            return (string) $request->getUri();
        } catch (\Exception $e) {
            \Log::error("Error generating presigned download URL for: {$filePath} - " . $e->getMessage());
            return null;
        }
    }
}
