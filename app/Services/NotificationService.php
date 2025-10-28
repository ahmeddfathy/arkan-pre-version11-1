<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\AbsenceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Notifications\ManagerNotificationService;
use App\Services\Notifications\EmployeeNotificationService;
use App\Services\FirebaseNotificationService;

class NotificationService
{
    protected $managerNotificationService;
    protected $employeeNotificationService;
    protected $firebaseService;

    public function __construct(
        ManagerNotificationService $managerNotificationService,
        EmployeeNotificationService $employeeNotificationService,
        FirebaseNotificationService $firebaseService
    ) {
        $this->managerNotificationService = $managerNotificationService;
        $this->employeeNotificationService = $employeeNotificationService;
        $this->firebaseService = $firebaseService;
    }

    public function createLeaveRequestNotification(AbsenceRequest $request): void
    {
        try {
            Log::info('Creating leave request notification', ['request_id' => $request->id]);

            $message = "{$request->user->name} قام بتقديم طلب غياب";

            // تقديم إشعار لمدراء الفرق
            if ($request->user && $request->user->currentTeam && $request->user->currentTeam->owner) {
                Log::info('Notifying team managers', [
                    'request_id' => $request->id,
                    'team_id' => $request->user->currentTeam->id
                ]);

                $this->managerNotificationService->notifyManagers(
                    $request,
                    'new_leave_request',
                    $message,
                    false
                );
            }

            // تقديم إشعار لقسم الموارد البشرية
            Log::info('Notifying HR department', ['request_id' => $request->id]);
            $this->managerNotificationService->notifyManagers(
                $request,
                'new_leave_request',
                $message,
                true
            );
        } catch (\Exception $e) {
            Log::error('Error creating leave request notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id ?? 'unknown'
            ]);
        }
    }

    public function createStatusUpdateNotification(AbsenceRequest $request): void
    {
        try {
            Log::info('Creating status update notification', ['request_id' => $request->id]);

            $updatedBy = auth()->guard()->user();
            $currentUserRole = $updatedBy?->roles->first()?->name;
            $hasTeam = DB::table('team_user')
                ->where('team_user.user_id', $request->user_id)
                ->exists();

            $statusMessage = $this->getStatusMessage($request, $currentUserRole);

            // استخدام EmployeeNotificationService لإرسال الإشعار (يتضمن قاعدة البيانات، Firebase، و Slack)
            $notificationData = [
                'message' => $statusMessage,
                'request_id' => $request->id,
                'date' => $request->absence_date->format('Y-m-d'),
                'status' => $request->status,
                'manager_status' => $request->manager_status,
                'hr_status' => $request->hr_status,
                'manager_rejection_reason' => $request->manager_rejection_reason,
                'hr_rejection_reason' => $request->hr_rejection_reason,
                'has_team' => $hasTeam,
                'response_by' => $currentUserRole
            ];

            $this->employeeNotificationService->notifyEmployee($request, 'leave_request_status_update', $notificationData);

            Log::info('Status update notification sent successfully', [
                'request_id' => $request->id,
                'user_id' => $request->user_id
            ]);

            // إرسال إشعارات إضافية بناءً على دور المستخدم الحالي
            $this->sendAdditionalNotifications($request, $currentUserRole, $hasTeam);

        } catch (\Exception $e) {
            Log::error('Error creating status update notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id ?? 'unknown'
            ]);
        }
    }

    private function getStatusMessage(AbsenceRequest $request, ?string $currentUserRole): string
    {
        $statusArabic = [
            'approved' => 'الموافقة على',
            'rejected' => 'رفض',
            'pending' => 'تعليق'
        ];

        if ($currentUserRole === 'hr') {
            $status = $request->hr_status;
            return "HR قام بـ " . ($statusArabic[$status] ?? 'تحديث') . " طلب الغياب";
        } else {
            $status = $request->manager_status;
            return "المدير قام بـ " . ($statusArabic[$status] ?? 'تحديث') . " طلب الغياب";
        }
    }

    private function sendAdditionalNotifications(AbsenceRequest $request, ?string $currentUserRole, bool $hasTeam): void
    {
        try {
            Log::info('Sending additional notifications', [
                'request_id' => $request->id,
                'current_user_role' => $currentUserRole,
                'has_team' => $hasTeam
            ]);

            if ($hasTeam && $currentUserRole === 'hr') {
                $this->notifyTeamOwners($request);
            }
            elseif (in_array($currentUserRole, ['team_leader', 'department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
                $this->notifyHR($request);
            }
        } catch (\Exception $e) {
            Log::error('Error sending additional notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id
            ]);
        }
    }

    public function notifyStatusReset(AbsenceRequest $request, string $responseType): void
    {
        try {
            Log::info('Notifying status reset', [
                'request_id' => $request->id,
                'response_type' => $responseType
            ]);

            $hasTeam = DB::table('team_user')
                ->where('team_user.user_id', $request->user_id)
                ->exists();

            $message = ($responseType === 'manager' ? 'المدير' : 'HR') . " قام بإعادة تعيين حالة طلب الغياب";

            // استخدام EmployeeNotificationService لإرسال الإشعار للموظف (يتضمن قاعدة البيانات، Firebase، و Slack)
            $notificationData = [
                'message' => $message,
                'request_id' => $request->id,
                'response_type' => $responseType,
                'has_team' => $hasTeam
            ];

            $this->employeeNotificationService->notifyEmployee($request, 'leave_request_status_reset', $notificationData);

            Log::info('Status reset notification sent successfully to employee', [
                'request_id' => $request->id,
                'user_id' => $request->user_id
            ]);

            $currentUserId = auth()->guard()->id();

            if ($responseType === 'manager' && $hasTeam) {
                $this->notifyHROfStatusReset($request, $currentUserId, $responseType);
            } elseif ($responseType === 'hr' && $hasTeam) {
                $this->notifyTeamOwnersOfStatusReset($request, $currentUserId, $responseType);
            }
        } catch (\Exception $e) {
            Log::error('Error notifying status reset', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id,
                'response_type' => $responseType
            ]);
        }
    }

    private function notifyHROfStatusReset(AbsenceRequest $request, $currentUserId, $responseType): void
    {
        try {
            $hrUsers = User::role('hr')->pluck('id');
            foreach ($hrUsers as $hrUserId) {
                if ($hrUserId !== $currentUserId) {
                    $hrUser = User::find($hrUserId);
                    $message = "المدير قام بإعادة تعيين حالة طلب الغياب للموظف {$request->user->name}";

                    $notification = Notification::create([
                        'user_id' => $hrUserId,
                        'type' => 'leave_request_status_reset',
                        'data' => [
                            'message' => $message,
                            'request_id' => $request->id,
                            'response_type' => $responseType,
                            'has_team' => true
                        ],
                        'related_id' => $request->id
                    ]);

                    Log::info('Created status reset notification for HR', [
                        'notification_id' => $notification->id,
                        'hr_user_id' => $hrUserId,
                        'request_id' => $request->id
                    ]);

                    // إرسال إشعار Firebase للمستخدم من فريق HR
                    if ($hrUser && !empty($hrUser->fcm_token)) {
                        $this->sendFirebaseToUser($hrUser, $message, 'reset', $request->id);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error notifying HR of status reset', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id
            ]);
        }
    }

    private function notifyTeamOwnersOfStatusReset(AbsenceRequest $request, $currentUserId, $responseType): void
    {
        try {
            $teamOwners = DB::table('team_user')
                ->join('teams', 'teams.id', '=', 'team_user.team_id')
                ->where('team_user.user_id', $request->user_id)
                ->where('team_user.role', 'owner')
                ->pluck('teams.user_id');

            foreach ($teamOwners as $ownerId) {
                if ($ownerId !== $currentUserId) {
                    $owner = User::find($ownerId);
                    $message = "HR قام بإعادة تعيين حالة طلب الغياب للموظف {$request->user->name}";

                    $notification = Notification::create([
                        'user_id' => $ownerId,
                        'type' => 'leave_request_status_reset',
                        'data' => [
                            'message' => $message,
                            'request_id' => $request->id,
                            'response_type' => $responseType,
                            'has_team' => true
                        ],
                        'related_id' => $request->id
                    ]);

                    Log::info('Created status reset notification for team owner', [
                        'notification_id' => $notification->id,
                        'owner_id' => $ownerId,
                        'request_id' => $request->id
                    ]);

                    // إرسال إشعار Firebase لمالك الفريق
                    if ($owner && !empty($owner->fcm_token)) {
                        $actionType = 'updated';
                        if ($request->hr_status === 'approved') {
                            $actionType = 'approved';
                        } elseif ($request->hr_status === 'rejected') {
                            $actionType = 'rejected';
                        }

                        $this->sendFirebaseToUser($owner, $message, $actionType, $request->id);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error notifying team owners of status reset', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id
            ]);
        }
    }

    public function notifyRequestModified(AbsenceRequest $request): void
    {
        try {
            Log::info('Notifying request modified', ['request_id' => $request->id]);

            $message = "{$request->user->name} قام بتعديل طلب الغياب";

            // تقديم إشعار لمدراء الفرق
            if ($request->user && $request->user->currentTeam && $request->user->currentTeam->owner) {
                Log::info('Notifying team managers of request modification', [
                    'request_id' => $request->id,
                    'team_id' => $request->user->currentTeam->id
                ]);

                $this->managerNotificationService->notifyManagers(
                    $request,
                    'leave_request_modified',
                    $message,
                    false
                );
            }

            // تقديم إشعار لقسم الموارد البشرية
            Log::info('Notifying HR department of request modification', ['request_id' => $request->id]);
            $this->managerNotificationService->notifyManagers(
                $request,
                'leave_request_modified',
                $message,
                true
            );
        } catch (\Exception $e) {
            Log::error('Error notifying request modified', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id ?? 'unknown'
            ]);
        }
    }

    public function notifyRequestDeleted(AbsenceRequest $request): void
    {
        try {
            Log::info('Notifying request deleted', ['request_id' => $request->id]);

            $message = "{$request->user->name} قام بحذف طلب الغياب";

            // تقديم إشعار لمدراء الفرق
            if ($request->user && $request->user->currentTeam && $request->user->currentTeam->owner) {
                Log::info('Notifying team managers of request deletion', [
                    'request_id' => $request->id,
                    'team_id' => $request->user->currentTeam->id
                ]);

                $this->managerNotificationService->notifyManagers(
                    $request,
                    'leave_request_deleted',
                    $message,
                    false
                );
            }

            // تقديم إشعار لقسم الموارد البشرية
            Log::info('Notifying HR department of request deletion', ['request_id' => $request->id]);
            $this->managerNotificationService->notifyManagers(
                $request,
                'leave_request_deleted',
                $message,
                true
            );
        } catch (\Exception $e) {
            Log::error('Error notifying request deleted', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id ?? 'unknown'
            ]);
        }
    }

    public function getUnreadCount(User $user): int
    {
        try {
            return Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();
        } catch (\Exception $e) {
            Log::error('Error getting unread count', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            return 0;
        }
    }

    public function getUserNotifications(User $user)
    {
        try {
            return Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } catch (\Exception $e) {
            Log::error('Error getting user notifications', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            return collect();
        }
    }

    public function markAsRead(Notification $notification): void
    {
        try {
            $notification->markAsRead();
            Log::info('Marked notification as read', ['notification_id' => $notification->id]);
        } catch (\Exception $e) {
            Log::error('Error marking notification as read', [
                'error' => $e->getMessage(),
                'notification_id' => $notification->id
            ]);
        }
    }

    private function notifyTeamOwners(AbsenceRequest $request): void
    {
        try {
            Log::info('Notifying team owners', ['request_id' => $request->id]);

            $message = "HR قام بالرد على طلب الغياب للموظف {$request->user->name}";

            // استخدام ManagerNotificationService الجديد الذي يحتوي على إشعارات Slack
            $this->managerNotificationService->notifyManagers(
                $request,
                'leave_request_hr_response',
                $message,
                false // false = تنبيه مالكي الفرق فقط، وليس HR
            );

        } catch (\Exception $e) {
            Log::error('Error notifying team owners', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id
            ]);
        }
    }

    private function notifyHR(AbsenceRequest $request): void
    {
        try {
            Log::info('Notifying HR department of manager response', ['request_id' => $request->id]);

            $hrUsers = User::role('hr')->get();
            foreach ($hrUsers as $hrUser) {
                $message = "المدير قام بالرد على طلب الغياب للموظف {$request->user->name}";

                $notification = Notification::create([
                    'user_id' => $hrUser->id,
                    'type' => 'leave_request_response_update',
                    'data' => [
                        'message' => $message,
                        'request_id' => $request->id,
                        'date' => $request->absence_date->format('Y-m-d'),
                        'status' => $request->manager_status,
                        'rejection_reason' => $request->manager_rejection_reason,
                        'is_manager_response' => true
                    ],
                    'related_id' => $request->id
                ]);

                Log::info('Created response update notification for HR', [
                    'notification_id' => $notification->id,
                    'hr_user_id' => $hrUser->id,
                    'request_id' => $request->id
                ]);

                // إرسال إشعار Firebase لمستخدم HR
                if (!empty($hrUser->fcm_token)) {
                    $actionType = 'updated';
                    if ($request->manager_status === 'approved') {
                        $actionType = 'approved';
                    } elseif ($request->manager_status === 'rejected') {
                        $actionType = 'rejected';
                    }

                    $this->sendFirebaseToUser($hrUser, $message, $actionType, $request->id);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error notifying HR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id
            ]);
        }
    }

    public function createResponseModificationNotification(AbsenceRequest $request, string $responseType): void
    {
        try {
            Log::info('Creating response modification notification', [
                'request_id' => $request->id,
                'response_type' => $responseType
            ]);

            $message = ($responseType === 'manager' ? 'المدير' : 'HR') . " قام بتعديل الرد على طلب الغياب";

            // استخدام EmployeeNotificationService لإرسال الإشعار (يتضمن قاعدة البيانات، Firebase، و Slack)
            $notificationData = [
                'message' => $message,
                'request_id' => $request->id,
                'date' => $request->absence_date->format('Y-m-d'),
                'status' => $responseType === 'manager' ? $request->manager_status : $request->hr_status,
                'rejection_reason' => $responseType === 'manager' ? $request->manager_rejection_reason : $request->hr_rejection_reason,
                'response_type' => $responseType,
                'final_status' => $request->status
            ];

            $this->employeeNotificationService->notifyEmployee($request, 'leave_request_response_modified', $notificationData);

            Log::info('Response modification notification sent successfully', [
                'request_id' => $request->id,
                'user_id' => $request->user_id
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating response modification notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id,
                'response_type' => $responseType
            ]);
            throw $e;
        }
    }

    public function createStatusResetNotification(AbsenceRequest $request, string $responseType): void
    {
        try {
            Log::info('Creating status reset notification', [
                'request_id' => $request->id,
                'response_type' => $responseType
            ]);

            $message = ($responseType === 'manager' ? 'المدير' : 'HR') . " قام بإعادة تعيين الرد على طلب الغياب";

            // استخدام EmployeeNotificationService لإرسال الإشعار (يتضمن قاعدة البيانات، Firebase، و Slack)
            $notificationData = [
                'message' => $message,
                'request_id' => $request->id,
                'date' => $request->absence_date->format('Y-m-d'),
                'response_type' => $responseType,
                'final_status' => $request->status
            ];

            $this->employeeNotificationService->notifyEmployee($request, 'leave_request_status_reset', $notificationData);

            Log::info('Status reset notification sent successfully', [
                'request_id' => $request->id,
                'user_id' => $request->user_id
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating status reset notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id,
                'response_type' => $responseType
            ]);
            throw $e;
        }
    }

    /**
     * إرسال إشعار Firebase إلى مستخدم باستخدام Queue
     */
    private function sendFirebaseToUser(User $user, string $message, string $actionType, int $requestId): void
    {
        try {
            if (!$user || empty($user->fcm_token)) {
                return;
            }

            $title = $this->getFirebaseTitle($actionType);
            $link = $this->getFirebaseLink($user, $requestId);

            Log::info('Queuing Firebase notification', [
                'user_id' => $user->id,
                'title' => $title,
                'action_type' => $actionType,
                'request_id' => $requestId
            ]);

            // استخدام Firebase queue بدلاً من الإرسال المباشر
            $result = $this->firebaseService->sendNotificationQueued(
                $user->fcm_token,
                $title,
                $message,
                $link
            );

            if ($result['success']) {
                Log::info('Firebase notification queued successfully', [
                    'user_id' => $user->id,
                    'title' => $title,
                    'request_id' => $requestId,
                    'queued' => $result['queued']
                ]);
            } else {
                Log::warning('Firebase notification queue failed, trying direct send', [
                    'user_id' => $user->id,
                    'title' => $title,
                    'request_id' => $requestId,
                    'error' => $result['message']
                ]);

                // Fallback للإرسال المباشر في حالة فشل الـ queue
                $this->firebaseService->sendNotification(
                    $user->fcm_token,
                    $title,
                    $message,
                    $link
                );
            }

        } catch (\Exception $e) {
            Log::error('Error queuing Firebase notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'request_id' => $requestId
            ]);

            // Fallback للإرسال المباشر في حالة الأخطاء
            try {
                $this->firebaseService->sendNotification(
                    $user->fcm_token,
                    $this->getFirebaseTitle($actionType),
                    $message,
                    $this->getFirebaseLink($user, $requestId)
                );
            } catch (\Exception $fallbackError) {
                Log::error('Firebase fallback notification also failed', [
                    'error' => $fallbackError->getMessage(),
                    'user_id' => $user->id,
                    'request_id' => $requestId
                ]);
            }
        }
    }

    /**
     * الحصول على عنوان الإشعار المناسب
     */
    private function getFirebaseTitle($actionType): string
    {
        $titles = [
            'approved' => 'طلب الغياب موافق',
            'rejected' => 'طلب الغياب مرفوض',
            'updated' => 'تحديث حالة طلب الغياب',
            'reset' => 'إعادة تعيين طلب الغياب',
            'created' => 'طلب غياب جديد',
            'deleted' => 'حذف طلب الغياب'
        ];

        return $titles[$actionType] ?? 'إشعار طلب غياب';
    }

    /**
     * الحصول على الرابط المناسب للإشعار
     */
    private function getFirebaseLink(User $user, int $requestId): string
    {
        // استخدام الرابط الموحد الجديد لجميع المستخدمين
        return "/absence-requests/{$requestId}";
    }
}
