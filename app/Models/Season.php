<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Traits\HasSecureId;

class Season extends Model
{
    use HasFactory, SoftDeletes, HasSecureId;

    protected $fillable = [
        'name',
        'description',
        'image',
        'banner_image',
        'color_theme',
        'start_date',
        'end_date',
        'is_active',
        'rewards',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'rewards' => 'array',
    ];

    /**
     * Get the projects associated with this season
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get tasks associated with this season through projects
     */
    public function tasks()
    {
        return Task::whereHas('project', function($query) {
            $query->where('season_id', $this->id);
        });
    }

    public function taskUsers()
    {
        return $this->hasMany(TaskUser::class);
    }


    /**
     * علاقة الموسم بالشارات التي حصل عليها المستخدمون في هذا الموسم
     */
    public function userBadges()
    {
        return $this->hasMany(UserBadge::class);
    }

    /**
     * علاقة الموسم بنقاط المستخدمين في هذا الموسم
     */
    public function userSeasonPoints()
    {
        return $this->hasMany(UserSeasonPoint::class);
    }

    /**
     * الحصول على الموسم النشط الحالي
     */
    public static function getCurrentActiveSeason()
    {
        return self::where('is_active', true)
                   ->where('start_date', '<=', now())
                   ->where('end_date', '>=', now())
                   ->first();
    }

    /**
     * تطبيق قواعد الهبوط على جميع المستخدمين عند انتهاء الموسم الحالي وبدء موسم جديد
     * @param Season $newSeason الموسم الجديد
     * @return array إحصائيات عن عدد المستخدمين الذين تم تخفيض رتبتهم
     */
    public function applyDemotionRules(Season $newSeason)
    {
        $stats = [
            'total_users' => 0,
            'demoted_users' => 0,
            'badges' => []
        ];

        // الحصول على جميع المستخدمين الذين لديهم نقاط في الموسم الحالي
        $userSeasonPoints = $this->userSeasonPoints()
                                 ->with(['user', 'currentBadge', 'highestBadge'])
                                 ->get();

        $stats['total_users'] = $userSeasonPoints->count();

        foreach ($userSeasonPoints as $userPoint) {
            // تخطي المستخدمين الذين ليس لديهم شارة حالية
            if (!$userPoint->current_badge_id) {
                continue;
            }

            $currentBadge = $userPoint->currentBadge;
            $userId = $userPoint->user_id;
            $userName = $userPoint->user->name;

            // البحث عن قاعدة هبوط لهذه الشارة
            $lowerBadge = $currentBadge->getNextLowerBadge();

            if ($lowerBadge) {
                // الحصول على النسبة المئوية للنقاط التي سيتم الاحتفاظ بها
                $rule = $currentBadge->getDemotionRuleTo($lowerBadge);
                $pointsPercentage = $rule ? $rule->points_percentage_retained : 50; // افتراضي 50%

                // حساب النقاط الجديدة
                $newPoints = floor($userPoint->total_points * ($pointsPercentage / 100));

                // إنشاء سجل نقاط جديد للموسم الجديد
                $newUserPoint = UserSeasonPoint::create([
                    'user_id' => $userId,
                    'season_id' => $newSeason->id,
                    'total_points' => $newPoints,
                    'current_badge_id' => $lowerBadge->id,
                    'highest_badge_id' => $lowerBadge->id,
                    'previous_season_badge_id' => $currentBadge->id,
                    'tasks_completed' => 0,
                    'projects_completed' => 0,
                    'total_minutes_worked' => 0,
                ]);

                // إضافة سجل للشارة الجديدة
                UserBadge::create([
                    'user_id' => $userId,
                    'badge_id' => $lowerBadge->id,
                    'season_id' => $newSeason->id,
                    'points_earned' => $newPoints,
                    'earned_at' => now(),
                    'is_active' => true,
                    'notes' => 'تم تخفيض الرتبة من ' . $currentBadge->name . ' إلى ' . $lowerBadge->name . ' مع بداية الموسم الجديد'
                ]);

                // إضافة إحصائيات
                $stats['demoted_users']++;

                if (!isset($stats['badges'][$currentBadge->name])) {
                    $stats['badges'][$currentBadge->name] = [
                        'from' => $currentBadge->name,
                        'to' => $lowerBadge->name,
                        'count' => 1,
                        'users' => [$userName]
                    ];
                } else {
                    $stats['badges'][$currentBadge->name]['count']++;
                    $stats['badges'][$currentBadge->name]['users'][] = $userName;
                }
            } else {
                // إذا لم تكن هناك شارة أقل (الشارة الأدنى)، حافظ على نفس الشارة ولكن صفر النقاط
                $newUserPoint = UserSeasonPoint::create([
                    'user_id' => $userId,
                    'season_id' => $newSeason->id,
                    'total_points' => 0,
                    'current_badge_id' => $currentBadge->id,
                    'highest_badge_id' => $currentBadge->id,
                    'previous_season_badge_id' => $currentBadge->id,
                    'tasks_completed' => 0,
                    'projects_completed' => 0,
                    'total_minutes_worked' => 0,
                ]);

                // إضافة سجل للشارة
                UserBadge::create([
                    'user_id' => $userId,
                    'badge_id' => $currentBadge->id,
                    'season_id' => $newSeason->id,
                    'points_earned' => 0,
                    'earned_at' => now(),
                    'is_active' => true,
                    'notes' => 'تم الاحتفاظ بنفس الشارة ' . $currentBadge->name . ' مع بداية الموسم الجديد'
                ]);
            }
        }

        return $stats;
    }

