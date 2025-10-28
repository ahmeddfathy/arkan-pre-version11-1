<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeeActivityExport;
use App\Notifications\EmployeeInactiveNotification;

class EmployeeController extends Controller
{
    protected $firestore;
    protected $cacheTtl = 60; // عمر الكاش بالثواني (1 دقيقة)

    public function __construct()
    {
        $this->firestore = app(\App\Services\FirestoreService::class);
        $this->middleware(function ($request, $next) {
            $roleCheckService = app(\App\Services\Auth\RoleCheckService::class);
            if (!$roleCheckService->userHasRole('hr')) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
            }
            return $next($request);
        });
    }

    /**
     * عرض لوحة التحكم مع بيانات الموظف
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // الحصول على قائمة الموظفين
        $employees = \App\Models\User::whereNotNull('employee_id')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id', 'department']);

        // الحصول على معرف الموظف والتاريخ من الطلب أو استخدام القيم الافتراضية
        $employeeId = $request->input('employee_id', $employees->first()->employee_id ?? '1');
        $date = $request->input('date', now()->format('Y-m-d'));

        // إنشاء مفتاح فريد للكاش بناءً على معرف الموظف والتاريخ
        $cacheKey = "employee_data_{$employeeId}_{$date}";

        // محاولة الحصول على البيانات من الكاش أولاً
        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($employeeId, $date) {
            $month = Carbon::parse($date)->format('Y-m');
            $day = Carbon::parse($date)->format('d');

            // تحسين الاستعلام - الوصول المباشر إلى الوثيقة المحددة
            $docRef = $this->firestore
                ->collection('employees')
                ->document($employeeId)
                ->collection($month)
                ->document($day);

            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return ['entries' => []];
            }

            $documentData = $snapshot->data();

            // تنظيم البيانات في هيكل منظم للعرض في الواجهة
            $entries = [];

            // إذا كانت البيانات منظمة بالفعل في مصفوفة entries، نستخدمها مباشرة
            if (isset($documentData['entries']) && is_array($documentData['entries'])) {
                $entries = $documentData['entries'];
            } else {
                // محاولة استخراج البيانات وتنظيمها في صيغة entries
                // تفترض أن البيانات منظمة بواسطة time_blocks
                foreach ($documentData as $timeBlock => $blockData) {
                    if (is_array($blockData) && !in_array($timeBlock, ['date', 'employee_id', 'last_updated'])) {
                        $entry = $blockData;
                        $entry['time_block'] = $timeBlock;
                        $entries[] = $entry;
                    }
                }

                // ترتيب الإدخالات حسب الوقت
                usort($entries, function($a, $b) {
                    return $a['time_block'] <=> $b['time_block'];
                });
            }

        // فحص خمول الموظف في آخر 5 دقائق
        $this->checkEmployeeInactivity($entries, $employeeId);

        // الحصول على حالة الموظف (إجازة، إذن، اجتماع)
        $employeeStatus = $this->getEmployeeStatus($employeeId, $date);

        return [
            'entries' => $entries,
            'employee_id' => $employeeId,
            'date' => $date,
            'document_path' => "employees/{$employeeId}/{$month}/{$day}",
            'employee_status' => $employeeStatus
        ];
        });

        return view('employee-tracking.dashboard', [
            'data' => $data,
            'employees' => $employees,
            'selected_employee' => $employeeId,
            'selected_date' => $date,
            'firestore_path' => [
                'collection' => 'employees',
                'employee_id' => $employeeId,
                'date_month' => Carbon::parse($date)->format('Y-m'),
                'date_day' => Carbon::parse($date)->format('d')
            ]
        ]);
    }

    /**
     * عرض تفاصيل موظف محدد
     *
     * @param Request $request
     * @param string $employeeId
     * @return \Illuminate\View\View
     */
    public function employeeDetails(Request $request, $employeeId)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        // إنشاء مفتاح فريد للكاش بناءً على معرف الموظف والتاريخ
        $cacheKey = "employee_data_{$employeeId}_{$date}";

        // محاولة الحصول على البيانات من الكاش أولاً
        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($employeeId, $date) {
            $month = Carbon::parse($date)->format('Y-m');
            $day = Carbon::parse($date)->format('d');

            // تحسين الاستعلام - الوصول المباشر إلى الوثيقة المحددة
            $docRef = $this->firestore
                ->collection('employees')
                ->document($employeeId)
                ->collection($month)
                ->document($day);

            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return ['entries' => []];
            }

            $documentData = $snapshot->data();

            // تنظيم البيانات في هيكل منظم للعرض في الواجهة
            $entries = [];

            // إذا كانت البيانات منظمة بالفعل في مصفوفة entries، نستخدمها مباشرة
            if (isset($documentData['entries']) && is_array($documentData['entries'])) {
                $entries = $documentData['entries'];
            } else {
                // محاولة استخراج البيانات وتنظيمها في صيغة entries
                foreach ($documentData as $timeBlock => $blockData) {
                    if (is_array($blockData) && !in_array($timeBlock, ['date', 'employee_id', 'last_updated'])) {
                        $entry = $blockData;
                        $entry['time_block'] = $timeBlock;
                        $entries[] = $entry;
                    }
                }

                // ترتيب الإدخالات حسب الوقت
                usort($entries, function($a, $b) {
                    return $a['time_block'] <=> $b['time_block'];
                });
            }

            return [
                'entries' => $entries,
                'employee_id' => $employeeId,
                'date' => $date
            ];
        });

        // بيانات إضافية للموظف - يمكن استكمالها من مصادر أخرى
        $employeeInfo = $this->getEmployeeInfo($employeeId);

        return view('employee_details', [
            'data' => $data,
            'employeeInfo' => $employeeInfo,
            'selected_date' => $date
        ]);
    }

    /**
     * عرض ملخص شهري للموظف
     *
     * @param Request $request
     * @param string $employeeId
     * @return \Illuminate\View\View
     */
    public function employeeMonthly(Request $request, $employeeId)
    {
        $month = $request->input('month', now()->format('Y-m'));

        // إنشاء مفتاح فريد للكاش الشهري
        $cacheKey = "employee_monthly_{$employeeId}_{$month}";

        // محاولة الحصول على البيانات الشهرية من الكاش أولاً
        $monthlyData = Cache::remember($cacheKey, $this->cacheTtl * 10, function () use ($employeeId, $month) {
            // جلب بيانات الشهر كاملة من Firestore
            $collectionRef = $this->firestore
                ->collection('employees')
                ->document($employeeId)
                ->collection($month);

            $documents = $collectionRef->documents();

            $dailySummary = [];

            foreach ($documents as $document) {
                if ($document->exists()) {
                    $day = $document->id();
                    $data = $document->data();

                    // حساب ملخص اليوم
                    $activeMinutes = 0;
                    $idleMinutes = 0;
                    $breakMinutes = 0;
                    $totalMouseClicks = 0;
                    $totalKeyboardClicks = 0;

                    // التعامل مع البيانات وفقًا لهيكلها
                    if (isset($data['entries']) && is_array($data['entries'])) {
                        foreach ($data['entries'] as $entry) {
                            if (isset($entry['status'])) {
                                if ($entry['status'] === 'ACTIVE') {
                                    $activeMinutes++;
                                } elseif ($entry['status'] === 'BREAK') {
                                    $breakMinutes++;
                                } else {
                                    $idleMinutes++;
                                }
                            }

                            $totalMouseClicks += isset($entry['mouse_clicks']) ? $entry['mouse_clicks'] : 0;
                            $totalKeyboardClicks += isset($entry['keyboard_clicks']) ? $entry['keyboard_clicks'] : 0;
                        }
                    } else {
                        foreach ($data as $timeBlock => $blockData) {
                            if (is_array($blockData) && !in_array($timeBlock, ['date', 'employee_id', 'last_updated'])) {
                                if (isset($blockData['status'])) {
                                    if ($blockData['status'] === 'ACTIVE') {
                                        $activeMinutes++;
                                    } elseif ($blockData['status'] === 'BREAK') {
                                        $breakMinutes++;
                                    } else {
                                        $idleMinutes++;
                                    }
                                }

                                $totalMouseClicks += isset($blockData['mouse_clicks']) ? $blockData['mouse_clicks'] : 0;
                                $totalKeyboardClicks += isset($blockData['keyboard_clicks']) ? $blockData['keyboard_clicks'] : 0;
                            }
                        }
                    }

                    $dailySummary[$day] = [
                        'date' => "$month-$day",
                        'active_minutes' => $activeMinutes,
                        'idle_minutes' => $idleMinutes,
                        'break_minutes' => $breakMinutes,
                        'total_minutes' => $activeMinutes + $idleMinutes + $breakMinutes,
                        'active_percentage' => ($activeMinutes + $idleMinutes + $breakMinutes) > 0
                            ? round(($activeMinutes / ($activeMinutes + $idleMinutes + $breakMinutes)) * 100, 1)
                            : 0,
                        'total_mouse_clicks' => $totalMouseClicks,
                        'total_keyboard_clicks' => $totalKeyboardClicks
                    ];
                }
            }

            // ترتيب الملخصات حسب اليوم
            ksort($dailySummary);

            return $dailySummary;
        });

        // بيانات إضافية للموظف
        $employeeInfo = $this->getEmployeeInfo($employeeId);

        return view('employee_monthly', [
            'monthlyData' => $monthlyData,
            'employeeInfo' => $employeeInfo,
            'selected_month' => $month,
            'employeeId' => $employeeId
        ]);
    }

    /**
     * تصدير بيانات الموظف بتنسيق CSV
     *
     * @param Request $request
     * @param string $employeeId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportCsv(Request $request, $employeeId)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        // الحصول على بيانات الموظف
        $data = $this->getEmployeeData($employeeId, $date);

        // إنشاء ملف CSV مؤقت
        $filename = "employee_{$employeeId}_activity_{$date}.csv";
        $tempFile = fopen(storage_path("app/temp/{$filename}"), 'w');

        // كتابة رؤوس الأعمدة
        fputcsv($tempFile, [
            'Time Block',
            'Status',
            'Mouse Clicks',
            'Keyboard Clicks',
            'Active Seconds',
            'Last Window Title'
        ]);

        // كتابة بيانات كل إدخال
        foreach ($data['entries'] as $entry) {
            fputcsv($tempFile, [
                $entry['time_block'] ?? '',
                $entry['status'] ?? '',
                $entry['mouse_clicks'] ?? 0,
                $entry['keyboard_clicks'] ?? 0,
                $entry['active_seconds'] ?? 0,
                $entry['last_window_title'] ?? ''
            ]);
        }

        fclose($tempFile);

        // إرجاع الملف للتنزيل
        return response()->download(storage_path("app/temp/{$filename}"))->deleteFileAfterSend();
    }

    /**
     * تصدير بيانات الموظف بتنسيق Excel
     *
     * @param Request $request
     * @param string $employeeId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request, $employeeId)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        // الحصول على بيانات الموظف
        $data = $this->getEmployeeData($employeeId, $date);

        $filename = "employee_{$employeeId}_activity_{$date}.xlsx";

        return Excel::download(new EmployeeActivityExport($data['entries'], $employeeId, $date), $filename);
    }

    /**
     * الحصول على بيانات الموظف من Firestore أو الكاش
     *
     * @param string $employeeId
     * @param string $date
     * @return array
     */
    protected function getEmployeeData($employeeId, $date)
    {
        // إنشاء مفتاح فريد للكاش
        $cacheKey = "employee_data_{$employeeId}_{$date}";

        // محاولة الحصول على البيانات من الكاش
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($employeeId, $date) {
            $month = Carbon::parse($date)->format('Y-m');
            $day = Carbon::parse($date)->format('d');

            $docRef = $this->firestore
                ->collection('employees')
                ->document($employeeId)
                ->collection($month)
                ->document($day);

            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                return ['entries' => []];
            }

            $documentData = $snapshot->data();

            $entries = [];

            if (isset($documentData['entries']) && is_array($documentData['entries'])) {
                $entries = $documentData['entries'];
            } else {
                foreach ($documentData as $timeBlock => $blockData) {
                    if (is_array($blockData) && !in_array($timeBlock, ['date', 'employee_id', 'last_updated'])) {
                        $entry = $blockData;
                        $entry['time_block'] = $timeBlock;
                        $entries[] = $entry;
                    }
                }

                usort($entries, function($a, $b) {
                    return $a['time_block'] <=> $b['time_block'];
                });
            }

            return [
                'entries' => $entries,
                'employee_id' => $employeeId,
                'date' => $date
            ];
        });
    }

    /**
     * الحصول على معلومات الموظف
     *
     * @param string $employeeId
     * @return array
     */
    protected function getEmployeeInfo($employeeId)
    {
        // يمكن استكمال هذه الدالة لجلب بيانات الموظف من مصدر آخر
        // مثال بسيط للتوضيح
        $dummyEmployees = [
            '1' => ['name' => 'أحمد محمد', 'department' => 'تطوير البرمجيات', 'position' => 'مطور ويب'],
            '2' => ['name' => 'سارة علي', 'department' => 'التسويق', 'position' => 'مدير تسويق'],
            '3' => ['name' => 'محمد عبدالله', 'department' => 'خدمة العملاء', 'position' => 'مسؤول دعم فني'],
        ];

        return $dummyEmployees[$employeeId] ?? ['name' => "موظف {$employeeId}", 'department' => 'غير معروف', 'position' => 'غير معروف'];
    }

    /**
     * فحص خمول الموظف في آخر فترة
     *
     * @param array $entries
     * @param string $employeeId
     * @return void
     */
    protected function checkEmployeeInactivity($entries, $employeeId)
    {
        // إذا لم تكن هناك بيانات، لا داعي للاستمرار
        if (empty($entries)) {
            return;
        }

        // أخذ آخر 5 إدخالات (بافتراض أن كل إدخال يمثل دقيقة)
        $lastEntries = array_slice($entries, -5);

        $allInactive = true;

        foreach ($lastEntries as $entry) {
            if (isset($entry['status']) && $entry['status'] === 'ACTIVE') {
                $allInactive = false;
                break;
            }
        }

        // إذا كان الموظف خاملاً لمدة 5 دقائق متتالية
        if ($allInactive && count($lastEntries) === 5) {
            // سجل هذه المعلومة في السجلات
            Log::warning("الموظف {$employeeId} غير نشط لمدة 5 دقائق متتالية.");

            // يمكنك إرسال إشعار هنا
            // Notification::route('mail', 'admin@example.com')
            //     ->notify(new EmployeeInactiveNotification($employeeId));
        }
    }

    /**
     * الحصول على حالة الموظف (إجازة، إذن، اجتماع)
     *
     * @param string $employeeId
     * @param string $date
     * @return array
     */
    protected function getEmployeeStatus($employeeId, $date)
    {
        $user = \App\Models\User::where('employee_id', $employeeId)->first();

        if (!$user) {
            return [
                'has_absence' => false,
                'has_permission' => false,
                'has_meeting' => false,
                'status_details' => []
            ];
        }

        $statusDetails = [];
        $hasAbsence = false;
        $hasPermission = false;
        $hasMeeting = false;

        // التحقق من الإجازات المعتمدة في هذا التاريخ
        $absenceRequest = \App\Models\AbsenceRequest::where('user_id', $user->id)
            ->whereDate('absence_date', $date)
            ->where('status', 'approved')
            ->first();

        if ($absenceRequest) {
            $hasAbsence = true;
            $statusDetails[] = [
                'type' => 'absence',
                'title' => 'إجازة معتمدة',
                'description' => 'الموظف في إجازة معتمدة',
                'details' => 'تاريخ الإجازة: ' . $absenceRequest->absence_date->format('Y-m-d'),
                'icon' => 'fas fa-calendar-times',
                'color' => 'bg-red-100 text-red-800'
            ];
        }

        // التحقق من الأذونات المعتمدة في هذا التاريخ
        $permissionRequest = \App\Models\PermissionRequest::where('user_id', $user->id)
            ->whereDate('departure_time', $date)
            ->where('status', 'approved')
            ->first();

        if ($permissionRequest) {
            $hasPermission = true;
            $departureTime = $permissionRequest->departure_time->format('H:i');
            $returnTime = $permissionRequest->return_time->format('H:i');
            $statusDetails[] = [
                'type' => 'permission',
                'title' => 'إذن معتمد',
                'description' => 'الموظف لديه إذن معتمد',
                'details' => "من {$departureTime} إلى {$returnTime}",
                'icon' => 'fas fa-door-open',
                'color' => 'bg-yellow-100 text-yellow-800'
            ];
        }

        // التحقق من الاجتماعات المعتمدة في هذا التاريخ
        $meeting = \App\Models\Meeting::where('created_by', $user->id)
            ->whereDate('start_time', $date)
            ->whereIn('approval_status', ['approved', 'auto_approved'])
            ->first();

        // التحقق من المشاركة في اجتماعات معتمدة
        if (!$meeting) {
            $meeting = \App\Models\Meeting::whereHas('participants', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereDate('start_time', $date)
            ->whereIn('approval_status', ['approved', 'auto_approved'])
            ->first();
        }

        if ($meeting) {
            $hasMeeting = true;
            $startTime = $meeting->start_time->format('H:i');
            $endTime = $meeting->end_time->format('H:i');
            $statusDetails[] = [
                'type' => 'meeting',
                'title' => 'اجتماع معتمد',
                'description' => 'الموظف لديه اجتماع معتمد',
                'details' => "من {$startTime} إلى {$endTime}",
                'icon' => 'fas fa-users',
                'color' => 'bg-blue-100 text-blue-800'
            ];
        }

        return [
            'has_absence' => $hasAbsence,
            'has_permission' => $hasPermission,
            'has_meeting' => $hasMeeting,
            'status_details' => $statusDetails
        ];
    }

    /**
     * مسح ذاكرة التخزين المؤقت للموظف
     *
     * @param string $employeeId
     * @param string|null $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache($employeeId, $date = null)
    {
        if (!$date) {
            $date = now()->format('Y-m-d');
        }

        $cacheKey = "employee_data_{$employeeId}_{$date}";
        Cache::forget($cacheKey);

        return response()->json(['success' => true, 'message' => 'تم مسح ذاكرة التخزين المؤقت بنجاح']);
    }
}
