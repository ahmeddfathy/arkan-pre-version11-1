<?php

namespace App\Http\Controllers;

use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkShiftController extends Controller
{
    public function index()
    {
        $workShifts = WorkShift::all();
        return view('work-shifts.index', compact('workShifts'));
    }

    public function create()
    {
        return view('work-shifts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'check_in_time' => 'required',
            'check_out_time' => 'required',
            'break_start_time' => 'nullable',
            'break_end_time' => 'nullable',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $validated['is_active'] = (bool)$validated['is_active'];

        WorkShift::create($validated);

        return redirect()->route('work-shifts.index')
            ->with('success', 'تم إنشاء الوردية بنجاح.');
    }

    public function show(WorkShift $workShift)
    {
        return view('work-shifts.show', compact('workShift'));
    }

    public function edit(WorkShift $workShift)
    {
        return view('work-shifts.edit', compact('workShift'));
    }

    public function update(Request $request, WorkShift $workShift)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'check_in_time' => 'required',
            'check_out_time' => 'required',
            'break_start_time' => 'nullable',
            'break_end_time' => 'nullable',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $validated['is_active'] = (bool)$validated['is_active'];

        $workShift->update($validated);

        return redirect()->route('work-shifts.index')
            ->with('success', 'تم تحديث الوردية بنجاح.');
    }

    public function destroy(WorkShift $workShift)
    {
        $workShift->delete();

        return redirect()->route('work-shifts.index')
            ->with('success', 'تم حذف الوردية بنجاح.');
    }

    public function toggleStatus(WorkShift $workShift)
    {
        $workShift->is_active = !$workShift->is_active;
        $workShift->save();

        return redirect()->route('work-shifts.index')
            ->with('success', 'تم تغيير حالة الوردية بنجاح.');
    }
}
