<?php

namespace App\Services\Tasks;

use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\User;
use App\Models\Season;
use App\Models\UserSeasonPoint;
use App\Services\BadgeService;
use App\Services\Slack\TaskTransferSlackService;
use App\Traits\HasNTPTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;


class TaskTransferService
{
    use HasNTPTime;

    protected $badgeService;
    protected $slackService;

    public function __construct(
        BadgeService $badgeService,
        TaskTransferSlackService $slackService
    ) {
        $this->badgeService = $badgeService;
        $this->slackService = $slackService;
    }

    /**
     * نقل مهمة عادية - نسخة محدثة
     * إذا كانت المهمة منقولة سابقاً، نعدل الشخص المستلم فقط
     * إذا كانت مهمة جديدة، ننشئ سجل جديد
     */
    public function transferTask(TaskUser $taskUser, User $toUser, int $transferPoints, string $reason = null, string $transferType = 'positive', $newDeadline = null): array
    {
        // ✅ منع نقل المهمة لنفس الشخص
        if ($taskUser->user_id == $toUser->id) {
            Log::warning('🚫 Attempted to transfer task to same user', [
                'task_user_id' => $taskUser->id,
                'user_id' => $toUser->id,
                'user_name' => $toUser->name
            ]);

            return [
                'success' => false,
                'message' => 'لا يمكن نقل المهمة لنفس الموظف الحالي',
                'error_type' => 'same_user'
            ];
        }

        // ✅ التحقق قبل الـ transaction: هل هذه مهمة إضافية منقولة سابقاً؟
        if ($taskUser->is_additional_task && $taskUser->task_source === 'transferred') {
            // ✅ منع إرجاع المهمة للموظف الأصلي
            if ($taskUser->original_task_user_id) {
                $originalTaskUser = TaskUser::find($taskUser->original_task_user_id);
                if ($originalTaskUser && $originalTaskUser->user_id == $toUser->id) {
                    return [
                        'success' => false,
                        'message' => 'لا يمكن إرجاع المهمة للموظف الذي تم نقلها منه أصلاً',
                        'error_type' => 'return_to_original_owner'
                    ];
                }
            }
        }

        // ✅ التحقق من أن المستخدم الجديد له نفس الـ role في المشروع
        $task = $taskUser->task;
        if ($task && $task->project_id) {
            $fromUserProjectRole = \App\Models\ProjectServiceUser::where('project_id', $task->project_id)
                ->where('user_id', $taskUser->user_id)
                ->first();

            $toUserProjectRole = \App\Models\ProjectServiceUser::where('project_id', $task->project_id)
                ->where('user_id', $toUser->id)
                ->first();

            Log::info('🔍 Checking role match for regular task transfer', [
                'project_id' => $task->project_id,
                'from_user_id' => $taskUser->user_id,
                'to_user_id' => $toUser->id,
                'from_user_role_id' => $fromUserProjectRole ? $fromUserProjectRole->role_id : null,
                'to_user_role_id' => $toUserProjectRole ? $toUserProjectRole->role_id : null,
                'from_user_role_name' => $fromUserProjectRole && $fromUserProjectRole->role ? $fromUserProjectRole->role->name : 'غير محدد',
                'to_user_role_name' => $toUserProjectRole && $toUserProjectRole->role ? $toUserProjectRole->role->name : 'غير محدد'
            ]);

            // التحقق من وجود المستخدم الجديد في المشروع
            if (!$toUserProjectRole) {
                Log::warning('🚫 User not in project', [
                    'to_user_id' => $toUser->id,
                    'project_id' => $task->project_id
                ]);
                return [
                    'success' => false,
                    'message' => 'المستخدم المستهدف غير مشارك في المشروع',
                    'error_type' => 'user_not_in_project'
                ];
            }

            // التحقق من تطابق الـ roles
            if ($fromUserProjectRole && $fromUserProjectRole->role_id !== $toUserProjectRole->role_id) {
                $fromRoleName = $fromUserProjectRole->role ? $fromUserProjectRole->role->name : 'غير محدد';
                $toRoleName = $toUserProjectRole->role ? $toUserProjectRole->role->name : 'غير محدد';

                Log::warning('🚫 Role mismatch detected', [
                    'from_role_id' => $fromUserProjectRole->role_id,
                    'to_role_id' => $toUserProjectRole->role_id,
                    'from_role_name' => $fromRoleName,
                    'to_role_name' => $toRoleName
                ]);

                return [
                    'success' => false,
                    'message' => "لا يمكن نقل المهمة. المستخدم الأصلي له دور ({$fromRoleName}) والمستخدم المستهدف له دور ({$toRoleName}). يجب أن يكون لهما نفس الدور في المشروع",
                    'error_type' => 'role_mismatch',
                    'from_role' => $fromRoleName,
                    'to_role' => $toRoleName
                ];
            }

            Log::info('✅ Role check passed', [
                'from_role_id' => $fromUserProjectRole ? $fromUserProjectRole->role_id : null,
                'to_role_id' => $toUserProjectRole ? $toUserProjectRole->role_id : null
            ]);
        }

        try {
            return DB::transaction(function () use ($taskUser, $toUser, $transferPoints, $reason, $transferType, $newDeadline) {
                $fromUser = $taskUser->user;
                $season = $taskUser->season ?: Season::where('is_active', true)->first();

                if (!$season) {
                    throw new Exception('لا يوجد موسم نشط');
                }

                // ✅ التحقق: هل هذه مهمة إضافية منقولة سابقاً؟
                if ($taskUser->is_additional_task && $taskUser->task_source === 'transferred') {

                    // 🔄 فقط تعديل الشخص المستلم - لا ننشئ سجل جديد
                    Log::info('Updating transferred task recipient', [
                        'task_user_id' => $taskUser->id,
                        'old_user' => $fromUser->name,
                        'new_user' => $toUser->name
                    ]);

                    // تحديث الشخص المستلم في السجل الموجود
                    $taskUser->update([
                        'user_id' => $toUser->id,
                        'transfer_reason' => $reason,
                        'due_date' => $newDeadline ?? $taskUser->due_date,
                    ]);

                    // إدارة النقاط للشخص الجديد فقط (لأن الأصلي خُصمت منه سابقاً)
                    if ($transferType === 'positive') {
                        $this->addPoints($toUser, $transferPoints, $season, [
                            'reason' => 'استلام مهمة منقولة إيجابياً (تعديل المستلم)',
                            'task_id' => $taskUser->task_id,
                            'transferred_from' => $fromUser->id,
                            'transfer_reason' => $reason
                        ]);
                    }

                $result = [
                    'success' => true,
                    'message' => "تم تعديل المستلم بنجاح من {$fromUser->name} إلى {$toUser->name}",
                    'updated_task_user' => $taskUser->fresh(),
                    'transfer_info' => [
                        'method' => 'update_recipient',
                        'updated_record_id' => $taskUser->id,
                        'transfer_type' => $transferType
                    ]
                ];

                // إرسال إشعارات Slack
                try {
                    $this->slackService->sendTaskTransferNotifications(
                        $taskUser,
                        null,
                        $fromUser,
                        $toUser,
                        $transferType,
                        $transferPoints,
                        $reason
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send task transfer Slack notifications', [
                        'task_user_id' => $taskUser->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return $result;
                }

                // 🆕 مهمة أصلية - ننشئ سجل جديد
                // 1️⃣ إنشاء سجل جديد كامل للموظف الجديد (نسخة من الأصل)
                $newTaskUser = TaskUser::create([
                    'task_id' => $taskUser->task_id,
                    'user_id' => $toUser->id,
                    'season_id' => $taskUser->season_id,
                    'original_task_user_id' => $taskUser->id, // مرجع للسجل الأصلي
                    'role' => $taskUser->role,
                    'status' => 'new', // يبدأ من جديد

                    // 🚩 تحديد إن دي مهمة إضافية منقولة للموظف الجديد
                    'is_additional_task' => true, // مهمة إضافية منقولة
                    'task_source' => 'transferred', // مصدر المهمة: منقولة

                    // نسخ التقديرات الزمنية
                    'estimated_hours' => $taskUser->estimated_hours,
                    'estimated_minutes' => $taskUser->estimated_minutes,
                    'is_flexible_time' => $taskUser->is_flexible_time,
                    'due_date' => $newDeadline ?? $taskUser->due_date, // deadline جديد أو نفس الأصلي

                    // أوقات جديدة للموظف الجديد (من الصفر)
                    'actual_hours' => 0,
                    'actual_minutes' => 0,
                    'start_date' => null,
                    'completed_date' => null,
                ]);

                // نسخ البنود من TaskUser الأصلي إلى الجديد
                if ($taskUser->items) {
                    // نسخ البنود من الأصلي مع إعادة حالتها إلى pending
                    $itemsCopy = array_map(function($item) {
                        $item['status'] = 'pending';
                        $item['note'] = null;
                        $item['completed_at'] = null;
                        $item['completed_by'] = null;
                        return $item;
                    }, $taskUser->items);
                    $newTaskUser->items = $itemsCopy;
                    $newTaskUser->save();
                }

                // 2️⃣ تحديث السجل الأصلي بمعلومات النقل
                // ✅ حساب الوقت المستخدم حتى لحظة النقل (إذا كانت المهمة قيد التنفيذ)
                $previousStatus = $taskUser->status;
                $currentTime = $this->getCurrentCairoTime();

                // إذا كانت المهمة قيد التنفيذ، نحسب الوقت المستخدم ونضيفه للـ actual_hours/actual_minutes
                if ($previousStatus === 'in_progress' && $taskUser->start_date) {
                    $taskTimeSplitService = app(\App\Services\Tasks\TaskTimeSplitService::class);
                    $startTime = \Carbon\Carbon::parse($taskUser->start_date);

                    $minutesSpent = $taskTimeSplitService->calculateAndUpdateCheckpoint(
                        $taskUser->id,
                        false,
                        $startTime,
                        $currentTime,
                        $taskUser->user_id
                    );

                    $totalMinutes = ($taskUser->actual_hours * 60) + $taskUser->actual_minutes + $minutesSpent;
                    $hours = intdiv($totalMinutes, 60);
                    $minutes = $totalMinutes % 60;

                    // ✅ تحديث حالة المهمة الأصلية إلى paused مع حفظ الوقت المستخدم
                    $taskUser->update([
                        'is_transferred' => true,
                        'transferred_to_user_id' => $toUser->id,
                        'transferred_record_id' => $newTaskUser->id,
                        'transferred_at' => $currentTime,
                        'transfer_type' => $transferType,
                        'transfer_reason' => $reason,
                        'transfer_points' => $transferPoints,
                        'status' => 'paused', // ✅ تغيير الحالة إلى paused عند النقل
                        'actual_hours' => $hours, // ✅ حفظ الوقت المستخدم
                        'actual_minutes' => $minutes, // ✅ حفظ الوقت المستخدم
                        'start_date' => null, // ✅ إيقاف التايمر
                    ]);
                } else {
                    // إذا لم تكن قيد التنفيذ، فقط نحدث الحالة
                    $taskUser->update([
                        'is_transferred' => true,
                        'transferred_to_user_id' => $toUser->id,
                        'transferred_record_id' => $newTaskUser->id,
                        'transferred_at' => $currentTime,
                        'transfer_type' => $transferType,
                        'transfer_reason' => $reason,
                        'transfer_points' => $transferPoints,
                        'status' => 'paused', // ✅ تغيير الحالة إلى paused عند النقل
                    ]);
                }

                // 3️⃣ إدارة النقاط
                if ($transferType === 'negative' && $transferPoints > 0) {
                    $this->deductPoints($fromUser, $transferPoints, $season, [
                        'reason' => 'نقل مهمة سلبي',
                        'task_id' => $taskUser->task_id,
                        'transferred_to' => $toUser->id,
                        'transfer_reason' => $reason
                    ]);
                }

                if ($transferType === 'positive') {
                    $this->addPoints($toUser, $transferPoints, $season, [
                        'reason' => 'استلام مهمة منقولة إيجابياً',
                        'task_id' => $taskUser->task_id,
                        'transferred_from' => $fromUser->id,
                        'transfer_reason' => $reason
                    ]);
                }

                // ✅ معلومات النقل محفوظة في السجلات نفسها - لا حاجة لجدول منفصل

                $transferTypeText = $transferType === 'positive' ? 'إيجابي' : 'سلبي';
                $pointsText = $transferType === 'positive' ? 'بدون خصم نقاط' : "مع خصم {$transferPoints} نقطة";

                Log::info('Task transferred successfully', [
                    'original_task_user_id' => $taskUser->id,
                    'new_task_user_id' => $newTaskUser->id,
                    'from_user' => $fromUser->name,
                    'to_user' => $toUser->name,
                    'transfer_type' => $transferType
                ]);

                $result = [
                    'success' => true,
                    'message' => "تم النقل {$transferTypeText} بنجاح من {$fromUser->name} إلى {$toUser->name} - {$pointsText}",
                    'original_task_user' => $taskUser->fresh(),
                    'new_task_user' => $newTaskUser,
                    'transfer_info' => [
                        'method' => 'separate_records',
                        'original_record_id' => $taskUser->id,
                        'new_record_id' => $newTaskUser->id,
                        'transfer_type' => $transferType
                    ]
                ];

                // إرسال إشعارات Slack
                try {
                    $this->slackService->sendTaskTransferNotifications(
                        $taskUser,
                        $newTaskUser,
                        $fromUser,
                        $toUser,
                        $transferType,
                        $transferPoints,
                        $reason
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send task transfer Slack notifications', [
                        'original_task_user_id' => $taskUser->id,
                        'new_task_user_id' => $newTaskUser->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return $result;
            });

        } catch (Exception $e) {
            Log::error('Error in task transfer', [
                'task_user_id' => $taskUser->id,
                'to_user_id' => $toUser->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء نقل المهمة: ' . $e->getMessage()
            ];
        }
    }

    /**
     * نقل مهمة قالب - نسخة محدثة
     * إذا كانت المهمة منقولة سابقاً، نعدل الشخص المستلم فقط
     * إذا كانت مهمة جديدة، ننشئ سجل جديد
     */
    public function transferTemplateTask(TemplateTaskUser $templateTaskUser, User $toUser, int $transferPoints, string $reason = null, string $transferType = 'positive', $newDeadline = null): array
    {
        Log::info('🔍 Checking template task transfer', [
            'template_task_user_id' => $templateTaskUser->id,
            'is_additional_task' => $templateTaskUser->is_additional_task,
            'task_source' => $templateTaskUser->task_source,
            'original_template_task_user_id' => $templateTaskUser->original_template_task_user_id,
            'to_user_id' => $toUser->id
        ]);

        // ✅ منع نقل المهمة لنفس الشخص
        if ($templateTaskUser->user_id == $toUser->id) {
            Log::warning('🚫 Attempted to transfer template task to same user', [
                'template_task_user_id' => $templateTaskUser->id,
                'user_id' => $toUser->id,
                'user_name' => $toUser->name
            ]);

            return [
                'success' => false,
                'message' => 'لا يمكن نقل المهمة لنفس الموظف الحالي',
                'error_type' => 'same_user'
            ];
        }

        // ✅ التحقق قبل الـ transaction: هل هذه مهمة إضافية منقولة سابقاً؟
        if ($templateTaskUser->is_additional_task && $templateTaskUser->task_source === 'transferred') {
            Log::info('✅ This is a transferred additional task');

            // ✅ منع إرجاع المهمة للموظف الأصلي
            if ($templateTaskUser->original_template_task_user_id) {
                $originalTemplateTaskUser = TemplateTaskUser::find($templateTaskUser->original_template_task_user_id);

                Log::info('🔍 Original task user found', [
                    'original_user_id' => $originalTemplateTaskUser ? $originalTemplateTaskUser->user_id : null,
                    'target_user_id' => $toUser->id,
                    'are_same' => $originalTemplateTaskUser && $originalTemplateTaskUser->user_id == $toUser->id
                ]);

                if ($originalTemplateTaskUser && $originalTemplateTaskUser->user_id == $toUser->id) {
                    Log::warning('🚫 Prevented return to original owner!');
                    return [
                        'success' => false,
                        'message' => 'لا يمكن إرجاع المهمة للموظف الذي تم نقلها منه أصلاً',
                        'error_type' => 'return_to_original_owner'
                    ];
                }
            }
        }

        // ✅ التحقق من أن المستخدم الجديد له نفس الـ role في المشروع
        $templateTask = $templateTaskUser->templateTask;
        if ($templateTask && $templateTaskUser->project_id) {
            $fromUserProjectRole = \App\Models\ProjectServiceUser::where('project_id', $templateTaskUser->project_id)
                ->where('user_id', $templateTaskUser->user_id)
                ->first();

            $toUserProjectRole = \App\Models\ProjectServiceUser::where('project_id', $templateTaskUser->project_id)
                ->where('user_id', $toUser->id)
                ->first();

            Log::info('🔍 Checking role match for template task transfer', [
                'project_id' => $templateTaskUser->project_id,
                'from_user_id' => $templateTaskUser->user_id,
                'to_user_id' => $toUser->id,
                'from_user_role_id' => $fromUserProjectRole ? $fromUserProjectRole->role_id : null,
                'to_user_role_id' => $toUserProjectRole ? $toUserProjectRole->role_id : null,
                'from_user_role_name' => $fromUserProjectRole && $fromUserProjectRole->role ? $fromUserProjectRole->role->name : 'غير محدد',
                'to_user_role_name' => $toUserProjectRole && $toUserProjectRole->role ? $toUserProjectRole->role->name : 'غير محدد'
            ]);

            // التحقق من وجود المستخدم الجديد في المشروع
            if (!$toUserProjectRole) {
                Log::warning('🚫 User not in project', [
                    'to_user_id' => $toUser->id,
                    'project_id' => $templateTaskUser->project_id
                ]);
                return [
                    'success' => false,
                    'message' => 'المستخدم المستهدف غير مشارك في المشروع',
                    'error_type' => 'user_not_in_project'
                ];
            }

            // التحقق من تطابق الـ roles
            if ($fromUserProjectRole && $fromUserProjectRole->role_id !== $toUserProjectRole->role_id) {
                $fromRoleName = $fromUserProjectRole->role ? $fromUserProjectRole->role->name : 'غير محدد';
                $toRoleName = $toUserProjectRole->role ? $toUserProjectRole->role->name : 'غير محدد';

                Log::warning('🚫 Role mismatch detected', [
                    'from_role_id' => $fromUserProjectRole->role_id,
                    'to_role_id' => $toUserProjectRole->role_id,
                    'from_role_name' => $fromRoleName,
                    'to_role_name' => $toRoleName
                ]);

                return [
                    'success' => false,
                    'message' => "لا يمكن نقل المهمة. المستخدم الأصلي له دور ({$fromRoleName}) والمستخدم المستهدف له دور ({$toRoleName}). يجب أن يكون لهما نفس الدور في المشروع",
                    'error_type' => 'role_mismatch',
                    'from_role' => $fromRoleName,
                    'to_role' => $toRoleName
                ];
            }

            Log::info('✅ Role check passed', [
                'from_role_id' => $fromUserProjectRole ? $fromUserProjectRole->role_id : null,
                'to_role_id' => $toUserProjectRole ? $toUserProjectRole->role_id : null
            ]);
        }

        try {
            return DB::transaction(function () use ($templateTaskUser, $toUser, $transferPoints, $reason, $transferType, $newDeadline) {
                $fromUser = $templateTaskUser->user;
                $season = $templateTaskUser->season ?: Season::where('is_active', true)->first();

                if (!$season) {
                    throw new Exception('لا يوجد موسم نشط');
                }

                // ✅ التحقق: هل هذه مهمة إضافية منقولة سابقاً؟
                if ($templateTaskUser->is_additional_task && $templateTaskUser->task_source === 'transferred') {

                    // 🔄 فقط تعديل الشخص المستلم - لا ننشئ سجل جديد
                    Log::info('Updating transferred template task recipient', [
                        'template_task_user_id' => $templateTaskUser->id,
                        'old_user' => $fromUser->name,
                        'new_user' => $toUser->name
                    ]);

                    // تحديث الشخص المستلم في السجل الموجود
                    $templateTaskUser->update([
                        'user_id' => $toUser->id,
                        'transfer_reason' => $reason,
                        'deadline' => $newDeadline ?? $templateTaskUser->deadline,
                    ]);

                    // إدارة النقاط للشخص الجديد فقط (لأن الأصلي خُصمت منه سابقاً)
                    if ($transferType === 'positive') {
                        $this->addPoints($toUser, $transferPoints, $season, [
                            'reason' => 'استلام مهمة قالب منقولة إيجابياً (تعديل المستلم)',
                            'template_task_id' => $templateTaskUser->template_task_id,
                            'transferred_from' => $fromUser->id,
                            'transfer_reason' => $reason
                        ]);
                    }

                    $result = [
                        'success' => true,
                        'message' => "تم تعديل المستلم لمهمة القالب بنجاح من {$fromUser->name} إلى {$toUser->name}",
                        'updated_template_task_user' => $templateTaskUser->fresh(),
                        'transfer_info' => [
                            'method' => 'update_recipient',
                            'updated_record_id' => $templateTaskUser->id,
                            'transfer_type' => $transferType
                        ]
                    ];

                    // إرسال إشعارات Slack
                    try {
                        $this->slackService->sendTemplateTaskTransferNotifications(
                            $templateTaskUser,
                            null,
                            $fromUser,
                            $toUser,
                            $transferType,
                            $transferPoints,
                            $reason
                        );
                    } catch (\Exception $e) {
                        Log::warning('Failed to send template task transfer Slack notifications', [
                            'template_task_user_id' => $templateTaskUser->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    return $result;
                }

                // 🆕 مهمة أصلية - ننشئ سجل جديد
                // 1️⃣ إنشاء سجل جديد كامل للموظف الجديد (نسخة من الأصل)
                $newTemplateTaskUser = TemplateTaskUser::create([
                    'template_task_id' => $templateTaskUser->template_task_id,
                    'user_id' => $toUser->id,
                    'project_id' => $templateTaskUser->project_id,
                    'season_id' => $templateTaskUser->season_id,
                    'original_template_task_user_id' => $templateTaskUser->id, // مرجع للسجل الأصلي
                    'assigned_by' => $templateTaskUser->assigned_by,
                    'assigned_at' => $this->getCurrentCairoTime(), // تاريخ التخصيص الجديد
                    'status' => 'new', // يبدأ من جديد
                    'deadline' => $newDeadline ?? $templateTaskUser->deadline, // deadline جديد أو نفس الأصلي

                    'is_additional_task' => true,
                    'task_source' => 'transferred',

                    // أوقات جديدة للموظف الجديد (من الصفر)
                    'actual_minutes' => 0,
                    'started_at' => null,
                    'completed_at' => null,
                ]);

                // نسخ البنود من TemplateTaskUser الأصلي إلى الجديد
                if ($templateTaskUser->items) {
                    $taskItemService = app(\App\Services\Tasks\TaskItemService::class);
                    // نسخ البنود من الأصلي مع إعادة حالتها إلى pending
                    $itemsCopy = array_map(function($item) {
                        $item['status'] = 'pending';
                        $item['note'] = null;
                        $item['completed_at'] = null;
                        $item['completed_by'] = null;
                        return $item;
                    }, $templateTaskUser->items);
                    $newTemplateTaskUser->items = $itemsCopy;
                    $newTemplateTaskUser->save();
                }

                // ✅ تحديث حالة المهمة الأصلية إلى paused عند النقل
                // ✅ حساب الوقت المستخدم حتى لحظة النقل (إذا كانت المهمة قيد التنفيذ)
                $previousStatus = $templateTaskUser->status;
                $currentTime = $this->getCurrentCairoTime();

                // إذا كانت المهمة قيد التنفيذ، نحسب الوقت المستخدم ونضيفه للـ actual_minutes
                if ($previousStatus === 'in_progress' && $templateTaskUser->started_at) {
                    $taskTimeSplitService = app(\App\Services\Tasks\TaskTimeSplitService::class);
                    $startTime = \Carbon\Carbon::parse($templateTaskUser->started_at);

                    $minutesSpent = $taskTimeSplitService->calculateAndUpdateCheckpoint(
                        $templateTaskUser->id,
                        true, // isTemplate = true
                        $startTime,
                        $currentTime,
                        $templateTaskUser->user_id
                    );

                    $totalMinutes = ($templateTaskUser->actual_minutes ?? 0) + $minutesSpent;

                    // ✅ تحديث حالة المهمة الأصلية إلى paused مع حفظ الوقت المستخدم
                    $templateTaskUser->update([
                        'is_transferred' => true,
                        'transferred_to_user_id' => $toUser->id,
                        'transferred_record_id' => $newTemplateTaskUser->id,
                        'transferred_at' => $currentTime,
                        'transfer_type' => $transferType,
                        'transfer_reason' => $reason,
                        'transfer_points' => $transferPoints,
                        'status' => 'paused', // ✅ تغيير الحالة إلى paused عند النقل
                        'actual_minutes' => $totalMinutes, // ✅ حفظ الوقت المستخدم
                        'started_at' => null, // ✅ إيقاف التايمر
                    ]);
                } else {
                    // إذا لم تكن قيد التنفيذ، فقط نحدث الحالة
                    $templateTaskUser->update([
                        'is_transferred' => true,
                        'transferred_to_user_id' => $toUser->id,
                        'transferred_record_id' => $newTemplateTaskUser->id,
                        'transferred_at' => $currentTime,
                        'transfer_type' => $transferType,
                        'transfer_reason' => $reason,
                        'transfer_points' => $transferPoints,
                        'status' => 'paused', // ✅ تغيير الحالة إلى paused عند النقل
                    ]);
                }

                // 3️⃣ إدارة النقاط
                if ($transferType === 'negative' && $transferPoints > 0) {
                    $this->deductPoints($fromUser, $transferPoints, $season, [
                        'reason' => 'نقل مهمة قالب سلبي',
                        'template_task_id' => $templateTaskUser->template_task_id,
                        'transferred_to' => $toUser->id,
                        'transfer_reason' => $reason
                    ]);
                }

                if ($transferType === 'positive') {
                    $this->addPoints($toUser, $transferPoints, $season, [
                        'reason' => 'استلام مهمة قالب منقولة إيجابياً',
                        'template_task_id' => $templateTaskUser->template_task_id,
                        'transferred_from' => $fromUser->id,
                        'transfer_reason' => $reason
                    ]);
                }

                // ✅ معلومات النقل محفوظة في السجلات نفسها - لا حاجة لجدول منفصل

                $transferTypeText = $transferType === 'positive' ? 'إيجابي' : 'سلبي';
                $pointsText = $transferType === 'positive' ? 'بدون خصم نقاط' : "مع خصم {$transferPoints} نقطة";

                Log::info('Template task transferred successfully', [
                    'original_template_task_user_id' => $templateTaskUser->id,
                    'new_template_task_user_id' => $newTemplateTaskUser->id,
                    'from_user' => $fromUser->name,
                    'to_user' => $toUser->name
                ]);

                $result = [
                    'success' => true,
                    'message' => "تم النقل {$transferTypeText} لمهمة القالب بنجاح من {$fromUser->name} إلى {$toUser->name} - {$pointsText}",
                    'original_template_task_user' => $templateTaskUser->fresh(),
                    'new_template_task_user' => $newTemplateTaskUser,
                    'transfer_info' => [
                        'method' => 'separate_records',
                        'original_record_id' => $templateTaskUser->id,
                        'new_record_id' => $newTemplateTaskUser->id,
                        'transfer_type' => $transferType
                    ]
                ];

                // إرسال إشعارات Slack
                try {
                    $this->slackService->sendTemplateTaskTransferNotifications(
                        $templateTaskUser,
                        $newTemplateTaskUser,
                        $fromUser,
                        $toUser,
                        $transferType,
                        $transferPoints,
                        $reason
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send template task transfer Slack notifications', [
                        'original_template_task_user_id' => $templateTaskUser->id,
                        'new_template_task_user_id' => $newTemplateTaskUser->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return $result;
            });

        } catch (Exception $e) {
            Log::error('Error in template task transfer', [
                'template_task_user_id' => $templateTaskUser->id,
                'to_user_id' => $toUser->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء نقل مهمة القالب: ' . $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على تاريخ النقل للمهمة
     */
    public function getTaskTransferHistory(TaskUser $taskUser): array
    {
        $history = [];

        // إذا كان السجل الحالي منقول من سجل أصلي
        if ($taskUser->original_task_user_id) {
            $originalRecord = TaskUser::find($taskUser->original_task_user_id);
            if ($originalRecord) {
                $history[] = [
                    'type' => 'received_transfer',
                    'from_user' => $originalRecord->user->name,
                    'transferred_at' => $originalRecord->transferred_at,
                    'transfer_type' => $originalRecord->transfer_type,
                    'reason' => $originalRecord->transfer_reason,
                    'original_record_id' => $originalRecord->id
                ];
            }
        }

        // إذا كان السجل الحالي نُقل لشخص آخر
        if ($taskUser->is_transferred) {
            $newRecord = TaskUser::find($taskUser->transferred_record_id);
            if ($newRecord) {
                $history[] = [
                    'type' => 'transferred_to',
                    'to_user' => $newRecord->user->name,
                    'transferred_at' => $taskUser->transferred_at,
                    'transfer_type' => $taskUser->transfer_type,
                    'reason' => $taskUser->transfer_reason,
                    'new_record_id' => $newRecord->id
                ];
            }
        }

        return $history;
    }

    /**
     * الحصول على معلومات المهمة مع تفاصيل النقل
     */
    public function getTaskWithTransferInfo(TaskUser $taskUser): array
    {
        $info = [
            'task_user' => $taskUser,
            'is_original' => is_null($taskUser->original_task_user_id),
            'is_transferred' => $taskUser->is_transferred,
            'transfer_history' => $this->getTaskTransferHistory($taskUser)
        ];

        // إضافة معلومات النقل إذا كان السجل منقول
        if ($taskUser->is_transferred) {
            $info['transfer_details'] = [
                'transferred_to_user' => User::find($taskUser->transferred_to_user_id)?->name,
                'transferred_at' => $taskUser->transferred_at,
                'transfer_type' => $taskUser->transfer_type,
                'transfer_reason' => $taskUser->transfer_reason,
                'transfer_points' => $taskUser->transfer_points,
                'new_record_id' => $taskUser->transferred_record_id
            ];
        }

        // إضافة معلومات الأصل إذا كان السجل منقول من مكان آخر
        if ($taskUser->original_task_user_id) {
            $originalRecord = TaskUser::find($taskUser->original_task_user_id);
            $info['original_details'] = [
                'original_user' => $originalRecord?->user->name,
                'original_record_id' => $taskUser->original_task_user_id,
                'received_transfer_at' => $originalRecord?->transferred_at
            ];
        }

        return $info;
    }

    // باقي الدوال المساعدة نفس الأصلي
    private function deductPoints(User $user, int $points, Season $season, array $details = []): void
    {
        $userSeasonPoint = UserSeasonPoint::where('user_id', $user->id)
            ->where('season_id', $season->id)
            ->first();

        if ($userSeasonPoint) {
            $userSeasonPoint->update([
                'total_points' => DB::raw("total_points - {$points}")
            ]);
        } else {
            UserSeasonPoint::create([
                'user_id' => $user->id,
                'season_id' => $season->id,
                'total_points' => -$points,
                'tasks_completed' => 0,
                'projects_completed' => 0,
                'total_minutes_worked' => 0,
            ]);
        }

        $updatedPoints = UserSeasonPoint::where('user_id', $user->id)
            ->where('season_id', $season->id)
            ->first();

        if ($updatedPoints && $updatedPoints->total_points >= 0) {
            $this->badgeService->updateUserBadge($user, $season);
        }
    }

    private function addPoints(User $user, int $points, Season $season, array $details = []): void
    {
        $this->badgeService->addPointsAndUpdateBadge($user, $points, $season, $details);
    }

    // ✅ لا حاجة لـ logTransfer - معلومات النقل محفوظة في السجلات نفسها

    public function canTransferTask($taskUser, User $toUser, int $transferPoints): array
    {
        $fromUser = $taskUser->user;
        $season = $taskUser->season ?: Season::where('is_active', true)->first();

        if (!$season) {
            return ['can_transfer' => false, 'reason' => 'لا يوجد موسم نشط'];
        }

        // منع نقل المهام المكتملة أو الملغاة
        if (in_array($taskUser->status, ['completed', 'cancelled'])) {
            return ['can_transfer' => false, 'reason' => 'لا يمكن نقل مهمة مكتملة أو ملغاة'];
        }

        // ✅ فقط منع النقل للمهام الأصلية التي تم نقلها (is_transferred = true)
        // أما المهام المنقولة (is_additional_task = true) فيمكن تعديل المستلم فيها
        if ($taskUser->is_transferred) {
            return ['can_transfer' => false, 'reason' => 'هذه المهمة تم نقلها بالفعل من مالكها الأصلي، لا يمكن نقلها مرة أخرى'];
        }

        // ✅ منع إرجاع المهمة المنقولة للموظف الأصلي
        if ($taskUser->is_additional_task && $taskUser->original_task_user_id) {
            $originalTaskUser = TaskUser::find($taskUser->original_task_user_id);
            if ($originalTaskUser && $originalTaskUser->user_id == $toUser->id) {
                return [
                    'can_transfer' => false,
                    'reason' => 'لا يمكن إرجاع المهمة للموظف الذي تم نقلها منه أصلاً'
                ];
            }
        }

        $fromUserPoints = UserSeasonPoint::where('user_id', $fromUser->id)
            ->where('season_id', $season->id)
            ->first();

        $currentPoints = $fromUserPoints ? $fromUserPoints->total_points : 0;
        $pointsAfterTransfer = $currentPoints - $transferPoints;

        // ✅ للمهام المنقولة، نسمح بتعديل المستلم
        $actionText = ($taskUser->is_additional_task && $taskUser->task_source === 'transferred')
            ? 'يمكن تعديل المستلم للمهمة'
            : 'يمكن نقل المهمة';

        return [
            'can_transfer' => true,
            'reason' => $actionText,
            'current_points' => $currentPoints,
            'points_after_transfer' => $pointsAfterTransfer,
            'will_be_negative' => $pointsAfterTransfer < 0,
            'is_update_recipient' => ($taskUser->is_additional_task && $taskUser->task_source === 'transferred')
        ];
    }

    /**
     * الحصول على المهام الإضافية (المنقولة) للموظف
     */
    public function getUserAdditionalTasks(User $user, Season $season = null): array
    {
        $season = $season ?: Season::where('is_active', true)->first();
        if (!$season) {
            return [];
        }

        $additionalTasks = TaskUser::with(['task.project', 'originalTaskUser.user'])
            ->where('user_id', $user->id)
            ->where('season_id', $season->id)
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred')
            ->get()
            ->map(function ($taskUser) {
                return [
                    'id' => $taskUser->id,
                    'task_name' => $taskUser->task->name,
                    'project_name' => $taskUser->task->project?->name,
                    'status' => $taskUser->status,
                    'estimated_time' => $taskUser->estimated_hours . 'س ' . $taskUser->estimated_minutes . 'د',
                    'actual_time' => $taskUser->actual_hours . 'س ' . $taskUser->actual_minutes . 'د',
                    'due_date' => $taskUser->due_date,
                    'received_from' => $taskUser->originalTaskUser?->user?->name,
                    'transferred_at' => $taskUser->originalTaskUser?->transferred_at,
                    'task_info' => $taskUser->task_info
                ];
            });

        return $additionalTasks->toArray();
    }

    /**
     * الحصول على إحصائيات المهام الإضافية للموظف
     */
    public function getUserAdditionalTasksStats(User $user, Season $season = null): array
    {
        $season = $season ?: Season::where('is_active', true)->first();
        if (!$season) {
            return [
                'total_additional_tasks' => 0,
                'completed_additional_tasks' => 0,
                'pending_additional_tasks' => 0,
                'completion_percentage' => 0
            ];
        }

        $additionalTasks = TaskUser::where('user_id', $user->id)
            ->where('season_id', $season->id)
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred');

        $total = $additionalTasks->count();
        $completed = $additionalTasks->where('status', 'completed')->count();
        $pending = $additionalTasks->whereNotIn('status', ['completed', 'cancelled'])->count();

        return [
            'total_additional_tasks' => $total,
            'completed_additional_tasks' => $completed,
            'pending_additional_tasks' => $pending,
            'completion_percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ];
    }

    /**
     * تمييز المهام في واجهة المستخدم
     */
    public function getTaskDisplayBadge(TaskUser $taskUser): array
    {
        if ($taskUser->isAdditionalTask()) {
            return [
                'badge_text' => 'مهمة إضافية',
                'badge_class' => 'bg-info text-white',
                'icon' => 'fas fa-plus-circle',
                'tooltip' => 'هذه مهمة منقولة إليك من: ' . ($taskUser->originalTaskUser?->user?->name ?? 'غير معروف')
            ];
        }

        if ($taskUser->isTransferred()) {
            return [
                'badge_text' => 'تم نقلها',
                'badge_class' => 'bg-warning text-dark',
                'icon' => 'fas fa-exchange-alt',
                'tooltip' => 'تم نقل هذه المهمة إلى: ' . ($taskUser->transferredToUser?->name ?? 'غير معروف')
            ];
        }

        return [
            'badge_text' => 'مهمة أصلية',
            'badge_class' => 'bg-primary text-white',
            'icon' => 'fas fa-tasks',
            'tooltip' => 'مهمة مخصصة لك أصلاً'
        ];
    }

    /**
     * الحصول على تاريخ النقل للمستخدم من السجلات المباشرة
     */
    public function getUserTransferHistory(User $user, $season = null): array
    {
        $season = $season ?: Season::where('is_active', true)->first();

        if (!$season) {
            return [];
        }

        $history = [];

        // المهام العادية المنقولة منه
        $transferredFrom = TaskUser::with(['task', 'transferredToUser'])
            ->where('user_id', $user->id)
            ->where('season_id', $season->id)
            ->where('is_transferred', true)
            ->get();

        foreach ($transferredFrom as $task) {
            $history[] = [
                'type' => 'task_transferred_from',
                'task_name' => $task->task?->name,
                'transferred_to' => $task->transferredToUser?->name,
                'transferred_at' => $task->transferred_at,
                'transfer_type' => $task->transfer_type,
                'reason' => $task->transfer_reason,
                'points' => $task->transfer_points,
            ];
        }

        // المهام العادية المنقولة إليه
        $transferredTo = TaskUser::with(['task', 'originalTaskUser.user'])
            ->where('user_id', $user->id)
            ->where('season_id', $season->id)
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred')
            ->get();

        foreach ($transferredTo as $task) {
            $history[] = [
                'type' => 'task_transferred_to',
                'task_name' => $task->task?->name,
                'transferred_from' => $task->originalTaskUser?->user?->name,
                'transferred_at' => $task->originalTaskUser?->transferred_at,
                'transfer_type' => $task->originalTaskUser?->transfer_type,
                'reason' => $task->originalTaskUser?->transfer_reason,
                'points' => $task->originalTaskUser?->transfer_points,
            ];
        }

        // TODO: إضافة مهام القوالب بنفس الطريقة إذا لزم الأمر

        // ترتيب حسب التاريخ
        usort($history, function($a, $b) {
            return ($b['transferred_at'] ?? '') <=> ($a['transferred_at'] ?? '');
        });

        return $history;
    }

    /**
     * إلغاء نقل مهمة - إرجاعها للموظف الأصلي
     *
     * @param TaskUser $transferredTaskUser المهمة المنقولة (is_additional_task = true)
     * @param string|null $cancelReason سبب الإلغاء
     * @return array
     */
    public function cancelTaskTransfer(TaskUser $transferredTaskUser, string $cancelReason = null): array
    {
        // ✅ التحقق: هل هذه مهمة منقولة فعلاً؟
        if (!$transferredTaskUser->is_additional_task || $transferredTaskUser->task_source !== 'transferred') {
            return [
                'success' => false,
                'message' => 'هذه المهمة ليست مهمة منقولة'
            ];
        }

        // ✅ التحقق: هل يوجد سجل أصلي؟
        if (!$transferredTaskUser->original_task_user_id) {
            return [
                'success' => false,
                'message' => 'لا يمكن العثور على السجل الأصلي للمهمة'
            ];
        }

        $originalTaskUser = TaskUser::find($transferredTaskUser->original_task_user_id);
        if (!$originalTaskUser) {
            return [
                'success' => false,
                'message' => 'السجل الأصلي للمهمة غير موجود'
            ];
        }

        // ✅ التحقق: هل المهمة مكتملة؟
        if (in_array($transferredTaskUser->status, ['completed', 'cancelled'])) {
            return [
                'success' => false,
                'message' => 'لا يمكن إلغاء نقل مهمة مكتملة أو ملغاة'
            ];
        }

        try {
            return DB::transaction(function () use ($transferredTaskUser, $originalTaskUser, $cancelReason) {
                $currentUser = $transferredTaskUser->user; // الموظف الحالي (اللي اتنقلت له)
                $originalUser = $originalTaskUser->user; // الموظف الأصلي
                $season = $transferredTaskUser->season ?: Season::where('is_active', true)->first();

                if (!$season) {
                    throw new Exception('لا يوجد موسم نشط');
                }

                // حفظ معلومات النقل قبل تحديث السجل
                $transferType = $originalTaskUser->transfer_type;
                $transferPoints = $originalTaskUser->transfer_points;

                Log::info('🔙 إلغاء نقل مهمة', [
                    'transferred_task_user_id' => $transferredTaskUser->id,
                    'original_task_user_id' => $originalTaskUser->id,
                    'current_user' => $currentUser->name,
                    'original_user' => $originalUser->name,
                    'transfer_type' => $transferType,
                    'transfer_points' => $transferPoints,
                    'cancel_reason' => $cancelReason
                ]);

                // 1️⃣ إدارة النقاط - إرجاع النقاط حسب نوع النقل الأصلي (قبل حذف البيانات!)
                if ($transferType === 'negative' && $transferPoints > 0) {
                    // كان نقل سلبي -> نرجع النقاط للموظف الأصلي
                    $this->addPoints($originalUser, $transferPoints, $season, [
                        'reason' => 'إلغاء نقل سلبي - إرجاع النقاط المخصومة',
                        'task_id' => $originalTaskUser->task_id,
                        'cancel_reason' => $cancelReason
                    ]);
                } elseif ($transferType === 'positive' && $transferPoints > 0) {
                    // كان نقل إيجابي -> نخصم النقاط من الموظف الحالي
                    $this->deductPoints($currentUser, $transferPoints, $season, [
                        'reason' => 'إلغاء نقل إيجابي - خصم النقاط الممنوحة',
                        'task_id' => $originalTaskUser->task_id,
                        'cancel_reason' => $cancelReason
                    ]);
                }

                // 2️⃣ حذف السجل المنقول
                $transferredTaskUser->delete();

                // 3️⃣ تحديث السجل الأصلي (إزالة علامات النقل)
                $originalTaskUser->update([
                    'is_transferred' => false,
                    'transferred_to_user_id' => null,
                    'transferred_record_id' => null,
                    'transferred_at' => null,
                    'transfer_type' => null,
                    'transfer_reason' => null,
                    'transfer_points' => 0, // استخدام 0 بدلاً من null
                ]);

                return [
                    'success' => true,
                    'message' => "تم إلغاء نقل المهمة بنجاح وإرجاعها إلى {$originalUser->name}",
                    'original_user' => $originalUser->name
                ];
            });
        } catch (Exception $e) {
            Log::error('❌ فشل إلغاء نقل المهمة', [
                'transferred_task_user_id' => $transferredTaskUser->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء النقل: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إلغاء نقل مهمة قالب - إرجاعها للموظف الأصلي
     *
     * @param TemplateTaskUser $transferredTemplateTaskUser المهمة المنقولة (is_additional_task = true)
     * @param string|null $cancelReason سبب الإلغاء
     * @return array
     */
    public function cancelTemplateTaskTransfer(TemplateTaskUser $transferredTemplateTaskUser, string $cancelReason = null): array
    {
        // ✅ التحقق: هل هذه مهمة منقولة فعلاً؟
        if (!$transferredTemplateTaskUser->is_additional_task || $transferredTemplateTaskUser->task_source !== 'transferred') {
            return [
                'success' => false,
                'message' => 'هذه المهمة ليست مهمة منقولة'
            ];
        }

        // ✅ التحقق: هل يوجد سجل أصلي؟
        if (!$transferredTemplateTaskUser->original_template_task_user_id) {
            return [
                'success' => false,
                'message' => 'لا يمكن العثور على السجل الأصلي للمهمة'
            ];
        }

        $originalTemplateTaskUser = TemplateTaskUser::find($transferredTemplateTaskUser->original_template_task_user_id);
        if (!$originalTemplateTaskUser) {
            return [
                'success' => false,
                'message' => 'السجل الأصلي للمهمة غير موجود'
            ];
        }

        // ✅ التحقق: هل المهمة مكتملة؟
        if (in_array($transferredTemplateTaskUser->status, ['completed', 'cancelled'])) {
            return [
                'success' => false,
                'message' => 'لا يمكن إلغاء نقل مهمة مكتملة أو ملغاة'
            ];
        }

        try {
            return DB::transaction(function () use ($transferredTemplateTaskUser, $originalTemplateTaskUser, $cancelReason) {
                $currentUser = $transferredTemplateTaskUser->user; // الموظف الحالي (اللي اتنقلت له)
                $originalUser = $originalTemplateTaskUser->user; // الموظف الأصلي
                $season = $transferredTemplateTaskUser->season ?: Season::where('is_active', true)->first();

                if (!$season) {
                    throw new Exception('لا يوجد موسم نشط');
                }

                // حفظ معلومات النقل قبل تحديث السجل
                $transferType = $originalTemplateTaskUser->transfer_type;
                $transferPoints = $originalTemplateTaskUser->transfer_points;

                Log::info('🔙 إلغاء نقل مهمة قالب', [
                    'transferred_template_task_user_id' => $transferredTemplateTaskUser->id,
                    'original_template_task_user_id' => $originalTemplateTaskUser->id,
                    'current_user' => $currentUser->name,
                    'original_user' => $originalUser->name,
                    'transfer_type' => $transferType,
                    'transfer_points' => $transferPoints,
                    'cancel_reason' => $cancelReason
                ]);

                // 1️⃣ إدارة النقاط - إرجاع النقاط حسب نوع النقل الأصلي (قبل حذف البيانات!)
                if ($transferType === 'negative' && $transferPoints > 0) {
                    // كان نقل سلبي -> نرجع النقاط للموظف الأصلي
                    $this->addPoints($originalUser, $transferPoints, $season, [
                        'reason' => 'إلغاء نقل سلبي - إرجاع النقاط المخصومة',
                        'template_task_id' => $originalTemplateTaskUser->template_task_id,
                        'cancel_reason' => $cancelReason
                    ]);
                } elseif ($transferType === 'positive' && $transferPoints > 0) {
                    // كان نقل إيجابي -> نخصم النقاط من الموظف الحالي
                    $this->deductPoints($currentUser, $transferPoints, $season, [
                        'reason' => 'إلغاء نقل إيجابي - خصم النقاط الممنوحة',
                        'template_task_id' => $originalTemplateTaskUser->template_task_id,
                        'cancel_reason' => $cancelReason
                    ]);
                }

                // 2️⃣ حذف السجل المنقول
                $transferredTemplateTaskUser->delete();

                // 3️⃣ تحديث السجل الأصلي (إزالة علامات النقل)
                $originalTemplateTaskUser->update([
                    'is_transferred' => false,
                    'transferred_to_user_id' => null,
                    'transferred_record_id' => null,
                    'transferred_at' => null,
                    'transfer_type' => null,
                    'transfer_reason' => null,
                    'transfer_points' => 0, // استخدام 0 بدلاً من null
                ]);

                return [
                    'success' => true,
                    'message' => "تم إلغاء نقل المهمة بنجاح وإرجاعها إلى {$originalUser->name}",
                    'original_user' => $originalUser->name
                ];
            });
        } catch (Exception $e) {
            Log::error('❌ فشل إلغاء نقل مهمة القالب', [
                'transferred_template_task_user_id' => $transferredTemplateTaskUser->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء النقل: ' . $e->getMessage()
            ];
        }
    }
}
