<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Models\Project;
use App\Models\CompanyService;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProjectNotificationService
{
    use HasFirebaseNotification;

    /**
     * إشعار المستخدم بإضافته لمشروع جديد
     *
     * @param User
     * @param Project
     * @param CompanyService
     * @param User|null
     * @return void
     */
    public function notifyUserAddedToProject(User $user, Project $project, CompanyService $service, ?User $addedBy = null): void
    {
        try {
            // تجنب إرسال إشعار للمستخدم إذا أضاف نفسه
            if ($addedBy && $user->id === $addedBy->id) {
                return;
            }

            $addedByName = $addedBy ? $addedBy->name : 'المدير';

            $message = "تم إضافتك للمشروع \"{$project->name}\" في قسم \"{$service->name}\"";

            // إنشاء سجل الإشعار في قاعدة البيانات
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'project_participant_added',
                'data' => [
                    'message' => $message,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'added_by_id' => $addedBy?->id,
                    'added_by_name' => $addedByName,
                    'notification_time' => now()->format('Y-m-d H:i:s'),
                    'project_start_date' => $project->start_date,
                    'project_delivery_date' => $project->client_agreed_delivery_date ?? $project->team_delivery_date
                ],
                'related_id' => $project->id
            ]);

            // إرسال إشعار Firebase إذا كان لدى المستخدم FCM token
            if ($user->fcm_token) {
                $this->sendTypedFirebaseNotification(
                    $user,
                    'projects',
                    'added',
                    $message,
                    $project->id
                );
            }

            Log::info('تم إرسال إشعار إضافة مستخدم للمشروع', [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'service_id' => $service->id,
                'added_by' => $addedBy?->id
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في إرسال إشعار إضافة مستخدم للمشروع', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'project_id' => $project->id,
                'service_id' => $service->id
            ]);
        }
    }

    /**
     * إشعار operation assistant بمشروع جديد
     *
     * @param Project $project المشروع الجديد
     * @param User|null $createdBy المستخدم الذي أنشأ المشروع
     * @return void
     */
    public function notifyOperationAssistantNewProject(Project $project, ?User $createdBy = null): void
    {
        try {
            // البحث عن جميع المستخدمين بدور operation_assistant
            $operationAssistants = User::whereHas('roles', function ($query) {
                $query->where('name', 'operation_assistant');
            })->get();

            $createdByName = $createdBy ? $createdBy->name : 'النظام';
            $message = "مشروع جديد يحتاج إلى تعيين فريق العمل: \"{$project->name}\"";

            foreach ($operationAssistants as $assistant) {
                // إنشاء سجل الإشعار
                Notification::create([
                    'user_id' => $assistant->id,
                    'type' => 'new_project_assignment_needed',
                    'data' => [
                        'message' => $message,
                        'project_id' => $project->id,
                        'project_name' => $project->name,
                        'client_name' => $project->client->name ?? 'غير محدد',
                        'created_by_id' => $createdBy?->id,
                        'created_by_name' => $createdByName,
                        'notification_time' => now()->format('Y-m-d H:i:s'),
                        'project_start_date' => $project->start_date,
                        'project_end_date' => $project->end_date
                    ],
                    'related_id' => $project->id
                ]);

                // إرسال إشعار Firebase
                if ($assistant->fcm_token) {
                    $this->sendTypedFirebaseNotification(
                        $assistant,
                        'projects',
                        'new_assignment_needed',
                        $message,
                        $project->id
                    );
                }
            }

            Log::info('تم إرسال إشعار مشروع جديد لفريق العمليات', [
                'project_id' => $project->id,
                'assistants_count' => $operationAssistants->count(),
                'created_by' => $createdBy?->id
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في إرسال إشعار مشروع جديد لفريق العمليات', [
                'error' => $e->getMessage(),
                'project_id' => $project->id
            ]);
        }
    }

    /**
     * إشعار المستخدم بإزالته من المشروع
     *
     * @param User $user المستخدم المزال
     * @param Project $project المشروع
     * @param CompanyService $service الخدمة
     * @param User|null $removedBy المستخدم الذي أزاله
     * @return void
     */
    public function notifyUserRemovedFromProject(User $user, Project $project, CompanyService $service, ?User $removedBy = null): void
    {
        try {
            $removedByName = $removedBy ? $removedBy->name : 'المدير';
            $message = "تم إزالتك من المشروع \"{$project->name}\" في قسم \"{$service->name}\"";

            // إنشاء سجل الإشعار
            Notification::create([
                'user_id' => $user->id,
                'type' => 'project_participant_removed',
                'data' => [
                    'message' => $message,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'removed_by_id' => $removedBy?->id,
                    'removed_by_name' => $removedByName,
                    'notification_time' => now()->format('Y-m-d H:i:s')
                ],
                'related_id' => $project->id
            ]);

            // إرسال إشعار Firebase
            if ($user->fcm_token) {
                $this->sendTypedFirebaseNotification(
                    $user,
                    'projects',
                    'removed',
                    $message,
                    $project->id
                );
            }

        } catch (\Exception $e) {
            Log::error('خطأ في إرسال إشعار إزالة مستخدم من المشروع', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'project_id' => $project->id
            ]);
        }
    }
}
