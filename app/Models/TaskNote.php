<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_type',
        'task_user_id',
        'template_task_user_id',
        'created_by',
        'content',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستخدم الذي أنشأ الملاحظة
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع المهمة العادية
     */
    public function taskUser(): BelongsTo
    {
        return $this->belongsTo(TaskUser::class, 'task_user_id');
    }

    /**
     * العلاقة مع مهمة القالب
     */
    public function templateTaskUser(): BelongsTo
    {
        return $this->belongsTo(TemplateTaskUser::class, 'template_task_user_id');
    }

    /**
     * الحصول على المهمة المرتبطة (عادية أو قالب)
     */
    public function getRelatedTask()
    {
        if ($this->task_type === 'regular' && $this->taskUser) {
            return $this->taskUser;
        }

        if ($this->task_type === 'template' && $this->templateTaskUser) {
            return $this->templateTaskUser;
        }

        return null;
    }

    /**
     * Scope للحصول على ملاحظات مستخدم محدد
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope للحصول على ملاحظات مهمة عادية
     */
    public function scopeForRegularTask($query, $taskUserId)
    {
        return $query->where('task_type', 'regular')
                    ->where('task_user_id', $taskUserId);
    }

    /**
     * Scope للحصول على ملاحظات مهمة قالب
     */
    public function scopeForTemplateTask($query, $templateTaskUserId)
    {
        return $query->where('task_type', 'template')
                    ->where('template_task_user_id', $templateTaskUserId);
    }
}
