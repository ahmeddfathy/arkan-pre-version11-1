<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class TaskTemplate extends Model
{
    use HasFactory, SoftDeletes, HasSecureId, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'service_id',
        'estimated_hours',
        'estimated_minutes',
        'order',
        'is_active',
        'is_flexible_time',
    ];

    protected $casts = [
        'estimated_hours' => 'integer',
        'estimated_minutes' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
        'is_flexible_time' => 'boolean',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'description', 'service_id', 'estimated_hours',
                'estimated_minutes', 'order', 'is_active', 'is_flexible_time'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء قالب مهمة جديد',
                'updated' => 'تم تحديث قالب المهمة',
                'deleted' => 'تم حذف قالب المهمة',
                default => $eventName
            });
    }

    /**
     * Get the service that owns the task template
     */
    public function service()
    {
        return $this->belongsTo(CompanyService::class);
    }

    /**
     * Get the tasks created from this template
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * الحصول على مهام القالب الفرعية
     */
    public function templateTasks()
    {
        return $this->hasMany(TemplateTask::class)->orderBy('order');
    }

    /**
     * Scope for active templates only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get total estimated time in minutes
     */
    public function getTotalEstimatedMinutesAttribute()
    {
        // إذا كان القالب مرن (بدون وقت محدد)
        if ($this->isFlexibleTime()) {
            return null;
        }

        return ($this->estimated_hours * 60) + $this->estimated_minutes;
    }

    /**
     * التحقق من كون قالب المهمة مرن (بدون وقت محدد)
     */
    public function isFlexibleTime()
    {
        return (bool) $this->is_flexible_time;
    }

    /**
     * التحقق من كون قالب المهمة له وقت محدد
     */
    public function hasEstimatedTime()
    {
        return !$this->isFlexibleTime() && (($this->estimated_hours ?? 0) > 0 || ($this->estimated_minutes ?? 0) > 0);
    }

    /**
     * الحصول على المهام المخصصة لأدوار معينة
     */
    public function roleSpecificTasks()
    {
        return $this->templateTasks()->whereNotNull('role_id');
    }

    /**
     * الحصول على المهام العامة (المتاحة للجميع)
     */
    public function generalTasks()
    {
        return $this->templateTasks()->whereNull('role_id');
    }

    /**
     * الحصول على المهام التي يمكن تخصيصها لمستخدم معين
     */
    public function getAssignableTasksForUser($user)
    {
        return $this->templateTasks()
            ->assignableToUser($user)
            ->where('is_active', true)
            ->get();
    }

    /**
     * الحصول على الأدوار المستخدمة في هذا القالب
     */
    public function getUsedRoles()
    {
        return $this->templateTasks()
            ->whereNotNull('role_id')
            ->with('role')
            ->get()
            ->pluck('role')
            ->unique('id')
            ->filter();
    }

    /**
     * Format estimated time as a string (e.g. "2h 30m")
     */
    public function getEstimatedTimeFormattedAttribute()
    {
        // إذا كان قالب المهمة مرن (بدون وقت محدد)
        if ($this->isFlexibleTime()) {
            return 'Flexible';
        }

        if ($this->estimated_hours == 0 && $this->estimated_minutes == 0) {
            return '0m';
        }

        $parts = [];

        if ($this->estimated_hours > 0) {
            $parts[] = $this->estimated_hours . 'h';
        }

        if ($this->estimated_minutes > 0) {
            $parts[] = $this->estimated_minutes . 'm';
        }

        return implode(' ', $parts);
    }

    /**
     * حساب مجموع الوقت المقدر للمهام الفرعية بالدقائق
     */
    public function getTotalSubTasksEstimatedMinutes()
    {
        $totalMinutes = 0;

        foreach ($this->templateTasks()->where('is_active', true)->get() as $task) {
            // تجاهل المهام المرنة
            if ($task->isFlexibleTime()) {
                continue;
            }

            $taskMinutes = ($task->estimated_hours ?? 0) * 60 + ($task->estimated_minutes ?? 0);
            $totalMinutes += $taskMinutes;
        }

        return $totalMinutes;
    }

    /**
     * التحقق من إمكانية إضافة مهمة جديدة بوقت معين دون تجاوز وقت القالب
     */
    public function canAddTaskWithTime($hoursToAdd, $minutesToAdd, $excludeTaskId = null)
    {
        // إذا كان القالب مرن، فلا توجد قيود
        if ($this->isFlexibleTime()) {
            return true;
        }

        // إذا كان القالب بدون وقت محدد، لا يمكن إضافة مهام بوقت
        if (!$this->hasEstimatedTime()) {
            return false;
        }

        $templateTotalMinutes = $this->getTotalEstimatedMinutesAttribute();
        $currentSubTasksMinutes = $this->getTotalSubTasksEstimatedMinutesExcluding($excludeTaskId);
        $newTaskMinutes = ($hoursToAdd * 60) + $minutesToAdd;

        return ($currentSubTasksMinutes + $newTaskMinutes) <= $templateTotalMinutes;
    }

    /**
     * حساب مجموع الوقت المقدر للمهام الفرعية باستثناء مهمة معينة
     */
    public function getTotalSubTasksEstimatedMinutesExcluding($excludeTaskId = null)
    {
        $query = $this->templateTasks()->where('is_active', true);

        if ($excludeTaskId) {
            $query->where('id', '!=', $excludeTaskId);
        }

        $totalMinutes = 0;

        foreach ($query->get() as $task) {
            // تجاهل المهام المرنة
            if ($task->isFlexibleTime()) {
                continue;
            }

            $taskMinutes = ($task->estimated_hours ?? 0) * 60 + ($task->estimated_minutes ?? 0);
            $totalMinutes += $taskMinutes;
        }

        return $totalMinutes;
    }

    /**
     * الحصول على الوقت المتاح للمهام الجديدة بالدقائق
     */
    public function getAvailableTimeForNewTasks()
    {
        if ($this->isFlexibleTime() || !$this->hasEstimatedTime()) {
            return null; // وقت غير محدود أو لا يوجد وقت
        }

        $templateTotalMinutes = $this->getTotalEstimatedMinutesAttribute();
        $usedMinutes = $this->getTotalSubTasksEstimatedMinutes();

        return max(0, $templateTotalMinutes - $usedMinutes);
    }

    /**
     * التحقق من منطق توزيع الوقت:
     * - المهام العامة (للجميع): كل مهمة عامة لها الحق في الوقت الكامل للقالب
     * - المهام المرتبطة بأدوار: كل رول له الوقت الكامل للقالب، لكن مهام نفس الرول مجتمعة لا تتعدى وقت القالب
     */
    public function validateTimeDistributionForNewTask($hoursToAdd, $minutesToAdd, $roleId = null, $excludeTaskId = null)
    {
        // إذا كان القالب مرن، فلا توجد قيود
        if ($this->isFlexibleTime()) {
            return ['valid' => true];
        }

        // إذا كان القالب بدون وقت محدد
        if (!$this->hasEstimatedTime()) {
            if ($hoursToAdd > 0 || $minutesToAdd > 0) {
                return [
                    'valid' => false,
                    'message' => 'لا يمكن إضافة مهام بوقت محدد للقالب المرن أو بدون وقت'
                ];
            }
            return ['valid' => true];
        }

        $templateTotalMinutes = $this->getTotalEstimatedMinutesAttribute();
        $newTaskMinutes = ($hoursToAdd * 60) + $minutesToAdd;

        // إذا كانت المهمة عامة (للجميع)
        if (!$roleId) {
            // كل مهمة عامة لها الحق في الوقت الكامل للقالب
            if ($newTaskMinutes <= $templateTotalMinutes) {
                return ['valid' => true];
            } else {
                return [
                    'valid' => false,
                    'message' => 'وقت المهمة العامة يتجاوز الوقت الإجمالي للقالب (' .
                                $this->formatMinutesToHours($templateTotalMinutes) . ')'
                ];
            }
        } else {
            // المهام المرتبطة برول محدد: التحقق من مهام نفس الرول فقط
            $sameRoleTasksMinutes = $this->getTotalTasksMinutesForSpecificRole($roleId, $excludeTaskId);

            if (($sameRoleTasksMinutes + $newTaskMinutes) <= $templateTotalMinutes) {
                return ['valid' => true];
            } else {
                $availableMinutes = $templateTotalMinutes - $sameRoleTasksMinutes;
                $roleName = $this->getRoleNameById($roleId);
                return [
                    'valid' => false,
                    'message' => 'مجموع أوقات مهام الدور "' . $roleName . '" يتجاوز وقت القالب. الوقت المتاح للدور: ' .
                                $this->formatMinutesToHours($availableMinutes)
                ];
            }
        }
    }

    /**
     * حساب مجموع الوقت للمهام المرتبطة برول محدد فقط
     */
    private function getTotalTasksMinutesForSpecificRole($roleId, $excludeTaskId = null)
    {
        $query = $this->templateTasks()
                     ->where('role_id', $roleId)
                     ->where('is_active', true);

        if ($excludeTaskId) {
            $query->where('id', '!=', $excludeTaskId);
        }

        $totalMinutes = 0;

        foreach ($query->get() as $task) {
            if (!$task->isFlexibleTime()) {
                $taskMinutes = ($task->estimated_hours ?? 0) * 60 + ($task->estimated_minutes ?? 0);
                $totalMinutes += $taskMinutes;
            }
        }

        return $totalMinutes;
    }

    /**
     * الحصول على اسم الدور بواسطة ID
     */
    private function getRoleNameById($roleId)
    {
        $role = \Spatie\Permission\Models\Role::find($roleId);
        return $role ? $role->name : 'غير معروف';
    }

    /**
     * الحصول على تفصيل الوقت المستخدم لكل رول والمهام العامة
     */
    public function getTimeUsageBreakdown()
    {
        $breakdown = [
            'roles' => [],
            'general_tasks' => [],
            'total_used_minutes' => 0
        ];

        // حساب وقت المهام العامة (للجميع)
        $generalTasks = $this->templateTasks()
                            ->whereNull('role_id')
                            ->where('is_active', true)
                            ->get();

        foreach ($generalTasks as $task) {
            if (!$task->isFlexibleTime()) {
                $taskMinutes = ($task->estimated_hours ?? 0) * 60 + ($task->estimated_minutes ?? 0);
                $breakdown['general_tasks'][] = [
                    'name' => $task->name,
                    'minutes' => $taskMinutes,
                    'formatted' => $this->formatMinutesToHours($taskMinutes)
                ];
                $breakdown['total_used_minutes'] += $taskMinutes;
            }
        }

        // حساب وقت المهام لكل رول
        $rolesWithTasks = $this->templateTasks()
                              ->whereNotNull('role_id')
                              ->where('is_active', true)
                              ->with('role')
                              ->get()
                              ->groupBy('role_id');

        foreach ($rolesWithTasks as $roleId => $tasks) {
            $roleMinutes = 0;
            $roleTasks = [];
            $roleName = $tasks->first()->role->name ?? 'غير معروف';

            foreach ($tasks as $task) {
                if (!$task->isFlexibleTime()) {
                    $taskMinutes = ($task->estimated_hours ?? 0) * 60 + ($task->estimated_minutes ?? 0);
                    $roleTasks[] = [
                        'name' => $task->name,
                        'minutes' => $taskMinutes,
                        'formatted' => $this->formatMinutesToHours($taskMinutes)
                    ];
                    $roleMinutes += $taskMinutes;
                }
            }

            if ($roleMinutes > 0) {
                $breakdown['roles'][$roleId] = [
                    'name' => $roleName,
                    'total_minutes' => $roleMinutes,
                    'formatted' => $this->formatMinutesToHours($roleMinutes),
                    'tasks' => $roleTasks
                ];
            }
        }

        return $breakdown;
    }

    /**
     * تحويل الدقائق إلى تنسيق ساعات:دقائق
     */
    private function formatMinutesToHours($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return $hours . 'س ' . $mins . 'د';
        } elseif ($hours > 0) {
            return $hours . 'س';
        } else {
            return $mins . 'د';
        }
    }
}
