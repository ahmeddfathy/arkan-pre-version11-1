<?php

namespace App\Services\PermissionRequest;

use App\Models\PermissionRequest;
use App\Models\User;
use App\Services\NotificationPermissionService;
use App\Services\ViolationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RequestStatusService
{
    protected $notificationService;
    protected $violationService;

    public function __construct(
        NotificationPermissionService $notificationService,
        ViolationService $violationService
    ) {
        $this->notificationService = $notificationService;
        $this->violationService = $violationService;
    }

    public function updateStatus(PermissionRequest $request, array $data): array
    {
        $responseType = $data['response_type'];
        $status = $data['status'];
        $rejectionReason = $status === 'rejected' ? $data['rejection_reason'] : null;
        $user = Auth::user();

        if ($responseType === 'manager' && !auth()->user()->hasPermissionTo('manager_respond_permission_request')) {
            return [
                'success' => false,
                'message' => 'ليس لديك صلاحية الرد على طلبات الاستئذان كمدير'
            ];
        }

        if ($responseType === 'hr' && !auth()->user()->hasPermissionTo('hr_respond_permission_request')) {
            return [
                'success' => false,
                'message' => 'ليس لديك صلاحية الرد على طلبات الاستئذان كموارد بشرية'
            ];
        }

        if ($responseType === 'manager' && $user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
            $request->updateHrStatus($status, $rejectionReason);
        } elseif ($responseType === 'hr' && $user->hasPermissionTo('manager_respond_permission_request')) {
            $request->updateManagerStatus($status, $rejectionReason);
        }

        if ($responseType === 'manager') {
            $request->updateManagerStatus($status, $rejectionReason);
        } elseif ($responseType === 'hr') {
            $request->updateHrStatus($status, $rejectionReason);
        }

        $this->notificationService->createPermissionStatusUpdateNotification($request);

        return ['success' => true];
    }

    public function resetStatus(PermissionRequest $request, string $responseType)
    {
        try {
            $user = Auth::user();

            if ($responseType === 'manager' && !auth()->user()->hasPermissionTo('manager_respond_permission_request')) {
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان كمدير'
                );
            }

            if ($responseType === 'hr' && !auth()->user()->hasPermissionTo('hr_respond_permission_request')) {
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان كموارد بشرية'
                );
            }

            if ($responseType === 'manager' && $user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
                $request->updateHrStatus('pending', null);
            } elseif ($responseType === 'hr' && $user->hasPermissionTo('manager_respond_permission_request')) {
                $request->updateManagerStatus('pending', null);
            }

            if ($responseType === 'manager') {
                $request->updateManagerStatus('pending', null);
                $request->updateFinalStatus();
                $request->save();
                $this->notificationService->notifyManagerResponseDeleted($request);
            } elseif ($responseType === 'hr') {
                $request->updateHrStatus('pending', null);
                $request->updateFinalStatus();
                $request->save();
                $this->notificationService->notifyStatusReset($request, 'hr');
            }

            return $request;
        } catch (\Exception $e) {
            Log::error('Error resetting status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function modifyResponse(PermissionRequest $request, array $data): array
    {
        $user = Auth::user();

        if (isset($data['status'])) {
            $status = $data['status'];
            $rejectionReason = $status === 'rejected' ? ($data['rejection_reason'] ?? null) : null;

            $request->updateManagerStatus($status, $rejectionReason);

            if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
                $request->updateHrStatus($status, $rejectionReason);
            }

            $request->save();

            $this->notificationService->notifyManagerStatusUpdate($request);
        }

        return ['success' => true];
    }

    public function updateReturnStatus(PermissionRequest $request, int $returnStatus): array
    {
        try {
            $now = Carbon::now()->setTimezone('Africa/Cairo');
            $departureTime = Carbon::parse($request->departure_time);
            $returnTime = Carbon::parse($request->return_time);

            $user = User::find($request->user_id);
            if ($user && $user->workShift) {
                $shiftEndTime = Carbon::parse($user->workShift->check_out_time)->setDateFrom($departureTime);
            } else {
                $shiftEndTime = Carbon::parse($departureTime)->setTime(16, 0, 0);
            }


            if ($returnTime->format('H:i') === $shiftEndTime->format('H:i')) {
                $request->returned_on_time = true;
            } else if ($returnStatus == 1) {
                $request->returned_on_time = true;

                if ($now->gt($shiftEndTime)) {
                    Log::info('Employee returned after shift end time - using shift end time', [
                        'request_id' => $request->id,
                        'now' => $now->format('Y-m-d H:i:s'),
                        'shift_end_time' => $shiftEndTime->format('Y-m-d H:i:s')
                    ]);
                } else {
                    Log::info('Employee returned before shift end time - using current time', [
                        'request_id' => $request->id,
                        'now' => $now->format('Y-m-d H:i:s')
                    ]);
                }
            } else if ($returnStatus == 0) {
                $request->returned_on_time = false;
            } else if ($returnStatus == 2) {
                $request->returned_on_time = 2;

                $this->violationService->handleReturnViolation(
                    $request,
                    $returnStatus
                );
            }

            $request->updateActualMinutesUsed();

            $request->save();

            $this->notificationService->notifyReturnStatus($request);

            $message = 'تم تحديث حالة العودة بنجاح';
            if ($returnStatus == 1) {
                $message = 'تم تسجيل عودتك بنجاح';
            } else if ($returnStatus == 2) {
                $message = 'تم تسجيل عدم العودة';
            } else if ($returnStatus == 0) {
                $message = 'تم إعادة تعيين حالة العودة بنجاح';
            }

            return [
                'success' => true,
                'message' => $message,
                'actual_minutes_used' => $request->minutes_used
            ];
        } catch (\Exception $e) {
            Log::error('Error updating return status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة العودة: ' . $e->getMessage()
            ];
        }
    }

    public function canRespond($user = null)
    {
        $user = $user ?? Auth::user();

        if (
            $user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_permission_request')
        ) {
            return true;
        }

        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
            return true;
        }

        return false;
    }
}
