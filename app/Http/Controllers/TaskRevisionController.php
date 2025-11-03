<?php

namespace App\Http\Controllers;

use App\Models\TaskRevision;
use App\Services\Tasks\TaskRevisionService;
use App\Services\Tasks\TaskRevisionStatusService;
use App\Services\Tasks\RevisionStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TaskRevisionController extends Controller
{
    protected $revisionService;
    protected $statusService;
    protected $storageService;

    public function __construct(
        TaskRevisionService $revisionService,
        TaskRevisionStatusService $statusService,
        RevisionStorageService $storageService
    ) {
        $this->revisionService = $revisionService;
        $this->statusService = $statusService;
        $this->storageService = $storageService;
        $this->middleware('auth');
    }

    /**
     * الحصول على قائمة تعديلات المهمة
     */
    public function index(Request $request, string $taskType, string $taskId)
    {
        try {
            $taskUserId = $request->get('task_user_id');
            $result = $this->revisionService->getTaskRevisions($taskType, $taskId, $taskUserId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'revisions' => $result['revisions']
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching task revisions', [
                'task_type' => $taskType,
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات'
            ], 500);
        }
    }

    /**
     * إنشاء تعديل جديد
     */
    public function store(Request $request)
    {
        // قواعد التحقق
        $rules = [
            'revision_type' => 'required|in:task,project,general',
            'revision_source' => 'required|in:internal,external',
            'task_type' => 'nullable|in:regular,template',
            'task_id' => 'nullable|integer|min:1',
            'task_user_id' => 'nullable|integer|min:1',
            'project_id' => 'nullable|integer|min:1',
            'service_id' => 'nullable|integer|exists:company_services,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'responsible_user_id' => 'nullable|integer|exists:users,id',
            'executor_user_id' => 'nullable|integer|exists:users,id',
            'reviewers' => 'nullable|json',
            'responsibility_notes' => 'nullable|string|max:2000',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'notes' => 'nullable|string|max:2000',
            'attachment_type' => 'nullable|in:file,link',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar',
            'attachment_link' => 'nullable|url|max:2048',
            // حقول الديدلاين
            'revision_deadline' => 'nullable|date|after:now', // ديدلاين التعديل العام
            'executor_deadline' => 'nullable|date|after:now',
            'reviewer_deadlines' => 'nullable|json', // JSON array of deadlines for each reviewer
        ];

        $messages = [
            'revision_type.required' => 'نوع التعديل مطلوب',
            'revision_type.in' => 'نوع التعديل غير صحيح',
            'revision_source.required' => 'مصدر التعديل مطلوب',
            'revision_source.in' => 'مصدر التعديل غير صحيح',
            'task_type.in' => 'نوع المهمة غير صحيح',
            'task_id.integer' => 'معرف المهمة يجب أن يكون رقم',
            'project_id.integer' => 'معرف المشروع يجب أن يكون رقم',
            'service_id.integer' => 'معرف الخدمة يجب أن يكون رقم',
            'service_id.exists' => 'الخدمة المحددة غير موجودة',
            'assigned_to.integer' => 'معرف المستخدم يجب أن يكون رقم',
            'assigned_to.exists' => 'المستخدم المحدد غير موجود',
            'responsible_user_id.integer' => 'معرف المسؤول يجب أن يكون رقم',
            'responsible_user_id.exists' => 'المسؤول المحدد غير موجود',
            'executor_user_id.integer' => 'معرف المنفذ يجب أن يكون رقم',
            'executor_user_id.exists' => 'المنفذ المحدد غير موجود',
            'reviewers.json' => 'بيانات المراجعين غير صحيحة',
            'responsibility_notes.max' => 'ملاحظات المسؤولية لا يجب أن تتجاوز 2000 حرف',
            'title.required' => 'عنوان التعديل مطلوب',
            'title.max' => 'عنوان التعديل لا يجب أن يتجاوز 255 حرف',
            'description.required' => 'وصف التعديل مطلوب',
            'description.max' => 'وصف التعديل لا يجب أن يتجاوز 5000 حرف',
            'notes.max' => 'الملاحظات لا يجب أن تتجاوز 2000 حرف',
            'attachment_type.in' => 'نوع المرفق غير صحيح',
            'attachment.file' => 'المرفق يجب أن يكون ملف صالح',
            'attachment.max' => 'حجم المرفق لا يجب أن يتجاوز 10 ميجابايت',
            'attachment.mimes' => 'نوع الملف المرفق غير مدعوم',
            'attachment_link.url' => 'رابط المرفق يجب أن يكون رابط صحيح',
            'attachment_link.max' => 'رابط المرفق لا يجب أن يتجاوز 2048 حرف',
            'revision_deadline.date' => 'تاريخ ديدلاين التعديل غير صحيح',
            'revision_deadline.after' => 'تاريخ ديدلاين التعديل يجب أن يكون بعد الآن',
            'executor_deadline.date' => 'تاريخ ديدلاين المنفذ غير صحيح',
            'executor_deadline.after' => 'تاريخ ديدلاين المنفذ يجب أن يكون بعد الآن',
            'reviewer_deadlines.json' => 'بيانات ديدلاينات المراجعين غير صحيحة'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->revisionService->createRevision($request->all());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'revision' => $result['revision']
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating task revision', [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إنشاء التعديل'
            ], 500);
        }
    }

    /**
     * تحديث حالة التعديل (موافقة/رفض)
     */
    public function updateStatus(Request $request, TaskRevision $revision)
    {
        $rules = [
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string|max:1000'
        ];

        $messages = [
            'status.required' => 'حالة التعديل مطلوبة',
            'status.in' => 'حالة التعديل غير صحيحة',
            'review_notes.max' => 'ملاحظات المراجعة لا يجب أن تتجاوز 1000 حرف'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->revisionService->updateRevisionStatus(
                $revision->id,
                $request->status,
                $request->review_notes
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'revision' => $result['revision']
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating revision status', [
                'revision_id' => $revision->id,
                'status' => $request->status,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث حالة التعديل'
            ], 500);
        }
    }

    /**
     * حذف تعديل
     */
    public function destroy(TaskRevision $revision)
    {
        try {
            $result = $this->revisionService->deleteRevision($revision->id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting revision', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حذف التعديل'
            ], 500);
        }
    }

    /**
     * تحميل الملف المرفق
     */
    public function downloadAttachment(TaskRevision $revision)
    {
        try {
            if (!$revision->attachment_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد ملف مرفق مع هذا التعديل'
                ], 404);
            }

            // تسجيل نشاط التحميل
            activity()
                ->performedOn($revision)
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'download_attachment',
                    'file_name' => $revision->attachment_name,
                    'file_path' => $revision->attachment_path,
                    'downloaded_at' => now()->toDateTimeString()
                ])
                ->log('تم تحميل مرفق التعديل');

            // التحقق: هل الملف في storage المحلي (ملفات قديمة) أم على Wasabi (ملفات جديدة)
            if (Storage::disk('public')->exists($revision->attachment_path)) {
                // ملف قديم على السيرفر المحلي
                $filePath = storage_path('app/public/' . $revision->attachment_path);
                return response()->download($filePath, $revision->attachment_name);
            } else {
                // ملف جديد على Wasabi - التحقق من وجوده
                if (!$this->storageService->fileExists($revision->attachment_path)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الملف المرفق غير موجود في التخزين'
                    ], 404);
                }

                // الحصول على presigned URL من Wasabi والتوجيه إليه
                $downloadUrl = $this->storageService->getPresignedUrl($revision->attachment_path, 5); // صالح لمدة 5 دقائق

                return redirect($downloadUrl);
            }
        } catch (\Exception $e) {
            Log::error('Error downloading revision attachment', [
                'revision_id' => $revision->id,
                'attachment_path' => $revision->attachment_path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل الملف'
            ], 500);
        }
    }

    /**
     * عرض الملف المرفق في المتصفح
     */
    public function viewAttachment(TaskRevision $revision)
    {
        try {
            if (!$revision->attachment_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد ملف مرفق مع هذا التعديل'
                ], 404);
            }

            // تسجيل نشاط العرض
            activity()
                ->performedOn($revision)
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'view_attachment',
                    'file_name' => $revision->attachment_name,
                    'file_path' => $revision->attachment_path,
                    'viewed_at' => now()->toDateTimeString()
                ])
                ->log('تم عرض مرفق التعديل');

            // التحقق: هل الملف في storage المحلي (ملفات قديمة) أم على Wasabi (ملفات جديدة)
            if (Storage::disk('public')->exists($revision->attachment_path)) {
                // ملف قديم على السيرفر المحلي
                $filePath = storage_path('app/public/' . $revision->attachment_path);

                // تحديد MIME type الصحيح
                $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

                // إرجاع الملف مع headers للعرض inline
                return response()->file($filePath, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . $revision->attachment_name . '"'
                ]);
            } else {
                // ملف جديد على Wasabi - التحقق من وجوده
                if (!$this->storageService->fileExists($revision->attachment_path)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الملف المرفق غير موجود في التخزين'
                    ], 404);
                }

                // الحصول على presigned URL من Wasabi للعرض (مدة أطول)
                $viewUrl = $this->storageService->getPresignedUrl($revision->attachment_path, 30); // صالح لمدة 30 دقيقة

                return redirect($viewUrl);
            }
        } catch (\Exception $e) {
            Log::error('Error viewing revision attachment', [
                'revision_id' => $revision->id,
                'attachment_path' => $revision->attachment_path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في عرض الملف: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على تعديلات المشروع
     */
    public function getProjectRevisions(Request $request, string $projectId)
    {
        try {
            $result = $this->revisionService->getProjectRevisions($projectId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'revisions' => $result['revisions']
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching project revisions', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل تعديلات المشروع'
            ], 500);
        }
    }

    /**
     * الحصول على التعديلات العامة
     */
    public function getGeneralRevisions(Request $request)
    {
        try {
            $result = $this->revisionService->getGeneralRevisions();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'revisions' => $result['revisions']
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching general revisions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات العامة'
            ], 500);
        }
    }

    /**
     * الحصول على جميع تعديلات المشروع (تعديلات المشروع + التعديلات العامة)
     */
    public function getAllProjectRelatedRevisions(Request $request, string $projectId)
    {
        try {
            $filters = $request->only(['service_id']);
            $result = $this->revisionService->getAllProjectRelatedRevisions($projectId, $filters);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'revisions' => $result['revisions']
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching all project related revisions', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات'
            ], 500);
        }
    }

    /**
     * الحصول على التعديلات الداخلية
     */
    public function getInternalRevisions(Request $request)
    {
        try {
            $filters = $request->only(['revision_type', 'status', 'project_id']);
            $result = $this->revisionService->getInternalRevisions($filters);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'revisions' => $result['revisions']
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching internal revisions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات الداخلية'
            ], 500);
        }
    }

    /**
     * الحصول على التعديلات الخارجية
     */
    public function getExternalRevisions(Request $request)
    {
        try {
            $filters = $request->only(['revision_type', 'status', 'project_id']);
            $result = $this->revisionService->getExternalRevisions($filters);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'revisions' => $result['revisions']
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching external revisions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات الخارجية'
            ], 500);
        }
    }

    /**
     * الحصول على إحصائيات تعديلات المشروع
     */
    public function getProjectRevisionStats(Request $request, $projectId)
    {
        try {
            $result = $this->revisionService->getProjectRevisionStats($projectId);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error fetching project revision stats', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل إحصائيات التعديلات'
            ], 500);
        }
    }

    /**
     * ========================================
     * إدارة حالة التعديلات والوقت
     * ========================================
     */

    /**
     * بدء العمل على التعديل
     */
    public function startRevision(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::with([
                'taskUser.user:id,name',
                'templateTaskUser.user:id,name'
            ])->findOrFail($revisionId);

            $result = $this->statusService->startRevision($revision);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error starting revision', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء بدء التعديل'
            ], 500);
        }
    }

    /**
     * إيقاف مؤقت للتعديل
     */
    public function pauseRevision(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::with([
                'taskUser.user:id,name',
                'templateTaskUser.user:id,name'
            ])->findOrFail($revisionId);
            $result = $this->statusService->pauseRevision($revision);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error pausing revision', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إيقاف التعديل'
            ], 500);
        }
    }

    /**
     * استئناف العمل على التعديل
     */
    public function resumeRevision(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::with([
                'taskUser.user:id,name',
                'templateTaskUser.user:id,name'
            ])->findOrFail($revisionId);
            $result = $this->statusService->resumeRevision($revision);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error resuming revision', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء استئناف التعديل'
            ], 500);
        }
    }

    /**
     * إكمال التعديل
     */
    public function completeRevision(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::with([
                'taskUser.user:id,name',
                'templateTaskUser.user:id,name'
            ])->findOrFail($revisionId);
            $result = $this->statusService->completeRevision($revision);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error completing revision', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إكمال التعديل'
            ], 500);
        }
    }

    /**
     * الحصول على التعديل النشط للمستخدم
     */
    public function getActiveRevision()
    {
        try {
            $activeRevision = $this->statusService->getActiveRevision();

            return response()->json([
                'success' => true,
                'has_active' => $activeRevision !== null,
                'revision' => $activeRevision
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting active revision', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب التعديل النشط'
            ], 500);
        }
    }

    /**
     * الحصول على إحصائيات التعديلات للمستخدم
     */
    public function getUserRevisionStats()
    {
        try {
            $stats = $this->statusService->getUserRevisionStats();

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting user revision stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }

    /**
     * 🎯 الحصول على المسؤولين المناسبين حسب role المستخدم الحالي
     * - جميع الموظفين النشطين مع تقديم المراجعين في البداية
     * - المراجعون في المشروع أولاً، ثم المراجعون الآخرون، ثم باقي الموظفين
     */
    public function getReviewersOnly(Request $request)
    {
        try {
            $projectId = $request->input('project_id');
            $currentUser = Auth::user();

            if (!$projectId) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف المشروع مطلوب'
                ], 400);
            }

            // جلب المشروع مع المشاركين
            $project = \App\Models\Project::with('participants')->find($projectId);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'المشروع غير موجود'
                ], 404);
            }

            $participantIds = $project->participants->pluck('id')->toArray();

            // ✅ جلب أدوار المراجعين
            $reviewerRoleIds = \App\Models\RoleHierarchy::getReviewerRoleIds();
            $generalReviewerRole = \Spatie\Permission\Models\Role::where('name', 'general_reviewer')->first();
            if ($generalReviewerRole && !in_array($generalReviewerRole->id, $reviewerRoleIds)) {
                $reviewerRoleIds[] = $generalReviewerRole->id;
            }

            // 1️⃣ المراجعون في المشروع
            $reviewersInProject = \App\Models\User::whereHas('roles', function ($query) use ($reviewerRoleIds) {
                $query->whereIn('roles.id', $reviewerRoleIds);
            })
                ->whereIn('id', $participantIds)
                ->where('employee_status', 'active')
                ->with(['roles' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->select('id', 'name', 'email', 'department')
                ->orderBy('name')
                ->get();

            // 2️⃣ المراجعون خارج المشروع (نشطين)
            $reviewersNotInProject = \App\Models\User::whereHas('roles', function ($query) use ($reviewerRoleIds) {
                $query->whereIn('roles.id', $reviewerRoleIds);
            })
                ->whereNotIn('id', $participantIds)
                ->where('employee_status', 'active')
                ->with(['roles' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->select('id', 'name', 'email', 'department')
                ->orderBy('name')
                ->get();

            // 3️⃣ باقي الموظفين في المشروع (ليسوا مراجعين)
            $allReviewerUserIds = $reviewersInProject->pluck('id')->merge($reviewersNotInProject->pluck('id'))->toArray();

            $otherParticipants = \App\Models\User::whereIn('id', $participantIds)
                ->whereNotIn('id', $allReviewerUserIds)
                ->where('employee_status', 'active')
                ->select('id', 'name', 'email', 'department')
                ->orderBy('name')
                ->get();

            // 4️⃣ باقي الموظفين النشطين (ليسوا في المشروع وليسوا مراجعين)
            $otherActiveUsers = \App\Models\User::whereNotIn('id', $participantIds)
                ->whereNotIn('id', $allReviewerUserIds)
                ->where('employee_status', 'active')
                ->select('id', 'name', 'email', 'department')
                ->orderBy('name')
                ->get();

            // ✅ دمج القوائم بالترتيب: مراجعون في المشروع → مراجعون خارج المشروع → موظفون في المشروع → موظفون نشطين آخرين
            $allUsers = $reviewersInProject
                ->concat($reviewersNotInProject)
                ->concat($otherParticipants)
                ->concat($otherActiveUsers);

            Log::info('📋 Users list for revision reviewers', [
                'user_id' => $currentUser->id,
                'user_roles' => $currentUser->roles->pluck('name'),
                'reviewers_in_project' => $reviewersInProject->count(),
                'reviewers_not_in_project' => $reviewersNotInProject->count(),
                'other_participants' => $otherParticipants->count(),
                'other_active_users' => $otherActiveUsers->count(),
                'total_users' => $allUsers->count()
            ]);

            return response()->json([
                'success' => true,
                'reviewers' => $allUsers,
                'counts' => [
                    'reviewers_in_project' => $reviewersInProject->count(),
                    'reviewers_not_in_project' => $reviewersNotInProject->count(),
                    'other_participants' => $otherParticipants->count(),
                    'other_active' => $otherActiveUsers->count(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching responsible users for project', [
                'project_id' => $request->input('project_id'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب القائمة'
            ], 500);
        }
    }

    /**
     * تحديث التعديل
     */
    public function update(Request $request, TaskRevision $revision)
    {
        try {
            // التحقق من أن المستخدم هو منشئ التعديل
            if ($revision->created_by != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا التعديل'
                ], 403);
            }

            // Validation
            $request->validate([
                'revision_type' => 'required|in:task,project,general',
                'revision_source' => 'required|in:internal,external,auto',
                'title' => 'required|string|max:500',
                'description' => 'required|string',
                'notes' => 'nullable|string',
                'project_id' => 'required_if:revision_type,project|exists:projects,id',
                'responsible_user_id' => 'nullable|exists:users,id',
                'executor_user_id' => 'nullable|exists:users,id',
                'reviewers' => 'nullable|json',
                'responsibility_notes' => 'nullable|string|max:2000',
                'attachment' => 'nullable|file|max:10240',
                'attachment_link' => 'nullable|url',
                'revision_deadline' => 'nullable|date|after:now',
                'executor_deadline' => 'nullable|date|after:now',
                'reviewer_deadlines' => 'nullable|json',
            ]);

            // Update basic fields
            $revision->revision_type = $request->revision_type;
            $revision->revision_source = $request->revision_source;
            $revision->title = $request->title;
            $revision->description = $request->description;
            $revision->notes = $request->notes;

            // تحديث ديدلاين التعديل
            if ($request->filled('revision_deadline')) {
                $revision->revision_deadline = $request->revision_deadline;
            } else {
                $revision->revision_deadline = null;
            }

            // Update project-related fields
            if ($request->revision_type === 'project') {
                $revision->project_id = $request->project_id;
                $revision->responsible_user_id = $request->responsible_user_id;
                $revision->executor_user_id = $request->executor_user_id;
                $revision->reviewers = $request->reviewers ? json_decode($request->reviewers, true) : null;
                $revision->responsibility_notes = $request->responsibility_notes;
            } else {
                // Clear project-related fields if not project type
                $revision->project_id = null;
                $revision->responsible_user_id = null;
                $revision->executor_user_id = null;
                $revision->reviewers = null;
                $revision->responsibility_notes = null;
            }

            // Handle attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('revisions/attachments', $fileName, 'public');
                $revision->attachment = $filePath;
                $revision->attachment_type = 'file';
            } elseif ($request->filled('attachment_link')) {
                $revision->attachment = $request->attachment_link;
                $revision->attachment_type = 'link';
            }

            $revision->save();

            // تحديث الديدلاينات
            $this->updateRevisionDeadlines($revision, $request);

            // Activity log
            activity()
                ->causedBy(Auth::user())
                ->performedOn($revision)
                ->withProperties([
                    'revision_id' => $revision->id,
                    'revision_type' => $revision->revision_type,
                    'title' => $revision->title,
                ])
                ->log('قام بتعديل التعديل');

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث التعديل بنجاح',
                'revision' => $revision->load(['deadlines', 'executorDeadline', 'reviewerDeadlines'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating revision: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث التعديل'
            ], 500);
        }
    }

    /**
     * إعادة تعيين المنفذ
     */
    public function reassignExecutor(Request $request, TaskRevision $revision)
    {
        try {
            $request->validate([
                'to_user_id' => 'required|exists:users,id',
                'reason' => 'nullable|string|max:500',
                'new_deadline' => 'nullable|date|after:now',
            ]);

            $fromUser = $revision->executor_user_id ? \App\Models\User::find($revision->executor_user_id) : null;
            $toUser = \App\Models\User::findOrFail($request->to_user_id);

            if (!$fromUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد منفذ حالي لهذا التعديل'
                ], 400);
            }

            // استخدام الـ Service مع الديدلاين الجديد
            $transferService = app(\App\Services\Tasks\RevisionTransferService::class);
            $result = $transferService->transferExecutor(
                $revision,
                $fromUser,
                $toUser,
                $request->reason,
                $request->new_deadline
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error reassigning executor: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إعادة تعيين المنفذ'
            ], 500);
        }
    }

    /**
     * إعادة تعيين المراجع
     */
    public function reassignReviewer(Request $request, TaskRevision $revision)
    {
        try {
            $request->validate([
                'to_user_id' => 'required|exists:users,id',
                'reviewer_order' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:500',
                'new_deadline' => 'nullable|date|after:now',
            ]);

            $toUser = \App\Models\User::findOrFail($request->to_user_id);
            $reviewerOrder = $request->reviewer_order;

            // استخدام الـ Service مع الديدلاين الجديد
            $transferService = app(\App\Services\Tasks\RevisionTransferService::class);
            $result = $transferService->transferReviewer(
                $revision,
                $reviewerOrder,
                $toUser,
                $request->reason,
                $request->new_deadline
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error reassigning reviewer: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إعادة تعيين المراجع'
            ], 500);
        }
    }

    /**
     * الحصول على سجل نقل التعديل
     */
    public function getTransferHistory(TaskRevision $revision)
    {
        try {
            $transferService = app(\App\Services\Tasks\RevisionTransferService::class);
            $history = $transferService->getRevisionTransferHistory($revision);

            return response()->json([
                'success' => true,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching transfer history: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب سجل النقل'
            ], 500);
        }
    }

    /**
     * الحصول على إحصائيات نقل التعديلات للمستخدم الحالي
     */
    public function getUserTransferStats(Request $request)
    {
        try {
            $user = Auth::user();
            $transferService = app(\App\Services\Tasks\RevisionTransferService::class);

            // يمكن تمرير season_id اختياري
            $season = null;
            if ($request->has('season_id')) {
                $season = \App\Models\Season::find($request->season_id);
            }

            $stats = $transferService->getUserTransferStats($user, $season);

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user transfer stats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الإحصائيات'
            ], 500);
        }
    }

    /**
     * ========================================
     * إدارة حالة المراجعة ووقت المراجع
     * ========================================
     */

    /**
     * بدء المراجعة
     */
    public function startReview(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::findOrFail($revisionId);

            // التحقق من أن المستخدم هو المراجع الحالي
            $currentReviewer = $revision->getCurrentReviewer();
            if (!$currentReviewer || $currentReviewer->id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'أنت لست المراجع الحالي لهذا التعديل'
                ], 403);
            }

            // ✅ التحقق من أن المنفذ خلص التعديل
            if ($revision->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن بدء المراجعة قبل إتمام المنفذ للتعديل',
                    'current_status' => $revision->status,
                    'status_text' => $revision->status_text
                ], 400);
            }

            // TODO: التحقق من وجود مراجعة أخرى نشطة للمستخدم (محتاج تحديث للنظام الجديد)
            // مؤقتاً: نسمح بمراجعات متعددة نشطة
            $activeReview = null;

            if ($activeReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'لديك مراجعة نشطة حالياً. يرجى إيقافها أو إكمالها أولاً.',
                    'active_review_id' => $activeReview->id,
                    'active_review_title' => $activeReview->title
                ], 400);
            }

            $revision->startReview();

            return response()->json([
                'success' => true,
                'message' => 'تم بدء المراجعة بنجاح',
                'revision' => $revision->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting review', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء بدء المراجعة'
            ], 500);
        }
    }

    /**
     * إيقاف مؤقت للمراجعة
     */
    public function pauseReview(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::findOrFail($revisionId);

            // التحقق من أن المستخدم هو المراجع الحالي
            $currentReviewer = $revision->getCurrentReviewer();
            if (!$currentReviewer || $currentReviewer->id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'أنت لست المراجع الحالي لهذا التعديل'
                ], 403);
            }

            $revision->pauseReview();

            return response()->json([
                'success' => true,
                'message' => 'تم إيقاف المراجعة مؤقتاً',
                'revision' => $revision->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error pausing review', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إيقاف المراجعة'
            ], 500);
        }
    }

    /**
     * استئناف المراجعة
     */
    public function resumeReview(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::findOrFail($revisionId);

            // التحقق من أن المستخدم هو المراجع الحالي
            $currentReviewer = $revision->getCurrentReviewer();
            if (!$currentReviewer || $currentReviewer->id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'أنت لست المراجع الحالي لهذا التعديل'
                ], 403);
            }

            // ✅ التحقق من أن المنفذ خلص التعديل
            if ($revision->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن استئناف المراجعة قبل إتمام المنفذ للتعديل',
                    'current_status' => $revision->status,
                    'status_text' => $revision->status_text
                ], 400);
            }

            // TODO: التحقق من وجود مراجعة أخرى نشطة للمستخدم (محتاج تحديث للنظام الجديد)
            // مؤقتاً: نسمح بمراجعات متعددة نشطة
            $activeReview = null;

            if ($activeReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'لديك مراجعة نشطة حالياً. يرجى إيقافها أو إكمالها أولاً.',
                    'active_review_id' => $activeReview->id,
                    'active_review_title' => $activeReview->title
                ], 400);
            }

            $revision->resumeReview();

            return response()->json([
                'success' => true,
                'message' => 'تم استئناف المراجعة بنجاح',
                'revision' => $revision->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error resuming review', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء استئناف المراجعة'
            ], 500);
        }
    }

    /**
     * إكمال المراجعة
     */
    public function completeReview(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::findOrFail($revisionId);

            // التحقق من أن المستخدم هو المراجع الحالي
            $currentReviewer = $revision->getCurrentReviewer();
            if (!$currentReviewer || $currentReviewer->id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'أنت لست المراجع الحالي لهذا التعديل'
                ], 403);
            }

            $revision->completeReview();

            return response()->json([
                'success' => true,
                'message' => 'تم إكمال المراجعة بنجاح',
                'total_minutes' => $revision->fresh()->review_actual_minutes,
                'revision' => $revision->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error completing review', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إكمال المراجعة'
            ], 500);
        }
    }

    /**
     * إعادة فتح العمل (للتعديل إذا تم إكماله بالغلط)
     */
    public function reopenWork(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::findOrFail($revisionId);

            // التحقق من أن المستخدم هو المنفذ
            if ($revision->executor_user_id != Auth::id() && $revision->assigned_to != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'أنت لست منفذ هذا التعديل'
                ], 403);
            }

            // التحقق من أن العمل مكتمل
            if ($revision->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا التعديل ليس مكتملاً'
                ], 400);
            }

            // إعادة فتح العمل (إرجاعه لحالة paused)
            $revision->update([
                'status' => 'paused',
                'completed_at' => null,
                'current_session_start' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة فتح العمل بنجاح',
                'revision' => $revision->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error reopening work', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة فتح العمل'
            ], 500);
        }
    }

    /**
     * إعادة فتح المراجعة (إذا تم إكمالها بالغلط)
     */
    public function reopenReview(Request $request, $revisionId)
    {
        try {
            $revision = TaskRevision::findOrFail($revisionId);

            // التحقق من أن المستخدم هو المراجع الحالي
            $currentReviewer = $revision->getCurrentReviewer();
            if (!$currentReviewer || $currentReviewer->id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'أنت لست المراجع الحالي لهذا التعديل'
                ], 403);
            }

            // التحقق من أن المراجعة مكتملة
            if ($revision->review_status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه المراجعة ليست مكتملة'
                ], 400);
            }

            // إعادة فتح المراجعة (إرجاعها لحالة paused)
            $revision->update([
                'review_status' => 'paused',
                'review_completed_at' => null,
                'review_current_session_start' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة فتح المراجعة بنجاح',
                'revision' => $revision->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error reopening review', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة فتح المراجعة'
            ], 500);
        }
    }

    /**
     * تحديث ديدلاينات التعديل
     */
    private function updateRevisionDeadlines(TaskRevision $revision, Request $request): void
    {
        try {
            // تحديث أو إنشاء ديدلاين المنفذ
            if ($request->filled('executor_deadline') && $request->filled('executor_user_id')) {
                $existingExecutorDeadline = \App\Models\RevisionDeadline::where('revision_id', $revision->id)
                    ->where('deadline_type', 'executor')
                    ->first();

                if ($existingExecutorDeadline) {
                    // تمديد الديدلاين القائم
                    $existingExecutorDeadline->extend(
                        \Carbon\Carbon::parse($request->executor_deadline),
                        Auth::id()
                    );
                } else {
                    // إنشاء ديدلاين جديد
                    \App\Models\RevisionDeadline::create([
                        'revision_id' => $revision->id,
                        'deadline_type' => 'executor',
                        'user_id' => $request->executor_user_id,
                        'deadline_date' => $request->executor_deadline,
                        'status' => 'pending',
                        'assigned_by' => Auth::id(),
                        'original_deadline' => $request->executor_deadline,
                    ]);
                }
            }

            // تحديث ديدلاينات المراجعين
            if ($request->filled('reviewer_deadlines')) {
                $reviewerDeadlines = json_decode($request->reviewer_deadlines, true);

                if (is_array($reviewerDeadlines)) {
                    foreach ($reviewerDeadlines as $reviewerDeadline) {
                        if (empty($reviewerDeadline['reviewer_id']) || empty($reviewerDeadline['deadline'])) {
                            continue;
                        }

                        // البحث عن الترتيب
                        $reviewerOrder = null;
                        if (!empty($revision->reviewers)) {
                            foreach ($revision->reviewers as $reviewer) {
                                if ($reviewer['reviewer_id'] == $reviewerDeadline['reviewer_id']) {
                                    $reviewerOrder = $reviewer['order'];
                                    break;
                                }
                            }
                        }

                        if ($reviewerOrder === null) {
                            continue;
                        }

                        $existingReviewerDeadline = \App\Models\RevisionDeadline::where('revision_id', $revision->id)
                            ->where('deadline_type', 'reviewer')
                            ->where('reviewer_order', $reviewerOrder)
                            ->first();

                        if ($existingReviewerDeadline) {
                            // تمديد الديدلاين القائم
                            $existingReviewerDeadline->extend(
                                \Carbon\Carbon::parse($reviewerDeadline['deadline']),
                                Auth::id()
                            );
                        } else {
                            // إنشاء ديدلاين جديد
                            \App\Models\RevisionDeadline::create([
                                'revision_id' => $revision->id,
                                'deadline_type' => 'reviewer',
                                'user_id' => $reviewerDeadline['reviewer_id'],
                                'deadline_date' => $reviewerDeadline['deadline'],
                                'reviewer_order' => $reviewerOrder,
                                'status' => 'pending',
                                'assigned_by' => Auth::id(),
                                'original_deadline' => $reviewerDeadline['deadline'],
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error updating revision deadlines', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
