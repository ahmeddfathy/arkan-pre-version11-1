<?php

namespace App\Services\TaskController;

use App\Models\User;
use App\Models\Task;
use App\Models\TemplateTaskUser;
use App\Models\CompanyService;
use App\Models\Project;
use App\Models\RoleHierarchy;
use App\Models\DepartmentRole;
use App\Services\TaskController\TaskHierarchyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class TaskFilterService
{
    protected $hierarchyService;

    public function __construct(TaskHierarchyService $hierarchyService)
    {
        $this->hierarchyService = $hierarchyService;
    }


    public function getFilteredServicesForUser()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole(['company_manager', 'hr', 'project_manager'])) {
            return CompanyService::orderBy('name')->get();
        }

        $userHierarchyLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($user);

        if ($userHierarchyLevel !== null) {
            if ($userHierarchyLevel >= 5) {
                return CompanyService::orderBy('name')->get();
            } elseif ($userHierarchyLevel >= 3) {
                return CompanyService::where(function($query) use ($user) {
                    $query->where('department', $user->department)
                          ->orWhere(function($subQuery) {
                              $subQuery->where('name', 'LIKE', '%جرافيك%')
                                       ->orWhere('name', 'LIKE', '%تصميم%')
                                       ->orWhere('name', 'LIKE', '%graphic%')
                                       ->orWhere('name', 'LIKE', '%design%');
                          });
                })->orderBy('name')->get();
            } else {
                return CompanyService::where(function($query) {
                    $query->where('name', 'LIKE', '%جرافيك%')
                          ->orWhere('name', 'LIKE', '%تصميم%')
                          ->orWhere('name', 'LIKE', '%graphic%')
                          ->orWhere('name', 'LIKE', '%design%');
                })->orderBy('name')->get();
            }
        }

        return CompanyService::orderBy('name')->get();
    }

    public function getFilteredUsersForCurrentUser()
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();



        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
                        return User::whereDoesntHave('roles', function($query) {
                        $query->where('name', 'company_manager');
                    })
                    ->where('employee_status', 'active')
                    ->select('id', 'name', 'email', 'department')
                    ->orderBy('name')
                    ->get();
        }

        $globalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($currentUser);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        if (!$currentUser->department) {
            return collect();
        }

        // فحص إذا كان المستخدم الحالي له أدوار غير مربوطة بقسمه - منع عرض أي مستخدمين
        $userRoleIds = $currentUser->roles->pluck('id')->toArray();
        foreach ($userRoleIds as $userRoleId) {
            if (!DepartmentRole::mappingExists($currentUser->department, $userRoleId)) {
                return collect(); // لا يتم عرض أي مستخدمين
            }
        }

        // للمستوى العالي (5 فما فوق) - جميع المستخدمين
        if ($globalLevel && $globalLevel >= 5) {
            $availableRoleIds = RoleHierarchy::where('hierarchy_level', '<=', $globalLevel)
                ->pluck('role_id')
                ->toArray();

            $users = User::whereHas('roles', function($query) use ($availableRoleIds) {
                        $query->whereIn('id', $availableRoleIds);
                    })
                    ->whereDoesntHave('roles', function($query) {
                        $query->where('name', 'company_manager');
                    })
                    ->where('employee_status', 'active')
                    ->select('id', 'name', 'email', 'department')
                    ->orderBy('name')
                    ->get();

            return $this->hierarchyService->addTeamInfoToUsers($users, true);
        }

        // للمستويات المتوسطة (3-4) - نفس القسم فقط
        if (($departmentLevel && $departmentLevel >= 3) || ($globalLevel && $globalLevel >= 3 && $globalLevel <= 4)) {
            // جلب أدوار القسم الأقل من المستخدم الحالي
            $effectiveLevel = $globalLevel ?? $departmentLevel;

            $departmentRoleIds = DepartmentRole::where('department_name', $currentUser->department)
                ->where('hierarchy_level', '<=', $effectiveLevel)
                ->pluck('role_id')
                ->toArray();

            $users = User::whereHas('roles', function($query) use ($departmentRoleIds) {
                        $query->whereIn('id', $departmentRoleIds);
                    })
                    ->where('department', $currentUser->department)
                    ->whereDoesntHave('roles', function($query) {
                        $query->where('name', 'company_manager');
                    })
                    ->where('employee_status', 'active')
                    ->select('id', 'name', 'email', 'department')
                    ->orderBy('name')
                    ->get();

            // تطبيق الفلترة الهرمية الجديدة
            $users = $this->hierarchyService->filterUsersByNewHierarchy($users, $currentUser);

            // إظهار معلومات الفريق للمستوى 4 فما فوق
            if ($effectiveLevel >= 4) {
                $users = $this->hierarchyService->addTeamInfoToUsers($users, true);
            }

            return $users;
        }

        // للمستوى الأقل (1) - نفس القسم فقط مع أدوار محدودة
        $users = User::where('department', $currentUser->department)
                    ->whereHas('roles', function($query) {
                        $query->whereIn('name', ['employee']);
                    })
                    ->whereDoesntHave('roles', function($query) {
                        $query->where('name', 'company_manager');
                    })
                    ->where('employee_status', 'active')
                    ->select('id', 'name', 'email', 'department')
                    ->orderBy('name')
                    ->get();

        return $users;
    }

    public function getFilteredRolesForUser()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole(['company_manager', 'hr', 'project_manager'])) {
            return Role::where('name', '!=', 'company_manager')->orderBy('name')->get();
        }

        $globalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        // للمستوى العالي (5 فما فوق) - جميع الأدوار
        if ($globalLevel && $globalLevel >= 5) {
            $availableRoleIds = RoleHierarchy::where('hierarchy_level', '<=', $globalLevel)
                ->pluck('role_id')
                ->toArray();

            return Role::whereIn('id', $availableRoleIds)
                      ->where('name', '!=', 'company_manager')
                      ->orderBy('name')
                      ->get();
        }

        // للمستويات المتوسطة (3-4) - أدوار القسم فقط
        if (($user->department && $departmentLevel && $departmentLevel >= 3) || ($globalLevel && $globalLevel >= 3 && $globalLevel <= 4)) {
            $effectiveLevel = $globalLevel ?? $departmentLevel;

            $departmentRoleIds = DepartmentRole::where('department_name', $user->department)
                ->where('hierarchy_level', '<=', $effectiveLevel)
                ->pluck('role_id')
                ->toArray();

            if (empty($departmentRoleIds)) {
                // لا توجد أدوار مربوطة بهذا القسم
                return collect();
            }

            return Role::whereIn('id', $departmentRoleIds)
                      ->where('name', '!=', 'company_manager')
                      ->orderBy('name')
                      ->get();
        }

        // المستوى الأقل - أدوار محدودة
        return Role::where('name', '!=', 'company_manager')
                   ->whereIn('name', ['employee']) // أدوار محدودة للمستوى 1
                   ->orderBy('name')
                   ->get();
    }

    public function getUsersByService(int $serviceId): array
    {
        try {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            $service = CompanyService::findOrFail($serviceId);



            $globalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($currentUser);
            $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

            if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
                $users = $service->specializedUsers()
                            ->where('employee_status', 'active')
                            ->select('id', 'name', 'email', 'department')
                            ->orderBy('name')
                            ->get();

                $users = $this->hierarchyService->addTeamInfoToUsers($users, true);

                return [
                    'success' => true,
                    'users' => $users
                ];
            }

            if ($globalLevel && $globalLevel >= 5) {
                $users = $service->specializedUsers()
                            ->where('employee_status', 'active')
                            ->select('id', 'name', 'email', 'department')
                            ->orderBy('name')
                            ->get();

                $users = $this->hierarchyService->filterUsersByNewHierarchy($users, $currentUser);
                $users = $this->hierarchyService->addTeamInfoToUsers($users, true);

                return [
                    'success' => true,
                    'users' => $users
                ];
            }

            $isGraphicService = $this->hierarchyService->isGraphicService($service);

            if ($isGraphicService) {
                $users = $service->specializedUsers()
                            ->where('employee_status', 'active')
                            ->select('id', 'name', 'email', 'department')
                                                        ->orderBy('name')
                            ->get();
            } else {
                if ($currentUser->department && !$currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
                    $userRoleIds = $currentUser->roles->pluck('id')->toArray();
                    foreach ($userRoleIds as $userRoleId) {
                        if (!DepartmentRole::mappingExists($currentUser->department, $userRoleId)) {
                            $roleName = \Spatie\Permission\Models\Role::find($userRoleId)->name ?? 'غير معروف';
                            return [
                                'success' => false,
                                'message' => "لديك دور '{$roleName}' غير مربوط بقسم '{$currentUser->department}'. لا يمكن إجراء الفلترة. يرجى التواصل مع الإدارة لربط الدور بالقسم."
                            ];
                        }
                    }
                }

                $allowedUsers = $this->getFilteredUsersForCurrentUser();

                $serviceUserIds = $service->specializedUsers()->pluck('id')->toArray();
                                $users = $allowedUsers->filter(function($user) use ($serviceUserIds) {
                    return in_array($user->id, $serviceUserIds);
                });
            }

            if ($departmentLevel >= 4 || $globalLevel >= 4) {
                $users = $this->hierarchyService->addTeamInfoToUsers($users, true);
            }

            return [
                'success' => true,
                'users' => $users->values()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل المستخدمين: ' . $e->getMessage()
            ];
        }
    }

    public function getUsersByRole(string $roleName): array
    {
        try {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();

            if (!$this->hierarchyService->canViewRoleUsers($roleName)) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بعرض مستخدمي هذا الدور'
                ];
            }

            if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
                $users = User::role($roleName)
                            ->whereDoesntHave('roles', function($query) {
                                $query->where('name', 'company_manager');
                            })
                            ->where('employee_status', 'active')
                            ->select('id', 'name', 'email', 'department')
                            ->orderBy('name')
                            ->get();

                return [
                    'success' => true,
                    'users' => $users,
                    'role' => $roleName
                ];
            }

            if (!$currentUser->department) {
                return [
                    'success' => false,
                    'message' => 'لا يمكنك عرض المستخدمين بدون قسم محدد'
                ];
            }

            $roleId = \Spatie\Permission\Models\Role::where('name', $roleName)->value('id');
            if ($roleId && !DepartmentRole::mappingExists($currentUser->department, $roleId)) {
                return [
                    'success' => false,
                    'message' => "الدور '{$roleName}' غير مربوط بقسم '{$currentUser->department}'. لا يمكن إجراء الفلترة. يرجى التواصل مع الإدارة لربط الدور بالقسم."
                ];
            }

            if ($currentUser->department && !$currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
                $userRoleIds = $currentUser->roles->pluck('id')->toArray();
                foreach ($userRoleIds as $userRoleId) {
                    if (!DepartmentRole::mappingExists($currentUser->department, $userRoleId)) {
                        $userRoleName = \Spatie\Permission\Models\Role::find($userRoleId)->name ?? 'غير معروف';
                        return [
                            'success' => false,
                            'message' => "لديك دور '{$userRoleName}' غير مربوط بقسم '{$currentUser->department}'. لا يمكن إجراء الفلترة. يرجى التواصل مع الإدارة لربط الدور بالقسم."
                        ];
                    }
                }
            }

            $currentUserLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($currentUser);

            $roleLevel = RoleHierarchy::getRoleHierarchyLevelByName($roleName);

            if ($currentUserLevel === null || $roleLevel === null || $currentUserLevel < $roleLevel) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بعرض مستخدمي هذا الدور'
                ];
            }

            $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

            if ($currentUserLevel >= 5) {
                $users = User::role($roleName)
                            ->whereDoesntHave('roles', function($query) {
                                $query->where('name', 'company_manager');
                            })
                            ->where('employee_status', 'active')
                            ->select('id', 'name', 'email', 'department')
                            ->orderBy('name')
                            ->get();
            }
            else {
                $users = User::role($roleName)
                            ->where('department', $currentUser->department)
                            ->whereDoesntHave('roles', function($query) {
                                $query->where('name', 'company_manager');
                            })
                            ->where('employee_status', 'active')
                            ->select('id', 'name', 'email', 'department')
                            ->orderBy('name')
                            ->get();
            }

            $users = $this->hierarchyService->filterUsersByNewHierarchy($users, $currentUser);

            if ($departmentLevel >= 4 || $currentUserLevel >= 4) {
                $users = $this->hierarchyService->addTeamInfoToUsers($users, true);
            }

            return [
                'success' => true,
                'users' => $users,
                'role' => $roleName,
                'current_user_level' => $currentUserLevel,
                'role_level' => $roleLevel,
                'show_team_info' => $currentUserLevel >= 4
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل المستخدمين: ' . $e->getMessage()
            ];
        }
    }

    public function getAvailableProjects()
    {
        return Project::where('status', '!=', 'مكتمل')->orderBy('name')->get();
    }

    public function getUserProjects(int $userId)
    {
        // ✅ جلب جميع المشاريع التي الموظف مشارك فيها (سواء لديه مهام أو لا)
        return Project::where('status', '!=', 'مكتمل')
            ->where(function ($query) use ($userId) {
                // 1. المشاريع التي الموظف مشارك فيها (من project_service_user)
                $query->whereHas('serviceParticipants', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                // OR 2. المشاريع التي لديها مهام عادية للمستخدم
                ->orWhereHas('tasks', function ($q) use ($userId) {
                    $q->whereHas('users', function ($q2) use ($userId) {
                        $q2->where('users.id', $userId);
                    });
                })
                // OR 3. المشاريع التي لديها مهام قوالب للمستخدم
                ->orWhereHas('templateTaskUsers', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function applyTaskFilters($query, array $filters)
    {
        if (isset($filters['project_id']) && $filters['project_id']) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['service_id']) && $filters['service_id']) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id']) && $filters['user_id']) {
            $query->whereHas('users', function ($q) use ($filters) {
                $q->where('users.id', $filters['user_id']);
            });
        }

        if (isset($filters['created_by']) && $filters['created_by']) {
            $query->where('created_by', $filters['created_by']);
        }

        return $query;
    }

    public function applyUserTaskFilters($query, int $userId, array $filters)
    {
        if (isset($filters['project_id']) && $filters['project_id']) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->whereHas('users', function ($q) use ($userId, $filters) {
                $q->where('users.id', $userId)
                    ->where('task_users.status', $filters['status']);
            });
        }

        return $query;
    }

    /**
     * الحصول على المستخدمين الذين أنشأوا مهام (للفلترة)
     */
    public function getTaskCreators()
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // تطبيق نفس الفلترة الهرمية على منشئي المهام
        $query = Task::select('created_by')->distinct()->whereNotNull('created_by');

        // تطبيق الفلترة الهرمية لرؤية المهام المسموح بها فقط
        $query = $this->applyHierarchicalTaskFiltering($query, $currentUser);

        $creatorIds = $query->pluck('created_by')->unique()->filter();

        return User::whereIn('id', $creatorIds)
                   ->select('id', 'name')
                   ->orderBy('name')
                   ->get();
    }

    /**
     * تطبيق الفلترة الهرمية على المهام حسب مستوى المستخدم
     */
    public function applyHierarchicalTaskFiltering($query, ?User $currentUser = null)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $currentUser ?? Auth::user();

        // التحقق من وجود المستخدم قبل المتابعة
        if (!$currentUser) {
            return $query; // إرجاع الـ query بدون تعديل إذا لم يوجد مستخدم
        }

        // الإدارة العليا - ترى كل المهام
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
                    // المستخدم له دور غير مربوط بقسمه - سيرى مهامه الشخصية فقط
                    $hasInvalidRoleMapping = true;
                    break;
                }
            }
        }

        // إذا كان هناك مشكلة في ربط الأدوار، اظهر المهام الشخصية فقط
        if ($hasInvalidRoleMapping) {
            return $query->where(function($q) use ($currentUser) {
                $q->whereHas('users', function($subQ) use ($currentUser) {
                    $subQ->where('users.id', $currentUser->id);
                })->orWhere('created_by', $currentUser->id);
            });
        }

        // المستوى العالي (5 فما فوق) - يرى كل المهام
        if ($globalLevel && $globalLevel >= 5) {
            return $query;
        }

        // مدير القسم (المستوى 4 فما فوق) - يرى مهام قسمه أو التي أنشأها
        if (($currentUser->department && $departmentLevel && $departmentLevel >= 4) || ($globalLevel && $globalLevel == 4)) {
            return $query->where(function($q) use ($currentUser) {
                $q->whereHas('users', function($subQ) use ($currentUser) {
                    $subQ->where('users.department', $currentUser->department);
                })->orWhere('created_by', $currentUser->id);
            });
        }

        // Team Leader (المستوى 3) - يرى مهام فريقه فقط
        if (($currentUser->department && $departmentLevel && $departmentLevel == 3) || ($globalLevel && $globalLevel == 3)) {
            $currentTeamId = $currentUser->current_team_id;

            // البحث عن فريق يملكه المستخدم إذا لم يكن له فريق حالي
            if (!$currentTeamId) {
                $ownedTeam = DB::table('teams')
                    ->where('user_id', $currentUser->id)
                    ->first();
                $currentTeamId = $ownedTeam ? $ownedTeam->id : null;
            }

            if (!$currentTeamId) {
                // لا يوجد فريق - يرى مهامه الشخصية فقط
                return $query->whereHas('users', function($q) use ($currentUser) {
                    $q->where('users.id', $currentUser->id);
                });
            }

            // جلب أعضاء الفريق
            $teamUserIds = collect([$currentUser->id]); // يشمل نفسه

            // المستخدمين الذين فريقهم الحالي هو نفس الفريق
            $directTeamMembers = User::where('current_team_id', $currentTeamId)
                ->pluck('id');
            $teamUserIds = $teamUserIds->merge($directTeamMembers);

            // المستخدمين أعضاء في الفريق من جدول team_user
            $teamMembers = DB::table('team_user')
                ->where('team_id', $currentTeamId)
                ->pluck('user_id');
            $teamUserIds = $teamUserIds->merge($teamMembers);

            $teamUserIds = $teamUserIds->unique()->toArray();

            return $query->where(function($q) use ($teamUserIds, $currentUser) {
                $q->whereHas('users', function($subQ) use ($teamUserIds) {
                    $subQ->whereIn('users.id', $teamUserIds);
                })->orWhere('created_by', $currentUser->id);
            });
        }

        // للمستخدمين الجرافيكيين فقط - يرون مهام الخدمات الجرافيكية أو التي أنشأوها
        if ($this->hierarchyService->isGraphicOnlyUser()) {
            return $query->where(function($q) use ($currentUser) {
                $q->where(function($subQ) use ($currentUser) {
                    $subQ->whereHas('service', function($serviceQ) {
                        $serviceQ->where(function($serviceQuery) {
                            $serviceQuery->where('name', 'LIKE', '%جرافيك%')
                                       ->orWhere('name', 'LIKE', '%تصميم%')
                                       ->orWhere('name', 'LIKE', '%graphic%')
                                       ->orWhere('name', 'LIKE', '%design%');
                        });
                    })->whereHas('users', function($userQ) use ($currentUser) {
                        $userQ->where('users.id', $currentUser->id);
                    });
                })->orWhere('created_by', $currentUser->id);
            });
        }

        // المستوى الأدنى (1) أو المستخدمين العاديين - يرون مهامهم الشخصية أو التي أنشأوها
        return $query->where(function($q) use ($currentUser) {
            $q->whereHas('users', function($subQ) use ($currentUser) {
                $subQ->where('users.id', $currentUser->id);
            })->orWhere('created_by', $currentUser->id);
        });
    }

    /**
     * تطبيق الفلترة الهرمية على مهام القوالب حسب مستوى المستخدم
     */
    public function applyHierarchicalTemplateTaskFiltering($query, ?User $currentUser = null)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $currentUser ?? Auth::user();

        // التحقق من وجود المستخدم قبل المتابعة
        if (!$currentUser) {
            return $query; // إرجاع الـ query بدون تعديل إذا لم يوجد مستخدم
        }

        // الإدارة العليا - ترى كل مهام القوالب
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
                    // المستخدم له دور غير مربوط بقسمه - سيرى مهامه الشخصية فقط
                    $hasInvalidRoleMapping = true;
                    break;
                }
            }
        }

        // إذا كان هناك مشكلة في ربط الأدوار، اظهر مهام التمبليت الشخصية فقط
        if ($hasInvalidRoleMapping) {
            return $query->where('user_id', $currentUser->id);
        }

        // المستوى العالي (5 فما فوق) - يرى كل مهام القوالب
        if ($globalLevel && $globalLevel >= 5) {
            return $query;
        }

        // مدير القسم (المستوى 4 فما فوق) - يرى مهام القوالب لقسمه
        if (($currentUser->department && $departmentLevel && $departmentLevel >= 4) || ($globalLevel && $globalLevel == 4)) {
            return $query->where(function($q) use ($currentUser) {
                $q->whereHas('user', function($subQ) use ($currentUser) {
                    $subQ->where('users.department', $currentUser->department);
                });
            });
        }

        // Team Leader (المستوى 3) - يرى مهام القوالب لفريقه (نفس منطق المهام العادية)
        if (($currentUser->department && $departmentLevel && $departmentLevel == 3) || ($globalLevel && $globalLevel == 3)) {
            $currentTeamId = $currentUser->current_team_id;

            // البحث عن فريق يملكه المستخدم إذا لم يكن له فريق حالي
            if (!$currentTeamId) {
                $ownedTeam = DB::table('teams')
                    ->where('user_id', $currentUser->id)
                    ->first();
                $currentTeamId = $ownedTeam ? $ownedTeam->id : null;
            }

            if (!$currentTeamId) {
                // لا يوجد فريق - يرى مهام التمبليت الشخصية فقط
                return $query->where('user_id', $currentUser->id);
            }

            // جلب أعضاء الفريق (نفس المنطق المستخدم في المهام العادية)
            $teamUserIds = collect([$currentUser->id]); // يشمل نفسه

            // المستخدمين الذين فريقهم الحالي هو نفس الفريق
            $directTeamMembers = User::where('current_team_id', $currentTeamId)
                ->pluck('id');
            $teamUserIds = $teamUserIds->merge($directTeamMembers);

            // المستخدمين أعضاء في الفريق من جدول team_user
            $teamMembers = DB::table('team_user')
                ->where('team_id', $currentTeamId)
                ->pluck('user_id');
            $teamUserIds = $teamUserIds->merge($teamMembers);

            $teamUserIds = $teamUserIds->unique()->toArray();

            return $query->whereIn('user_id', $teamUserIds);
        }

        // المستوى الأدنى (1) أو المستخدمين العاديين - يرون مهام القوالب الشخصية فقط
        return $query->where('user_id', $currentUser->id);
    }
}
