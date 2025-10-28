<?php

namespace App\Services;

use App\Models\Season;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUser;

use App\Models\User;
use App\Models\TemplateTaskUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SeasonStatisticsService
{
    public function getUserStatistics($userId, $seasonId = null)
    {
        if (!$seasonId) {
            $season = Season::getCurrentSeason();
            if (!$season) {
                return $this->getEmptyUserStats();
            }
            $seasonId = $season->id;
        } else {
            $season = Season::find($seasonId);
            if (!$season) {
                return $this->getEmptyUserStats();
            }
        }

        $projectsQuery = Project::where('season_id', $seasonId)
            ->whereHas('participants', function($query) use ($userId) {
                $query->where('users.id', $userId);
            });

        $projectsCount = $projectsQuery->count();
        $projectsCompleted = (clone $projectsQuery)->where('status', 'مكتمل')->count();
        $projectsInProgress = (clone $projectsQuery)->where('status', 'جاري التنفيذ')->count();
        $projectsNew = (clone $projectsQuery)->where('status', 'جديد')->count();
        $projectsCancelled = (clone $projectsQuery)->where('status', 'ملغي')->count();

        $tasksQuery = TaskUser::where('user_id', $userId)
            ->where(function($query) use ($seasonId) {
                $query->where('season_id', $seasonId)
                    ->orWhereHas('task.project', function($q) use ($seasonId) {
                        $q->where('season_id', $seasonId);
                    });
            });

        $tasksCount = $tasksQuery->count();
        $tasksCompleted = (clone $tasksQuery)->where('status', 'completed')->count();
        $tasksInProgress = (clone $tasksQuery)->where('status', 'in_progress')->count();
        $tasksNew = (clone $tasksQuery)->where('status', 'new')->count();
        $tasksPaused = (clone $tasksQuery)->where('status', 'paused')->count();

        $templateTasksQuery = TemplateTaskUser::where('user_id', $userId)
            ->where(function($query) use ($seasonId) {
                $query->where('season_id', $seasonId)
                    ->orWhereHas('project', function($q) use ($seasonId) {
                        $q->where('season_id', $seasonId);
                    });
            });

        $templateTasksCount = $templateTasksQuery->count();
        $templateTasksCompleted = (clone $templateTasksQuery)->where('status', 'completed')->count();

        $timeSpent = $tasksQuery->sum(DB::raw('(actual_hours * 60) + actual_minutes'));

        // إضافة وقت مهام القوالب
        $templateTimeSpent = $templateTasksQuery->sum('actual_minutes');

        $totalTimeSpent = $timeSpent + $templateTimeSpent;

        $hours = intdiv($totalTimeSpent, 60);
        $minutes = $totalTimeSpent % 60;

        return [
            'user_id' => $userId,
            'user' => User::find($userId),
            'season_id' => $seasonId,
            'season' => $season,
            'projects' => [
                'total' => $projectsCount,
                'completed' => $projectsCompleted,
                'in_progress' => $projectsInProgress,
                'new' => $projectsNew,
                'cancelled' => $projectsCancelled,
                'completion_percentage' => $projectsCount > 0 ? round(($projectsCompleted / $projectsCount) * 100) : 0,
            ],
            'tasks' => [
                'total' => $tasksCount,
                'completed' => $tasksCompleted,
                'in_progress' => $tasksInProgress,
                'new' => $tasksNew,
                'paused' => $tasksPaused,
                'completion_percentage' => $tasksCount > 0 ? round(($tasksCompleted / $tasksCount) * 100) : 0,
            ],
            'template_tasks' => [
                'total' => $templateTasksCount,
                'completed' => $templateTasksCompleted,
                'completion_percentage' => $templateTasksCount > 0 ? round(($templateTasksCompleted / $templateTasksCount) * 100) : 0,
            ],
            'time_spent' => [
                'total_minutes' => $totalTimeSpent,
                'hours' => $hours,
                'minutes' => $minutes,
                'formatted' => $hours . 'h ' . $minutes . 'm',
            ]
        ];
    }

    public function getCompanyStatistics($seasonId = null)
    {
        if (!$seasonId) {
            $season = Season::getCurrentSeason();
            if (!$season) {
                return $this->getEmptyCompanyStats();
            }
            $seasonId = $season->id;
        } else {
            $season = Season::find($seasonId);
            if (!$season) {
                return $this->getEmptyCompanyStats();
            }
        }

        $projectsQuery = Project::where('season_id', $seasonId);

        $projectsCount = $projectsQuery->count();
        $projectsCompleted = (clone $projectsQuery)->where('status', 'مكتمل')->count();
        $projectsInProgress = (clone $projectsQuery)->where('status', 'جاري التنفيذ')->count();
        $projectsNew = (clone $projectsQuery)->where('status', 'جديد')->count();
        $projectsCancelled = (clone $projectsQuery)->where('status', 'ملغي')->count();

        $tasksQuery = TaskUser::whereHas('task.project', function($q) use ($seasonId) {
            $q->where('season_id', $seasonId);
        })->orWhere('season_id', $seasonId);

        $tasksCount = $tasksQuery->count();
        $tasksCompleted = (clone $tasksQuery)->where('status', 'completed')->count();
        $tasksInProgress = (clone $tasksQuery)->where('status', 'in_progress')->count();
        $tasksNew = (clone $tasksQuery)->where('status', 'new')->count();
        $tasksPaused = (clone $tasksQuery)->where('status', 'paused')->count();

        $templateTasksQuery = TemplateTaskUser::where('season_id', $seasonId)
            ->orWhereHas('project', function($q) use ($seasonId) {
                $q->where('season_id', $seasonId);
            });

        $templateTasksCount = $templateTasksQuery->count();
        $templateTasksCompleted = (clone $templateTasksQuery)->where('status', 'completed')->count();

        $timeSpent = $tasksQuery->sum(DB::raw('(actual_hours * 60) + actual_minutes'));

        // إضافة وقت مهام القوالب
        $templateTimeSpent = $templateTasksQuery->sum('actual_minutes');

        $totalTimeSpent = $timeSpent + $templateTimeSpent;

        $hours = intdiv($totalTimeSpent, 60);
        $minutes = $totalTimeSpent % 60;

        $topUsers = TaskUser::whereHas('task.project', function($q) use ($seasonId) {
            $q->where('season_id', $seasonId);
        })->orWhere('season_id', $seasonId)
            ->where('status', 'completed')
            ->select('user_id', DB::raw('count(*) as completed_tasks'))
            ->groupBy('user_id')
            ->orderBy('completed_tasks', 'desc')
            ->limit(5)
            ->with('user')
            ->get();

        $usersByTime = TaskUser::whereHas('task.project', function($q) use ($seasonId) {
            $q->where('season_id', $seasonId);
        })->orWhere('season_id', $seasonId)
            ->select('user_id', DB::raw('sum((actual_hours * 60) + actual_minutes) as total_time'))
            ->groupBy('user_id')
            ->orderBy('total_time', 'desc')
            ->limit(5)
            ->with('user')
            ->get();

        return [
            'season_id' => $seasonId,
            'season' => $season,
            'projects' => [
                'total' => $projectsCount,
                'completed' => $projectsCompleted,
                'in_progress' => $projectsInProgress,
                'new' => $projectsNew,
                'cancelled' => $projectsCancelled,
                'completion_percentage' => $projectsCount > 0 ? round(($projectsCompleted / $projectsCount) * 100) : 0,
            ],
            'tasks' => [
                'total' => $tasksCount,
                'completed' => $tasksCompleted,
                'in_progress' => $tasksInProgress,
                'new' => $tasksNew,
                'paused' => $tasksPaused,
                'completion_percentage' => $tasksCount > 0 ? round(($tasksCompleted / $tasksCount) * 100) : 0,
            ],
            'template_tasks' => [
                'total' => $templateTasksCount,
                'completed' => $templateTasksCompleted,
                'completion_percentage' => $templateTasksCount > 0 ? round(($templateTasksCompleted / $templateTasksCount) * 100) : 0,
            ],
            'time_spent' => [
                'total_minutes' => $totalTimeSpent,
                'hours' => $hours,
                'minutes' => $minutes,
                'formatted' => $hours . 'h ' . $minutes . 'm',
            ],
            'top_users' => $topUsers,
            'users_by_time' => $usersByTime,
        ];
    }

    public function getAllUsersStatistics($seasonId = null)
    {
        if (!$seasonId) {
            $season = Season::getCurrentSeason();
            if (!$season) {
                return [];
            }
            $seasonId = $season->id;
        } else {
            $season = Season::find($seasonId);
            if (!$season) {
                return [];
            }
        }

        $userIds = TaskUser::whereHas('task.project', function($q) use ($seasonId) {
            $q->where('season_id', $seasonId);
        })->orWhere('season_id', $seasonId)
            ->select('user_id')
            ->groupBy('user_id')
            ->pluck('user_id')
            ->toArray();

        $templateUserIds = TemplateTaskUser::where('season_id', $seasonId)
            ->orWhereHas('project', function($q) use ($seasonId) {
                $q->where('season_id', $seasonId);
            })
            ->select('user_id')
            ->groupBy('user_id')
            ->pluck('user_id')
            ->toArray();

        $userIds = array_unique(array_merge($userIds, $templateUserIds));

        $usersStats = [];
        foreach ($userIds as $userId) {
            $usersStats[] = $this->getUserStatistics($userId, $seasonId);
        }

        usort($usersStats, function($a, $b) {
            return $b['tasks']['completed'] - $a['tasks']['completed'];
        });

        return [
            'season' => $season,
            'users' => $usersStats
        ];
    }

    private function getEmptyUserStats()
    {
        return [
            'projects' => [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'new' => 0,
                'cancelled' => 0,
                'completion_percentage' => 0,
            ],
            'tasks' => [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'new' => 0,
                'paused' => 0,
                'completion_percentage' => 0,
            ],
            'template_tasks' => [
                'total' => 0,
                'completed' => 0,
                'completion_percentage' => 0,
            ],
            'time_spent' => [
                'total_minutes' => 0,
                'hours' => 0,
                'minutes' => 0,
                'formatted' => '0h 0m',
            ]
        ];
    }

    private function getEmptyCompanyStats()
    {
        return [
            'projects' => [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'new' => 0,
                'cancelled' => 0,
                'completion_percentage' => 0,
            ],
            'tasks' => [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'new' => 0,
                'paused' => 0,
                'completion_percentage' => 0,
            ],
            'template_tasks' => [
                'total' => 0,
                'completed' => 0,
                'completion_percentage' => 0,
            ],
            'time_spent' => [
                'total_minutes' => 0,
                'hours' => 0,
                'minutes' => 0,
                'formatted' => '0h 0m',
            ],
            'top_users' => [],
            'users_by_time' => [],
        ];
    }
}
