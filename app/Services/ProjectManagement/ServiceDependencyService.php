<?php

namespace App\Services\ProjectManagement;

use App\Models\CompanyService;
use App\Models\ServiceDependency;
use App\Models\Project;
use App\Models\ProjectServiceUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceDependencyService
{
    /**
     * الحصول على الخدمات التي تعتمد على خدمة معينة
     * (الخدمات التي ستبدأ بعد اكتمال الخدمة المحددة)
     */
    public function getDependentServices(int $serviceId): array
    {
        $service = CompanyService::find($serviceId);
        if (!$service) {
            return [];
        }

        return $service->dependentServices()
                      ->with('department')
                      ->get()
                      ->toArray();
    }

    /**
     * الحصول على الخدمات التي تعتمد عليها خدمة معينة
     * (الخدمات التي يجب أن تكتمل قبل بدء الخدمة المحددة)
     */
    public function getServiceDependencies(int $serviceId): array
    {
        $service = CompanyService::find($serviceId);
        if (!$service) {
            return [];
        }

        return $service->dependencies()
                      ->with('department')
                      ->get()
                      ->toArray();
    }

    /**
     * إضافة اعتمادية جديدة
     * "service_id تعتمد على depends_on_service_id"
     */
    public function addDependency(int $serviceId, int $dependsOnServiceId, ?string $notes = null): array
    {
        try {
            // التحقق من عدم إضافة خدمة لتعتمد على نفسها
            if ($serviceId === $dependsOnServiceId) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن للخدمة أن تعتمد على نفسها'
                ];
            }

            // التحقق من وجود الخدمتين
            $service = CompanyService::find($serviceId);
            $dependsOnService = CompanyService::find($dependsOnServiceId);

            if (!$service || !$dependsOnService) {
                return [
                    'success' => false,
                    'message' => 'إحدى الخدمات غير موجودة'
                ];
            }

            // التحقق من عدم وجود تكرار
            $exists = ServiceDependency::where('service_id', $serviceId)
                                      ->where('depends_on_service_id', $dependsOnServiceId)
                                      ->exists();

            if ($exists) {
                return [
                    'success' => false,
                    'message' => 'هذه الاعتمادية موجودة بالفعل'
                ];
            }

            // إنشاء الاعتمادية
            $dependency = ServiceDependency::create([
                'service_id' => $serviceId,
                'depends_on_service_id' => $dependsOnServiceId,
                'notes' => $notes
            ]);

            Log::info('تم إضافة اعتمادية خدمة جديدة', [
                'service_id' => $serviceId,
                'depends_on_service_id' => $dependsOnServiceId
            ]);

            return [
                'success' => true,
                'message' => 'تم إضافة الاعتمادية بنجاح',
                'dependency' => $dependency
            ];

        } catch (\Exception $e) {
            Log::error('خطأ في إضافة اعتمادية الخدمة', [
                'error' => $e->getMessage(),
                'service_id' => $serviceId,
                'depends_on_service_id' => $dependsOnServiceId
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الاعتمادية: ' . $e->getMessage()
            ];
        }
    }

    /**
     * حذف اعتمادية
     */
    public function removeDependency(int $serviceId, int $dependsOnServiceId): array
    {
        try {
            $deleted = ServiceDependency::where('service_id', $serviceId)
                                       ->where('depends_on_service_id', $dependsOnServiceId)
                                       ->delete();

            if ($deleted) {
                Log::info('تم حذف اعتمادية الخدمة', [
                    'service_id' => $serviceId,
                    'depends_on_service_id' => $dependsOnServiceId
                ]);

                return [
                    'success' => true,
                    'message' => 'تم حذف الاعتمادية بنجاح'
                ];
            }

            return [
                'success' => false,
                'message' => 'الاعتمادية غير موجودة'
            ];

        } catch (\Exception $e) {
            Log::error('خطأ في حذف اعتمادية الخدمة', [
                'error' => $e->getMessage(),
                'service_id' => $serviceId,
                'depends_on_service_id' => $dependsOnServiceId
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الاعتمادية: ' . $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على المشاركين في الخدمات التي تعتمد على خدمة معينة في مشروع محدد
     * (المستخدمون الذين سيتلقون الإشعارات عند اكتمال الخدمة)
     */
    public function getUsersToNotify(int $projectId, int $completedServiceId): array
    {
        try {
            // الحصول على الخدمات التي تعتمد على الخدمة المكتملة
            $dependentServices = CompanyService::find($completedServiceId)
                                              ->dependentServices()
                                              ->pluck('id')
                                              ->toArray();

            if (empty($dependentServices)) {
                return [];
            }

            // الحصول على المشاركين في هذه الخدمات في المشروع المحدد
            $users = ProjectServiceUser::where('project_id', $projectId)
                                      ->whereIn('service_id', $dependentServices)
                                      ->with(['user', 'service'])
                                      ->get()
                                      ->map(function ($psu) {
                                          return [
                                              'user' => $psu->user,
                                              'service' => $psu->service,
                                              'service_id' => $psu->service_id
                                          ];
                                      })
                                      ->toArray();

            return $users;

        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على المستخدمين للإشعار', [
                'error' => $e->getMessage(),
                'project_id' => $projectId,
                'completed_service_id' => $completedServiceId
            ]);

            return [];
        }
    }

    /**
     * التحقق من اكتمال جميع الاعتماديات لخدمة معينة في مشروع محدد
     * (التحقق من أن جميع الخدمات المطلوبة قد اكتملت)
     */
    public function areAllDependenciesCompleted(int $projectId, int $serviceId): bool
    {
        try {
            // الحصول على الخدمات التي تعتمد عليها الخدمة المحددة
            $service = CompanyService::find($serviceId);
            if (!$service) {
                return false;
            }

            $requiredServices = $service->dependencies()->pluck('id')->toArray();

            if (empty($requiredServices)) {
                // لا توجد اعتماديات - يمكن البدء
                return true;
            }

            // التحقق من حالة كل خدمة مطلوبة في المشروع
            foreach ($requiredServices as $requiredServiceId) {
                $status = DB::table('project_service')
                           ->where('project_id', $projectId)
                           ->where('service_id', $requiredServiceId)
                           ->value('service_status');

                // إذا كانت الخدمة غير مكتملة
                if ($status !== 'مكتملة') {
                    return false;
                }
            }

            // جميع الاعتماديات مكتملة
            return true;

        } catch (\Exception $e) {
            Log::error('خطأ في التحقق من اكتمال الاعتماديات', [
                'error' => $e->getMessage(),
                'project_id' => $projectId,
                'service_id' => $serviceId
            ]);

            return false;
        }
    }

    /**
     * الحصول على جميع الاعتماديات لكل الخدمات (للعرض في صفحة الإعدادات)
     */
    public function getAllDependencies(): array
    {
        return ServiceDependency::with(['service', 'dependsOnService'])
                                ->orderBy('service_id')
                                ->get()
                                ->groupBy('service_id')
                                ->map(function ($dependencies, $serviceId) {
                                    $service = $dependencies->first()->service;
                                    return [
                                        'service_id' => $serviceId,
                                        'service_name' => $service->name,
                                        'execution_order' => $service->execution_order,
                                        'dependencies' => $dependencies->map(function ($dep) {
                                            return [
                                                'id' => $dep->id,
                                                'depends_on_service_id' => $dep->depends_on_service_id,
                                                'depends_on_service_name' => $dep->dependsOnService->name,
                                                'notes' => $dep->notes
                                            ];
                                        })->toArray()
                                    ];
                                })
                                ->values()
                                ->toArray();
    }
}

