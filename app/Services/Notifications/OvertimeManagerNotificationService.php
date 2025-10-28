<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Models\OverTimeRequests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use App\Services\Notifications\Traits\HasSlackNotification;
use App\Services\SlackNotificationService;

class OvertimeManagerNotificationService
{
    use HasFirebaseNotification, HasSlackNotification;

    protected $slackNotificationService;

    public function __construct(SlackNotificationService $slackNotificationService)
    {
        $this->slackNotificationService = $slackNotificationService;
    }

    public function notifyManagers(OverTimeRequests $request, string $type, string $message, bool $isHRNotification = false): void
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

                    // استخدام التاريخ المنسق بدون الأصفار الزائدة
                    $date = $request->formatted_overtime_date;

                    $slackMessage = "طلب عمل إضافي: $employeeName - $date";

                    // تحديد نوع العملية بناءً على نوع الإشعار
                    $operation = $this->determineSlackOperation($type);

                    // إضافة الرابط للطلب
                    $additionalData = [
                        'link_url' => url("/overtime-requests/{$request->id}"),
                        'link_text' => 'عرض الطلب'
                    ];

                    // تحديد context حسب نوع الإشعار
                    if (strpos($type, 'hr_response') !== false) {
                        if ($request->hr_status === 'approved') {
                            $this->setHRNotificationContext('إشعار رد HR بالموافقة على العمل الإضافي');
                        } elseif ($request->hr_status === 'rejected') {
                            $this->setHRNotificationContext('إشعار رد HR برفض العمل الإضافي');
                        } else {
                            $this->setHRNotificationContext('إشعار رد HR على العمل الإضافي');
                        }
                    } elseif (strpos($type, 'manager_response') !== false) {
                        if ($request->manager_status === 'approved') {
                            $this->setHRNotificationContext('إشعار رد المدير بالموافقة على العمل الإضافي');
                        } elseif ($request->manager_status === 'rejected') {
                            $this->setHRNotificationContext('إشعار رد المدير برفض العمل الإضافي');
                        } else {
                            $this->setHRNotificationContext('إشعار رد المدير على العمل الإضافي');
                        }
                    } else {
                        $this->setHRNotificationContext('إشعار العمل الإضافي');
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
                            $this->slackNotificationService->sendOvertimeRequestNotification(
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
            Log::error('Error in OvertimeManagerNotificationService::notifyManagers - ' . $e->getMessage());
        }
    }

    /**
     * تحديد نوع العملية لإشعارات Slack بناءً على نوع الإشعار
     */
    private function determineSlackOperation(string $type): string
    {
        if (strpos($type, 'new_overtime_request') !== false) {
            return 'إنشاء';
        } elseif (strpos($type, 'modified') !== false || strpos($type, 'response') !== false) {
            return 'تحديث';
        } elseif (strpos($type, 'deleted') !== false) {
            return 'حذف';
        } else {
            return 'إنشاء';
        }
    }

    private function sendNotificationsToMultipleUsers($users, OverTimeRequests $request, string $type, string $message, bool $isHRNotification): void
    {
        try {
            foreach ($users as $user) {
                $this->createNotification($user, $request, $type, $message, $isHRNotification);
            }
        } catch (\Exception $e) {
        }
    }

    private function createNotification(User $user, OverTimeRequests $request, string $type, string $message, bool $isHRNotification): void
    {
        try {
            $timestamp = now()->format('Y-m-d H:i:s');

            if (strpos($type, 'response_modified') !== false || strpos($type, 'status_reset') !== false) {
                Notification::where([
                    'user_id' => $user->id,
                    'type' => $type,
                    'related_id' => $request->id
                ])->delete();
            }

            $isTeamOwner = false;
            if (!$isHRNotification && $request->user && $request->user->currentTeam) {
                $isTeamOwner = $request->user->currentTeam->owner_id === $user->id;
            }

            $responderName = 'النظام';
            if (\Illuminate\Support\Facades\Auth::check()) {
                $responderName = \Illuminate\Support\Facades\Auth::user()->name;
            }

            $notificationData = [
                'message' => $message,
                'request_id' => $request->id,
                'employee_name' => $request->user->name,
                'overtime_date' => $request->overtime_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'reason' => $request->reason,
                'is_hr_notification' => $isHRNotification,
                'is_manager_notification' => !$isHRNotification,
                'is_team_owner' => $isTeamOwner,
                'responder_name' => $responderName,
                'notification_time' => $timestamp,
                'action_type' => $this->determineActionType($type),
                'action_details' => $this->getActionDetails($request, $type)
            ];

            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'data' => $notificationData,
                'related_id' => $request->id
            ]);

