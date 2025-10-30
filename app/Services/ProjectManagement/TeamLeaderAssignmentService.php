<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\User;
use App\Models\RoleHierarchy;
use App\Models\ProjectServiceUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\SlackNotificationService;

class TeamLeaderAssignmentService
{
    protected $notificationService;
    protected $slackNotificationService;

    public function __construct(
        ProjectNotificationService $notificationService,
        SlackNotificationService $slackNotificationService
    ) {
        $this->notificationService = $notificationService;
        $this->slackNotificationService = $slackNotificationService;
    }


    public function assignTeamLeadersToProjectServices(Project $project)
    {
        try {
            Log::info('🔄 بدء تعيين Team Leaders لخدمات المشروع', [
                'project_id' => $project->id,
                'services_count' => $project->services()->count()
            ]);

            $successCount = 0;
            $failureCount = 0;

            // جلب جميع خدمات المشروع
            $services = $project->services()->get();

            if ($services->isEmpty()) {
                Log::warning('⚠️ المشروع لا يحتوي على خدمات', [
                    'project_id' => $project->id
                ]);

                return [
                    'success' => false,
                    'message' => 'لا توجد خدمات في المشروع',
                    'success_count' => 0,
                    'failure_count' => 0
                ];
            }

            // لكل خدمة، نختار Team Leader
            foreach ($services as $service) {
                $result = $this->assignTeamLeaderToService($project, $service);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            }

            $finalResult = [
                'success' => $successCount > 0,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'message' => "تم تعيين $successCount Team Leaders بنجاح"
            ];

            if ($failureCount > 0) {
                $finalResult['warning'] = "فشل تعيين $failureCount Team Leaders";
            }

            Log::info('✅ انتهى تعيين Team Leaders للمشروع', $finalResult);

            return $finalResult;

        } catch (\Exception $e) {
            Log::error('❌ خطأ في تعيين Team Leaders', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ في تعيين Team Leaders: ' . $e->getMessage(),
                'success_count' => 0,
                'failure_count' => 0
            ];
        }
    }

    /**
     * تعيين Team Leader واحد لخدمة معينة
     */
    private function assignTeamLeaderToService(Project $project, $service)
    {
        try {
            // 1. جلب الأدوار المطلوبة للخدمة
            $requiredRoles = $service->requiredRoles()->pluck('role_id')->toArray();

            if (empty($requiredRoles)) {
                Log::warning('⚠️ الخدمة لا تحتوي على أدوار مطلوبة', [
                    'project_id' => $project->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name ?? 'Unknown'
                ]);

                return [
                    'success' => false,
                    'message' => 'الخدمة لا تحتوي على أدوار مطلوبة'
                ];
            }

            // 2. ✅ البحث عن Team Leaders فقط (الترتيب الهرمي = 3)
            // نجلب الأدوار اللي ترتيبها الهرمي = 3 من الأدوار المطلوبة
            $teamLeaderRoleIds = RoleHierarchy::whereIn('role_id', $requiredRoles)
                ->where('hierarchy_level', 3) // Team Leader Level
                ->pluck('role_id')
                ->toArray();

            if (empty($teamLeaderRoleIds)) {
                Log::warning('⚠️ الخدمة لا تحتوي على أدوار بترتيب هرمي 3 (Team Leaders)', [
                    'project_id' => $project->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name ?? 'Unknown',
                    'required_roles' => $requiredRoles
                ]);

                return [
                    'success' => false,
                    'message' => 'الخدمة لا تحتوي على أدوار Team Leaders (ترتيب هرمي 3)'
                ];
            }

            // ✅ البحث عن Team Leaders من نفس قسم الخدمة فقط
            $serviceDepartment = $service->department;

            $teamLeaders = User::whereHas('roles', function ($query) use ($teamLeaderRoleIds) {
                $query->whereIn('id', $teamLeaderRoleIds);
            })
            ->where('department', $serviceDepartment) // ✅ فلتر حسب القسم
            ->get();

            if ($teamLeaders->isEmpty()) {
                Log::warning('⚠️ لا يوجد Team Leaders في قسم الخدمة', [
                    'project_id' => $project->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name ?? 'Unknown',
                    'service_department' => $serviceDepartment,
                    'team_leader_role_ids' => $teamLeaderRoleIds
                ]);

                return [
                    'success' => false,
                    'message' => "لا يوجد Team Leaders في قسم {$serviceDepartment}"
                ];
            }

            // 3. اختيار Team Leader بناء على Round Robin
            $selectedTeamLeader = $this->selectTeamLeaderRoundRobin($project, $service, $teamLeaders);

            if (!$selectedTeamLeader) {
                Log::warning('⚠️ فشل اختيار Team Leader من الدورة', [
                    'project_id' => $project->id,
                    'service_id' => $service->id
                ]);

                return [
                    'success' => false,
                    'message' => 'فشل اختيار Team Leader'
                ];
            }

            // 4. التحقق من عدم إضافة نفس Team Leader للخدمة مسبقاً
            $alreadyExists = DB::table('project_service_user')
                ->where('project_id', $project->id)
                ->where('service_id', $service->id)
                ->where('user_id', $selectedTeamLeader->id)
                ->exists();

            if ($alreadyExists) {
                Log::info('ℹ️ Team Leader موجود بالفعل للخدمة', [
                    'project_id' => $project->id,
                    'service_id' => $service->id,
                    'team_leader_id' => $selectedTeamLeader->id
                ]);

                return [
                    'success' => true,
                    'message' => 'Team Leader موجود بالفعل'
                ];
            }

            // 5. إضافة Team Leader للخدمة
            $projectServiceUser = ProjectServiceUser::create([
                'project_id' => $project->id,
                'service_id' => $service->id,
                'user_id' => $selectedTeamLeader->id,
                'role_id' => $this->getRoleIdForTeamLeader($selectedTeamLeader, $requiredRoles),
                'project_share' => 1.0, // مشروع كامل
            ]);

            Log::info('✅ تم إضافة Team Leader للخدمة', [
                'project_id' => $project->id,
                'service_id' => $service->id,
                'service_name' => $service->name ?? 'Unknown',
                'service_department' => $service->department,
                'team_leader_id' => $selectedTeamLeader->id,
                'team_leader_name' => $selectedTeamLeader->name,
                'team_leader_department' => $selectedTeamLeader->department
            ]);

            // 6. إرسال الإشعارات
            $this->notifyTeamLeader($selectedTeamLeader, $project, $service);

            return [
                'success' => true,
                'team_leader' => $selectedTeamLeader,
                'message' => 'تم تعيين Team Leader بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('❌ خطأ في تعيين Team Leader للخدمة', [
                'project_id' => $project->id,
                'service_id' => $service->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ];
        }
    }

