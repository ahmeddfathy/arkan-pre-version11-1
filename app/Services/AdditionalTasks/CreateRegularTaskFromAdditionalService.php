<?php

namespace App\Services\AdditionalTasks;

use App\Models\Task;
use App\Models\TaskUser;
use App\Models\AdditionalTask;
use App\Models\AdditionalTaskUser;
use App\Services\TaskController\TaskManagementService;
use App\Services\TaskController\TaskUserAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateRegularTaskFromAdditionalService
{

    protected $taskManagementService;
    protected $taskUserAssignmentService;

    public function __construct(
        TaskManagementService $taskManagementService,
        TaskUserAssignmentService $taskUserAssignmentService
    ) {
        $this->taskManagementService = $taskManagementService;
        $this->taskUserAssignmentService = $taskUserAssignmentService;
    }

    /**
     * إنشاء مهمة عادية من مهمة إضافية عند الموافقة
     */
    public function createTaskFromAdditional(AdditionalTaskUser $additionalTaskUser): ?Task
    {
        try {
            DB::beginTransaction();

            $additionalTask = $additionalTaskUser->additionalTask;
            $user = $additionalTaskUser->user;

            if (!$additionalTask || !$user) {
                Log::error('Missing additional task or user', [
                    'additional_task_user_id' => $additionalTaskUser->id
                ]);
                DB::rollBack();
                return null;
            }

            // حساب deadline: إذا كانت المهمة الإضافية مرنة (duration_hours = null)، لا نضع deadline
            // وإذا كانت لها مدة، نضع deadline = وقت الموافقة + المدة
            $dueDate = null;
            if ($additionalTask->duration_hours && $additionalTask->current_end_time) {
                // استخدام current_end_time كمدة نهائية (بعد أي تمديدات)
                $dueDate = $additionalTask->current_end_time;
            } elseif ($additionalTask->duration_hours) {
                // إذا كان هناك duration_hours ولكن لا يوجد current_end_time، نستخدم duration_hours
                $dueDate = Carbon::now()->addHours($additionalTask->duration_hours);
            }

            // إعداد بيانات المهمة العادية
            $taskData = [
                'name' => $additionalTask->title,
                'description' => $additionalTask->description,
                'project_id' => null, // مهمة عادية غير مرتبطة بمشروع
                'service_id' => null,
                'points' => $additionalTask->points,
                'due_date' => $dueDate,
                'status' => 'new',
                'created_by' => $additionalTask->created_by, // منشئ المهمة الإضافية
                'is_flexible_time' => true, // الوقت مرن دائماً
            ];

            // إنشاء المهمة العادية
            $task = $this->taskManagementService->createTask($taskData);

            Log::info('Regular task created from additional task', [
                'task_id' => $task->id,
                'additional_task_id' => $additionalTask->id,
                'additional_task_user_id' => $additionalTaskUser->id,
                'user_id' => $user->id,
            ]);

            // تعيين المستخدم للمهمة مع تحديد أنها مهمة إضافية
            $assignedUsers = [
                [
                    'user_id' => $user->id,
                    'role' => 'منفذ',
                ]
            ];

            // استخدام TaskUserAssignmentService لتعيين المستخدم مع ربط مباشر بالمهمة الإضافية
            $this->taskUserAssignmentService->assignUsersToTask(
                $task,
                $assignedUsers,
                true, // isFlexible = true
                true, // isAdditionalTask = true
                $additionalTaskUser->id // ربط مباشر بالمهمة الإضافية
            );

            // ربط المهمة الإضافية بالمهمة العادية (يمكن إضافة حقل additional_task_id في Task)
            // يمكن حفظ الـ reference في description أو في حقل خاص
            if (!$task->description) {
                $task->description = '';
            }
            $task->description .= "\n\n---\nمهمة إضافية - معرف المهمة الإضافية: #{$additionalTask->id}";
            $task->save();

            DB::commit();

            Log::info('Successfully created regular task from additional task', [
                'task_id' => $task->id,
                'additional_task_user_id' => $additionalTaskUser->id,
            ]);

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create regular task from additional task', [
                'additional_task_user_id' => $additionalTaskUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * البحث عن المهمة العادية المرتبطة بمهمة إضافية
     * طريقة دقيقة: استخدام حقل additional_task_user_id المباشر
     */
    public function findRelatedRegularTask(AdditionalTaskUser $additionalTaskUser): ?Task
    {
        // البحث المباشر والدقيق من خلال additional_task_user_id
        $taskUser = TaskUser::where('additional_task_user_id', $additionalTaskUser->id)
            ->with('task')
            ->first();

        if ($taskUser && $taskUser->task) {
            return $taskUser->task;
        }

        // Fallback: في حالة عدم وجود الحقل (للتوافق مع البيانات القديمة)
        // البحث من خلال is_additional_task و task_source
        $additionalTask = $additionalTaskUser->additionalTask;
        $user = $additionalTaskUser->user;

        if (!$additionalTask || !$user) {
            return null;
        }

        $taskUser = TaskUser::where('user_id', $user->id)
            ->where('is_additional_task', true)
            ->where('task_source', 'additional')
            ->whereHas('task', function ($query) use ($additionalTask) {
                // البحث في description عن معرف المهمة الإضافية
                $query->where('description', 'like', '%مهمة إضافية - معرف المهمة الإضافية: #' . $additionalTask->id . '%');
            })
            ->first();

        if ($taskUser && $taskUser->task) {
            return $taskUser->task;
        }

        return null;
    }

    /**
     * إلغاء الموافقة وحذف المهمة العادية المرتبطة
     */
    public function revokeApproval(AdditionalTaskUser $additionalTaskUser, string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            // التحقق من أن الحالة الحالية هي 'assigned'
            if ($additionalTaskUser->status !== 'assigned') {
                Log::warning('Cannot revoke approval - status is not assigned', [
                    'additional_task_user_id' => $additionalTaskUser->id,
                    'current_status' => $additionalTaskUser->status
                ]);
                DB::rollBack();
                return false;
            }

            // البحث عن المهمة العادية المرتبطة وحذفها
            $regularTask = $this->findRelatedRegularTask($additionalTaskUser);

            if ($regularTask) {
                // حذف جميع TaskUsers المرتبطة
                TaskUser::where('task_id', $regularTask->id)->delete();

                // حذف المهمة العادية
                $regularTask->delete();

                Log::info('Regular task deleted after revoking approval', [
                    'task_id' => $regularTask->id,
                    'additional_task_user_id' => $additionalTaskUser->id
                ]);
            } else {
                Log::warning('Could not find related regular task to delete', [
                    'additional_task_user_id' => $additionalTaskUser->id
                ]);
            }

            // إرجاع status إلى 'applied' مع إضافة ملاحظة
            $adminNotes = $additionalTaskUser->admin_notes ?? '';
            if ($reason) {
                $adminNotes = ($adminNotes ? $adminNotes . "\n\n" : '') . "تم إلغاء الموافقة: " . $reason;
            } else {
                $adminNotes = ($adminNotes ? $adminNotes . "\n\n" : '') . "تم إلغاء الموافقة من قبل الإدارة";
            }

            $additionalTaskUser->update([
                'status' => 'applied',
                'approved_at' => null,
                'admin_notes' => $adminNotes,
            ]);

            DB::commit();

            Log::info('Approval revoked successfully', [
                'additional_task_user_id' => $additionalTaskUser->id,
                'regular_task_deleted' => $regularTask !== null
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to revoke approval', [
                'additional_task_user_id' => $additionalTaskUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
