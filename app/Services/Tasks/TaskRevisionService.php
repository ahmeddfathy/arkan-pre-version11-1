<?php

namespace App\Services\Tasks;

use App\Models\TaskRevision;
use App\Models\Task;
use App\Models\TemplateTaskUser;
use App\Services\Tasks\RevisionFilterService;
use App\Services\Slack\RevisionSlackService;
use App\Traits\SeasonAwareTrait;
use App\Traits\HasNTPTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskRevisionService
{
    use SeasonAwareTrait, HasNTPTime;

    protected $revisionFilterService;
    protected $slackService;

    public function __construct(
        RevisionFilterService $revisionFilterService,
        RevisionSlackService $slackService
    ) {
        $this->revisionFilterService = $revisionFilterService;
        $this->slackService = $slackService;
    }

    /**
     * إنشاء تعديل جديد
     */
    public function createRevision(array $data): array
    {
        try {
            DB::beginTransaction();

            // إعداد بيانات التعديل الأساسية
            $revisionData = [
                'title' => $data['title'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'revision_type' => $data['revision_type'] ?? 'task',
                'revision_source' => $data['revision_source'] ?? 'internal',
                'created_by' => Auth::id(),
                'revision_date' => $this->getCurrentCairoTime(),
                'status' => 'new',
                'season_id' => $this->getCurrentSeasonId(),
                // الحقول الجديدة للمسؤولية
                'responsible_user_id' => $data['responsible_user_id'] ?? null,
                'executor_user_id' => $data['executor_user_id'] ?? null,
                'reviewers' => isset($data['reviewers']) ? (is_string($data['reviewers']) ? json_decode($data['reviewers'], true) : $data['reviewers']) : null,
                'responsibility_notes' => $data['responsibility_notes'] ?? null,
            ];

            // ربط التعديل حسب النوع
            if ($data['revision_type'] === 'project') {
                // تعديل مشروع
                $revisionData['project_id'] = $data['project_id'];
                $revisionData['service_id'] = $data['service_id'] ?? null;
                $revisionData['task_id'] = null;
                $revisionData['task_user_id'] = null;
                $revisionData['template_task_user_id'] = null;
                $revisionData['task_type'] = null;
                $revisionData['assigned_to'] = $data['assigned_to'] ?? null;

                // معالجة المرفق (ملف أو لينك)
                $attachmentData = $this->handleAttachment($data, 'project', $data['project_id']);
                if ($attachmentData) {
                    $revisionData = array_merge($revisionData, $attachmentData);
                }
            } elseif ($data['revision_type'] === 'general') {
                // تعديل عام
                $revisionData['project_id'] = null;
                $revisionData['task_id'] = null;
                $revisionData['task_user_id'] = null;
                $revisionData['template_task_user_id'] = null;
                $revisionData['task_type'] = null;
                $revisionData['assigned_to'] = $data['assigned_to'] ?? null;

                // معالجة المرفق (ملف أو لينك)
                $attachmentData = $this->handleAttachment($data, 'general', 'general');
                if ($attachmentData) {
                    $revisionData = array_merge($revisionData, $attachmentData);
                }
            } else {
                // تعديل مهمة (السلوك القديم)
                $taskData = $this->validateTaskData($data);
                $revisionData['task_type'] = $data['task_type'];

                if ($data['task_type'] === 'template') {
                    $revisionData['template_task_user_id'] = $data['task_id'];
                    $revisionData['task_id'] = null;
                    $revisionData['task_user_id'] = null;
                } else {
                    $revisionData['task_id'] = $taskData['task_id'];
                    $revisionData['task_user_id'] = $data['task_user_id'] ?? null;
                    $revisionData['template_task_user_id'] = null;
                }

                $revisionData['project_id'] = null;
                $revisionData['assigned_to'] = $data['assigned_to'] ?? null;

                // معالجة المرفق (ملف أو لينك)
                $attachmentData = $this->handleAttachment($data, $data['task_type'], $taskData['task_id']);
                if ($attachmentData) {
                    $revisionData = array_merge($revisionData, $attachmentData);
                }
            }

            // إنشاء التعديل
            $revision = TaskRevision::create($revisionData);

            // تسجيل النشاط
            $this->logRevisionActivity($revision, 'created');

            // 📝 Log تفصيلي لإضافة المستخدمين في التعديل
            Log::info('✅ Revision Created with Users', [
                'revision_id' => $revision->id,
                'revision_title' => $revision->title,
                'revision_type' => $revision->revision_type,
                'created_by' => [
                    'id' => $revision->created_by,
                    'name' => auth()->user()->name ?? 'N/A'
                ],
                'responsible_user' => $revision->responsible_user_id ? [
                    'id' => $revision->responsible_user_id,
                    'name' => \App\Models\User::find($revision->responsible_user_id)?->name ?? 'N/A',
                    'role' => 'المسؤول (اللي اتخصم عليه)'
                ] : null,
                'executor_user' => $revision->executor_user_id ? [
                    'id' => $revision->executor_user_id,
                    'name' => \App\Models\User::find($revision->executor_user_id)?->name ?? 'N/A',
                    'role' => 'المنفذ (اللي هيصلح)'
                ] : null,
                'reviewers' => $revision->reviewers ? collect($revision->reviewers)->map(function($r) {
                    $user = \App\Models\User::find($r['reviewer_id']);
                    return [
                        'id' => $r['reviewer_id'],
                        'name' => $user ? $user->name : 'N/A',
                        'order' => $r['order'],
                        'status' => $r['status'],
                        'role' => 'مراجع ' . $r['order']
                    ];
                })->toArray() : [],
                'assigned_to' => $revision->assigned_to ? [
                    'id' => $revision->assigned_to,
                    'name' => \App\Models\User::find($revision->assigned_to)?->name ?? 'N/A',
                    'role' => 'المكلف'
                ] : null,
                'total_users_assigned' => collect([
                    $revision->responsible_user_id,
                    $revision->executor_user_id,
                    $revision->assigned_to
                ])->filter()->count() + (is_array($revision->reviewers) ? count($revision->reviewers) : 0)
            ]);

            DB::commit();

            // إرسال إشعارات Slack للمستخدمين المعنيين
            try {
                $this->slackService->sendRevisionCreatedNotification($revision);
            } catch (\Exception $e) {
                Log::warning('Failed to send revision Slack notifications', [
                    'revision_id' => $revision->id,
                    'error' => $e->getMessage()
                ]);
            }

            // إرسال إشعارات داخلية وFirebase للمعنيين
            $this->sendInternalNotifications($revision);

            return [
                'success' => true,
                'message' => 'تم إنشاء التعديل بنجاح',
                'revision' => $revision->load(['creator', 'reviewer', 'project', 'assignedUser', 'responsibleUser', 'executorUser'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating revision', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في إنشاء التعديل: ' . $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على تعديلات المهمة مع تطبيق الفلترة الهرمية
     */
    public function getTaskRevisions(string $taskType, string|int $taskId, string|int $taskUserId = null): array
    {
        try {
            // تحويل المعاملات إلى integers
            $taskId = (int) $taskId;
            $taskUserId = $taskUserId ? (int) $taskUserId : null;

            $query = TaskRevision::with(['creator', 'reviewer', 'assignedUser', 'responsibleUser', 'executorUser'])
                                ->latest();

            if ($taskType === 'template') {
                $query = $query->forTemplateTask($taskId);
            } else {
                $query = $query->forRegularTask($taskId, $taskUserId);
            }

            // تطبيق الفلترة الهرمية
            $query = $this->revisionFilterService->applyHierarchicalRevisionFiltering($query);

            $revisions = $query->get();

            return [
                'success' => true,
                'revisions' => $revisions
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching task revisions', [
                'task_type' => $taskType,
                'task_id' => $taskId,
                'task_user_id' => $taskUserId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات',
                'revisions' => collect()
            ];
        }
    }


    public function getProjectRevisions(string|int $projectId): array
    {
        try {
            // تحويل المعامل إلى integer
            $projectId = (int) $projectId;

            $query = TaskRevision::with(['creator', 'reviewer', 'project', 'service', 'assignedUser', 'responsibleUser', 'executorUser'])
                                ->forProject($projectId)
                                ->latest();

            // تطبيق الفلترة الهرمية
            $query = $this->revisionFilterService->applyHierarchicalRevisionFiltering($query);

            $revisions = $query->get();

            return [
                'success' => true,
                'revisions' => $revisions
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching project revisions', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل تعديلات المشروع',
                'revisions' => collect()
            ];
        }
    }

    /**
     * الحصول على التعديلات العامة مع تطبيق الفلترة الهرمية
     */
    public function getGeneralRevisions(): array
    {
        try {
            $query = TaskRevision::with(['creator', 'reviewer', 'assignedUser'])
                                ->general()
                                ->latest();

            // تطبيق الفلترة الهرمية
            $query = $this->revisionFilterService->applyHierarchicalRevisionFiltering($query);

            $revisions = $query->get();

            return [
                'success' => true,
                'revisions' => $revisions
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching general revisions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات العامة',
                'revisions' => collect()
            ];
        }
    }

    /**
     * الحصول على جميع تعديلات المشروع (تعديلات المشروع + التعديلات العامة) مع تطبيق الفلترة الهرمية
     */
    public function getAllProjectRelatedRevisions(string|int $projectId, array $filters = []): array
    {
        try {
            // تحويل المعامل إلى integer
            $projectId = (int) $projectId;

            $query = TaskRevision::with(['creator', 'reviewer', 'project', 'service', 'assignedUser', 'responsibleUser', 'executorUser'])
                                ->where(function($query) use ($projectId) {
                                    // تعديلات المشروع
                                    $query->where('revision_type', 'project')
                                          ->where('project_id', $projectId);
                                })
                                ->orWhere(function($query) {
                                    // التعديلات العامة
                                    $query->where('revision_type', 'general');
                                })
                                ->latest();

            // فلترة حسب الخدمة إذا تم تحديدها
            if (isset($filters['service_id']) && $filters['service_id']) {
                $query->where('service_id', $filters['service_id']);
            }

            // تطبيق الفلترة الهرمية
            $query = $this->revisionFilterService->applyHierarchicalRevisionFiltering($query);

            $revisions = $query->get();

            return [
                'success' => true,
                'revisions' => $revisions
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching all project related revisions', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات',
                'revisions' => collect()
            ];
        }
    }

    /**
     * تحديث حالة التعديل (موافقة/رفض)
     */
    public function updateRevisionStatus(int $revisionId, string $status, string $reviewNotes = null): array
    {
        try {
            $revision = TaskRevision::findOrFail($revisionId);
            $currentUser = Auth::user();

            // ✅ التحقق من الصلاحية - فقط المراجع أو الإدارة العليا
            $canReview = false;

            // الإدارة العليا يمكنها مراجعة أي تعديل
            if ($currentUser->hasRole(['hr', 'company_manager', 'project_manager'])) {
                $canReview = true;
            }
            // المراجع الحالي (التالي في الترتيب)
            elseif ($revision->getCurrentReviewer() && $revision->getCurrentReviewer()->id == $currentUser->id) {
                $canReview = true;
            }
            // من أنشأ التعديل (للتوافق مع التعديلات القديمة)
            elseif ($revision->created_by == $currentUser->id) {
                $canReview = true;
            }

            if (!$canReview) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بمراجعة هذا التعديل - فقط المراجع المحدد أو الإدارة العليا'
                ];
            }

            $oldStatus = $revision->status;

            $revision->update([
                'status' => $status,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => $this->getCurrentCairoTime(),
                'review_notes' => $reviewNotes
            ]);

            $this->logRevisionActivity($revision, 'status_updated', [
                'new_status' => $status,
                'review_notes' => $reviewNotes
            ]);

            // إرسال إشعار Slack عن تحديث الحالة
            try {
                $updatedBy = Auth::user();
                $this->slackService->sendRevisionStatusUpdateNotification($revision, $oldStatus, $updatedBy);
            } catch (\Exception $e) {
                Log::warning('Failed to send revision status update Slack notification', [
                    'revision_id' => $revision->id,
                    'error' => $e->getMessage()
                ]);
            }

            return [
                'success' => true,
                'message' => $status === 'approved' ? 'تم الموافقة على التعديل' : 'تم رفض التعديل',
                'revision' => $revision->load(['creator', 'reviewer', 'responsibleUser', 'executorUser'])
            ];

        } catch (\Exception $e) {
            Log::error('Error updating revision status', [
                'revision_id' => $revisionId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في تحديث حالة التعديل'
            ];
        }
    }

    /**
     * حذف تعديل
     */
    public function deleteRevision(int $revisionId): array
    {
        try {
            $revision = TaskRevision::findOrFail($revisionId);

            // التحقق من الصلاحية
            if ($revision->created_by !== Auth::id() && !Auth::user()->hasRole(['hr', 'company_manager', 'project_manager'])) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بحذف هذا التعديل'
                ];
            }

            $this->logRevisionActivity($revision, 'deleted');
            $revision->delete();

            return [
                'success' => true,
                'message' => 'تم حذف التعديل بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Error deleting revision', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في حذف التعديل'
            ];
        }
    }

    /**
     * التحقق من صحة بيانات المهمة
     */
    private function validateTaskData(array $data): array
    {
        if ($data['task_type'] === 'template') {
            $templateTaskUser = TemplateTaskUser::findOrFail($data['task_id']);
            return [
                'task_id' => $data['task_id'],
                'task_instance' => $templateTaskUser
            ];
        } else {
            $task = Task::findOrFail($data['task_id']);
            return [
                'task_id' => $data['task_id'],
                'task_instance' => $task
            ];
        }
    }

    /**
     * معالجة المرفق (ملف أو لينك)
     */
    private function handleAttachment(array $data, string $taskType, $taskId): ?array
    {
        $attachmentType = $data['attachment_type'] ?? 'file';

        if ($attachmentType === 'link' && isset($data['attachment_link']) && $data['attachment_link']) {
            // حفظ اللينك
            return [
                'attachment_link' => $data['attachment_link'],
                'attachment_type' => 'link',
                'attachment_path' => null,
                'attachment_name' => null,
                'attachment_size' => null
            ];
        } elseif ($attachmentType === 'file' && isset($data['attachment']) && $data['attachment']) {
            // رفع الملف
            return $this->handleFileUpload($data['attachment'], $taskType, $taskId);
        }

        return null;
    }

    /**
     * التعامل مع رفع الملف
     */
    private function handleFileUpload($file, string $taskType, $taskId): array
    {
        $directory = "task-revisions/{$taskType}/{$taskId}";

        $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

        $path = $file->storeAs($directory, $filename, 'public');

        return [
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_type' => 'file',
            'attachment_size' => $file->getSize(),
            'attachment_link' => null
        ];
    }


    private function logRevisionActivity(TaskRevision $revision, string $action, array $properties = []): void
    {
        try {
            $baseProperties = [
                'revision_id' => $revision->id,
                'revision_title' => $revision->title,
                'task_type' => $revision->task_type,
                'action' => $action,
                'performed_at' => $this->getCurrentCairoTime()->toDateTimeString()
            ];

            $allProperties = array_merge($baseProperties, $properties);

            activity()
                ->performedOn($revision)
                ->causedBy(Auth::user())
                ->withProperties($allProperties)
                ->log("تعديل المهمة - {$action}");

        } catch (\Exception $e) {
            Log::warning('Failed to log revision activity', [
                'revision_id' => $revision->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }


    public function getAllRevisions(array $filters = []): array
    {
        try {
            $query = TaskRevision::with(['creator', 'reviewer', 'project', 'assignedUser', 'responsibleUser', 'executorUser'])
                                ->latest();

            // تطبيق الفلاتر
            if (isset($filters['revision_type']) && $filters['revision_type']) {
                $query->byType($filters['revision_type']);
            }

            if (isset($filters['status']) && $filters['status']) {
                $query->byStatus($filters['status']);
            }

            if (isset($filters['revision_source']) && $filters['revision_source']) {
                $query->bySource($filters['revision_source']);
            }

            if (isset($filters['project_id']) && $filters['project_id']) {
                $query->where('project_id', $filters['project_id']);
            }

            // تطبيق الفلترة الهرمية
            $query = $this->revisionFilterService->applyHierarchicalRevisionFiltering($query);

            $revisions = $query->paginate(20);

            return [
                'success' => true,
                'revisions' => $revisions
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching all revisions', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات',
                'revisions' => collect()
            ];
        }
    }

    /**
     * الحصول على التعديلات حسب المصدر مع تطبيق الفلترة الهرمية
     */
    public function getRevisionsBySource(string $source, array $filters = []): array
    {
        try {
            $query = TaskRevision::with(['creator', 'reviewer', 'project', 'assignedUser', 'responsibleUser', 'executorUser'])
                                ->bySource($source)
                                ->latest();

            // تطبيق الفلاتر
            if (isset($filters['revision_type'])) {
                $query->byType($filters['revision_type']);
            }

            if (isset($filters['status'])) {
                $query->byStatus($filters['status']);
            }

            if (isset($filters['project_id'])) {
                $query->where('project_id', $filters['project_id']);
            }

            // تطبيق الفلترة الهرمية
            $query = $this->revisionFilterService->applyHierarchicalRevisionFiltering($query);

            $revisions = $query->get();

            return [
                'success' => true,
                'revisions' => $revisions
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching revisions by source', [
                'source' => $source,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات',
                'revisions' => collect()
            ];
        }
    }

    /**
     * الحصول على التعديلات الداخلية
     */
    public function getInternalRevisions(array $filters = []): array
    {
        return $this->getRevisionsBySource('internal', $filters);
    }

    /**
     * الحصول على التعديلات الخارجية
     */
    public function getExternalRevisions(array $filters = []): array
    {
        return $this->getRevisionsBySource('external', $filters);
    }

    /**
     * الحصول على إحصائيات التعديلات
     */
    public function getRevisionStats(string $taskType, string|int $taskId, string|int $taskUserId = null): array
    {
        try {
            // تحويل المعاملات إلى integers
            $taskId = (int) $taskId;
            $taskUserId = $taskUserId ? (int) $taskUserId : null;

            $query = TaskRevision::query();

            if ($taskType === 'template') {
                $query->forTemplateTask($taskId);
            } else {
                $query->forRegularTask($taskId, $taskUserId);
            }

            $total = $query->count();
            $pending = $query->where('status', 'pending')->count();
            $approved = $query->where('status', 'approved')->count();
            $rejected = $query->where('status', 'rejected')->count();

            // إحصائيات حسب المصدر
            $internal = $query->where('revision_source', 'internal')->count();
            $external = $query->where('revision_source', 'external')->count();

            return [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'internal' => $internal,
                'external' => $external
            ];

        } catch (\Exception $e) {
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'internal' => 0,
                'external' => 0
            ];
        }
    }

    /**
     * الحصول على إحصائيات التعديلات للمشروع
     */
    public function getProjectRevisionStats(string|int $projectId): array
    {
        try {
            $projectId = (int) $projectId;

            $query = TaskRevision::forProject($projectId);

            $total = $query->count();
            $pending = $query->where('status', 'pending')->count();
            $approved = $query->where('status', 'approved')->count();
            $rejected = $query->where('status', 'rejected')->count();
            $internal = $query->where('revision_source', 'internal')->count();
            $external = $query->where('revision_source', 'external')->count();

            return [
                'success' => true,
                'stats' => [
                    'total' => $total,
                    'pending' => $pending,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'internal' => $internal,
                    'external' => $external
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching project revision stats', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل إحصائيات التعديلات',
                'stats' => [
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'internal' => 0,
                    'external' => 0
                ]
            ];
        }
    }

    /**
     * إرسال إشعارات داخلية وFirebase للمعنيين بالتعديل
     */
    private function sendInternalNotifications(TaskRevision $revision): void
    {
        try {
            $notifiedUsers = [];
            $creatorName = $revision->creator ? $revision->creator->name : 'مستخدم';

            // 1. إشعار المسؤول (اللي اتخصم عليه)
            if ($revision->responsible_user_id && !in_array($revision->responsible_user_id, $notifiedUsers)) {
                $responsibleUser = \App\Models\User::find($revision->responsible_user_id);
                Log::info('📧 Sending notification to RESPONSIBLE user', [
                    'revision_id' => $revision->id,
                    'user_id' => $revision->responsible_user_id,
                    'user_name' => $responsibleUser?->name ?? 'N/A',
                    'role' => 'مسؤول (اللي اتخصم عليه)',
                    'has_fcm' => $responsibleUser && $responsibleUser->fcm_token ? 'Yes' : 'No'
                ]);

                $this->sendSingleNotification(
                    $revision->responsible_user_id,
                    'revision_assigned_responsible',
                    "تم تسجيل تعديل على مسؤوليتك: {$revision->title}",
                    $revision,
                    "بواسطة {$creatorName}"
                );
                $notifiedUsers[] = $revision->responsible_user_id;
            }

            // 2. إشعار المنفذ
            if ($revision->executor_user_id && !in_array($revision->executor_user_id, $notifiedUsers)) {
                $executorUser = \App\Models\User::find($revision->executor_user_id);
                Log::info('📧 Sending notification to EXECUTOR user', [
                    'revision_id' => $revision->id,
                    'user_id' => $revision->executor_user_id,
                    'user_name' => $executorUser?->name ?? 'N/A',
                    'role' => 'منفذ (اللي هيصلح)',
                    'has_fcm' => $executorUser && $executorUser->fcm_token ? 'Yes' : 'No'
                ]);

                $this->sendSingleNotification(
                    $revision->executor_user_id,
                    'revision_assigned_executor',
                    "تم تكليفك بتنفيذ تعديل: {$revision->title}",
                    $revision,
                    "بواسطة {$creatorName}"
                );
                $notifiedUsers[] = $revision->executor_user_id;
            }

            // 3. إشعار المراجعين (كل المراجعين المضافين)
            if ($revision->reviewers && is_array($revision->reviewers)) {
                Log::info('📧 Processing REVIEWERS notifications', [
                    'revision_id' => $revision->id,
                    'total_reviewers' => count($revision->reviewers),
                    'reviewers_list' => collect($revision->reviewers)->map(function($r) {
                        $user = \App\Models\User::find($r['reviewer_id']);
                        return [
                            'id' => $r['reviewer_id'],
                            'name' => $user?->name ?? 'N/A',
                            'order' => $r['order']
                        ];
                    })->toArray()
                ]);

                foreach ($revision->reviewers as $index => $reviewerData) {
                    $reviewerId = $reviewerData['reviewer_id'];

                    if (!in_array($reviewerId, $notifiedUsers)) {
                        $reviewerUser = \App\Models\User::find($reviewerId);
                        $orderLabel = 'المراجع ' . ($index + 1);
                        $isFirst = $index === 0;

                        Log::info('📧 Sending notification to REVIEWER', [
                            'revision_id' => $revision->id,
                            'user_id' => $reviewerId,
                            'user_name' => $reviewerUser?->name ?? 'N/A',
                            'role' => $orderLabel,
                            'is_first' => $isFirst,
                            'has_fcm' => $reviewerUser && $reviewerUser->fcm_token ? 'Yes' : 'No'
                        ]);

                        $message = $isFirst
                            ? "تم تكليفك بمراجعة تعديل (أنت المراجع الأول): {$revision->title}"
                            : "تم إضافتك كمراجع ({$orderLabel}) لتعديل: {$revision->title}";

                        $this->sendSingleNotification(
                            $reviewerId,
                            'revision_assigned_reviewer',
                            $message,
                            $revision,
                            "بواسطة {$creatorName} - ستتم مراجعتك بعد إتمام المراجع السابق"
                        );
                        $notifiedUsers[] = $reviewerId;
                    } else {
                        Log::warning('⚠️ Reviewer already notified, skipping', [
                            'revision_id' => $revision->id,
                            'user_id' => $reviewerId,
                            'reason' => 'Already in notified users list'
                        ]);
                    }
                }
            }

            // 4. إشعار الشخص المكلف (assigned_to)
            if ($revision->assigned_to && !in_array($revision->assigned_to, $notifiedUsers)) {
                $assignedUser = \App\Models\User::find($revision->assigned_to);
                Log::info('📧 Sending notification to ASSIGNED user', [
                    'revision_id' => $revision->id,
                    'user_id' => $revision->assigned_to,
                    'user_name' => $assignedUser?->name ?? 'N/A',
                    'role' => 'مكلف',
                    'has_fcm' => $assignedUser && $assignedUser->fcm_token ? 'Yes' : 'No'
                ]);

                $this->sendSingleNotification(
                    $revision->assigned_to,
                    'revision_assigned',
                    "تم تكليفك بتعديل جديد: {$revision->title}",
                    $revision,
                    "بواسطة {$creatorName}"
                );
                $notifiedUsers[] = $revision->assigned_to;
            }

            Log::info('✅ Internal notifications sent for revision', [
                'revision_id' => $revision->id,
                'notified_users_count' => count($notifiedUsers),
                'notified_users' => $notifiedUsers
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send internal notifications for revision', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * إرسال إشعار واحد (Database + Firebase)
     */
    private function sendSingleNotification(int $userId, string $type, string $message, TaskRevision $revision, string $extraInfo = ''): void
    {
        try {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                Log::warning('❌ User not found for notification', [
                    'user_id' => $userId,
                    'revision_id' => $revision->id,
                    'type' => $type
                ]);
                return;
            }

            // 1. إنشاء إشعار داخلي (Database)
            $notification = \App\Models\Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'data' => [
                    'message' => $message,
                    'revision_id' => $revision->id,
                    'revision_title' => $revision->title,
                    'revision_type' => $revision->revision_type,
                    'extra_info' => $extraInfo,
                    'created_at' => now()->toDateTimeString()
                ],
                'related_id' => $revision->id
            ]);

            Log::info('✅ Database notification created', [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'user_name' => $user->name,
                'type' => $type,
                'revision_id' => $revision->id
            ]);

            // 2. إرسال Firebase Notification
            if ($user->fcm_token) {
                try {
                    $firebaseService = app(\App\Services\FirebaseNotificationService::class);
                    $firebaseService->sendNotificationQueued(
                        $user->fcm_token,
                        'تعديل جديد',
                        $message,
                        "/revisions?revision_id={$revision->id}"
                    );

                    Log::info('✅ Firebase notification queued', [
                        'user_id' => $userId,
                        'user_name' => $user->name,
                        'revision_id' => $revision->id
                    ]);
                } catch (\Exception $e) {
                    Log::warning('⚠️ Firebase notification failed', [
                        'user_id' => $userId,
                        'user_name' => $user->name,
                        'revision_id' => $revision->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                Log::info('ℹ️ No FCM token for user, skipping Firebase', [
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'revision_id' => $revision->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ Failed to send single notification', [
                'user_id' => $userId,
                'type' => $type,
                'revision_id' => $revision->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