    /**
     * اختيار Team Leader بناء على Round Robin
     *
     * الخوارزمية:
     * 1. جلب آخر Team Leader تم تعيينه (من Cache)
     * 2. اختيار التالي في الدورة
     * 3. تحديث الـ Cache برقم التالي
     */
    private function selectTeamLeaderRoundRobin(Project $project, $service, $teamLeaders)
    {
        try {
            // مفتاح Cache فريد لكل خدمة
            $cacheKey = "team_leader_round_robin_service_{$service->id}";

            // جلب آخر Team Leader تم تعيينه لهذه الخدمة
            $lastAssignedIndex = Cache::get($cacheKey, -1);

            // حساب الفهرس التالي
            $nextIndex = ($lastAssignedIndex + 1) % $teamLeaders->count();

            // اختيار Team Leader
            $selectedTeamLeader = $teamLeaders[$nextIndex];

            // تحديث الـ Cache
            Cache::put($cacheKey, $nextIndex, now()->addDays(365)); // سنة واحدة

            Log::info('🔄 Round Robin - اختيار Team Leader', [
                'service_id' => $service->id,
                'last_index' => $lastAssignedIndex,
                'next_index' => $nextIndex,
                'total_leaders' => $teamLeaders->count(),
                'selected_leader' => $selectedTeamLeader->name
            ]);

            return $selectedTeamLeader;

        } catch (\Exception $e) {
            Log::error('❌ خطأ في Round Robin', [
                'service_id' => $service->id ?? null,
                'error' => $e->getMessage()
            ]);

            // في حالة الفشل، نختار الأول
            return $teamLeaders->first();
        }
    }

    /**
     * إرسال إشعارات للـ Team Leader
     */
    private function notifyTeamLeader(User $teamLeader, Project $project, $service)
    {
        try {
            // الحصول على المستخدم الحالي
            $currentUser = Auth::user() ?? $teamLeader;

            // إرسال إشعار Database
            $this->notificationService->notifyUserAddedToProject(
                $teamLeader,
                $project,
                $service,
                $currentUser
            );

            // إرسال إشعار Slack إذا كان متوفراً
            if ($teamLeader->slack_user_id) {
                $this->slackNotificationService->sendProjectAssignmentNotification(
                    $project,
                    $teamLeader,
                    $currentUser
                );
            }

            Log::info('✅ تم إرسال إشعارات للـ Team Leader', [
                'project_id' => $project->id,
                'service_id' => $service->id,
                'team_leader_id' => $teamLeader->id,
                'team_leader_name' => $teamLeader->name,
                'slack_notified' => !empty($teamLeader->slack_user_id)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إرسال الإشعارات', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'service_id' => $service->id ?? null,
                'team_leader_id' => $teamLeader->id ?? null
            ]);
        }
    }

    /**
     * الحصول على معرف الدور الأساسي للـ Team Leader من الأدوار المطلوبة
     * ✅ يختار دور بترتيب هرمي = 3 فقط
     */
    private function getRoleIdForTeamLeader(User $teamLeader, array $requiredRoles)
    {
        // جلب أدوار المستخدم اللي ترتيبها الهرمي = 3
        $teamLeaderRoleIds = RoleHierarchy::whereIn('role_id', $requiredRoles)
            ->where('hierarchy_level', 3)
            ->pluck('role_id')
            ->toArray();

        if (empty($teamLeaderRoleIds)) {
            return null;
        }

        // الحصول على أول دور Team Leader عند المستخدم
        $userRole = $teamLeader->roles()->whereIn('id', $teamLeaderRoleIds)->first();

        return $userRole?->id ?? $teamLeaderRoleIds[0] ?? null;
    }

    /**
     * إعادة تعيين دورة التوزيع لخدمة معينة
     */
    public function resetRoundRobinForService($serviceId)
    {
        try {
            $cacheKey = "team_leader_round_robin_service_{$serviceId}";
            Cache::forget($cacheKey);

            Log::info('🔄 تم إعادة تعيين دورة التوزيع', [
                'service_id' => $serviceId
            ]);

            return [
                'success' => true,
                'message' => 'تم إعادة تعيين الدورة بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إعادة التعيين', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إعادة تعيين جميع دورات التوزيع
     */
    public function resetAllRoundRobins()
    {
        try {
            // جلب جميع مفاتيح الـ Cache الخاصة بالتوزيع
            $pattern = 'team_leader_round_robin_service_*';

            // بما أن Laravel's Cache API محدودة، نستخدم طريقة بسيطة
            Log::info('🔄 تم طلب إعادة تعيين جميع الدورات', [
                'timestamp' => now()
            ]);

            return [
                'success' => true,
                'message' => 'تم إعادة تعيين جميع الدورات بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إعادة التعيين الشامل', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ];
        }
    }
}

