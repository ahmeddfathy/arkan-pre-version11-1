<?php

namespace App\Services;

use App\Models\PermissionRequest;
use App\Services\Notifications\ManagerPermissionNotificationService;
use App\Services\Notifications\EmployeePermissionNotificationService;

class NotificationPermissionService
{
    protected $managerNotificationService;
    protected $employeeNotificationService;

    public function __construct(
        ManagerPermissionNotificationService $managerNotificationService,
        EmployeePermissionNotificationService $employeeNotificationService
    ) {
        $this->managerNotificationService = $managerNotificationService;
        $this->employeeNotificationService = $employeeNotificationService;
    }

    public function createPermissionRequestNotification(PermissionRequest $request): void
    {
        $message = "قام {$request->user->name} بتقديم طلب استئذان جديد";

        if ($request->user && $request->user->currentTeam && $request->user->currentTeam->owner) {
            $this->managerNotificationService->notifyManagers($request, 'new_permission_request', $message, false);
        }

        $this->managerNotificationService->notifyManagers($request, 'new_permission_request', $message, true);
    }

    public function notifyPermissionModified(PermissionRequest $request): void
    {
        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_modified');

        $message = "قام {$request->user->name} بتعديل طلب الاستئذان";
        $this->managerNotificationService->notifyManagers($request, 'permission_request_modified', $message, false);
        $this->managerNotificationService->notifyManagers($request, 'permission_request_modified', $message, true);
    }

    public function createPermissionStatusUpdateNotification(PermissionRequest $request): void
    {
        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_status_update');

        $statusArabic = [
            'approved' => 'تمت الموافقة على',
            'rejected' => 'تم رفض',
            'pending' => 'في انتظار الرد على'
        ][$request->status];

        $message = "{$statusArabic} طلب الاستئذان الخاص بك";
        $data = [
            'message' => $message,
            'status' => $request->status,
            'manager_status' => $request->manager_status,
            'hr_status' => $request->hr_status,
            'manager_rejection_reason' => $request->manager_rejection_reason,
            'hr_rejection_reason' => $request->hr_rejection_reason,
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'permission_request_status_update', $data);
    }

    public function notifyReturnStatus(PermissionRequest $request): void
    {
        $returnStatus = 'لم يتم تحديد حالة العودة';

        if ($request->returned_on_time === true) {
            $returnStatus = 'عاد في الوقت المحدد';
        } elseif ($request->returned_on_time === 2) {
            $returnStatus = 'تأخر عن موعد العودة';
        }

        $message = "تم تحديث حالة العودة: {$returnStatus}";
        $data = [
            'message' => $message,
            'return_status' => $request->returned_on_time
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'return_status_update', $data);
    }

    public function notifyPermissionDeleted(PermissionRequest $request): void
    {
        $message = "قام {$request->user->name} بحذف طلب الاستئذان";

        if ($request->user && $request->user->currentTeam && $request->user->currentTeam->owner) {
            $this->managerNotificationService->notifyManagers($request, 'permission_request_deleted', $message, false);
        }

        $this->managerNotificationService->notifyManagers($request, 'permission_request_deleted', $message, true);

        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_status_update');
        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_modified');
    }

    public function notifyManagerStatusUpdate(PermissionRequest $request): void
    {
        $this->employeeNotificationService->deleteExistingNotifications($request, 'manager_response_update');

        $statusArabic = [
            'approved' => 'تمت الموافقة على',
            'rejected' => 'تم رفض',
            'pending' => 'في انتظار الرد على'
        ][$request->manager_status];

        $message = "{$statusArabic} طلب الاستئذان من قبل المدير";

        $data = [
            'message' => $message,
            'status' => $request->manager_status,
            'rejection_reason' => $request->manager_rejection_reason,
            'is_manager_response' => true
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'manager_response_update', $data);
    }

    public function notifyManagerResponseDeleted(PermissionRequest $request): void
    {
        $message = "تم حذف الرد على طلب الاستئذان";

        $data = [
            'message' => $message,
            'status' => 'pending'
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'manager_response_deleted', $data);
    }

    public function notifyHRStatusUpdate(PermissionRequest $request): void
    {
        $this->employeeNotificationService->deleteExistingNotifications($request, 'hr_response_update');

        $statusArabic = [
            'approved' => 'تمت الموافقة على',
            'rejected' => 'تم رفض',
            'pending' => 'في انتظار الرد على'
        ][$request->hr_status];

        $message = "{$statusArabic} طلب الاستئذان من قبل HR";

        $data = [
            'message' => $message,
            'status' => $request->hr_status,
            'rejection_reason' => $request->hr_rejection_reason,
            'is_hr_response' => true
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'hr_response_update', $data);
    }

    public function notifyStatusReset(PermissionRequest $request, string $type): void
    {
        if ($type === 'manager') {
            $this->employeeNotificationService->deleteExistingNotifications($request, 'manager_response_update');
            $this->employeeNotificationService->deleteExistingNotifications($request, 'manager_status_reset');
        } else {
            $this->employeeNotificationService->deleteExistingNotifications($request, 'hr_response_update');
            $this->employeeNotificationService->deleteExistingNotifications($request, 'hr_status_reset');
        }

        $roleType = $type === 'manager' ? 'المدير' : 'HR';
        $message = "تم إعادة تعيين رد {$roleType} على طلب الاستئذان";

        $data = [
            'message' => $message,
            'status' => 'pending',
            'is_reset' => true,
            'reset_by' => $type
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'status_reset', $data);
    }
}
