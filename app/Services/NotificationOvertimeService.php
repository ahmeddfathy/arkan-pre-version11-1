<?php

namespace App\Services;

use App\Models\OverTimeRequests;
use App\Models\Notification;
use App\Services\Notifications\OvertimeEmployeeNotificationService;
use App\Services\Notifications\OvertimeManagerNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationOvertimeService
{
    protected $employeeNotificationService;
    protected $managerNotificationService;

    public function __construct(
        OvertimeEmployeeNotificationService $employeeNotificationService,
        OvertimeManagerNotificationService $managerNotificationService
    ) {
        $this->employeeNotificationService = $employeeNotificationService;
        $this->managerNotificationService = $managerNotificationService;
    }

    public function createOvertimeRequestNotification(OverTimeRequests $request): void
    {
        try {
            if ($request->user_id !== Auth::id()) {
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'new_overtime_request',
                    'تم تقديم طلب عمل إضافي جديد'
                );

                $this->managerNotificationService->notifyManagers(
                    $request,
                    'new_overtime_request_hr',
                    'تم تقديم طلب عمل إضافي جديد يحتاج لمراجعتك',
                    true
                );
            }
        } catch (\Exception $e) {
        }
    }

    public function notifyStatusUpdate(OverTimeRequests $request): void
    {
        try {
            // Determine the appropriate message based on the status
            $message = 'تم تحديث حالة طلب العمل الإضافي الخاص بك';

            if ($request->status === 'approved') {
                $message = 'تمت الموافقة على طلب العمل الإضافي الخاص بك';
            } elseif ($request->status === 'rejected') {
                $message = 'تم رفض طلب العمل الإضافي الخاص بك';

                // Add rejection reason if available
                if ($request->manager_status === 'rejected' && $request->manager_rejection_reason) {
                    $message .= ' - سبب الرفض من المدير: ' . $request->manager_rejection_reason;
                }

                if ($request->hr_status === 'rejected' && $request->hr_rejection_reason) {
                    $message .= ' - سبب الرفض من HR: ' . $request->hr_rejection_reason;
                }
            }

            // Notify the employee about the status update
            $this->employeeNotificationService->notifyEmployee(
                $request,
                'overtime_status_updated',
                $message
            );

            // If manager responded, notify HR
            if ($request->manager_status !== 'pending' && $request->hr_status === 'pending') {
                $hrMessage = 'تم الرد على طلب العمل الإضافي من قبل المدير';

                if ($request->manager_status === 'approved') {
                    $hrMessage = 'تمت الموافقة على طلب العمل الإضافي من قبل المدير';
                } elseif ($request->manager_status === 'rejected') {
                    $hrMessage = 'تم رفض طلب العمل الإضافي من قبل المدير';
                    if ($request->manager_rejection_reason) {
                        $hrMessage .= ' - السبب: ' . $request->manager_rejection_reason;
                    }
                }

                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_manager_response',
                    $hrMessage,
                    true
                );
            }

            // If HR responded, notify managers
            if ($request->hr_status !== 'pending' && $request->manager_status === 'pending') {
                $managerMessage = 'تم الرد على طلب العمل الإضافي من قبل HR';

                if ($request->hr_status === 'approved') {
                    $managerMessage = 'تمت الموافقة على طلب العمل الإضافي من قبل HR';
                } elseif ($request->hr_status === 'rejected') {
                    $managerMessage = 'تم رفض طلب العمل الإضافي من قبل HR';
                    if ($request->hr_rejection_reason) {
                        $managerMessage .= ' - السبب: ' . $request->hr_rejection_reason;
                    }
                }

                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_hr_response',
                    $managerMessage,
                    false
                );
            }
        } catch (\Exception $e) {
        }
    }

    public function notifyOvertimeDeleted(OverTimeRequests $request): void
    {
        try {
            $this->employeeNotificationService->notifyEmployee(
                $request,
                'overtime_request_deleted',
                'تم حذف طلب العمل الإضافي الخاص بك'
            );

            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_deleted',
                'تم حذف طلب العمل الإضافي',
                false
            );

            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_deleted_hr',
                'تم حذف طلب العمل الإضافي',
                true
            );
        } catch (\Exception $e) {
        }
    }

    public function deleteExistingStatusNotifications(OverTimeRequests $request): void
    {
        try {
            Notification::where('related_id', $request->id)
                ->whereIn('type', [
                    'overtime_status_updated',
                    'overtime_manager_response',
                    'overtime_hr_response'
                ])
                ->delete();
        } catch (\Exception $e) {
        }
    }

    public function notifyOvertimeModified(OverTimeRequests $request): void
    {
        try {
            // Delete all existing notifications for this request
            Notification::where('related_id', $request->id)
                ->whereIn('type', [
                    'overtime_request_modified',
                    'overtime_request_modified_hr'
                ])
                ->delete();

            // Create new notifications
            if ($request->user_id !== Auth::id()) {
                $this->employeeNotificationService->notifyEmployee(
                    $request,
                    'overtime_request_modified',
                    'تم تعديل طلب العمل الإضافي الخاص بك'
                );
            }

            // Notify managers
            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_modified',
                'تم تعديل طلب العمل الإضافي',
                false
            );

            // Notify HR
            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_request_modified_hr',
                'تم تعديل طلب العمل الإضافي',
                true
            );

            Log::info('Overtime modification notifications sent successfully for request ID: ' . $request->id);
        } catch (\Exception $e) {
            Log::error('Error sending overtime modification notification: ' . $e->getMessage());
        }
    }

    public function notifyManagerStatusUpdate(OverTimeRequests $request): void
    {
        try {
            if ($request->user_id !== Auth::id()) {
                $message = 'تم تحديث حالة طلب العمل الإضافي من قبل المدير';
                if ($request->manager_status === 'approved') {
                    $message = 'تمت الموافقة على طلب العمل الإضافي من قبل المدير';
                } elseif ($request->manager_status === 'rejected') {
                    $message = 'تم رفض طلب العمل الإضافي من قبل المدير';
                    if ($request->manager_rejection_reason) {
                        $message .= ' - السبب: ' . $request->manager_rejection_reason;
                    }
                }

                $this->employeeNotificationService->notifyEmployee(
                    $request,
                    'overtime_manager_status_updated',
                    $message
                );
            }

            $hrMessage = 'تم تحديث حالة طلب العمل الإضافي من قبل المدير';
            if ($request->manager_status === 'approved') {
                $hrMessage = 'تمت الموافقة على طلب العمل الإضافي من قبل المدير';
            } elseif ($request->manager_status === 'rejected') {
                $hrMessage = 'تم رفض طلب العمل الإضافي من قبل المدير';
                if ($request->manager_rejection_reason) {
                    $hrMessage .= ' - السبب: ' . $request->manager_rejection_reason;
                }
            }

            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_manager_status_updated_hr',
                $hrMessage,
                true
            );
        } catch (\Exception $e) {
            Log::error('Error sending manager status update notification: ' . $e->getMessage());
        }
    }

    public function notifyHRStatusUpdate(OverTimeRequests $request): void
    {
        try {
            if ($request->user_id !== Auth::id()) {
                $message = 'تم تحديث حالة طلب العمل الإضافي من قبل HR';
                if ($request->hr_status === 'approved') {
                    $message = 'تمت الموافقة على طلب العمل الإضافي من قبل HR';
                } elseif ($request->hr_status === 'rejected') {
                    $message = 'تم رفض طلب العمل الإضافي من قبل HR';
                    if ($request->hr_rejection_reason) {
                        $message .= ' - السبب: ' . $request->hr_rejection_reason;
                    }
                }

                $this->employeeNotificationService->notifyEmployee(
                    $request,
                    'overtime_hr_status_updated',
                    $message
                );
            }

            $managerMessage = 'تم تحديث حالة طلب العمل الإضافي من قبل HR';
            if ($request->hr_status === 'approved') {
                $managerMessage = 'تمت الموافقة على طلب العمل الإضافي من قبل HR';
            } elseif ($request->hr_status === 'rejected') {
                $managerMessage = 'تم رفض طلب العمل الإضافي من قبل HR';
                if ($request->hr_rejection_reason) {
                    $managerMessage .= ' - السبب: ' . $request->hr_rejection_reason;
                }
            }

            $this->managerNotificationService->notifyManagers(
                $request,
                'overtime_hr_status_updated_manager',
                $managerMessage,
                false
            );
        } catch (\Exception $e) {
            Log::error('Error sending HR status update notification: ' . $e->getMessage());
        }
    }

    public function notifyStatusReset(OverTimeRequests $request, string $type): void
    {
        try {
            // إرسال إشعار للموظف دائماً
            $message = 'تم إعادة تعيين حالة طلب العمل الإضافي';
            $notificationType = 'overtime_status_reset';

            if ($type === 'manager') {
                $message = 'تم إعادة تعيين حالة المدير لطلب العمل الإضافي';
                $notificationType = 'overtime_manager_status_reset';
            } elseif ($type === 'hr') {
                $message = 'تم إعادة تعيين حالة HR لطلب العمل الإضافي';
                $notificationType = 'overtime_hr_status_reset';
            }

            $this->employeeNotificationService->notifyEmployee(
                $request,
                $notificationType,
                $message
            );

            // إشعار للمدراء أو HR بإعادة التعيين
            if ($type === 'manager') {
                // إشعار HR بإعادة تعيين حالة المدير
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_manager_status_reset_hr',
                    'تم إعادة تعيين حالة المدير لطلب العمل الإضافي',
                    true
                );
            } elseif ($type === 'hr') {
                // إشعار المدراء بإعادة تعيين حالة HR
                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_hr_status_reset_manager',
                    'تم إعادة تعيين حالة HR لطلب العمل الإضافي',
                    false
                );
            }
        } catch (\Exception $e) {
            Log::error('Error sending status reset notification: ' . $e->getMessage());
        }
    }

    /**
     * Send notifications when a response is modified
     */
    public function notifyResponseModified(OverTimeRequests $request, string $responseType): void
    {
        try {
            // حذف الإشعارات السابقة المتعلقة بهذا النوع من الاستجابة
            Notification::where('related_id', $request->id)
                ->where(function($query) use ($responseType) {
                    $query->whereIn('type', [
                        'overtime_response_modified',
                        'overtime_manager_response_modified',
                        'overtime_hr_response_modified'
                    ]);

                    // حذف إشعارات إعادة التعيين المتعلقة بنفس نوع الاستجابة
                    if ($responseType === 'manager') {
                        $query->orWhereIn('type', [
                            'overtime_manager_status_reset',
                            'overtime_manager_status_reset_hr'
                        ]);
                    } elseif ($responseType === 'hr') {
                        $query->orWhereIn('type', [
                            'overtime_hr_status_reset',
                            'overtime_hr_status_reset_manager'
                        ]);
                    }
                })
                ->delete();

            // Notify the employee
            if ($request->user_id !== Auth::id()) {
                $message = 'تم تعديل الرد على طلب العمل الإضافي الخاص بك';

                if ($responseType === 'manager') {
                    $message = 'تم تعديل رد المدير على طلب العمل الإضافي الخاص بك';
                    if ($request->manager_status === 'approved') {
                        $message = 'تم تعديل رد المدير إلى موافقة على طلب العمل الإضافي الخاص بك';
                    } elseif ($request->manager_status === 'rejected') {
                        $message = 'تم تعديل رد المدير إلى رفض طلب العمل الإضافي الخاص بك';
                        if ($request->manager_rejection_reason) {
                            $message .= ' - السبب: ' . $request->manager_rejection_reason;
                        }
                    }
                } elseif ($responseType === 'hr') {
                    $message = 'تم تعديل رد HR على طلب العمل الإضافي الخاص بك';
                    if ($request->hr_status === 'approved') {
                        $message = 'تم تعديل رد HR إلى موافقة على طلب العمل الإضافي الخاص بك';
                    } elseif ($request->hr_status === 'rejected') {
                        $message = 'تم تعديل رد HR إلى رفض طلب العمل الإضافي الخاص بك';
                        if ($request->hr_rejection_reason) {
                            $message .= ' - السبب: ' . $request->hr_rejection_reason;
                        }
                    }
                }

                $this->employeeNotificationService->notifyEmployee(
                    $request,
                    'overtime_response_modified',
                    $message
                );
            }

            // Notify HR if manager response was modified
            if ($responseType === 'manager') {
                $hrMessage = 'تم تعديل رد المدير على طلب العمل الإضافي';

                if ($request->manager_status === 'approved') {
                    $hrMessage = 'تم تعديل رد المدير إلى موافقة على طلب العمل الإضافي';
                } elseif ($request->manager_status === 'rejected') {
                    $hrMessage = 'تم تعديل رد المدير إلى رفض طلب العمل الإضافي';
                    if ($request->manager_rejection_reason) {
                        $hrMessage .= ' - السبب: ' . $request->manager_rejection_reason;
                    }
                }

                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_manager_response_modified',
                    $hrMessage,
                    true
                );
            }

            // Notify managers if HR response was modified
            if ($responseType === 'hr') {
                $managerMessage = 'تم تعديل رد HR على طلب العمل الإضافي';

                if ($request->hr_status === 'approved') {
                    $managerMessage = 'تم تعديل رد HR إلى موافقة على طلب العمل الإضافي';
                } elseif ($request->hr_status === 'rejected') {
                    $managerMessage = 'تم تعديل رد HR إلى رفض طلب العمل الإضافي';
                    if ($request->hr_rejection_reason) {
                        $managerMessage .= ' - السبب: ' . $request->hr_rejection_reason;
                    }
                }

                $this->managerNotificationService->notifyManagers(
                    $request,
                    'overtime_hr_response_modified',
                    $managerMessage,
                    false
                );
            }

            Log::info('Overtime response modification notifications sent successfully for request ID: ' . $request->id);
        } catch (\Exception $e) {
            Log::error('Error sending overtime response modification notification: ' . $e->getMessage());
        }
    }
}
