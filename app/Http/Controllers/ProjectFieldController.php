<?php

namespace App\Http\Controllers;

use App\Models\ProjectField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fields = ProjectField::orderBy('order')->get();
        return view('admin.project-fields.index', compact('fields'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project-fields.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'field_key' => 'nullable|string|max:255|unique:project_fields,field_key',
            'field_type' => 'required|in:text,select,textarea,number,date',
            'field_options' => 'nullable|array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        // إذا لم يتم تحديد field_key، قم بإنشائه تلقائياً من الاسم
        if (empty($validated['field_key'])) {
            $validated['field_key'] = Str::slug($validated['name'], '_');

            // التأكد من عدم وجود مفتاح مكرر
            $counter = 1;
            $originalKey = $validated['field_key'];
            while (ProjectField::where('field_key', $validated['field_key'])->exists()) {
                $validated['field_key'] = $originalKey . '_' . $counter;
                $counter++;
            }
        }

        // تحديد الترتيب إذا لم يتم تحديده
        if (!isset($validated['order'])) {
            $maxOrder = ProjectField::max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        ProjectField::create($validated);

        return redirect()->route('project-fields.index')
            ->with('success', 'تم إنشاء الحقل بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectField $projectField)
    {
        return view('admin.project-fields.show', compact('projectField'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectField $projectField)
    {
        return view('admin.project-fields.edit', compact('projectField'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectField $projectField)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'field_key' => 'required|string|max:255|unique:project_fields,field_key,' . $projectField->id,
            'field_type' => 'required|in:text,select,textarea,number,date',
            'field_options' => 'nullable|array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $projectField->update($validated);

        return redirect()->route('project-fields.index')
            ->with('success', 'تم تحديث الحقل بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectField $projectField)
    {
        $projectField->delete();

        return redirect()->route('project-fields.index')
            ->with('success', 'تم حذف الحقل بنجاح');
    }

    /**
     * Update the order of fields
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'fields' => 'required|array',
            'fields.*.id' => 'required|exists:project_fields,id',
            'fields.*.order' => 'required|integer',
        ]);

        foreach ($request->fields as $field) {
            ProjectField::where('id', $field['id'])->update(['order' => $field['order']]);
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث الترتيب بنجاح']);
    }

    /**
     * Toggle field active status
     */
    public function toggleActive(ProjectField $projectField)
    {
        $projectField->update(['is_active' => !$projectField->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $projectField->is_active,
            'message' => $projectField->is_active ? 'تم تفعيل الحقل' : 'تم إلغاء تفعيل الحقل'
        ]);
    }
}
