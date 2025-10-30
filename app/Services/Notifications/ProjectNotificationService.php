<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Models\Project;
use App\Models\CompanyService;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * إشعار جميع المشاركين في المشروع برفع مرفق جديد في الفولدرات الثابتة
     *
     * @param Project $project المشروع
     * @param string $folderName اسم الفولدر (مرفقات أولية، تقارير مكالمات، مرفقات من العميل)
     * @param string $fileName اسم الملف المرفوع
     * @param User|null $uploadedBy المستخدم الذي رفع الملف
     * @return void
     */
    public function notifyProjectParticipantsOfAttachment(Project $project, string $folderName, string $fileName, ?User $uploadedBy = null): void
    {
        try {

            $notifiableFolders = ['مرفقات أولية', 'تقارير مكالمات', 'مرفقات من العميل'];


            if (!in_array($folderName, $notifiableFolders)) {
                return;
            }


            $participants = DB::table('project_service_user')
                ->where('project_id', $project->id)
                ->distinct()
                ->pluck('user_id');

            $uploadedByName = $uploadedBy ? $uploadedBy->name : 'أحد أعضاء الفريق';
            $projectCode = $project->code ?? 'غير محدد';

            $message = "تم رفع مرفق جديد في المشروع [{$projectCode}] - {$folderName}";
            $detailedMessage = "قام {$uploadedByName} برفع ملف \"{$fileName}\" في فولدر \"{$folderName}\" للمشروع \"{$project->name}\" (كود: {$projectCode})";

            foreach ($participants as $participantId) {

                if ($uploadedBy && $participantId == $uploadedBy->id) {
                    continue;
                }

                $participant = User::find($participantId);
                if (!$participant) {
                    continue;
                }

                Notification::create([
                    'user_id' => $participant->id,
                    'type' => 'project_attachment_uploaded',
                    'data' => [
                        'message' => $message,
                        'detailed_message' => $detailedMessage,
                        'project_id' => $project->id,
                        'project_name' => $project->name,
                        'project_code' => $projectCode,
                        'folder_name' => $folderName,
                        'file_name' => $fileName,
                        'uploaded_by_id' => $uploadedBy?->id,
                        'uploaded_by_name' => $uploadedByName,
                        'notification_time' => now()->format('Y-m-d H:i:s'),
                    ],
                    'related_id' => $project->id
                ]);

                if ($participant->fcm_token) {
                    $this->sendTypedFirebaseNotification(
                        $participant,
                        'projects',
                        'attachment_uploaded',
                        $message,
                        $project->id
                    );
                }
            }

            Log::info('تم إرسال إشعارات رفع مرفق لمشاركي المشروع', [
                'project_id' => $project->id,
                'folder_name' => $folderName,
                'file_name' => $fileName,
                'participants_count' => count($participants),
                'uploaded_by' => $uploadedBy?->id
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في إرسال إشعارات رفع مرفق للمشروع', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'folder_name' => $folderName
            ]);
        }
    }

    /**
     * إشعار المشاركين في خدمة بأن الخدمات التي تعتمد عليها قد اكتملت
     *
     * @param Project $project المشروع
     * @param CompanyService $completedService الخدمة التي اكتملت
     * @param array $usersToNotify المستخدمون الذين سيتم إشعارهم مع خدماتهم
     * @param User|null $completedBy المستخدم الذي أكمل الخدمة
     * @return void
     */
    public function notifyDependentServiceParticipants(
        Project $project,
        CompanyService $completedService,
        array $usersToNotify,
        ?User $completedBy = null
    ): void {
        try {
            if (empty($usersToNotify)) {
                return;
            }

            $projectCode = $project->code ?? 'غير محدد';
            $completedByName = $completedBy ? $completedBy->name : 'الفريق';

            // تجميع المستخدمين حسب الخدمة لإرسال إشعار واحد لكل مستخدم
            $userNotifications = [];
            foreach ($usersToNotify as $item) {
                $userId = $item['user']->id;
                if (!isset($userNotifications[$userId])) {
                    $userNotifications[$userId] = [
                        'user' => $item['user'],
                        'services' => []
                    ];
                }
                $userNotifications[$userId]['services'][] = $item['service']->name;
            }

            // إرسال إشعار لكل مستخدم
            foreach ($userNotifications as $userId => $data) {
                $user = $data['user'];
                $services = $data['services'];

                $servicesText = count($services) > 1
                    ? implode('، ', $services)
                    : $services[0];

                $message = "تم اكتمال خدمة \"{$completedService->name}\" في المشروع [{$projectCode}] - يمكنك الآن البدء في خدمة: {$servicesText}";
                $detailedMessage = "قام {$completedByName} بإكمال خدمة \"{$completedService->name}\" في المشروع \"{$project->name}\" (كود: {$projectCode}). الخدمات التي يمكنك البدء فيها الآن: {$servicesText}";

                // إنشاء سجل الإشعار
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'service_dependency_completed',
                    'data' => [
                        'message' => $message,
                        'detailed_message' => $detailedMessage,
                        'project_id' => $project->id,
                        'project_name' => $project->name,
                        'project_code' => $projectCode,
                        'completed_service_id' => $completedService->id,
                        'completed_service_name' => $completedService->name,
                        'dependent_services' => $services,
                        'completed_by_id' => $completedBy?->id,
                        'completed_by_name' => $completedByName,
                        'notification_time' => now()->format('Y-m-d H:i:s'),
                    ],
                    'related_id' => $project->id
                ]);

                // إرسال إشعار Firebase
                if ($user->fcm_token) {
                    $this->sendTypedFirebaseNotification(
                        $user,
                        'projects',
                        'service_ready',
                        $message,
                        $project->id
                    );
                }
            }

            Log::info('تم إرسال إشعارات اكتمال الخدمات المعتمد عليها', [
                'project_id' => $project->id,
                'completed_service_id' => $completedService->id,
                'users_notified' => count($userNotifications),
                'completed_by' => $completedBy?->id
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في إرسال إشعارات الخدمات المعتمد عليها', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'completed_service_id' => $completedService->id
            ]);
        }
    }
}
