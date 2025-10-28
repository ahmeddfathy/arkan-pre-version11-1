<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\WorkShift;
use App\Traits\HasNTPTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use DateTimeZone;

class AttendanceController extends Controller
{
    use HasNTPTime;

    public function dashboard(Request $request)
    {
        $date = $request->input('date', $this->getCurrentCairoTime()->toDateString());

        $employees = User::with(['workShift', 'attendances' => function($query) use ($date) {
            $query->where('date', $date);
        }])
        ->where('role', '!=', 'admin')
        ->where('employee_status', 'active')
        ->get();

        $sortedEmployees = $employees->sortBy(function ($employee) {
            $attendance = $employee->attendances->first();
            if (!$attendance || !$attendance->check_in) {
                return PHP_INT_MAX;
            }
            return $attendance->check_in->timestamp;
        });

        return view('attendance.dashboard', ['employees' => $sortedEmployees, 'date' => $date]);
    }

    public function myAttendance(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Attendance::where('employee_id', $user->employee_id)
            ->orderBy('date', 'desc');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $attendances = $query->paginate(15)->appends($request->except('page'));

        return view('attendance.my-attendance', compact('attendances', 'startDate', 'endDate'));
    }

    public function checkIn(Request $request)
    {
        $user = Auth::user();
        $now = $this->getCurrentCairoTime();
        $today = $now->toDateString();

        Log::info('Check-in time (adjusted): ' . $now->format('Y-m-d H:i:s'));
        Log::info('Server time: ' . Carbon::now()->format('Y-m-d H:i:s'));

        $attendance = Attendance::where('employee_id', $user->employee_id)
            ->where('date', $today)
            ->first();

        if ($attendance && $attendance->check_in) {
            return redirect()->back()->with('error', 'You have already checked in today.');
        }

        $workShift = $user->workShift;
        if (!$workShift) {
            return redirect()->back()->with('error', 'You do not have an assigned work shift.');
        }

        $shiftStartTime = Carbon::parse($today . ' ' . $workShift->check_in_time->format('H:i:s'), new DateTimeZone('Africa/Cairo'));
        $lateMinutes = 0;
        $earlyArrivalMinutes = 0;

        if ($now->gt($shiftStartTime)) {
            $lateMinutes = abs(round($now->diffInMinutes($shiftStartTime)));
        } else {
            $earlyArrivalMinutes = abs(round($shiftStartTime->diffInMinutes($now)));
        }

        if (!$attendance) {
            $attendance = new Attendance();
            $attendance->employee_id = $user->employee_id;
            $attendance->date = $today;
            $attendance->work_shift_id = $workShift->id;
        }

        $attendance->check_in = $now->setTimezone('Africa/Cairo');
        $attendance->late_minutes = $lateMinutes;
        $attendance->early_minutes = $earlyArrivalMinutes;
        $attendance->status = $lateMinutes > 0 ? 'late' : 'present';
        $attendance->save();

        return redirect()->back()->with('success', 'Check-in recorded successfully.');
    }

    public function checkOut(Request $request)
    {
        $user = Auth::user();
        $now = $this->getCurrentCairoTime();
        $today = $now->toDateString();

        Log::info('Check-out time (adjusted): ' . $now->format('Y-m-d H:i:s'));
        Log::info('Server time: ' . Carbon::now()->format('Y-m-d H:i:s'));

        $attendance = Attendance::where('employee_id', $user->employee_id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in) {
            return redirect()->back()->with('error', 'You need to check in first.');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'You have already checked out today.');
        }

        $workShift = $user->workShift;
        if (!$workShift) {
            return redirect()->back()->with('error', 'You do not have an assigned work shift.');
        }

        Log::info('Work shift check-out time: ' . $workShift->check_out_time->format('H:i:s'));

        $shiftEndTime = Carbon::parse($today . ' ' . $workShift->check_out_time->format('H:i:s'), new DateTimeZone('Africa/Cairo'));

        Log::info('Calculated shift end time: ' . $shiftEndTime->format('Y-m-d H:i:s'));
        Log::info('Current time for comparison: ' . $now->format('Y-m-d H:i:s'));
        Log::info('Is now earlier than shift end? ' . ($now->lt($shiftEndTime) ? 'Yes' : 'No'));

        $earlyDepartureMinutes = 0;
        $lateDepartureMinutes = 0;

        if ($now->lt($shiftEndTime)) {
            $earlyDepartureMinutes = abs(round($shiftEndTime->diffInMinutes($now)));
            Log::info('Early departure minutes calculated: ' . $earlyDepartureMinutes);
        } else {
            $lateDepartureMinutes = abs(round($now->diffInMinutes($shiftEndTime)));
            Log::info('Late departure minutes calculated: ' . $lateDepartureMinutes);
        }

