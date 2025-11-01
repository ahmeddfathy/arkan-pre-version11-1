<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Traits\HasSecureId;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Project extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasSecureId, LogsActivity, SoftDeletes;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'name',
        'company_type',
        'description',
        'client_id',
        'package_id',
        'start_date',
        'team_delivery_date',
        'actual_delivery_date',
        'client_agreed_delivery_date',
        'status',
        'is_urgent',
        'total_points',
        'manager',
        'code',
        'season_id',
        'project_month_year',
        'deleted_at'
    ];

    protected $fillable = [
        'name',
        'company_type',
        'description',
        'client_id',
        'package_id',
        'start_date',
        'team_delivery_date',
        'actual_delivery_date',
        'client_agreed_delivery_date',
        'delivery_type',
        'delivery_notes',
        'status',
        'is_urgent',
        'total_points',
        'manager',
        'note',
        'code',
        'season_id',
        'project_month_year',
        'custom_fields_data',
        'deleted_at',
        'preparation_enabled',
        'preparation_start_date',
        'preparation_days',
    ];


    protected $guarded_after_creation = ['code'];

    protected $casts = [
        'start_date' => 'date',
        'team_delivery_date' => 'date',
        'actual_delivery_date' => 'datetime',
        'client_agreed_delivery_date' => 'date',
        'is_urgent' => 'boolean',
        'custom_fields_data' => 'array',
        'deleted_at' => 'datetime',
        'preparation_enabled' => 'boolean',
        'preparation_start_date' => 'datetime',
        'preparation_days' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // تعبئة project_month_year تلقائياً عند إنشاء المشروع
        static::creating(function ($project) {
            if (empty($project->project_month_year)) {
                $now = now();
                $project->project_month_year = $now->month . '-' . $now->year;
            }
        });

        // حماية كود المشروع من التعديل
        static::updating(function ($project) {
            if ($project->isDirty('code') && $project->exists) {
                throw new \Exception('لا يمكن تعديل كود المشروع بعد إنشائه');
            }
        });

        // حماية المشاريع من الحذف نهائياً
        static::deleting(function ($project) {
            throw new \Exception('حذف المشاريع محظور نهائياً للحفاظ على سلامة البيانات التاريخية للنظام');
        });

        // حفظ log للتواريخ القديمة عند التعديل
        static::updating(function ($project) {
            $dateFields = [
                'start_date',
                'team_delivery_date',
                'client_agreed_delivery_date',
                'actual_delivery_date'
            ];

            foreach ($dateFields as $field) {
                if ($project->isDirty($field)) {
                    $oldValue = $project->getOriginal($field);
                    $newValue = $project->getAttribute($field);

                    // حفظ التاريخ القديم في الـ log إذا كان موجود
                    if ($oldValue && $oldValue != $newValue) {
                        ProjectDate::logOldDate(
                            $project->id,
                            $field,
                            $oldValue,
                            'تاريخ سابق تم تغييره إلى: ' . ($newValue ? \Carbon\Carbon::parse($newValue)->format('Y-m-d') : 'غير محدد'),
                            Auth::id() ?? 1
                        );
                    }
                }
            }
        });
    }

    /**
     * الحصول على الشهر من project_month_year
     */
    public function getProjectMonth()
    {
        if (empty($this->project_month_year)) {
            return null;
        }

        $parts = explode('-', $this->project_month_year);
        return isset($parts[0]) ? (int) $parts[0] : null;
    }

    /**
     * الحصول على السنة من project_month_year
     */
    public function getProjectYear()
    {
        if (empty($this->project_month_year)) {
            return null;
        }

        $parts = explode('-', $this->project_month_year);
        return isset($parts[1]) ? (int) $parts[1] : null;
    }

    /**
     * Scope للبحث حسب شهر وسنة محددة
     */
    public function scopeByMonthYear($query, $month, $year)
    {
        return $query->where('project_month_year', $month . '-' . $year);
    }

    /**
     * Scope للبحث حسب سنة محددة
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('project_month_year', 'like', '%-' . $year);
    }

    /**
     * Scope للبحث حسب شهر محدد (بغض النظر عن السنة)
     */
    public function scopeByMonth($query, $month)
    {
        return $query->where('project_month_year', 'like', $month . '-%');
    }

    /**
     * التحقق من إمكانية تعديل كود المشروع
     */
    public function canEditCode()
    {
        return !$this->exists; // فقط المشاريع الجديدة غير المحفوظة يمكن تعديل كودها
    }

    /**
     * التحقق من إمكانية تعديل خاصية معينة
     */
    public function canEditAttribute($attribute)
    {
        $protectedAfterCreation = $this->guarded_after_creation ?? [];

        if (in_array($attribute, $protectedAfterCreation) && $this->exists) {
            return false;
        }

        return true;
    }

    /**
     * الحصول على تاريخ التعديلات لنوع تاريخ معين
     */
    public function getDateHistory($dateType)
    {
        return $this->projectDates()
            ->where('date_type', $dateType)
            ->orderBy('effective_from', 'desc')
            ->get();
    }

    /**
     * الحصول على جميع تواريخ التعديلات للمشروع
     */
    public function getAllDateHistory()
    {
        return $this->projectDates()
            ->with('user')
            ->orderBy('effective_from', 'desc')
            ->get();
    }

    /**
     * التحقق من وجود تعديلات على تاريخ معين
     */
    public function hasDateHistory($dateType)
    {
        return $this->projectDates()
            ->where('date_type', $dateType)
            ->count() > 0;
    }

    /**
     * الحصول على عدد التعديلات لتاريخ معين
     */
    public function getDateHistoryCount($dateType)
    {
        return $this->projectDates()
            ->where('date_type', $dateType)
            ->count();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'status', 'start_date', 'team_delivery_date', 'actual_delivery_date', 'client_agreed_delivery_date', 'is_urgent', 'total_points', 'manager', 'note', 'code', 'project_month_year'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // العلاقة مع الموسم
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    // العلاقة مع العميل
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // العلاقة مع الخدمات
    public function services()
    {
        return $this->belongsToMany(CompanyService::class, 'project_service', 'project_id', 'service_id')
            ->withPivot('service_status', 'service_data')
            ->withTimestamps();
    }

    // علاقة المشروع مع الباقة
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    // المرفقات
    public function attachments()
    {
        return $this->hasMany(\App\Models\ProjectAttachment::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'project_service_user', 'project_id', 'user_id')
            ->select('users.id', 'users.name', 'users.email')
            ->distinct();
    }

    public function serviceParticipants()
    {
        return $this->hasMany(ProjectServiceUser::class, 'project_id')->with('role');
    }

    /**
     * Alias for serviceParticipants
     */
    public function projectServiceUsers()
    {
        return $this->serviceParticipants();
    }

    /**
     * Get tasks associated with this project
     */
    public function tasks()
    {
        return $this->hasMany(Task::class)
            ->orderBy('order');
    }

    /**
     * التحقق من وجود مهام مرتبطة بالمشروع
     */
    public function hasActiveTasks()
    {
        return $this->tasks()
            ->whereIn('status', ['جاري', 'متوقف', 'مكتمل', 'قيد المراجعة', 'في الانتظار'])
            ->exists();
    }

    /**
     * التحقق من وجود مهام غير مكتملة
     */
    public function hasIncompleteTasks()
    {
        return $this->tasks()
            ->whereNotIn('status', ['مكتمل', 'ملغي'])
            ->exists();
    }

    /**
     * التحقق من وجود مهام نشطة (جاري أو متوقف)
     */
    public function hasRunningTasks()
    {
        return $this->tasks()
            ->whereIn('status', ['جاري', 'متوقف'])
            ->exists();
    }

    /**
     * التحقق من إمكانية حذف المشروع - محظور نهائياً
     */
    public function canBeDeleted()
    {
        // حذف المشاريع محظور نهائياً لحماية البيانات التاريخية
        return false;
    }

    /**
     * الحصول على رسالة خطأ عند عدم إمكانية الحذف
     */
    public function getDeletionErrorMessage()
    {
        return 'حذف المشاريع محظور نهائياً للحفاظ على سلامة البيانات التاريخية للنظام. يمكنك تغيير حالة المشروع إلى "ملغي" بدلاً من ذلك.';
    }

    /**
     * Get template task assignments for this project
     */
    public function templateTaskUsers()
    {
        return $this->hasMany(TemplateTaskUser::class);
    }

    /**
     * Get regular task assignments for this project
     */
    public function taskUsers()
    {
        return $this->hasManyThrough(
            TaskUser::class,
            Task::class,
            'project_id', // Foreign key on tasks table
            'task_id',    // Foreign key on task_users table
            'id',         // Local key on projects table
            'id'          // Local key on tasks table
        );
    }

    /**
     * Get project notes
     */
    public function notes()
    {
        return $this->hasMany(ProjectNote::class)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * العلاقة مع تواريخ المشروع
     */
    public function projectDates()
    {
        return $this->hasMany(ProjectDate::class)->orderBy('effective_from', 'desc');
    }

    /**
     * العلاقة مع سجل فترات التحضير
     */
    public function preparationHistory()
    {
        return $this->hasMany(ProjectPreparationHistory::class)->orderBy('effective_from', 'desc');
    }


    public function scopeNew($query)
    {
        return $query->where('status', 'جديد');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'جاري التنفيذ');
    }

    // Scope للمشاريع المكتملة
    public function scopeCompleted($query)
    {
        return $query->where('status', 'مكتمل');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'ملغي');
    }


    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }


    public function scopeNotUrgent($query)
    {
        return $query->where('is_urgent', false);
    }

    public function calculateTotalPoints()
    {
        $total = $this->services()->sum('points');
        $this->update(['total_points' => $total]);
        return $total;
    }


    /**
     * Get the total actual time spent on all tasks in this project in minutes (including both regular and template tasks)
     */
    public function getTotalActualMinutesAttribute()
    {
        // الوقت الفعلي للمهام العادية
        $regularTasksTime = 0;
        $tasks = $this->tasks;
        foreach ($tasks as $task) {
            $regularTasksTime += $task->getTotalActualMinutesAttribute();
        }

        // الوقت الفعلي لمهام القوالب
        $templateTasksTime = $this->templateTaskUsers()->sum('actual_minutes');

        // إجمالي الوقت
        return $regularTasksTime + $templateTasksTime;
    }


    public function getTotalEstimatedMinutesAttribute()
    {
        // الوقت المقدر للمهام العادية (تجاهل المهام المرنة)
        $regularTasksTime = 0;
        $tasks = $this->tasks;
        foreach ($tasks as $task) {
            $estimatedTime = $task->getTotalEstimatedMinutesAttribute();
            if ($estimatedTime !== null) {
                $regularTasksTime += $estimatedTime;
            }
        }

        // الوقت المقدر لمهام القوالب (مع تجاهل المهام المرنة)
        $templateTasksTime = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->where('template_task_user.project_id', $this->id)
            ->whereNotNull('template_tasks.estimated_hours') // تجاهل المهام المرنة
            ->whereNotNull('template_tasks.estimated_minutes')
            ->sum(DB::raw('(template_tasks.estimated_hours * 60) + template_tasks.estimated_minutes'));

        // إجمالي الوقت
        return $regularTasksTime + $templateTasksTime;
    }

    /**
     * Get formatted total actual time
     */
    public function getTotalActualTimeFormattedAttribute()
    {
        $totalMinutes = $this->getTotalActualMinutesAttribute();

        if ($totalMinutes == 0) {
            return '0h';
        }

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }

        if ($minutes > 0) {
            $parts[] = $minutes . 'm';
        }

        return implode(' ', $parts);
    }

    /**
     * Get formatted total estimated time
     */
    public function getTotalEstimatedTimeFormattedAttribute()
    {
        $totalMinutes = $this->getTotalEstimatedMinutesAttribute();

        if ($totalMinutes == 0) {
            return '0h';
        }

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }

        if ($minutes > 0) {
            $parts[] = $minutes . 'm';
        }

        return implode(' ', $parts);
    }

    /**
     * Get completion percentage for both regular tasks and template tasks
     */
    public function getCompletionPercentageAttribute()
    {
        // الحساب على مستوى تعيينات المهام (TaskUser + TemplateTaskUser)
        // لضمان دقة النسبة عندما تكون هناك مهام متعددة للمستخدمين على نفس المهمة

        // التعيينات العادية
        $regularAssignmentsCount = $this->taskUsers()->count();
        $regularCompletedAssignments = $this->taskUsers()->where('task_users.status', 'completed')->count();

        // تعيينات مهام القوالب
        $templateAssignmentsCount = $this->templateTaskUsers()->count();
        $templateCompletedAssignments = $this->templateTaskUsers()->where('status', 'completed')->count();

        // الإجمالي
        $totalAssignments = $regularAssignmentsCount + $templateAssignmentsCount;
        if ($totalAssignments === 0) {
            return 0;
        }

        $totalCompleted = $regularCompletedAssignments + $templateCompletedAssignments;
        return round(($totalCompleted / $totalAssignments) * 100);
    }

    /**
     * التحقق من حالة تأكيد الاستلام للمستخدم الحالي
     */
    public function isAcknowledgedByUser($userId = null): bool
    {
        $userId = $userId ?? \Illuminate\Support\Facades\Auth::id();

        return $this->serviceParticipants()
            ->where('user_id', $userId)
            ->where('is_acknowledged', true)
            ->exists();
    }


    public function hasUnacknowledgedParticipation($userId = null): bool
    {
        $userId = $userId ?? \Illuminate\Support\Facades\Auth::id();

        return $this->serviceParticipants()
            ->where('user_id', $userId)
            ->where('is_acknowledged', false)
            ->exists();
    }


    public function acknowledgeForUser($userId = null): bool
    {
        $userId = $userId ?? \Illuminate\Support\Facades\Auth::id();

        $updated = $this->serviceParticipants()
            ->where('user_id', $userId)
            ->update([
                'is_acknowledged' => true,
                'acknowledged_at' => now(),
            ]);

        return $updated > 0;
    }


    public function getAcknowledgmentStatusForUser($userId = null): array
    {
        $userId = $userId ?? \Illuminate\Support\Facades\Auth::id();

        $participations = $this->serviceParticipants()
            ->where('user_id', $userId)
            ->get();

        $totalParticipations = $participations->count();
        $acknowledgedParticipations = $participations->where('is_acknowledged', true)->count();

        return [
            'total_participations' => $totalParticipations,
            'acknowledged_participations' => $acknowledgedParticipations,
            'is_fully_acknowledged' => $totalParticipations > 0 && $acknowledgedParticipations === $totalParticipations,
            'has_unacknowledged' => $acknowledgedParticipations < $totalParticipations,
            'acknowledgment_percentage' => $totalParticipations > 0 ? round(($acknowledgedParticipations / $totalParticipations) * 100) : 0,
        ];
    }

    /**
     * العلاقة مع تعديلات المشروع
     */
    public function projectRevisions()
    {
        return $this->hasMany(TaskRevision::class, 'project_id')
            ->where('revision_type', 'project');
    }

    /**
     * الحصول على قيمة حقل مخصص
     */
    public function getCustomField($fieldKey, $default = null)
    {
        $customFields = $this->custom_fields_data ?? [];
        return $customFields[$fieldKey] ?? $default;
    }

    /**
     * تعيين قيمة حقل مخصص
     */
    public function setCustomField($fieldKey, $value)
    {
        $customFields = $this->custom_fields_data ?? [];
        $customFields[$fieldKey] = $value;
        $this->custom_fields_data = $customFields;
        return $this;
    }

    /**
     * تعيين عدة حقول مخصصة
     */
    public function setCustomFields(array $fields)
    {
        $customFields = $this->custom_fields_data ?? [];
        foreach ($fields as $key => $value) {
            $customFields[$key] = $value;
        }
        $this->custom_fields_data = $customFields;
        return $this;
    }

    /**
     * الحصول على جميع الحقول المخصصة مع معلوماتها
     */
    public function getCustomFieldsWithInfo()
    {
        $fields = ProjectField::active()->ordered()->get();
        $customFieldValues = $this->custom_fields_data ?? [];

        return $fields->map(function ($field) use ($customFieldValues) {
            return [
                'field' => $field,
                'value' => $customFieldValues[$field->field_key] ?? null
            ];
        });
    }


    public function getPreparationEndDateAttribute()
    {
        try {
            if (!$this->preparation_enabled || !$this->preparation_start_date || !$this->preparation_days) {
                return null;
            }

            $startDateTime = \Carbon\Carbon::parse($this->preparation_start_date);
            $originalTime = [
                'hour' => $startDateTime->hour,
                'minute' => $startDateTime->minute,
                'second' => $startDateTime->second,
            ];

            $daysToAdd = $this->preparation_days;
            $currentDate = $startDateTime->copy();
            $workDaysAdded = 0;

            while ($workDaysAdded < $daysToAdd) {
                $currentDate->addDay();

                if ($currentDate->dayOfWeek !== \Carbon\Carbon::FRIDAY) {
                    $workDaysAdded++;
                }
            }

            $currentDate->setTime(
                $originalTime['hour'],
                $originalTime['minute'],
                $originalTime['second']
            );

            return $currentDate;
        } catch (\Exception $e) {
            Log::error('Error calculating preparation end date', [
                'project_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function isInPreparationPeriod(): bool
    {
        try {
            if (!$this->preparation_enabled || !$this->preparation_start_date || !$this->preparation_days) {
                return false;
            }

            $now = \Carbon\Carbon::now();
            $startDate = \Carbon\Carbon::parse($this->preparation_start_date);
            $endDate = $this->preparation_end_date;

            if (!$endDate) {
                return false;
            }

            return $now->between($startDate, $endDate);
        } catch (\Exception $e) {
            Log::error('Error checking preparation period', [
                'project_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * الحصول على عدد الأيام المتبقية في فترة التحضير
     */
    public function getRemainingPreparationDaysAttribute()
    {
        if (!$this->isInPreparationPeriod()) {
            return 0;
        }

        $today = \Carbon\Carbon::today();
        $endDate = $this->preparation_end_date;
        $remainingDays = 0;
        $currentDate = $today->copy();

        while ($currentDate->lte($endDate)) {
            // عد فقط أيام العمل (بدون الجمعة)
            if ($currentDate->dayOfWeek !== \Carbon\Carbon::FRIDAY) {
                $remainingDays++;
            }
            $currentDate->addDay();
        }

        return max(0, $remainingDays);
    }

    /**
     * الحصول على نسبة التقدم في فترة التحضير
     */
    public function getPreparationProgressPercentageAttribute()
    {
        if (!$this->preparation_enabled || !$this->preparation_start_date || !$this->preparation_days) {
            return 0;
        }

        $today = \Carbon\Carbon::today();
        $startDate = \Carbon\Carbon::parse($this->preparation_start_date);
        $endDate = $this->preparation_end_date;

        // إذا لم تبدأ الفترة بعد
        if ($today->lt($startDate)) {
            return 0;
        }

        // إذا انتهت الفترة
        if ($today->gt($endDate)) {
            return 100;
        }

        // حساب الأيام المنقضية (بدون الجمعة)
        $elapsedDays = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lt($today)) {
            if ($currentDate->dayOfWeek !== \Carbon\Carbon::FRIDAY) {
                $elapsedDays++;
            }
            $currentDate->addDay();
        }

        return round(($elapsedDays / $this->preparation_days) * 100);
    }

    /**
     * التحقق من انتهاء فترة التحضير
     */
    public function hasPreparationPeriodEnded(): bool
    {
        if (!$this->preparation_enabled || !$this->preparation_start_date || !$this->preparation_days) {
            return false;
        }

        $now = \Carbon\Carbon::now();
        $endDate = $this->preparation_end_date;

        return $now->gt($endDate);
    }

    /**
     * الحصول على حالة فترة التحضير
     */
    public function getPreparationStatusAttribute(): string
    {
        if (!$this->preparation_enabled) {
            return 'disabled';
        }

        if (!$this->preparation_start_date || !$this->preparation_days) {
            return 'not_configured';
        }

        $now = \Carbon\Carbon::now();
        $startDate = \Carbon\Carbon::parse($this->preparation_start_date);
        $endDate = $this->preparation_end_date;

        if ($now->lt($startDate)) {
            return 'pending'; // لم تبدأ بعد
        }

        if ($now->between($startDate, $endDate)) {
            return 'active'; // جارية حالياً
        }

        return 'completed'; // انتهت
    }

    /**
     * Scope للمشاريع في فترة التحضير
     */
    public function scopeInPreparationPeriod($query)
    {
        return $query->where('preparation_enabled', true)
            ->whereNotNull('preparation_start_date')
            ->whereNotNull('preparation_days')
            ->whereRaw('preparation_start_date <= CURDATE()')
            ->where(function ($q) {
                $q->whereRaw('DATE_ADD(preparation_start_date, INTERVAL preparation_days DAY) >= CURDATE()');
            });
    }

    /**
     * Scope للمشاريع التي انتهت فترة تحضيرها
     */
    public function scopePreparationPeriodEnded($query)
    {
        return $query->where('preparation_enabled', true)
            ->whereNotNull('preparation_start_date')
            ->whereNotNull('preparation_days')
            ->whereRaw('DATE_ADD(preparation_start_date, INTERVAL preparation_days DAY) < CURDATE()');
    }

    /**
     * Scope للمشاريع التي لم تبدأ فترة تحضيرها بعد
     */
    public function scopePreparationPeriodPending($query)
    {
        return $query->where('preparation_enabled', true)
            ->whereNotNull('preparation_start_date')
            ->whereNotNull('preparation_days')
            ->whereRaw('preparation_start_date > CURDATE()');
    }

    /**
     * Scope للمشاريع التي فعلت فترة التحضير
     */
    public function scopeWithPreparationEnabled($query)
    {
        return $query->where('preparation_enabled', true);
    }

    /**
     * العلاقة مع طلبات تأكيد المرفقات
     */
    public function attachmentConfirmations()
    {
        return $this->hasMany(AttachmentConfirmation::class);
    }

    /**
     * الحصول على عدد طلبات تأكيد المرفقات التي تمت خلال فترة التحضير
     */
    public function getAttachmentConfirmationsDuringPreparationAttribute()
    {
        if (!$this->preparation_enabled || !$this->preparation_start_date || !$this->preparation_days) {
            return 0;
        }

        $endDate = $this->preparation_end_date;

        return $this->attachmentConfirmations()
            ->whereBetween('created_at', [$this->preparation_start_date, $endDate])
            ->count();
    }

    /**
     * الحصول على عدد طلبات تأكيد المرفقات التي تمت بعد فترة التحضير
     */
    public function getAttachmentConfirmationsAfterPreparationAttribute()
    {
        if (!$this->preparation_enabled || !$this->preparation_start_date || !$this->preparation_days) {
            return 0;
        }

        $endDate = $this->preparation_end_date;

        return $this->attachmentConfirmations()
            ->where('created_at', '>', $endDate)
            ->count();
    }

    /**
     * الحصول على إجمالي طلبات تأكيد المرفقات
     */
    public function getTotalAttachmentConfirmationsAttribute()
    {
        return $this->attachmentConfirmations()->count();
    }

    /**
     * العلاقة مع سجلات التوقيف
     */
    public function pauses()
    {
        return $this->hasMany(ProjectPause::class)->orderBy('paused_at', 'desc');
    }

    /**
     * العلاقة مع التوقيف النشط الحالي
     */
    public function activePause()
    {
        return $this->hasOne(ProjectPause::class)->where('is_active', true)->latest('paused_at');
    }

    /**
     * العلاقة مع التسليمات
     */
    public function deliveries()
    {
        return $this->hasMany(ProjectDelivery::class)->orderBy('delivery_date', 'desc');
    }

    /**
     * العلاقة مع آخر تسليم مسودة
     */
    public function lastDraftDelivery()
    {
        return $this->hasOne(ProjectDelivery::class)->where('delivery_type', 'مسودة')->latest('delivery_date');
    }

    /**
     * العلاقة مع آخر تسليم نهائي
     */
    public function lastFinalDelivery()
    {
        return $this->hasOne(ProjectDelivery::class)->where('delivery_type', 'نهائي')->latest('delivery_date');
    }

    /**
     * الحصول على قائمة أسباب التوقيف المتاحة
     */
    public static function getPauseReasons(): array
    {
        return [
            'واقف ع النموذج' => 'واقف على النموذج',
            'واقف ع الأسئلة' => 'واقف على الأسئلة',
            'واقف ع العميل' => 'واقف على العميل',
            'واقف ع مكالمة' => 'واقف على مكالمة',
            'موقوف' => 'موقوف',
        ];
    }

    /**
     * توقيف المشروع
     */
    public function pauseProject(string $reason, ?string $notes = null, ?int $userId = null): bool
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // إلغاء أي توقيف نشط سابق
            $this->pauses()->where('is_active', true)->update(['is_active' => false]);

            // إنشاء سجل توقيف جديد
            $this->pauses()->create([
                'pause_reason' => $reason,
                'pause_notes' => $notes,
                'paused_at' => now(),
                'paused_by' => $userId ?? \Illuminate\Support\Facades\Auth::id(),
                'is_active' => true,
            ]);

            // تحديث حالة المشروع
            $this->status = 'موقوف';
            $this->save();

            \Illuminate\Support\Facades\DB::commit();
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error pausing project', [
                'project_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * إلغاء توقيف المشروع
     */
    public function resumeProject(?int $userId = null): bool
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // إلغاء التوقيف النشط
            $activePause = $this->activePause;
            if ($activePause) {
                $activePause->resume($userId);
            }

            // تحديث حالة المشروع
            if ($this->status === 'موقوف') {
                $this->status = 'جاري التنفيذ';
                $this->save();
            }

            \Illuminate\Support\Facades\DB::commit();
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error resuming project', [
                'project_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * التحقق من أن المشروع موقوف
     */
    public function isPaused(): bool
    {
        return $this->status === 'موقوف' && $this->activePause()->exists();
    }

    /**
     * الحصول على عدد مرات التوقيف
     */
    public function getPausesCountAttribute(): int
    {
        return $this->pauses()->count();
    }

    /**
     * الحصول على إجمالي مدة التوقيف بالأيام
     */
    public function getTotalPauseDaysAttribute(): int
    {
        return $this->pauses()->get()->sum('duration_in_days');
    }
}
