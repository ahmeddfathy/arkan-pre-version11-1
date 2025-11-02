<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\AbsenceRequest;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use App\Services\Notifications\Traits\HasSlackNotification;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Log;

class EmployeeNotificationService
{
    use HasFirebaseNotification, HasSlackNotification;

    protected $slackNotificationService;

    public function __construct(SlackNotificationService $slackNotificationService)
    {
        $this->slackNotificationService = $slackNotificationService;
    }

    public function notifyEmployee(AbsenceRequest $request, string $type, array $data): void
    {
        try {
            $notificationData = array_merge($data, [
                'request_details' => [
                    'absence_date' => $request->absence_date->format('Y-m-d'),
                    'reason' => $request->reason,
                    'status' => $request->status
                ]
            ]);

            Notification::create([
                'user_id' => $request->user_id,
                'type' => $type,
                'data' => $notificationData,
                'related_id' => $request->id
            ]);

            if ($request->user) {
                $title = $this->getFirebaseTitle($type);
                $link = "/absence-requests/{$request->id}";

                $this->sendAdditionalFirebaseNotification(
                    $request->user,
                    $data['message'] ?? 'إشعار جديد',
                    $title,
                    $link,
                    $type
                );

                                if ($request->user->slack_user_id) {
                    $action = $this->determineSlackAction($type, $request);
                    $currentUser = \Illuminate\Support\Facades\Auth::user();
                    $this->slackNotificationService->sendAbsenceRequestNotification(
                        $request,
                        $request->user,
                        $currentUser ?: $request->user,
                        $action
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('Error in EmployeeNotificationService::notifyEmployee - ' . $e->getMessage());
        }
    }

    public function deleteExistingNotifications(AbsenceRequest $request, string $type): void
    {
        try {
            Notification::where('related_id', $request->id)
                ->where('type', $type)
                ->delete();
        } catch (\Exception $e) {
            Log::error('Error in EmployeeNotificationService::deleteExistingNotifications - ' . $e->getMessage());
        }
    }

    private function determineSlackAction(string $type, AbsenceRequest $request = null): string
    {
        if (strpos($type, 'status_updated') !== false || strpos($type, 'status_update') !== false) {
            if ($request) {
                if ($request->status === 'approved') {
                    return 'approved';
                } elseif ($request->status === 'rejected') {
                    return 'rejected';
                }
            }
            return 'approved';
        } elseif (strpos($type, 'status_reset') !== false) {
            return 'reset';
        } elseif (strpos($type, 'manager_response') !== false) {
            if ($request && $request->manager_status === 'rejected') {
                return 'rejected';
            }
            return 'approved';
        } elseif (strpos($type, 'hr_response') !== false) {
            if ($request && $request->hr_status === 'rejected') {
                return 'rejected';
            }
            return 'approved';
        } elseif (strpos($type, 'response_modified') !== false) {
            return 'modified';
        } else {
            return 'created';
        }
    }

    private function getStatusInArabic(string $status): string
    {
        $statusMap = [
            'pending' => 'قيد الانتظار',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي'
        ];

        return $statusMap[$status] ?? $status;
    }

    private function getFirebaseTitle(string $type): string
    {
        if (strpos($type, 'status_updated') !== false || strpos($type, 'status_update') !== false) {
            return 'تحديث حالة طلب الغياب';
        } elseif (strpos($type, 'manager_response') !== false) {
            return 'رد المدير على طلب الغياب';
        } elseif (strpos($type, 'hr_response') !== false) {
            return 'رد HR على طلب الغياب';
        } elseif (strpos($type, 'status_reset') !== false) {
            return 'إعادة تعيين طلب الغياب';
        } elseif (strpos($type, 'response_modified') !== false) {
            return 'تعديل رد على طلب الغياب';
        } else {
            return 'إشعار طلب الغياب';
        }
    }
}
