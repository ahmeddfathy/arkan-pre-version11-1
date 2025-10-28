<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\Season;
use App\Models\User;
use App\Services\BadgeService;

class ProjectCompletionService
{
    protected $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }

    public function processProjectCompletion(Project $project, User $user)
    {
        $currentSeason = Season::getCurrentActiveSeason();

        if (!$currentSeason) {
            return [
                'success' => false,
                'message' => 'لا يوجد موسم نشط حاليًا',
                'data' => null
            ];
        }

        $isParticipant = $project->participants()->where('user_id', $user->id)->exists();

        if (!$isParticipant && $project->user_id != $user->id) {
            return [
                'success' => false,
                'message' => 'المشروع غير مخصص لهذا المستخدم',
                'data' => null
            ];
        }

        $points = $this->calculatePointsForProject($project);

        $result = $this->badgeService->addPointsToUser(
            $user,
            $points,
            0,
            1,
            0
        );

        return $result;
    }

    protected function calculatePointsForProject(Project $project)
    {
        $basePoints = 500;

        $priorityMultiplier = 1.0;

        if ($project->priority) {
            $priorityMultipliers = [
                'low' => 0.8,
                'medium' => 1.0,
                'high' => 1.5,
                'urgent' => 2.0,
            ];

            $priority = strtolower($project->priority);
            $priorityMultiplier = $priorityMultipliers[$priority] ?? 1.0;
        }

        $typeMultiplier = 1.0;

        if ($project->type) {
            $typeMultipliers = [
                'simple' => 0.8,
                'standard' => 1.0,
                'complex' => 1.3,
                'strategic' => 1.5,
            ];

            $type = strtolower($project->type);
            $typeMultiplier = $typeMultipliers[$type] ?? 1.0;
        }

        $durationMultiplier = 1.0;

        $deliveryDate = $project->client_agreed_delivery_date ?? $project->team_delivery_date;
        if ($project->start_date && $deliveryDate) {
            $startDateString = $project->start_date instanceof \Carbon\Carbon
                ? $project->start_date->format('Y-m-d')
                : (string) $project->start_date;
            $endDateString = $deliveryDate instanceof \Carbon\Carbon
                ? $deliveryDate->format('Y-m-d')
                : (string) $deliveryDate;

            $startDate = new \DateTime($startDateString);
            $endDate = new \DateTime($endDateString);
            $duration = $startDate->diff($endDate)->days;

            if ($duration <= 7) {
                $durationMultiplier = 0.8;
            } elseif ($duration <= 30) {
                $durationMultiplier = 1.0;
            } elseif ($duration <= 90) {
                $durationMultiplier = 1.3;
            } else {
                $durationMultiplier = 1.5;
            }
        }

        $totalPoints = $basePoints * $priorityMultiplier * $typeMultiplier * $durationMultiplier;

        return (int) $totalPoints;
    }
}
