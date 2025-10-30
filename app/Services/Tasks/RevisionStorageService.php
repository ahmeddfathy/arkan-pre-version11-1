<?php

namespace App\Services\Tasks;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class RevisionStorageService
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
                'connect_timeout' => 5,
                'timeout' => 30,
            ],
        ]);
    }

    /**
     * رفع ملف تعديل إلى Wasabi مع هيكل فولدرات منظم
     */
    public function uploadRevisionFile(UploadedFile $file, string $taskType, $taskId, array $revisionData = []): array
    {
        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            // بناء المسار المنظم
            $s3Path = $this->buildOrganizedPath($file, $taskType, $taskId, $revisionData);

            Log::info('Uploading revision file to Wasabi', [
                'file_name' => $file->getClientOriginalName(),
                'path' => $s3Path,
                'size' => $file->getSize()
            ]);

            // رفع الملف إلى Wasabi
            $result = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $s3Path,
                'Body'   => fopen($file->getRealPath(), 'r'),
                'ContentType' => $file->getMimeType(),
                'ACL'    => 'private', // الملف خاص (يحتاج presigned URL للوصول)
            ]);

            Log::info('✅ Revision file uploaded successfully to Wasabi', [
                'path' => $s3Path,
                'etag' => $result['ETag'] ?? null
            ]);

            return [
                'attachment_path' => $s3Path,
                'attachment_name' => $file->getClientOriginalName(),
                'attachment_type' => 'file',
                'attachment_size' => $file->getSize(),
                'attachment_link' => null
            ];

        } catch (\Aws\S3\Exception\S3Exception $e) {
            Log::error('❌ Wasabi S3 error uploading revision file', [
                'error_code' => $e->getAwsErrorCode(),
                'error_message' => $e->getMessage()
            ]);

            throw new \Exception('فشل رفع الملف إلى Wasabi: ' . $e->getMessage());

        } catch (\Exception $e) {
            Log::error('❌ General error uploading revision file', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * بناء مسار منظم للملف في Wasabi
     * task-revisions/موظف/تاريخ/كود_مشروع_اسم_مهمة/اسم_ملف
     */
    private function buildOrganizedPath(UploadedFile $file, string $taskType, $taskId, array $revisionData): string
    {
        // 1. اسم الموظف المسؤول (اللي عليه التعديل)
        $employeeName = 'unknown';
        if (isset($revisionData['responsible_user_id'])) {
            $user = \App\Models\User::find($revisionData['responsible_user_id']);
            if ($user) {
                $employeeName = $this->sanitizeForPath($user->name);
            }
        }

        // 2. التاريخ (YYYY-MM-DD)
        $date = now()->format('Y-m-d');

        // 3. معلومات المهمة/المشروع حسب النوع
        if ($taskType === 'project') {
            // تعديل مشروع
            $projectInfo = $this->getProjectInfo($taskId);
            $projectCode = $projectInfo['project_code'] ?? '';
            $projectName = $projectInfo['project_name'] ?? 'project_' . $taskId;

            $folderName = '';
            if ($projectCode) {
                $folderName .= $projectCode . '_';
            }
            $folderName .= $this->sanitizeForPath($projectName);
        } elseif ($taskType === 'general') {
            // تعديل عام
            $folderName = 'تعديل_عام';
        } else {
            // تعديل مهمة
            $taskInfo = $this->getTaskInfo($taskType, $taskId);
            $projectCode = $taskInfo['project_code'] ?? '';
            $taskName = $taskInfo['task_name'] ?? 'task_' . $taskId;

            $folderName = '';
            if ($projectCode) {
                $folderName .= $projectCode . '_';
            }
            $folderName .= $this->sanitizeForPath($taskName);
        }

        // 4. اسم الملف مع timestamp
        $timestamp = now()->format('His'); // ساعة:دقيقة:ثانية
        $cleanFileName = $this->sanitizeForPath($file->getClientOriginalName());
        $filename = $timestamp . '_' . $cleanFileName;

        // 5. بناء المسار الكامل
        $path = "task-revisions/{$employeeName}/{$date}/{$folderName}/{$filename}";

        Log::info('📁 Built organized path for revision file', [
            'type' => $taskType,
            'employee' => $employeeName,
            'date' => $date,
            'folder' => $folderName,
            'final_path' => $path
        ]);

        return $path;
    }

    /**
     * الحصول على معلومات المشروع
     */
    private function getProjectInfo($projectId): array
    {
        try {
            $project = \App\Models\Project::find($projectId);
            if ($project) {
                return [
                    'project_name' => $project->name ?? 'project_' . $projectId,
                    'project_code' => $project->code ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch project info for revision path', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'project_name' => 'project_' . $projectId,
            'project_code' => null,
        ];
    }

    /**
     * الحصول على معلومات المهمة والمشروع
     */
    private function getTaskInfo(string $taskType, $taskId): array
    {
        try {
            if ($taskType === 'template') {
                $templateTaskUser = \App\Models\TemplateTaskUser::with(['templateTask.project'])->find($taskId);
                if ($templateTaskUser) {
                    return [
                        'task_name' => $templateTaskUser->templateTask->name ?? 'template_task_' . $taskId,
                        'project_code' => $templateTaskUser->templateTask->project->code ?? null,
                    ];
                }
            } else {
                // للمهام العادية، نحاول الحصول على Task أو TaskUser
                $task = \App\Models\Task::with('project')->find($taskId);
                if (!$task) {
                    $taskUser = \App\Models\TaskUser::with('task.project')->find($taskId);
                    if ($taskUser && $taskUser->task) {
                        $task = $taskUser->task;
                    }
                }

                if ($task) {
                    return [
                        'task_name' => $task->name ?? 'task_' . $taskId,
                        'project_code' => $task->project->code ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch task info for revision path', [
                'task_type' => $taskType,
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'task_name' => 'task_' . $taskId,
            'project_code' => null,
        ];
    }

    /**
     * تنظيف النص للاستخدام في المسار
     */
    private function sanitizeForPath(string $text): string
    {
        // إزالة المسافات والرموز الخاصة
        $text = str_replace(' ', '_', $text);
        $text = preg_replace('/[^\p{Arabic}\p{L}\p{N}_-]/u', '', $text);
        // تحديد الطول الأقصى
        if (mb_strlen($text) > 50) {
            $text = mb_substr($text, 0, 50);
        }
        return $text ?: 'unnamed';
    }

    /**
     * الحصول على رابط مؤقت لعرض/تحميل الملف من Wasabi
     */
    public function getPresignedUrl(string $filePath, int $expirationMinutes = 60): string
    {
        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => $filePath
            ]);

            $request = $s3->createPresignedRequest($cmd, "+{$expirationMinutes} minutes");

            return (string) $request->getUri();

        } catch (\Exception $e) {
            Log::error('Error generating presigned URL for revision file', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('فشل إنشاء رابط التحميل');
        }
    }

    /**
     * حذف ملف من Wasabi
     */
    public function deleteRevisionFile(string $filePath): bool
    {
        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            $s3->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $filePath
            ]);

            Log::info('✅ Revision file deleted from Wasabi', [
                'path' => $filePath
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Error deleting revision file from Wasabi', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * التحقق من وجود ملف في Wasabi
     */
    public function fileExists(string $filePath): bool
    {
        try {
            $s3 = $this->getS3Client();
            $bucket = env('AWS_BUCKET');

            return $s3->doesObjectExist($bucket, $filePath);

        } catch (\Exception $e) {
            Log::error('Error checking if revision file exists', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}

