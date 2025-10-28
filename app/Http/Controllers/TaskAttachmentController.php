<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Tasks\TaskAttachmentService;
use App\Models\TaskUser;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskAttachmentController extends Controller
{
    protected $attachmentService;

    public function __construct(TaskAttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
        $this->middleware('auth');
    }

    /**
     * الحصول على presigned URL لرفع مرفق
     */
    public function getPresignedUrl(Request $request)
    {
        $request->validate([
            'task_user_id' => 'required|exists:task_users,id',
            'file_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $result = $this->attachmentService->getPresignedUrl(
                $request->task_user_id,
                $request->file_name,
                $request->description
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'upload_url' => $result['upload_url'],
                        'attachment_id' => $result['attachment_id'],
                        'file_key' => $result['file_key']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error in getPresignedUrl', [
                'error' => $e->getMessage(),
                'task_user_id' => $request->task_user_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء رابط الرفع'
            ], 500);
        }
    }

    /**
     * تأكيد اكتمال رفع المرفق
     */
    public function confirmUpload(Request $request, $attachmentId)
    {
        Log::info('TaskAttachmentController::confirmUpload called', [
            'attachment_id' => $attachmentId,
            'user_id' => Auth::id()
        ]);

        try {
            $result = $this->attachmentService->confirmUpload($attachmentId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'attachment' => $result['attachment']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error in confirmUpload', [
                'error' => $e->getMessage(),
                'attachment_id' => $attachmentId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تأكيد الرفع'
            ], 500);
        }
    }

    /**
     * رفع ملف مباشر
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'task_user_id' => 'required|exists:task_users,id',
            'file' => 'required|file|max:50240', // 50MB
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $result = $this->attachmentService->uploadFile(
                $request->task_user_id,
                $request->file('file'),
                $request->description
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'attachment' => $result['attachment']
                    ],
                    'message' => 'تم رفع الملف بنجاح'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error in uploadFile', [
                'error' => $e->getMessage(),
                'task_user_id' => $request->task_user_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع الملف'
            ], 500);
        }
    }

    /**
     * عرض مرفقات المهمة
     */
    public function getTaskAttachments($taskUserId)
    {
        // تسجيل النشاط - عرض مرفقات المهمة
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'task_user_id' => $taskUserId,
                    'action_type' => 'view_task_attachments',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد مرفقات المهمة');
        }

        Log::info('TaskAttachmentController::getTaskAttachments called', [
            'task_user_id' => $taskUserId,
            'user_id' => Auth::id()
        ]);

        try {
            $result = $this->attachmentService->getTaskAttachments($taskUserId);

            Log::info('TaskAttachmentController::getTaskAttachments result', [
                'success' => $result['success'] ?? false,
                'attachments_count' => isset($result['attachments']) ? count($result['attachments']) : 0
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'attachments' => $result['attachments'],
                        'task_user' => $result['task_user']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 403);
            }

        } catch (\Exception $e) {
            Log::error('Error in getTaskAttachments', [
                'error' => $e->getMessage(),
                'task_user_id' => $taskUserId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المرفقات'
            ], 500);
        }
    }

    /**
     * عرض الملف
     */
    public function viewAttachment($attachmentId)
    {
        // تسجيل النشاط - عرض المرفق
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'attachment_id' => $attachmentId,
                    'action_type' => 'view_attachment',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد مرفق');
        }

        try {
            $result = $this->attachmentService->getViewUrl($attachmentId);

            if ($result['success']) {
                return redirect($result['url']);
            } else {
                abort(404, $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Error in viewAttachment', [
                'error' => $e->getMessage(),
                'attachment_id' => $attachmentId
            ]);

            abort(500, 'حدث خطأ أثناء عرض الملف');
        }
    }

    /**
     * تحميل الملف
     */
    public function downloadAttachment($attachmentId)
    {
        // تسجيل النشاط - تحميل المرفق
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'attachment_id' => $attachmentId,
                    'action_type' => 'download_attachment',
                    'downloaded_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('حمّل مرفق');
        }

        try {
            $result = $this->attachmentService->getDownloadUrl($attachmentId);

            if ($result['success']) {
                return redirect($result['url']);
            } else {
                abort(404, $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Error in downloadAttachment', [
                'error' => $e->getMessage(),
                'attachment_id' => $attachmentId
            ]);

            abort(500, 'حدث خطأ أثناء تحميل الملف');
        }
    }

    /**
     * حذف المرفق
     */
    public function deleteAttachment($attachmentId)
    {
        try {
            $result = $this->attachmentService->deleteAttachment($attachmentId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 403);
            }

        } catch (\Exception $e) {
            Log::error('Error in deleteAttachment', [
                'error' => $e->getMessage(),
                'attachment_id' => $attachmentId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الملف'
            ], 500);
        }
    }

    /**
     * إحصائيات مرفقات المستخدم
     */
    public function getUserStats(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            $result = $this->attachmentService->getUserAttachmentStats($userId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['stats']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error in getUserStats', [
                'error' => $e->getMessage(),
                'user_id' => $request->get('user_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }

    /**
     * صفحة عرض مرفقات المهمة (إذا كنت تريد واجهة منفصلة)
     */
    public function showTaskAttachments($taskUserId)
    {
        try {
            // استخدام نفس منطق البحث المطور
            $taskUser = TaskUser::with(['task', 'user'])->find($taskUserId);

            if (!$taskUser) {
                // البحث بـ task_id إذا لم نجد TaskUser
                $task = \App\Models\Task::find($taskUserId);
                if ($task) {
                    $currentUser = \Illuminate\Support\Facades\Auth::user();
                    $currentUserId = $currentUser ? $currentUser->id : null;

                    $taskUser = TaskUser::with(['task', 'user'])
                        ->where('task_id', $taskUserId)
                        ->where('user_id', $currentUserId)
                        ->first();

                    if (!$taskUser && $currentUser && ($currentUser->hasRole('hr') || $currentUser->hasRole('admin'))) {
                        $taskUser = TaskUser::with(['task', 'user'])
                            ->where('task_id', $taskUserId)
                            ->first();
                    }
                }

                if (!$taskUser) {
                    return redirect()->back()->with('error', 'المهمة غير موجودة أو غير مُعيَّنة لك');
                }
            }

            // تسجيل النشاط - عرض صفحة مرفقات المهمة
            if (\Illuminate\Support\Facades\Auth::check()) {
                activity()
                    ->performedOn($taskUser)
                    ->causedBy(\Illuminate\Support\Facades\Auth::user())
                    ->withProperties([
                        'task_user_id' => $taskUserId,
                        'task_name' => $taskUser->task ? $taskUser->task->name : null,
                        'user_name' => $taskUser->user ? $taskUser->user->name : null,
                        'action_type' => 'view_page',
                        'page' => 'task_attachments',
                        'viewed_at' => now()->toDateTimeString(),
                        'user_agent' => request()->userAgent(),
                        'ip_address' => request()->ip()
                    ])
                    ->log('دخل على صفحة مرفقات المهمة');
            }

                    // التحقق من الصلاحيات
        $user = Auth::user();
        if ($taskUser->user_id !== Auth::id() && !$user->hasRole(['hr', 'company_manager', 'project_manager'])) {
            abort(403, 'غير مسموح لك بعرض هذه المرفقات');
        }

            // التأكد من أن المهمة غير مرتبطة بمشروع
            if ($taskUser->project_id) {
                return redirect()->back()->with('error', 'هذه المهمة مرتبطة بمشروع، استخدم صفحة المشروع لعرض المرفقات');
            }

            $attachments = TaskAttachment::getAttachmentsForTask($taskUserId);

            return view('tasks.attachments.index', compact('taskUser', 'attachments'));

        } catch (\Exception $e) {
            Log::error('Error in showTaskAttachments', [
                'error' => $e->getMessage(),
                'task_user_id' => $taskUserId
            ]);

            return redirect()->back()->with('error', 'حدث خطأ أثناء عرض المرفقات');
        }
    }

    /**
     * تنظيف المرفقات غير المكتملة (للمديرين فقط)
     */
    public function cleanupIncompleteUploads(Request $request)
    {
        // التحقق من صلاحيات المدير
        $user = Auth::user();
        if (!$user->hasRole(['hr', 'company_manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بتنفيذ هذه العملية'
            ], 403);
        }

        try {
            $hours = $request->get('hours', 24);
            $result = $this->attachmentService->cleanupIncompleteUploads($hours);

            return response()->json([
                'success' => true,
                'message' => "تم تنظيف {$result['deleted_count']} مرفق غير مكتمل",
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error in cleanupIncompleteUploads', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تنظيف المرفقات'
            ], 500);
        }
    }
}
