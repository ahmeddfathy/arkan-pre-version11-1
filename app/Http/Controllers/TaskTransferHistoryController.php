<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskTransferHistoryController extends Controller
{
    /**
     * عرض تاريخ نقل المهام مع الفلاتر
     */
    public function index(Request $request)
    {
        // تسجيل النشاط
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'action_type' => 'view_transfer_history',
                    'filters' => $request->all(),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip()
                ])
                ->log('دخل على صفحة تاريخ نقل المهام');
        }

        // الحصول على البيانات للفلاتر
        $users = User::select('id', 'name', 'employee_id')->orderBy('name')->get();

        // بناء استعلام المهام المنقولة مع الفلاتر
        $transfersQuery = $this->buildTransfersQuery($request);

        // ترقيم النتائج
        $perPage = $request->get('per_page', 'all');

        if ($perPage === 'all') {
            $transfers = $transfersQuery->get();
            $totalCount = $transfers->count();
            // إنشاء pagination مخصص للعرض
            $transfers = new \Illuminate\Pagination\LengthAwarePaginator(
                $transfers,
                $totalCount,
                $totalCount > 0 ? $totalCount : 1, // تجنب القسمة على صفر
                1,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        } else {
            $transfers = $transfersQuery->paginate($perPage);
        }

        // إحصائيات عامة
        $totalTransfers = $this->getTotalTransfersCount($request);
        $transfersByType = $this->getTransfersByType($request);

        // التأكد من وجود قيم افتراضية لتجنب الأخطاء
        $totalTransfers = $totalTransfers ?: ['total' => 0, 'regular' => 0, 'template' => 0];
        $transfersByType = $transfersByType ?: [
            'regular_count' => 0,
            'template_count' => 0,
            'positive_count' => 0,
            'negative_count' => 0,
            'total_points' => 0
        ];

        return view('tasks.transfer.history', compact(
            'transfers',
            'users',
            'totalTransfers',
            'transfersByType'
        ));
    }

    /**
     * بناء الاستعلام الأساسي للمهام المنقولة
     */
    private function buildTransfersQuery($request)
    {
        // ✅ المهام العادية المنقولة
        $regularTransfers = DB::table('task_users as received')
            ->join('tasks', 'received.task_id', '=', 'tasks.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->leftJoin('task_users as original', 'received.original_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        // تطبيق فلاتر التاريخ على المهام العادية
        if ($request->filled('from_date')) {
            $regularTransfers->where('original.transferred_at', '>=', $request->from_date . ' 00:00:00');
        }

        if ($request->filled('to_date')) {
            $regularTransfers->where('original.transferred_at', '<=', $request->to_date . ' 23:59:59');
        }

        // تطبيق فلاتر المستخدمين على المهام العادية
        if ($request->filled('from_user_id')) {
            $regularTransfers->where('original.user_id', $request->from_user_id);
        }

        if ($request->filled('to_user_id')) {
            $regularTransfers->where('received.user_id', $request->to_user_id);
        }

        $regularTransfers->select(
            DB::raw('CONVERT(tasks.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as task_name'),
            DB::raw('CONVERT(COALESCE(from_users.name, "غير محدد") USING utf8mb4) COLLATE utf8mb4_unicode_ci as from_user_name'),
            DB::raw('CONVERT(to_users.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as to_user_name'),
            'from_users.employee_id as from_user_employee_id',
            'to_users.employee_id as to_user_employee_id',
            'original.user_id as from_user_id',
            'received.user_id as to_user_id',
            DB::raw('CONVERT(COALESCE(original.transfer_reason, "") USING utf8mb4) COLLATE utf8mb4_unicode_ci as reason'),
            DB::raw('CONVERT(COALESCE(original.transfer_type, "positive") USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_direction'),
            'original.transferred_at',
            DB::raw('CONVERT(projects.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as project_name'),
            'projects.id as project_id',
            DB::raw('CONVERT("غير محدد" USING utf8mb4) COLLATE utf8mb4_unicode_ci as season_name'),
            DB::raw('NULL as season_id'),
            DB::raw('CONVERT(received.status USING utf8mb4) COLLATE utf8mb4_unicode_ci as current_status'),
            DB::raw('CONVERT("regular" USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_type'),
            DB::raw('0 as transfer_points') // المهام العادية لا تحتوي على نقاط نقل مباشرة
        );

        // ✅ مهام القوالب المنقولة
        $templateTransfers = DB::table('template_task_user as received')
            ->join('template_tasks', 'received.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('template_task_user as original', 'received.original_template_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->leftJoin('projects', 'received.project_id', '=', 'projects.id')
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        // تطبيق فلاتر التاريخ على مهام القوالب
        if ($request->filled('from_date')) {
            $templateTransfers->where('original.transferred_at', '>=', $request->from_date . ' 00:00:00');
        }

        if ($request->filled('to_date')) {
            $templateTransfers->where('original.transferred_at', '<=', $request->to_date . ' 23:59:59');
        }

        // تطبيق فلاتر المستخدمين على مهام القوالب
        if ($request->filled('from_user_id')) {
            $templateTransfers->where('original.user_id', $request->from_user_id);
        }

        if ($request->filled('to_user_id')) {
            $templateTransfers->where('received.user_id', $request->to_user_id);
        }

        $templateTransfers->select(
            DB::raw('CONVERT(template_tasks.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as task_name'),
            DB::raw('CONVERT(COALESCE(from_users.name, "غير محدد") USING utf8mb4) COLLATE utf8mb4_unicode_ci as from_user_name'),
            DB::raw('CONVERT(to_users.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as to_user_name'),
            'from_users.employee_id as from_user_employee_id',
            'to_users.employee_id as to_user_employee_id',
            'original.user_id as from_user_id',
            'received.user_id as to_user_id',
            DB::raw('CONVERT(COALESCE(original.transfer_reason, "") USING utf8mb4) COLLATE utf8mb4_unicode_ci as reason'),
            DB::raw('CONVERT(COALESCE(original.transfer_type, "positive") USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_direction'),
            'original.transferred_at',
            DB::raw('CONVERT(projects.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as project_name'),
            'projects.id as project_id',
            DB::raw('CONVERT("غير محدد" USING utf8mb4) COLLATE utf8mb4_unicode_ci as season_name'),
            DB::raw('NULL as season_id'),
            DB::raw('CONVERT(received.status USING utf8mb4) COLLATE utf8mb4_unicode_ci as current_status'),
            DB::raw('CONVERT("template" USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_type'),
            DB::raw('COALESCE(template_tasks.points, 0) as transfer_points')
        );

        // دمج النتائج وترتيبها
        $unionQuery = $regularTransfers->union($templateTransfers);

        // تطبيق فلتر نوع المهمة على النتيجة النهائية
        if ($request->filled('task_type')) {
            if ($request->task_type === 'task') {
                // فقط المهام العادية
                $unionQuery->where('transfer_type', 'regular');
            } elseif ($request->task_type === 'template_task') {
                // فقط مهام القوالب
                $unionQuery->where('transfer_type', 'template');
            }
        }

        return $unionQuery->orderBy('transferred_at', 'desc');
    }


    /**
     * الحصول على العدد الإجمالي للمهام المنقولة
     */
    private function getTotalTransfersCount($request)
    {
        $regularCount = DB::table('task_users as received')
            ->leftJoin('task_users as original', 'received.original_task_user_id', '=', 'original.id')
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        $templateCount = DB::table('template_task_user as received')
            ->leftJoin('template_task_user as original', 'received.original_template_task_user_id', '=', 'original.id')
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        // تطبيق فلاتر التاريخ
        if ($request->filled('from_date')) {
            $regularCount->where('original.transferred_at', '>=', $request->from_date . ' 00:00:00');
            $templateCount->where('original.transferred_at', '>=', $request->from_date . ' 00:00:00');
        }

        if ($request->filled('to_date')) {
            $regularCount->where('original.transferred_at', '<=', $request->to_date . ' 23:59:59');
            $templateCount->where('original.transferred_at', '<=', $request->to_date . ' 23:59:59');
        }

        // تطبيق فلاتر المستخدمين
        if ($request->filled('from_user_id')) {
            $regularCount->where('original.user_id', $request->from_user_id);
            $templateCount->where('original.user_id', $request->from_user_id);
        }

        if ($request->filled('to_user_id')) {
            $regularCount->where('received.user_id', $request->to_user_id);
            $templateCount->where('received.user_id', $request->to_user_id);
        }

        // تطبيق فلتر نوع المهمة
        if ($request->filled('task_type')) {
            if ($request->task_type === 'task') {
                $templateCount->whereRaw('1 = 0'); // لا توجد نتائج للقوالب
            } elseif ($request->task_type === 'template_task') {
                $regularCount->whereRaw('1 = 0'); // لا توجد نتائج للمهام العادية
            }
        }

        return [
            'regular' => $regularCount->count(),
            'template' => $templateCount->count(),
            'total' => $regularCount->count() + $templateCount->count()
        ];
    }

    /**
     * الحصول على إحصائيات المهام حسب النوع
     */
    private function getTransfersByType($request)
    {
        $query = $this->buildTransfersQuery($request);
        $results = $query->get();

        return [
            'regular_count' => $results->where('transfer_type', 'regular')->count(),
            'template_count' => $results->where('transfer_type', 'template')->count(),
            'positive_count' => $results->where('transfer_direction', 'positive')->count(),
            'negative_count' => $results->where('transfer_direction', 'negative')->count(),
            'total_points' => $results->sum('transfer_points')
        ];
    }

    /**
     * تصدير البيانات
     */
    public function export(Request $request)
    {
        // TODO: تنفيذ منطق التصدير
        return response()->json(['message' => 'ميزة التصدير ستتم إضافتها قريباً']);
    }
}
