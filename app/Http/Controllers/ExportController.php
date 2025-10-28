<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\EmployeeActivityExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
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
     * تصدير تقرير نشاط الموظف كملف Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportActivityReport(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $date = $request->input('date', now()->format('Y-m-d'));

        if (!$employeeId) {
            return back()->withErrors(['message' => 'معرف الموظف مطلوب']);
        }

        $data = $this->getEmployeeData($employeeId, $date);

        // إعداد اسم الملف
        $filename = "employee_activity_{$employeeId}_{$date}.csv";

        // إعداد الهيدر للتحميل
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");

        // فتح مخرجات الملف
        $output = fopen('php://output', 'w');
        // BOM للغة العربية
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // العناوين
        fputcsv($output, ['وقت القياس', 'الحالة', 'نقرات الماوس', 'ضغطات الكيبورد', 'الثواني النشطة', 'عنوان النافذة']);

        $totalMouseClicks = 0;
        $totalKeyboardClicks = 0;
        $totalActiveSeconds = 0;
        $totalActiveBlocks = 0;

        foreach ($data['entries'] as $entry) {
            $activeSeconds = 0;
            if (isset($entry['total_active_seconds'])) {
                $activeSeconds = $entry['total_active_seconds'];
            } elseif (isset($entry['active_seconds'])) {
                $activeSeconds = $entry['active_seconds'];
            } elseif (isset($entry['active_seconds_in_minute'])) {
                $activeSeconds = $entry['active_seconds_in_minute'];
            } elseif (isset($entry['status']) && $entry['status'] === 'ACTIVE') {
                $activeSeconds = 60;
            }

            $totalMouseClicks += $entry['mouse_clicks'] ?? 0;
            $totalKeyboardClicks += $entry['keyboard_clicks'] ?? 0;
            $totalActiveSeconds += $activeSeconds;
            if (($entry['status'] ?? '') === 'ACTIVE') $totalActiveBlocks++;

            fputcsv($output, [
                $entry['time_block'] ?? '',
                $entry['status'] ?? '',
                $entry['mouse_clicks'] ?? 0,
                $entry['keyboard_clicks'] ?? 0,
                $activeSeconds,
                $entry['last_window_title'] ?? '',
            ]);
        }

        // صف الملخص
        fputcsv($output, []);
        fputcsv($output, ['ملخص النشاط', '', $totalMouseClicks, $totalKeyboardClicks, $totalActiveSeconds, '']);
        fputcsv($output, ['إجمالي البلوكات النشطة', $totalActiveBlocks]);
        $hours = floor($totalActiveSeconds / 3600);
        $minutes = floor(($totalActiveSeconds % 3600) / 60);
        $seconds = $totalActiveSeconds % 60;
        $formattedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        fputcsv($output, ['إجمالي الوقت النشط', $formattedTime]);

        fclose($output);
        exit;
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

        // محاولة الحصول على البيانات من الكاش أولاً
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($employeeId, $date) {
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

            // تنظيم البيانات في هيكل منظم للتصدير
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
    }
}
