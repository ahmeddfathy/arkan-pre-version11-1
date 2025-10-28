<?php

namespace App\Services\PermissionRequest;

use App\Models\PermissionRequest;
use App\Models\User;
use App\Services\NotificationPermissionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RequestCreationService
{
    protected $validationService;
    protected $notificationService;

    public function __construct(
        RequestValidationService $validationService,
        NotificationPermissionService $notificationService
    ) {
        $this->validationService = $validationService;
        $this->notificationService = $notificationService;
    }

    public function createRequest(array $data): array
    {
        try {
            if (!auth()->user()->hasPermissionTo('create_permission')) {
                return [
                    'success' => false,
                    'message' => 'ليس لديك صلاحية تقديم طلب استئذان'
                ];
            }

            $userId = Auth::id();
            $validation = $this->validationService->validateTimeRequest($userId, $data['departure_time'], $data['return_time']);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            $currentUser = Auth::user();

            $managerStatus = 'pending';
            $hrStatus = 'pending';
            $status = 'pending';

            if ($currentUser->hasRole('hr')) {
                $hrStatus = 'approved';

                if ($currentUser->hasPermissionTo('manager_respond_permission_request')) {
                    $managerStatus = 'approved';
                }
            }

            if ($currentUser->hasAnyRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            }

            $returnTime = Carbon::parse($data['return_time']);
            $user = User::find($userId);

            if ($user && (!$user->teams()->exists() || $user->teams()->where('name', 'HR')->exists())) {
                if ($hrStatus === 'approved') {
                    $status = 'approved';
                } elseif ($hrStatus === 'rejected') {
                    $status = 'rejected';
                }
            } else {
                if ($managerStatus === 'approved' && $hrStatus === 'approved') {
                    $status = 'approved';
                } elseif ($managerStatus === 'rejected' || $hrStatus === 'rejected') {
                    $status = 'rejected';
                }
            }

            $workShift = $user->workShift;
            $returnedOnTime = false;

            if ($workShift) {
                $shiftEndTime = Carbon::parse($workShift->check_out_time)->setDateFrom($returnTime);

                if ($returnTime->format('H:i') === $shiftEndTime->format('H:i')) {
                    $returnedOnTime = true;
                }
            }

            $request = PermissionRequest::create([
                'user_id' => $userId,
                'departure_time' => $data['departure_time'],
                'return_time' => $data['return_time'],
                'minutes_used' => $validation['duration'],
                'remaining_minutes' => $this->validationService->getRemainingMinutes($userId) - $validation['duration'],
                'reason' => $data['reason'],
                'manager_status' => $managerStatus,
                'hr_status' => $hrStatus,
                'status' => $status,
                'returned_on_time' => $returnedOnTime,
            ]);


            $this->notificationService->createPermissionRequestNotification($request);

            $usedMinutes = $this->validationService->getUsedMinutes($userId);
            $remainingMinutes = $this->validationService->getRemainingMinutes($userId);

            return [
                'success' => true,
                'request_id' => $request->id,
                'message' => 'تم إنشاء طلب الاستئذان بنجاح.',
                'exceeded_limit' => $validation['exceeded_limit'] ?? false,
                'used_minutes' => $usedMinutes,
                'remaining_minutes' => $remainingMinutes
            ];
        } catch (\Exception $e) {
            Log::error('Error creating request: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createRequestForUser(int $userId, array $data): array
    {
        try {
            if (!auth()->user()->hasPermissionTo('create_permission')) {
                return [
                    'success' => false,
                    'message' => 'ليس لديك صلاحية تقديم طلب استئذان'
                ];
            }

            $validation = $this->validationService->validateTimeRequest($userId, $data['departure_time'], $data['return_time']);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            $currentUser = Auth::user();
            $targetUser = User::find($userId);
            $remainingMinutes = $this->validationService->getRemainingMinutes($userId);

            $managerStatus = 'pending';
            $hrStatus = 'pending';
            $status = 'pending';

            if ($currentUser->hasRole('hr')) {
                $hrStatus = 'approved';

                $isTargetInHrTeam = false;
                if ($currentUser->currentTeam && $targetUser) {
                    $isTargetInHrTeam = DB::table('team_user')
                        ->where('team_id', $currentUser->currentTeam->id)
                        ->where('user_id', $userId)
                        ->exists();
                }

                if ($currentUser->hasPermissionTo('manager_respond_permission_request') && $isTargetInHrTeam) {
                    $managerStatus = 'approved';
                }
            }

            if ($currentUser->hasAnyRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
                $managerStatus = 'approved';
            }

            if ($targetUser && (!$targetUser->teams()->exists() || $targetUser->teams()->where('name', 'HR')->exists())) {
                if ($hrStatus === 'approved') {
                    $status = 'approved';
                } elseif ($hrStatus === 'rejected') {
                    $status = 'rejected';
                }
            } else {
                if ($managerStatus === 'approved' && $hrStatus === 'approved') {
                    $status = 'approved';
                } elseif ($managerStatus === 'rejected' || $hrStatus === 'rejected') {
                    $status = 'rejected';
                }
            }

            $returnTime = Carbon::parse($data['return_time']);
            $workShift = $targetUser->workShift;
            $returnedOnTime = false;

            if ($workShift) {
                $shiftEndTime = Carbon::parse($workShift->check_out_time)->setDateFrom($returnTime);

                if ($returnTime->format('H:i') === $shiftEndTime->format('H:i')) {
                    $returnedOnTime = true;
                }
            }

            $request = PermissionRequest::create([
                'user_id' => $userId,
                'departure_time' => $data['departure_time'],
                'return_time' => $data['return_time'],
                'minutes_used' => $validation['duration'],
                'remaining_minutes' => $remainingMinutes - $validation['duration'],
                'reason' => $data['reason'],
                'manager_status' => $managerStatus,
                'hr_status' => $hrStatus,
                'status' => $status,
                'returned_on_time' => $returnedOnTime,
            ]);

            $this->notificationService->createPermissionRequestNotification($request);

            $usedMinutes = $this->validationService->getUsedMinutes($userId);
            $remainingMinutes = $this->validationService->getRemainingMinutes($userId);

            return [
                'success' => true,
                'request_id' => $request->id,
                'message' => 'تم إنشاء طلب الاستئذان بنجاح للموظف.',
                'exceeded_limit' => $validation['exceeded_limit'] ?? false,
                'used_minutes' => $usedMinutes,
                'remaining_minutes' => $remainingMinutes
            ];
        } catch (\Exception $e) {
            Log::error('Error creating request for user: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteRequest(PermissionRequest $request)
    {
        if (!auth()->user()->hasPermissionTo('delete_permission')) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'ليس لديك صلاحية حذف طلب الاستئذان'
            );
        }

        if ($request->status !== 'pending' || auth()->id() !== $request->user_id) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'لا يمكن حذف هذا الطلب'
            );
        }

        $this->notificationService->notifyPermissionDeleted($request);
        $request->delete();
        return ['success' => true];
    }
}
