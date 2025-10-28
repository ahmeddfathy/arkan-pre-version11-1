<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\ProjectAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentService
{
    protected $storageService;

    public function __construct(ProjectStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    public function getPresignedUrl(Project $project, $fileName, $serviceType, $description, $taskData = null)
    {
        // Increase execution time for large files
        set_time_limit(300); // 5 minutes

        // فحص لمنع الرفع المكرر - التحقق من وجود ملف بنفس الاسم في نفس المشروع والخدمة
        $existingAttachment = ProjectAttachment::where('project_id', $project->id)
            ->where('service_type', $serviceType)
            ->where('file_name', $fileName)
            ->where('uploaded_by', Auth::id())
            ->where('is_uploaded', true) // التحقق فقط من الملفات المرفوعة بالفعل
            ->where('created_at', '>=', now()->subMinutes(5)) // في آخر 5 دقائق
            ->first();

        if ($existingAttachment) {
            throw new \Exception('تم رفع هذا الملف مؤخراً. يرجى الانتظار قليلاً أو تغيير اسم الملف.');
        }

        $result = $this->storageService->generatePresignedUploadUrl($project, $fileName, $serviceType);

        $attachmentData = [
            'project_id' => $project->id,
            'service_type' => $serviceType,
            'file_path' => $result['file_key'],
            'file_name' => $fileName,
            'description' => $description,
            'uploaded_by' => Auth::id(),
            'is_uploaded' => false,
        ];

        // Agregar datos de tarea si se proporcionan
        if ($taskData && isset($taskData['task_type'])) {
            $attachmentData['task_type'] = $taskData['task_type'];

            if ($taskData['task_type'] === 'template_task' && isset($taskData['task_id'])) {
                $attachmentData['template_task_user_id'] = $taskData['task_id'];
            } elseif ($taskData['task_type'] === 'regular_task' && isset($taskData['task_id'])) {
                $attachmentData['task_user_id'] = $taskData['task_id'];
            }
        }

        $attachment = ProjectAttachment::create($attachmentData);

        return [
            'upload_url' => $result['upload_url'],
            'attachment_id' => $attachment->id,
            'file_key' => $result['file_key']
        ];
    }

    public function getMultiplePresignedUrls(Project $project, array $fileNames, $serviceType, $description, $taskData = null)
    {
        $results = [];

        foreach ($fileNames as $fileName) {
            $result = $this->storageService->generatePresignedUploadUrl($project, $fileName, $serviceType);

            $attachmentData = [
                'project_id' => $project->id,
                'service_type' => $serviceType,
                'file_path' => $result['file_key'],
                'file_name' => $fileName,
                'description' => $description,
                'uploaded_by' => Auth::id(),
                'is_uploaded' => false,
            ];

            // Agregar datos de tarea si se proporcionan
            if ($taskData && isset($taskData['task_type'])) {
                $attachmentData['task_type'] = $taskData['task_type'];

                if ($taskData['task_type'] === 'template_task' && isset($taskData['task_id'])) {
                    $attachmentData['template_task_user_id'] = $taskData['task_id'];
                } elseif ($taskData['task_type'] === 'regular_task' && isset($taskData['task_id'])) {
                    $attachmentData['task_user_id'] = $taskData['task_id'];
                }
            }

            $attachment = ProjectAttachment::create($attachmentData);

            $results[] = [
                'upload_url' => $result['upload_url'],
                'attachment_id' => $attachment->id,
                'file_key' => $result['file_key'],
                'file_name' => $fileName
            ];
        }

        return $results;
    }

    public function confirmUpload($attachmentId)
    {
        $attachment = ProjectAttachment::findOrFail($attachmentId);
        $attachment->update(['is_uploaded' => true]);
        return $attachment;
    }

    public function uploadFile(Project $project, $file, $serviceType, $description, $taskData = null, $parentAttachmentId = null)
    {
        $fileName = $file->getClientOriginalName();

        // فحص لمنع الرفع المكرر - التحقق من وجود ملف بنفس الاسم في نفس المشروع والخدمة
        $existingAttachment = ProjectAttachment::where('project_id', $project->id)
            ->where('service_type', $serviceType)
            ->where('file_name', $fileName)
            ->where('uploaded_by', Auth::id())
            ->where('is_uploaded', true) // التحقق فقط من الملفات المرفوعة بالفعل
            ->where('created_at', '>=', now()->subMinutes(5)) // في آخر 5 دقائق
            ->first();

        if ($existingAttachment) {
            throw new \Exception('تم رفع هذا الملف مؤخراً. يرجى الانتظار قليلاً أو تغيير اسم الملف.');
        }

        $project->load('client');
        $clientName = $project->client ? $project->client->name : 'no-client';
        $projectCode = $project->code ? $project->code : 'no-code';

        $projectFolder = 'projects/' . $clientName . '_' . $project->name . '_' . $projectCode;

        $userName = Auth::user() ? Auth::user()->name : 'unknown-user';

        // إذا كان هذا رد على ملف، أضف بادئة "reply_"
        $filePrefix = $parentAttachmentId ? 'reply_' : '';
        $fileKey = $projectFolder . '/' . $serviceType . '/' . $filePrefix . $userName . '_' . $fileName;

        Storage::disk('s3')->put($fileKey, file_get_contents($file), 'public');

        $attachmentData = [
            'project_id' => $project->id,
            'service_type' => $serviceType,
            'file_path' => $fileKey,
            'file_name' => $fileName,
            'description' => $description,
            'uploaded_by' => Auth::id(),
            'is_uploaded' => true,
            'parent_attachment_id' => $parentAttachmentId,
        ];

        // إضافة بيانات المهمة
        if ($taskData && isset($taskData['task_type'])) {
            // إعطاء أولوية لبيانات المهمة المرسلة من المستخدم
            $attachmentData['task_type'] = $taskData['task_type'];

            if ($taskData['task_type'] === 'template_task' && isset($taskData['task_id'])) {
                $attachmentData['template_task_user_id'] = $taskData['task_id'];
            } elseif ($taskData['task_type'] === 'regular_task' && isset($taskData['task_id'])) {
                $attachmentData['task_user_id'] = $taskData['task_id'];
            }
        } elseif ($parentAttachmentId) {
            // إذا كان هذا رد ولم يتم تحديد مهمة، نسخ معلومات المهمة من الملف الأصلي
            $parentAttachment = ProjectAttachment::find($parentAttachmentId);
            if ($parentAttachment) {
                $attachmentData['task_type'] = $parentAttachment->task_type;
                $attachmentData['template_task_user_id'] = $parentAttachment->template_task_user_id;
                $attachmentData['task_user_id'] = $parentAttachment->task_user_id;
            }
        }

        return ProjectAttachment::create($attachmentData);
    }

    public function getViewUrl($attachmentId)
    {
        try {
            $attachment = ProjectAttachment::findOrFail($attachmentId);
            $url = $this->storageService->generatePresignedViewUrl($attachment->file_path);

            if (!$url) {
                \Log::warning("Failed to generate view URL for attachment: {$attachmentId}");
                return null;
            }

            return $url;
        } catch (\Exception $e) {
            \Log::error("Error getting view URL for attachment {$attachmentId}: " . $e->getMessage());
            return null;
        }
    }

    public function getDownloadUrl($attachmentId)
    {
        try {
            $attachment = ProjectAttachment::findOrFail($attachmentId);
            $url = $this->storageService->generatePresignedDownloadUrl($attachment->file_path, $attachment->file_name);

            if (!$url) {
                \Log::warning("Failed to generate download URL for attachment: {$attachmentId}");
                return null;
            }

            return $url;
        } catch (\Exception $e) {
            \Log::error("Error getting download URL for attachment {$attachmentId}: " . $e->getMessage());
            return null;
        }
    }




    /**
     * رفع رد على ملف موجود
     */
    public function uploadReply(Project $project, $file, $parentAttachmentId, $description = null, $taskData = null)
    {
        $parentAttachment = ProjectAttachment::findOrFail($parentAttachmentId);

        // التأكد من أن الملف الأصلي ينتمي لنفس المشروع
        if ($parentAttachment->project_id !== $project->id) {
            throw new \Exception('الملف الأصلي لا ينتمي لهذا المشروع');
        }

        return $this->uploadFile(
            $project,
            $file,
            $parentAttachment->service_type,
            $description,
            $taskData,
            $parentAttachmentId
        );
    }
}
