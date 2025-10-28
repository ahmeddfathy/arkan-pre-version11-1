<?php

namespace App\Http\Controllers;

use App\Models\SkillCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Display a listing of the skill categories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = SkillCategory::withCount('skills')
            ->orderBy('name')
            ->get();

        return view('skills.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new skill category.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('skills.categories.create');
    }

    /**
     * Store a newly created skill category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:skill_categories',
            'description' => 'nullable|string',
        ]);

        SkillCategory::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('skill-categories.index')
            ->with('success', 'تم إنشاء تصنيف المهارات بنجاح!');
    }

    /**
     * Display the specified skill category.
     *
     * @param  \App\Models\SkillCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function show(SkillCategory $skillCategory)
    {
        $skills = $skillCategory->skills()->orderBy('name')->get();
        return view('skills.categories.show', compact('skillCategory', 'skills'));
    }

    /**
     * Show the form for editing the specified skill category.
     *
     * @param  \App\Models\SkillCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(SkillCategory $skillCategory)
    {
        return view('skills.categories.edit', compact('skillCategory'));
    }

    /**
     * Update the specified skill category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SkillCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SkillCategory $skillCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:skill_categories,name,'.$skillCategory->id,
            'description' => 'nullable|string',
        ]);

        $skillCategory->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('skill-categories.index')
            ->with('success', 'تم تحديث تصنيف المهارات بنجاح!');
    }

    /**
     * Remove the specified skill category from storage.
     *
     * @param  \App\Models\SkillCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(SkillCategory $skillCategory)
    {
        // التحقق ما إذا كان التصنيف يحتوي على مهارات
        if ($skillCategory->skills()->count() > 0) {
            return redirect()->route('skill-categories.index')
                ->with('error', 'لا يمكن حذف التصنيف لأنه يحتوي على مهارات!');
        }

        $skillCategory->delete();

        return redirect()->route('skill-categories.index')
            ->with('success', 'تم حذف تصنيف المهارات بنجاح!');
    }
}
