<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\CompanyService;
use App\Models\RoleHierarchy;
use App\Services\ProjectManagement\ProjectSidebarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceAnalysisController extends Controller
{
    protected $sidebarService;

    public function __construct(ProjectSidebarService $sidebarService)
    {
        $this->sidebarService = $sidebarService;
    }
    /**
     * عرض صفحة تحليل الأداء للمراجع العام
     */
    public function index(Request $request)
    {
        $query = Project::with(['client', 'services', 'serviceParticipants.user.roles']);

        // الحصول على الأدوار التي لها hierarchy_level = 2 (المراجعين)
        $reviewerRoleIds = RoleHierarchy::getReviewerRoleIds();

        // البحث بكود المشروع
        if ($request->filled('project_code')) {
            $query->where('code', 'like', '%' . $request->project_code . '%');
        }

        // البحث بالمراجع (hierarchy_level = 2)
        if ($request->filled('reviewer_id')) {
            $query->whereHas('serviceParticipants', function ($q) use ($request, $reviewerRoleIds) {
                $q->where('user_id', $request->reviewer_id)
                    ->whereIn('role_id', $reviewerRoleIds);
            });
        }

        // البحث بالخدمة
        if ($request->filled('service_id')) {
            $query->whereHas('services', function ($q) use ($request) {
                $q->where('company_services.id', $request->service_id);
            });
        }

        // البحث بالحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // البحث بالشهر والسنة
        if ($request->filled('month_year')) {
            $monthYear = $request->month_year; // Format: YYYY-MM
            $query->whereYear('created_at', substr($monthYear, 0, 4))
                ->whereMonth('created_at', substr($monthYear, 5, 2));
        }

        // البحث بفترة زمنية محددة
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        // جلب المراجعين (المستخدمين الذين لديهم أدوار بـ hierarchy_level = 2)
        $reviewers = \App\Models\User::whereHas('projectServiceUsers', function ($q) use ($reviewerRoleIds) {
            $q->whereIn('role_id', $reviewerRoleIds);
        })->orderBy('name')->get();

        $services = CompanyService::orderBy('name')->get();
        $statuses = ['جديد', 'قيد التنفيذ', 'مكتمل', 'معلق', 'ملغي'];

        return view('performance-analysis.index', compact('projects', 'reviewers', 'services', 'statuses', 'reviewerRoleIds'));
    }

    /**
     * عرض تفاصيل مشروع معين مع جميع بياناته
     */
    public function show($id)
    {
        $project = Project::with(['client', 'services'])->findOrFail($id);

        // جلب خدمات المشروع مع البيانات الديناميكية
        $projectServices = DB::table('project_service')
            ->where('project_id', $project->id)
            ->get();

        $servicesWithData = [];
        foreach ($projectServices as $projectService) {
            $service = CompanyService::with('dataFields')->find($projectService->service_id);
            if ($service) {
                // ✅ استخدام الـ Service لجلب إحصائيات التعديلات والأخطاء
                $stats = $this->sidebarService->getServiceStats($project, $service);

                $servicesWithData[] = [
                    'service' => $service,
                    'service_data' => json_decode($projectService->service_data, true) ?? [],
                    'service_status' => $projectService->service_status,
                    'stats' => $stats, // ✅ إضافة الإحصائيات
                    'created_at' => $projectService->created_at,
                    'updated_at' => $projectService->updated_at
                ];
            }
        }

        return view('performance-analysis.show', compact('project', 'servicesWithData'));
    }

    /**
     * البحث بكود المشروع
     */
    public function searchByCode(Request $request)
    {
        $projectCode = $request->project_code;

        if (empty($projectCode)) {
            return redirect()->route('performance-analysis.index')
                ->with('error', 'يرجى إدخال كود المشروع');
        }

        $project = Project::where('code', $projectCode)->first();

        if (!$project) {
            return redirect()->route('performance-analysis.index')
                ->with('error', 'لم يتم العثور على مشروع بهذا الكود: ' . $projectCode);
        }

        return redirect()->route('performance-analysis.show', $project->id);
    }

    /**
     * تصدير البيانات كـ Excel
     */
    public function export(Request $request)
    {
        // TODO: إضافة تصدير Excel
        return response()->json(['message' => 'سيتم إضافة التصدير قريباً']);
    }
}
