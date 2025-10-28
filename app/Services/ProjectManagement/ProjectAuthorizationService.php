<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\User;
use App\Models\DepartmentRole;
use App\Services\Auth\RoleCheckService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectAuthorizationService
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * Check if user has access to a project
     */
    public function checkProjectAccess(Project $project, $userId = null)
    {
        $userId = $userId ?: Auth::id();
        $user = Auth::user();

        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager']);

        if ($isAdmin) {
            return [
                'has_access' => true,
                'is_admin' => true,
                'access_type' => 'admin'
            ];
        }

        // Check if user is part of this project
        $userProjectIds = DB::table('project_service_user')
            ->where('user_id', $userId)
            ->pluck('project_id')
            ->toArray();

        $hasAccess = in_array($project->id, $userProjectIds);

        return [
            'has_access' => $hasAccess,
            'is_admin' => false,
            'access_type' => $hasAccess ? 'member' : 'none'
        ];
    }

    /**
     * Check if user has access to project analytics
     */
    public function checkAnalyticsAccess(Project $project, $userId = null)
    {
        $userId = $userId ?: Auth::id();
        $accessInfo = $this->checkProjectAccess($project, $userId);

        if (!$accessInfo['has_access']) {
            return [
                'has_access' => false,
                'message' => 'غير مسموح لك بعرض إحصائيات هذا المشروع'
            ];
        }

        return [
            'has_access' => true,
            'is_admin' => $accessInfo['is_admin'],
            'access_type' => $accessInfo['access_type']
        ];
    }

    /**
     * Check if user can view employee performance
     */
    public function checkEmployeePerformanceAccess(Project $project, $targetUserId, $currentUserId = null)
    {
        $currentUserId = $currentUserId ?: Auth::id();
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager']);

        // Admin can view anyone's performance
        if ($isAdmin) {
            return [
                'has_access' => true,
                'is_admin' => true,
                'message' => null
            ];
        }

        // Users can view their own performance
        if ($currentUserId == $targetUserId) {
            // But still need to check if they're part of the project
            $accessInfo = $this->checkProjectAccess($project, $currentUserId);

            if (!$accessInfo['has_access']) {
                return [
                    'has_access' => false,
                    'is_admin' => false,
                    'message' => 'غير مسموح لك بعرض هذه المعلومات'
                ];
            }

            return [
                'has_access' => true,
                'is_admin' => false,
                'message' => null
            ];
        }

        // Users can't view other people's performance
        return [
            'has_access' => false,
            'is_admin' => false,
            'message' => 'غير مسموح لك بعرض هذه المعلومات'
        ];
    }

    /**
     * Check if target employee is part of the project
     */
    public function checkEmployeeProjectMembership(Project $project, $userId)
    {
        $isProjectMember = DB::table('project_service_user')
            ->where('project_id', $project->id)
            ->where('user_id', $userId)
            ->exists();

        if (!$isProjectMember) {
            return [
                'is_member' => false,
                'message' => 'الموظف ليس عضواً في هذا المشروع'
            ];
        }

        return [
            'is_member' => true,
            'message' => null
        ];
    }

    /**
     * Check if user has sales employee role
     */
    public function checkSalesEmployeeRole()
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            return [
                'has_role' => false,
                'message' => 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.'
            ];
        }

        return [
            'has_role' => true,
            'message' => null
        ];
    }

    /**
     * Check if user can modify note (owner or admin)
     */
    public function checkNoteModificationAccess($noteUserId, $currentUserId = null)
    {
        $currentUserId = $currentUserId ?: Auth::id();
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager']);

        if ($noteUserId === $currentUserId || $isAdmin) {
            return [
                'can_modify' => true,
                'is_owner' => $noteUserId === $currentUserId,
                'is_admin' => $isAdmin,
                'message' => null
            ];
        }

        return [
            'can_modify' => false,
            'is_owner' => false,
            'is_admin' => false,
            'message' => 'غير مسموح لك بتعديل هذه الملاحظة'
        ];
    }

    /**
     * Get user project IDs
     */
    public function getUserProjectIds($userId = null)
    {
        $userId = $userId ?: Auth::id();

        return DB::table('project_service_user')
            ->where('user_id', $userId)
            ->pluck('project_id')
            ->toArray();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin($userId = null)
    {
        return $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager']);
    }

    /**
     * Get access summary for user and project
     */
    public function getAccessSummary(Project $project, $userId = null)
    {
        $userId = $userId ?: Auth::id();
        $isAdmin = $this->isAdmin();
        $userProjectIds = $this->getUserProjectIds($userId);
        $hasProjectAccess = in_array($project->id, $userProjectIds);

        return [
            'user_id' => $userId,
            'project_id' => $project->id,
            'is_admin' => $isAdmin,
            'has_project_access' => $hasProjectAccess || $isAdmin,
            'can_view_analytics' => $hasProjectAccess || $isAdmin,
            'can_modify_project' => $this->roleCheckService->userHasRole('sales_employee'),
            'access_level' => $isAdmin ? 'admin' : ($hasProjectAccess ? 'member' : 'none')
        ];
    }

    /**
     * Authorize or abort with message
     */
    public function authorizeOrAbort($condition, $message = 'غير مسموح لك بتنفيذ هذا الإجراء', $statusCode = 403)
    {
        if (!$condition) {
            abort($statusCode, $message);
        }

        return true;
    }

    /**
     * Check project analytics access and abort if no access
     */
    public function authorizeProjectAnalytics(Project $project, $userId = null)
    {
        $accessInfo = $this->checkAnalyticsAccess($project, $userId);

        if (!$accessInfo['has_access']) {
            abort(403, $accessInfo['message']);
        }

        return $accessInfo;
    }

    /**
     * Check employee performance access and abort if no access
     */
    public function authorizeEmployeePerformance(Project $project, $targetUserId, $currentUserId = null)
    {
        $accessInfo = $this->checkEmployeePerformanceAccess($project, $targetUserId, $currentUserId);

        if (!$accessInfo['has_access']) {
            abort(403, $accessInfo['message']);
        }

        // Also check if target employee is part of the project
        $membershipInfo = $this->checkEmployeeProjectMembership($project, $targetUserId);

        if (!$membershipInfo['is_member']) {
            abort(404, $membershipInfo['message']);
        }

        return $accessInfo;
    }

            /**
     * فحص صلاحيات إدارة المشاركين في المشروع
     */
    public function canManageProjectParticipants(Project $project, $userId = null)
    {
        $userId = $userId ?: Auth::id();
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        // 1. فحص إذا كان operation assistant
        if ($this->roleCheckService->userHasRole('operation_assistant')) {
            return true;
        }

        // 2. فحص إذا كان company_manager
        if ($this->roleCheckService->userHasRole('company_manager')) {
            return true;
        }

        // 3. فحص إذا كان project_manager ومضاف للمشروع
        if ($this->roleCheckService->userHasRole('project_manager')) {
            $isInProject = DB::table('project_service_user')
                ->where('project_id', $project->id)
                ->where('user_id', $userId)
                ->exists();

            if ($isInProject) {
                return true;
            }
        }

        // 4. فحص إذا كان يملك الرول رقم 2 في القسم ومضاف للمشروع ومالك تيم
        if ($user->department) {
            // جلب DepartmentRole للرول رقم 2 في قسم المستخدم
            $departmentRole = DepartmentRole::where('department_name', $user->department)
                ->where('role_id', 2)
                ->first();

            if ($departmentRole) {
                // التحقق من أن المستخدم يملك هذا الرول
                $hasRole2 = $user->roles->contains('id', 2);

                if ($hasRole2) {
                    // التحقق من أنه مضاف للمشروع
                    $isInProject = DB::table('project_service_user')
                        ->where('project_id', $project->id)
                        ->where('user_id', $userId)
                        ->exists();

                    if ($isInProject) {
                        // التحقق من أنه مالك تيم
                        $isTeamOwner = DB::table('teams')
                            ->where('user_id', $userId)
                            ->exists();

                        if ($isTeamOwner) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * فحص إذا كان يمكن عرض اقتراح الفريق
     */
    public function canViewTeamSuggestion($userId = null)
    {
        return $this->roleCheckService->userHasRole('operation_assistant');
    }
}
