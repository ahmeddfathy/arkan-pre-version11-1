<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\TaskRevision;
use App\Models\EmployeeError;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class ProjectSidebarService
{
    /**
     * Get project details for sidebar
     */
    public function getSidebarDetails(Project $project): array
    {
        try {
            // Load all necessary relationships
            $project->load([
                'client',
                'services.dataFields' => function($query) {
                    $query->active()->ordered();
                },
                'serviceParticipants.user',
                'serviceParticipants.service',
                'serviceParticipants.role',
                'season'
            ]);

            // Get project custom fields (from custom_fields_data)
            $customFieldsData = $project->custom_fields_data ?? [];
            $projectCustomFields = \App\Models\ProjectField::active()->ordered()->get()->map(function ($field) use ($customFieldsData) {
                $value = $customFieldsData[$field->field_key] ?? null;

                // Format value based on field type
                if ($value !== null && $value !== '') {
                    if ($field->field_type === 'date') {
                        $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
                    } elseif ($field->field_type === 'number') {
                        $value = number_format($value);
                    }
                } else {
                    $value = 'غير محدد';
                }

                return [
                    'label' => $field->name,
                    'value' => $value,
                    'type' => $field->field_type
                ];
            })->values()->toArray();

            // Get service data fields (from ServiceDataField)
            $serviceDataFields = [];
            foreach ($project->services as $service) {
                // Use the pre-loaded dataFields relationship
                $serviceFields = $service->dataFields
                    ->map(function ($field) use ($service) {
                        // Get the service data from pivot
                        $serviceData = [];

                        if ($service->pivot && $service->pivot->service_data) {
                            // If service_data is already an array (Laravel casts it)
                            if (is_array($service->pivot->service_data)) {
                                $serviceData = $service->pivot->service_data;
                            } else {
                                // If it's still a string, decode it
                                $serviceData = json_decode($service->pivot->service_data, true) ?? [];
                            }
                        }

                        $value = $serviceData[$field->field_name] ?? null;

                        // Format value based on field type
                        if ($value !== null && $value !== '') {
                            if ($field->field_type === 'date') {
                                $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
                            } elseif ($field->field_type === 'number') {
                                $value = number_format($value);
                            }
                        } else {
                            $value = 'غير محدد';
                        }

                        return [
                            'label' => $field->field_label,
                            'value' => $value,
                            'type' => $field->field_type
                        ];
                    });

                // Show service tab even if fields are empty
                if ($serviceFields->isNotEmpty()) {
                    $serviceDataFields[$service->name] = $serviceFields;
                }
            }

            // Get participants with deadlines
            $participants = $project->serviceParticipants->map(function ($participant) {
                return [
                    'user_name' => $participant->user->name,
                    'user_email' => $participant->user->email,
                    'service_name' => $participant->service->name ?? 'غير محدد',
                    'role_name' => $participant->role->name ?? 'غير محدد',
                    'deadline' => $participant->deadline ? $participant->deadline->format('Y-m-d H:i') : null,
                    'is_overdue' => $participant->isOverdue(),
                    'days_remaining' => $participant->getDaysRemaining(),
                    'project_share' => $participant->getProjectShareLabel(),
                    'is_acknowledged' => $participant->is_acknowledged,
                ];
            });

            // ✅ حساب عدد التعديلات والأخطاء للمشروع
            $stats = $this->getProjectStats($project);

            // جلب تاريخ فترات التحضير
            $preparationHistory = \App\Models\ProjectPreparationHistory::getHistoryForProject($project->id);
            $preparationHistoryFormatted = $preparationHistory->map(function($history) {
                return [
                    'id' => $history->id,
                    'preparation_start_date' => $history->preparation_start_date ? $history->preparation_start_date->format('Y-m-d H:i') : null,
                    'preparation_days' => $history->preparation_days,
                    'preparation_end_date' => $history->preparation_end_date ? $history->preparation_end_date->format('Y-m-d H:i') : null,
                    'notes' => $history->notes,
                    'user_name' => $history->user ? $history->user->name : 'غير محدد',
                    'is_current' => $history->is_current,
                    'is_active' => $history->isActive(),
                    'effective_from' => $history->effective_from ? $history->effective_from->format('Y-m-d H:i') : null,
                    'time_ago' => $history->time_ago,
                    'duration_text' => $history->duration_text,
                ];
            });

            return [
                'success' => true,
                'data' => [
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'code' => $project->code,
                        'status' => $project->status,
                        'is_urgent' => $project->is_urgent,
                        'description' => $project->description,
                        'start_date' => $project->start_date ? $project->start_date->format('Y-m-d') : null,
                        'team_delivery_date' => $project->team_delivery_date ? $project->team_delivery_date->format('Y-m-d') : null,
                        'client_agreed_delivery_date' => $project->client_agreed_delivery_date ? $project->client_agreed_delivery_date->format('Y-m-d') : null,
                        'actual_delivery_date' => $project->actual_delivery_date ? $project->actual_delivery_date->format('Y-m-d') : null,
                        'manager' => $project->manager,
                        'total_points' => $project->total_points,
                        'completion_percentage' => $project->completion_percentage,
                        'client_name' => $project->client->name ?? 'غير محدد',
                        'season_name' => $project->season->name ?? 'غير محدد',
                        'preparation_enabled' => $project->preparation_enabled,
                        'preparation_start_date' => $project->preparation_start_date ? $project->preparation_start_date->format('Y-m-d H:i') : null,
                        'preparation_end_date' => $project->preparation_end_date ? $project->preparation_end_date->format('Y-m-d H:i') : null,
                        'preparation_days' => $project->preparation_days,
                        'preparation_status' => $project->preparation_status,
                        'remaining_preparation_days' => $project->remaining_preparation_days,
                    ],
                    'services' => $project->services->map(function ($service) use ($project) {
                        // حساب إحصائيات الخدمة
                        $serviceStats = $this->getServiceStats($project, $service);

                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'points' => $service->points,
                            'status' => $service->pivot->service_status ?? 'لم تبدأ',
                            'stats' => $serviceStats, // إحصائيات الخدمة
                        ];
                    }),
                    'participants' => $participants,
                    'project_custom_fields' => $projectCustomFields,
                    'service_data_fields' => $serviceDataFields,
                    'stats' => $stats, // ✅ إضافة الإحصائيات
                    'preparation_history' => $preparationHistoryFormatted, // ✅ تاريخ فترات التحضير
                    'preparation_periods_count' => $preparationHistory->count(), // عدد الفترات
                ],
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching sidebar details', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل البيانات',
                'status_code' => 500
            ];
        }
    }

    /**
     * Get project participants for revisions
     */
    public function getProjectParticipants(Project $project): array
    {
        try {
            // Get unique users from project participants
            $participants = $project->serviceParticipants()
                ->with('user:id,name,email')
                ->get()
                ->pluck('user')
                ->unique('id')
                ->values()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ];
                });

            return [
                'success' => true,
                'participants' => $participants,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ في تحميل المشاركين',
                'status_code' => 500
            ];
        }
    }

    /**
     * حساب إحصائيات خدمة معينة (التعديلات والأخطاء)
     */
    public function getServiceStats(Project $project, $service): array
    {
        try {
            // ============================================
            // حساب التعديلات للخدمة (نفس منطق getProjectStats)
            // ============================================

            // 1. جلب IDs المهام (Tasks) المرتبطة بهذه الخدمة
            $taskIds = Task::where('project_id', $project->id)
                ->where('service_id', $service->id)
                ->pluck('id')
                ->toArray();

            // 2. التعديلات على المهام العادية لهذه الخدمة
            $taskRevisions = collect();
            if (!empty($taskIds)) {
                // ✅ التعديلات المرتبطة بـ task_id مباشرة
                $directTaskRevisions = TaskRevision::where('revision_type', 'task')
                    ->whereIn('task_id', $taskIds)
                    ->get();

                // ✅ التعديلات المرتبطة بـ task_user_id (عن طريق TaskUser -> Task -> service_id)
                $taskUserRevisions = TaskRevision::whereHas('taskUser.task', function($query) use ($project, $service) {
                    $query->where('project_id', $project->id)
                          ->where('service_id', $service->id);
                })
                ->get();

                // دمج النوعين
                $taskRevisions = $directTaskRevisions->merge($taskUserRevisions)->unique('id');
            }

            // ✅ 2.5 التعديلات على المشروع المرتبطة بأشخاص من هذه الخدمة (responsible/executor)
            // جلب IDs الأشخاص اللي شغالين في الخدمة دي في المشروع
            $serviceUserIds = \App\Models\ProjectServiceUser::where('project_id', $project->id)
                ->where('service_id', $service->id)
                ->pluck('user_id')
                ->toArray();

            $projectRevisionsForService = collect();
            if (!empty($serviceUserIds)) {
                $projectRevisionsForService = TaskRevision::where('revision_type', 'project')
                    ->where('project_id', $project->id)
                    ->where(function($query) use ($serviceUserIds) {
                        $query->whereIn('responsible_user_id', $serviceUserIds)
                              ->orWhereIn('executor_user_id', $serviceUserIds);
                    })
                    ->get();
            }

            // 3. جلب IDs مهام القوالب المرتبطة بهذه الخدمة
            $templateTaskUserIds = \App\Models\TemplateTaskUser::where('project_id', $project->id)
                ->whereHas('templateTask.template', function($query) use ($service) {
                    $query->where('service_id', $service->id);
                })
                ->pluck('id')
                ->toArray();

            // 4. التعديلات على مهام القوالب (نفس الطريقة في getProjectStats)
            $templateTaskRevisions = collect();
            if (!empty($templateTaskUserIds)) {
                // ✅ نفس المنطق: whereIn template_task_user_id مباشرة
                $templateTaskRevisions = TaskRevision::whereIn('template_task_user_id', $templateTaskUserIds)
                    ->get();
            }

            // دمج كل التعديلات للخدمة
            $allRevisions = $taskRevisions
                ->merge($templateTaskRevisions)
                ->merge($projectRevisionsForService)
                ->unique('id'); // عشان ميكررش لو كان نفس التعديل مرتبط بأكتر من طريقة

            // Logging للـ debug
            Log::info('Service Revisions Debug', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'serviceUserIds_count' => count($serviceUserIds),
                'serviceUserIds' => $serviceUserIds,
                'projectRevisionsForService_count' => $projectRevisionsForService->count(),
                'taskRevisions_count' => $taskRevisions->count(),
                'templateTaskRevisions_count' => $templateTaskRevisions->count(),
                'allRevisions_count' => $allRevisions->count(),
            ]);

            // إحصائيات التعديلات
            $revisionsStats = [
                'total' => $allRevisions->count(),
                'new' => $allRevisions->where('status', 'new')->count(),
                'in_progress' => $allRevisions->where('status', 'in_progress')->count(),
                'completed' => $allRevisions->where('status', 'completed')->count(),
            ];

            // ============================================
            // حساب الأخطاء للخدمة
            // ============================================

            // 1. أخطاء ProjectServiceUser لهذه الخدمة
            $projectServiceErrors = \App\Models\EmployeeError::whereHasMorph(
                'errorable',
                [\App\Models\ProjectServiceUser::class],
                function ($query) use ($project, $service) {
                    $query->where('project_id', $project->id)
                          ->where('service_id', $service->id);
                }
            )->get();

            // 2. أخطاء TaskUser المرتبطة بمهام هذه الخدمة
            $taskUserErrors = \App\Models\EmployeeError::whereHasMorph(
                'errorable',
                [\App\Models\TaskUser::class],
                function ($query) use ($taskIds) {
                    $query->whereHas('task', function ($taskQuery) use ($taskIds) {
                        $taskQuery->whereIn('id', $taskIds);
                    });
                }
            )->get();

            // 3. أخطاء TemplateTaskUser لهذه الخدمة
            $templateTaskUserErrors = \App\Models\EmployeeError::whereHasMorph(
                'errorable',
                [\App\Models\TemplateTaskUser::class],
                function ($query) use ($templateTaskUserIds) {
                    $query->whereIn('id', $templateTaskUserIds);
                }
            )->get();

            // دمج كل الأخطاء
            $allErrors = $projectServiceErrors
                ->merge($taskUserErrors)
                ->merge($templateTaskUserErrors);

            // إحصائيات الأخطاء
            $errorsStats = [
                'total' => $allErrors->count(),
                'critical' => $allErrors->where('error_type', 'critical')->count(),
                'normal' => $allErrors->where('error_type', 'normal')->count(),
            ];

            return [
                'revisions' => $revisionsStats,
                'errors' => $errorsStats,
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating service stats', [
                'project_id' => $project->id,
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'revisions' => ['total' => 0, 'new' => 0, 'in_progress' => 0, 'completed' => 0],
                'errors' => ['total' => 0, 'critical' => 0, 'normal' => 0],
            ];
        }
    }

    /**
     * حساب إحصائيات المشروع (التعديلات والأخطاء)
     */
    private function getProjectStats(Project $project): array
    {
        try {
            // ============================================
            // حساب التعديلات (Revisions)
            // ============================================

            // 1. التعديلات المباشرة على المشروع (revision_type = 'project')
            $projectRevisions = TaskRevision::where('revision_type', 'project')
                ->where('project_id', $project->id)
                ->get();

            // 2. التعديلات على المهام العادية التابعة للمشروع (Task)
            $taskIds = Task::where('project_id', $project->id)->pluck('id');
            $taskRevisions = TaskRevision::where('revision_type', 'task')
                ->whereIn('task_id', $taskIds)
                ->get();

            // 3. التعديلات على مهام القوالب التابعة للمشروع (TemplateTaskUser)
            $templateTaskUserIds = \App\Models\TemplateTaskUser::where('project_id', $project->id)
                ->pluck('id');
            $templateTaskRevisions = TaskRevision::whereIn('template_task_user_id', $templateTaskUserIds)
                ->get();

            // دمج كل التعديلات
            $allRevisions = $projectRevisions
                ->merge($taskRevisions)
                ->merge($templateTaskRevisions);

            // Logging للتعديلات الإجمالية
            Log::info('Project Total Revisions', [
                'project_id' => $project->id,
                'total_count' => $allRevisions->count(),
                'revisions_details' => $allRevisions->map(function($rev) {
                    return [
                        'id' => $rev->id,
                        'title' => $rev->title,
                        'revision_type' => $rev->revision_type,
                        'task_id' => $rev->task_id,
                        'task_user_id' => $rev->task_user_id,
                        'template_task_user_id' => $rev->template_task_user_id,
                        'responsible_user_id' => $rev->responsible_user_id,
                        'executor_user_id' => $rev->executor_user_id,
                    ];
                })->toArray()
            ]);

            // إحصائيات التعديلات حسب الحالة
            $revisionsStats = [
                'total' => $allRevisions->count(),
                'new' => $allRevisions->where('status', 'new')->count(),
                'in_progress' => $allRevisions->where('status', 'in_progress')->count(),
                'paused' => $allRevisions->where('status', 'paused')->count(),
                'completed' => $allRevisions->where('status', 'completed')->count(),
                // إحصائيات حسب الموافقة
                'pending' => $allRevisions->where('approval_status', 'pending')->count(),
                'approved' => $allRevisions->where('approval_status', 'approved')->count(),
                'rejected' => $allRevisions->where('approval_status', 'rejected')->count(),
                // إحصائيات حسب المصدر
                'internal' => $allRevisions->where('revision_source', 'internal')->count(),
                'external' => $allRevisions->where('revision_source', 'external')->count(),
            ];

            // ============================================
            // حساب الأخطاء (Errors)
            // ============================================

            // 1. الأخطاء المرتبطة بـ ProjectServiceUser للمشروع
            $projectServiceErrors = EmployeeError::whereHasMorph(
                'errorable',
                [\App\Models\ProjectServiceUser::class],
                function ($query) use ($project) {
                    $query->where('project_id', $project->id);
                }
            )->get();

            // 2. الأخطاء المرتبطة بـ TaskUser (المهام العادية التابعة للمشروع)
            $taskUserErrors = EmployeeError::whereHasMorph(
                'errorable',
                [\App\Models\TaskUser::class],
                function ($query) use ($project) {
                    $query->whereHas('task', function ($taskQuery) use ($project) {
                        $taskQuery->where('project_id', $project->id);
                    });
                }
            )->get();

            // 3. الأخطاء المرتبطة بـ TemplateTaskUser (مهام القوالب التابعة للمشروع)
            $templateTaskUserErrors = EmployeeError::whereHasMorph(
                'errorable',
                [\App\Models\TemplateTaskUser::class],
                function ($query) use ($project) {
                    $query->where('project_id', $project->id);
                }
            )->get();

            // دمج كل الأخطاء
            $allErrors = $projectServiceErrors
                ->merge($taskUserErrors)
                ->merge($templateTaskUserErrors);

            // إحصائيات الأخطاء
            $errorsStats = [
                'total' => $allErrors->count(),
                'critical' => $allErrors->where('error_type', 'critical')->count(),
                'normal' => $allErrors->where('error_type', 'normal')->count(),
                // إحصائيات حسب التصنيف
                'by_category' => [
                    'quality' => $allErrors->where('error_category', 'quality')->count(),
                    'deadline' => $allErrors->where('error_category', 'deadline')->count(),
                    'communication' => $allErrors->where('error_category', 'communication')->count(),
                    'technical' => $allErrors->where('error_category', 'technical')->count(),
                    'procedural' => $allErrors->where('error_category', 'procedural')->count(),
                    'other' => $allErrors->where('error_category', 'other')->count(),
                ],
            ];

            return [
                'revisions' => $revisionsStats,
                'errors' => $errorsStats,
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating project stats', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            // إرجاع قيم افتراضية في حالة الخطأ
            return [
                'revisions' => [
                    'total' => 0,
                    'new' => 0,
                    'in_progress' => 0,
                    'paused' => 0,
                    'completed' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'internal' => 0,
                    'external' => 0,
                ],
                'errors' => [
                    'total' => 0,
                    'critical' => 0,
                    'normal' => 0,
                    'by_category' => [
                        'quality' => 0,
                        'deadline' => 0,
                        'communication' => 0,
                        'technical' => 0,
                        'procedural' => 0,
                        'other' => 0,
                    ],
                ],
            ];
        }
    }
}

