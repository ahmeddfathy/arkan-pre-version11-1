<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Services\ProjectManagement\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectAttachmentManagementService
{
    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }


    public function getPresignedUrl(Request $request, $projectId)
    {
        $validationRules = [
            'file_name' => 'required|string',
            'service_type' => 'required|string',
            'description' => 'nullable|string',
            'task_type' => 'nullable|in:template_task,regular_task',
            'task_id' => 'nullable|integer',
        ];

        $request->validate($validationRules);

        $project = Project::findOrFail($projectId);

        $taskData = null;
        if ($request->task_type && $request->task_id) {
            $taskData = [
                'task_type' => $request->task_type,
                'task_id' => $request->task_id
            ];
        }

        $result = $this->attachmentService->getPresignedUrl(
            $project,
            $request->file_name,
            $request->service_type,
            $request->description,
            $taskData
        );

        return $result;
    }


    public function getMultiplePresignedUrls(Request $request, $projectId)
    {
        $validationRules = [
            'file_names' => 'required|array',
            'file_names.*' => 'required|string',
            'service_type' => 'required|string',
            'description' => 'nullable|string',
            'task_type' => 'nullable|in:template_task,regular_task',
            'task_id' => 'nullable|integer',
        ];

        $request->validate($validationRules);

        $project = Project::findOrFail($projectId);

        $taskData = null;
        if ($request->task_type && $request->task_id) {
            $taskData = [
                'task_type' => $request->task_type,
                'task_id' => $request->task_id
            ];
        }

        $result = $this->attachmentService->getMultiplePresignedUrls(
            $project,
            $request->file_names,
            $request->service_type,
            $request->description,
            $taskData
        );

        return [
            'success' => true,
            'files' => $result
        ];
    }

    /**
     * Confirm file upload completion
     */
    public function confirmUpload(Request $request, $attachmentId)
    {
        $attachment = $this->attachmentService->confirmUpload($attachmentId);

        // استخراج معلومات المهمة إن وجدت
        $taskInfo = null;
        if ($attachment->task_type === 'template_task' && $attachment->template_task_user_id) {
            $taskInfo = [
                'type' => 'template_task',
                'id' => $attachment->template_task_user_id,
                'name' => optional(optional($attachment->templateTaskUser)->templateTask)->name
            ];
        } elseif ($attachment->task_type === 'regular_task' && $attachment->task_user_id) {
            $taskInfo = [
                'type' => 'regular_task',
                'id' => $attachment->task_user_id,
                'name' => optional(optional($attachment->taskUser)->task)->title
            ];
        }

        return [
            'success' => true,
            'attachment' => [
                'id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'service_type' => $attachment->service_type,
                'description' => $attachment->description,
                'file_path' => $attachment->file_path,
                'uploaded_by' => $attachment->uploaded_by,
                'created_at' => $attachment->created_at,
                'task_type' => $attachment->task_type,
                'task' => $taskInfo
            ]
        ];
    }


    public function viewAttachment($id)
    {
        try {
            $presignedUrl = $this->attachmentService->getViewUrl($id);

            if ($presignedUrl) {
                return [
                    'success' => true,
                    'redirect_url' => $presignedUrl
                ];
            }

            return [
                'success' => false,
                'message' => 'الملف غير موجود أو لا يمكن الوصول إليه',
                'status_code' => 404
            ];
        } catch (\Exception $e) {
            \Log::error("Error viewing attachment {$id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء محاولة عرض الملف',
                'status_code' => 500
            ];
        }
    }


    /**
     * Get attachment download URL
     */
    public function downloadAttachment($id)
    {
        $presignedUrl = $this->attachmentService->getDownloadUrl($id);

        if ($presignedUrl) {
            return [
                'success' => true,
                'redirect_url' => $presignedUrl
            ];
        }

        return [
            'success' => false,
            'message' => 'الملف غير موجود',
            'status_code' => 404
        ];
    }

    /**
     * Get validation rules for presigned URL request
     */
    public function getPresignedUrlValidationRules()
    {
        return [
            'file_name' => 'required|string',
            'service_type' => 'required|string',
            'description' => 'nullable|string',
            'task_type' => 'nullable|in:template_task,regular_task',
            'task_id' => 'nullable|integer',
        ];
    }

    /**
     * Get validation rules for multiple presigned URLs request
     */
    public function getMultiplePresignedUrlsValidationRules()
    {
        return [
            'file_names' => 'required|array',
            'file_names.*' => 'required|string',
            'service_type' => 'required|string',
            'description' => 'nullable|string',
            'task_type' => 'nullable|in:template_task,regular_task',
            'task_id' => 'nullable|integer',
        ];
    }

    /**
     * Prepare task data from request
     */
    public function prepareTaskData(Request $request)
    {
        if (!$request->task_type || !$request->task_id) {
            return null;
        }

        return [
            'task_type' => $request->task_type,
            'task_id' => $request->task_id
        ];
    }

    /**
     * Format attachment response
     */
    public function formatAttachmentResponse($attachment)
    {
        $taskInfo = $this->extractTaskInfo($attachment);

        return [
            'id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'service_type' => $attachment->service_type,
            'description' => $attachment->description,
            'file_path' => $attachment->file_path,
            'uploaded_by' => $attachment->uploaded_by,
            'created_at' => $attachment->created_at,
            'task_type' => $attachment->task_type,
            'task' => $taskInfo
        ];
    }

    /**
     * Extract task information from attachment
     */
    private function extractTaskInfo($attachment)
    {
        if ($attachment->task_type === 'template_task' && $attachment->template_task_user_id) {
            return [
                'type' => 'template_task',
                'id' => $attachment->template_task_user_id,
                'name' => optional(optional($attachment->templateTaskUser)->templateTask)->name
            ];
        } elseif ($attachment->task_type === 'regular_task' && $attachment->task_user_id) {
            return [
                'type' => 'regular_task',
                'id' => $attachment->task_user_id,
                'name' => optional(optional($attachment->taskUser)->task)->title
            ];
        }

        return null;
    }
}
