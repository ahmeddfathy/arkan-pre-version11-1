<?php

namespace App\Services\PermissionRequest;

use App\Models\PermissionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RequestValidationService
{
    const MONTHLY_LIMIT_MINUTES = 180;

    public function validateTimeRequest(int $userId, string $departureTime, string $returnTime, ?int $excludeId = null): array
    {
        $departureDateTime = Carbon::parse($departureTime);
        $returnDateTime = Carbon::parse($returnTime);
        $duration = $departureDateTime->diffInMinutes($returnDateTime);

        $user = User::find($userId);
        $workShift = $user->workShift;

        if ($workShift) {
            $shiftStartTime = Carbon::parse($workShift->check_in_time)->setDateFrom($departureDateTime);
            $shiftEndTime = Carbon::parse($workShift->check_out_time)->setDateFrom($departureDateTime);
        } else {
            $shiftStartTime = Carbon::parse($departureTime)->setTime(8, 0, 0);
            $shiftEndTime = Carbon::parse($departureTime)->setTime(16, 0, 0);
        }

        Log::info('Validating time request', [
            'user_id' => $userId,
            'departure_time' => $departureTime,
            'return_time' => $returnTime,
            'shift_start_time' => $shiftStartTime->format('Y-m-d H:i:s'),
            'shift_end_time' => $shiftEndTime->format('Y-m-d H:i:s')
        ]);

        if ($departureDateTime->greaterThanOrEqualTo($returnDateTime)) {
            return [
                'valid' => false,
                'message' => 'وقت المغادرة يجب أن يكون قبل وقت العودة.',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        if ($departureDateTime->lessThan($shiftStartTime)) {
            return [
                'valid' => false,
                'message' => 'وقت المغادرة يجب أن يكون بعد بداية الوردية (' . $shiftStartTime->format('h:i A') . ').',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        if ($returnDateTime->greaterThan($shiftEndTime)) {
            return [
                'valid' => false,
                'message' => 'وقت العودة يجب أن يكون قبل نهاية الوردية (' . $shiftEndTime->format('h:i A') . ').',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        if ($departureDateTime->diffInMinutes($returnDateTime) > 180) {
            return [
                'valid' => false,
                'message' => 'مدة الاستئذان يجب أن لا تزيد عن 3 ساعات.',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        $overlappingRequests = PermissionRequest::where('user_id', $userId)
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($departureTime, $returnTime) {
                $query->where(function ($query) use ($departureTime, $returnTime) {
                    $query->where('departure_time', '<=', $departureTime)
                        ->where('return_time', '>=', $departureTime);
                })->orWhere(function ($query) use ($departureTime, $returnTime) {
                    $query->where('departure_time', '<=', $returnTime)
                        ->where('return_time', '>=', $returnTime);
                })->orWhere(function ($query) use ($departureTime, $returnTime) {
                    $query->where('departure_time', '>=', $departureTime)
                        ->where('return_time', '<=', $returnTime);
                });
            });

        if ($excludeId !== null) {
            $overlappingRequests->where('id', '!=', $excludeId);
        }

        $count = $overlappingRequests->count();

        if ($count > 0) {
            return [
                'valid' => false,
                'message' => 'هناك تعارض مع طلب استئذان آخر في نفس الوقت.',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        $remainingMinutes = $this->getRemainingMinutes($userId);
        $requestedMinutes = $departureDateTime->diffInMinutes($returnDateTime);

        if ($requestedMinutes > $remainingMinutes) {
            return [
                'valid' => true,
                'message' => "تنبيه: لقد تجاوزت الحد المجاني للاستئذان الشهري المسموح به (180 دقيقة). المتبقي: {$remainingMinutes} دقيقة.",
                'duration' => $duration,
                'exceeded_limit' => true
            ];
        }

        return [
            'valid' => true,
            'duration' => $duration,
            'exceeded_limit' => false
        ];
    }

    public function getRemainingMinutes(int $userId): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $usedMinutes = PermissionRequest::where('user_id', $userId)
            ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['pending', 'approved'])
            ->sum('minutes_used');

        return max(0, self::MONTHLY_LIMIT_MINUTES - $usedMinutes);
    }

    public function getUsedMinutes(int $userId): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return PermissionRequest::where('user_id', $userId)
            ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['pending', 'approved'])
            ->sum('minutes_used');
    }
}
