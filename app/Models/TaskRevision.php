<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class TaskRevision extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasSecureId, LogsActivity;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'task_id',
        'task_user_id',
        'template_task_user_id',
        'project_id',
        'assigned_to',
        'task_type',
        'revision_type',
        'revision_source',
        'title',
        'description',
        'notes',
        'attachment_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'created_by',
        'season_id'
    ];

    protected $fillable = [
        'task_id',
        'task_user_id',
        'template_task_user_id',
        'project_id',
        'assigned_to',
        'task_type',
        'revision_type',
        'revision_source',
        'title',
        'description',
        'notes',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'attachment_size',
        'attachment_link',
        'created_by',
        'revision_date',
        'status', // new, in_progress, paused, completed
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'season_id',
        // حقول تتبع الوقت
        'started_at',
        'paused_at',
        'completed_at_work',
        'resumed_at',
        'actual_minutes',
        'current_session_start',
        // حقول المسؤولية والتنفيذ
        'responsible_user_id',  // المسؤول (اللي غلط وسبب التعديل - هيتحاسب)
        'executor_user_id',
        'reviewers',
        'responsibility_notes',
        'review_started_at',
        'review_paused_at',
        'review_completed_at',
        'review_resumed_at',
        'review_actual_minutes',
        'review_current_session_start',
        'review_status',
        'revision_deadline', // ديدلاين التعديل العام
    ];

    protected $casts = [
        'revision_date' => 'datetime',
        'reviewed_at' => 'datetime',
        'attachment_size' => 'integer',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at_work' => 'datetime',
        'resumed_at' => 'datetime',
        'current_session_start' => 'datetime',
        'actual_minutes' => 'integer',
        // Reviewer time tracking casts
        'review_started_at' => 'datetime',
        'review_paused_at' => 'datetime',
        'review_completed_at' => 'datetime',
        'review_resumed_at' => 'datetime',
        'review_current_session_start' => 'datetime',
        'review_actual_minutes' => 'integer',
        // Multiple reviewers JSON cast
        'reviewers' => 'array',
        'revision_deadline' => 'datetime',
    ];

    protected $dates = [
        'revision_date',
        'reviewed_at',
        'started_at',
        'paused_at',
        'review_started_at',
        'review_paused_at',
        'review_completed_at',
        'review_current_session_start',
        'completed_at_work',
        'resumed_at',
        'current_session_start',
        'revision_deadline',
    ];

    protected $appends = [
        'reviewers_with_data'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'status', 'task_type', 'reviewed_by', 'review_notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }


    public function task()
    {
        return $this->belongsTo(Task::class);
    }


    public function taskUser()
    {
        return $this->belongsTo(TaskUser::class);
    }


    public function templateTaskUser()
    {
        return $this->belongsTo(TemplateTaskUser::class);
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع المستخدم الذي راجع التعديل
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * العلاقة مع الموسم
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * العلاقة مع المشروع
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * العلاقة مع الخدمة
     */
    public function service()
    {
        return $this->belongsTo(\App\Models\CompanyService::class, 'service_id');
    }

    /**
     * العلاقة مع الشخص المسند إليه التعديل
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * العلاقة مع المسؤول عن الغلط (اللي غلط في الأول وسبب التعديل - هيتحاسب)
     */
    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * العلاقة مع منفذ التعديل (اللي هيصلح الغلط)
     */
    public function executorUser()
    {
        return $this->belongsTo(User::class, 'executor_user_id');
    }

    /**
     * العلاقة مع الديدلاينات (Deadlines)
     */
    public function deadlines()
    {
        return $this->hasMany(RevisionDeadline::class, 'revision_id');
    }

    /**
     * الحصول على ديدلاين المنفذ
     */
    public function executorDeadline()
    {
        return $this->hasOne(RevisionDeadline::class, 'revision_id')
                    ->where('deadline_type', 'executor')
                    ->latest();
    }

    /**
     * الحصول على ديدلاينات المراجعين
     */
    public function reviewerDeadlines()
    {
        return $this->hasMany(RevisionDeadline::class, 'revision_id')
                    ->where('deadline_type', 'reviewer')
                    ->orderBy('reviewer_order');
    }

    /**
     * الحصول على المراجع الحالي (التالي في الترتيب)
     */
    public function getCurrentReviewer()
    {
        if (!$this->reviewers || !is_array($this->reviewers)) {
            return null;
        }

        // البحث عن أول مراجع pending
        foreach ($this->reviewers as $reviewer) {
            if ($reviewer['status'] === 'pending') {
                return User::find($reviewer['reviewer_id']);
            }
        }

        return null;
    }

    /**
     * الحصول على جميع المراجعين
     */
    public function getAllReviewers()
    {
        if (!$this->reviewers || !is_array($this->reviewers)) {
            return collect([]);
        }

        $reviewerIds = array_column($this->reviewers, 'reviewer_id');
        return User::whereIn('id', $reviewerIds)->get();
    }

    /**
     * الحصول على المراجع التالي (بعد إكمال الحالي)
     */
    public function getNextReviewer()
    {
        if (!$this->reviewers || !is_array($this->reviewers)) {
            return null;
        }

        // البحث عن أول مراجع pending بعد آخر completed
        $foundCompleted = false;
        foreach ($this->reviewers as $reviewer) {
            if ($reviewer['status'] === 'completed') {
                $foundCompleted = true;
                continue;
            }

            if ($foundCompleted && $reviewer['status'] === 'pending') {
                return User::find($reviewer['reviewer_id']);
            }
        }

        return null;
    }

    /**
     * إكمال مراجعة المراجع الحالي وإشعار التالي
     */
    public function completeCurrentReviewerAndNotifyNext()
    {
        if (!$this->reviewers || !is_array($this->reviewers)) {
            return false;
        }

        $currentUserId = auth()->id();
        $reviewers = $this->reviewers;
        $updated = false;

        // البحث عن المراجع الحالي وتحديث حالته
        foreach ($reviewers as $index => &$reviewer) {
            if ($reviewer['reviewer_id'] == $currentUserId && $reviewer['status'] === 'in_progress') {
                $reviewer['status'] = 'completed';
                $reviewer['completed_at'] = now()->toDateTimeString();
                $updated = true;

                // إشعار المراجع التالي
                if (isset($reviewers[$index + 1])) {
                    $nextReviewer = $reviewers[$index + 1];
                    $nextReviewerUser = User::find($nextReviewer['reviewer_id']);

                    if ($nextReviewerUser) {
                        try {
                            // إنشاء إشعار داخلي
                            \App\Models\Notification::create([
                                'user_id' => $nextReviewerUser->id,
                                'type' => 'revision_ready_for_review',
                                'data' => [
                                    'message' => "تم انتهاء المراجع السابق - دورك الآن في مراجعة: {$this->title}",
                                    'revision_id' => $this->id,
                                    'revision_title' => $this->title,
                                    'reviewer_order' => $index + 2,
                                    'previous_reviewer' => auth()->user()->name,
                                ],
                                'related_id' => $this->id
                            ]);

                            // إرسال إشعار Firebase
                            if ($nextReviewerUser->fcm_token) {
                                $firebaseService = app(\App\Services\FirebaseNotificationService::class);
                                $firebaseService->sendNotificationQueued(
                                    $nextReviewerUser->fcm_token,
                                    'دورك في المراجعة',
                                    "تم انتهاء المراجع السابق - دورك الآن في مراجعة: {$this->title}",
                                    "/revisions?revision_id={$this->id}"
                                );
                            }

                            \Illuminate\Support\Facades\Log::info('✅ Notification sent to next reviewer', [
                                'revision_id' => $this->id,
                                'next_reviewer_id' => $nextReviewerUser->id,
                                'reviewer_order' => $index + 2,
                            ]);

                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error sending notification to next reviewer', [
                                'revision_id' => $this->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                break;
            }
        }

        if ($updated) {
            $this->reviewers = $reviewers;
            $this->save();
        }

        return $updated;
    }

    /**
     * الحصول على رابط تحميل الملف المرفق
     */
    public function getAttachmentUrlAttribute()
    {
        if ($this->attachment_path) {
            return Storage::url($this->attachment_path);
        }
        return null;
    }

    /**
     * الحصول على حجم الملف مُنسق
     */
    public function getFormattedAttachmentSizeAttribute()
    {
        if (!$this->attachment_size) {
            return null;
        }

        $bytes = $this->attachment_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * الحصول على أيقونة نوع الملف
     */
    public function getAttachmentIconAttribute()
    {
        if (!$this->attachment_type) {
            return 'fas fa-file';
        }

        $type = strtolower($this->attachment_type);

        if (str_contains($type, 'image')) {
            return 'fas fa-image';
        } elseif (str_contains($type, 'pdf')) {
            return 'fas fa-file-pdf';
        } elseif (str_contains($type, 'word') || str_contains($type, 'document')) {
            return 'fas fa-file-word';
        } elseif (str_contains($type, 'excel') || str_contains($type, 'spreadsheet')) {
            return 'fas fa-file-excel';
        } elseif (str_contains($type, 'powerpoint') || str_contains($type, 'presentation')) {
            return 'fas fa-file-powerpoint';
        } elseif (str_contains($type, 'zip') || str_contains($type, 'rar')) {
            return 'fas fa-file-archive';
        } elseif (str_contains($type, 'video')) {
            return 'fas fa-file-video';
        } elseif (str_contains($type, 'audio')) {
            return 'fas fa-file-audio';
        } else {
            return 'fas fa-file';
        }
    }

    /**
     * الحصول على لون حالة العمل
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'new' => 'secondary',
            'in_progress' => 'primary',
            'paused' => 'warning',
            'completed' => 'success',
            default => 'secondary'
        };
    }

    /**
     * الحصول على نص حالة العمل
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'new' => 'جديد',
            'in_progress' => 'جاري العمل',
            'paused' => 'متوقف',
            'completed' => 'مكتمل',
            default => 'غير محدد'
        };
    }

    /**
     * الحصول على المراجعين مع بياناتهم الكاملة
     */
    public function getReviewersWithDataAttribute()
    {
        if (!$this->reviewers || !is_array($this->reviewers)) {
            return [];
        }

        return collect($this->reviewers)->map(function ($reviewer) {
            $user = User::select('id', 'name', 'email', 'department')->find($reviewer['reviewer_id']);

            return [
                'reviewer_id' => $reviewer['reviewer_id'],
                'order' => $reviewer['order'],
                'status' => $reviewer['status'],
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department
                ] : null,
                'completed_at' => $reviewer['completed_at'] ?? null
            ];
        })->toArray();
    }

    /**
     * الحصول على نص مصدر التعديل
     */
    public function getRevisionSourceTextAttribute()
    {
        return match($this->revision_source) {
            'internal' => 'تعديل داخلي',
            'external' => 'تعديل خارجي',
            default => 'غير محدد'
        };
    }

    /**
     * الحصول على لون مصدر التعديل
     */
    public function getRevisionSourceColorAttribute()
    {
        return match($this->revision_source) {
            'internal' => 'primary',
            'external' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * الحصول على أيقونة مصدر التعديل
     */
    public function getRevisionSourceIconAttribute()
    {
        return match($this->revision_source) {
            'internal' => 'fas fa-users',
            'external' => 'fas fa-external-link-alt',
            default => 'fas fa-question'
        };
    }

    /**
     * فلترة التعديلات حسب نوع المهمة
     */
    public function scopeForRegularTask($query, $taskId, $taskUserId = null)
    {
        // إذا كان task_user_id موجود، نحصل على task_id الحقيقي منه
        if ($taskUserId) {
            $taskUser = \App\Models\TaskUser::find($taskUserId);
            if ($taskUser) {
                $actualTaskId = $taskUser->task_id;
            } else {
                $actualTaskId = $taskId;
            }
        } else {
            // إذا لم يكن task_user_id موجود، نتحقق هل الـ taskId هو فعلاً task أم task_user
            $task = \App\Models\Task::find($taskId);
            if (!$task) {
                // قد يكون معرف TaskUser، جرب الحصول عليه
                $taskUser = \App\Models\TaskUser::find($taskId);
                if ($taskUser) {
                    $actualTaskId = $taskUser->task_id;
                    $taskUserId = $taskId;
                } else {
                    $actualTaskId = $taskId; // استخدم القيمة كما هي
                }
            } else {
                $actualTaskId = $taskId;
            }
        }

        return $query->where('task_type', 'regular')
                    ->where('task_id', $actualTaskId)
                    ->when($taskUserId, function($q) use ($taskUserId) {
                        return $q->orWhere('task_user_id', $taskUserId);
                    });
    }

    /**
     * فلترة التعديلات لمهام القوالب
     */
    public function scopeForTemplateTask($query, $templateTaskUserId)
    {
        return $query->where('task_type', 'template')
                    ->where('template_task_user_id', $templateTaskUserId);
    }

    /**
     * فلترة التعديلات حسب المستخدم
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * فلترة التعديلات حسب الحالة
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * ترتيب التعديلات من الأحدث للأقدم
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('revision_date', 'desc');
    }

    /**
     * فلترة التعديلات للمشاريع
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('revision_type', 'project')
                    ->where('project_id', $projectId);
    }

    /**
     * فلترة التعديلات العامة
     */
    public function scopeGeneral($query)
    {
        return $query->where('revision_type', 'general');
    }

    /**
     * فلترة التعديلات حسب النوع
     */
    public function scopeByType($query, $revisionType)
    {
        return $query->where('revision_type', $revisionType);
    }

    /**
     * فلترة التعديلات حسب المصدر
     */
    public function scopeBySource($query, $revisionSource)
    {
        return $query->where('revision_source', $revisionSource);
    }

    /**
     * فلترة التعديلات الداخلية
     */
    public function scopeInternal($query)
    {
        return $query->where('revision_source', 'internal');
    }

    /**
     * فلترة التعديلات الخارجية
     */
    public function scopeExternal($query)
    {
        return $query->where('revision_source', 'external');
    }

    /**
     * عند حذف التعديل - لا نحذف الملفات من Wasabi (للأرشفة)
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($revision) {
            // نسجل فقط أن التعديل تم حذفه، لكن نحتفظ بالملف في Wasabi للأرشفة
            if ($revision->attachment_path && $revision->attachment_type === 'file') {
                \Illuminate\Support\Facades\Log::info('📦 Revision deleted but file kept in Wasabi for archive', [
                    'revision_id' => $revision->id,
                    'file_path' => $revision->attachment_path,
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now()->toDateTimeString()
                ]);
            }
        });
    }

    /**
     * ========================================
     * Methods لإدارة حالة العمل والوقت
     * ========================================
     */

    /**
     * بدء العمل على التعديل
     */
    public function startWork(): bool
    {
        // إيقاف أي تعديل آخر يعمل عليه نفس المستخدم
        self::where('created_by', $this->created_by)
            ->where('status', 'in_progress')
            ->where('id', '!=', $this->id)
            ->each(function ($revision) {
                $revision->pauseWork();
            });

        $this->update([
            'status' => 'in_progress',
            'started_at' => $this->started_at ?? now(),
            'current_session_start' => now(),
            'paused_at' => null,
        ]);

        return true;
    }

    /**
     * إيقاف مؤقت للعمل
     */
    public function pauseWork(): bool
    {
        if ($this->status !== 'in_progress' || !$this->current_session_start) {
            return false;
        }

        // حساب الوقت المستغرق في هذه الجلسة باستخدام abs للتأكد من عدم وجود قيم سالبة
        $sessionMinutes = abs(now()->diffInMinutes($this->current_session_start));

        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
            'actual_minutes' => max(0, $this->actual_minutes + $sessionMinutes),
            'current_session_start' => null,
        ]);

        return true;
    }

    /**
     * استئناف العمل
     */
    public function resumeWork(): bool
    {
        if ($this->status !== 'paused') {
            return false;
        }

        return $this->startWork();
    }

    /**
     * إكمال التعديل
     */
    public function completeWork(): bool
    {

        if ($this->status === 'in_progress' && $this->current_session_start) {

            $sessionMinutes = abs(now()->diffInMinutes($this->current_session_start));
            $this->actual_minutes = max(0, $this->actual_minutes + $sessionMinutes);
        }

        $this->update([
            'status' => 'completed',
            'completed_at_work' => now(),
            'current_session_start' => null,
        ]);

        // ✅ إرسال إشعار للمراجع الأول (نظام المراجعة المتسلسلة)
        $firstReviewer = $this->getCurrentReviewer();
        if ($firstReviewer) {
            try {
                // إنشاء إشعار داخلي
                \App\Models\Notification::create([
                    'user_id' => $firstReviewer->id,
                    'type' => 'revision_completed_for_review',
                    'data' => [
                        'message' => "تم إكمال التعديل: {$this->title} - في انتظار مراجعتك",
                        'revision_id' => $this->id,
                        'revision_title' => $this->title,
                        'revision_type' => $this->revision_type_text,
                        'completed_by' => auth()->user() ? auth()->user()->name : 'المنفذ',
                        'completed_at' => now()->format('Y-m-d H:i'),
                    ],
                    'related_id' => $this->id
                ]);

                // إرسال إشعار Firebase
                if ($firstReviewer->fcm_token) {
                    $firebaseService = app(\App\Services\FirebaseNotificationService::class);
                    $firebaseService->sendNotificationQueued(
                        $firstReviewer->fcm_token,
                        'تعديل جاهز للمراجعة',
                        "تم إكمال التعديل: {$this->title} - في انتظار مراجعتك",
                        "/revisions?revision_id={$this->id}"
                    );
                }

                \Illuminate\Support\Facades\Log::info('✅ Notification sent to first reviewer', [
                    'revision_id' => $this->id,
                    'reviewer_id' => $firstReviewer->id,
                    'reviewer_order' => 1,
                ]);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error sending notification to reviewer', [
                    'revision_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return true;
    }


    public function hasActiveSession(): bool
    {
        return $this->status === 'in_progress' && $this->current_session_start !== null;
    }

    public function getActualTimeFormattedAttribute(): string
    {
        if ($this->actual_minutes == 0) {
            return '0د';
        }

        $hours = intdiv($this->actual_minutes, 60);
        $minutes = $this->actual_minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . 'س';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . 'د';
        }

        return implode(' ', $parts);
    }


    public function getCurrentSessionMinutesAttribute(): int
    {
        if (!$this->hasActiveSession()) {
            return 0;
        }

        return now()->diffInMinutes($this->current_session_start);
    }


    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope للتعديل النشط للمستخدم
     */
    public function scopeActiveForUser($query, $userId)
    {
        return $query->where('created_by', $userId)
                     ->where('status', 'in_progress');
    }

    // =====================================================
    // دوال تتبع وقت المراجع (Reviewer Time Tracking)
    // =====================================================

    /**
     * بدء المراجعة
     */
    public function startReview(): bool
    {
        if ($this->review_status === 'in_progress') {
            return false;
        }

        $this->update([
            'review_status' => 'in_progress',
            'review_started_at' => $this->review_started_at ?? now(),
            'review_current_session_start' => now(),
            'review_paused_at' => null,
        ]);

        return true;
    }

    /**
     * إيقاف مؤقت للمراجعة
     */
    public function pauseReview(): bool
    {
        if ($this->review_status !== 'in_progress' || !$this->review_current_session_start) {
            return false;
        }

        // حساب الوقت المستغرق في هذه الجلسة
        $sessionMinutes = abs(now()->diffInMinutes($this->review_current_session_start));

        $this->update([
            'review_status' => 'paused',
            'review_paused_at' => now(),
            'review_actual_minutes' => max(0, $this->review_actual_minutes + $sessionMinutes),
            'review_current_session_start' => null,
        ]);

        return true;
    }

    /**
     * استئناف المراجعة
     */
    public function resumeReview(): bool
    {
        if ($this->review_status !== 'paused') {
            return false;
        }

        return $this->startReview();
    }

    /**
     * إكمال المراجعة
     */
    public function completeReview(): bool
    {
        if ($this->review_status === 'in_progress' && $this->review_current_session_start) {
            $sessionMinutes = abs(now()->diffInMinutes($this->review_current_session_start));
            $this->review_actual_minutes = max(0, $this->review_actual_minutes + $sessionMinutes);
        }

        $this->update([
            'review_status' => 'completed',
            'review_completed_at' => now(),
            'review_current_session_start' => null,
        ]);

        // إكمال المراجع الحالي وإشعار التالي
        $this->completeCurrentReviewerAndNotifyNext();

        return true;
    }

    /**
     * التحقق من وجود جلسة مراجعة نشطة
     */
    public function hasActiveReviewSession(): bool
    {
        return $this->review_status === 'in_progress' && $this->review_current_session_start !== null;
    }

    /**
     * الحصول على وقت المراجعة المنسق
     */
    public function getReviewTimeFormattedAttribute(): string
    {
        if ($this->review_actual_minutes == 0) {
            return '0د';
        }

        $hours = intdiv($this->review_actual_minutes, 60);
        $minutes = $this->review_actual_minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . 'س';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . 'د';
        }

        return implode(' ', $parts);
    }

    /**
     * الحصول على دقائق الجلسة الحالية للمراجعة
     */
    public function getCurrentReviewSessionMinutesAttribute(): int
    {
        if (!$this->hasActiveReviewSession()) {
            return 0;
        }

        return now()->diffInMinutes($this->review_current_session_start);
    }
}
