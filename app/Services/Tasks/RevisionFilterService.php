<?php

namespace App\Services\Tasks;

use App\Models\User;
use App\Models\TaskRevision;
use App\Models\DepartmentRole;
use App\Services\TaskController\TaskHierarchyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class RevisionFilterService
{
    protected $hierarchyService;

    public function __construct(TaskHierarchyService $hierarchyService)
    {
        $this->hierarchyService = $hierarchyService;
    }


    protected function addReviewerCheck($query, $userId)
    {
        return $query->orWhereRaw("JSON_CONTAINS(reviewers, JSON_OBJECT('reviewer_id', ?), '$')", [$userId]);
    }

    public function applyHierarchicalRevisionFiltering(Builder $query, ?User $currentUser = null): Builder
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $currentUser ?? Auth::user();

        if (!$currentUser) {
            return $query;
        }

        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
            return $query;
        }

        $globalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($currentUser);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        $hasInvalidRoleMapping = false;
        if ($currentUser->department && !$currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
            $userRoleIds = $currentUser->roles->pluck('id')->toArray();
            foreach ($userRoleIds as $userRoleId) {
                if (!DepartmentRole::mappingExists($currentUser->department, $userRoleId)) {
                    $hasInvalidRoleMapping = true;
                    break;
                }
            }
        }

        if ($hasInvalidRoleMapping) {
            return $this->getPersonalRevisions($query, $currentUser);
        }

        if ($globalLevel && $globalLevel >= 5) {
            return $query;
        }

        if ($globalLevel && $globalLevel >= 4) {
            return $query;
        }

        if ($globalLevel && $globalLevel >= 3) {
            return $this->getTeamRevisions($query, $currentUser);
        }

        return $this->getPersonalRevisions($query, $currentUser);
    }

    protected function getPersonalRevisions(Builder $query, User $currentUser): Builder
    {
        return $query->where(function($q) use ($currentUser) {
            $q->where('created_by', $currentUser->id)
              ->orWhere('assigned_to', $currentUser->id)
              ->orWhere('responsible_user_id', $currentUser->id)
              ->orWhere('executor_user_id', $currentUser->id);
            $this->addReviewerCheck($q, $currentUser->id);

            $q->orWhereHas('taskUser', function($taskUserQuery) use ($currentUser) {
                  $taskUserQuery->where('user_id', $currentUser->id);
              })
              ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($currentUser) {
                  $templateTaskUserQuery->where('user_id', $currentUser->id);
              });
        });
    }

    protected function getTeamRevisions(Builder $query, User $currentUser): Builder
    {
        $teamUserIds = $this->getTeamMemberIds($currentUser);

        return $query->where(function($q) use ($currentUser, $teamUserIds) {
            $q->where(function($personalQ) use ($currentUser) {
                $personalQ->where('created_by', $currentUser->id)
                          ->orWhere('assigned_to', $currentUser->id)
                          ->orWhere('responsible_user_id', $currentUser->id)
                          ->orWhere('executor_user_id', $currentUser->id);

                $this->addReviewerCheck($personalQ, $currentUser->id);

                $personalQ->orWhereHas('taskUser', function($taskUserQuery) use ($currentUser) {
                              $taskUserQuery->where('user_id', $currentUser->id);
                          })
                          ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($currentUser) {
                              $templateTaskUserQuery->where('user_id', $currentUser->id);
                          });
            })
            ->orWhere(function($teamQ) use ($teamUserIds) {
                $teamQ->whereIn('created_by', $teamUserIds)
                      ->orWhereIn('assigned_to', $teamUserIds)
                      ->orWhereIn('responsible_user_id', $teamUserIds)
                      ->orWhereIn('executor_user_id', $teamUserIds);

                foreach ($teamUserIds as $teamUserId) {
                    $this->addReviewerCheck($teamQ, $teamUserId);
                }

                $teamQ->orWhereHas('taskUser', function($taskUserQuery) use ($teamUserIds) {
                          $taskUserQuery->whereIn('user_id', $teamUserIds);
                      })
                      ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($teamUserIds) {
                          $templateTaskUserQuery->whereIn('user_id', $teamUserIds);
                      });
            });
        });
    }

    protected function getTeamMemberIds(User $currentUser): array
    {
        if ($currentUser->hasAnyRole(['technical_department_manager', 'marketing_department_manager',
                                      'customer_service_department_manager', 'coordination_department_manager'])) {
            return User::where('department', $currentUser->department)
                      ->pluck('id')
                      ->toArray();
        }

        if ($currentUser->hasAnyRole(['technical_team_leader', 'marketing_team_leader',
                                      'customer_service_team_leader', 'coordination_team_leader'])) {
            $teamRoles = $this->getTeamRoles($currentUser);
            return User::where('department', $currentUser->department)
                      ->whereHas('roles', function($q) use ($teamRoles) {
                          $q->whereIn('name', $teamRoles);
                      })
                      ->pluck('id')
                      ->toArray();
        }

        return [];
    }

    protected function getTeamRoles(User $currentUser): array
    {
        if ($currentUser->hasRole('technical_team_leader')) {
            return ['graphic_designer', 'motion_graphic_designer', 'video_editor'];
        }

        if ($currentUser->hasRole('marketing_team_leader')) {
            return ['marketing_employee', 'social_media_manager'];
        }

        if ($currentUser->hasRole('customer_service_team_leader')) {
            return ['sales_employee', 'customer_service_employee'];
        }

        if ($currentUser->hasRole('coordination_team_leader')) {
            return ['coordination_employee'];
        }

        return [];
    }

    public function getMyAssignedRevisions(Builder $query, User $currentUser): Builder
    {
        return $query->where(function($q) use ($currentUser) {
            $q->where('assigned_to', $currentUser->id)
              ->orWhere('responsible_user_id', $currentUser->id)
              ->orWhere('executor_user_id', $currentUser->id);
            $this->addReviewerCheck($q, $currentUser->id);

            $q->orWhereHas('taskUser', function($taskUserQuery) use ($currentUser) {
                  $taskUserQuery->where('user_id', $currentUser->id);
              })
              ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($currentUser) {
                  $templateTaskUserQuery->where('user_id', $currentUser->id);
              });
        });
    }

    public function getMyCreatedRevisions(Builder $query, User $currentUser): Builder
    {
        return $query->where('created_by', $currentUser->id);
    }
}
