<?php

namespace App\Services\PermissionRequest;

use App\Models\PermissionRequest;
use App\Services\NotificationPermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RequestUpdateService
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

    public function updateRequest(PermissionRequest $request, array $data): array
    {
        try {
            if (!auth()->user()->hasPermissionTo('update_permission')) {
                return [
                    'success' => false,
                    'message' => 'ليس لديك صلاحية تعديل طلب الاستئذان'
                ];
            }

            if ($request->status !== 'pending' || auth()->id() !== $request->user_id) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن تعديل هذا الطلب'
                ];
            }

            $validation = $this->validationService->validateTimeRequest($request->user_id, $data['departure_time'], $data['return_time'], $request->id);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            $returnTime = \Carbon\Carbon::parse($data['return_time']);
            $user = \App\Models\User::find($request->user_id);
            $workShift = $user->workShift;
            $returnedOnTime = false;

            if ($workShift) {
                $shiftEndTime = \Carbon\Carbon::parse($workShift->check_out_time)->setDateFrom($returnTime);

                if ($returnTime->format('H:i') === $shiftEndTime->format('H:i')) {
                    $returnedOnTime = true;
                }
            }

            $remainingMinutes = $this->validationService->getRemainingMinutes($request->user_id);
            $minutesUsed = $validation['duration'];
            $oldMinutesUsed = $request->minutes_used;

            $updateData = [
                'departure_time' => $data['departure_time'],
                'return_time' => $data['return_time'],
                'minutes_used' => $minutesUsed,
                'remaining_minutes' => $remainingMinutes + ($oldMinutesUsed - $minutesUsed),
                'reason' => $data['reason']
            ];

            if ($returnedOnTime !== null) {
                $updateData['returned_on_time'] = $returnedOnTime;
            }

            $request->update($updateData);

            $this->notificationService->notifyPermissionModified($request);

            $usedMinutes = $this->validationService->getUsedMinutes($request->user_id);
            $newRemainingMinutes = $this->validationService->getRemainingMinutes($request->user_id);

            return [
                'success' => true,
                'used_minutes' => $usedMinutes,
                'remaining_minutes' => $newRemainingMinutes,
                'exceeded_limit' => $validation['exceeded_limit'] ?? false
            ];
        } catch (\Exception $e) {
            Log::error('Error updating request: ' . $e->getMessage());
            throw $e;
        }
    }
}
