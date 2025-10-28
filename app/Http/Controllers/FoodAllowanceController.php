<?php

namespace App\Http\Controllers;

use App\Models\FoodAllowance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FoodAllowanceController extends Controller
{
    // عرض بدل الأكل مع الفلترة
    public function index(Request $request)
    {
        // التحقق من الصلاحية
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        // تسجيل النشاط - دخول صفحة بدل الطعام
        activity()
            ->causedBy($authUser)
            ->withProperties([
                'action_type' => 'view_index',
                'page' => 'food_allowance_index',
                'filters' => $request->all(),
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ])
            ->log('دخل على صفحة بدل الطعام');

        $defaultStartDate = now()->day >= 26 ? now()->day(26) : now()->subMonth()->day(26);
        $defaultEndDate = $defaultStartDate->copy()->addMonth()->day(25);

        $startDate = $request->input('start_date', $defaultStartDate->format('Y-m-d'));
        $endDate = $request->input('end_date', $defaultEndDate->format('Y-m-d'));

        // جلب الموظفين النشطين فقط
        $users = User::where('employee_status', 'active')
            ->orderBy('name')
            ->get();

        // جلب كل السجلات في هذه الفترة
        $allowances = FoodAllowance::whereBetween('date', [$startDate, $endDate])
            ->with('user')
            ->get();

        // تجميع المبالغ لكل موظف
        $totals = $allowances->groupBy('user_id')->map(function($rows) {
            return $rows->sum('amount');
        });

        // ترتيب الموظفين حسب المبالغ (الأعلى أولاً)
        $users = $users->sortByDesc(function($user) use ($totals) {
            return $totals[$user->id] ?? 0;
        });

        return view('food-allowances.index', compact('users', 'allowances', 'startDate', 'endDate', 'totals'));
    }

    // إضافة سجل جديد
    public function store(Request $request)
    {
        // التحقق من الصلاحية
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'food_type' => 'required|string|max:100',
        ]);

        FoodAllowance::create([
            'user_id' => $request->user_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'food_type' => $request->food_type,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'تم تسجيل طلب الأكل بنجاح');
    }

    // تعديل سجل
    public function update(Request $request, $id)
    {
        // التحقق من الصلاحية
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'food_type' => 'required|string|max:100',
        ]);

        $allowance = FoodAllowance::findOrFail($id);
        $allowance->update([
            'date' => $request->date,
            'amount' => $request->amount,
            'food_type' => $request->food_type,
        ]);

        return redirect()->back()->with('success', 'تم تعديل طلب الأكل بنجاح');
    }

    // حذف سجل
    public function destroy($id)
    {
        // التحقق من الصلاحية
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $allowance = FoodAllowance::findOrFail($id);
        $allowance->delete();
        return redirect()->back()->with('success', 'تم حذف طلب الأكل بنجاح');
    }

    // عرض طلبات الموظف
    public function myRequests(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        // تسجيل النشاط - دخول الموظف لمشاهدة طلباته
        activity()
            ->causedBy($authUser)
            ->withProperties([
                'action_type' => 'view_my_requests',
                'page' => 'food_allowance_my_requests',
                'filters' => $request->all(),
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ])
            ->log('دخل على صفحة طلبات الأكل الخاصة به');

        // الفترة الافتراضية (آخر 3 أشهر)
        $defaultStartDate = now()->subMonths(3)->startOfMonth();
        $defaultEndDate = now()->endOfMonth();

        $startDate = $request->input('start_date', $defaultStartDate->format('Y-m-d'));
        $endDate = $request->input('end_date', $defaultEndDate->format('Y-m-d'));

        // جلب طلبات الموظف في الفترة المحددة
        $allowances = FoodAllowance::where('user_id', $authUser->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['user', 'creator'])
            ->orderBy('date', 'desc')
            ->get();

        // حساب إجمالي المبالغ ونوع الأكل
        $totalAmount = $allowances->sum('amount');
        $foodTypes = $allowances->groupBy('food_type')->map(function($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount')
            ];
        });

        return view('food-allowances.my-requests', compact(
            'allowances',
            'startDate',
            'endDate',
            'totalAmount',
            'foodTypes'
        ));
    }
}
