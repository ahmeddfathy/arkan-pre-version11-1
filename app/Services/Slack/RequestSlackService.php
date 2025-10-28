<?php

namespace App\Services\Slack;

use App\Models\User;

class RequestSlackService extends BaseSlackService
{
    /**
     * إرسال إشعار طلب عمل إضافي
     */
    public function sendOvertimeRequestNotification($request, User $targetUser, User $author, string $action): bool
    {
        $message = $this->buildOvertimeRequestMessage($request, $targetUser, $author, $action);

        // تحديد context بناءً على نوع العملية والرد
        $context = $this->getOvertimeNotificationContext($request, $action, $author);
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($targetUser, $message, $context, true);
    }

    /**
     * إرسال إشعار طلب إذن
     */
    public function sendPermissionRequestNotification($request, User $targetUser, User $author, string $action): bool
    {
        $message = $this->buildPermissionRequestMessage($request, $targetUser, $author, $action);

        // تحديد context بناءً على نوع العملية والرد
        $context = $this->getPermissionNotificationContext($request, $action, $author);
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($targetUser, $message, $context, true);
    }

    /**
     * إرسال إشعار طلب غياب
     */
    public function sendAbsenceRequestNotification($request, User $targetUser, User $author, string $action): bool
    {
        $message = $this->buildAbsenceRequestMessage($request, $targetUser, $author, $action);

        // تحديد context بناءً على نوع العملية والرد
        $context = $this->getAbsenceNotificationContext($request, $action, $author);
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($targetUser, $message, $context, true);
    }