    public function templateTaskUsers()
    {
        return $this->hasMany(TemplateTaskUser::class);
    }

    /**
     * Get statistics for a specific user in this season
     *
     * @param int $userId
     * @return array
     */
    public function getUserStatistics($userId)
    {
        // Proyectos en los que el usuario participó en esta temporada
        $projects = $this->projects()
            ->whereHas('participants', function($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->get();

        // Total de proyectos para este usuario
        $totalProjects = $projects->count();

        // Proyectos completados
        $completedProjects = $projects->where('status', 'مكتمل')->count();

        // Obtener las tareas del usuario en esta temporada directamente
        $taskUsers = $this->taskUsers()->where('user_id', $userId)->get();

        // Total de tareas asignadas
        $totalTasks = $taskUsers->count();

        // Tareas completadas
        $completedTasks = $taskUsers->where('status', 'completed')->count();

        // Tiempo total dedicado
        $totalMinutes = $taskUsers->sum(function($taskUser) {
            return ($taskUser->actual_hours * 60) + $taskUser->actual_minutes;
        });

        // استخدام إجمالي الدقائق المحسوبة من الـ task users
        $totalTimeSpent = $totalMinutes;

        // Convertir minutos a formato legible
        $hours = intdiv($totalTimeSpent, 60);
        $minutes = $totalTimeSpent % 60;
        $timeSpentFormatted = ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : '');
        if (empty($timeSpentFormatted)) $timeSpentFormatted = '0m';

        // Plantillas de tareas completadas
        $templateTasks = $this->templateTaskUsers()
            ->where('user_id', $userId)
            ->get();

        $totalTemplateTasks = $templateTasks->count();
        $completedTemplateTasks = $templateTasks->where('status', 'completed')->count();

        return [
            'total_projects' => $totalProjects,
            'completed_projects' => $completedProjects,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'total_template_tasks' => $totalTemplateTasks,
            'completed_template_tasks' => $completedTemplateTasks,
            'completion_percentage' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
            'total_time_spent_minutes' => $totalTimeSpent,
            'total_time_spent_formatted' => $timeSpentFormatted,
        ];
    }

    /**
     * تحديد ما إذا كان السيزون نشط حالياً
     */
    public function getIsCurrentAttribute()
    {
        $now = Carbon::now();
        return $this->is_active && $now->between($this->start_date, $this->end_date);
    }

    /**
     * تحديد ما إذا كان السيزون سيبدأ في المستقبل
     */
    public function getIsUpcomingAttribute()
    {
        return $this->is_active && Carbon::now()->lt($this->start_date);
    }

    /**
     * تحديد ما إذا كان السيزون قد انتهى
     */
    public function getIsExpiredAttribute()
    {
        return Carbon::now()->gt($this->end_date);
    }

    /**
     * الحصول على النسبة المئوية للتقدم في السيزون
     */
    public function getProgressPercentageAttribute()
    {
        $now = Carbon::now();

        if ($now->lt($this->start_date)) {
            return 0;
        }

        if ($now->gt($this->end_date)) {
            return 100;
        }

        $totalDuration = $this->start_date->diffInSeconds($this->end_date);
        $elapsedDuration = $this->start_date->diffInSeconds($now);

        return min(100, round(($elapsedDuration / $totalDuration) * 100));
    }

    /**
     * الحصول على الوقت المتبقي للسيزون كنص مقروء
     */
    public function getRemainingTimeAttribute()
    {
        $now = Carbon::now();

        if ($now->gt($this->end_date)) {
            return 'انتهى';
        }

        if ($now->lt($this->start_date)) {
            return 'يبدأ بعد ' . $now->diffForHumans($this->start_date, ['parts' => 2]);
        }

        return 'ينتهي بعد ' . $now->diffForHumans($this->end_date, ['parts' => 2]);
    }

    /**
     * الحصول على السيزون الحالي النشط
     */
    public static function getCurrentSeason()
    {
        $now = Carbon::now();

        return self::where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();
    }

    /**
     * الحصول على السيزون القادم
     */
    public static function getUpcomingSeason()
    {
        $now = Carbon::now();

        return self::where('is_active', true)
            ->where('start_date', '>', $now)
            ->orderBy('start_date', 'asc')
            ->first();
    }

}
