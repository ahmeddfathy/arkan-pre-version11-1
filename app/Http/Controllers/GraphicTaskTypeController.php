<?php

namespace App\Http\Controllers;

use App\Models\GraphicTaskType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GraphicTaskTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض قائمة أنواع المهام الجرافيكية
     */
    public function index()
    {
        $graphicTaskTypes = GraphicTaskType::orderBy('created_at', 'desc')->paginate(10);

        return view('graphic-task-types.index', compact('graphicTaskTypes'));
    }



    /**
     * حفظ نوع مهمة جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:graphic_task_types,name',
            'description' => 'nullable|string|max:1000',
            'points' => 'required|integer|min:1|max:100',
            'min_minutes' => 'required|integer|min:1|max:1440',
            'max_minutes' => 'required|integer|min:1|max:1440',
            'average_minutes' => 'required|integer|min:1|max:1440',
            'department' => 'required|string|max:100',
            'is_active' => 'sometimes|in:0,1',
        ]);

        // التحقق من أن الحد الأدنى أقل من الحد الأقصى
        $validator->after(function ($validator) use ($request) {
            if ($request->min_minutes >= $request->max_minutes) {
                $validator->errors()->add('min_minutes', 'الحد الأدنى يجب أن يكون أقل من الحد الأقصى');
            }

            if ($request->average_minutes < $request->min_minutes || $request->average_minutes > $request->max_minutes) {
                $validator->errors()->add('average_minutes', 'المتوسط يجب أن يكون بين الحد الأدنى والأقصى');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        GraphicTaskType::create([
            'name' => $request->name,
            'description' => $request->description,
            'points' => $request->points,
            'min_minutes' => $request->min_minutes,
            'max_minutes' => $request->max_minutes,
            'average_minutes' => $request->average_minutes,
            'department' => $request->department,
            'is_active' => (bool) $request->input('is_active', 1),
        ]);

        return redirect()->route('graphic-task-types.index')
            ->with('success', 'تم إضافة نوع المهمة الجرافيكية بنجاح');
    }

        /**
     * عرض تفاصيل نوع مهمة معين
     */
    public function show(GraphicTaskType $graphicTaskType)
    {
        $graphicTaskType->load('tasks');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $graphicTaskType->id,
                'name' => $graphicTaskType->name,
                'description' => $graphicTaskType->description,
                'points' => $graphicTaskType->points,
                'min_minutes' => $graphicTaskType->min_minutes,
                'max_minutes' => $graphicTaskType->max_minutes,
                'average_minutes' => $graphicTaskType->average_minutes,
                'average_time_formatted' => $graphicTaskType->average_time_formatted,
                'department' => $graphicTaskType->department,
                'is_active' => $graphicTaskType->is_active,
                'tasks_count' => $graphicTaskType->tasks()->count(),
                'created_at' => $graphicTaskType->created_at,
                'updated_at' => $graphicTaskType->updated_at,
            ]
        ]);
    }



    /**
     * تحديث نوع مهمة موجود
     */
    public function update(Request $request, GraphicTaskType $graphicTaskType)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:graphic_task_types,name,' . $graphicTaskType->id,
            'description' => 'nullable|string|max:1000',
            'points' => 'required|integer|min:1|max:100',
            'min_minutes' => 'required|integer|min:1|max:1440',
            'max_minutes' => 'required|integer|min:1|max:1440',
            'average_minutes' => 'required|integer|min:1|max:1440',
            'department' => 'required|string|max:100',
            'is_active' => 'sometimes|in:0,1',
        ]);

        // التحقق من أن الحد الأدنى أقل من الحد الأقصى
        $validator->after(function ($validator) use ($request) {
            if ($request->min_minutes >= $request->max_minutes) {
                $validator->errors()->add('min_minutes', 'الحد الأدنى يجب أن يكون أقل من الحد الأقصى');
            }

            if ($request->average_minutes < $request->min_minutes || $request->average_minutes > $request->max_minutes) {
                $validator->errors()->add('average_minutes', 'المتوسط يجب أن يكون بين الحد الأدنى والأقصى');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $graphicTaskType->update([
            'name' => $request->name,
            'description' => $request->description,
            'points' => $request->points,
            'min_minutes' => $request->min_minutes,
            'max_minutes' => $request->max_minutes,
            'average_minutes' => $request->average_minutes,
            'department' => $request->department,
            'is_active' => (bool) $request->input('is_active', 0),
        ]);

        return redirect()->route('graphic-task-types.index')
            ->with('success', 'تم تحديث نوع المهمة الجرافيكية بنجاح');
    }

    /**
     * حذف نوع مهمة
     */
    public function destroy(GraphicTaskType $graphicTaskType)
    {
        // التحقق من عدم وجود مهام مرتبطة بهذا النوع
        if ($graphicTaskType->tasks()->count() > 0) {
            return redirect()->back()
                ->with('error', 'لا يمكن حذف هذا النوع لأنه مرتبط بمهام موجودة');
        }

        $graphicTaskType->delete();

        return redirect()->route('graphic-task-types.index')
            ->with('success', 'تم حذف نوع المهمة الجرافيكية بنجاح');
    }

    /**
     * تبديل حالة النشاط
     */
    public function toggleStatus(GraphicTaskType $graphicTaskType)
    {
        $graphicTaskType->update([
            'is_active' => !$graphicTaskType->is_active
        ]);

        $status = $graphicTaskType->is_active ? 'تفعيل' : 'إلغاء تفعيل';

        return response()->json([
            'success' => true,
            'message' => "تم {$status} نوع المهمة بنجاح",
            'is_active' => $graphicTaskType->is_active
        ]);
    }

    /**
     * الحصول على أنواع المهام النشطة (للاستخدام في الـ AJAX)
     */
    public function getActiveTypes()
    {
        $types = GraphicTaskType::active()
            ->forDepartment('التصميم')
            ->select('id', 'name', 'points', 'average_minutes', 'min_minutes', 'max_minutes')
            ->orderBy('name')
            ->get();

        return response()->json($types);
    }
}
