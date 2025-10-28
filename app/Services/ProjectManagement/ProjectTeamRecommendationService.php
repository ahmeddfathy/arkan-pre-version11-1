<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\CompanyService;
use Illuminate\Support\Facades\DB;

class ProjectTeamRecommendationService
{
    /**
     * الحصول على اقتراح ذكي للفريق المناسب للمشروع
     */
    public function getSmartTeamSuggestion(Project $project, $serviceId)
    {
        $service = CompanyService::findOrFail($serviceId);

        // جلب الفرق المتاحة للقسم
        $teams = DB::table('teams')
            ->join('users as owners', 'teams.user_id', '=', 'owners.id')
            ->leftJoin('team_user', 'teams.id', '=', 'team_user.team_id')
            ->leftJoin('users as members', 'team_user.user_id', '=', 'members.id')
            ->where(function($query) use ($service) {
                $query->where('owners.department', $service->department)
                      ->orWhere('members.department', $service->department);
            })
            ->where('teams.personal_team', false)
            ->select('teams.id', 'teams.name', 'teams.user_id', 'owners.name as owner_name')
            ->distinct()
            ->get();

        $teamAnalysis = [];

        foreach ($teams as $team) {
            // جلب أعضاء الفريق
            $members = DB::table('users')
                ->leftJoin('team_user', function($join) use ($team) {
                    $join->on('users.id', '=', 'team_user.user_id')
                         ->where('team_user.team_id', '=', $team->id);
                })
                ->where(function($query) use ($team) {
                    $query->where('users.id', $team->user_id)
                          ->orWhereNotNull('team_user.user_id');
                })
                ->select('users.id', 'users.name', 'users.email')
                ->get();

            $teamWorkload = $this->calculateTeamWorkload($members);

            $teamAnalysis[] = [
                'team' => $team,
                'members' => $members,
                'workload' => $teamWorkload,
                'score' => $this->calculateTeamScore($teamWorkload),
                'recommendation_reason' => $this->generateRecommendationReason($teamWorkload)
            ];
        }

        // ترتيب الفرق حسب النقاط (أقل حمل = أفضل)
        usort($teamAnalysis, function($a, $b) {
            return $a['score'] <=> $b['score'];
        });

        return [
            'service' => $service,
            'teams_analysis' => $teamAnalysis,
            'best_team' => $teamAnalysis[0] ?? null
        ];
    }

    /**
     * حساب حمل العمل للفريق
     */
    public function calculateTeamWorkload($members)
    {
        $totalActiveProjects = 0;
        $totalEndingSoonProjects = 0;
        $totalOverdueProjects = 0;
        $memberDetails = [];

        foreach ($members as $member) {
            // المشاريع النشطة للعضو
            $activeProjects = DB::table('project_service_user')
                ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                ->where('project_service_user.user_id', $member->id)
                ->whereIn('projects.status', ['جديد', 'جاري التنفيذ'])
                ->count();

            // المشاريع التي ستنتهي خلال أسبوع
            $endingSoonProjects = DB::table('project_service_user')
                ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                ->where('project_service_user.user_id', $member->id)
                ->whereIn('projects.status', ['جديد', 'جاري التنفيذ'])
                ->where('projects.client_agreed_delivery_date', '<=', now()->addWeek())
                ->where('projects.client_agreed_delivery_date', '>=', now())
                ->count();

            // المشاريع المتأخرة
            $overdueProjects = DB::table('project_service_user')
                ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                ->where('project_service_user.user_id', $member->id)
                ->whereIn('projects.status', ['جديد', 'جاري التنفيذ'])
                ->where('projects.client_agreed_delivery_date', '<', now())
                ->count();

            // المهام النشطة
            $activeTasks = DB::table('template_task_user')
                ->where('user_id', $member->id)
                ->whereIn('status', ['new', 'in_progress'])
                ->count();

            $activeTasks += DB::table('task_users')
                ->where('user_id', $member->id)
                ->whereIn('status', ['new', 'in_progress'])
                ->count();

            $memberDetails[] = [
                'user' => $member,
                'active_projects' => $activeProjects,
                'ending_soon_projects' => $endingSoonProjects,
                'overdue_projects' => $overdueProjects,
                'active_tasks' => $activeTasks,
                'effective_workload' => max(0, $activeProjects - $endingSoonProjects) // العبء الفعلي
            ];

            $totalActiveProjects += $activeProjects;
            $totalEndingSoonProjects += $endingSoonProjects;
            $totalOverdueProjects += $overdueProjects;
        }

        return [
            'total_active_projects' => $totalActiveProjects,
            'total_ending_soon_projects' => $totalEndingSoonProjects,
            'total_overdue_projects' => $totalOverdueProjects,
            'effective_workload' => max(0, $totalActiveProjects - $totalEndingSoonProjects),
            'team_size' => count($members),
            'average_workload' => count($members) > 0 ? round($totalActiveProjects / count($members), 2) : 0,
            'member_details' => $memberDetails
        ];
    }

    /**
     * حساب نقاط الفريق (أقل = أفضل)
     */
    public function calculateTeamScore($workload)
    {
        $score = 0;

        // العبء الفعلي (المشاريع النشطة - التي ستنتهي قريباً)
        $score += $workload['effective_workload'] * 10;

        // المشاريع المتأخرة (عامل سلبي كبير)
        $score += $workload['total_overdue_projects'] * 25;

        // متوسط العبء لكل عضو
        $score += $workload['average_workload'] * 5;

        // مكافأة للفرق الأكبر حجماً (توزيع أفضل للعبء)
        $score -= $workload['team_size'] * 2;

        return $score;
    }

    /**
     * توليد سبب التوصية
     */
    public function generateRecommendationReason($workload)
    {
        $reasons = [];

        if ($workload['effective_workload'] <= 5) {
            $reasons[] = "عبء عمل منخفض ({$workload['effective_workload']} مشاريع فعلية)";
        } elseif ($workload['effective_workload'] <= 10) {
            $reasons[] = "عبء عمل متوسط ({$workload['effective_workload']} مشاريع فعلية)";
        } else {
            $reasons[] = "عبء عمل مرتفع ({$workload['effective_workload']} مشاريع فعلية)";
        }

        if ($workload['total_ending_soon_projects'] > 0) {
            $reasons[] = "{$workload['total_ending_soon_projects']} مشروع سينتهي قريباً";
        }

        if ($workload['total_overdue_projects'] > 0) {
            $reasons[] = "⚠️ {$workload['total_overdue_projects']} مشروع متأخر";
        }

        if ($workload['team_size'] >= 4) {
            $reasons[] = "فريق كبير ({$workload['team_size']} أعضاء)";
        }

        $reasons[] = "متوسط {$workload['average_workload']} مشروع/عضو";

        return implode(" • ", $reasons);
    }
}
