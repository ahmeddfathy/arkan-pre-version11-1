<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Models\PermissionRequest;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use App\Services\Notifications\Traits\HasSlackNotification;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ManagerPermissionNotificationService
{
    use HasFirebaseNotification, HasSlackNotification;

    protected $slackNotificationService;

    public function __construct(SlackNotificationService $slackNotificationService)
    {
        $this->slackNotificationService = $slackNotificationService;
    }

    public function notifyManagers(PermissionRequest $request, string $type, string $message, bool $isHRNotification = false): void
    {
        try {
            if ($isHRNotification) {
                $hrUsers = User::whereHas('roles', function ($q) {
                    $q->where('name', 'hr');
                })
                    ->where('id', '!=', $request->user_id)
                    ->get();

                if ($hrUsers->isNotEmpty()) {
                    $this->sendNotificationsToMultipleUsers($hrUsers, $request, $type, $message, true);

                    // إرسال إشعار Slack لفريق الموارد البشرية - فقط ببيانات الموظف والطلب
                    $employeeName = $request->user ? $request->user->name : 'موظف';
                    $slackMessage = "طلب إذن: $employeeName - " . $request->departure_time->format('Y-m-d H:i');

                    // تحديد نوع العملية بناءً على نوع الإشعار
                    $operation = $this->determineSlackOperation($type);

                    // إضافة الرابط للطلب
                    $additionalData = [
                        'link_url' => url("/permission-requests/{$request->id}"),
                        'link_text' => 'عرض الطلب'
                    ];

                    // تحديد context حسب نوع الإشعار
                    if (strpos($type, 'hr_response') !== false) {
                        if ($request->hr_status === 'approved') {
                            $this->setHRNotificationContext('إشعار رد HR بالموافقة على الإذن');
                        } elseif ($request->hr_status === 'rejected') {
                            $this->setHRNotificationContext('إشعار رد HR برفض الإذن');
                        } else {
                            $this->setHRNotificationContext('إشعار رد HR على الإذن');
                        }
                    } elseif (strpos($type, 'manager_response') !== false) {
                        if ($request->manager_status === 'approved') {
                            $this->setHRNotificationContext('إشعار رد المدير بالموافقة على الإذن');
                        } elseif ($request->manager_status === 'rejected') {
                            $this->setHRNotificationContext('إشعار رد المدير برفض الإذن');
                        } else {
                            $this->setHRNotificationContext('إشعار رد المدير على الإذن');
                        }
                    } else {
                        $this->setHRNotificationContext('إشعار الإذن');
                    }
                    $this->sendSlackNotification($slackMessage, $operation, $additionalData);
                }
            } else {
                $userTeams = $request->user->teams;
                $managersToNotify = collect();

                foreach ($userTeams as $team) {
                    if ($team->owner_id === $request->user_id) {
                        continue;
                    }

                    $isAdmin = DB::table('team_user')
                        ->where('team_id', $team->id)
                        ->where('user_id', $request->user_id)
                        ->where('role', 'admin')
                        ->exists();

                    if ($isAdmin) {
                        continue;
                    }

                    $teamMembersCount = $team->users()
                        ->where('users.id', '!=', $team->owner_id)
                        ->count();

                    if ($teamMembersCount > 0) {
                        $teamOwner = $team->owner;
                        if ($teamOwner && $teamOwner->id !== $request->user_id) {
                            $managersToNotify->push($teamOwner);
                        }
                    }
                }

                if ($managersToNotify->isNotEmpty()) {
                    $this->sendNotificationsToMultipleUsers($managersToNotify, $request, $type, $message, false);

                                        // إرسال إشعارات Slack لمالكي الفرق
                    $currentUser = \Illuminate\Support\Facades\Auth::user();
                    $action = $this->determineSlackActionFromType($type, $request);

                    foreach ($managersToNotify as $manager) {
                        if ($manager->slack_user_id) {
                            $this->slackNotificationService->sendPermissionRequestNotification(
                                $request,
                                $manager,
                                $currentUser ?: $request->user,
                                $action
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in ManagerPermissionNotificationService::notifyManagers - ' . $e->getMessage());
        }
    }

    private function sendNotificationsToMultipleUsers($users, PermissionRequest $request, string $type, string $message, bool $isHRNotification): void
    {
        try {
            foreach ($users as $user) {
                $this->createNotification($user, $request, $type, $message, $isHRNotification);
            }
        } catch (\Exception $e) {
        }
    }

    private function createNotification(User $user, PermissionRequest $request, string $type, string $message, bool $isHRNotification): void
    {
        try {
            $existingNotification = Notification::where([
                'user_id' => $user->id,
                'type' => $type,
                'related_id' => $request->id
            ])->exists();

            if (!$existingNotification ||
                strpos($type, '_modified') !== false ||
                strpos($type, '_deleted') !== false ||
                strpos($type, '_reset') !== false) {

                $isTeamOwner = false;
                if (!$isHRNotification && $request->user && $request->user->currentTeam) {
                    $isTeamOwner = $request->user->currentTeam->owner_id === $user->id;
                }

                $notificationData = [
                    'message' => $message,
                    'request_id' => $request->id,
                    'employee_name' => $request->user->name,
                    'departure_time' => $request->departure_time->format('Y-m-d H:i'),
                    'return_time' => $request->return_time->format('Y-m-d H:i'),
                    'minutes_used' => $request->minutes_used,
                    'reason' => $request->reason,
                    'remaining_minutes' => $request->remaining_minutes,
                    'is_hr_notification' => $isHRNotification,
                    'is_manager_notification' => !$isHRNotification,
                    'is_team_owner' => $isTeamOwner,
                    'notification_time' => now()->format('Y-m-d H:i:s')
                ];

                if ($type === 'permission_request_modified') {
                    $notificationData['modification_time'] = now()->format('Y-m-d H:i:s');
                } elseif ($type === 'permission_request_deleted') {
                    $notificationData['deletion_time'] = now()->format('Y-m-d H:i:s');
                }

                $notification = Notification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'data' => $notificationData,
                    'related_id' => $request->id
                ]);

                $actionType = $this->determineFirebaseActionType($type);
                $title = $this->determineNotificationTitle($type, $request);
                $firebaseResult = $this->sendTypedFirebaseNotification($user, 'permission', $actionType, $message, $request->id);
            }
        } catch (\Exception $e) {
        }
    }

    private function determineFirebaseActionType(string $type): string
    {
        if (strpos($type, 'new_permission_request') !== false) {
            return 'created';
        } elseif (strpos($type, 'permission_request_modified') !== false) {
            return 'updated';
        } elseif (strpos($type, 'permission_request_deleted') !== false) {
            return 'deleted';
        } elseif (strpos($type, 'manager_response') !== false || strpos($type, 'hr_response') !== false) {
            if (strpos($type, 'approved') !== false) {
                return 'approved';
            } elseif (strpos($type, 'rejected') !== false) {
                return 'rejected';
            }
        } elseif (strpos($type, 'status_reset') !== false) {
            return 'reset';
        }

        return 'updated';
    }

    private function determineNotificationTitle(string $type, PermissionRequest $request): string
    {
        if (strpos($type, 'new_permission_request') !== false) {
            return 'طلب إذن جديد';
        } elseif (strpos($type, 'permission_request_modified') !== false) {
            return 'تم تحديث طلب الإذن';
        } elseif (strpos($type, 'permission_request_deleted') !== false) {
            return 'تم حذف طلب الإذن';
        } elseif (strpos($type, 'manager_response') !== false) {
            if ($request->manager_status === 'approved') {
                return 'تمت الموافقة على طلب الإذن من المدير';
            } elseif ($request->manager_status === 'rejected') {
                return 'تم رفض طلب الإذن من المدير';
            }
        } elseif (strpos($type, 'hr_response') !== false) {
            if ($request->hr_status === 'approved') {
                return 'تمت الموافقة على طلب الإذن من الموارد البشرية';
            } elseif ($request->hr_status === 'rejected') {
                return 'تم رفض طلب الإذن من الموارد البشرية';
            }
        } elseif (strpos($type, 'status_reset') !== false) {
            return 'تمت إعادة تعيين حالة طلب الإذن';
        }

        return 'إشعار طلب إذن';
    }

    /**
     * تحديد نوع العملية لإشعارات Slack بناءً على نوع الإشعار
     */
    private function determineSlackOperation(string $type): string
    {
        if (strpos($type, 'new_permission_request') !== false) {
            return 'إنشاء';
        } elseif (strpos($type, 'modified') !== false || strpos($type, 'response') !== false) {
            return 'تحديث';
        } elseif (strpos($type, 'deleted') !== false) {
            return 'حذف';
        } else {
            return 'إنشاء';
        }
    }

    private function determineSlackActionFromType(string $type, PermissionRequest $request = null): string
    {
        if (strpos($type, 'new_permission_request') !== false) {
            return 'created';
        } elseif (strpos($type, 'hr_response') !== false) {
            if ($request && $request->hr_status === 'rejected') {
                return 'rejected';
            } elseif ($request && $request->hr_status === 'approved') {
                return 'approved';
            }
            return 'approved';
        } elseif (strpos($type, 'manager_response') !== false) {
            if ($request && $request->manager_status === 'rejected') {
                return 'rejected';
            } elseif ($request && $request->manager_status === 'approved') {
                return 'approved';
            }
            return 'approved';
        } elseif (strpos($type, 'modified') !== false) {
            return 'modified';
        } elseif (strpos($type, 'deleted') !== false) {
            return 'deleted';
        } elseif (strpos($type, 'approved') !== false) {
            return 'approved';
        } elseif (strpos($type, 'rejected') !== false) {
            return 'rejected';
        } else {
            return 'created';
        }
    }
}
