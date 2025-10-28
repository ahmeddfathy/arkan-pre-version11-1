<?php

namespace App\Http\Controllers\Traits\Projects;

use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\TemplateTaskUser;
use App\Models\TaskUser;;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


trait ProjectAttachmentsTrait
{
    protected $attachmentManagementService;
    protected $attachmentUploadService;
    protected $attachmentReplyService;
    protected $attachmentSharingService;
    protected $attachmentSharingHandlerService;
    protected $authorizationService;

    /**
     * الحصول على رابط مرفق واحد
     */
    public function getPresignedUrl(Request $request, $projectId)
    {
        $result = $this->attachmentManagementService->getPresignedUrl($request, $projectId);
        return response()->json($result);
    }

    /**
     * الحصول على روابط متعددة للمرفقات
     */
    public function getMultiplePresignedUrls(Request $request, $projectId)
    {
        $result = $this->attachmentManagementService->getMultiplePresignedUrls($request, $projectId);
        return response()->json($result);
    }

    /**
     * تأكيد رفع المرفق
     */
    public function confirmUpload(Request $request, $attachmentId)
    {
        $result = $this->attachmentManagementService->confirmUpload($request, $attachmentId);
        return response()->json($result);
    }

    /**
     * رفع مرفق جديد
     */
    public function storeAttachment(Request $request, $projectId)
    {
        $validationRules = $this->attachmentUploadService->validateUploadData($request->all());
        $request->validate($validationRules);

        $project = Project::findOrFail($projectId);

        // فحص أمان: التأكد من أن المستخدم مشارك في المشروع
        $currentUser = Auth::user();
        $isParticipant = DB::table('project_service_user')
            ->where('project_id', $project->id)
            ->where('user_id', $currentUser->id)
            ->exists();

        if (!$isParticipant && !$this->authorizationService->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك برفع ملفات في هذا المشروع'
            ], 403);
        }

        // فحص إضافي: إذا كان يختار مهمة، تأكد أنها مهمته وفي خدمته
        if ($request->filled('template_task_id') || $request->filled('task_user_id')) {
            $userServiceIds = DB::table('project_service_user')
                ->where('project_id', $project->id)
                ->where('user_id', $currentUser->id)
                ->pluck('service_id')
                ->toArray();

            if ($request->filled('template_task_id')) {
                $templateTask = TemplateTaskUser::with('templateTask.template')
                    ->where('id', $request->template_task_id)
                    ->where('user_id', $currentUser->id)
                    ->where('project_id', $project->id)
                    ->first();

                if (!$templateTask || !in_array($templateTask->templateTask->template->service_id, $userServiceIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مسموح لك بربط الملف بهذه المهمة'
                    ], 403);
                }
            }

            if ($request->filled('task_user_id')) {
                $regularTask = TaskUser::with('task')
                    ->where('id', $request->task_user_id)
                    ->where('user_id', $currentUser->id)
                    ->first();

                if (!$regularTask || !in_array($regularTask->task->service_id, $userServiceIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مسموح لك بربط الملف بهذه المهمة'
                    ], 403);
                }
            }
        }

        try {
            $result = $this->attachmentUploadService->processAttachmentUpload(
                $project,
                $request->file('attachment'),
                $request->all()
            );

            return response()->json($this->attachmentUploadService->formatResponse($result));
        } catch (\Exception $e) {
            return response()->json($this->attachmentUploadService->formatResponse([
                'message' => $e->getMessage()
            ], false), 500);
        }
    }

    /**
     * عرض مرفق
     */
    public function viewAttachment($id)
    {
        $result = $this->attachmentManagementService->viewAttachment($id);

        if ($result['success']) {
            return redirect($result['redirect_url']);
        }

        abort($result['status_code'], $result['message']);
    }

    /**
     * تحميل مرفق
     */
    public function downloadAttachment($id)
    {
        $result = $this->attachmentManagementService->downloadAttachment($id);

        if ($result['success']) {
            return redirect($result['redirect_url']);
        }

        abort($result['status_code'], $result['message']);
    }

    /**
     * مشاركة مرفقات مع مستخدمين
     */
    public function shareAttachments(Request $request)
    {
        $request->validate([
            'attachment_ids' => 'required|array',
            'attachment_ids.*' => 'required|integer|exists:project_attachments,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|integer|exists:users,id',
            'expires_in_hours' => 'nullable|integer|min:1|max:8760',
            'description' => 'nullable|string|max:500'
        ]);

        $result = $this->attachmentSharingService->shareAttachments(
            $request->attachment_ids,
            $request->user_ids,
            [
                'expires_in_hours' => $request->expires_in_hours,
                'description' => $request->description
            ]
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'تم مشاركة الملفات بنجاح',
                'data' => [
                    'access_token' => $result['access_token'],
                    'share_url' => $result['share_url'],
                    'shared_attachments_count' => $result['shared_attachments_count'],
                    'shared_with_count' => $result['shared_with_count'],
                    'expires_at' => $result['expires_at']
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }

    /**
     * جلب المستخدمين المتاحين للمشاركة
     */
    public function getAvailableUsersForSharing(Project $project)
    {
        try {
            $usersData = $this->attachmentSharingHandlerService->getAvailableUsersForSharing($project);

            return response()->json([
                'success' => true,
                ...$usersData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المستخدمين'
            ], 500);
        }
    }

    /**
     * عرض المرفقات المشتركة
     */
    public function viewSharedAttachments($token)
    {
        $result = $this->attachmentSharingHandlerService->handleViewSharedAttachments($token, request()->ajax());

        if (!$result['success']) {
            if (isset($result['status_code'])) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], $result['status_code']);
            }

            return view($result['view'], $result['data']);
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                ...$result['data']
            ]);
        }

        return view($result['view'], $result['data']);
    }

    /**
     * تحميل مرفق مشترك
     */
    public function downloadSharedAttachment($token, $attachmentId)
    {
        try {
            $presignedUrl = $this->attachmentSharingHandlerService->handleDownloadSharedAttachment($token, $attachmentId);
            return redirect($presignedUrl);
        } catch (\Exception $e) {
            abort($e->getMessage() === 'المرفق غير موجود في هذه المشاركة' ? 404 : 403, $e->getMessage());
        }
    }

    /**
     * إلغاء مشاركة بالمعرف
     */
    public function cancelShare(Request $request, $shareId)
    {
        $result = $this->attachmentSharingHandlerService->cancelShare($shareId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * إلغاء مشاركة بالتوكن
     */
    public function cancelShareByToken(Request $request, $token)
    {
        $result = $this->attachmentSharingHandlerService->cancelShareByToken($token);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * جلب مشاركات المستخدم
     */
    public function getUserShares(Request $request)
    {
        $type = $request->get('type', 'sent');
        return response()->json($this->attachmentSharingHandlerService->getUserShares($type));
    }

    /**
     * جلب مشاركات مرفق معين
     */
    public function getAttachmentShares($attachmentId)
    {
        try {
            return response()->json($this->attachmentSharingHandlerService->getAttachmentShares($attachmentId));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المشاركات'
            ], 500);
        }
    }

    /**
     * جلب إحصائيات المشاركة
     */
    public function getShareStatistics()
    {
        try {
            return response()->json($this->attachmentSharingHandlerService->getShareStatistics());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }

    /**
     * جلب ردود المرفق
     */
    public function getAttachmentReplies($attachmentId)
    {
        try {
            $result = $this->attachmentReplyService->getAttachmentReplies($attachmentId);

            $this->authorize('view', $result['attachment']->project);

            return response()->json([
                'success' => true,
                'replies' => $result['replies']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الردود'
            ], 500);
        }
    }

    /**
     * عرض صفحة مشاركات المرفقات
     */
    public function attachmentSharesIndex(Request $request)
    {
        $type = $request->get('type', 'received');
        $data = $this->attachmentSharingHandlerService->getAttachmentSharesIndex($type);

        return view('projects.attachment-shares.index', $data);
    }
}
