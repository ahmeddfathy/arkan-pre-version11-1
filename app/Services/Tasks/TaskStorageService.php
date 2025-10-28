<?php

namespace App\Services\Tasks;

use App\Models\TaskUser;
use App\Models\TaskAttachment;
use App\Traits\HasNTPTime;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TaskStorageService
{
    use HasNTPTime;
    /**
     * إنشاء S3 Client
     */
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

    /**
     * إنشاء هيكل مجلد للموظف إذا لم يكن موجوداً
     */
    public function createEmployeeFolderStructure($employeeName)
    {
        $s3 = $this->getS3Client();
        $bucket = env('AWS_BUCKET');

        $employeeFolder = 'employees/' . str_replace(' ', '_', $employeeName);

        try {
            // إنشاء مجلد الموظف
            $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $employeeFolder . '/',
                'Body'   => '',
            ]);

            Log::info('Created employee folder structure', [
                'employee' => $employeeName,
                'folder' => $employeeFolder
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create employee folder structure', [
                'employee' => $employeeName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * إنشاء presigned URL لرفع ملف مهمة
     */
    public function generatePresignedUploadUrl(TaskUser $taskUser, $fileName)
    {
        // التأكد من أن المهمة غير مرتبطة بمشروع
        if ($taskUser->project_id) {
            throw new \Exception('هذه المهمة مرتبطة بمشروع، استخدم نظام مرفقات المشاريع');
        }

        $user = $taskUser->user;
        $task = $taskUser->task;

        // إنشاء مسار الملف
        $employeeName = str_replace(' ', '_', $user->name);
        $taskTitle = str_replace(' ', '_', substr($task->title, 0, 50)); // تحديد طول العنوان
        $date = $this->getCurrentCairoTime()->format('Y-m-d');
        $timestamp = $this->getCurrentCairoTime()->format('His'); // ساعة ودقيقة وثانية

        // تنظيف اسم الملف
        $cleanFileName = $this->sanitizeFileName($fileName);

        $fileKey = "employees/{$employeeName}/{$taskTitle}_{$date}_{$timestamp}_{$cleanFileName}";

        $s3 = $this->getS3Client();
        $bucket = env('AWS_BUCKET');

        try {
            // إنشاء مجلد الموظف إذا لم يكن موجوداً
            $this->createEmployeeFolderStructure($user->name);

            // إنشاء presigned URL
            $cmd = $s3->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key'    => $fileKey,
                'ContentType' => $this->getMimeType($fileName),
            ]);

            $requestObj = $s3->createPresignedRequest($cmd, '+10 minutes');
            $uploadUrl = (string) $requestObj->getUri();

            Log::info('Generated presigned URL for task attachment', [
                'task_user_id' => $taskUser->id,
                'file_name' => $fileName,
                'file_key' => $fileKey
            ]);

            return [
                'upload_url' => $uploadUrl,
                'file_key' => $fileKey,
                'file_name' => $cleanFileName
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate presigned URL for task attachment', [
                'task_user_id' => $taskUser->id,
                'file_name' => $fileName,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('فشل في إنشاء رابط الرفع: ' . $e->getMessage());
        }
    }

    /**
     * إنشاء presigned URL لعرض ملف
     */
    public function generatePresignedViewUrl($filePath)
    {
        if (!Storage::disk('s3')->exists($filePath)) {
            return null;
        }

        $s3 = $this->getS3Client();
        $bucket = env('AWS_BUCKET');

        try {
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => $filePath,
            ]);

            $request = $s3->createPresignedRequest($cmd, '+5 minutes');
            return (string) $request->getUri();

        } catch (\Exception $e) {
            Log::error('Failed to generate presigned view URL', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * إنشاء presigned URL لتحميل ملف
     */
    public function generatePresignedDownloadUrl($filePath, $fileName)
    {
        if (!Storage::disk('s3')->exists($filePath)) {
            return null;
        }

        $s3 = $this->getS3Client();
        $bucket = env('AWS_BUCKET');

        try {
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => $filePath,
                'ResponseContentDisposition' => 'attachment; filename="' . $fileName . '"'
            ]);

            $request = $s3->createPresignedRequest($cmd, '+5 minutes');
            return (string) $request->getUri();

        } catch (\Exception $e) {
            Log::error('Failed to generate presigned download URL', [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * حذف ملف من S3
     */
    public function deleteFile($filePath)
    {
        try {
            if (Storage::disk('s3')->exists($filePath)) {
                Storage::disk('s3')->delete($filePath);

                Log::info('Deleted task attachment file', [
                    'file_path' => $filePath
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to delete task attachment file', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('فشل في حذف الملف: ' . $e->getMessage());
        }
    }

    /**
     * الحصول على معلومات الملف من S3
     */
    public function getFileInfo($filePath)
    {
        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            // استخدام headObject مباشرة بدلاً من exists للتعامل مع التأخير في Wasabi
            $result = $s3->headObject([
                'Bucket' => $bucket,
                'Key' => $filePath
            ]);

            Log::info('Successfully retrieved file info from S3', [
                'file_path' => $filePath,
                'size' => $result['ContentLength'] ?? 0
            ]);

            return [
                'size' => $result['ContentLength'] ?? 0,
                'last_modified' => $result['LastModified'] ?? null,
                'content_type' => $result['ContentType'] ?? 'application/octet-stream'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get file info', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * تنظيف اسم الملف من الأحرف غير المسموحة
     */
    protected function sanitizeFileName($fileName)
    {
        // إزالة الأحرف الخاصة والمسافات (مع دعم الأحرف العربية)
        $cleanName = preg_replace('/[^\w\.\-_\x{0600}-\x{06FF}]/u', '_', $fileName);

        // إزالة المسافات المتعددة والشرطات
        $cleanName = preg_replace('/[_\-]+/', '_', $cleanName);

        // إزالة الشرطة من البداية والنهاية
        $cleanName = trim($cleanName, '_-');

        return $cleanName;
    }

    /**
     * تحديد نوع MIME للملف
     */
    protected function getMimeType($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * الحصول على قائمة ملفات الموظف
     */
    public function getEmployeeFiles($employeeName, $limit = 20)
    {
        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            $employeeFolder = 'employees/' . str_replace(' ', '_', $employeeName);

            $result = $s3->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => $employeeFolder . '/',
                'MaxKeys' => $limit
            ]);

            $files = [];
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    if ($object['Key'] !== $employeeFolder . '/') { // تجاهل المجلد نفسه
                        $files[] = [
                            'key' => $object['Key'],
                            'size' => $object['Size'],
                            'last_modified' => $object['LastModified'],
                            'file_name' => basename($object['Key'])
                        ];
                    }
                }
            }

            return $files;

        } catch (\Exception $e) {
            Log::error('Failed to get employee files', [
                'employee' => $employeeName,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * إحصائيات استخدام التخزين للموظف
     */
    public function getEmployeeStorageStats($employeeName)
    {
        try {
            $files = $this->getEmployeeFiles($employeeName, 1000); // جلب كل الملفات

            $stats = [
                'total_files' => count($files),
                'total_size' => array_sum(array_column($files, 'size')),
                'largest_file' => 0,
                'latest_upload' => null
            ];

            if (!empty($files)) {
                $stats['largest_file'] = max(array_column($files, 'size'));
                $latestFile = collect($files)->sortByDesc('last_modified')->first();
                $stats['latest_upload'] = $latestFile['last_modified'] ?? null;
            }

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to get employee storage stats', [
                'employee' => $employeeName,
                'error' => $e->getMessage()
            ]);

            return [
                'total_files' => 0,
                'total_size' => 0,
                'largest_file' => 0,
                'latest_upload' => null
            ];
        }
    }
}
