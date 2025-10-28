<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequests;
use App\Models\AttendanceRecord;
use App\Models\SpecialCase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeCompetitionController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $now = now();

        // Get default date range (26th to 25th of next month)
        $startDate = $request->start_date ?? ($now->day >= 26
            ? $now->copy()->startOfDay()->setDay(26)
            : $now->copy()->subMonth()->startOfDay()->setDay(26))->format('Y-m-d');

        $endDate = $request->end_date ?? ($now->day >= 26
            ? $now->copy()->addMonth()->startOfDay()->setDay(25)->endOfDay()
            : $now->copy()->startOfDay()->setDay(25)->endOfDay())->format('Y-m-d');

        // Base query for employees
        $employeeQuery = User::query();

        // Apply role-based filters
        if ($user->hasRole('hr')) {
            $employeeQuery->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['hr', 'company_manager']);
            });
        } elseif ($user->hasRole(['department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager'])) {
            $managedTeams = $user->allTeams()->pluck('id');
            $employeeQuery->whereHas('teams', function ($q) use ($managedTeams) {
                $q->whereIn('teams.id', $managedTeams);
            });
        } elseif ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'])) {
            if ($user->currentTeam) {
                $teamMembers = $user->currentTeam->users()
                    ->whereHas('roles', function ($q) {
                        $q->where('name', 'employee');
                    })
                    ->pluck('users.id');
                $employeeQuery->whereIn('id', $teamMembers);
            } else {
                $employeeQuery->where('id', 0);
            }
        }

        // Get employees with their statistics
        $employees = $employeeQuery->get()->map(function ($employee) use ($startDate, $endDate) {
            // Get attendance statistics
            $attendanceStats = $this->getAttendanceStats($employee, $startDate, $endDate);

            // Calculate early minutes (الحضور المبكر)
            // Only use regular attendance records for early minutes
            $earlyMinutes = AttendanceRecord::where('employee_id', $employee->employee_id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('early_minutes', '>', 0)
                ->sum('early_minutes');

            // Get permissions count
            $permissionsCount = PermissionRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->count();

            // Get leaves count
            $leavesCount = AbsenceRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [$startDate, $endDate])
                ->count();

            // Calculate competition points
            $points = $this->calculatePoints(
                $attendanceStats['attendance_percentage'],
                $earlyMinutes,
                $attendanceStats['absences'],
                $permissionsCount,
                $leavesCount
            );

            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'department' => $employee->department,
                'team' => $employee->currentTeam ? $employee->currentTeam->name : null,
                'attendance_percentage' => $attendanceStats['attendance_percentage'],
                'total_working_days' => $attendanceStats['total_working_days'],
                'actual_attendance_days' => $attendanceStats['actual_attendance_days'],
                'total_shift_hours' => $attendanceStats['total_shift_hours'],
                'actual_working_hours' => $attendanceStats['actual_working_hours'],
                'early_minutes' => $earlyMinutes,
                'absences' => $attendanceStats['absences'],
                'permissions_count' => $permissionsCount,
                'leaves_count' => $leavesCount,
                'points' => $points,
                'profile_photo' => $employee->profile_photo_url
            ];
        });

        // Sort employees by different metrics
        $sortBy = $request->sort_by ?? 'points';
        $employees = match ($sortBy) {
            'attendance' => $employees->sortByDesc('attendance_percentage'),
            'early_minutes' => $employees->sortByDesc('early_minutes'),
            'absences' => $employees->sortBy('absences'),
            'permissions' => $employees->sortBy('permissions_count'),
            'leaves' => $employees->sortBy('leaves_count'),
            default => $employees->sortByDesc('points')
        };


        $topPerformers = [
            'attendance' => $employees->sortByDesc('attendance_percentage')->take(3),
            'early_minutes' => $employees->sortByDesc('early_minutes')->take(3),
            'least_absences' => $employees->sortBy('absences')->take(3),
            'least_permissions' => $employees->sortBy('permissions_count')->take(3),
            'least_leaves' => $employees->sortBy('leaves_count')->take(3),
            'overall' => $employees->sortByDesc('points')->take(3)
        ];

        return view('employee-competition.index', compact(
            'employees',
            'topPerformers',
            'startDate',
            'endDate',
            'sortBy'
        ));
    }

    private function getAttendanceStats($employee, $startDate, $endDate)
    {
        $statsQuery = AttendanceRecord::where('employee_id', $employee->employee_id)
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        // Get special cases for this period
        $specialCases = SpecialCase::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->mapWithKeys(function ($case) {
                return [Carbon::parse($case->date)->format('Y-m-d') => $case];
            })
            ->all();

        // Calculate total shift hours and working hours
        $attendanceRecords = (clone $statsQuery)
            ->where(function ($query) {
                $query->where('status', 'حضـور')
                    ->orWhere('status', 'غيــاب');
            })
            ->get();

        $totalShiftHours = 0;
        $actualWorkingHours = 0;

        foreach ($attendanceRecords as $record) {
            $totalShiftHours += $record->shift_hours ?? 0;

            if ($record->status === 'حضـور') {
                // Use working hours, capped at shift hours
                $workingHours = min($record->working_hours ?? 0, $record->shift_hours ?? 0);
                $actualWorkingHours += $workingHours;
            } elseif ($record->status === 'غيــاب') {
                // Use shift hours for absences
                $actualWorkingHours += $record->shift_hours ?? 0;
            }
        }

        $totalWorkDays = $attendanceRecords->count();
        $actualAttendanceDays = 0;

        foreach ($attendanceRecords as $record) {
            $date = Carbon::parse($record->attendance_date)->format('Y-m-d');

            if (isset($specialCases[$date])) {
                if ($record->status === 'حضـور' || $record->status === 'غيــاب') {
                    $actualAttendanceDays++;
                }
            } else {
                if ($record->status === 'حضـور' && $record->entry_time) {
                    $actualAttendanceDays++;
                }
            }
        }

        // Calculate absences correctly
        $absences = $totalWorkDays - $actualAttendanceDays;

        // Check for approved leaves
        $approvedLeaves = AbsenceRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [$startDate, $endDate])
            ->count();

        // Absences should not include approved leaves
        $absences = max(0, $absences - $approvedLeaves);

        return [
            'total_working_days' => $totalWorkDays,
            'actual_attendance_days' => $actualAttendanceDays,
            'attendance_percentage' => $totalWorkDays > 0
                ? round(($actualAttendanceDays / $totalWorkDays) * 100, 1)
                : 0,
            'absences' => $absences,
            'total_shift_hours' => round($totalShiftHours, 2),
            'actual_working_hours' => round($actualWorkingHours, 2)
        ];
    }

    private function calculatePoints(
        $attendancePercentage,
        $earlyMinutes,
        $absences,
        $permissionsCount,
        $leavesCount
    ) {
        $points = 0;

        // Points for attendance percentage (max 50 points)
        $points += ($attendancePercentage / 100) * 50;

        // Points for early arrival (max 20 points)
        // New calculation: More gradual increase based on early minutes
        if ($earlyMinutes > 0) {
            if ($earlyMinutes <= 60) { // First hour
                $points += ($earlyMinutes / 60) * 10;
            } else if ($earlyMinutes <= 120) { // Second hour
                $points += 10 + (($earlyMinutes - 60) / 60) * 6;
            } else { // More than 2 hours
                $points += 16 + (($earlyMinutes - 120) / 60) * 4;
                $points = min($points, 20); // Cap at 20 points
            }
        }

        // Deductions for absences (max -15 points)
        // Progressive deduction: each absence costs more
        if ($absences > 0) {
            if ($absences <= 2) {
                $points -= $absences * 4;
            } else if ($absences <= 4) {
                $points -= 8 + (($absences - 2) * 5);
            } else {
                $points -= min(15, 18 + (($absences - 4) * 6));
            }
        }

        // Deductions for permissions (max -7.5 points)
        // Progressive deduction: more permissions result in higher deductions per permission
        if ($permissionsCount > 0) {
            if ($permissionsCount <= 2) {
                $points -= $permissionsCount * 2;
            } else if ($permissionsCount <= 4) {
                $points -= 4 + (($permissionsCount - 2) * 2.5);
            } else {
                $points -= min(7.5, 9 + (($permissionsCount - 4) * 3));
            }
        }

        // Deductions for leaves (max -7.5 points)
        // Progressive deduction: similar to permissions
        if ($leavesCount > 0) {
            if ($leavesCount <= 2) {
                $points -= $leavesCount * 2;
            } else if ($leavesCount <= 4) {
                $points -= 4 + (($leavesCount - 2) * 2.5);
            } else {
                $points -= min(7.5, 9 + (($leavesCount - 4) * 3));
            }
        }

        return max(round($points, 2), 0); // Ensure points don't go below 0
    }
}
