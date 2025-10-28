<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Display a listing of the skills.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $skills = Skill::with('category')
            ->orderBy('name')
            ->get();

        return view('skills.index', compact('skills'));
    }

    /**
     * Show the form for creating a new skill.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = SkillCategory::orderBy('name')->pluck('name', 'id');
        return view('skills.create', compact('categories'));
    }

    /**
     * Store a newly created skill in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:skills',
            'category_id' => 'required|exists:skill_categories,id',
            'description' => 'nullable|string',
            'max_points' => 'required|integer|min:1|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        Skill::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'max_points' => $request->max_points,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('skills.index')
            ->with('success', 'تم إنشاء المهارة بنجاح!');
    }

    /**
     * Display the specified skill.
     *
     * @param  \App\Models\Skill  $skill
     * @return \Illuminate\Http\Response
     */
    public function show(Skill $skill)
    {
        return view('skills.show', compact('skill'));
    }

    /**
     * Show the form for editing the specified skill.
     *
     * @param  \App\Models\Skill  $skill
     * @return \Illuminate\Http\Response
     */
    public function edit(Skill $skill)
    {
        $categories = SkillCategory::orderBy('name')->pluck('name', 'id');
        return view('skills.edit', compact('skill', 'categories'));
    }

    /**
     * Update the specified skill in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Skill  $skill
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Skill $skill)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:skills,name,'.$skill->id,
            'category_id' => 'required|exists:skill_categories,id',
            'description' => 'nullable|string',
            'max_points' => 'required|integer|min:1|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $skill->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'max_points' => $request->max_points,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('skills.index')
            ->with('success', 'تم تحديث المهارة بنجاح!');
    }

    /**
     * Remove the specified skill from storage.
     *
     * @param  \App\Models\Skill  $skill
     * @return \Illuminate\Http\Response
     */
    public function destroy(Skill $skill)
    {
        // التحقق ما إذا كانت المهارة مستخدمة في تقييمات
        if ($skill->evaluationDetails()->count() > 0) {
            return redirect()->route('skills.index')
                ->with('error', 'لا يمكن حذف المهارة لأنها مستخدمة في تقييمات!');
        }

        $skill->delete();

        return redirect()->route('skills.index')
            ->with('success', 'تم حذف المهارة بنجاح!');
    }
}