    /**
     * بناء رسالة طلب العمل الإضافي
     */
    private function buildOvertimeRequestMessage($request, User $targetUser, User $author, string $action): array
    {
        $actionEmoji = $this->getActionEmoji($action);
        $actionText = $this->getActionText($action);

        $overtimeDate = is_string($request->overtime_date) ? $request->overtime_date : $request->overtime_date->format('Y-m-d');
        $requestUrl = url("/overtime-requests/{$request->id}");

        return [
            'text' => "إشعار طلب عمل إضافي",
            'blocks' => [
                $this->buildHeader("$actionEmoji $actionText طلب عمل إضافي"),
                $this->buildInfoSection([
                    "*الموظف:*\n" . ($request->user ? $request->user->name : 'غير محدد'),
                    "*التاريخ:*\n$overtimeDate"
                ]),
                $this->buildInfoSection([
                    "*وقت البداية:*\n" . ($request->start_time ?: 'غير محدد'),
                    "*وقت النهاية:*\n" . ($request->end_time ?: 'غير محدد')
                ]),
                $this->buildInfoSection([
                    "*بواسطة:*\n{$author->name}",
                    "*الحالة:*\n" . $this->getStatusText($request, $action)
                ]),
                $this->buildTextSection("*السبب:*\n" . ($request->reason ?: 'لا يوجد سبب')),
                $this->buildActionsSection([
                    $this->buildActionButton('📋 عرض الطلب', $requestUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * بناء رسالة طلب الإذن
     */
    private function buildPermissionRequestMessage($request, User $targetUser, User $author, string $action): array
    {
        $actionEmoji = $this->getActionEmoji($action);
        $actionText = $this->getActionText($action);

        $departureTime = is_string($request->departure_time) ? $request->departure_time : $request->departure_time->format('Y-m-d H:i');
        $returnTime = is_string($request->return_time) ? $request->return_time : $request->return_time->format('Y-m-d H:i');
        $requestUrl = url("/permission-requests/{$request->id}");

        return [
            'text' => "إشعار طلب إذن",
            'blocks' => [
                $this->buildHeader("$actionEmoji $actionText طلب إذن"),
                $this->buildInfoSection([
                    "*الموظف:*\n" . ($request->user ? $request->user->name : 'غير محدد'),
                    "*المدة:*\n" . ($request->minutes_used ?? 0) . " دقيقة"
                ]),
                $this->buildInfoSection([
                    "*وقت المغادرة:*\n$departureTime",
                    "*وقت العودة:*\n$returnTime"
                ]),
                $this->buildInfoSection([
                    "*بواسطة:*\n{$author->name}",
                    "*الحالة:*\n" . $this->getStatusText($request, $action)
                ]),
                $this->buildTextSection("*السبب:*\n" . ($request->reason ?: 'لا يوجد سبب')),
                $this->buildActionsSection([
                    $this->buildActionButton('📋 عرض الطلب', $requestUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * بناء رسالة طلب الغياب
     */
    private function buildAbsenceRequestMessage($request, User $targetUser, User $author, string $action): array
    {
        $actionEmoji = $this->getActionEmoji($action);
        $actionText = $this->getActionText($action);

        $absenceDate = is_string($request->absence_date) ? $request->absence_date : $request->absence_date->format('Y-m-d');
        $requestUrl = url("/absence-requests/{$request->id}");

        return [
            'text' => "إشعار طلب غياب",
            'blocks' => [
                $this->buildHeader("$actionEmoji $actionText طلب غياب"),
                $this->buildInfoSection([
                    "*الموظف:*\n" . ($request->user ? $request->user->name : 'غير محدد'),
                    "*تاريخ الغياب:*\n$absenceDate"
                ]),
                $this->buildInfoSection([
                    "*بواسطة:*\n{$author->name}",
                    "*الحالة:*\n" . $this->getStatusText($request, $action)
                ]),
                $this->buildTextSection("*السبب:*\n" . ($request->reason ?: 'لا يوجد سبب')),
                $this->buildActionsSection([
                    $this->buildActionButton('📋 عرض الطلب', $requestUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * الحصول على رمز الإجراء
     */
    private function getActionEmoji(string $action): string
    {
        switch ($action) {
            case 'created': return '📝';
            case 'approved': return '✅';
            case 'rejected': return '❌';
            case 'modified': return '✏️';
            case 'deleted': return '🗑️';
            case 'reset': return '🔄';
            default: return '📢';
        }
    }

    /**
     * الحصول على نص الإجراء
     */
    private function getActionText(string $action): string
    {
        switch ($action) {
            case 'created': return 'طلب جديد';
            case 'approved': return 'تمت الموافقة على';
            case 'rejected': return 'تم رفض';
            case 'modified': return 'تم تعديل';
            case 'deleted': return 'تم حذف';
            case 'reset': return 'تم إعادة تعيين';
            default: return 'إشعار حول';
        }
    }

    /**
     * الحصول على نص الحالة
     */
    private function getStatusText($request, string $action): string
    {
        switch ($action) {
            case 'approved':
                return '✅ موافق عليه';
            case 'rejected':
                return '❌ مرفوض';
            case 'reset':
                return '🔄 تم إعادة التعيين';
            case 'created':
                return '⏳ قيد الانتظار';
            case 'modified':
                return '✏️ تم التعديل';
            default:
                return '⏳ قيد المراجعة';
        }
    }

        /**
     * تحديد context الإشعار للإجازات بناءً على العملية والمستخدم
     */
    private function getAbsenceNotificationContext($request, string $action, User $author): string
    {
        // التحقق من دور المؤلف لتحديد نوع الرد
        $authorRole = $author->roles->first()?->name;

        // إذا كان العمل إنشاء طلب جديد
        if ($action === 'created') {
            return 'إشعار طلب إجازة';
        }

        // إذا كان رد من HR
        if ($authorRole === 'hr') {
            if ($action === 'approved') {
                return 'إشعار رد HR بالموافقة على الإجازة';
            } elseif ($action === 'rejected') {
                return 'إشعار رد HR برفض الإجازة';
            } else {
                return 'إشعار رد HR على الإجازة';
            }
        }
        // إذا كان رد من مدير
        else {
            if ($action === 'approved') {
                return 'إشعار رد المدير بالموافقة على الإجازة';
            } elseif ($action === 'rejected') {
                return 'إشعار رد المدير برفض الإجازة';
            } else {
                return 'إشعار رد المدير على الإجازة';
            }
        }
    }

    /**
     * تحديد context الإشعار للعمل الإضافي بناءً على العملية والمستخدم
     */
    private function getOvertimeNotificationContext($request, string $action, User $author): string
    {
        $authorRole = $author->roles->first()?->name;

        if ($action === 'created') {
            return 'إشعار طلب عمل إضافي';
        }

        if ($authorRole === 'hr') {
            if ($action === 'approved') {
                return 'إشعار رد HR بالموافقة على العمل الإضافي';
            } elseif ($action === 'rejected') {
                return 'إشعار رد HR برفض العمل الإضافي';
            } else {
                return 'إشعار رد HR على العمل الإضافي';
            }
        } else {
            if ($action === 'approved') {
                return 'إشعار رد المدير بالموافقة على العمل الإضافي';
            } elseif ($action === 'rejected') {
                return 'إشعار رد المدير برفض العمل الإضافي';
            } else {
                return 'إشعار رد المدير على العمل الإضافي';
            }
        }
    }

    /**
     * تحديد context الإشعار للإذن بناءً على العملية والمستخدم
     */
    private function getPermissionNotificationContext($request, string $action, User $author): string
    {
        $authorRole = $author->roles->first()?->name;

        if ($action === 'created') {
            return 'إشعار طلب إذن';
        }

        if ($authorRole === 'hr') {
            if ($action === 'approved') {
                return 'إشعار رد HR بالموافقة على الإذن';
            } elseif ($action === 'rejected') {
                return 'إشعار رد HR برفض الإذن';
            } else {
                return 'إشعار رد HR على الإذن';
            }
        } else {
            if ($action === 'approved') {
                return 'إشعار رد المدير بالموافقة على الإذن';
            } elseif ($action === 'rejected') {
                return 'إشعار رد المدير برفض الإذن';
            } else {
                return 'إشعار رد المدير على الإذن';
            }
        }
    }
}
