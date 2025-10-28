<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\PermissionRequest;
use App\Models\User;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use App\Services\Notifications\Traits\HasSlackNotification;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Log;

class EmployeePermissionNotificationService
{
    use HasFirebaseNotification, HasSlackNotification;

    protected $slackNotificationService;

    public function __construct(SlackNotificationService $slackNotificationService)
    {
        $this->slackNotificationService = $slackNotificationService;
    }

    public function notifyEmployee(PermissionRequest $request, string $type, $data): void
    {
        try {
            if (is_string($data)) {
                $message = $data;
                $data = ['message' => $message];
            }

            $existingNotification = Notification::where([
                'user_id' => $request->user_id,
                'type' => $type,
                'related_id' => $request->id
            ])->exists();

            if (!$existingNotification) {
                $notificationData = array_merge($data, [
                    'request_details' => [
                        'departure_time' => $request->departure_time->format('Y-m-d H:i'),
                        'return_time' => $request->return_time->format('Y-m-d H:i'),
                        'minutes_used' => $request->minutes_used,
                        'reason' => $request->reason,
                        'remaining_minutes' => $request->remaining_minutes
                    ]
                ]);

                Notification::create([
                    'user_id' => $request->user_id,
                    'type' => $type,
                    'data' => $notificationData,
                    'related_id' => $request->id
                ]);
            }

            if ($request->user) {
                // تحديد العنوان والرابط بناءً على نوع الإشعار
                $title = $this->getPermissionFirebaseTitle($type);
                $link = "/permission-requests/{$request->id}";

                $this->sendAdditionalFirebaseNotification(
                    $request->user,
                    $data['message'] ?? 'إشعار جديد',
                    $title,
                    $link,
                    $type
                );

                // إرسال إشعار Slack للموظف إذا كان لديه slack_user_id
                if ($request->user->slack_user_id) {
                    $action = $this->determineSlackAction($type, $request);
                    $currentUser = \Illuminate\Support\Facades\Auth::user();
                    $this->slackNotificationService->sendPermissionRequestNotification(
                        $request,
                        $request->user,
                        $currentUser ?: $request->user,
                        $action
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('Error in EmployeePermissionNotificationService::notifyEmployee - ' . $e->getMessage());
        }
    }

    public function notifyTeamMembers(PermissionRequest $request, string $type, array $data): void
    {
        try {
        if ($request->user && $request->user->currentTeam) {
            $teamMembers = $request->user->currentTeam->users()
                ->where('users.id', '!=', $request->user_id)
                ->get();

            foreach ($teamMembers as $member) {
                Notification::create([
                    'user_id' => $member->id,
                    'type' => $type,
                    'data' => $data,
                    'related_id' => $request->id
                ]);
            }
            }
        } catch (\Exception $e) {
            Log::error('Error in EmployeePermissionNotificationService::notifyTeamMembers - ' . $e->getMessage());
        }
    }

    public function deleteExistingNotifications(PermissionRequest $request, string $type): void
    {
        try {
            Notification::where('related_id', $request->id)
                ->where('type', $type)
                ->delete();
        } catch (\Exception $e) {
            Log::error('Error in EmployeePermissionNotificationService::deleteExistingNotifications - ' . $e->getMessage());
        }
    }

    private function determineSlackAction(string $type, PermissionRequest $request = null): string
    {
        if (strpos($type, 'status_updated') !== false) {
            if ($request) {
                if ($request->status === 'approved') {
                    return 'approved';
                } elseif ($request->status === 'rejected') {
                    return 'rejected';
                }
            }
            return 'approved';
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

    /**
     * Get Firebase notification title for permission requests
     */
    private function getPermissionFirebaseTitle(string $type): string
    {
        if (strpos($type, 'status_updated') !== false || strpos($type, 'status_update') !== false) {
            return 'تحديث حالة طلب الاستئذان';
        } elseif (strpos($type, 'manager_response') !== false) {
            return 'رد المدير على طلب الاستئذان';
        } elseif (strpos($type, 'hr_response') !== false) {
            return 'رد HR على طلب الاستئذان';
        } elseif (strpos($type, 'status_reset') !== false) {
            return 'إعادة تعيين طلب الاستئذان';
        } elseif (strpos($type, 'response_modified') !== false) {
            return 'تعديل رد على طلب الاستئذان';
        } elseif (strpos($type, 'return_status') !== false) {
            return 'تحديث حالة العودة';
        } else {
            return 'إشعار طلب الاستئذان';
        }
    }
}
