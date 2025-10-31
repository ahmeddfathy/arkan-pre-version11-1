<?php

namespace App\Http\Controllers\Traits\Projects;

use App\Models\Project;
use App\Models\ProjectField;
use App\Models\RoleHierarchy;
use App\Models\CompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ProjectCRUDTrait
{
    protected $crudService;
    protected $codeService;
    protected $validationService;
    protected $roleCheckService;

    /**
     * عرض قائمة المشاريع
     */
    public function index()
    {
        // تسجيل نشاط دخول صفحة المشاريع
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'projects_list',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة المشاريع');
        }

        $projects = $this->projectService->getProjects();
        return view('projects.index', compact('projects'));
    }

    /**
     * عرض صفحة إنشاء مشروع جديد
     */
    public function create()
    {
        $data = $this->crudService->getCreateData();
        return view('projects.create', $data);
    }

    /**
     * حفظ مشروع جديد
     */
    public function store(Request $request)
    {
        $result = $this->crudService->store($request);

        if ($result['success']) {
            return redirect()->route($result['redirect'])
                ->with('success', $result['message']);
        } else {
            return back()->withInput()
                ->with('error', $result['message']);
        }
    }

    /**
     * عرض تفاصيل مشروع
     */
    public function show(Project $project)
    {
        // تسجيل نشاط عرض المشروع
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($project)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'project_name' => $project->name,
                    'project_code' => $project->code,
                    'project_status' => $project->status,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد المشروع');
        }

        $data = $this->crudService->show($project);
        return view('projects.show', $data);
    }

    /**
     * عرض صفحة تعديل مشروع
     */
    public function edit(Project $project)
    {
        // التحقق من أن المستخدم الحالي هو مدير المشروع
        if ($project->manager !== Auth::user()->name) {
            return back()->with('error', 'غير مسموح لك بتعديل هذا المشروع. فقط مدير المشروع يمكنه تعديله.');
        }

        $data = $this->crudService->getEditData($project);
        return view('projects.edit', $data);
    }

    /**
     * تحديث بيانات مشروع
     */
    public function update(Request $request, Project $project)
    {
        // التحقق من أن المستخدم الحالي هو مدير المشروع
        if ($project->manager !== Auth::user()->name) {
            return back()->with('error', 'غير مسموح لك بتعديل هذا المشروع. فقط مدير المشروع يمكنه تعديله.');
        }

        $result = $this->crudService->update($request, $project);

        if ($result['success']) {
            return redirect()->route($result['redirect'])
                ->with('success', $result['message']);
        } else {
            return back()->withInput()
                ->with('error', $result['message']);
        }
    }

    /**
     * تأكيد استلام المشروع
     */
    public function acknowledgeProject(Project $project)
    {
        $result = $this->crudService->acknowledgeProject($project);
        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * عرض تاريخ التعديلات لمشروع معين
     */
    public function showDateHistory(Project $project, $dateType = null)
    {
        $history = $project->projectDates();

        if ($dateType) {
            $history->where('date_type', $dateType);
        }

        $dates = $history->with('user')->orderBy('effective_from', 'desc')->get();

        return response()->json([
            'success' => true,
            'dates' => $dates
        ]);
    }

    /**
     * حذف مشروع (محظور)
     */
    public function destroy(Project $project)
    {
        // حذف المشاريع محظور نهائياً لحماية البيانات التاريخية
        return back()->with('error', 'حذف المشاريع محظور نهائياً للحفاظ على سلامة البيانات التاريخية للنظام. يمكنك تغيير حالة المشروع إلى "ملغي" بدلاً من ذلك.');
    }

    /**
     * التحقق من إمكانية حذف المشروع
     */
    public function checkDeletionPossibility(Request $request, $id)
    {
        $result = $this->validationService->checkDeletionPossibility($id);
        return response()->json($result, $result['status_code']);
    }

    /**
     * التحقق من توفر كود المشروع
     */
    public function checkCodeAvailability(Request $request)
    {
        $result = $this->codeService->checkCodeAvailability($request->input('code'));
        return response()->json($result, $result['status_code']);
    }

    /**
     * توليد كود مشروع تلقائي
     */
    public function generateCode(Request $request)
    {
        $result = $this->codeService->generateCode($request->input('company_type'));
        return response()->json($result, $result['status_code']);
    }

    /**
     * عرض صفحة بيانات خدمات المشروع
     */
    public function showServiceData($id)
    {
        $project = Project::with(['services', 'client'])->findOrFail($id);
        $user = Auth::user();

        // الحصول على المستوى الهرمي للمستخدم
        $userHierarchyLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);

        // التحقق من عضوية المستخدم في المشروع
        $isProjectMember = DB::table('project_service_user')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();

        // تحديد صلاحيات المستخدم
        $canEdit = false;
        $canViewAll = false;

        // Level 5+ يشوف كل التابات لكن مش يملأ
        if ($userHierarchyLevel >= 5) {
            $canViewAll = true;
            $canEdit = false;
        }
        // Level 2 & 3 يملأوا البيانات (لو في المشروع)
        elseif (($userHierarchyLevel == 2 || $userHierarchyLevel == 3) && $isProjectMember) {
            $canEdit = true;
            $canViewAll = false;
        }

        // جلب خدمات المشروع مع الحقول الديناميكية
        $projectServices = DB::table('project_service')
            ->where('project_id', $project->id)
            ->get();

        $servicesWithData = [];
        foreach ($projectServices as $projectService) {
            $service = CompanyService::with('dataFields')->find($projectService->service_id);
            if ($service) {
                // التحقق من صلاحية عرض هذه الخدمة
                $canViewService = $canViewAll;

                // إذا لم يكن يشوف كل شي، تحقق من أنه مشارك في هذه الخدمة
                if (!$canViewAll) {
                    $userInService = DB::table('project_service_user')
                        ->where('project_id', $project->id)
                        ->where('service_id', $projectService->service_id)
                        ->where('user_id', $user->id)
                        ->exists();

                    $canViewService = $userInService;
                }

                // إضافة الخدمة فقط إذا كان لديه صلاحية عرضها
                if ($canViewService) {
                    $servicesWithData[] = [
                        'service' => $service,
                        'service_data' => json_decode($projectService->service_data, true) ?? [],
                        'service_status' => $projectService->service_status
                    ];
                }
            }
        }

        return view('projects.service-data', compact('project', 'servicesWithData', 'canEdit', 'canViewAll', 'userHierarchyLevel'));
    }

    /**
     * حفظ/تحديث بيانات خدمة في المشروع
     */
    public function updateServiceData(Request $request, $projectId, $serviceId)
    {
        try {
            $user = Auth::user();

            // 1. التحقق من المستوى الهرمي للمستخدم
            $userHierarchyLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);

            // 2. Level 5+ لا يمكنهم التعديل (view only)
            if ($userHierarchyLevel >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسموح لك بتعديل البيانات. صلاحيتك للعرض فقط.'
                ], 403);
            }

            // 3. التحقق من أن المستخدم في المشروع
            $isProjectMember = DB::table('project_service_user')
                ->where('project_id', $projectId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$isProjectMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب أن تكون عضواً في المشروع لتعديل البيانات'
                ], 403);
            }

            // 4. التحقق من أن المستخدم له hierarchy level 2 أو 3
            if ($userHierarchyLevel != 2 && $userHierarchyLevel != 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسموح لك بتعديل بيانات الخدمات. صلاحيتك غير كافية.'
                ], 403);
            }

            // 5. التحقق من أن المستخدم مشارك في هذه الخدمة بالذات
            $userInService = DB::table('project_service_user')
                ->where('project_id', $projectId)
                ->where('service_id', $serviceId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$userInService) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسموح لك بتعديل بيانات هذه الخدمة'
                ], 403);
            }

            $validated = $request->validate([
                'service_data' => 'required|array'
            ]);

            // التحقق من وجود الخدمة في المشروع
            $projectService = DB::table('project_service')
                ->where('project_id', $projectId)
                ->where('service_id', $serviceId)
                ->first();

            if (!$projectService) {
                return response()->json([
                    'success' => false,
                    'message' => 'الخدمة غير موجودة في هذا المشروع'
                ], 404);
            }

            // التحقق من صحة البيانات حسب حقول الخدمة
            $service = CompanyService::with('dataFields')->find($serviceId);
            $serviceDataManagementService = app(\App\Services\ServiceDataManagementService::class);

            $validation = $serviceDataManagementService->validateServiceData($serviceId, $validated['service_data']);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validation['errors']
                ], 422);
            }

            // تحديث البيانات
            DB::table('project_service')
                ->where('project_id', $projectId)
                ->where('service_id', $serviceId)
                ->update([
                    'service_data' => json_encode($validated['service_data']),
                    'updated_at' => now()
                ]);

            // تسجيل النشاط
            activity()
                ->causedBy($user)
                ->performedOn(Project::find($projectId))
                ->withProperties([
                    'service_id' => $serviceId,
                    'action' => 'update_service_data'
                ])
                ->log('تم تحديث بيانات خدمة في المشروع');

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ البيانات بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating service data', [
                'error' => $e->getMessage(),
                'project_id' => $projectId,
                'service_id' => $serviceId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ البيانات'
            ], 500);
        }
    }

    /**
     * جلب بيانات خدمة في المشروع
     */
    public function getServiceData($projectId, $serviceId)
    {
        try {
            $projectService = DB::table('project_service')
                ->where('project_id', $projectId)
                ->where('service_id', $serviceId)
                ->first();

            if (!$projectService) {
                return response()->json([
                    'success' => false,
                    'message' => 'الخدمة غير موجودة في هذا المشروع'
                ], 404);
            }

            $service = CompanyService::with('dataFields')->find($serviceId);

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => $service,
                    'service_data' => json_decode($projectService->service_data, true) ?? [],
                    'service_status' => $projectService->service_status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting service data', [
                'error' => $e->getMessage(),
                'project_id' => $projectId,
                'service_id' => $serviceId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات'
            ], 500);
        }
    }

    /**
     * عرض المشاريع في فترة التحضير
     */
    public function preparationPeriod()
    {
        try {
            // تسجيل نشاط دخول صفحة المشاريع في فترة التحضير
            if (Auth::check()) {
                activity()
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'action_type' => 'view_preparation_period',
                        'page' => 'projects_preparation_period',
                        'viewed_at' => now()->toDateTimeString(),
                        'user_agent' => request()->userAgent(),
                        'ip_address' => request()->ip()
                    ])
                    ->log('دخل على صفحة المشاريع في فترة التحضير');
            }

            // جلب المشاريع في فترة التحضير
            $query = Project::where('preparation_enabled', true)
                ->whereNotNull('preparation_start_date')
                ->whereNotNull('preparation_days')
                ->whereNotNull('client_id')
                ->with(['client', 'services', 'attachmentConfirmations']);

            // Filter by month and year (any project with preparation period overlapping the selected month)
            if (request('month')) {
                $monthYear = request('month'); // Format: YYYY-MM
                $parts = explode('-', $monthYear);
                if (count($parts) == 2) {
                    $year = $parts[0];
                    $month = $parts[1];

                    // Get first and last day of selected month
                    $monthStart = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
                    $monthEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

                    // Find projects where preparation period overlaps with selected month
                    $query->where(function($q) use ($monthStart, $monthEnd) {
                        // Preparation starts before or during month AND ends during or after month
                        $q->where(function($innerQ) use ($monthStart, $monthEnd) {
                            $innerQ->where('preparation_start_date', '<=', $monthEnd)
                                   ->whereRaw('DATE_ADD(preparation_start_date, INTERVAL preparation_days DAY) >= ?', [$monthStart]);
                        });
                    });
                }
            }

            // Filter by status
            if (request('status')) {
                $today = \Carbon\Carbon::today();

                switch (request('status')) {
                    case 'active':
                        // جارية حالياً
                        $query->whereRaw('preparation_start_date <= ?', [$today])
                              ->whereRaw('DATE_ADD(preparation_start_date, INTERVAL preparation_days DAY) >= ?', [$today]);
                        break;

                    case 'pending':
                        // لم تبدأ بعد
                        $query->whereRaw('preparation_start_date > ?', [$today]);
                        break;

                    case 'completed':
                        // انتهت
                        $query->whereRaw('DATE_ADD(preparation_start_date, INTERVAL preparation_days DAY) < ?', [$today]);
                        break;
                }
            }

            // Search
            if (request('search')) {
                $search = request('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhereHas('client', function($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sort
            switch (request('sort', 'start_date_desc')) {
                case 'start_date_asc':
                    $query->orderBy('preparation_start_date', 'asc');
                    break;

                case 'end_date_asc':
                    $query->orderByRaw('DATE_ADD(preparation_start_date, INTERVAL preparation_days DAY) ASC');
                    break;

                case 'days_asc':
                    $query->orderBy('preparation_days', 'asc');
                    break;

                case 'days_desc':
                    $query->orderBy('preparation_days', 'desc');
                    break;

                default: // start_date_desc
                    $query->orderBy('preparation_start_date', 'desc');
                    break;
            }

            $projects = $query->paginate(20)->appends(request()->query());

            return view('projects.preparation-period', compact('projects'));
        } catch (\Exception $e) {
            Log::error('Error in preparation period page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'حدث خطأ أثناء تحميل الصفحة');
        }
    }

    /**
     * جلب قائمة المشاريع المتاحة لإضافتها لفترة التحضير (AJAX)
     */
    public function getProjectsListForPreparation(Request $request)
    {
        try {
            $search = $request->input('search', '');

            // جلب المشاريع التي لديها عملاء فقط
            $query = Project::with('client')
                ->whereNotNull('client_id')
                ->select('id', 'name', 'code', 'client_id', 'preparation_enabled', 'preparation_start_date', 'preparation_days');

            // البحث إذا تم إدخال نص
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $projects = $query->limit(100)->get()->map(function($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'client_name' => $project->client ? $project->client->name : 'غير محدد',
                    'preparation_enabled' => $project->preparation_enabled,
                    'display_text' => $project->code . ' - ' . $project->name . ' (' . ($project->client ? $project->client->name : 'غير محدد') . ')',
                ];
            });

            return response()->json([
                'success' => true,
                'projects' => $projects
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting projects list for preparation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة المشاريع'
            ], 500);
        }
    }

    /**
     * إضافة مشروع لفترة التحضير
     */
    public function addPreparationPeriod(Request $request)
    {
        try {
            // التحقق من صحة البيانات
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'preparation_start_date' => 'required|date',
                'preparation_end_date' => 'required|date|after:preparation_start_date',
            ], [
                'project_id.required' => 'المشروع مطلوب',
                'project_id.exists' => 'المشروع غير موجود',
                'preparation_start_date.required' => 'تاريخ بداية التحضير مطلوب',
                'preparation_start_date.date' => 'تاريخ بداية التحضير غير صحيح',
                'preparation_end_date.required' => 'تاريخ نهاية التحضير مطلوب',
                'preparation_end_date.date' => 'تاريخ نهاية التحضير غير صحيح',
                'preparation_end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            ]);

            // البحث عن المشروع
            $project = Project::find($validated['project_id']);

            if (!$project) {
                return back()
                    ->withErrors(['project_id' => 'المشروع غير موجود'])
                    ->withInput();
            }

            // حساب عدد الأيام
            $startDate = \Carbon\Carbon::parse($validated['preparation_start_date']);
            $endDate = \Carbon\Carbon::parse($validated['preparation_end_date']);
            $days = $startDate->diffInDays($endDate);

            // تحديث المشروع
            $project->update([
                'preparation_enabled' => true,
                'preparation_start_date' => $startDate,
                'preparation_days' => $days,
            ]);

            // تسجيل النشاط
            activity()
                ->causedBy(Auth::user())
                ->performedOn($project)
                ->withProperties([
                    'action_type' => 'add_preparation_period',
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'project_code' => $project->code,
                    'preparation_start_date' => $startDate->toDateTimeString(),
                    'preparation_end_date' => $endDate->toDateTimeString(),
                    'preparation_days' => $days,
                    'ip_address' => request()->ip()
                ])
                ->log('أضاف المشروع "' . $project->name . '" لفترة التحضير من ' . $startDate->format('Y-m-d') . ' إلى ' . $endDate->format('Y-m-d'));

            return redirect()
                ->route('projects.preparation-period')
                ->with('success', 'تم تفعيل فترة التحضير للمشروع "' . $project->name . '" بنجاح');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error adding preparation period', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return back()
                ->with('error', 'حدث خطأ أثناء تفعيل فترة التحضير')
                ->withInput();
        }
    }

    /**
     * صفحة تسليم المشاريع للعملاء (لفريق خدمة العملاء)
     */
    public function clientDeliveries(Request $request)
    {
        try {
            // تسجيل النشاط
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'action_type' => 'view_client_deliveries',
                    'page' => 'client_deliveries',
                    'viewed_at' => now()->toDateTimeString()
                ])
                ->log('دخل على صفحة تسليم المشاريع للعملاء');

            // الفلتر بالشهر
            $selectedMonth = $request->input('month', now()->format('Y-m'));
            $selectedWeek = $request->input('week', 'all');

            // تحويل الشهر لتاريخ
            $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd = $monthDate->copy()->endOfMonth();

            // جلب المشاريع التي لديها تاريخ تسليم متفق عليه مع العميل
            $query = Project::with(['client', 'lastFinalDelivery'])
                ->whereNotNull('client_agreed_delivery_date')
                ->whereNotNull('client_id')
                ->whereBetween('client_agreed_delivery_date', [$monthStart, $monthEnd]);

            // فلتر الأسبوع إذا تم اختياره
            if ($selectedWeek !== 'all') {
                $weekNumber = (int)$selectedWeek;
                $weekStart = $monthStart->copy()->addWeeks($weekNumber - 1);
                $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

                // التأكد من عدم تجاوز نهاية الشهر
                if ($weekEnd->gt($monthEnd)) {
                    $weekEnd = $monthEnd->copy();
                }

                $query->whereBetween('client_agreed_delivery_date', [$weekStart, $weekEnd]);
            }

            $projects = $query->orderBy('client_agreed_delivery_date', 'asc')->paginate(20);

            // حساب عدد الأسابيع في الشهر
            $weeksInMonth = $this->getWeeksInMonth($monthDate);

            // إحصائيات
            $totalProjects = $projects->total();
            $deliveredProjects = 0;
            $pendingProjects = 0;
            $overdueProjects = 0;

            foreach ($projects as $project) {
                // تحقق من وجود تسليم نهائي
                if ($project->lastFinalDelivery) {
                    $deliveredProjects++;
                } else {
                    // لم يتم التسليم بعد
                    if (now()->greaterThan($project->client_agreed_delivery_date)) {
                        $overdueProjects++; // متأخر
                    } else {
                        $pendingProjects++; // قادم
                    }
                }
            }

            return view('projects.client-deliveries', compact(
                'projects',
                'selectedMonth',
                'selectedWeek',
                'weeksInMonth',
                'totalProjects',
                'deliveredProjects',
                'pendingProjects',
                'overdueProjects'
            ));

        } catch (\Exception $e) {
            Log::error('Error in client deliveries page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'حدث خطأ أثناء تحميل الصفحة');
        }
    }

    /**
     * حساب عدد الأسابيع في الشهر
     */
    private function getWeeksInMonth($monthDate)
    {
        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();

        $weeks = [];
        $weekNumber = 1;

        while ($start->lte($end)) {
            $weekEnd = $start->copy()->addDays(6);
            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $weeks[] = [
                'number' => $weekNumber,
                'start' => $start->copy(),
                'end' => $weekEnd->copy(),
                'label' => 'الأسبوع ' . $weekNumber . ' (' . $start->format('d') . ' - ' . $weekEnd->format('d') . ')'
            ];

            $start->addDays(7);
            $weekNumber++;
        }

        return $weeks;
    }

    /**
     * عرض صفحة تعديل البيانات الإضافية للمشروع
     */
    public function editCustomFields(Project $project)
    {
        $fields = ProjectField::active()->ordered()->get();
        return view('projects.custom-fields-edit', compact('project', 'fields'));
    }

    /**
     * تحديث البيانات الإضافية للمشروع
     */
    public function updateCustomFields(Request $request, Project $project)
    {
        try {
            $customFieldsData = [];

            // جلب جميع الحقول النشطة
            $fields = ProjectField::active()->get();

            foreach ($fields as $field) {
                $value = $request->input('custom_field_' . $field->id);

                // التحقق من الحقول الإلزامية
                if ($field->is_required && empty($value)) {
                    return back()->withInput()
                        ->with('error', "الحقل '{$field->name}' مطلوب");
                }

                // حفظ القيمة إذا كانت موجودة
                if ($value !== null && $value !== '') {
                    $customFieldsData[$field->field_key] = $value;
                }
            }

            // حفظ البيانات في المشروع
            $project->custom_fields_data = $customFieldsData;
            $project->save();

            return redirect()->route('projects.show', $project)
                ->with('success', 'تم حفظ البيانات الإضافية بنجاح');

        } catch (\Exception $e) {
            Log::error('Error updating custom fields: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء حفظ البيانات');
        }
    }

    /**
     * Simple overview of all projects
     */
    public function simpleOverview()
    {
        try {
            $user = Auth::user();
            // Check if user is admin using RoleCheckService
            $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager', 'sales_employee', 'operation_assistant']);

            $projectsQuery = Project::with(['client', 'services']);

            if (!$isAdmin) {
                $userProjectIds = DB::table('project_service_user')
                    ->where('user_id', $user->id)
                    ->pluck('project_id')
                    ->toArray();

                $projectsQuery->whereIn('id', $userProjectIds);
            }

            $projects = $projectsQuery->orderBy('is_urgent', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate statistics
            $totalProjects = $projects->count();
            $activeProjects = $projects->where('status', 'جاري التنفيذ')->count();
            $completedProjects = $projects->where('status', 'مكتمل')->count();
            $totalServices = $projects->sum(function($project) {
                return $project->services->count();
            });

        return view('projects.project-services-overview', compact(
            'projects',
            'totalProjects',
            'activeProjects',
            'completedProjects',
            'totalServices'
        ));

        } catch (\Exception $e) {
            Log::error('Error in simple overview', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'حدث خطأ في تحميل الصفحة');
        }
    }

    /**
     * عرض أرشيف المشاريع
     */
    public function archive()
    {
        // تسجيل نشاط دخول صفحة الأرشيف
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'action_type' => 'view_archive',
                    'page' => 'projects_archive',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة أرشيف المشاريع');
        }

        $user = Auth::user();
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager', 'sales_employee', 'operation_assistant', 'technical_support', 'operations_manager']);

        // التحقق من الصلاحيات المطلوبة للأرشيف
        if (!$this->roleCheckService->userHasRole(['operation_assistant', 'technical_support', 'operations_manager', 'project_manager', 'company_manager', 'hr'])) {
            abort(403, 'غير مسموح لك بالوصول إلى أرشيف المشاريع');
        }

        $projectsQuery = Project::query();

        if (!$isAdmin) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();

            $projectsQuery->whereIn('id', $userProjectIds);
        }

        // البحث
        if (request('search')) {
            $search = request('search');
            $projectsQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // جلب الحقول المخصصة
        $customFields = ProjectField::active()->ordered()->get();

        // فلترة بالحقول المخصصة
        foreach ($customFields as $field) {
            $filterKey = 'custom_field_' . $field->id;
            if (request($filterKey) !== null && request($filterKey) !== '') {
                $projectsQuery->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(custom_fields_data, '$.{$field->field_key}')) = ?", [request($filterKey)]);
            }
        }

        $projects = $projectsQuery->orderBy('created_at', 'desc')->paginate(20)->appends(request()->query());

        // جلب القيم الفريدة لكل حقل مخصص من البيانات الموجودة
        $customFieldsOptions = [];
        foreach ($customFields as $field) {
            // جلب جميع المشاريع للحصول على القيم الفريدة
            $allProjects = Project::whereNotNull('custom_fields_data')->get();
            $uniqueValues = collect();

            foreach ($allProjects as $project) {
                $customFieldsData = $project->custom_fields_data ?? [];
                if (isset($customFieldsData[$field->field_key]) && !empty($customFieldsData[$field->field_key])) {
                    $uniqueValues->push($customFieldsData[$field->field_key]);
                }
            }

            $customFieldsOptions[$field->id] = $uniqueValues->unique()->sort()->values();
        }

        return view('projects.archive', compact('projects', 'customFields', 'customFieldsOptions'));
    }
}
