<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class KpiEvaluation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasSecureId;

    protected $table = 'kpi_evaluations';

    protected $fillable = [
        'user_id',
        'reviewer_id',
        'role_id',
        'evaluation_type', // monthly, bi_weekly
        'review_month',
        'total_score',
        'total_after_deductions',
        'total_bonus',
        'total_deductions',
        'total_development',
        'criteria_scores', // JSON
        'notes',
    ];

    protected $casts = [
        'evaluation_type' => 'string',
        'review_month' => 'string',
        'criteria_scores' => 'array',
        'total_score' => 'integer',
        'total_after_deductions' => 'integer',
        'total_bonus' => 'integer',
        'total_deductions' => 'integer',
        'total_development' => 'integer',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id', 'reviewer_id', 'role_id', 'evaluation_type', 'review_month',
                'total_score', 'total_after_deductions', 'total_bonus', 'total_deductions', 'total_development'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء تقييم أداء KPI جديد',
                'updated' => 'تم تحديث تقييم الأداء',
                'deleted' => 'تم حذف تقييم الأداء',
                default => $eventName
            });
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }


    public function getCriteriaWithScores()
    {
        $scoresData = $this->criteria_scores ?? [];

        if (empty($scoresData)) {
            return collect();
        }


        if (is_string($scoresData)) {
            $scoresData = json_decode($scoresData, true);
        }


        if (isset($scoresData[0]) && is_array($scoresData[0]) && isset($scoresData[0]['name'])) {
            return collect($scoresData)->map(function($criteriaData) {
                return (object) [
                    'id' => $criteriaData['id'] ?? null,
                    'criteria_name' => $criteriaData['name'] ?? 'بند محذوف',
                    'criteria_description' => $criteriaData['description'] ?? '',
                    'score' => (int) ($criteriaData['score'] ?? 0),
                    'max_points' => (int) ($criteriaData['max_points'] ?? 0),
                    'criteria_type' => $criteriaData['criteria_type'] ?? 'positive',
                    'category' => $criteriaData['category'] ?? null,
                    'sort_order' => $criteriaData['sort_order'] ?? 0,
                    'note' => $criteriaData['note'] ?? null, // إضافة الملاحظة
                    'is_historical' => true // بيانات تاريخية محفوظة
                ];
            });
        }

        // البيانات بالطريقة القديمة - جلب البنود من قاعدة البيانات
        $criteria = EvaluationCriteria::forRole($this->role_id)->active()->get();

        // إذا لم توجد بنود نشطة، جرب جلب جميع البنود (حتى المحذوفة)
        if ($criteria->isEmpty()) {
            $criteria = EvaluationCriteria::withTrashed()->forRole($this->role_id)->get();
        }

        // إذا لم توجد بنود على الإطلاق، أنشئ بنود وهمية من البيانات الموجودة
        if ($criteria->isEmpty() && !empty($scoresData)) {
            $fakeCriteria = collect();
            foreach ($scoresData as $criteriaId => $score) {
                $fakeCriteria->push((object) [
                    'id' => $criteriaId,
                    'criteria_name' => "بند محذوف (#{$criteriaId})",
                    'criteria_description' => 'هذا البند لم يعد موجوداً في النظام',
                    'score' => (int) $score,
                    'max_points' => (int) $score, // نفترض أن النقطة الممنوحة هي الحد الأقصى
                    'criteria_type' => 'positive',
                    'category' => 'محذوف',
                    'sort_order' => $criteriaId,
                    'note' => null,
                    'is_historical' => true
                ]);
            }
            return $fakeCriteria;
        }

        return $criteria->map(function($criterion) use ($scoresData) {
            $criterion->score = (int) ($scoresData[$criterion->id] ?? 0);
            $criterion->note = null; // البيانات القديمة لا تحتوي على ملاحظات
            $criterion->is_historical = false; // بيانات حية من قاعدة البيانات
            return $criterion;
        });
    }

    /**
     * حساب نسبة الأداء المئوية
     */
    public function getScorePercentageAttribute()
    {
        $maxPossibleScore = EvaluationCriteria::getTotalPointsForRole($this->role_id, 'positive');

        if ($maxPossibleScore == 0) {
            return 0;
        }

        return round(($this->total_score / $maxPossibleScore) * 100, 1);
    }

    /**
     * تحديد درجة الأداء
     */
    public function getPerformanceGradeAttribute()
    {
        $percentage = $this->score_percentage;

        if ($percentage >= 90) {
            return ['grade' => 'ممتاز', 'color' => 'success'];
        } elseif ($percentage >= 80) {
            return ['grade' => 'جيد جداً', 'color' => 'info'];
        } elseif ($percentage >= 70) {
            return ['grade' => 'جيد', 'color' => 'warning'];
        } elseif ($percentage >= 60) {
            return ['grade' => 'مقبول', 'color' => 'secondary'];
        } else {
            return ['grade' => 'ضعيف', 'color' => 'danger'];
        }
    }

    /**
     * فلترة حسب الشهر
     */
    public function scopeForMonth($query, $month)
    {
        // تحويل من Y-m إلى Y-m-d إذا كان مطلوب
        if (strlen($month) === 7) { // Y-m format
            $month = $month . '-01';
        }

        return $query->whereMonth('review_month', date('m', strtotime($month)))
                    ->whereYear('review_month', date('Y', strtotime($month)));
    }

    /**
     * فلترة حسب الدور
     */
    public function scopeForRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * فلترة حسب المُقيِّم
     */
    public function scopeByReviewer($query, $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    /**
     * فلترة حسب نوع التقييم
     */
    public function scopeByEvaluationType($query, $evaluationType)
    {
        return $query->where('evaluation_type', $evaluationType);
    }

    /**
     * الحصول على التقييمات الشهرية
     */
    public function scopeMonthly($query)
    {
        return $query->where('evaluation_type', 'monthly');
    }

    /**
     * الحصول على التقييمات نصف الشهرية
     */
    public function scopeBiWeekly($query)
    {
        return $query->where('evaluation_type', 'bi_weekly');
    }

    /**
     * حساب تاريخ بداية فترة التقييم
     * - التقييم الشهري: من يوم 26 الشهر السابق
     * - التقييم نصف شهري: من يوم 15 الشهر السابق
     *
     * @param string $reviewMonth بصيغة Y-m (مثال: 2025-11)
     * @param string $evaluationType نوع التقييم (monthly أو bi_weekly)
     * @return string تاريخ البداية بصيغة Y-m-d
     */
    public static function getEvaluationStartDate($reviewMonth, $evaluationType = 'monthly')
    {
        // التأكد من أن reviewMonth بصيغة Y-m
        if (strlen($reviewMonth) === 10) {
            $reviewMonth = substr($reviewMonth, 0, 7); // تحويل من Y-m-d إلى Y-m
        }

        $date = \Carbon\Carbon::createFromFormat('Y-m', $reviewMonth);

        if ($evaluationType === 'bi_weekly') {
            // نصف شهري: من يوم 15 الشهر السابق
            return $date->copy()->subMonth()->day(15)->format('Y-m-d');
        } else {
            // شهري: من يوم 26 الشهر السابق
            return $date->copy()->subMonth()->day(26)->format('Y-m-d');
        }
    }

    /**
     * حساب تاريخ نهاية فترة التقييم
     * - التقييم الشهري: حتى يوم 25 من الشهر المحدد
     * - التقييم نصف شهري: حتى يوم 14 من الشهر المحدد
     *
     * @param string $reviewMonth بصيغة Y-m (مثال: 2025-11)
     * @param string $evaluationType نوع التقييم (monthly أو bi_weekly)
     * @return string تاريخ النهاية بصيغة Y-m-d
     */
    public static function getEvaluationEndDate($reviewMonth, $evaluationType = 'monthly')
    {
        // التأكد من أن reviewMonth بصيغة Y-m
        if (strlen($reviewMonth) === 10) {
            $reviewMonth = substr($reviewMonth, 0, 7); // تحويل من Y-m-d إلى Y-m
        }

        $date = \Carbon\Carbon::createFromFormat('Y-m', $reviewMonth);

        if ($evaluationType === 'bi_weekly') {
            // نصف شهري: حتى يوم 14 من الشهر المحدد
            return $date->day(14)->format('Y-m-d');
        } else {
            // شهري: حتى يوم 25 من الشهر المحدد
            return $date->day(25)->format('Y-m-d');
        }
    }

    /**
     * الحصول على فترة التقييم الكاملة (تاريخ البداية والنهاية)
     *
     * @param string $reviewMonth بصيغة Y-m
     * @param string $evaluationType نوع التقييم
     * @return array ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     */
    public static function getEvaluationPeriod($reviewMonth, $evaluationType = 'monthly')
    {
        return [
            'start' => self::getEvaluationStartDate($reviewMonth, $evaluationType),
            'end' => self::getEvaluationEndDate($reviewMonth, $evaluationType),
        ];
    }

    /**
     * الحصول على فترة التقييم للسجل الحالي
     *
     * @return array ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     */
    public function getEvaluationPeriodAttribute()
    {
        $reviewMonth = $this->review_month;
        if (strlen($reviewMonth) === 10) {
            $reviewMonth = substr($reviewMonth, 0, 7);
        }

        return self::getEvaluationPeriod($reviewMonth, $this->evaluation_type);
    }
}
