<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AttendanceImport;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class AttendanceRecordController extends Controller
{
  public function index(Request $request)
  {
    $employees = User::select('id', 'name', 'employee_id')
      ->orderBy('name')
      ->get();

    $query = AttendanceRecord::query()
      ->join('users', 'attendance_records.employee_id', '=', 'users.employee_id')
      ->select('attendance_records.*', 'users.name as employee_name');

    $selectedMonth = $request->input('month', now()->format('Y-m'));
    $startOfMonth = Carbon::parse($selectedMonth)->startOfMonth();
    $endOfMonth = Carbon::parse($selectedMonth)->endOfMonth();

    if ($request->has('employee_filter') && !empty($request->employee_filter)) {
      $query->where('attendance_records.employee_id', $request->employee_filter);
    }

    $query->whereBetween('attendance_date', [$startOfMonth, $endOfMonth]);

    $records = $query->orderBy('attendance_date', 'desc')
      ->paginate(10)
      ->appends($request->except('page'));

    $attendanceStats = [
      'present_days' => 0,
      'absent_days' => 0,
      'violation_days' => 0,
      'late_days' => 0,
      'total_delay_minutes' => 0,
      'avg_delay_minutes' => 0,
      'max_delay_minutes' => 0,
      'attendance_rate' => 0
    ];

    if ($request->has('employee_filter') && !empty($request->employee_filter)) {
      $statsQuery = AttendanceRecord::where('employee_id', $request->employee_filter)
        ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth]);

      $attendanceStats['present_days'] = (clone $statsQuery)
        ->where('status', 'حضـور')
        ->whereNotNull('entry_time')
        ->count();

      $attendanceStats['absent_days'] = (clone $statsQuery)
        ->where('status', 'غيــاب')
        ->count();

      $attendanceStats['violation_days'] = (clone $statsQuery)
        ->where('penalty', '>', 0)
        ->count();

      $lateRecords = (clone $statsQuery)
        ->where('delay_minutes', '>', 0)
        ->whereNotNull('entry_time')
        ->get();

      $attendanceStats['late_days'] = $lateRecords->count();
      $attendanceStats['total_delay_minutes'] = $lateRecords->sum('delay_minutes');
      $attendanceStats['avg_delay_minutes'] = $lateRecords->count() > 0
        ? round($lateRecords->average('delay_minutes'), 1)
        : 0;
      $attendanceStats['max_delay_minutes'] = $lateRecords->max('delay_minutes') ?? 0;

      $totalDays = $attendanceStats['present_days'] + $attendanceStats['absent_days'];
      $attendanceStats['attendance_rate'] = $totalDays > 0
        ? round(($attendanceStats['present_days'] / $totalDays) * 100, 1)
        : 0;
    }

    $selectedEmployeeName = '';
    if ($request->has('employee_filter') && !empty($request->employee_filter)) {
      $selectedEmployee = $employees->firstWhere('employee_id', $request->employee_filter);
      $selectedEmployeeName = $selectedEmployee ? $selectedEmployee->name : '';
    }

    return view('attendancesRecord.index', compact(
      'records',
      'employees',
      'selectedEmployeeName',
      'attendanceStats',
      'selectedMonth'
    ));
  }

  public function import(Request $request)
  {
    Excel::import(new AttendanceImport, $request->file('file'));

    if (Session::has('duplicate_records')) {
      return redirect()->route('attendance.records.index')
        ->with('duplicates', Session::get('duplicate_records'))
        ->with('duplicate_count', Session::get('duplicate_count'))
        ->with('success', 'تم استيراد البيانات بنجاح');
    }

    return redirect()->route('attendance.records.index')
      ->with('success', 'تم استيراد البيانات بنجاح');
  }
}
