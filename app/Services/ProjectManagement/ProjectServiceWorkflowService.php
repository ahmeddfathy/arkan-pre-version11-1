<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
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
}

