<?php

namespace App\Services\Tasks;

use App\Models\TaskRevision;
use App\Models\RevisionAssignment;
use App\Models\User;
use App\Models\Season;
use App\Services\Slack\RevisionSlackService;
use App\Traits\HasNTPTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class RevisionTransferService
{
    use HasNTPTime;

    protected $slackService;

    public function __construct(RevisionSlackService $slackService)
    {
        $this->slackService = $slackService;
    }

    /**
     * نقل المنفذ (Executor) - من شخص لشخص آخر
     */
    public function transferExecutor(TaskRevision $revision, User $fromUser, User $toUser, string $reason = null, ?string $newDeadline = null): array
    {
        // ✅ التحقق من الصلاحيات
        $currentUser = Auth::user();
        if (!$this->canTransferExecutor($revision, $currentUser)) {
            return [
                'success' => false,
                'message' => 'غير مسموح لك بنقل المنفذ لهذا التعديل'
            ];
        }

        // ✅ منع نقل التعديل لنفس الشخص
        if ($fromUser->id == $toUser->id) {
            return [
                'success' => false,
                'message' => 'لا يمكن نقل التعديل لنفس الموظف'
            ];
        }

        // ✅ التحقق من أن fromUser هو المنفذ الحالي
        if ($revision->executor_user_id != $fromUser->id) {
            return [
                'success' => false,
                'message' => 'المستخدم المحدد ليس المنفذ الحالي'
            ];
        }

        try {
            return DB::transaction(function () use ($revision, $fromUser, $toUser, $reason, $currentUser, $newDeadline) {
                // تحديث المنفذ
                $revision->update([
                    'executor_user_id' => $toUser->id,
                ]);

                // تحديث أو إنشاء ديدلاين المنفذ الجديد
                if ($newDeadline) {
                    $existingDeadline = \App\Models\RevisionDeadline::where('revision_id', $revision->id)
                        ->where('deadline_type', 'executor')
                        ->first();

                    if ($existingDeadline) {
                        // تحديث ديدلاين موجود
                        $existingDeadline->update([
                            'user_id' => $toUser->id,
                            'deadline_date' => $newDeadline,
                            'last_updated_by' => $currentUser->id,
                        ]);

                        Log::info('✅ Executor deadline updated on transfer', [
                            'revision_id' => $revision->id,
                            'new_executor' => $toUser->id,
                            'new_deadline' => $newDeadline
                        ]);
                    } else {
                        // إنشاء ديدلاين جديد
                        \App\Models\RevisionDeadline::create([
                            'revision_id' => $revision->id,
                            'deadline_type' => 'executor',
                            'user_id' => $toUser->id,
                            'deadline_date' => $newDeadline,
                            'status' => 'pending',
                            'assigned_by' => $currentUser->id,
                            'original_deadline' => $newDeadline,
                        ]);

                        Log::info('✅ New executor deadline created on transfer', [
                            'revision_id' => $revision->id,
                            'new_executor' => $toUser->id,
                            'deadline' => $newDeadline
                        ]);
                    }
                } else {
                    // تحديث user_id في الديدلاين الموجود إذا لم يكن هناك ديدلاين جديد
                    $existingDeadline = \App\Models\RevisionDeadline::where('revision_id', $revision->id)
                        ->where('deadline_type', 'executor')
                        ->first();

                    if ($existingDeadline) {
                        $existingDeadline->update([
                            'user_id' => $toUser->id,
                            'last_updated_by' => $currentUser->id,
                        ]);
                    }
                }

                // تسجيل عملية النقل
                $assignment = RevisionAssignment::create([
                    'revision_id' => $revision->id,
                    'from_user_id' => $fromUser->id,
                    'to_user_id' => $toUser->id,
                    'assigned_by_user_id' => $currentUser->id,
                    'assignment_type' => 'executor',
                    'reason' => $reason,
                ]);

                // تسجيل النشاط
                activity()
                    ->performedOn($revision)
                    ->causedBy($currentUser)
                    ->withProperties([
                        'action' => 'executor_transferred',
                        'from_user' => $fromUser->name,
                        'to_user' => $toUser->name,
                        'reason' => $reason,
                    ])
                    ->log('تم نقل التعديل (المنفذ) من ' . $fromUser->name . ' إلى ' . $toUser->name);

                // إرسال إشعارات
                $this->sendTransferNotifications($revision, $fromUser, $toUser, 'executor', $reason);

                // إرسال إشعار Slack
                try {
                    $this->slackService->sendRevisionExecutorTransferNotification(
                        $revision,
                        $fromUser,
                        $toUser,
                        $currentUser,
                        $reason
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send executor transfer Slack notification', [
                        'revision_id' => $revision->id,
                        'error' => $e->getMessage()
                    ]);
                }

                Log::info('✅ Revision executor transferred successfully', [
                    'revision_id' => $revision->id,
                    'from_user' => $fromUser->name,
                    'to_user' => $toUser->name,
                    'assigned_by' => $currentUser->name
                ]);

                return [
                    'success' => true,
                    'message' => "تم نقل التعديل (المنفذ) بنجاح من {$fromUser->name} إلى {$toUser->name}",
                    'revision' => $revision->fresh(),
                    'assignment' => $assignment
                ];
            });

        } catch (Exception $e) {
            Log::error('Error transferring revision executor', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء نقل التعديل: ' . $e->getMessage()
            ];
        }
    }

    /**
     * نقل المراجع (Reviewer) - تحديث المراجع في القائمة
     */
    public function transferReviewer(TaskRevision $revision, int $reviewerOrder, User $toUser, string $reason = null, ?string $newDeadline = null): array
    {
        // ✅ التحقق من الصلاحيات
        $currentUser = Auth::user();
        if (!$this->canTransferReviewer($revision, $currentUser)) {
            return [
                'success' => false,
                'message' => 'غير مسموح لك بنقل المراجع لهذا التعديل'
            ];
        }

        // ✅ التحقق من وجود مراجعين
        if (!$revision->reviewers || !is_array($revision->reviewers)) {
            return [
                'success' => false,
                'message' => 'لا يوجد مراجعين لهذا التعديل'
            ];
        }

        // ✅ البحث عن المراجع المحدد
        $reviewerIndex = null;
        foreach ($revision->reviewers as $index => $reviewer) {
            if ($reviewer['order'] == $reviewerOrder) {
                $reviewerIndex = $index;
                break;
            }
        }

        if ($reviewerIndex === null) {
            return [
                'success' => false,
                'message' => 'المراجع المحدد غير موجود'
            ];
        }

        $oldReviewerId = $revision->reviewers[$reviewerIndex]['reviewer_id'];
        $fromUser = User::find($oldReviewerId);

        // ✅ منع نقل المراجع لنفس الشخص
        if ($oldReviewerId == $toUser->id) {
            return [
                'success' => false,
                'message' => 'المراجع الجديد هو نفس المراجع الحالي'
            ];
        }

        try {
            return DB::transaction(function () use ($revision, $reviewerIndex, $fromUser, $toUser, $reason, $currentUser, $reviewerOrder, $newDeadline) {
                // تحديث المراجع في القائمة
                $reviewers = $revision->reviewers;
                $oldStatus = $reviewers[$reviewerIndex]['status'];

                $reviewers[$reviewerIndex]['reviewer_id'] = $toUser->id;
                // إعادة تعيين الحالة إلى pending إذا لم يكن completed
                if ($oldStatus !== 'completed') {
                    $reviewers[$reviewerIndex]['status'] = 'pending';
                }

                $revision->update([
                    'reviewers' => $reviewers
                ]);

                // تحديث أو إنشاء ديدلاين المراجع
                if ($newDeadline) {
                    $existingDeadline = \App\Models\RevisionDeadline::where('revision_id', $revision->id)
                        ->where('deadline_type', 'reviewer')
                        ->where('reviewer_order', $reviewerOrder)
                        ->first();

                    if ($existingDeadline) {
                        // تحديث ديدلاين موجود
                        $existingDeadline->update([
                            'user_id' => $toUser->id,
                            'deadline_date' => $newDeadline,
                            'last_updated_by' => $currentUser->id,
                        ]);

                        Log::info('✅ Reviewer deadline updated on transfer', [
                            'revision_id' => $revision->id,
                            'reviewer_order' => $reviewerOrder,
                            'new_reviewer' => $toUser->id,
                            'new_deadline' => $newDeadline
                        ]);
                    } else {
                        // إنشاء ديدلاين جديد
                        \App\Models\RevisionDeadline::create([
                            'revision_id' => $revision->id,
                            'deadline_type' => 'reviewer',
                            'user_id' => $toUser->id,
                            'deadline_date' => $newDeadline,
                            'reviewer_order' => $reviewerOrder,
                            'status' => 'pending',
                            'assigned_by' => $currentUser->id,
                            'original_deadline' => $newDeadline,
                        ]);

                        Log::info('✅ New reviewer deadline created on transfer', [
                            'revision_id' => $revision->id,
                            'reviewer_order' => $reviewerOrder,
                            'new_reviewer' => $toUser->id,
                            'deadline' => $newDeadline
                        ]);
                    }
                } else {
                    // تحديث user_id في الديدلاين الموجود إذا لم يكن هناك ديدلاين جديد
                    $existingDeadline = \App\Models\RevisionDeadline::where('revision_id', $revision->id)
                        ->where('deadline_type', 'reviewer')
                        ->where('reviewer_order', $reviewerOrder)
                        ->first();

                    if ($existingDeadline) {
                        $existingDeadline->update([
                            'user_id' => $toUser->id,
                            'last_updated_by' => $currentUser->id,
                        ]);
                    }
                }

                // تسجيل عملية النقل
                $assignment = RevisionAssignment::create([
                    'revision_id' => $revision->id,
                    'from_user_id' => $fromUser ? $fromUser->id : null,
                    'to_user_id' => $toUser->id,
                    'assigned_by_user_id' => $currentUser->id,
                    'assignment_type' => 'reviewer',
                    'reason' => $reason,
                ]);

                // تسجيل النشاط
                activity()
                    ->performedOn($revision)
                    ->causedBy($currentUser)
                    ->withProperties([
                        'action' => 'reviewer_transferred',
                        'from_user' => $fromUser ? $fromUser->name : 'غير محدد',
                        'to_user' => $toUser->name,
                        'reviewer_order' => $reviewerOrder,
                        'reason' => $reason,
                    ])
                    ->log('تم نقل التعديل (المراجع رقم ' . $reviewerOrder . ') من ' . ($fromUser ? $fromUser->name : 'غير محدد') . ' إلى ' . $toUser->name);

                // إرسال إشعارات
                $this->sendTransferNotifications($revision, $fromUser, $toUser, 'reviewer', $reason);

                // إرسال إشعار Slack
                try {
                    $this->slackService->sendRevisionReviewerTransferNotification(
                        $revision,
                        $fromUser,
                        $toUser,
                        $currentUser,
                        $reviewerOrder,
                        $reason
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send reviewer transfer Slack notification', [
                        'revision_id' => $revision->id,
                        'error' => $e->getMessage()
                    ]);
                }

                Log::info('✅ Revision reviewer transferred successfully', [
                    'revision_id' => $revision->id,
                    'from_user' => $fromUser ? $fromUser->name : 'غير محدد',
                    'to_user' => $toUser->name,
                    'reviewer_order' => $reviewerOrder,
                    'assigned_by' => $currentUser->name
                ]);

                return [
                    'success' => true,
                    'message' => "تم نقل التعديل (المراجع رقم {$reviewerOrder}) بنجاح إلى {$toUser->name}",
                    'revision' => $revision->fresh(),
                    'assignment' => $assignment
                ];
            });

        } catch (Exception $e) {
            Log::error('Error transferring revision reviewer', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء نقل المراجع: ' . $e->getMessage()
            ];
        }
    }

    /**
     * التحقق من إمكانية نقل المنفذ
     */
    protected function canTransferExecutor(TaskRevision $revision, User $user): bool
    {
        // الإدارة العليا
        if ($user->hasRole(['hr', 'company_manager', 'project_manager'])) {
            return true;
        }

        // من أنشأ التعديل
        if ($revision->created_by == $user->id) {
            return true;
        }

        // المنفذ نفسه (يمكنه نقل التعديل لشخص آخر)
        if ($revision->executor_user_id == $user->id) {
            return true;
        }

        return false;
    }

    /**
     * التحقق من إمكانية نقل المراجع
     */
    protected function canTransferReviewer(TaskRevision $revision, User $user): bool
    {
        // الإدارة العليا
        if ($user->hasRole(['hr', 'company_manager', 'project_manager'])) {
            return true;
        }

        // من أنشأ التعديل
        if ($revision->created_by == $user->id) {
            return true;
        }

        return false;
    }

    /**
     * إرسال إشعارات النقل
     */
    protected function sendTransferNotifications(TaskRevision $revision, ?User $fromUser, User $toUser, string $type, ?string $reason): void
    {
        try {
            $typeName = $type === 'executor' ? 'المنفذ' : 'المراجع';
            $currentUser = Auth::user();

            // إشعار للمستلم الجديد
            \App\Models\Notification::create([
                'user_id' => $toUser->id,
                'type' => 'revision_transferred_to_you',
                'data' => [
                    'message' => "تم نقل تعديل ({$typeName}) إليك: {$revision->title}",
                    'revision_id' => $revision->id,
                    'revision_title' => $revision->title,
                    'from_user' => $fromUser ? $fromUser->name : 'غير محدد',
                    'transferred_by' => $currentUser->name,
                    'type' => $type,
                    'reason' => $reason,
                ],
                'related_id' => $revision->id
            ]);

            // إشعار Firebase
            if ($toUser->fcm_token) {
                $firebaseService = app(\App\Services\FirebaseNotificationService::class);
                $firebaseService->sendNotificationQueued(
                    $toUser->fcm_token,
                    'تعديل منقول إليك',
                    "تم نقل تعديل ({$typeName}) إليك: {$revision->title}",
                    "/revisions?revision_id={$revision->id}"
                );
            }

            // إشعار للمُرسِل (إذا كان موجود)
            if ($fromUser) {
                \App\Models\Notification::create([
                    'user_id' => $fromUser->id,
                    'type' => 'revision_transferred_from_you',
                    'data' => [
                        'message' => "تم نقل تعديل ({$typeName}) منك: {$revision->title}",
                        'revision_id' => $revision->id,
                        'revision_title' => $revision->title,
                        'to_user' => $toUser->name,
                        'transferred_by' => $currentUser->name,
                        'type' => $type,
                        'reason' => $reason,
                    ],
                    'related_id' => $revision->id
                ]);

                // إشعار Firebase
                if ($fromUser->fcm_token) {
                    $firebaseService = app(\App\Services\FirebaseNotificationService::class);
                    $firebaseService->sendNotificationQueued(
                        $fromUser->fcm_token,
                        'تعديل منقول منك',
                        "تم نقل تعديل ({$typeName}) منك: {$revision->title}",
                        "/revisions?revision_id={$revision->id}"
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send transfer notifications', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * الحصول على سجل نقل التعديل
     */
    public function getRevisionTransferHistory(TaskRevision $revision): array
    {
        $assignments = RevisionAssignment::with(['fromUser', 'toUser', 'assignedBy'])
            ->where('revision_id', $revision->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $assignments->map(function($assignment) {
            return [
                'id' => $assignment->id,
                'from_user' => $assignment->fromUser ? $assignment->fromUser->name : 'غير محدد',
                'to_user' => $assignment->toUser ? $assignment->toUser->name : 'غير محدد',
                'assigned_by' => $assignment->assignedBy ? $assignment->assignedBy->name : 'النظام',
                'assignment_type' => $assignment->assignment_type === 'executor' ? 'المنفذ' : 'المراجع',
                'reason' => $assignment->reason,
                'transferred_at' => $assignment->transferred_at ? $assignment->transferred_at->format('Y-m-d H:i') : $assignment->created_at->format('Y-m-d H:i'),
            ];
        })->toArray();
    }

    /**
     * الحصول على إحصائيات نقل التعديلات للمستخدم
     */
    public function getUserTransferStats(User $user, ?Season $season = null): array
    {
        $query = RevisionAssignment::query();

        if ($season) {
            $query->whereHas('revision', function($q) use ($season) {
                $q->where('season_id', $season->id);
            });
        }

        // التعديلات المنقولة إليه
        $transferredTo = (clone $query)->where('to_user_id', $user->id)->count();
        $executorTransferredTo = (clone $query)->where('to_user_id', $user->id)
            ->where('assignment_type', 'executor')->count();
        $reviewerTransferredTo = (clone $query)->where('to_user_id', $user->id)
            ->where('assignment_type', 'reviewer')->count();

        // التعديلات المنقولة منه
        $transferredFrom = (clone $query)->where('from_user_id', $user->id)->count();
        $executorTransferredFrom = (clone $query)->where('from_user_id', $user->id)
            ->where('assignment_type', 'executor')->count();
        $reviewerTransferredFrom = (clone $query)->where('from_user_id', $user->id)
            ->where('assignment_type', 'reviewer')->count();

        return [
            'transferred_to_me' => $transferredTo,
            'executor_transferred_to_me' => $executorTransferredTo,
            'reviewer_transferred_to_me' => $reviewerTransferredTo,
            'transferred_from_me' => $transferredFrom,
            'executor_transferred_from_me' => $executorTransferredFrom,
            'reviewer_transferred_from_me' => $reviewerTransferredFrom,
            'has_transfers' => $transferredTo > 0 || $transferredFrom > 0,
        ];
    }
}

