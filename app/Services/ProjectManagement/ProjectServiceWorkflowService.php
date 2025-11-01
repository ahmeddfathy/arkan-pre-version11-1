<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskRevision;
use Illuminate\Support\Facades\DB;

/**
 * Service لحساب workflow الخدمات في المشاريع
 */
class ProjectServiceWorkflowService
{
    /**
     * الحصول على workflow الخدمات لمشروع معين
     * مرتبة حسب execution_order مع حالة كل خدمة
     */
    public function getProjectServicesWorkflow(int $projectId): array
    {
        // جلب الخدمات الفريدة فقط (بدون تكرار) باستخدام groupBy
        $services = DB::table('project_service')
            ->join('company_services', 'project_service.service_id', '=', 'company_services.id')
            ->where('project_service.project_id', $projectId)
            ->select(
                'company_services.id',
                'company_services.name',
                'company_services.execution_order',
                DB::raw('MAX(project_service.service_status) as service_status')
            )
            ->groupBy('company_services.id', 'company_services.name', 'company_services.execution_order')
            ->orderBy('company_services.execution_order', 'asc')
            ->orderBy('company_services.name', 'asc')
            ->get();

        $workflow = [];
        $completedCount = 0;
        $totalCount = $services->count();

        foreach ($services as $service) {
            // حساب الحالة الفعلية بناءً على المشاركين
            $effectiveStatus = $this->calculateEffectiveServiceStatus($projectId, $service->id);

            // جلب الموظفين المشاركين في هذه الخدمة
            $participants = $this->getServiceParticipants($projectId, $service->id);

            // حساب التعديلات لهذه الخدمة
            $revisionsData = $this->getServiceRevisionsData($projectId, $service->id);

            $workflow[] = [
                'id' => $service->id,
                'name' => $service->name,
                'execution_order' => $service->execution_order ?? 1,
                'status' => $service->service_status ?? 'لم تبدأ',
                'effective_status' => $effectiveStatus,
                'status_class' => $this->getStatusClass($effectiveStatus),
                'status_icon' => $this->getStatusIcon($effectiveStatus),
                'status_label' => $this->getStatusLabel($effectiveStatus),
                'participants' => $participants, // إضافة معلومات المشاركين
                'revisions_count' => $revisionsData['total'], // عدد التعديلات الإجمالي
                'revisions_data' => $revisionsData, // بيانات التعديلات التفصيلية
            ];

            if (in_array($effectiveStatus, ['مكتملة', 'تسليم نهائي'])) {
                $completedCount++;
            }
        }

        $progressPercentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

        return [
            'services' => $workflow,
            'total' => $totalCount,
            'completed' => $completedCount,
            'progress_percentage' => $progressPercentage,
        ];
    }

    /**
     * جلب المشاركين في خدمة معينة مع حالاتهم
     */
    private function getServiceParticipants(int $projectId, int $serviceId): array
    {
        $participants = DB::table('project_service_user')
            ->join('users', 'project_service_user.user_id', '=', 'users.id')
            ->where('project_service_user.project_id', $projectId)
            ->where('project_service_user.service_id', $serviceId)
            ->select(
                'users.id',
                'users.name',
                'project_service_user.status',
                'project_service_user.delivered_at',
                'project_service_user.deadline'
            )
            ->get();

        $participantsList = [];

        foreach ($participants as $participant) {
            $statusInfo = $this->getParticipantStatusInfo($participant->status, $participant->delivered_at, $participant->deadline);

            $participantsList[] = [
                'id' => $participant->id,
                'name' => $participant->name,
                'status' => $participant->status ?? 'جاري',
                'status_icon' => $statusInfo['icon'],
                'status_color' => $statusInfo['color'],
                'delivered' => !is_null($participant->delivered_at),
                'delivered_at' => $participant->delivered_at,
                'deadline' => $participant->deadline,
            ];
        }

        return $participantsList;
    }

