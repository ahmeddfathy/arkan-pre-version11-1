<?php

namespace App\Http\Controllers;

use App\Services\ProjectDelivery\ProjectDeliveryHierarchyService;
use App\Models\ProjectServiceUser;
use App\Models\RoleApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeDeliveryController extends Controller
{
    protected $deliveryHierarchyService;
    protected $notificationService;

    public function __construct(
        ProjectDeliveryHierarchyService $deliveryHierarchyService,
        \App\Services\Notifications\DeliveryNotificationService $notificationService
    ) {
        $this->deliveryHierarchyService = $deliveryHierarchyService;
        $this->notificationService = $notificationService;
    }


    public function index(Request $request)
    {
        $currentUser = Auth::user();

        $filters = $this->prepareFilters($request);

        $deliveries = $this->deliveryHierarchyService->getHierarchicalDeliveries($currentUser, $filters);

        $myDeliveries = $this->deliveryHierarchyService->getMyDeliveries($currentUser, $filters);

        $allowedUsers = $this->deliveryHierarchyService->getFilteredUsersForCurrentUser($currentUser);

        $allowedProjects = $this->deliveryHierarchyService->getFilteredProjectsForCurrentUser($currentUser);

        $allowedServices = $this->deliveryHierarchyService->getFilteredServicesForCurrentUser($currentUser);

        $userHierarchyLevel = $this->deliveryHierarchyService->getUserHierarchyLevel($currentUser);
        $showProjectDates = $userHierarchyLevel >= 4;

        $showAllDeliveriesTab = $userHierarchyLevel >= 2;

        $projects = collect();
        if ($userHierarchyLevel >= 4) {
            $projects = $this->deliveryHierarchyService->getFilteredProjectsForCurrentUser($currentUser, $filters);
        }

        // حساب الإحصائيات
        $stats = [
            'total' => $deliveries->count(),
            'acknowledged' => $deliveries->where('is_acknowledged', true)->count(),
            'unacknowledged' => $deliveries->where('is_acknowledged', false)->count(),
            'overdue' => $deliveries->filter(function($delivery) {
                return $delivery->deadline && $delivery->deadline->isPast();
            })->count(),
        ];

        // إحصائيات التسليمات الشخصية
        $myStats = [
            'total' => $myDeliveries->count(),
            'acknowledged' => $myDeliveries->where('is_acknowledged', true)->count(),
            'unacknowledged' => $myDeliveries->where('is_acknowledged', false)->count(),
            'overdue' => $myDeliveries->filter(function($delivery) {
                return $delivery->deadline && $delivery->deadline->isPast();
            })->count(),
        ];

        return view('deliveries.employee-deliveries', compact(
            'deliveries',
            'myDeliveries',
            'allowedUsers',
            'allowedProjects',
            'allowedServices',
            'filters',
            'currentUser',
            'showProjectDates',
            'showAllDeliveriesTab',
            'userHierarchyLevel',
            'stats',
            'myStats',
            'projects'
        ));
    }

    /**
     * جلب البيانات عبر AJAX
     */
    public function getData(Request $request)
    {
        $currentUser = Auth::user();
        $filters = $this->prepareFilters($request);

        $deliveries = $this->deliveryHierarchyService->getHierarchicalDeliveries($currentUser, $filters);

        return response()->json([
            'success' => true,
            'data' => $deliveries,
            'total' => $deliveries->count(),
            'filters_applied' => $filters
        ]);
    }

    /**
     * تأكيد استلام تسليمة
     */
    public function acknowledge(Request $request, $deliveryId)
    {
        try {
            $currentUser = Auth::user();

            $result = $this->deliveryHierarchyService->acknowledgeDelivery($deliveryId, $currentUser);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تأكيد الاستلام بنجاح'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 403);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تأكيد الاستلام: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إلغاء تأكيد الاستلام
     */
    public function unacknowledge(Request $request, $deliveryId)
    {
        try {
            $currentUser = Auth::user();

            $result = $this->deliveryHierarchyService->unacknowledgeDelivery($deliveryId, $currentUser);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إلغاء تأكيد الاستلام بنجاح'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 403);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء تأكيد الاستلام: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحضير الفلاتر
     */
    private function prepareFilters(Request $request): array
    {
        $filters = [];

        // فلتر نوع التاريخ (التاريخ المتفق مع الفريق أو العميل)
        if ($request->filled('date_type')) {
            $filters['date_type'] = $request->date_type; // 'team' أو 'client'
        } else {
            $filters['date_type'] = 'team'; // القيمة الافتراضية
        }

        // فلتر التاريخ
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'current_week':
                    $filters['start_date'] = Carbon::now()->startOfWeek();
                    $filters['end_date'] = Carbon::now()->endOfWeek();
                    break;
                case 'last_week':
                    $filters['start_date'] = Carbon::now()->subWeek()->startOfWeek();
                    $filters['end_date'] = Carbon::now()->subWeek()->endOfWeek();
                    break;
                case 'current_month':
                    $filters['start_date'] = Carbon::now()->startOfMonth();
                    $filters['end_date'] = Carbon::now()->endOfMonth();
                    break;
                case 'last_month':
                    $filters['start_date'] = Carbon::now()->subMonth()->startOfMonth();
                    $filters['end_date'] = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case 'custom':
                    if ($request->filled('start_date')) {
                        $filters['start_date'] = Carbon::parse($request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $filters['end_date'] = Carbon::parse($request->end_date);
                    }
                    break;
            }
        }

        // فلتر المستخدم
        if ($request->filled('user_id')) {
            $filters['user_id'] = $request->user_id;
        }

        // فلتر المشروع
        if ($request->filled('project_id')) {
            $filters['project_id'] = $request->project_id;
        }

        // فلتر الخدمة
        if ($request->filled('service_id')) {
            $filters['service_id'] = $request->service_id;
        }

        // فلتر حالة التأكيد
        if ($request->filled('acknowledgment_status')) {
            $filters['acknowledgment_status'] = $request->acknowledgment_status;
        }

        // فلتر الفريق
        if ($request->filled('team_id')) {
            $filters['team_id'] = $request->team_id;
        }

        return $filters;
    }

    /**
     * تصدير البيانات
     */
    public function export(Request $request)
    {
        $currentUser = Auth::user();
        $filters = $this->prepareFilters($request);

        $deliveries = $this->deliveryHierarchyService->getHierarchicalDeliveries($currentUser, $filters);

        // تحضير البيانات للتصدير
        $exportData = $deliveries->map(function ($delivery) {
            return [
                'المشروع' => $delivery->project->name ?? 'غير محدد',
                'الخدمة' => $delivery->service->name ?? 'غير محدد',
                'الموظف' => $delivery->user->name ?? 'غير محدد',
                'القسم' => $delivery->user->department ?? 'غير محدد',
                'الفريق' => $delivery->team->name ?? 'غير محدد',
                'حالة التأكيد' => $delivery->is_acknowledged ? 'مؤكد' : 'غير مؤكد',
                'تاريخ التأكيد' => $delivery->acknowledged_at ? $delivery->acknowledged_at->format('Y-m-d H:i') : 'لم يتم التأكيد',
                'الموعد النهائي' => $delivery->deadline ? $delivery->deadline->format('Y-m-d') : 'غير محدد',
                'تاريخ الإنشاء' => $delivery->created_at->format('Y-m-d H:i'),
            ];
        });

        // إنشاء ملف Excel
        return response()->streamDownload(function () use ($exportData) {
            $handle = fopen('php://output', 'w');

            // إضافة BOM للـ UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            // إضافة العناوين
            if ($exportData->isNotEmpty()) {
                fputcsv($handle, array_keys($exportData->first()));

                // إضافة البيانات
                foreach ($exportData as $row) {
                    fputcsv($handle, array_values($row));
                }
            }

            fclose($handle);
        }, 'employee-deliveries-' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="employee-deliveries-' . date('Y-m-d') . '.csv"',
        ]);
    }


    /**
     * جلب المشاريع للمستخدمين من المستوى 3 فما فوق
     */
    public function getProjects(Request $request)
    {
        $currentUser = Auth::user();
        $userHierarchyLevel = $this->deliveryHierarchyService->getUserHierarchyLevel($currentUser);

        // فقط المستخدمين من المستوى 3 فما فوق يمكنهم الوصول
        if ($userHierarchyLevel < 3) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذه البيانات'
            ], 403);
        }

        $filters = $this->prepareFilters($request);

        // جلب المشاريع حسب الصلاحيات الهرمية
        $projects = $this->deliveryHierarchyService->getFilteredProjectsForCurrentUser($currentUser);

        // تطبيق الفلاتر الإضافية
        if (!empty($filters['project_id'])) {
            $projects = $projects->where('id', $filters['project_id']);
        }

        return response()->json([
            'success' => true,
            'data' => $projects,
            'total' => $projects->count()
        ]);
    }

    // ==========================================
    // وظائف الاعتماد الجديدة (التسليم)
    // ==========================================

    /**
     * اعتماد إداري للتسليمة
     */
    public function grantAdministrativeApproval(Request $request, $deliveryId)
    {
        $delivery = ProjectServiceUser::find($deliveryId);

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'التسليمة غير موجودة'
            ], 404);
        }

        $currentUser = Auth::user();

        // فحص الصلاحية
        if (!$delivery->canUserApprove($currentUser->id, 'administrative')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بإعطاء الاعتماد الإداري لهذه التسليمة'
            ], 403);
        }

        // فحص أن التسليمة تم تسليمها أولاً
        if (!$delivery->isDelivered()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد تسليمة لم يتم تسليمها بعد'
            ], 400);
        }

        // فحص أن التسليمة مستلمة
        if (!$delivery->isAcknowledged()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد تسليمة غير مستلمة بعد'
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $result = $delivery->grantAdministrativeApproval(
                $currentUser->id,
                $request->input('notes')
            );

            if ($result) {
                DB::commit();

                // إرسال إشعار للموظف
                $this->notificationService->notifyEmployeeWhenApproved(
                    $delivery->fresh(),
                    $currentUser,
                    'administrative'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'تم إعطاء الاعتماد الإداري بنجاح',
                    'approval_status' => $delivery->fresh()->getApprovalStatus()
                ]);
            } else {
                DB::rollback();

                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إعطاء الاعتماد'
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعطاء الاعتماد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * اعتماد فني للتسليمة
     */
    public function grantTechnicalApproval(Request $request, $deliveryId)
    {
        $delivery = ProjectServiceUser::find($deliveryId);

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'التسليمة غير موجودة'
            ], 404);
        }

        $currentUser = Auth::user();

        // فحص الصلاحية
        if (!$delivery->canUserApprove($currentUser->id, 'technical')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بإعطاء الاعتماد الفني لهذه التسليمة'
            ], 403);
        }

        // فحص أن التسليمة تم تسليمها أولاً
        if (!$delivery->isDelivered()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد تسليمة لم يتم تسليمها بعد'
            ], 400);
        }

        // فحص أن التسليمة مستلمة
        if (!$delivery->isAcknowledged()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد تسليمة غير مستلمة بعد'
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $result = $delivery->grantTechnicalApproval(
                $currentUser->id,
                $request->input('notes')
            );

            if ($result) {
                DB::commit();

                // إرسال إشعار للموظف
                $this->notificationService->notifyEmployeeWhenApproved(
                    $delivery->fresh(),
                    $currentUser,
                    'technical'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'تم إعطاء الاعتماد الفني بنجاح',
                    'approval_status' => $delivery->fresh()->getApprovalStatus()
                ]);
            } else {
                DB::rollback();

                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إعطاء الاعتماد'
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعطاء الاعتماد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إلغاء الاعتماد الإداري
     */
    public function revokeAdministrativeApproval($deliveryId)
    {
        $delivery = ProjectServiceUser::find($deliveryId);

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'التسليمة غير موجودة'
            ], 404);
        }

        $currentUser = Auth::user();

        // فحص الصلاحية - يجب أن يكون المعتمد الأصلي أو أدمن
        if ($delivery->administrative_approver_id !== $currentUser->id &&
            !$currentUser->hasRole(['company_manager', 'hr'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بإلغاء هذا الاعتماد'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $result = $delivery->revokeAdministrativeApproval();

            if ($result) {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'تم إلغاء الاعتماد الإداري بنجاح',
                    'approval_status' => $delivery->fresh()->getApprovalStatus()
                ]);
            } else {
                DB::rollback();

                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إلغاء الاعتماد'
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الاعتماد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إلغاء الاعتماد الفني
     */
    public function revokeTechnicalApproval($deliveryId)
    {
        $delivery = ProjectServiceUser::find($deliveryId);

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'التسليمة غير موجودة'
            ], 404);
        }

        $currentUser = Auth::user();

        // فحص الصلاحية - يجب أن يكون المعتمد الأصلي أو أدمن
        if ($delivery->technical_approver_id !== $currentUser->id &&
            !$currentUser->hasRole(['company_manager', 'hr'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بإلغاء هذا الاعتماد'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $result = $delivery->revokeTechnicalApproval();

            if ($result) {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'تم إلغاء الاعتماد الفني بنجاح',
                    'approval_status' => $delivery->fresh()->getApprovalStatus()
                ]);
            } else {
                DB::rollback();

                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إلغاء الاعتماد'
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الاعتماد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض تفاصيل الاعتمادات للتسليمة
     */
    public function getApprovalDetails($deliveryId)
    {
        $delivery = ProjectServiceUser::find($deliveryId);

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'التسليمة غير موجودة'
            ], 404);
        }

        $approvalStatus = $delivery->getApprovalStatus();
        $requiredApprovals = $delivery->getRequiredApprovals();

        return response()->json([
            'success' => true,
            'data' => [
                'delivery_id' => $delivery->id,
                'is_acknowledged' => $delivery->isAcknowledged(),
                'acknowledged_at' => $delivery->acknowledged_at,
                'approval_status' => $approvalStatus,
                'required_approvals' => $requiredApprovals,
                'is_fully_completed' => $delivery->isFullyCompleted(),
            ]
        ]);
    }

    /**
     * جلب قائمة التسليمات التي يمكن للمستخدم الحالي اعتمادها
     */
    public function getDeliveriesAwaitingApproval(Request $request)
    {
        $currentUser = Auth::user();
        $approvalType = $request->get('approval_type', 'all'); // all, administrative, technical

        // جلب جميع التسليمات المستلمة
        $query = ProjectServiceUser::with([
            'project', 'service', 'user', 'team',
            'administrativeApprover', 'technicalApprover'
        ])
        ->where('is_acknowledged', true); // فقط التسليمات المستلمة

        $deliveries = $query->get()->filter(function ($delivery) use ($currentUser, $approvalType) {
            $canApprove = false;

            switch ($approvalType) {
                case 'administrative':
                    $canApprove = $delivery->canUserApprove($currentUser->id, 'administrative')
                                 && !$delivery->hasAdministrativeApproval();
                    break;

                case 'technical':
                    $canApprove = $delivery->canUserApprove($currentUser->id, 'technical')
                                 && !$delivery->hasTechnicalApproval();
                    break;

                default: // 'all'
                    $canApprove = ($delivery->canUserApprove($currentUser->id, 'administrative')
                                  && !$delivery->hasAdministrativeApproval()) ||
                                 ($delivery->canUserApprove($currentUser->id, 'technical')
                                  && !$delivery->hasTechnicalApproval());
                    break;
            }

            return $canApprove;
        });

        // إضافة معلومات الاعتماد لكل تسليمة
        $deliveries = $deliveries->map(function ($delivery) use ($currentUser) {
            $approvalStatus = $delivery->getApprovalStatus();

            return [
                'id' => $delivery->id,
                'project' => [
                    'id' => $delivery->project->id,
                    'name' => $delivery->project->name,
                    'code' => $delivery->project->code,
                ],
                'service' => [
                    'id' => $delivery->service->id,
                    'name' => $delivery->service->name,
                ],
                'user' => [
                    'id' => $delivery->user->id,
                    'name' => $delivery->user->name,
                ],
                'acknowledged_at' => $delivery->acknowledged_at,
                'deadline' => $delivery->deadline,
                'approval_status' => $approvalStatus,
                'can_approve_administrative' => $delivery->isDelivered()
                                              && $delivery->isAcknowledged()
                                              && $delivery->canUserApprove($currentUser->id, 'administrative')
                                              && !$delivery->hasAdministrativeApproval(),
                'can_approve_technical' => $delivery->isDelivered()
                                         && $delivery->isAcknowledged()
                                         && $delivery->canUserApprove($currentUser->id, 'technical')
                                         && !$delivery->hasTechnicalApproval(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $deliveries->values(),
            'total' => $deliveries->count()
        ]);
    }

}
