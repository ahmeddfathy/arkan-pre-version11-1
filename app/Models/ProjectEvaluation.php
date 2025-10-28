<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'role_id',
        'evaluator_id',
        'review_month',
        'total_project_score',
        'criteria_scores',
        'notes',
    ];

    protected $casts = [
        'criteria_scores' => 'array',
        'total_project_score' => 'decimal:2',
    ];

    /**
     * العلاقة مع الموظف المُقيَّم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * العلاقة مع المشروع (مع المحذوفات)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /**
     * العلاقة مع الدور
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    /**
     * العلاقة مع المُقيِّم
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * التحقق من وجود تقييم مشروع للموظف
     */
    public static function hasProjectEvaluation($userId, $projectId, $roleId, $reviewMonth)
    {
        return self::where([
            'user_id' => $userId,
            'project_id' => $projectId,
            'role_id' => $roleId,
            'review_month' => $reviewMonth,
        ])->exists();
    }

    /**
     * الحصول على تقييم مشروع محدد
     */
    public static function getProjectEvaluation($userId, $projectId, $roleId, $reviewMonth)
    {
        return self::where([
            'user_id' => $userId,
            'project_id' => $projectId,
            'role_id' => $roleId,
            'review_month' => $reviewMonth,
        ])->first();
    }

    /**
     * الحصول على المشاريع المُقيَّمة للموظف
     */
    public static function getEvaluatedProjects($userId, $roleId, $reviewMonth)
    {
        return self::where([
            'user_id' => $userId,
            'role_id' => $roleId,
            'review_month' => $reviewMonth,
        ])->pluck('project_id')->toArray();
    }
}
