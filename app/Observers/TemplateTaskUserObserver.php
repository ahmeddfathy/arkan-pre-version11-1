<?php

namespace App\Observers;

use App\Models\TemplateTaskUser;
use App\Services\BadgeService;
use App\Services\Tasks\TaskNotificationService;
use App\Services\TimeTracking\TimeTrackingService;
use App\Models\Season;
use App\Models\UserSeasonPoint;
use Illuminate\Support\Facades\DB;

class TemplateTaskUserObserver
{
    protected $badgeService;
    protected $taskNotificationService;
    protected $timeTrackingService;

    public function __construct(
        BadgeService $badgeService,
        TaskNotificationService $taskNotificationService,
        TimeTrackingService $timeTrackingService
    ) {
        $this->badgeService = $badgeService;
        $this->taskNotificationService = $taskNotificationService;
        $this->timeTrackingService = $timeTrackingService;
    }

    public function updated(TemplateTaskUser $templateTaskUser)
    {
        if ($templateTaskUser->wasChanged('started_at')) {
            $activeLogs = $templateTaskUser->timeLogs()->whereNull('stopped_at')->get();
            foreach ($activeLogs as $log) {
                // No logging or comments
            }
        }

        if ($templateTaskUser->wasChanged('status')) {
            $oldStatus = $templateTaskUser->getOriginal('status');
            $newStatus = $templateTaskUser->status;

            $this->handleTimeTracking($templateTaskUser, $oldStatus, $newStatus);

            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $this->addPointsForCompletedTemplateTask($templateTaskUser);
            } elseif ($oldStatus === 'completed' && $newStatus !== 'completed') {
                $this->removePointsForUncompletedTemplateTask($templateTaskUser);
            }
        }
    }

    private function handleTimeTracking(TemplateTaskUser $templateTaskUser, $oldStatus, $newStatus)
    {
        try {
            if ($newStatus === 'in_progress' && $oldStatus !== 'in_progress') {
                $this->timeTrackingService->startTemplateTaskTracking($templateTaskUser);
            } elseif (in_array($newStatus, ['completed', 'paused', 'cancelled']) && $oldStatus === 'in_progress') {
                $this->timeTrackingService->stopTaskTracking($templateTaskUser);
            } elseif ($newStatus === 'cancelled' && $templateTaskUser->hasActiveTimeLog()) {
                $this->timeTrackingService->stopTaskTracking($templateTaskUser);
            }
        } catch (\Exception $e) {
            // No logging or comments
        }
    }

    private function addPointsForCompletedTemplateTask(TemplateTaskUser $templateTaskUser)
    {
        try {
            $templateTaskUser->load(['assignedBy', 'user', 'templateTask']);
            $this->taskNotificationService->notifyTemplateTaskCompleted($templateTaskUser);
        } catch (\Exception $e) {
            // No logging or comments
        }

        try {
            $templateTask = $templateTaskUser->templateTask;
            $user = $templateTaskUser->user;

            if (!$templateTask || !$user) {
                return;
            }

            $season = $templateTaskUser->season ?: Season::where('is_active', true)->first();

            if (!$season) {
                return;
            }

            $points = $templateTask->points ?? 10;

            DB::transaction(function () use ($user, $season, $points, $templateTask, $templateTaskUser) {
                $userSeasonPoint = UserSeasonPoint::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'season_id' => $season->id,
                    ],
                    [
                        'total_points' => DB::raw("total_points + {$points}"),
                        'tasks_completed' => DB::raw("tasks_completed + 1"),
                        'minutes_worked' => DB::raw("minutes_worked + " . ($templateTaskUser->actual_minutes ?? 0)),
                    ]
                );

                $userSeasonPoint->refresh();
            });

        } catch (\Exception $e) {
            // No logging or comments
        }
    }

    private function removePointsForUncompletedTemplateTask(TemplateTaskUser $templateTaskUser)
    {
        try {
            $templateTask = $templateTaskUser->templateTask;
            $user = $templateTaskUser->user;

            if (!$templateTask || !$user) {
                return;
            }

            $season = $templateTaskUser->season ?: Season::where('is_active', true)->first();

            if (!$season) {
                return;
            }

            $points = $templateTask->points ?? 10;
            $minutesToRemove = $templateTaskUser->getOriginal('actual_minutes') ?? 0;

            DB::transaction(function () use ($user, $season, $points, $templateTask, $minutesToRemove) {
                $userSeasonPoint = UserSeasonPoint::where('user_id', $user->id)
                                                 ->where('season_id', $season->id)
                                                 ->first();

                if ($userSeasonPoint) {
                    $newPoints = max(0, $userSeasonPoint->total_points - $points);
                    $newTasksCompleted = max(0, $userSeasonPoint->tasks_completed - 1);
                    $newMinutesWorked = max(0, $userSeasonPoint->minutes_worked - $minutesToRemove);

                    $userSeasonPoint->update([
                        'total_points' => $newPoints,
                        'tasks_completed' => $newTasksCompleted,
                        'minutes_worked' => $newMinutesWorked,
                    ]);
                } else {
                    // No logging or comments
                }
            });

        } catch (\Exception $e) {
            // No logging or comments
        }
    }
}
