<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RevisionDeadline extends Model
{
    use HasFactory;

    protected $fillable = [
        'revision_id',
        'deadline_type',
        'user_id',
        'deadline_date',
        'reviewer_order',
        'status',
        'completed_at',
        'notes',
        'assigned_by',
        'original_deadline',
        'extension_count',
        'last_updated_by',
    ];

    protected $casts = [
        'deadline_date' => 'datetime',
        'completed_at' => 'datetime',
        'original_deadline' => 'datetime',
        'extension_count' => 'integer',
    ];

    /**
     * العلاقة مع التعديل
     */
    public function revision()
    {
        return $this->belongsTo(TaskRevision::class, 'revision_id');
    }

    /**
     * العلاقة مع المستخدم المسند له
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * العلاقة مع من قام بالتعيين
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * العلاقة مع من قام بآخر تحديث
     */
    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Scopes
     */

    /**
     * فلترة الديدلاينات حسب النوع (executor/reviewer)
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('deadline_type', $type);
    }

    /**
     * الديدلاينات المنتظرة (pending)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * الديدلاينات التي تم الالتزام بها
     */
    public function scopeMet($query)
    {
        return $query->where('status', 'met');
    }

    /**
     * الديدلاينات الفائتة
     */
    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }

    /**
     * الديدلاينات القادمة (خلال فترة معينة)
     */
    public function scopeUpcoming($query, int $days = 3)
    {
        return $query->where('status', 'pending')
                     ->where('deadline_date', '<=', now()->addDays($days))
                     ->where('deadline_date', '>=', now());
    }

    /**
     * الديدلاينات المتأخرة (فات موعدها)
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                     ->where('deadline_date', '<', now());
    }

    /**
     * فلترة حسب المستخدم
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Methods
     */

    /**
     * التحقق من إذا كان الديدلاين متأخر
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->deadline_date < now();
    }

    /**
     * التحقق من إذا كان الديدلاين قريب (خلال 24 ساعة)
     */
    public function isUpcoming(): bool
    {
        return $this->status === 'pending' &&
               $this->deadline_date <= now()->addDay() &&
               $this->deadline_date >= now();
    }

    /**
     * حساب عدد الساعات المتبقية
     */
    public function getHoursRemainingAttribute(): ?int
    {
        if ($this->status !== 'pending') {
            return null;
        }

        $diff = now()->diffInHours($this->deadline_date, false);
        return $diff > 0 ? $diff : 0;
    }

    /**
     * حساب عدد الأيام المتبقية
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->status !== 'pending') {
            return null;
        }

        $diff = now()->diffInDays($this->deadline_date, false);
        return $diff > 0 ? $diff : 0;
    }

    /**
     * الحصول على لون الديدلاين حسب حالته
     */
    public function getColorAttribute(): string
    {
        if ($this->status === 'met') {
            return 'success';
        }

        if ($this->status === 'missed') {
            return 'danger';
        }

        // pending
        if ($this->isOverdue()) {
            return 'danger';
        }

        if ($this->isUpcoming()) {
            return 'warning';
        }

        return 'info';
    }

    /**
     * الحصول على النص الوصفي للديدلاين
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => $this->isOverdue() ? 'متأخر' : 'قيد الانتظار',
            'met' => 'تم الالتزام',
            'missed' => 'فات الموعد',
            default => 'غير محدد'
        };
    }

    /**
     * الحصول على نص نوع الديدلاين
     */
    public function getTypeTextAttribute(): string
    {
        return match($this->deadline_type) {
            'executor' => 'ديدلاين المنفذ',
            'reviewer' => 'ديدلاين المراجع',
            default => 'غير محدد'
        };
    }

    /**
     * تمديد الديدلاين
     */
    public function extend(Carbon $newDeadline, ?int $updatedBy = null): bool
    {
        // حفظ الديدلاين الأصلي إذا لم يكن محفوظاً
        if (!$this->original_deadline) {
            $this->original_deadline = $this->deadline_date;
        }

        $this->deadline_date = $newDeadline;
        $this->extension_count += 1;
        $this->last_updated_by = $updatedBy ?? auth()->id();

        return $this->save();
    }

    /**
     * إكمال الديدلاين (تم الإنجاز)
     */
    public function complete(?Carbon $completedAt = null): bool
    {
        $completedAt = $completedAt ?? now();

        $this->completed_at = $completedAt;

        // تحديد إذا كان الديدلاين تم الالتزام به أو فات
        if ($completedAt <= $this->deadline_date) {
            $this->status = 'met'; // تم الالتزام
        } else {
            $this->status = 'missed'; // فات الموعد
        }

        return $this->save();
    }

    /**
     * إعادة فتح الديدلاين (العودة لحالة pending)
     */
    public function reopen(): bool
    {
        $this->status = 'pending';
        $this->completed_at = null;

        return $this->save();
    }

    /**
     * الحصول على الفرق بين وقت الإنجاز والديدلاين (بالساعات)
     */
    public function getCompletionDifferenceInHoursAttribute(): ?int
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->deadline_date->diffInHours($this->completed_at, false);
    }

    /**
     * التحقق من وجود تمديدات
     */
    public function hasExtensions(): bool
    {
        return $this->extension_count > 0;
    }
}