        $attendance->check_out = $now->setTimezone('Africa/Cairo');

        $existingNotes = $attendance->notes ?? '';

        if ($earlyDepartureMinutes > 0) {
            $attendance->notes = trim($existingNotes . ' Early departure: ' . $earlyDepartureMinutes . ' minutes');
            Log::info('Adding early departure note: ' . $earlyDepartureMinutes . ' minutes');

            $attendance->status = 'early_leave';
        }

        $attendance->working_hours = $attendance->calculateWorkingHours();

        if ($lateDepartureMinutes > 0) {
            $attendance->notes = trim($existingNotes . ' Late departure: ' . $lateDepartureMinutes . ' minutes');
            Log::info('Adding late departure note: ' . $lateDepartureMinutes . ' minutes');
        }

        $attendance->save();

        return redirect()->back()->with('success', 'Check-out recorded successfully.');
    }

    public function index(Request $request)
    {
        $date = $request->input('date', $this->getCurrentCairoTime()->toDateString());
        $employeeId = $request->input('employee_id');
        $status = $request->input('status');

        $query = Attendance::with(['employee', 'workShift'])
            ->when($date, function($q) use ($date) {
                return $q->whereDate('date', $date);
            })
            ->when($employeeId, function($q) use ($employeeId) {
                return $q->where('employee_id', $employeeId);
            })
            ->when($status, function($q) use ($status) {
                return $q->where('status', $status);
            })
            ->whereHas('employee', function($q) {
                $q->where('employee_status', 'active');
            });

        $query->orderByRaw('CASE WHEN check_in IS NULL THEN 1 ELSE 0 END')
              ->orderBy('check_in')
              ->orderBy('date', 'desc')
              ->orderBy('employee_id');

        $attendances = $query->paginate(15);
        $employees = User::where('role', '!=', 'admin')
                        ->where('employee_status', 'active')
                        ->get();

        return view('attendance.index', compact('attendances', 'employees', 'date', 'employeeId', 'status'));
    }

    public function cancelCheckIn(Request $request)
    {
        $user = Auth::user();
        $now = $this->getCurrentCairoTime();
        $today = $now->toDateString();

        $attendance = Attendance::where('employee_id', $user->employee_id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in) {
            return redirect()->back()->with('error', 'No check-in record found for today.');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'Cannot cancel check-in after checking out. Please cancel check-out first.');
        }

        $workShift = $user->workShift;
        if (!$workShift) {
            return redirect()->back()->with('error', 'You do not have an assigned work shift.');
        }

        $shiftEndTime = Carbon::parse($today . ' ' . $workShift->check_out_time->format('H:i:s'), new DateTimeZone('Africa/Cairo'));

        if ($now->gt($shiftEndTime)) {
            return redirect()->back()->with('error', 'Cannot cancel check-in after work shift has ended.');
        }

        $attendance->check_in = null;
        $attendance->late_minutes = 0;
        $attendance->early_minutes = 0;
        $attendance->status = 'absent';
        $attendance->save();

        return redirect()->back()->with('success', 'Check-in has been cancelled successfully. You can check-in again.');
    }

    public function cancelCheckOut(Request $request)
    {
        $user = Auth::user();
        $now = $this->getCurrentCairoTime();
        $today = $now->toDateString();

        $attendance = Attendance::where('employee_id', $user->employee_id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_out) {
            return redirect()->back()->with('error', 'No check-out record found for today.');
        }

        $workShift = $user->workShift;
        if (!$workShift) {
            return redirect()->back()->with('error', 'You do not have an assigned work shift.');
        }

        $endOfDay = Carbon::parse($today)->endOfDay();

        if ($now->gt($endOfDay)) {
            return redirect()->back()->with('error', 'Cannot cancel check-out after the day has ended.');
        }

        $earlyArrivalMinutes = $attendance->early_minutes;

        $attendance->check_out = null;

        if ($attendance->notes) {
            $attendance->notes = preg_replace('/(Early departure: \d+ minutes\s*)+/', '', $attendance->notes);
            $attendance->notes = preg_replace('/(Late departure: \d+ minutes\s*)+/', '', $attendance->notes);
            $attendance->notes = trim($attendance->notes);
        }

        $shiftStartTime = Carbon::parse($today . ' ' . $workShift->check_in_time->format('H:i:s'), new DateTimeZone('Africa/Cairo'));
        if ($attendance->check_in->gt($shiftStartTime)) {
            $attendance->status = 'late';
            $attendance->early_minutes = 0;
        } else {
            $attendance->status = 'present';
            $attendance->early_minutes = $earlyArrivalMinutes;
        }

        $attendance->save();

        return redirect()->back()->with('success', 'Check-out has been cancelled successfully. You can check-out again.');
    }
}
