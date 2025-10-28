<?php

namespace App\Policies;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BadgePolicy
{
    /**
     * التحقق مما إذا كان المستخدم يمكنه عرض إحصائيات الشارات
     */
    public function viewBadgeStatistics(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'hr', 'manager']);
    }

    /**
     * التحقق مما إذا كان المستخدم يمكنه عرض سجل شارات مستخدم آخر
     */
    public function viewOtherUserBadges(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'hr', 'manager']);
    }

    /**
     * التحقق مما إذا كان المستخدم يمكنه إدارة الشارات
     */
    public function manageBadges(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin' , 'hr']);
    }

    /**
     * التحقق مما إذا كان المستخدم يمكنه إضافة نقاط لمستخدمين آخرين
     */
    public function addPointsToUsers(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }
}