            $actionType = $this->determineFirebaseActionType($type);

            $title = $this->determineNotificationTitle($type, $request);

            $firebaseResult = $this->sendTypedFirebaseNotification($user, 'overtime', $actionType, $message, $request->id);

        } catch (\Exception $e) {
        }
    }

    private function determineFirebaseActionType(string $type): string
    {
        if (strpos($type, 'new_overtime_request') !== false) {
            return 'created';
        } elseif (strpos($type, 'overtime_request_modified') !== false) {
            return 'updated';
        } elseif (strpos($type, 'overtime_request_deleted') !== false) {
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

    private function determineNotificationTitle(string $type, OverTimeRequests $request): string
    {
        if (strpos($type, 'new_overtime_request') !== false) {
            return 'طلب عمل إضافي جديد';
        } elseif (strpos($type, 'overtime_request_modified') !== false) {
            return 'تم تحديث طلب العمل الإضافي';
        } elseif (strpos($type, 'overtime_request_deleted') !== false) {
            return 'تم حذف طلب العمل الإضافي';
        } elseif (strpos($type, 'manager_response') !== false) {
            if ($request->manager_status === 'approved') {
                return 'تمت الموافقة على طلب العمل الإضافي من المدير';
            } elseif ($request->manager_status === 'rejected') {
                return 'تم رفض طلب العمل الإضافي من المدير';
            }
        } elseif (strpos($type, 'hr_response') !== false) {
            if ($request->hr_status === 'approved') {
                return 'تمت الموافقة على طلب العمل الإضافي من الموارد البشرية';
            } elseif ($request->hr_status === 'rejected') {
                return 'تم رفض طلب العمل الإضافي من الموارد البشرية';
            }
        } elseif (strpos($type, 'status_reset') !== false) {
            return 'تمت إعادة تعيين حالة طلب العمل الإضافي';
        }

        return 'إشعار طلب عمل إضافي';
    }

    private function determineActionType(string $type): string
    {
        if (strpos($type, 'created') !== false) {
            return 'إنشاء';
        } elseif (strpos($type, 'updated') !== false) {
            return 'تحديث';
        } elseif (strpos($type, 'reset') !== false) {
            return 'إعادة تعيين';
        } elseif (strpos($type, 'deleted') !== false) {
            return 'حذف';
        } else {
            return 'إجراء';
        }
    }

    private function getActionDetails($request, string $type): array
    {
        $details = [
            'by_who' => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->name : 'النظام'
        ];

        $details['status'] = $request->status;
        $details['manager_status'] = $request->manager_status;
        $details['hr_status'] = $request->hr_status;

        if ($request->status === 'approved') {
            $details['status_text'] = 'موافقة';
        } elseif ($request->status === 'rejected') {
            $details['status_text'] = 'رفض';
        } elseif ($request->status === 'pending') {
            $details['status_text'] = 'قيد الانتظار';
        }

        if ($request->manager_rejection_reason) {
            $details['manager_rejection_reason'] = $request->manager_rejection_reason;
        }

        if ($request->hr_rejection_reason) {
            $details['hr_rejection_reason'] = $request->hr_rejection_reason;
        }

        if (strpos($type, 'new_overtime_request') !== false) {
            $details['request_type'] = 'جديد';
            $details['action_needed'] = 'يحتاج إلى مراجعة';
        } elseif (strpos($type, 'overtime_manager_response') !== false || strpos($type, 'overtime_hr_response') !== false) {
            if (strpos($type, 'manager_response') !== false) {
                $details['responder_type'] = 'مدير';
                $details['response_status'] = $request->manager_status;
                if ($request->manager_status === 'approved') {
                    $details['response_text'] = 'تمت الموافقة من المدير';
                } elseif ($request->manager_status === 'rejected') {
                    $details['response_text'] = 'تم الرفض من المدير';
                    $details['rejection_reason'] = $request->manager_rejection_reason;
                }
            } else {
                $details['responder_type'] = 'HR';
                $details['response_status'] = $request->hr_status;
                if ($request->hr_status === 'approved') {
                    $details['response_text'] = 'تمت الموافقة من HR';
                } elseif ($request->hr_status === 'rejected') {
                    $details['response_text'] = 'تم الرفض من HR';
                    $details['rejection_reason'] = $request->hr_rejection_reason;
                }
            }
        } elseif (strpos($type, 'manager_status') !== false) {
            $details['responder_type'] = 'مدير';
            $details['status'] = $request->manager_status;
            if ($request->manager_status === 'approved') {
                $details['status_text'] = 'موافقة';
            } elseif ($request->manager_status === 'rejected') {
                $details['status_text'] = 'رفض';
                $details['reason'] = $request->manager_rejection_reason;
            } elseif ($request->manager_status === 'pending') {
                $details['status_text'] = 'قيد الانتظار';
            }
        } elseif (strpos($type, 'hr_status') !== false) {
            $details['responder_type'] = 'HR';
            $details['status'] = $request->hr_status;
            if ($request->hr_status === 'approved') {
                $details['status_text'] = 'موافقة';
            } elseif ($request->hr_status === 'rejected') {
                $details['status_text'] = 'رفض';
                $details['reason'] = $request->hr_rejection_reason;
            } elseif ($request->hr_status === 'pending') {
                $details['status_text'] = 'قيد الانتظار';
            }
        } elseif (strpos($type, 'status_reset') !== false) {
            if (strpos($type, 'hr_status_reset') !== false) {
                $details['responder_type'] = 'HR';
                $details['reset_status'] = 'تم إعادة تعيين حالة HR إلى قيد الانتظار';
            } else {
                $details['responder_type'] = 'مدير';
                $details['reset_status'] = 'تم إعادة تعيين حالة المدير إلى قيد الانتظار';
            }
        } elseif (strpos($type, 'overtime_request_deleted') !== false) {
            $details['action'] = 'حذف';
            $details['deleted_by'] = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->name : 'النظام';
        } elseif (strpos($type, 'overtime_request_modified') !== false) {
            $details['action'] = 'تعديل';
            $details['modified_by'] = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->name : 'النظام';
        } elseif (strpos($type, 'overtime_manager_response_modified') !== false || strpos($type, 'overtime_hr_response_modified') !== false) {
            if (strpos($type, 'manager_response_modified') !== false) {
                $details['responder_type'] = 'مدير';
                $details['status'] = $request->manager_status;
                $details['action'] = 'تعديل الرد';
                if ($request->manager_status === 'approved') {
                    $details['status_text'] = 'موافقة';
                } elseif ($request->manager_status === 'rejected') {
                    $details['status_text'] = 'رفض';
                    $details['reason'] = $request->manager_rejection_reason;
                } elseif ($request->manager_status === 'pending') {
                    $details['status_text'] = 'قيد الانتظار';
                }
            } else {
                $details['responder_type'] = 'HR';
                $details['status'] = $request->hr_status;
                $details['action'] = 'تعديل الرد';
                if ($request->hr_status === 'approved') {
                    $details['status_text'] = 'موافقة';
                } elseif ($request->hr_status === 'rejected') {
                    $details['status_text'] = 'رفض';
                    $details['reason'] = $request->hr_rejection_reason;
                } elseif ($request->hr_status === 'pending') {
                    $details['status_text'] = 'قيد الانتظار';
                }
            }
            $details['modified_by'] = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->name : 'النظام';
            $details['modification_time'] = now()->format('Y-m-d H:i:s');
        }

        return $details;
    }

    private function determineSlackActionFromType(string $type, OverTimeRequests $request = null): string
    {
        if (strpos($type, 'new_overtime_request') !== false) {
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
