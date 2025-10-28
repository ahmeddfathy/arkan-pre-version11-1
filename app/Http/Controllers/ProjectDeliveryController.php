<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDelivery;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProjectDeliveryController extends Controller
{
    /**
     * عرض صفحة إدارة تسليمات المشاريع
     */
    public function index(Request $request)
    {
        // تسجيل النشاط
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'project_deliveries',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة إدارة تسليمات المشاريع');
        }

        // تجهيز الفلاتر
        $filters = $this->prepareFilters($request);

        // جلب المشاريع مع التسليمات
        $projects = $this->getProjectsWithDeliveries($filters);

        // جلب جميع المشاريع للـ datalist
        $allProjects = Project::select('id', 'name', 'code')
            ->orderBy('code')
            ->get();

        // جلب العملاء للفلترة
        $clients = Client::orderBy('name')->get();

        // أنواع التسليم
        $deliveryTypes = ProjectDelivery::getDeliveryTypes();

        // حساب الإحصائيات
        $stats = $this->calculateStats();

        return view('projects.deliveries.index', compact(
            'projects',
            'allProjects',
            'clients',
            'deliveryTypes',
            'filters',
            'stats'
        ));
    }

    /**
     * تسليم مشروع
     */
    public function deliver(Request $request, $projectId)
    {
        try {
            $request->validate([
                'delivery_type' => 'required|string',
                'notes' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $project = Project::findOrFail($projectId);

            // إنشاء تسليم جديد
            $delivery = ProjectDelivery::create([
                'project_id' => $project->id,
                'delivery_type' => $request->delivery_type,
                'delivery_date' => now(),
                'delivered_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            // تسجيل النشاط
            activity()
                ->causedBy(Auth::user())
                ->performedOn($project)
                ->withProperties([
                    'delivery_type' => $request->delivery_type,
                    'delivery_id' => $delivery->id,
                    'notes' => $request->notes,
                ])
                ->log('تسليم مشروع: ' . $project->name . ' - نوع التسليم: ' . $request->delivery_type);

            DB::commit();

            return redirect()->back()->with('success', 'تم تسليم المشروع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error delivering project: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء تسليم المشروع');
        }
    }

    /**
     * الحصول على سجل تسليمات مشروع
     */
    public function getDeliveriesHistory($projectId)
    {
        try {
            $deliveries = ProjectDelivery::with('deliveredBy')
                ->where('project_id', $projectId)
                ->orderBy('delivery_date', 'desc')
                ->get()
                ->map(function ($delivery) {
                    return [
                        'id' => $delivery->id,
                        'delivery_type' => $delivery->delivery_type,
                        'delivery_date' => $delivery->delivery_date->format('Y-m-d H:i'),
                        'delivered_by' => $delivery->deliveredBy->name ?? 'غير محدد',
                        'notes' => $delivery->notes,
                    ];
                });

            return response()->json([
                'success' => true,
                'deliveries' => $deliveries,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching deliveries history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب سجل التسليمات',
            ], 500);
        }
    }

    /**
     * الحصول على خدمات المشروع وحالاتها
     */
    public function getProjectServices($projectId)
    {
        try {
            $project = Project::with(['services' => function ($query) {
                $query->withPivot('service_status', 'service_data');
            }])->findOrFail($projectId);

            $services = $project->services->map(function ($service) {
                // Try to decode service_data if it exists as JSON
                $serviceData = [];
                if (!empty($service->pivot->service_data)) {
                    $decoded = json_decode($service->pivot->service_data, true);
                    $serviceData = is_array($decoded) ? $decoded : [];
                }

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'icon' => $service->icon ?? '📋',
                    'status' => $service->pivot->service_status ?? 'لم تبدأ',
                    'progress' => $serviceData['progress'] ?? 0,
                    'notes' => $serviceData['notes'] ?? null,
                    'start_date' => $serviceData['start_date'] ?? null,
                    'end_date' => $serviceData['end_date'] ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                ],
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching project services: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب خدمات المشروع',
            ], 500);
        }
    }

    /**
     * تجهيز الفلاتر
     */
    private function prepareFilters(Request $request)
    {
        return [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'client_id' => $request->get('client_id'),
            'delivery_type' => $request->get('delivery_type'),
            'delivery_date_from' => $request->get('delivery_date_from'),
            'delivery_date_to' => $request->get('delivery_date_to'),
        ];
    }

    /**
     * جلب المشاريع مع التسليمات
     */
    private function getProjectsWithDeliveries($filters)
    {
        $query = Project::with([
            'client',
            'deliveries' => function ($q) {
                $q->orderBy('delivery_date', 'desc');
            },
            'deliveries.deliveredBy'
        ]);

        // تطبيق الفلاتر
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('code', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['delivery_type']) || !empty($filters['delivery_date_from']) || !empty($filters['delivery_date_to'])) {
            $query->whereHas('deliveries', function ($q) use ($filters) {
                if (!empty($filters['delivery_type'])) {
                    $q->where('delivery_type', $filters['delivery_type']);
                }

                if (!empty($filters['delivery_date_from'])) {
                    $q->whereDate('delivery_date', '>=', $filters['delivery_date_from']);
                }

                if (!empty($filters['delivery_date_to'])) {
                    $q->whereDate('delivery_date', '<=', $filters['delivery_date_to']);
                }
            });
        }

        $projects = $query->paginate(20);

        // إضافة معلومات التسليمات
        $projects->getCollection()->transform(function ($project) {
            $project->lastDraftDelivery = $project->deliveries->where('delivery_type', 'مسودة')->first();
            $project->lastFinalDelivery = $project->deliveries->whereIn('delivery_type', ['كامل', 'نهائي'])->first();
            return $project;
        });

        return $projects;
    }

    /**
     * حساب الإحصائيات
     */
    private function calculateStats()
    {
        $totalDeliveries = ProjectDelivery::count();
        $draftDeliveries = ProjectDelivery::where('delivery_type', 'مسودة')->count();
        $finalDeliveries = ProjectDelivery::whereIn('delivery_type', ['كامل', 'نهائي'])->count();

        return [
            'total_deliveries' => $totalDeliveries,
            'draft_deliveries' => $draftDeliveries,
            'final_deliveries' => $finalDeliveries,
        ];
    }
}

