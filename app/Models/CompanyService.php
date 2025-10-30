<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;
use Illuminate\Support\Facades\DB;

class CompanyService extends Model
{
    use HasFactory, SoftDeletes, HasSecureId, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'points',
        'max_points_per_project',
        'is_active',
        'department',
        'execution_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'points' => 'integer',
        'max_points_per_project' => 'integer',
        'execution_order' => 'integer'
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'points', 'is_active', 'department', 'execution_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء خدمة شركة جديدة',
                'updated' => 'تم تحديث خدمة الشركة',
                'deleted' => 'تم حذف خدمة الشركة',
                default => $eventName
            });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderByPoints($query, $direction = 'desc')
    {
        return $query->orderBy('points', $direction);
    }

    public function scopeWithPointsGreaterThan($query, $points)
    {
        return $query->where('points', '>', $points);
    }

    public function scopeByDepartment($query, $department)
    {
        if ($department) {
            return $query->where('department', $department);
        }
        return $query;
    }

    public function taskTemplates()
    {
        return $this->hasMany(TaskTemplate::class, 'service_id')
                    ->orderBy('order');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'service_id');
    }

    /**
     * حقول البيانات الديناميكية للخدمة
     */
    public function dataFields()
    {
        return $this->hasMany(ServiceDataField::class, 'service_id')
                    ->orderBy('order');
    }

    /**
     * Get users who work with this service through projects
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_service_user', 'service_id', 'user_id')
                    ->select('users.id', 'users.name', 'users.email')
                    ->distinct();
    }

    /**
     * Get users who specialize in this service based on department or roles
     */
    public function specializedUsers()
    {
        // First get users from project_service_user table
        $projectUsers = $this->users();

        // Also include users whose department matches this service's department
        $departmentUsers = User::where('department', $this->department);

        // Combine both queries
        return User::whereIn('id', function($query) {
            $query->select('user_id')
                  ->from('project_service_user')
                  ->where('service_id', $this->id);
        })->orWhere('department', $this->department);
    }

    /**
     * الحصول على أدوار القسم المرتبط بالخدمة
     */
    public function getDepartmentRoles()
    {
        if (!$this->department) {
            return collect();
        }

        return DepartmentRole::with('role')
            ->where('department_name', $this->department)
            ->orderBy('hierarchy_level', 'desc')
            ->get();
    }

    /**
     * الحصول على المستخدمين في القسم حسب الدور
     */
    public function getUsersByRole($roleId)
    {
        return User::where('department', $this->department)
            ->whereHas('roles', function ($query) use ($roleId) {
                $query->where('id', $roleId);
            })
            ->get();
    }

    /**
     * الأدوار المطلوبة للخدمة
     */
    public function requiredRoles()
    {
        return $this->belongsToMany(
            \Spatie\Permission\Models\Role::class,
            'company_service_role',
            'company_service_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * التحقق من أن الدور مطلوب للخدمة
     */
    public function requiresRole($roleId)
    {
        return $this->requiredRoles()->where('role_id', $roleId)->exists();
    }

    /**
     * التحقق من وجود حد أقصى للنقاط لكل مشروع
     */
    public function hasMaxPointsLimit()
    {
        return $this->max_points_per_project > 0;
    }

    /**
     * الحصول على الحد الأقصى للنقاط لكل مشروع
     */
    public function getMaxPointsPerProject()
    {
        return $this->max_points_per_project ?? 0;
    }

    /**
     * التحقق من إمكانية إضافة نقاط إضافية لمشروع
     */
    public function canAddPointsToProject($projectId, $additionalPoints)
    {
        if (!$this->hasMaxPointsLimit()) {
            return true; // لا يوجد حد أقصى محدد
        }

        $currentPoints = $this->getCurrentPointsForProject($projectId);
        return ($currentPoints + $additionalPoints) <= $this->max_points_per_project;
    }

    /**
     * الحصول على النقاط الحالية للخدمة في مشروع معين
     */
    public function getCurrentPointsForProject($projectId)
    {
        // حساب النقاط من المهام العادية
        $regularTasksPoints = \App\Models\Task::where('project_id', $projectId)
            ->where('service_id', $this->id)
            ->sum('points');

        // حساب النقاط من مهام القوالب
        $templateTasksPoints = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->join('task_templates', 'template_tasks.task_template_id', '=', 'task_templates.id')
            ->where('template_task_user.project_id', $projectId)
            ->where('task_templates.service_id', $this->id)
            ->sum('template_tasks.points');

        return $regularTasksPoints + $templateTasksPoints;
    }

    /**
     * الحصول على النقاط المتبقية للمشروع
     */
    public function getRemainingPointsForProject($projectId)
    {
        if (!$this->hasMaxPointsLimit()) {
            return null; // لا يوجد حد أقصى
        }

        $currentPoints = $this->getCurrentPointsForProject($projectId);
        return max(0, $this->max_points_per_project - $currentPoints);
    }

    /**
     * التحقق من تجاوز الحد الأقصى للنقاط في مشروع
     */
    public function isOverLimitForProject($projectId)
    {
        if (!$this->hasMaxPointsLimit()) {
            return false;
        }

        $currentPoints = $this->getCurrentPointsForProject($projectId);
        return $currentPoints > $this->max_points_per_project;
    }

    /**
     * الخدمات التي تعتمد على هذه الخدمة
     * (الخدمات التي ستبدأ بعد اكتمال هذه الخدمة)
     */
    public function dependentServices()
    {
        return $this->belongsToMany(
            CompanyService::class,
            'service_dependencies',
            'depends_on_service_id',  // الخدمة الحالية
            'service_id'               // الخدمات التي تعتمد عليها
        )->withTimestamps();
    }

    /**
     * الخدمات التي تعتمد عليها هذه الخدمة
     * (الخدمات التي يجب أن تكتمل قبل بدء هذه الخدمة)
     */
    public function dependencies()
    {
        return $this->belongsToMany(
            CompanyService::class,
            'service_dependencies',
            'service_id',              // الخدمة الحالية
            'depends_on_service_id'    // الخدمات المطلوبة
        )->withTimestamps();
    }

    /**
     * التحقق من وجود خدمات تعتمد على هذه الخدمة
     */
    public function hasDependentServices(): bool
    {
        return $this->dependentServices()->exists();
    }

    /**
     * التحقق من أن هذه الخدمة تعتمد على خدمات أخرى
     */
    public function hasDependencies(): bool
    {
        return $this->dependencies()->exists();
    }
}
