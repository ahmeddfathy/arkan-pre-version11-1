<?php

namespace App\Services\EmployeeErrorController;

use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\ProjectServiceUser;
use App\Models\User;
use App\Models\RoleHierarchy;
use App\Models\DepartmentRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeErrorValidationService
{
    /**
     * التحقق من صلاحية تسجيل خطأ
     */
    public function canReportError(): bool
    {
        $user = Auth::user();

        // التحقق من المستوى الهرمي
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        // إذا كان المستوى الهرمي 2 أو أعلى، يمكنه تسجيل الأخطاء
        return ($globalLevel && $globalLevel >= 2) || ($departmentLevel && $departmentLevel >= 2);
    }

    /**
     * التحقق من صلاحية تعديل خطأ
     */
    public function canEditError($error): bool
    {
        $user = Auth::user();

        // من سجل الخطأ يمكنه تعديله
        if ($error->reported_by === $user->id) {
            return true;
        }

        // التحقق من المستوى الهرمي
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        if ($globalLevel && $globalLevel >= 3) {
            return $this->canAccessHierarchicalError($user, $error, $globalLevel);
        }

        if ($departmentLevel && $departmentLevel >= 3) {
            return $this->canAccessDepartmentHierarchicalError($user, $error, $departmentLevel);
        }

        return false;
    }

    /**
     * التحقق من صلاحية حذف خطأ
     */
    public function canDeleteError($error): bool
    {
        $user = Auth::user();

        // من سجل الخطأ يمكنه حذفه
        if ($error->reported_by === $user->id) {
            return true;
        }

        // التحقق من المستوى الهرمي - مستوى 4 أو أعلى للحذف
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        if ($globalLevel && $globalLevel >= 4) {
            return $this->canAccessHierarchicalError($user, $error, $globalLevel);
        }

        if ($departmentLevel && $departmentLevel >= 4) {
            return $this->canAccessDepartmentHierarchicalError($user, $error, $departmentLevel);
        }

        return false;
    }

    /**
     * التحقق من صحة بيانات الخطأ
     */
    public function validateErrorData(array $data): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = 'عنوان الخطأ مطلوب';
        }

        if (empty($data['description'])) {
            $errors[] = 'وصف الخطأ مطلوب';
        }

        if (isset($data['error_category'])) {
            $validCategories = ['quality', 'deadline', 'communication', 'technical', 'procedural', 'other'];
            if (!in_array($data['error_category'], $validCategories)) {
                $errors[] = 'تصنيف الخطأ غير صالح';
            }
        }

        if (isset($data['error_type'])) {
            $validTypes = ['normal', 'critical'];
            if (!in_array($data['error_type'], $validTypes)) {
                $errors[] = 'نوع الخطأ غير صالح';
            }
        }

        return $errors;
    }

    /**
     * التحقق من وجود الـ errorable
     */
    public function validateErrorable($errorableType, $errorableId): ?object
    {
        switch ($errorableType) {
            case 'TaskUser':
            case 'App\Models\TaskUser':
                return TaskUser::find($errorableId);

            case 'TemplateTaskUser':
            case 'App\Models\TemplateTaskUser':
                return TemplateTaskUser::find($errorableId);

            case 'ProjectServiceUser':
            case 'App\Models\ProjectServiceUser':
                return ProjectServiceUser::find($errorableId);

            default:
                return null;
        }
    }

    /**
     * التحقق من وجود المستخدم
     */
    public function validateUser($userId): ?User
    {
        return User::find($userId);
    }

    /**
     * التحقق من عدم تكرار الخطأ
     */
    public function isDuplicateError($userId, $errorableType, $errorableId, $title): bool
    {
        return \App\Models\EmployeeError::where('user_id', $userId)
            ->where('errorable_type', $errorableType)
            ->where('errorable_id', $errorableId)
            ->where('title', $title)
            ->where('created_at', '>=', now()->subHours(24)) // خلال آخر 24 ساعة
            ->exists();
    }

    /**
     * التحقق من حد الأخطاء اليومي
     */
    public function hasReachedDailyLimit($userId, $limit = 10): bool
    {
        $todayErrorsCount = \App\Models\EmployeeError::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->count();

        return $todayErrorsCount >= $limit;
    }

    /**
     * التحقق من وجود أخطاء جوهرية متكررة
     */
    public function hasRecurringCriticalErrors($userId, $threshold = 3): bool
    {
        $criticalErrorsCount = \App\Models\EmployeeError::where('user_id', $userId)
            ->where('error_type', 'critical')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return $criticalErrorsCount >= $threshold;
    }

    /**
     * التحقق من صلاحية عرض الأخطاء
     */
    public function canViewErrors($userId): bool
    {
        $currentUser = Auth::user();

        // المستخدم يمكنه رؤية أخطائه
        if ($currentUser->id == $userId) {
            return true;
        }

        // التحقق من المستوى الهرمي
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($currentUser);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        // إذا كان المستوى الهرمي 2 أو أعلى، يمكنه رؤية الأخطاء
        if ($globalLevel && $globalLevel >= 2) {
            return $this->canViewHierarchicalErrors($currentUser, $userId, $globalLevel);
        }

        if ($departmentLevel && $departmentLevel >= 2) {
            return $this->canViewDepartmentHierarchicalErrors($currentUser, $userId, $departmentLevel);
        }

        return false;
    }

    /**
     * التحقق من صلاحية عرض إحصائيات الفريق
     */
    public function canViewTeamStats(): bool
    {
        $user = Auth::user();

        // التحقق من المستوى الهرمي
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        // إذا كان المستوى الهرمي 3 أو أعلى، يمكنه رؤية إحصائيات الفريق
        return ($globalLevel && $globalLevel >= 3) || ($departmentLevel && $departmentLevel >= 3);
    }

    /**
     * التحقق من إمكانية الوصول لخطأ حسب التسلسل الهرمي العام
     */
    private function canAccessHierarchicalError($user, $error, $userLevel): bool
    {
        $targetUser = User::find($error->user_id);
        if (!$targetUser) return false;

        $targetUserLevel = RoleHierarchy::getUserMaxHierarchyLevel($targetUser);

        // يمكن الوصول إذا كان المستوى الهرمي للمستخدم الحالي أعلى من المستهدف
        return $targetUserLevel === null || $userLevel > $targetUserLevel;
    }

    /**
     * التحقق من إمكانية الوصول لخطأ حسب التسلسل الهرمي للقسم
     */
    private function canAccessDepartmentHierarchicalError($user, $error, $userLevel): bool
    {
        $targetUser = User::find($error->user_id);
        if (!$targetUser) return false;

        // يجب أن يكونوا في نفس القسم
        if ($user->department !== $targetUser->department) {
            return false;
        }

        $targetUserLevel = DepartmentRole::getUserDepartmentHierarchyLevel($targetUser);

        // يمكن الوصول إذا كان المستوى الهرمي للمستخدم الحالي أعلى من المستهدف
        return $targetUserLevel === null || $userLevel > $targetUserLevel;
    }

    /**
     * التحقق من إمكانية رؤية الأخطاء حسب التسلسل الهرمي العام
     */
    private function canViewHierarchicalErrors($currentUser, $targetUserId, $userLevel): bool
    {
        $targetUser = User::find($targetUserId);
        if (!$targetUser) return false;

        $targetUserLevel = RoleHierarchy::getUserMaxHierarchyLevel($targetUser);

        // يمكن رؤية الأخطاء إذا كان المستوى الهرمي للمستخدم الحالي أعلى من أو مساوي للمستهدف
        return $targetUserLevel === null || $userLevel >= $targetUserLevel;
    }

    /**
     * التحقق من إمكانية رؤية الأخطاء حسب التسلسل الهرمي للقسم
     */
    private function canViewDepartmentHierarchicalErrors($currentUser, $targetUserId, $userLevel): bool
    {
        $targetUser = User::find($targetUserId);
        if (!$targetUser) return false;

        // يجب أن يكونوا في نفس القسم
        if ($currentUser->department !== $targetUser->department) {
            return false;
        }

        $targetUserLevel = DepartmentRole::getUserDepartmentHierarchyLevel($targetUser);

        // يمكن رؤية الأخطاء إذا كان المستوى الهرمي للمستخدم الحالي أعلى من أو مساوي للمستهدف
        return $targetUserLevel === null || $userLevel >= $targetUserLevel;
    }

    /**
     * الحصول على قائمة الموظفين الذين يمكن إضافة أخطاء عليهم حسب النظام الهرمي
     */
    public function getUsersCanAddErrorsTo(): \Illuminate\Support\Collection
    {
        $currentUser = Auth::user();
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($currentUser);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        // إذا لم يكن لديه مستوى هرمي، لا يمكنه إضافة أخطاء
        if (!$globalLevel && !$departmentLevel) {
            return collect();
        }

        // جلب جميع المستخدمين النشطين
        $allUsers = User::where('employee_status', 'active')->get();
        $availableUsers = collect();

        Log::info('EmployeeErrorValidationService - Current User Info', [
            'user_id' => $currentUser->id,
            'user_name' => $currentUser->name,
            'global_level' => $globalLevel,
            'department_level' => $departmentLevel,
            'department' => $currentUser->department
        ]);

        foreach ($allUsers as $user) {
            // تخطي المستخدم الحالي
            if ($user->id === $currentUser->id) {
                continue;
            }

            $userGlobalLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);
            $userDepartmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

            $canAddError = false;

            // التحقق من المستوى الهرمي العام (مثل TaskHierarchyService)
            if ($globalLevel && $globalLevel >= 5) {
                // يمكن إضافة خطأ على المستخدمين في مستوى أقل أو مساوي
                if ($userGlobalLevel === null || $userGlobalLevel <= $globalLevel) {
                    $canAddError = true;
                }
            }

            // التحقق من المستوى الهرمي للقسم (فقط المستوى 4+)
            if (!$canAddError && $departmentLevel && $departmentLevel >= 4) {
                if ($user->department === $currentUser->department) {
                    // يمكن إضافة خطأ على المستخدمين في مستوى أقل فقط (وليس مساوي)
                    if ($userDepartmentLevel && $userDepartmentLevel < $departmentLevel) {
                        $canAddError = true;
                    }
                }
            }

            // المستوى 2 العام - يمكن إضافة خطأ على المستوى 1 فقط
            if (!$canAddError && $globalLevel && $globalLevel == 2) {
                if ($user->department === $currentUser->department) {
                    // يمكن إضافة خطأ على المستوى 1 فقط
                    if ($userDepartmentLevel && $userDepartmentLevel == 1) {
                        $canAddError = true;
                    }
                }
            }

            // المستوى 2 القسمي - يمكن إضافة خطأ على المستوى 1 فقط
            if (!$canAddError && $departmentLevel && $departmentLevel == 2) {
                if ($user->department === $currentUser->department) {
                    // يمكن إضافة خطأ على المستوى 1 فقط
                    if ($userDepartmentLevel && $userDepartmentLevel == 1) {
                        $canAddError = true;
                    }
                }
            }

            // المستوى 4 العام - يمكن إضافة خطأ على المستويات 1، 2، 3
            if (!$canAddError && $globalLevel && $globalLevel == 4) {
                if ($user->department === $currentUser->department) {
                    // يمكن إضافة خطأ على المستويات 1، 2، 3
                    if ($userDepartmentLevel && $userDepartmentLevel < 4) {
                        $canAddError = true;
                    }
                }
            }

            // المستوى 3 - فقط أعضاء الفريق (مثل TaskHierarchyService)
            if (!$canAddError && $departmentLevel && $departmentLevel == 3) {
                if ($user->department === $currentUser->department) {
                    // يمكن إضافة خطأ على أعضاء الفريق فقط
                    $canAddError = $this->isUserInSameTeam($user, $currentUser);
                }
            }

            Log::info('EmployeeErrorValidationService - User Check', [
                'target_user_id' => $user->id,
                'target_user_name' => $user->name,
                'target_global_level' => $userGlobalLevel,
                'target_department_level' => $userDepartmentLevel,
                'target_department' => $user->department,
                'current_user_global_level' => $globalLevel,
                'current_user_department_level' => $departmentLevel,
                'current_user_department' => $currentUser->department,
                'can_add_error' => $canAddError,
                'reason' => $canAddError ? 'Allowed' : 'Not allowed - same or higher level'
            ]);

            if ($canAddError) {
                $availableUsers->push($user);
            }
        }

        Log::info('EmployeeErrorValidationService - Final Result', [
            'available_users_count' => $availableUsers->count(),
            'available_users' => $availableUsers->pluck('name')->toArray()
        ]);

        return $availableUsers;
    }

    /**
     * التحقق من أن المستخدمين في نفس الفريق
     */
    private function isUserInSameTeam($user, $currentUser): bool
    {
        $currentTeamId = $currentUser->current_team_id;

        if (!$currentTeamId) {
            $ownedTeam = DB::table('teams')
                ->where('user_id', $currentUser->id)
                ->first();
            $currentTeamId = $ownedTeam ? $ownedTeam->id : null;
        }

        if (!$currentTeamId) {
            return false;
        }

        if ($user->current_team_id === $currentTeamId) {
            return true;
        }

        $isMember = DB::table('team_user')
            ->where('team_id', $currentTeamId)
            ->where('user_id', $user->id)
            ->exists();

        return $isMember;
    }
}

