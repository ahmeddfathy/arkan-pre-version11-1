<?php

namespace App\Services\ProjectManagement;

use App\Models\ProjectAttachment;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ProjectAttachmentReplyService
{
    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * جلب الردود على مرفق معين
     */
    public function getAttachmentReplies($attachmentId)
    {
        $attachment = ProjectAttachment::with('project')->findOrFail($attachmentId);

        // جلب الردود
        $replies = ProjectAttachment::where('parent_attachment_id', $attachmentId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($reply) {
                return [
                    'id' => $reply->id,
                    'file_name' => $reply->file_name,
                    'description' => $reply->description,
                    'uploaded_by_name' => optional($reply->user)->name ?? 'مستخدم غير معروف',
                    'created_at' => $reply->created_at->format('Y-m-d H:i:s'),
                    'file_path' => $reply->file_path
                ];
            });

        return [
            'attachment' => $attachment,
            'replies' => $replies
        ];
    }


    /**
     * إضافة رد على مرفق
     */
    public function addReplyToAttachment(Project $project, $file, $parentAttachmentId, $description = null, $taskData = null)
    {
        // التحقق من وجود المرفق الأصلي
        $parentAttachment = ProjectAttachment::findOrFail($parentAttachmentId);

        if ($parentAttachment->project_id !== $project->id) {
            throw new \Exception('المرفق لا ينتمي لهذا المشروع');
        }

        // إعداد بيانات المهمة للرد
        $taskData = $taskData ?: [
            'task_type' => $parentAttachment->task_type,
            'task_id' => $parentAttachment->task_type === 'template_task'
                ? $parentAttachment->template_task_user_id
                : $parentAttachment->task_user_id
        ];

        // رفع الرد
        $reply = $this->attachmentService->uploadReply(
            $project,
            $file,
            $parentAttachmentId,
            $description,
            $taskData
        );

        return $reply;
    }

    /**
     * الحصول على عدد الردود لمرفق معين
     */
    public function getAttachmentRepliesCount($attachmentId)
    {
        return ProjectAttachment::where('parent_attachment_id', $attachmentId)->count();
    }

    /**
     * الحصول على آخر رد لمرفق معين
     */
    public function getLastReply($attachmentId)
    {
        return ProjectAttachment::where('parent_attachment_id', $attachmentId)
            ->with('user')
            ->latest()
            ->first();
    }

    /**
     * التحقق من وجود ردود لمرفق معين
     */
    public function hasReplies($attachmentId)
    {
        return ProjectAttachment::where('parent_attachment_id', $attachmentId)->exists();
    }

    /**
     * جلب إحصائيات الردود لمشروع معين
     */
    public function getProjectRepliesStats(Project $project)
    {
        return [
            'total_replies' => ProjectAttachment::whereNotNull('parent_attachment_id')
                ->whereHas('project', function($query) use ($project) {
                    $query->where('id', $project->id);
                })
                ->count(),

            'attachments_with_replies' => ProjectAttachment::whereNull('parent_attachment_id')
                ->where('project_id', $project->id)
                ->whereHas('replies')
                ->count(),

            'most_replied_attachment' => ProjectAttachment::whereNull('parent_attachment_id')
                ->where('project_id', $project->id)
                ->withCount('replies')
                ->orderByDesc('replies_count')
                ->first()
        ];
    }
}
