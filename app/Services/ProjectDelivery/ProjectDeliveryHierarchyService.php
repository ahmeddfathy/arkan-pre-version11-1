<?php

namespace App\Services\ProjectDelivery;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectServiceUser;
use App\Models\CompanyService;
use App\Models\Team;
use App\Models\RoleHierarchy;
use App\Models\DepartmentRole;
use App\Models\RoleApproval;
use App\Services\Auth\RoleCheckService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ProjectDeliveryHierarchyService
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * جلب التسليمات حسب النظام الهرمي
     */
    public function getHierarchicalDeliveries(User $currentUser, array $filters = []): Collection
    {
        // بناء الاستعلام الأساسي
        $query = ProjectServiceUser::with([
            'project:id,name,code,status,client_id',
            'project.client:id,name',
            'service:id,name,department',
            'user:id,name,email,department,current_team_id',
            'user.roles:id,name',
            'team:id,name',
            'administrativeApprover:id,name',
            'technicalApprover:id,name'
        ]);

        // تطبيق الفلترة الهرمية
        $query = $this->applyHierarchicalFiltering($query, $currentUser);

        // تطبيق الفلاتر الإضافية
        $query = $this->applyAdditionalFilters($query, $filters);

        // ترتيب النتائج
        $query->orderBy('created_at', 'desc');

        $deliveries = $query->get();

        // إضافة معلومات إضافية للتسليمات
        return $this->enrichDeliveriesData($deliveries, $currentUser);
    }

    /**
     * تطبيق الفلترة الهرمية
     */
