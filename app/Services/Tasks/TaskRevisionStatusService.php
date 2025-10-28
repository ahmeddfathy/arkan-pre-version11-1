<?php

namespace App\Services\Tasks;

use App\Models\TaskRevision;
use App\Models\User;
use App\Services\Slack\RevisionSlackService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskRevisionStatusService
{
    protected $slackService;

    public function __construct(RevisionSlackService $slackService)
    {
        $this->slackService = $slackService;
    }
    /**
     * التحقق من أن التعديل مسند للمستخدم الحالي
     */
    protected function isAssignedToCurrentUser(TaskRevision $revision): bool
    {
        $userId = Auth::id();

        // Debug logging للبيانات اللي جاية
        Log::info('=== REVISION ASSIGNMENT DEBUG ===', [
            'current_user_id' => $userId,
            'revision_id' => $revision->id,
            'revision_assigned_to' => $revision->assigned_to,
            'executor_user_id' => $revision->executor_user_id,
            'assigned_reviewer_id' => $revision->assigned_reviewer_id,
            'responsible_user_id' => $revision->responsible_user_id,
            'revision_task_user_exists' => $revision->taskUser ? 'YES' : 'NO',
            'revision_task_user_id' => $revision->taskUser ? $revision->taskUser->user_id : 'NULL',
            'revision_template_task_user_exists' => $revision->templateTaskUser ? 'YES' : 'NO',
            'revision_template_task_user_id' => $revision->templateTaskUser ? $revision->templateTaskUser->user_id : 'NULL',
        ]);

        // ✅ التحقق من المنفذ (اللي هيشتغل على التعديل)
        if ($revision->executor_user_id == $userId) {
            Log::info('✅ ASSIGNED AS EXECUTOR', [
                'executor_user_id' => $revision->executor_user_id,
                'current_user_id' => $userId,
                'match' => 'YES'
            ]);
            return true;
        }

        // التحقق المباشر من assigned_to (للتوافق مع التعديلات القديمة)
        if ($revision->assigned_to == $userId) {
            Log::info('✅ ASSIGNED VIA DIRECT ASSIGNMENT', [
                'revision_assigned_to' => $revision->assigned_to,
                'current_user_id' => $userId,
                'match' => 'YES'
            ]);
            return true;
        }

        // التحقق من TaskUser
        if ($revision->taskUser && $revision->taskUser->user_id == $userId) {
            Log::info('✅ ASSIGNED VIA TASK_USER', [
                'task_user_id' => $revision->taskUser->user_id,
                'current_user_id' => $userId,
                'match' => 'YES'
            ]);
            return true;
        }

        // التحقق من TemplateTaskUser
        if ($revision->templateTaskUser && $revision->templateTaskUser->user_id == $userId) {
            Log::info('✅ ASSIGNED VIA TEMPLATE_TASK_USER', [
                'template_task_user_id' => $revision->templateTaskUser->user_id,
                'current_user_id' => $userId,
                'match' => 'YES'
            ]);
            return true;
        }

        Log::warning('❌ USER NOT ASSIGNED TO REVISION', [
            'current_user_id' => $userId,
            'revision_id' => $revision->id,
            'assigned_to' => $revision->assigned_to,
            'task_user_exists' => $revision->taskUser ? 'YES' : 'NO',
            'template_task_user_exists' => $revision->templateTaskUser ? 'YES' : 'NO'
        ]);

        Log::warning('User not assigned to this revision');
        return false;
    }

    /**
     * بدء العمل على تعديل
     */
    public function startRevision(TaskRevision $revision): array
    {
        try {
            // التحقق من أن المستخدم هو المسند له التعديل
            if (!$this->isAssignedToCurrentUser($revision)) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك ببدء هذا التعديل - التعديل مسند لشخص آخر'
                ];
            }

            // التحقق من وجود تعديل آخر قيد التنفيذ لنفس المستخدم
            $userId = Auth::id();
            $activeRevision = TaskRevision::where('status', 'in_progress')
                ->where(function($query) use ($userId) {
                    $query->where('assigned_to', $userId)
                        ->orWhereHas('taskUser', function($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->orWhereHas('templateTaskUser', function($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->where('id', '!=', $revision->id)
                ->first();

            if ($activeRevision) {
                return [
                    'success' => false,
                    'message' => 'لديك تعديل آخر قيد التنفيذ. يرجى إيقافه أو إكماله أولاً',
                    'active_revision_id' => $activeRevision->id,
                    'active_revision_title' => $activeRevision->title
                ];
            }

            // التحقق من أن التعديل في حالة جديد أو متوقف
            if (!in_array($revision->status, ['new', 'paused'])) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن بدء تعديل في حالة ' . $revision->status_text
                ];
            }

            $revision->startWork();

            Log::info('Revision work started', [
                'revision_id' => $revision->id,
                'user_id' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => 'تم بدء العمل على التعديل',
                'revision' => $revision->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('Error starting revision work', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء بدء العمل: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إيقاف مؤقت للعمل على التعديل
     */
    public function pauseRevision(TaskRevision $revision): array
    {
        try {
            if (!$this->isAssignedToCurrentUser($revision)) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بإيقاف هذا التعديل - التعديل مسند لشخص آخر'
                ];
            }

            if ($revision->status !== 'in_progress') {
                return [
                    'success' => false,
                    'message' => 'لا يمكن إيقاف تعديل غير نشط'
                ];
            }

            $revision->pauseWork();

            Log::info('Revision work paused', [
                'revision_id' => $revision->id,
                'user_id' => Auth::id(),
                'actual_minutes' => $revision->actual_minutes
            ]);

            return [
                'success' => true,
                'message' => 'تم إيقاف العمل مؤقتاً',
                'revision' => $revision->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('Error pausing revision work', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء الإيقاف: ' . $e->getMessage()
            ];
        }
    }

    /**
     * استئناف العمل على التعديل
     */
    public function resumeRevision(TaskRevision $revision): array
    {
        try {
            if (!$this->isAssignedToCurrentUser($revision)) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك باستئناف هذا التعديل - التعديل مسند لشخص آخر'
                ];
            }

            // التحقق من وجود تعديل آخر قيد التنفيذ لنفس المستخدم
            $userId = Auth::id();
            $activeRevision = TaskRevision::where('status', 'in_progress')
                ->where(function($query) use ($userId) {
                    $query->where('assigned_to', $userId)
                        ->orWhereHas('taskUser', function($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->orWhereHas('templateTaskUser', function($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->where('id', '!=', $revision->id)
                ->first();

            if ($activeRevision) {
                return [
                    'success' => false,
                    'message' => 'لديك تعديل آخر قيد التنفيذ. يرجى إيقافه أو إكماله أولاً',
                    'active_revision_id' => $activeRevision->id,
                    'active_revision_title' => $activeRevision->title
                ];
            }

            if ($revision->status !== 'paused') {
                return [
                    'success' => false,
                    'message' => 'لا يمكن استئناف تعديل غير متوقف'
                ];
            }

            $revision->resumeWork();

            Log::info('Revision work resumed', [
                'revision_id' => $revision->id,
                'user_id' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => 'تم استئناف العمل',
                'revision' => $revision->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('Error resuming revision work', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء الاستئناف: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إكمال التعديل
     */
    public function completeRevision(TaskRevision $revision): array
    {
        try {
            if (!$this->isAssignedToCurrentUser($revision)) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بإكمال هذا التعديل - التعديل مسند لشخص آخر'
                ];
            }

            if ($revision->status === 'completed') {
                return [
                    'success' => false,
                    'message' => 'التعديل مكتمل بالفعل'
                ];
            }

            $revision->completeWork();

            Log::info('Revision work completed', [
                'revision_id' => $revision->id,
                'user_id' => Auth::id(),
                'total_minutes' => $revision->actual_minutes
            ]);

            // إرسال إشعار Slack عن اكتمال التعديل
            try {
                $this->slackService->sendRevisionCompletedNotification($revision);
            } catch (\Exception $e) {
                Log::warning('Failed to send revision completed Slack notification', [
                    'revision_id' => $revision->id,
                    'error' => $e->getMessage()
                ]);
            }

            return [
                'success' => true,
                'message' => 'تم إكمال التعديل بنجاح',
                'revision' => $revision->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('Error completing revision work', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء الإكمال: ' . $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على التعديل النشط للمستخدم
     */
    public function getActiveRevision(?User $user = null): ?TaskRevision
    {
        $user = $user ?? Auth::user();

        return TaskRevision::activeForUser($user->id)->first();
    }

    /**
     * التحقق من وجود تعديل نشط للمستخدم
     */
    public function hasActiveRevision(?User $user = null): bool
    {
        return $this->getActiveRevision($user) !== null;
    }

    /**
     * الحصول على إحصائيات التعديلات للمستخدم
     */
    public function getUserRevisionStats(?User $user = null): array
    {
        $user = $user ?? Auth::user();

        $revisions = TaskRevision::where('created_by', $user->id);

        return [
            'total' => $revisions->count(),
            'new' => $revisions->where('status', 'new')->count(),
            'in_progress' => $revisions->where('status', 'in_progress')->count(),
            'paused' => $revisions->where('status', 'paused')->count(),
            'completed' => $revisions->where('status', 'completed')->count(),
            'total_minutes' => $revisions->sum('actual_minutes'),
        ];
    }
}