    /**
     * الحصول على معلومات حالة المشارك (أيقونة ولون)
     */
    private function getParticipantStatusInfo(?string $status, $deliveredAt, $deadline): array
    {
        // إذا تم التسليم النهائي
        if ($status === 'تم تسليم نهائي') {
            return ['icon' => '✅', 'color' => '#10b981'];
        }

        // إذا تم تسليم المسودة
        if ($status === 'تسليم مسودة') {
            return ['icon' => '📋', 'color' => '#f59e0b'];
        }

        // إذا كان جاري العمل
        if ($status === 'جاري') {
            return ['icon' => '🔄', 'color' => '#3b82f6'];
        }

        // إذا كان موقوف أو واقف
        if (in_array($status, ['موقوف', 'واقف ع النموذج', 'واقف ع الأسئلة', 'واقف ع العميل', 'واقف ع مكالمة'])) {
            return ['icon' => '⏸️', 'color' => '#ec4899'];
        }

        // الحالة الافتراضية
        return ['icon' => '⏳', 'color' => '#6b7280'];
    }

    /**
     * حساب الحالة الفعلية للخدمة بناءً على حالات المشاركين فيها
     */
    private function calculateEffectiveServiceStatus(int $projectId, int $serviceId): string
    {
        // الحصول على حالات جميع المشاركين في هذه الخدمة
        $participants = DB::table('project_service_user')
            ->where('project_id', $projectId)
            ->where('service_id', $serviceId)
            ->select('status')
            ->get();

        if ($participants->isEmpty()) {
            // لا يوجد مشاركين - نستخدم حالة الخدمة نفسها
            return DB::table('project_service')
                ->where('project_id', $projectId)
                ->where('service_id', $serviceId)
                ->value('service_status') ?? 'لم تبدأ';
        }

        $statuses = $participants->pluck('status')->toArray();

        // إذا كل المشاركين سلموا نهائي → الخدمة مكتملة تماماً
        $finalDeliveryCount = count(array_filter($statuses, fn($s) => $s === 'تم تسليم نهائي'));
        if ($finalDeliveryCount === count($statuses)) {
            return 'تسليم نهائي';
        }

        // إذا كل المشاركين سلموا مسودة على الأقل → الخدمة في مرحلة المراجعة
        $draftDeliveryCount = count(array_filter($statuses, fn($s) => in_array($s, ['تسليم مسودة', 'تم تسليم نهائي'])));
        if ($draftDeliveryCount === count($statuses)) {
            return 'تسليم مسودة';
        }

        // إذا فيه مشاركين شغالين → قيد التنفيذ
        $inProgressCount = count(array_filter($statuses, fn($s) => $s === 'جاري'));
        if ($inProgressCount > 0) {
            return 'قيد التنفيذ';
        }

        // إذا فيه مشاركين واقفين → معلقة
        $pausedCount = count(array_filter($statuses, fn($s) => in_array($s, ['موقوف', 'واقف ع النموذج', 'واقف ع الأسئلة', 'واقف ع العميل', 'واقف ع مكالمة'])));
        if ($pausedCount > 0) {
            return 'معلقة';
        }

        return 'لم تبدأ';
    }

    /**
     * الحصول على CSS class للحالة
     */
    private function getStatusClass(string $status): string
    {
        return match($status) {
            'مكتملة', 'تسليم نهائي' => 'step-completed',
            'تسليم مسودة' => 'step-draft',
            'قيد التنفيذ' => 'step-current',
            'معلقة' => 'step-paused',
            'ملغية' => 'step-cancelled',
            default => 'step-pending',
        };
    }

    /**
     * الحصول على Icon للحالة
     */
    private function getStatusIcon(string $status): string
    {
        return match($status) {
            'مكتملة', 'تسليم نهائي' => '✅',
            'تسليم مسودة' => '📋',
            'قيد التنفيذ' => '🔄',
            'معلقة' => '⏸️',
            'ملغية' => '❌',
            default => '⏸️',
        };
    }