private function applyHierarchicalFiltering($query, User $currentUser)
    {
        // الإدارة العليا - ترى كل التسليمات
        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager', 'operations_manager'])) {
            return $query;
        }

        $globalLevel = $this->getCurrentUserHierarchyLevel($currentUser);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        // التحقق من صحة ارتباط أدوار المستخدم بقسمه
        if ($currentUser->department && !$this->hasValidRoleMapping($currentUser)) {
            // المستخدم له دور غير مربوط بقسمه - يرى تسليماته الشخصية فقط
            return $query->where('user_id', $currentUser->id);
        }

        // المستوى العالي (5 فما فوق) - يرى كل التسليمات
        if ($globalLevel && $globalLevel >= 5) {
            return $query;
        }

        // مدير القسم (المستوى 4 فما فوق) - يرى تسليمات قسمه
        if ($currentUser->department && $departmentLevel && $departmentLevel >= 4) {
            return $query->whereHas('user', function($q) use ($currentUser) {
                $q->where('department', $currentUser->department);
            });
        }

        // Team Leader (المستوى 3) - يرى تسليمات فريقه
        if ($currentUser->department && $departmentLevel && $departmentLevel == 3) {
            $teamUserIds = $this->getTeamMemberIds($currentUser);

            if ($teamUserIds->isEmpty()) {
                // لا يوجد فريق - يرى تسليماته الشخصية فقط
                return $query->where('user_id', $currentUser->id);
            }

            return $query->whereIn('user_id', $teamUserIds->toArray());
        }

        // المستخدم العادي - يرى تسليماته الشخصية فقط
        return $query->where('user_id', $currentUser->id);
    }

    /**
     * تطبيق الفلاتر الإضافية
     */
    private function applyAdditionalFilters($query, array $filters)
    {
        // تحديد نوع التاريخ للفلترة (team أو client)
        $dateType = $filters['date_type'] ?? 'team';
        $dateColumn = $dateType === 'client' ? 'client_agreed_delivery_date' : 'team_delivery_date';

        // فلتر التاريخ - حسب تاريخ المشروع
        if (isset($filters['start_date']) || isset($filters['end_date'])) {
            $query->whereHas('project', function($q) use ($filters, $dateColumn) {
                if (isset($filters['start_date'])) {
                    $q->where($dateColumn, '>=', $filters['start_date']);
                }
                if (isset($filters['end_date'])) {
                    $q->where($dateColumn, '<=', $filters['end_date']);
                }
            });
        }

        // فلتر المستخدم
        if (isset($filters['user_id']) && $filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        // فلتر المشروع
        if (isset($filters['project_id']) && $filters['project_id']) {
            $query->where('project_id', $filters['project_id']);
        }

        // فلتر الخدمة
        if (isset($filters['service_id']) && $filters['service_id']) {
            $query->where('service_id', $filters['service_id']);
        }

        // فلتر حالة التأكيد
        if (isset($filters['acknowledgment_status']) && $filters['acknowledgment_status'] !== '') {
            $isAcknowledged = $filters['acknowledgment_status'] === '1';
            $query->where('is_acknowledged', $isAcknowledged);
        }

        // فلتر الفريق
        if (isset($filters['team_id']) && $filters['team_id']) {
            $query->where('team_id', $filters['team_id']);
        }

        return $query;
    }

    /**
     * إثراء بيانات التسليمات
     */
    private function enrichDeliveriesData(Collection $deliveries, User $currentUser): Collection
    {
        return $deliveries->map(function ($delivery) use ($currentUser) {
            // إضافة معلومات الفريق والرول
            if ($delivery->user) {
                $userLevel = $this->getCurrentUserHierarchyLevel($delivery->user);
                $delivery->user->hierarchy_level = $userLevel;
                $delivery->user->hierarchy_title = $this->getUserRealRoles($delivery->user);

                // إضافة معلومات الفريق
                $teamInfo = $this->getUserTeamInfo($delivery->user);
                $delivery->user->team_info = $teamInfo;
            }

            $delivery->days_remaining = $delivery->getDaysRemaining();

            $delivery->can_acknowledge = $this->canUserAcknowledge($delivery, $currentUser);
            $delivery->can_unacknowledge = $this->canUserUnacknowledge($delivery, $currentUser);


            $delivery->approval_status = $delivery->getApprovalStatus();
            $delivery->required_approvals = $delivery->getRequiredApprovals();
            $delivery->can_approve_administrative = $delivery->isDelivered()
                                                    && $delivery->isAcknowledged()
                                                    && $delivery->canUserApprove($currentUser->id, 'administrative')
                                                    && !$delivery->hasAdministrativeApproval();
            $delivery->can_approve_technical = $delivery->isDelivered()
                                               && $delivery->isAcknowledged()
                                               && $delivery->canUserApprove($currentUser->id, 'technical')
                                               && !$delivery->hasTechnicalApproval();
            $delivery->is_fully_completed = $delivery->isFullyCompleted();

            return $delivery;
        });
    }

    /**
     * جلب المستخدمين المسموح برؤية تسليماتهم
     */
    public function getFilteredUsersForCurrentUser(User $currentUser): Collection
    {
        // الإدارة العليا - ترى جميع المستخدمين
        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager', 'operations_manager'])) {
            return User::whereDoesntHave('roles', function($query) {
                    $query->where('name', 'company_manager');
                })
                ->where('employee_status', 'active')
                ->select('id', 'name', 'email', 'department')
                ->orderBy('name')
                ->get();
        }

        $globalLevel = $this->getCurrentUserHierarchyLevel($currentUser);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        if (!$currentUser->department) {
            return collect();
        }

        // فحص صحة ربط الأدوار
        if (!$this->hasValidRoleMapping($currentUser)) {
            return collect([$currentUser]); // المستخدم نفسه فقط
        }

        // المستوى العالي (5 فما فوق) - جميع المستخدمين
        if ($globalLevel && $globalLevel >= 5) {
            $availableRoleIds = RoleHierarchy::where('hierarchy_level', '<=', $globalLevel)
                ->pluck('role_id')
                ->toArray();

            return User::whereHas('roles', function($query) use ($availableRoleIds) {
                    $query->whereIn('id', $availableRoleIds);
                })
                ->whereDoesntHave('roles', function($query) {
                    $query->where('name', 'company_manager');
                })
                ->where('employee_status', 'active')
                ->select('id', 'name', 'email', 'department')
                ->orderBy('name')
                ->get();
        }

        // المستويات المتوسطة (3-4) - نفس القسم فقط
        if ($departmentLevel && $departmentLevel >= 3) {
            $departmentRoleIds = DepartmentRole::where('department_name', $currentUser->department)
                ->where('hierarchy_level', '<=', $departmentLevel)
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

            // للمستوى 3 - فلترة حسب الفريق
            if ($departmentLevel == 3) {
                $teamUserIds = $this->getTeamMemberIds($currentUser);
                $users = $users->whereIn('id', $teamUserIds->toArray());
            }

            return $users;
        }

        // المستوى الأقل - المستخدم نفسه فقط
        return collect([$currentUser]);
    }

    /**
     * جلب المشاريع المسموح برؤيتها مع تطبيق الفلاتر
     */
    public function getFilteredProjectsForCurrentUser(User $currentUser, array $filters = []): Collection
    {
        // الإدارة العليا - ترى جميع المشاريع
        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager', 'operations_manager'])) {
            $query = Project::select('id', 'name', 'code', 'status', 'start_date', 'client_agreed_delivery_date', 'team_delivery_date', 'is_urgent', 'client_id')
                ->with(['client:id,name']);

            // تطبيق الفلاتر
            $query = $this->applyFiltersToProjectsQuery($query, $filters);

            return $query->orderBy('name')->get();
        }

        // باقي المستخدمين - المشاريع التي لهم تسليمات فيها (حسب الهرمية)
        $allowedUserIds = $this->getFilteredUsersForCurrentUser($currentUser)->pluck('id')->toArray();

        if (empty($allowedUserIds)) {
            return collect();
        }

        $query = Project::whereHas('serviceParticipants', function($query) use ($allowedUserIds) {
                $query->whereIn('user_id', $allowedUserIds);
            })
            ->select('id', 'name', 'code', 'status', 'start_date', 'client_agreed_delivery_date', 'team_delivery_date', 'is_urgent', 'client_id')
            ->with(['client:id,name']);

        // تطبيق الفلاتر
        $query = $this->applyFiltersToProjectsQuery($query, $filters);

        return $query->orderBy('name')->get();
    }

    /**
     * جلب الخدمات المسموح برؤيتها
     */
    public function getFilteredServicesForCurrentUser(User $currentUser): Collection
    {
        // الإدارة العليا - ترى جميع الخدمات
        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager', 'operations_manager'])) {
            return CompanyService::select('id', 'name', 'department')
                ->orderBy('name')
                ->get();
        }

        $globalLevel = $this->getCurrentUserHierarchyLevel($currentUser);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        // المستوى العالي - جميع الخدمات
        if ($globalLevel && $globalLevel >= 5) {
            return CompanyService::select('id', 'name', 'department')
                ->orderBy('name')
                ->get();
        }

        // المستويات المتوسطة - خدمات القسم + الخدمات الجرافيكية
        if ($currentUser->department && $departmentLevel && $departmentLevel >= 3) {
            return CompanyService::where(function($query) use ($currentUser) {
                $query->where('department', $currentUser->department)
                      ->orWhere(function($subQuery) {
                          $subQuery->where('name', 'LIKE', '%جرافيك%')
                                   ->orWhere('name', 'LIKE', '%تصميم%')
                                   ->orWhere('name', 'LIKE', '%graphic%')
                                   ->orWhere('name', 'LIKE', '%design%');
                      });
            })->select('id', 'name', 'department')
            ->orderBy('name')
            ->get();
        }

        // المستوى الأقل - الخدمات الجرافيكية فقط
        return CompanyService::where(function($query) {
            $query->where('name', 'LIKE', '%جرافيك%')
                  ->orWhere('name', 'LIKE', '%تصميم%')
                  ->orWhere('name', 'LIKE', '%graphic%')
                  ->orWhere('name', 'LIKE', '%design%');
        })->select('id', 'name', 'department')
        ->orderBy('name')
        ->get();
    }

    /**
     * تأكيد استلام تسليمة
     */
    public function acknowledgeDelivery(int $deliveryId, User $currentUser): array
    {
        $delivery = ProjectServiceUser::find($deliveryId);

        if (!$delivery) {
            return [
                'success' => false,
                'message' => 'التسليمة غير موجودة'
            ];
        }

        if (!$this->canUserAcknowledge($delivery, $currentUser)) {
            return [
                'success' => false,
                'message' => 'غير مصرح لك بتأكيد هذه التسليمة'
            ];
        }

        $delivery->acknowledge();

        return [
            'success' => true,
            'message' => 'تم تأكيد الاستلام بنجاح'
        ];
    }

    /**
     * إلغاء تأكيد الاستلام
     */
    public function unacknowledgeDelivery(int $deliveryId, User $currentUser): array
    {
        $delivery = ProjectServiceUser::find($deliveryId);

        if (!$delivery) {
            return [
                'success' => false,
                'message' => 'التسليمة غير موجودة'
            ];
        }

        if (!$this->canUserUnacknowledge($delivery, $currentUser)) {
            return [
                'success' => false,
                'message' => 'غير مصرح لك بإلغاء تأكيد هذه التسليمة'
            ];
        }

        $delivery->unacknowledge();

        return [
            'success' => true,
            'message' => 'تم إلغاء تأكيد الاستلام بنجاح'
        ];
    }

    /**
     * التحقق من صلاحية تأكيد التسليمة
     */
    private function canUserAcknowledge($delivery, User $currentUser): bool
    {
        // المالك يمكنه تأكيد تسليمته
        if ($delivery->user_id === $currentUser->id) {
            return true;
        }

        // الإدارة العليا يمكنها تأكيد أي تسليمة
        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager', 'operations_manager'])) {
            return true;
        }

        // المدراء يمكنهم تأكيد تسليمات مرؤوسيهم
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        if ($departmentLevel && $departmentLevel >= 4) {
            // مدير القسم يمكنه تأكيد تسليمات قسمه
            return $delivery->user->department === $currentUser->department;
        }

        if ($departmentLevel && $departmentLevel == 3) {
            // Team Leader يمكنه تأكيد تسليمات فريقه
            $teamUserIds = $this->getTeamMemberIds($currentUser);
            return $teamUserIds->contains($delivery->user_id);
        }

        return false;
    }

    /**
     * التحقق من صلاحية إلغاء تأكيد التسليمة
     */
    private function canUserUnacknowledge($delivery, User $currentUser): bool
    {
        // نفس منطق التأكيد
        return $this->canUserAcknowledge($delivery, $currentUser);
    }

    /**
     * الحصول على المستوى الهرمي للمستخدم
     */
    private function getCurrentUserHierarchyLevel(User $user): ?int
    {
        return RoleHierarchy::getUserMaxHierarchyLevel($user);
    }

    /**
     * التحقق من صحة ربط أدوار المستخدم بقسمه
     */
    private function hasValidRoleMapping(User $user): bool
    {
        if ($user->hasRole(['company_manager', 'hr', 'project_manager', 'operations_manager'])) {
            return true;
        }

        if (!$user->department) {
            return false;
        }

        $userRoleIds = $user->roles->pluck('id')->toArray();

        foreach ($userRoleIds as $roleId) {
            if (!DepartmentRole::mappingExists($user->department, $roleId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * جلب معرفات أعضاء الفريق
     */
    private function getTeamMemberIds(User $user): Collection
    {
        $currentTeamId = $user->current_team_id;

        // البحث عن فريق يملكه المستخدم إذا لم يكن له فريق حالي
        if (!$currentTeamId) {
            $ownedTeam = DB::table('teams')
                ->where('user_id', $user->id)
                ->first();
            $currentTeamId = $ownedTeam ? $ownedTeam->id : null;
        }

        if (!$currentTeamId) {
            return collect([$user->id]);
        }

        // جلب أعضاء الفريق
        $teamUserIds = collect([$user->id]); // يشمل نفسه

        // المستخدمين الذين فريقهم الحالي هو نفس الفريق
        $directTeamMembers = User::where('current_team_id', $currentTeamId)
            ->pluck('id');
        $teamUserIds = $teamUserIds->merge($directTeamMembers);

        // المستخدمين أعضاء في الفريق من جدول team_user
        $teamMembers = DB::table('team_user')
            ->where('team_id', $currentTeamId)
            ->pluck('user_id');
        $teamUserIds = $teamUserIds->merge($teamMembers);

        return $teamUserIds->unique();
    }

    /**
     * جلب معلومات فريق المستخدم
     */
    private function getUserTeamInfo(User $user): array
    {
        $teamName = 'غير محدد';

        // البحث في الفريق الحالي
        if ($user->current_team_id) {
            $currentTeam = Team::find($user->current_team_id);
            if ($currentTeam) {
                $teamName = $currentTeam->name;
            }
        } else {
            // البحث في عضوية الفرق
            $userTeam = DB::table('team_user')
                ->join('teams', 'team_user.team_id', '=', 'teams.id')
                ->where('team_user.user_id', $user->id)
                ->select('teams.name as team_name')
                ->first();

            if ($userTeam) {
                $teamName = $userTeam->team_name;
            }
        }

        return [
            'name' => $teamName,
            'hierarchy_level' => $user->hierarchy_level ?? 0,
        ];
    }

    /**
     * الحصول على الرولز الحقيقية للمستخدم
     */
    private function getUserRealRoles(User $user): string
    {
        if (!$user->roles || $user->roles->isEmpty()) {
            return 'غير محدد';
        }

        // جلب أسماء الرولز كما هي
        $roleNames = $user->roles->pluck('name')->filter()->unique();

        if ($roleNames->isEmpty()) {
            return 'غير محدد';
        }

        // إرجاع الرولز مفصولة بفاصلة
        return $roleNames->join('، ');
    }


    /**
     * Get my personal deliveries
     */
    public function getMyDeliveries($currentUser, $filters = [])
    {
        $query = ProjectServiceUser::with([
            'project' => function($query) {
                $query->with(['client', 'projectDates']);
            },
            'service',
            'user.roles:id,name',
            'user',
            'team',
            'administrativeApprover:id,name',
            'technicalApprover:id,name'
        ])
        ->where('user_id', $currentUser->id)
        ->whereHas('project', function ($query) {
            $query->whereNull('deleted_at');
        });

        // Apply date filters
        if (!empty($filters['date_filter'])) {
            $this->applyDateFiltersToQuery($query, $filters);
        }

        // Apply other filters if needed
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['acknowledgment_status']) && $filters['acknowledgment_status'] !== '') {
            $query->where('is_acknowledged', (bool) $filters['acknowledgment_status']);
        }

        $deliveries = $query->orderBy('created_at', 'desc')->get();

        // إضافة البيانات الإضافية لكل تسليمة
        foreach ($deliveries as $delivery) {
            $this->enhanceDeliveryWithAdditionalData($delivery, $currentUser);
        }

        return $deliveries;
    }

    /**
     * Get user hierarchy level
     */
    public function getUserHierarchyLevel($currentUser)
    {
        // Get global hierarchy level
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($currentUser);

        // Get department hierarchy level
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        // Return the maximum level
        return max($globalLevel ?? 0, $departmentLevel ?? 0);
    }

    /**
     * Apply date filters to query
     */
    private function applyDateFiltersToQuery($query, $filters)
    {
        $dateFilter = $filters['date_filter'] ?? null;

        switch ($dateFilter) {
            case 'current_week':
                $startOfWeek = Carbon::now()->startOfWeek();
                $endOfWeek = Carbon::now()->endOfWeek();
                $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                break;

            case 'last_week':
                $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
                $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();
                $query->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek]);
                break;

            case 'current_month':
                $startOfMonth = Carbon::now()->startOfMonth();
                $endOfMonth = Carbon::now()->endOfMonth();
                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                break;

            case 'last_month':
                $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
                $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
                $query->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth]);
                break;

            case 'custom':
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $startDate = Carbon::parse($filters['start_date'])->startOfDay();
                    $endDate = Carbon::parse($filters['end_date'])->endOfDay();
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
                break;
        }
    }

    /**
     * Enhance delivery with additional data
     */
    private function enhanceDeliveryWithAdditionalData($delivery, $currentUser)
    {
        // Add deadline status
        if ($delivery->deadline) {
            $now = Carbon::now();
            $deadline = Carbon::parse($delivery->deadline);
            $daysRemaining = $now->diffInDays($deadline, false);

            $delivery->days_remaining = $daysRemaining;

            $delivery->days_remaining = null;
        }

        // Add permission flags
        $delivery->can_acknowledge = !$delivery->is_acknowledged;
        $delivery->can_unacknowledge = $delivery->is_acknowledged;

        // إضافة معلومات الاعتمادات الجديدة
        $delivery->approval_status = $delivery->getApprovalStatus();
        $delivery->required_approvals = $delivery->getRequiredApprovals();
        $delivery->can_approve_administrative = $delivery->isDelivered()
                                                && $delivery->isAcknowledged()
                                                && $delivery->canUserApprove($currentUser->id, 'administrative')
                                                && !$delivery->hasAdministrativeApproval();
        $delivery->can_approve_technical = $delivery->isDelivered()
                                           && $delivery->isAcknowledged()
                                           && $delivery->canUserApprove($currentUser->id, 'technical')
                                           && !$delivery->hasTechnicalApproval();
        $delivery->is_fully_completed = $delivery->isFullyCompleted();

        // Add user real roles info if not already present
        if (!isset($delivery->user->hierarchy_title)) {
            $delivery->user->hierarchy_title = $this->getUserRealRoles($delivery->user);
        }

        // Add team info if not already present
        if (!isset($delivery->user->team_info)) {
            $delivery->user->team_info = $this->getUserTeamInfo($delivery->user);
        }
    }

    /**
     * تطبيق الفلاتر على استعلام المشاريع
     */
    private function applyFiltersToProjectsQuery($query, array $filters = [])
    {
        // فلتر المشروع المحدد
        if (isset($filters['project_id']) && $filters['project_id']) {
            $query->where('id', $filters['project_id']);
        }

        // تحديد نوع التاريخ للفلترة (team أو client)
        $dateType = $filters['date_type'] ?? 'team';
        $dateColumn = $dateType === 'client' ? 'client_agreed_delivery_date' : 'team_delivery_date';

        // فلتر التاريخ - تاريخ بداية المشروع
        if (isset($filters['start_date'])) {
            $query->where($dateColumn, '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where($dateColumn, '<=', $filters['end_date']);
        }

        // فلتر المشاريع التي لها تسليمات حسب المستخدم أو الخدمة أو حالة التأكيد
        if (isset($filters['user_id']) || isset($filters['service_id']) ||
            isset($filters['acknowledgment_status'])) {

            $query->whereHas('serviceParticipants', function($subQuery) use ($filters) {

                // فلتر حسب المستخدم
                if (isset($filters['user_id']) && $filters['user_id']) {
                    $subQuery->where('user_id', $filters['user_id']);
                }

                // فلتر حسب الخدمة
                if (isset($filters['service_id']) && $filters['service_id']) {
                    $subQuery->where('service_id', $filters['service_id']);
                }

                // فلتر حسب حالة التأكيد
                if (isset($filters['acknowledgment_status']) && $filters['acknowledgment_status'] !== '') {
                    $isAcknowledged = $filters['acknowledgment_status'] === '1';
                    $subQuery->where('is_acknowledged', $isAcknowledged);
                }
            });
        }

        return $query;
    }
}
