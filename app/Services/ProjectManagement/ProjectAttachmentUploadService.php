<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Services\ProjectManagement\AttachmentService;
use Illuminate\Http\UploadedFile;

class ProjectAttachmentUploadService
{
    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Process attachment upload (regular file or reply)
     */
    public function processAttachmentUpload(Project $project, UploadedFile $file, array $data)
    {
        $isReply = isset($data['is_reply']) && ($data['is_reply'] == '1' || $data['is_reply'] === true);

        if ($isReply) {
            return $this->handleReplyUpload($project, $file, $data);
        }

        return $this->handleRegularUpload($project, $file, $data);
    }

    /**
     * Handle reply upload
     */
    private function handleReplyUpload(Project $project, UploadedFile $file, array $data)
    {
        if (empty($data['parent_attachment_id'])) {
            throw new \Exception('يجب اختيار ملف للرد عليه');
        }

        // إعداد بيانات المهمة للرد
        $taskData = $this->prepareTaskData($data);

        $reply = $this->attachmentService->uploadReply(
            $project,
            $file,
            $data['parent_attachment_id'],
            $data['description'] ?? null,
            $taskData
        );

        return [
            'success' => true,
            'message' => 'تم رفع الرد بنجاح',
            'attachment' => $reply
        ];
    }

    /**
     * Handle regular file upload
     */
    private function handleRegularUpload(Project $project, UploadedFile $file, array $data)
    {
        if (empty($data['service_type'])) {
            throw new \Exception('نوع الخدمة مطلوب');
        }

        $taskData = $this->prepareTaskData($data);

        $attachment = $this->attachmentService->uploadFile(
            $project,
            $file,
            $data['service_type'],
            $data['description'] ?? null,
            $taskData
        );

        return [
            'success' => true,
            'message' => 'تم رفع المرفق بنجاح',
            'attachment' => $attachment
        ];
    }

    /**
     * Prepare task data from request
     */
    private function prepareTaskData(array $data)
    {
        if (empty($data['task_type'])) {
            return null;
        }

        $taskId = null;
        if ($data['task_type'] === 'template_task' && !empty($data['template_task_id'])) {
            $taskId = $data['template_task_id'];
        } elseif ($data['task_type'] === 'regular_task' && !empty($data['task_user_id'])) {
            $taskId = $data['task_user_id'];
        }

        return $taskId ? [
            'task_type' => $data['task_type'],
            'task_id' => $taskId
        ] : null;
    }

    /**
     * Validate upload request data
     */
    public function validateUploadData(array $data, bool $isFileRequired = true)
    {
        $currentUserId = auth()->id();

        $rules = [
            'service_type' => 'required|string',
            'description' => 'nullable|string',
            'task_type' => 'nullable|in:template_task,regular_task',
            'template_task_id' => [
                'nullable',
                'exists:template_task_user,id',
                function ($attribute, $value, $fail) use ($currentUserId) {
                    if ($value) {
                        $task = \App\Models\TemplateTaskUser::where('id', $value)
                            ->where('user_id', $currentUserId)
                            ->first();
                        if (!$task) {
                            $fail('لا يمكنك ربط الملف بمهمة ليست خاصة بك.');
                        }
                    }
                },
            ],
            'task_user_id' => [
                'nullable',
                'exists:task_users,id',
                function ($attribute, $value, $fail) use ($currentUserId) {
                    if ($value) {
                        $task = \App\Models\TaskUser::where('id', $value)
                            ->where('user_id', $currentUserId)
                            ->first();
                        if (!$task) {
                            $fail('لا يمكنك ربط الملف بمهمة ليست خاصة بك.');
                        }
                    }
                },
            ],
            'is_reply' => 'nullable|in:1,0,true,false',
            'parent_attachment_id' => 'nullable|exists:project_attachments,id',
        ];

        if ($isFileRequired) {
            $rules['attachment'] = 'required|file';
        }

        return $rules;
    }

    /**
     * Get upload response format
     */
    public function formatResponse(array $result, bool $success = true)
    {
        if (!$success) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'حدث خطأ أثناء رفع الملف'
            ];
        }

        return [
            'success' => true,
            'message' => $result['message'],
            'data' => $result['attachment'] ?? null
        ];
    }
}
