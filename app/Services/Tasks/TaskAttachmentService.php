<?php

namespace App\Services\Tasks;

use App\Models\TaskUser;
use App\Models\TaskAttachment;
use App\Services\Tasks\TaskStorageService;
use App\Traits\HasNTPTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class TaskAttachmentService
{
    use HasNTPTime;

    protected $storageService;

    public function __construct(TaskStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * البحث عن TaskUser بطريقة ذكية
     */
    protected function findTaskUser($taskUserId)
    {
        // محاولة العثور على TaskUser - أولاً بالـ ID مباشرة
        $taskUser = TaskUser::find($taskUserId);

        if (!$taskUser) {
            Log::info('TaskUser not found by ID in TaskAttachmentService, trying to find by task_id', [
                'task_user_id' => $taskUserId
            ]);

            // البحث عن المهمة بـ task_id
            $task = \App\Models\Task::find($taskUserId);
            if ($task) {
                $currentUser = \Illuminate\Support\Facades\Auth::user();
                $currentUserId = $currentUser ? $currentUser->id : null;

                Log::info('Found task by ID in TaskAttachmentService, looking for TaskUser', [
                    'task_id' => $taskUserId,
                    'task_name' => $task->name,
                    'current_user_id' => $currentUserId
                ]);

                // البحث عن TaskUser للمستخدم الحالي
                $taskUser = TaskUser::where('task_id', $taskUserId)
                    ->where('user_id', $currentUserId)
                    ->first();

                if (!$taskUser) {
                    // إذا لم نجد للمستخدم الحالي، نجرب أي مستخدم (للإداريين فقط)
                    if ($currentUser && ($currentUser->hasRole('hr') || $currentUser->hasRole('admin'))) {
                        $taskUser = TaskUser::where('task_id', $taskUserId)->first();
                    }
                }
            }

            if (!$taskUser) {
                Log::error('TaskUser not found in TaskAttachmentService', [
                    'task_user_id' => $taskUserId
                ]);


                return null;
            }
        }

        return $taskUser;
    }

    /**
     * إنشاء presigned URL لرفع مرفق
     */
    public function getPresignedUrl($taskUserId, $fileName, $description = null)
    {
        try {
            $taskUser = $this->findTaskUser($taskUserId);

            if (!$taskUser) {
                return [
                    'success' => false,
                    'message' => 'المهمة غير موجودة أو غير مُعيَّنة لك'
                ];
            }

            // التحقق من الصلاحيات
            $this->validateTaskAccess($taskUser);

            // التأكد من أن المهمة غير مرتبطة بمشروع
            if ($taskUser->project_id) {
                throw new \Exception('هذه المهمة مرتبطة بمشروع، استخدم نظام مرفقات المشاريع');
            }

            // إنشاء presigned URL
            $result = $this->storageService->generatePresignedUploadUrl($taskUser, $fileName);

            // إنشاء سجل المرفق في قاعدة البيانات
            $attachment = TaskAttachment::create([
                'task_user_id' => $taskUserId,
                'file_path' => $result['file_key'],
                'file_name' => $result['file_name'],
                'original_name' => $fileName,
                'description' => $description,
                'uploaded_by' => Auth::id(),
                'is_uploaded' => false,
            ]);

            Log::info('Generated presigned URL for task attachment', [
                'task_user_id' => $taskUserId,
                'attachment_id' => $attachment->id,
                'file_name' => $fileName
            ]);

            return [
                'success' => true,
                'upload_url' => $result['upload_url'],
                'attachment_id' => $attachment->id,
                'file_key' => $result['file_key']
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate presigned URL for task attachment', [
                'task_user_id' => $taskUserId,
                'file_name' => $fileName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * تأكيد اكتمال رفع المرفق
     */
    public function confirmUpload($attachmentId)
    {
        try {
            $attachment = TaskAttachment::findOrFail($attachmentId);

            // التحقق من الصلاحيات
            if ($attachment->uploaded_by !== Auth::id()) {
                throw new \Exception('غير مسموح لك بتأكيد هذا الرفع');
            }

            // الحصول على معلومات الملف من S3
            $fileInfo = $this->storageService->getFileInfo($attachment->file_path);

            if (!$fileInfo) {
                throw new \Exception('الملف غير موجود في التخزين');
            }

            // تحديث سجل المرفق
            $attachment->update([
                'is_uploaded' => true,
                'file_size' => $fileInfo['size'],
                'mime_type' => $fileInfo['content_type']
            ]);

            Log::info('Confirmed task attachment upload', [
                'attachment_id' => $attachmentId,
                'file_size' => $fileInfo['size']
            ]);

            return [
                'success' => true,
                'attachment' => $attachment->load(['taskUser.task', 'taskUser.user', 'uploader'])
            ];

        } catch (\Exception $e) {
            Log::error('Failed to confirm task attachment upload', [
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * رفع ملف مباشر (بديل عن presigned URL)
     */
    public function uploadFile($taskUserId, UploadedFile $file, $description = null)
    {
        try {
            DB::beginTransaction();

            $taskUser = $this->findTaskUser($taskUserId);

            if (!$taskUser) {
                return [
                    'success' => false,
                    'message' => 'المهمة غير موجودة أو غير مُعيَّنة لك'
                ];
            }

            // التحقق من الصلاحيات
            $this->validateTaskAccess($taskUser);

            // التأكد من أن المهمة غير مرتبطة بمشروع
            if ($taskUser->project_id) {
                throw new \Exception('هذه المهمة مرتبطة بمشروع، استخدم نظام مرفقات المشاريع');
            }

            // إنشاء مسار الملف
            $filePath = TaskAttachment::generateFilePath($taskUser, $file->getClientOriginalName());

            // رفع الملف إلى S3
            $uploaded = $file->storeAs('', $filePath, 's3');

            if (!$uploaded) {
                throw new \Exception('فشل في رفع الملف');
            }

            // إنشاء سجل المرفق
            $attachment = TaskAttachment::create([
                'task_user_id' => $taskUserId,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'original_name' => $file->getClientOriginalName(),
                'description' => $description,
                'uploaded_by' => Auth::id(),
                'is_uploaded' => true,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            DB::commit();

            Log::info('Uploaded task attachment directly', [
                'task_user_id' => $taskUserId,
                'attachment_id' => $attachment->id,
                'file_size' => $file->getSize()
            ]);

            return [
                'success' => true,
                'attachment' => $attachment->load(['taskUser.task', 'taskUser.user', 'uploader'])
            ];

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to upload task attachment directly', [
                'task_user_id' => $taskUserId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على مرفقات المهمة
     */
    public function getTaskAttachments($taskUserId)
    {
        try {
            Log::info('TaskAttachmentService::getTaskAttachments called', [
                'task_user_id' => $taskUserId
            ]);

            // البحث عن TaskUser باستخدام الدالة المساعدة
            $taskUser = $this->findTaskUser($taskUserId);

            if (!$taskUser) {
                // للمهام غير المُعيَّنة، نعيد مرفقات فارغة مع رسالة تحذيرية
                Log::info('TaskAttachmentService: Task is unassigned, returning empty attachments', [
                    'task_user_id' => $taskUserId
                ]);

                return [
                    'success' => true, // نجعلها true لكن بدون مرفقات
                    'message' => 'هذه المهمة غير مُعيَّنة لأي مستخدم',
                    'attachments' => collect(),
                    'task_user' => null,
                    'is_unassigned' => true
                ];
            }

            // التحقق من الصلاحيات
            $this->validateTaskAccess($taskUser);

            $attachments = TaskAttachment::getAttachmentsForTask($taskUser->id);

            Log::info('TaskAttachmentService::getTaskAttachments found attachments', [
                'task_user_id' => $taskUserId,
                'found_task_user_id' => $taskUser->id,
                'attachments_count' => $attachments ? $attachments->count() : 0
            ]);

            return [
                'success' => true,
                'attachments' => $attachments,
                'task_user' => $taskUser->load(['task', 'user'])
            ];

        } catch (\Exception $e) {
            Log::error('Error in TaskAttachmentService::getTaskAttachments', [
                'task_user_id' => $taskUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * حذف مرفق
     */
    public function deleteAttachment($attachmentId)
    {
        try {
            $attachment = TaskAttachment::findOrFail($attachmentId);

            // التحقق من الصلاحيات
            if ($attachment->uploaded_by !== Auth::id() && !$this->canDeleteAnyAttachment()) {
                throw new \Exception('غير مسموح لك بحذف هذا المرفق');
            }

            // حذف الملف من S3
            $this->storageService->deleteFile($attachment->file_path);

            // حذف السجل من قاعدة البيانات
            $attachment->delete();

            Log::info('Deleted task attachment', [
                'attachment_id' => $attachmentId,
                'file_path' => $attachment->file_path
            ]);

            return [
                'success' => true,
                'message' => 'تم حذف المرفق بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to delete task attachment', [
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على رابط عرض المرفق
     */
    public function getViewUrl($attachmentId)
    {
        try {
            $attachment = TaskAttachment::findOrFail($attachmentId);

            // التحقق من الصلاحيات
            $this->validateAttachmentAccess($attachment);

            $url = $this->storageService->generatePresignedViewUrl($attachment->file_path);

            if (!$url) {
                throw new \Exception('الملف غير موجود');
            }

            return [
                'success' => true,
                'url' => $url
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على رابط تحميل المرفق
     */
    public function getDownloadUrl($attachmentId)
    {
        try {
            $attachment = TaskAttachment::findOrFail($attachmentId);

            // التحقق من الصلاحيات
            $this->validateAttachmentAccess($attachment);

            $url = $this->storageService->generatePresignedDownloadUrl(
                $attachment->file_path,
                $attachment->original_name
            );

            if (!$url) {
                throw new \Exception('الملف غير موجود');
            }

            return [
                'success' => true,
                'url' => $url
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على إحصائيات مرفقات المستخدم
     */
    public function getUserAttachmentStats($userId = null)
    {
        $userId = $userId ?? Auth::id();

        try {
            // إحصائيات قاعدة البيانات
            $dbStats = TaskAttachment::where('uploaded_by', $userId)
                ->selectRaw('
                    COUNT(*) as total_attachments,
                    SUM(file_size) as total_size,
                    MAX(file_size) as largest_file,
                    MAX(created_at) as latest_upload
                ')
                ->first();

            // إحصائيات التخزين
            $user = \App\Models\User::findOrFail($userId);
            $storageStats = $this->storageService->getEmployeeStorageStats($user->name);

            return [
                'success' => true,
                'stats' => [
                    'total_attachments' => $dbStats->total_attachments ?? 0,
                    'total_size' => $dbStats->total_size ?? 0,
                    'largest_file' => $dbStats->largest_file ?? 0,
                    'latest_upload' => $dbStats->latest_upload,
                    'storage_stats' => $storageStats
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * التحقق من صلاحيات الوصول للمهمة
     */
    protected function validateTaskAccess(TaskUser $taskUser)
    {
        $user = Auth::user();

        // صاحب المهمة يمكنه الوصول
        if ($taskUser->user_id === $user->id) {
            return true;
        }

        // المديرين يمكنهم الوصول
        if ($this->canAccessAnyTask()) {
            return true;
        }

        throw new \Exception('غير مسموح لك بالوصول لهذه المهمة');
    }

    /**
     * التحقق من صلاحيات الوصول للمرفق
     */
    protected function validateAttachmentAccess(TaskAttachment $attachment)
    {
        $this->validateTaskAccess($attachment->taskUser);
    }

    /**
     * التحقق من إمكانية الوصول لأي مهمة
     */
    protected function canAccessAnyTask()
    {
        $user = Auth::user();
        return $user && $user->hasRole(['hr', 'company_manager', 'project_manager']);
    }

    /**
     * التحقق من إمكانية حذف أي مرفق
     */
    protected function canDeleteAnyAttachment()
    {
        $user = Auth::user();
        return $user && $user->hasRole(['hr', 'company_manager']);
    }

    /**
     * تنظيف المرفقات غير المكتملة (التي لم يتم تأكيد رفعها)
     */
    public function cleanupIncompleteUploads($olderThanHours = 24)
    {
        try {
            $cutoffTime = $this->getCurrentCairoTime()->subHours($olderThanHours);

            $incompleteAttachments = TaskAttachment::where('is_uploaded', false)
                ->where('created_at', '<', $cutoffTime)
                ->get();

            $deletedCount = 0;
            foreach ($incompleteAttachments as $attachment) {
                try {
                    $this->storageService->deleteFile($attachment->file_path);
                    $attachment->delete();
                    $deletedCount++;
                } catch (\Exception $e) {
                    Log::warning('Failed to cleanup incomplete attachment', [
                        'attachment_id' => $attachment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Cleaned up incomplete task attachments', [
                'deleted_count' => $deletedCount,
                'cutoff_time' => $cutoffTime
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount
            ];

        } catch (\Exception $e) {
            Log::error('Failed to cleanup incomplete uploads', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
