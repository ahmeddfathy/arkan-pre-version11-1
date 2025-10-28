<?php

namespace App\Services\TaskController;

use App\Models\Task;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use Carbon\Carbon;

class TaskDataTransformationService
{
    /**
     * تحويل المهمة العادية إلى مصفوفة للـ JSON
     */
    public function transformTaskToArray(Task $task): array
    {
        $taskData = $task->toArray();

        // إصلاح بيانات الموظفين مع التواريخ الصحيحة
        if (isset($taskData['users']) && is_array($taskData['users'])) {
            foreach ($taskData['users'] as $key => $user) {
                $taskData['users'][$key] = $this->transformUserPivotData($user, $task);
            }
        }

        // تنسيق التواريخ الرئيسية للمهمة
        if (isset($taskData['due_date']) && $taskData['due_date']) {
            $taskData['due_date'] = Carbon::parse($taskData['due_date'])->format('Y-m-d');
        }

        return $taskData;
    }

    /**
     * تحويل مهمة القالب إلى شكل يشبه المهمة العادية
     */
    public function transformTemplateTaskToArray(TemplateTaskUser $templateTaskUser): array
    {
        $templateTask = $templateTaskUser->templateTask;
        $template = $templateTask->template ?? null;

        return [
            'id' => $templateTaskUser->id,
            'name' => ($templateTask->name ?? 'مهمة قالب') . ' (قالب)',
            'description' => $templateTask->description ?? 'مهمة من قالب',
            'is_template' => true,
            'template_name' => $template->name ?? 'قالب غير محدد',

            // المشروع
            'project' => $templateTaskUser->project ? [
                'id' => $templateTaskUser->project->id,
                'name' => $templateTaskUser->project->name
            ] : null,
            'project_id' => $templateTaskUser->project_id,

            // الخدمة
            'service' => $template && $template->service ? [
                'id' => $template->service->id,
                'name' => $template->service->name
            ] : null,
            'service_id' => $template->service_id ?? null,

            // الأوقات
            'estimated_hours' => $templateTask->estimated_hours ?? 0,
            'estimated_minutes' => $templateTask->estimated_minutes ?? 0,
            'actual_hours' => $templateTaskUser->actual_minutes ? intval($templateTaskUser->actual_minutes / 60) : 0,
            'actual_minutes' => $templateTaskUser->actual_minutes ? ($templateTaskUser->actual_minutes % 60) : 0,

            // الحالة والتواريخ
            'status' => $templateTaskUser->status ?? 'new',
            'due_date' => null,
            'created_at' => $templateTaskUser->created_at,
            'updated_at' => $templateTaskUser->updated_at,

            // المستخدم المعين
            'users' => $templateTaskUser->user ? [$this->transformTemplateTaskUser($templateTaskUser, $templateTask)] : [],

            // منشئ المهمة (لا يوجد للقوالب)
            'createdBy' => null,
            'created_by' => null,
        ];
    }

    /**
     * تحويل مهمة القالب للتعديل
     */
    public function transformTemplateTaskForEdit(TemplateTaskUser $templateTaskUser): array
    {
        $templateTask = $templateTaskUser->templateTask;
        $template = $templateTask->template ?? null;

        $baseData = $this->transformTemplateTaskToArray($templateTaskUser);

        // إضافة بيانات خاصة بالتعديل
        $baseData['due_date'] = $templateTaskUser->due_date;
        $baseData['points'] = $templateTask->points ?? 10;
        $baseData['template_task_id'] = $templateTask->id;
        $baseData['template_id'] = $template->id ?? null;
        $baseData['season_id'] = $templateTaskUser->season_id;

        // تنسيق التاريخ للتعديل
        if ($templateTaskUser->due_date) {
            $baseData['users'][0]['pivot']['due_date'] = Carbon::parse($templateTaskUser->due_date)->format('Y-m-d');
        }

        return $baseData;
    }

    /**
     * تحويل بيانات pivot للمستخدم
     */
    private function transformUserPivotData(array $user, Task $task): array
    {
        if (!isset($user['pivot'])) {
            // إذا لم تكن بيانات pivot موجودة، أحضرها يدوياً
            $taskUser = TaskUser::where('task_id', $task->id)
                              ->where('user_id', $user['id'])
                              ->first();

            if ($taskUser) {
                $user['pivot'] = [
                    'id' => $taskUser->id,
                    'task_id' => $task->id,
                    'user_id' => $user['id'],
                    'role' => $taskUser->role ?? 'غير محدد',
                    'status' => $taskUser->status ?? 'new',
                    'estimated_hours' => $taskUser->estimated_hours ?? 0,
                    'estimated_minutes' => $taskUser->estimated_minutes ?? 0,
                    'actual_hours' => $taskUser->actual_hours ?? 0,
                    'actual_minutes' => $taskUser->actual_minutes ?? 0,
                    'due_date' => $taskUser->due_date ? $taskUser->due_date->format('Y-m-d') : null,
                    'completed_date' => $taskUser->completed_date ? $taskUser->completed_date->format('Y-m-d') : null
                ];
            } else {
                $user['pivot'] = $this->getDefaultPivotData($task->id, $user['id']);
            }
        } else {
            // تنسيق التواريخ من pivot بشكل صحيح
            if (isset($user['pivot']['due_date']) && $user['pivot']['due_date']) {
                $dueDate = Carbon::parse($user['pivot']['due_date']);
                $user['pivot']['due_date'] = $dueDate->format('Y-m-d');
            }
        }

        return $user;
    }

    /**
     * تحويل مستخدم مهمة القالب
     */
    private function transformTemplateTaskUser(TemplateTaskUser $templateTaskUser, $templateTask): array
    {
        return [
            'id' => $templateTaskUser->user->id,
            'name' => $templateTaskUser->user->name,
            'email' => $templateTaskUser->user->email,
            'pivot' => [
                'id' => $templateTaskUser->id,
                'task_id' => $templateTaskUser->id,
                'user_id' => $templateTaskUser->user->id,
                'role' => 'منفذ قالب',
                'status' => $templateTaskUser->status ?? 'new',
                'estimated_hours' => $templateTask->estimated_hours ?? 0,
                'estimated_minutes' => $templateTask->estimated_minutes ?? 0,
                'actual_hours' => $templateTaskUser->actual_minutes ? intval($templateTaskUser->actual_minutes / 60) : 0,
                'actual_minutes' => $templateTaskUser->actual_minutes ? ($templateTaskUser->actual_minutes % 60) : 0,
                'due_date' => $templateTaskUser->due_date ? Carbon::parse($templateTaskUser->due_date)->format('Y-m-d') : null,
            ]
        ];
    }

    /**
     * الحصول على بيانات pivot افتراضية
     */
    private function getDefaultPivotData(int $taskId, int $userId): array
    {
        return [
            'task_id' => $taskId,
            'user_id' => $userId,
            'role' => 'غير محدد',
            'status' => 'new',
            'estimated_hours' => 0,
            'estimated_minutes' => 0,
            'actual_hours' => 0,
            'actual_minutes' => 0,
            'due_date' => null,
            'completed_date' => null
        ];
    }
}
