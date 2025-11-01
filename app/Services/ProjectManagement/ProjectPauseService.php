<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProjectPauseService
{
    /**
     * توقيف مشروع
     */
    public function pauseProject(Project $project, string $reason, ?string $notes = null): array
    {
        try {
            DB::beginTransaction();

            $userId = Auth::id();

            $success = $project->pauseProject($reason, $notes, $userId);

            if ($success) {
                DB::commit();

                Log::info('Project paused', [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'pause_reason' => $reason,
                    'paused_by' => $userId
                ]);

                return [
                    'success' => true,
                    'message' => 'تم توقيف المشروع بنجاح',
                    'project' => $project->fresh()
                ];
            }

            DB::rollBack();
            return [
                'success' => false,
                'message' => 'فشل في توقيف المشروع'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error pausing project', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء توقيف المشروع: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إلغاء توقيف مشروع
     */
    public function resumeProject(Project $project): array
    {
        try {
            DB::beginTransaction();

            $success = $project->resumeProject();

            if ($success) {
                DB::commit();

                Log::info('Project resumed', [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'resumed_by' => Auth::id()
                ]);

                return [
                    'success' => true,
                    'message' => 'تم إلغاء توقيف المشروع وإعادة تشغيله',
                    'project' => $project->fresh()
                ];
            }

            DB::rollBack();
            return [
                'success' => false,
                'message' => 'فشل في إلغاء توقيف المشروع'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error resuming project', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء التوقيف: ' . $e->getMessage()
            ];
        }
    }

    /**
     * البحث عن المشاريع
     */
    public function searchProjects(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Project::query();

        // البحث بالاسم أو الكود
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // فلتر حسب الحالة
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // فلتر حسب العميل
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // فلتر المشاريع الموقوفة فقط
        if (!empty($filters['paused_only']) && $filters['paused_only']) {
            $query->where('status', 'موقوف');
        }

        // فلتر حسب سبب التوقيف
        if (!empty($filters['pause_reason'])) {
            $query->whereHas('activePause', function($q) use ($filters) {
                $q->where('pause_reason', $filters['pause_reason']);
            });
        }

        // فلتر حسب المدير
        if (!empty($filters['manager'])) {
            $query->where('manager', 'like', "%{$filters['manager']}%");
        }

        // فلتر حسب تاريخ تسليم العميل
        if (!empty($filters['client_date_from'])) {
            $query->where('client_agreed_delivery_date', '>=', $filters['client_date_from']);
        }
        if (!empty($filters['client_date_to'])) {
            $query->where('client_agreed_delivery_date', '<=', $filters['client_date_to']);
        }

        // فلتر حسب تاريخ تسليم الفريق
        if (!empty($filters['team_date_from'])) {
            $query->where('team_delivery_date', '>=', $filters['team_date_from']);
        }
        if (!empty($filters['team_date_to'])) {
            $query->where('team_delivery_date', '<=', $filters['team_date_to']);
        }

        // الترتيب
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // تحميل العلاقات المطلوبة
        return $query->with(['client', 'activePause.pausedBy', 'activePause.resumedBy'])->paginate(15);
    }

    /**
     * الحصول على إحصائيات المشاريع الموقوفة
     */
    public function getPausedProjectsStats(): array
    {
        // المشاريع الموقوفة حالياً
        $currentlyPausedProjects = Project::where('status', 'موقوف')->count();

        // المشاريع النشطة
        $activeProjects = Project::whereIn('status', ['جديد', 'جاري'])->count();

        // إجمالي المشاريع
        $totalProjects = Project::count();

        // جلب كل سجلات التوقيف (النشطة والمنتهية)
        $allPauses = \App\Models\ProjectPause::with(['project', 'pausedBy', 'resumedBy'])
            ->orderBy('paused_at', 'desc')
            ->get();

        $stats = [
            'total_paused' => $currentlyPausedProjects,
            'total_active' => $activeProjects,
            'pause_percentage' => $totalProjects > 0 ? round(($currentlyPausedProjects / $totalProjects) * 100, 1) : 0,
            'by_reason' => [],
            'recent_pauses' => []
        ];

        // توزيع أسباب التوقيف (جميع السجلات - نشطة ومنتهية)
        foreach (Project::getPauseReasons() as $key => $label) {
            $count = $allPauses->where('pause_reason', $key)->count();

            if ($count > 0) {
                $stats['by_reason'][$key] = [
                    'label' => $label,
                    'count' => $count
                ];
            }
        }

        // آخر 10 سجلات توقيف (سواء نشطة أو منتهية)
        $stats['recent_pauses'] = $allPauses->take(10);

        return $stats;
    }

    /**
     * توقيف مشاريع متعددة دفعة واحدة
     */
    public function pauseMultipleProjects(array $projectIds, string $reason, ?string $notes = null): array
    {
        try {
            DB::beginTransaction();

            $userId = Auth::id();
            $successCount = 0;
            $failedProjects = [];

            foreach ($projectIds as $projectId) {
                $project = Project::find($projectId);

                if (!$project) {
                    $failedProjects[] = [
                        'id' => $projectId,
                        'reason' => 'المشروع غير موجود'
                    ];
                    continue;
                }

                $success = $project->pauseProject($reason, $notes, $userId);

                if ($success) {
                    $successCount++;
                } else {
                    $failedProjects[] = [
                        'id' => $projectId,
                        'name' => $project->name,
                        'reason' => 'فشل في التوقيف'
                    ];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "تم توقيف {$successCount} مشروع بنجاح",
                'paused_count' => $successCount,
                'failed_count' => count($failedProjects),
                'failed_projects' => $failedProjects
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error pausing multiple projects', [
                'project_ids' => $projectIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء توقيف المشاريع: ' . $e->getMessage()
            ];
        }
    }
}

