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

    /**
     * إضافة شرط للبحث في reviewers JSON
     */
    protected function addReviewerCheck($query, $userId)
    {
        return $query->orWhereRaw("JSON_CONTAINS(reviewers, JSON_OBJECT('reviewer_id', ?), '$')", [$userId]);
    }

    /**
     * تطبيق الفلترة الهرمية على التعديلات حسب مستوى المستخدم
     */
    public function applyHierarchicalRevisionFiltering(Builder $query, ?User $currentUser = null): Builder
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $currentUser ?? Auth::user();

        // التحقق من وجود المستخدم قبل المتابعة
        if (!$currentUser) {
            return $query;
        }

        // الإدارة العليا - ترى كل التعديلات
        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
            return $query;
        }

        $globalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($currentUser);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        // التحقق من صحة ارتباط أدوار المستخدم بقسمه
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

        // إذا كان هناك مشكلة في ربط الأدوار، اظهر التعديلات الشخصية فقط
        if ($hasInvalidRoleMapping) {
            return $this->getPersonalRevisions($query, $currentUser);
        }

        // المستوى العالي (5 فما فوق) - يرى كل التعديلات
        if ($globalLevel && $globalLevel >= 5) {
            return $query;
        }

        // المستوى 4 فما فوق - يرى كل التعديلات
        if ($globalLevel && $globalLevel >= 4) {
            return $query;
        }

        // المدراء والـ Team Leaders - يرون تعديلات فريقهم/قسمهم
        if ($globalLevel && $globalLevel >= 3) {
            return $this->getTeamRevisions($query, $currentUser);
        }

        // المستوى الأدنى أو المستخدمين العاديين - يرون تعديلاتهم الشخصية
        return $this->getPersonalRevisions($query, $currentUser);
    }

    /**
     * الحصول على التعديلات الشخصية للمستخدم
     */
    protected function getPersonalRevisions(Builder $query, User $currentUser): Builder
    {
        return $query->where(function($q) use ($currentUser) {
            $q->where('created_by', $currentUser->id) // التعديلات التي أنشأها
              ->orWhere('assigned_to', $currentUser->id) // التعديلات المسندة إليه مباشرة
              ->orWhere('responsible_user_id', $currentUser->id) // التعديلات التي هو مسؤول عنها
              ->orWhere('executor_user_id', $currentUser->id); // التعديلات المعين كمنفذ لها

            // التعديلات المعين كمراجع لها (JSON)
            $this->addReviewerCheck($q, $currentUser->id);

            $q->orWhereHas('taskUser', function($taskUserQuery) use ($currentUser) {
                  $taskUserQuery->where('user_id', $currentUser->id); // التعديلات المرتبطة بمهامه
              })
              ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($currentUser) {
                  $templateTaskUserQuery->where('user_id', $currentUser->id); // التعديلات المرتبطة بمهام القوالب
              });
        });
    }

    /**
     * الحصول على تعديلات الفريق/القسم
     */
    protected function getTeamRevisions(Builder $query, User $currentUser): Builder
    {
        $teamUserIds = $this->getTeamMemberIds($currentUser);

        return $query->where(function($q) use ($currentUser, $teamUserIds) {
            // التعديلات الشخصية
            $q->where(function($personalQ) use ($currentUser) {
                $personalQ->where('created_by', $currentUser->id)
                          ->orWhere('assigned_to', $currentUser->id)
                          ->orWhere('responsible_user_id', $currentUser->id)
                          ->orWhere('executor_user_id', $currentUser->id);

                // المعين كمراجع (JSON)
                $this->addReviewerCheck($personalQ, $currentUser->id);

                $personalQ->orWhereHas('taskUser', function($taskUserQuery) use ($currentUser) {
                              $taskUserQuery->where('user_id', $currentUser->id);
                          })
                          ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($currentUser) {
                              $templateTaskUserQuery->where('user_id', $currentUser->id);
                          });
            })
            // تعديلات أعضاء الفريق
            ->orWhere(function($teamQ) use ($teamUserIds) {
                $teamQ->whereIn('created_by', $teamUserIds)
                      ->orWhereIn('assigned_to', $teamUserIds)
                      ->orWhereIn('responsible_user_id', $teamUserIds)
                      ->orWhereIn('executor_user_id', $teamUserIds);

                // المعينين كمراجعين من الفريق (JSON)
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

    /**
     * الحصول على معرفات أعضاء الفريق
     */
    protected function getTeamMemberIds(User $currentUser): array
    {
        // إذا كان department manager
        if ($currentUser->hasAnyRole(['technical_department_manager', 'marketing_department_manager',
                                      'customer_service_department_manager', 'coordination_department_manager'])) {
            // جلب جميع أعضاء القسم
            return User::where('department', $currentUser->department)
                      ->pluck('id')
                      ->toArray();
        }

        // إذا كان team leader
        if ($currentUser->hasAnyRole(['technical_team_leader', 'marketing_team_leader',
                                      'customer_service_team_leader', 'coordination_team_leader'])) {
            // جلب أعضاء الفريق بناءً على القسم والدور
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

    /**
     * الحصول على أدوار الفريق بناءً على دور القائد
     */
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

    /**
     * فلترة التعديلات حسب المستخدم الحالي (للاستخدام في "تعديلاتي")
     */
    public function getMyAssignedRevisions(Builder $query, User $currentUser): Builder
    {
        return $query->where(function($q) use ($currentUser) {
            $q->where('assigned_to', $currentUser->id)
              ->orWhere('responsible_user_id', $currentUser->id) // المسؤول عن التعديل
              ->orWhere('executor_user_id', $currentUser->id); // المنفذ المعين

            // المراجع المعين (JSON)
            $this->addReviewerCheck($q, $currentUser->id);

            $q->orWhereHas('taskUser', function($taskUserQuery) use ($currentUser) {
                  $taskUserQuery->where('user_id', $currentUser->id);
              })
              ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($currentUser) {
                  $templateTaskUserQuery->where('user_id', $currentUser->id);
              });
        });
    }

    /**
     * فلترة التعديلات التي أنشأها المستخدم (للاستخدام في "التعديلات التي أضفتها")
     */
    public function getMyCreatedRevisions(Builder $query, User $currentUser): Builder
    {
        return $query->where('created_by', $currentUser->id);
    }
}