    /**
     * الحصول على Label للحالة
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'مكتملة' => 'مكتملة',
            'تسليم نهائي' => 'تسليم نهائي ✓',
            'تسليم مسودة' => 'تسليم مسودة',
            'قيد التنفيذ' => 'جاري العمل',
            'معلقة' => 'معلقة',
            'ملغية' => 'ملغية',
            'لم تبدأ' => 'لم تبدأ',
            default => $status,
        };
    }

    /**
     * حساب بيانات التعديلات التفصيلية لخدمة معينة
     */
    private function getServiceRevisionsData(int $projectId, int $serviceId): array
    {
        // 1. جلب IDs المهام (Tasks) المرتبطة بهذه الخدمة
        $taskIds = Task::where('project_id', $projectId)
            ->where('service_id', $serviceId)
            ->pluck('id')
            ->toArray();

        $allRevisions = collect();

        // 2. التعديلات على المهام العادية لهذه الخدمة
        if (!empty($taskIds)) {
            // التعديلات المرتبطة بـ task_id مباشرة
            $directTaskRevisions = TaskRevision::where('revision_type', 'task')
                ->whereIn('task_id', $taskIds)
                ->get();

            // التعديلات المرتبطة بـ task_user_id (عن طريق TaskUser -> Task -> service_id)
            $taskUserRevisions = TaskRevision::whereHas('taskUser.task', function($query) use ($projectId, $serviceId) {
                $query->where('project_id', $projectId)
                      ->where('service_id', $serviceId);
            })
            ->get();

            $allRevisions = $allRevisions->merge($directTaskRevisions)->merge($taskUserRevisions);
        }

        // 3. التعديلات على المشروع المرتبطة بأشخاص من هذه الخدمة (responsible/executor)
        $serviceUserIds = DB::table('project_service_user')
            ->where('project_id', $projectId)
            ->where('service_id', $serviceId)
            ->pluck('user_id')
            ->toArray();

        if (!empty($serviceUserIds)) {
            $projectRevisions = TaskRevision::where('revision_type', 'project')
                ->where('project_id', $projectId)
                ->where(function($query) use ($serviceUserIds) {
                    $query->whereIn('responsible_user_id', $serviceUserIds)
                          ->orWhereIn('executor_user_id', $serviceUserIds);
                })
                ->get();

            $allRevisions = $allRevisions->merge($projectRevisions);
        }

        // 4. التعديلات على مهام القوالب المرتبطة بهذه الخدمة
        $templateTaskUserIds = DB::table('template_task_user')
            ->where('project_id', $projectId)
            ->whereIn('template_task_id', function($query) use ($serviceId) {
                $query->select('id')
                    ->from('template_tasks')
                    ->whereIn('task_template_id', function($subQuery) use ($serviceId) {
                        $subQuery->select('id')
                            ->from('task_templates')
                            ->where('service_id', $serviceId);
                    });
            })
            ->pluck('id')
            ->toArray();

        if (!empty($templateTaskUserIds)) {
            $templateRevisions = TaskRevision::whereIn('template_task_user_id', $templateTaskUserIds)
                ->get();

            $allRevisions = $allRevisions->merge($templateRevisions);
        }

        // إزالة التكرارات
        $allRevisions = $allRevisions->unique('id');

        // تصنيف التعديلات حسب المصدر والحالة
        return [
            'total' => $allRevisions->count(),
            'internal' => $allRevisions->where('revision_source', 'internal')->count(),
            'external' => $allRevisions->where('revision_source', 'external')->count(),
            'by_status' => [
                'new' => $allRevisions->where('status', 'new')->count(),
                'in_progress' => $allRevisions->where('status', 'in_progress')->count(),
                'paused' => $allRevisions->where('status', 'paused')->count(),
                'completed' => $allRevisions->where('status', 'completed')->count(),
            ],
        ];
    }
}

