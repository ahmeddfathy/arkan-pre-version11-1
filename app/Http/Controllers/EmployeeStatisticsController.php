<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequests;
use App\Models\AttendanceRecord;
use App\Models\Attendance;
use App\Models\SpecialCase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmployeeStatisticsController extends Controller
{
    /**
     * Get attendance data from either AttendanceRecord or Attendance based on availability
     *
     * @param string $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getAttendanceData($employeeId, $startDate, $endDate)
    {

        $recordsCount = AttendanceRecord::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        // If no records exist in AttendanceRecord, use Attendance data
        if ($recordsCount == 0) {
            return $this->getAttendanceFromCheckInSystem($employeeId, $startDate, $endDate);
        } else {
            return AttendanceRecord::where('employee_id', $employeeId)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();
        }
    }

    /**
     * Get attendance data from the Attendance model (check-in/out system)
     *
     * @param string $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getAttendanceFromCheckInSystem($employeeId, $startDate, $endDate)
    {
        try {
            $attendanceData = Attendance::where('employee_id', $employeeId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            // Convert Attendance format to match AttendanceRecord format
            return $attendanceData->map(function($record) {
                // Map status from Attendance to AttendanceRecord format
                $status = 'غيــاب'; // default to absent
                if ($record->check_in) {
                    $status = 'حضـور'; // present if checked in
                }

                // Calculate working hours if both check-in and check-out exist
                $workingHours = null;
                if ($record->check_in && $record->check_out) {
                    $workingHours = $record->check_out->diffInHours($record->check_in);
                }

                // Format entry and exit times to string format if they exist
                $entryTime = $record->check_in ? $record->check_in->format('H:i:s') : null;
                $exitTime = $record->check_out ? $record->check_out->format('H:i:s') : null;

                // Get early arrival & late departure minutes using the helper methods
                $earlyArrivalMinutes = $record->getEarlyArrivalMinutes();
                $lateDepartureMinutes = $record->getLateDepartureMinutes();

                $attendanceRecord = new AttendanceRecord([
                    'attendance_date' => $record->date->format('Y-m-d'),
                    'status' => $status,
                    'entry_time' => $entryTime,
                    'exit_time' => $exitTime,
                    'delay_minutes' => $record->late_minutes,
                    'early_minutes' => $record->early_minutes,
                    'working_hours' => $workingHours,
                    'employee_id' => $record->employee_id,
                    'notes' => "تم إنشاؤه من نظام تسجيل الحضور المباشر" // Note that this was created from Attendance system
                ]);

                return $attendanceRecord;
            });
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching attendance data from check-in system: ' . $e->getMessage());

            // Return empty collection
            return collect([]);
        }
    }

    public function index(Request $request)
    {
        // تسجيل النشاط - دخول صفحة إحصائيات الموظفين
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'employee_statistics_index',
                    'filters' => $request->all(),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة إحصائيات الموظفين');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employeeQuery = User::query();

        // إضافة شرط لعرض المستخدمين النشطين فقط
        $employeeQuery->where('employee_status', 'active');

        if ($user->hasRole('hr')) {
            $employeeQuery->where(function ($query) use ($user) {
                $query->whereDoesntHave('roles', function ($q) {
                    $q->whereIn('name', ['hr', 'company_manager']);
                })
                    ->orWhere('id', $user->id); // Include the current HR user
            });

            $allUsers = User::where(function ($query) use ($user) {
                $query->whereDoesntHave('roles', function ($q) {
                    $q->whereIn('name', ['hr', 'company_manager']);
                })
                    ->orWhere('id', $user->id); // Include the current HR user
            })->where('employee_status', 'active')->get();
        } elseif ($user->hasRole(['department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager'])) {
            $managedTeams = $user->allTeams()->pluck('id');

            $employeeQuery->where(function ($query) use ($managedTeams, $user) {
                $query->whereHas('teams', function ($q) use ($managedTeams) {
                    $q->whereIn('teams.id', $managedTeams);
                })->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader']);
                })
                    ->orWhereHas('ownedTeams', function ($q) use ($managedTeams) {
                        $q->whereIn('id', $managedTeams);
                    })
                    ->orWhere('id', $user->id); // Include the current department manager
            });

            $allUsers = User::where(function ($query) use ($managedTeams, $user) {
                $query->whereHas('teams', function ($q) use ($managedTeams) {
                    $q->whereIn('teams.id', $managedTeams);
                })->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader']);
                })
                    ->orWhereHas('ownedTeams', function ($q) use ($managedTeams) {
                        $q->whereIn('id', $managedTeams);
                    })
                    ->orWhere('id', $user->id); // Include the current department manager
            })->where('employee_status', 'active')->get();
        } elseif ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'])) {
            if ($user->currentTeam) {
                $teamMembers = $user->currentTeam->users()
                    ->whereHas('roles', function ($q) {
                        $q->where('name', 'employee');
                    })
                    ->pluck('users.id');

                $employeeQuery->where(function ($query) use ($teamMembers, $user) {
                    $query->whereIn('id', $teamMembers)
                        ->orWhere('id', $user->id); // Include the current team leader
                });

                $allUsers = User::where(function ($query) use ($teamMembers, $user) {
                    $query->whereIn('id', $teamMembers)
                        ->orWhere('id', $user->id); // Include the current team leader
                })->where('employee_status', 'active')->get();
            } else {
                $employeeQuery->where('id', $user->id); // If no team, just show self
                $allUsers = collect([$user]);
            }
        } elseif ($user->hasRole('company_manager')) {
            $employeeQuery->where(function ($query) use ($user) {
                $query->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'hr');
                })
                    ->orWhere('id', $user->id); // Include the current company manager
            });

            $allUsers = User::where(function ($query) use ($user) {
                $query->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'hr');
                })
                    ->orWhere('id', $user->id); // Include the current company manager
            })->where('employee_status', 'active')->get();
        } else {
            $employeeQuery->where('id', $user->id);
            $allUsers = collect([$user]);
        }

        if ($request->has('department') && $request->department != '') {
            $employeeQuery->where('department', $request->department);
        }

        if ($request->has('search') && $request->search != '') {
            $employeeQuery->where(function ($q) use ($request) {
                $q->where('employee_id', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        $departments = [];
        if ($user->hasRole(['hr', 'company_manager', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager'])) {
            $departments = User::select('department')
                ->distinct()
                ->whereNotNull('department')
                ->pluck('department');
        }

        $now = now();
        $startDate = $request->start_date ?? ($now->day >= 26
            ? $now->copy()->startOfDay()->setDay(26)
            : $now->copy()->subMonth()->startOfDay()->setDay(26))->format('Y-m-d');

        $endDate = $request->end_date ?? ($now->day >= 26
            ? $now->copy()->addMonth()->startOfDay()->setDay(25)->endOfDay()
            : $now->copy()->startOfDay()->setDay(25)->endOfDay())->format('Y-m-d');

        // جمع إحصائيات كاملة للرسوم البيانية
        $statsQuery = $employeeQuery->get();

        // إجماليات للرسم البياني
        $totalStats = [
            'total_attendance_days' => 0,
            'total_absence_days' => 0,
            'total_permissions' => 0,
            'total_delays' => 0,
            'total_taken_leaves' => 0,
            'total_remaining_leaves' => 0,
            'departments_stats' => [],
        ];

        foreach ($statsQuery as $emp) {
            // حساب الحضور والغياب لجميع الموظفين
            $attendanceData = $this->getAttendanceData($emp->employee_id, $startDate, $endDate);

            $presentDays = $attendanceData->where('status', 'حضـور')->count();
            $absentDays = $attendanceData->where('status', 'غيــاب')->count();

            $totalStats['total_attendance_days'] += $presentDays;
            $totalStats['total_absence_days'] += $absentDays;

            // إحصاءات الإذن
            $permissions = PermissionRequest::where('user_id', $emp->id)
                ->where('status', 'approved')
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->count();
            $totalStats['total_permissions'] += $permissions;

            // إضافة إحصاءات حسب القسم
            if (!isset($totalStats['departments_stats'][$emp->department])) {
                $totalStats['departments_stats'][$emp->department] = [
                    'attendance' => 0,
                    'absence' => 0,
                    'permissions' => 0,
                    'count' => 0,
                ];
            }

            $totalStats['departments_stats'][$emp->department]['attendance'] += $presentDays;
            $totalStats['departments_stats'][$emp->department]['absence'] += $absentDays;
            $totalStats['departments_stats'][$emp->department]['permissions'] += $permissions;
            $totalStats['departments_stats'][$emp->department]['count']++;

            // إضافة الإجازات المأخوذة والمتبقية
            $takenLeaves = AbsenceRequest::where('user_id', $emp->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [
                    Carbon::parse($startDate)->startOfYear(),
                    Carbon::parse($endDate)->endOfYear()
                ])
                ->count();

            $remainingLeaves = $emp->getMaxAllowedAbsenceDays() - $takenLeaves;

            $totalStats['total_taken_leaves'] += $takenLeaves;
            $totalStats['total_remaining_leaves'] += $remainingLeaves;
        }

        // استمرار بـ paginate للعرض في الجدول
        $employees = $employeeQuery->orderBy('department')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        foreach ($employees as $employee) {
            if ($startDate && $endDate) {
                $approvedLeaves = AbsenceRequest::where('user_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereBetween('absence_date', [
                        Carbon::parse($startDate)->startOfYear(),
                        Carbon::parse($endDate)->endOfYear()
                    ])
                    ->get();

                $totalApprovedLeaves = $approvedLeaves->count();

                $approvedLeavesDates = [];
                if ($totalApprovedLeaves <= 21) {
                    $approvedLeavesDates = $approvedLeaves->pluck('absence_date')->toArray();
                } else {
                    $approvedLeavesDates = $approvedLeaves->take(21)->pluck('absence_date')->toArray();
                }

                $statsQuery = $this->getAttendanceData($employee->employee_id, $startDate, $endDate);

                // Get special cases for this period
                $specialCases = SpecialCase::where('employee_id', $employee->employee_id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get()
                    ->mapWithKeys(function ($case) {
                        return [Carbon::parse($case->date)->format('Y-m-d') => $case];
                    })
                    ->all();

                $totalWorkDays = $statsQuery->filter(function ($record) {
                    return $record->status === 'حضـور' || $record->status === 'غيــاب';
                })->count();

                // Get all attendance records for the period
                $attendanceRecords = $statsQuery;
                $actualAttendanceDays = 0;
                $totalDelayMinutes = 0;
                $totalWorkingHours = 0;
                $daysWithHours = 0;

                foreach ($attendanceRecords as $record) {
                    $date = Carbon::parse($record->attendance_date)->format('Y-m-d');

                    if (isset($specialCases[$date])) {
                        // If there's a special case, always count as present
                        $specialCase = $specialCases[$date];
                        $actualAttendanceDays++;
                        $daysWithHours++;

                        $totalDelayMinutes += $specialCase->late_minutes ?? 0;

                        if ($specialCase->check_in && $specialCase->check_out) {
                            $checkIn = Carbon::parse($specialCase->check_in);
                            $checkOut = Carbon::parse($specialCase->check_out);
                            $hours = $checkOut->diffInHours($checkIn);
                            $totalWorkingHours += $hours;
                        }
                    } else {
                        if ($record->status === 'حضـور' && $record->entry_time) {
                            $actualAttendanceDays++;
                            $totalDelayMinutes += $record->delay_minutes ?? 0;

                            if ($record->working_hours) {
                                $daysWithHours++;
                                $totalWorkingHours += $record->working_hours;
                            }
                        }
                    }
                }

                $employee->total_working_days = $totalWorkDays;
                $employee->actual_attendance_days = $actualAttendanceDays;

                // Calculate absences correctly
                $employee->absences = max(0, $totalWorkDays - $actualAttendanceDays);

                $employee->attendance_percentage = $totalWorkDays > 0
                    ? round(($actualAttendanceDays / $totalWorkDays) * 100, 1)
                    : 0;

                $employee->weekend_days = $statsQuery->filter(function ($record) {
                    return $record->status === 'عطله إسبوعية';
                })->count();

                $employee->delays = $totalDelayMinutes;

                $attendanceData = $this->getAttendanceData($employee->employee_id, $startDate, $endDate);

                $workingHoursRecords = $attendanceData;
                $totalWorkingHours = 0;

                foreach ($workingHoursRecords as $record) {
                    // استخدام القيمة الفعلية المخزنة في working_hours
                    $totalWorkingHours += $record->working_hours ?? 0;
                }

                $daysWithHours = $workingHoursRecords->where('working_hours', '>', 0)->count();
                $avgHours = $daysWithHours > 0 ? $totalWorkingHours / $daysWithHours : 0;
                $avgHoursRate = $avgHours / 8;
                $avgHoursRate = $avgHours / 8;

                $workingHoursScore = min(100, ($employee->attendance_percentage ?? 0) * $avgHoursRate * 100);

                // تخزين متوسط ساعات العمل في كائن الموظف للعرض في الإحصائيات
                $employee->average_working_hours = round($avgHours, 2);
            } else {
                $employee->total_working_days = 0;
                $employee->actual_attendance_days = 0;
                $employee->absences = 0;
                $employee->weekend_days = 0;
                $employee->delays = 0;
                $employee->average_working_hours = 0;
                $employee->attendance_percentage = 0;
            }

            $permissionQuery = PermissionRequest::where('user_id', $employee->id)
                ->where('status', 'approved');
            if ($startDate && $endDate) {
                $permissionQuery->whereBetween('departure_time', [$startDate, $endDate]);
            }
            $employee->permissions = $permissionQuery->count();

            $overtimeQuery = OverTimeRequests::where('user_id', $employee->id)
                ->where('status', 'approved');
            if ($startDate && $endDate) {
                $overtimeQuery->whereBetween('overtime_date', [$startDate, $endDate]);
            }
            $employee->overtimes = $overtimeQuery->count();

            $takenLeaves = AbsenceRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [
                    Carbon::parse($startDate)->startOfYear(),
                    Carbon::parse($endDate)->endOfYear()
                ])
                ->count();

            $employee->taken_leaves = $takenLeaves;
            $employee->remaining_leaves = $employee->getMaxAllowedAbsenceDays() - $takenLeaves;

            $currentMonthLeaves = AbsenceRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [
                    Carbon::parse($startDate)->day >= 26
                        ? Carbon::parse($startDate)->startOfDay()
                        : Carbon::parse($startDate)->subMonth()->startOfDay()->setDay(26),
                    Carbon::parse($endDate)->day >= 26
                        ? Carbon::parse($endDate)->addMonth()->startOfDay()->setDay(25)->endOfDay()
                        : Carbon::parse($endDate)->startOfDay()->setDay(25)->endOfDay()
                ])
                ->count();

            $employee->current_month_leaves = $currentMonthLeaves;


            $employee->performance_metrics = $this->calculatePerformanceMetrics($employee, $startDate, $endDate);
            $employee->performance_predictions = $this->predictFuturePerformance($employee);
        }

        return view('employee-statistics.index', compact(
            'employees',
            'startDate',
            'endDate',
            'departments',
            'allUsers',
            'totalStats'
        ));
    }

    public function getEmployeeDetails($employee_id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employee = User::where('employee_id', $employee_id)
                        ->where('employee_status', 'active')
                        ->firstOrFail();

        // تسجيل النشاط - عرض تفاصيل إحصائيات موظف
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($employee)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'target_employee_id' => $employee_id,
                    'target_employee_name' => $employee->name,
                    'target_employee_department' => $employee->department,
                    'start_date' => request('start_date'),
                    'end_date' => request('end_date'),
                    'action_type' => 'view_employee_statistics',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد إحصائيات الموظف');
        }

        $canViewEmployee = false;

        if ($user->hasRole('hr')) {
            $canViewEmployee = true;
        } elseif ($user->hasRole(['department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager'])) {
            $managedTeams = $user->allTeams()->pluck('id');
            $canViewEmployee = $employee->teams()
                ->whereIn('teams.id', $managedTeams)
                ->exists() ||
                $employee->ownedTeams()
                ->whereIn('id', $managedTeams)
                ->exists();
        } elseif ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'])) {
            $canViewEmployee = $user->currentTeam && $employee->teams()
                ->where('teams.id', $user->currentTeam->id)
                ->exists();
        } elseif ($user->hasRole('company_manager')) {
            $canViewEmployee = !$employee->hasRole('hr');
        } else {
            $canViewEmployee = $user->id === $employee->id;
        }

        // Always allow users to see their own statistics
        if ($user->employee_id === $employee_id) {
            $canViewEmployee = true;
        }

        if (!$canViewEmployee) {
            abort(403, 'غير مصرح لك بعرض بيانات هذا الموظف');
        }

        $startDate = request('start_date');
        $endDate = request('end_date');

        $statsQuery = $this->getAttendanceData($employee_id, $startDate, $endDate);

        // Get special cases for this period
        $specialCases = SpecialCase::where('employee_id', $employee_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->mapWithKeys(function ($case) {
                return [Carbon::parse($case->date)->format('Y-m-d') => $case];
            })
            ->all();

        // Get all attendance records
        $attendanceRecords = $statsQuery;

        $totalWorkDays = $attendanceRecords->filter(function ($record) {
            return $record->status === 'حضـور' || $record->status === 'غيــاب';
        })->count();

        $actualAttendanceDays = 0;
        $totalDelayMinutes = 0;
        $totalWorkingHours = 0;
        $daysWithHours = 0;

        foreach ($attendanceRecords as $record) {
            $date = Carbon::parse($record->attendance_date)->format('Y-m-d');

            if (isset($specialCases[$date])) {
                // Only count as present if there's an attendance record for this day
                if ($record->status === 'حضـور' || $record->status === 'غيــاب') {
                    $specialCase = $specialCases[$date];
                    $actualAttendanceDays++;
                    $daysWithHours++;

                    $totalDelayMinutes += $specialCase->late_minutes ?? 0;

                    if ($specialCase->check_in && $specialCase->check_out) {
                        $checkIn = Carbon::parse($specialCase->check_in);
                        $checkOut = Carbon::parse($specialCase->check_out);
                        $hours = $checkOut->diffInHours($checkIn);
                        $totalWorkingHours += $hours;
                    }
                }
            } else {
                if ($record->status === 'حضـور' && $record->entry_time) {
                    $actualAttendanceDays++;
                    $totalDelayMinutes += $record->delay_minutes ?? 0;

                    if ($record->working_hours) {
                        $daysWithHours++;
                        $totalWorkingHours += $record->working_hours;
                    }
                }
            }
        }

        $statistics = [
            'total_working_days' => $totalWorkDays,
            'actual_attendance_days' => $actualAttendanceDays,
            'absences' => $totalWorkDays - $actualAttendanceDays,
            'permissions' => PermissionRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->count(),
            'overtimes' => OverTimeRequests::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$startDate, $endDate])
                ->count(),
            'delays' => $totalDelayMinutes,
            'attendance' => $attendanceRecords->map(function ($record) use ($specialCases) {
                $date = Carbon::parse($record->attendance_date)->format('Y-m-d');
                if (isset($specialCases[$date])) {
                    $specialCase = $specialCases[$date];
                    return [
                        'attendance_date' => $date,
                        'status' => 'حضـور', // Always mark as present for special cases
                        'entry_time' => $specialCase->check_in,
                        'exit_time' => $specialCase->check_out,
                        'delay_minutes' => $specialCase->late_minutes,
                        'early_leave_minutes' => $specialCase->early_minutes,
                        'working_hours' => $specialCase->check_in && $specialCase->check_out ?
                            Carbon::parse($specialCase->check_out)->diffInHours(Carbon::parse($specialCase->check_in)) : null,
                        'is_special_case' => true,
                        'special_case_reason' => $specialCase->reason
                    ];
                }
                return $record;
            })
        ];

        $statistics['attendance_percentage'] = $statistics['total_working_days'] > 0
            ? round(($statistics['actual_attendance_days'] / $statistics['total_working_days']) * 100, 1)
            : 0;

        $takenLeaves = AbsenceRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [
                Carbon::parse($startDate)->startOfYear(),
                Carbon::parse($endDate)->endOfYear()
            ])
            ->count();

        $statistics['taken_leaves'] = $takenLeaves;
        $statistics['remaining_leaves'] = $employee->getMaxAllowedAbsenceDays() - $takenLeaves;

        $approvedLeavesDates = AbsenceRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [$startDate, $endDate])
            ->pluck('absence_date')
            ->toArray();

        // Recalculate absences excluding special cases and approved leaves
        $statistics['absences'] = $totalWorkDays - $actualAttendanceDays;

        $statistics['current_month_leaves'] = AbsenceRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [
                Carbon::parse($startDate)->day >= 26
                    ? Carbon::parse($startDate)->startOfDay()
                    : Carbon::parse($startDate)->subMonth()->startOfDay()->setDay(26),
                Carbon::parse($endDate)->day >= 26
                    ? Carbon::parse($endDate)->addMonth()->startOfDay()->setDay(25)->endOfDay()
                    : Carbon::parse($endDate)->startOfDay()->setDay(25)->endOfDay()
            ])
            ->count();

        $employeeData = $employee->only([
            'id',
            'name',
            'employee_id',
            'email',
            'phone_number',
            'position',
            'department',
            'date_of_birth',
            'hire_date',
            'employment_status',
            'profile_photo_url'
        ]);

        // Add max allowed absence days
        $employeeData['max_allowed_absence_days'] = $employee->getMaxAllowedAbsenceDays();

        return response()->json([
            'employee' => $employeeData,
            'statistics' => $statistics
        ]);
    }

    private function getAllowedRoles($user)
    {
        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'])) {
            return ['employee'];
        } elseif ($user->hasRole(['department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager'])) {
            return ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'];
        } elseif ($user->hasRole('company_manager')) {
            return ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager'];
        } elseif ($user->hasRole('project_manager')) {
            return ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager'];
        }
        return [];
    }

    private function getTeamMembers($team, $allowedRoles)
    {
        return $team->users()
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->pluck('users.id')
            ->toArray();
    }

    public function getAbsences($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            $approvedLeavesDates = AbsenceRequest::where('user_id', function ($query) use ($employee_id) {
                $query->select('id')
                    ->from('users')
                    ->where('employee_id', $employee_id)
                    ->first();
            })
                ->where('status', 'approved')
                ->whereBetween('absence_date', [$startDate, $endDate])
                ->pluck('absence_date')
                ->toArray();

            $absences = $this->getAttendanceData($employee_id, $startDate, $endDate)
                ->where('status', 'غيــاب')
                ->whereNotIn('attendance_date', $approvedLeavesDates)
                ->values(); // Re-index the collection after filtering

            return $absences->map(function ($record) {
                return [
                    'date' => $record->attendance_date,
                    'reason' => 'غياب',
                    'status' => 'غياب'
                ];
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage()], 500);
        }
    }

    public function getPermissions($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            $user = User::where('employee_id', $employee_id)->firstOrFail();

            $permissions = PermissionRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->orderBy('departure_time', 'desc')
                ->get();

            return $permissions->map(function ($record) {
                $departureTime = Carbon::parse($record->departure_time);
                $returnTime = Carbon::parse($record->return_time);
                $minutes = abs($returnTime->diffInMinutes($departureTime));

                return [
                    'date' => $departureTime->format('Y-m-d'),
                    'departure_time' => $departureTime->format('H:i'),
                    'return_time' => $returnTime->format('H:i'),
                    'minutes' => $minutes,
                    'reason' => $record->reason,
                    'status' => 'معتمد'
                ];
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getOvertimes($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            $user = User::where('employee_id', $employee_id)->firstOrFail();

            $overtimes = OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$startDate, $endDate])
                ->orderBy('overtime_date', 'desc')
                ->get();

            return $overtimes->map(function ($record) {
                $startTime = Carbon::parse($record->start_time);
                $endTime = Carbon::parse($record->end_time);
                $minutes = abs($endTime->diffInMinutes($startTime));

                return [
                    'date' => Carbon::parse($record->overtime_date)->format('Y-m-d'),
                    'start_time' => Carbon::parse($record->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($record->end_time)->format('H:i'),
                    'minutes' => $minutes,
                    'reason' => $record->reason,
                    'status' => 'معتمد'
                ];
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getLeaves($employee_id)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');

            $user = User::where('employee_id', $employee_id)->firstOrFail();

            $leaves = AbsenceRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('absence_date', [
                    Carbon::parse($startDate)->startOfYear(),
                    Carbon::parse($endDate)->endOfYear()
                ])
                ->orderBy('absence_date', 'desc')
                ->get();

            $formattedLeaves = $leaves->map(function ($record) {
                return [
                    'date' => $record->absence_date,
                    'reason' => $record->reason,
                    'status' => 'معتمد'
                ];
            });

            return response()->json($formattedLeaves);
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    public function getCurrentMonthLeaves($employee_id)
    {
        $startDate = request('start_date');
        $endDate = request('end_date');

        $user = User::where('employee_id', $employee_id)->firstOrFail();

        $leaves = AbsenceRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [
                Carbon::parse($startDate)->day >= 26
                    ? Carbon::parse($startDate)->startOfDay()
                    : Carbon::parse($startDate)->subMonth()->startOfDay()->setDay(26),
                Carbon::parse($endDate)->day >= 26
                    ? Carbon::parse($endDate)->addMonth()->startOfDay()->setDay(25)->endOfDay()
                    : Carbon::parse($endDate)->startOfDay()->setDay(25)->endOfDay()
            ])
            ->get();

        $formattedLeaves = $leaves->map(function ($record) {
            return [
                'date' => $record->absence_date,
                'reason' => $record->reason,
                'status' => 'معتمد'
            ];
        });

        return response()->json($formattedLeaves);
    }

    private function calculatePerformanceMetrics($employee, $startDate, $endDate)
    {
        // Default values in case we can't calculate metrics
        $defaultMetrics = [
            'attendance_score' => 0,
            'punctuality_score' => 0,
            'working_hours_score' => 0,
            'permissions_score' => 0,
            'overall_score' => 0,
            'trend' => 0,
            'performance_level' => 'غير محدد',
            'areas_for_improvement' => [],
            'delay_status' => [
                'minutes' => 0,
                'is_good' => true,
                'percentage' => 0
            ],
            'permissions_status' => [
                'minutes' => 0,
                'is_good' => true,
                'percentage' => 0
            ],
            // إضافة خاصية لإخفاء الخصومات عندما تكون القيم صفرًا
            'show_deductions' => false
        ];

        // If employee data is not properly set, return default metrics
        if (
            !isset($employee->attendance_percentage) && !isset($employee->delays) &&
            !isset($employee->total_working_days) && !isset($employee->actual_attendance_days)
        ) {
            return $defaultMetrics;
        }

        // إذا لم يكن هناك أيام عمل أو كانت نسبة الحضور صفر، نعيد القيم الافتراضية (كلها أصفار)
        if (($employee->total_working_days ?? 0) == 0 || ($employee->attendance_percentage ?? 0) == 0) {
            return $defaultMetrics;
        }

        $attendanceScore = min(100, ($employee->attendance_percentage ?? 0));

        // Calculate months between start and end date
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        $monthsDifference = max(1, $startDateObj->diffInMonths($endDateObj));

        // التعامل مع حالة الشهر الواحد المقسم على شهرين ميلاديين
        if ($monthsDifference < 1 && $startDateObj->format('m') != $endDateObj->format('m')) {
            $monthsDifference = 1;
        }

        // Scale maxAcceptableDelays based on the number of months
        $baseMaxAcceptableDelays = 120; // 120 minutes per month
        $maxAcceptableDelays = $baseMaxAcceptableDelays * $monthsDifference;

        // ضمان أن درجة الالتزام بالمواعيد تساوي صفراً إذا كانت نسبة الحضور صفر
        $punctualityScore = 0;
        if (($employee->attendance_percentage ?? 0) > 0) {
            $punctualityScore = 100;
            if (($employee->delays ?? 0) > $maxAcceptableDelays) {
                $excessDelays = ($employee->delays ?? 0) - $maxAcceptableDelays;
                $punctualityScore = max(0, 100 - (($excessDelays / $maxAcceptableDelays) * 100));
            }
        }

        // ضمان أن درجة ساعات العمل تساوي صفراً إذا كانت نسبة الحضور صفر
        $workingHoursScore = 0;

        if ((($employee->total_working_days ?? 0) > 0) && (($employee->attendance_percentage ?? 0) > 0)) {
            $attendanceRate = ($employee->actual_attendance_days ?? 0) / ($employee->total_working_days ?? 1);

            $attendanceData = $this->getAttendanceData($employee->employee_id, $startDate, $endDate);

            $workingHoursRecords = $attendanceData;
            $totalWorkingHours = 0;

            foreach ($workingHoursRecords as $record) {
                // استخدام القيمة الفعلية المخزنة في working_hours
                $totalWorkingHours += $record->working_hours ?? 0;
            }

            $daysWithHours = $workingHoursRecords->where('working_hours', '>', 0)->count();
            $avgHours = $daysWithHours > 0 ? $totalWorkingHours / $daysWithHours : 0;
            $avgHoursRate = $avgHours / 8;

            $workingHoursScore = min(100, ($attendanceRate * $avgHoursRate * 100));

            // تخزين متوسط ساعات العمل في كائن الموظف للعرض في الإحصائيات
            $employee->average_working_hours = round($avgHours, 2);
        }

        // Scale maxAcceptablePermissions based on the number of months as well
        $baseMaxAcceptablePermissions = 180; // 180 minutes per month
        $maxAcceptablePermissions = $baseMaxAcceptablePermissions * $monthsDifference;

        // درجة الأذونات دائماً 100% إلا إذا تجاوز الحد المسموح
        $permissionsScore = 100;

        if (($employee->permissions ?? 0) > $maxAcceptablePermissions) {
            $excessPermissions = ($employee->permissions ?? 0) - $maxAcceptablePermissions;
            $permissionsScore = max(0, 100 - (($excessPermissions / $maxAcceptablePermissions) * 100));
        }

        // حساب الدرجة الكلية
        $overallScore = round(($attendanceScore * 0.45) + ($punctualityScore * 0.2) + ($workingHoursScore * 0.35), 1);

        $previousPeriodScore = $this->calculatePreviousPeriodScore($employee, $startDate);
        $trend = $overallScore - $previousPeriodScore;

        // إنشاء مصفوفة الخصومات
        $deductions = [];
        if ($attendanceScore < 100) {
            $deductions['attendance'] = [
                'percentage' => round(100 - $attendanceScore, 1),
                'weight' => 45,
                'deduction' => round((100 - $attendanceScore) * 0.45, 1),
                'absences' => $employee->absences ?? 0
            ];
        }

        if ($punctualityScore < 100) {
            $deductions['punctuality'] = [
                'percentage' => round(100 - $punctualityScore, 1),
                'weight' => 20,
                'deduction' => round((100 - $punctualityScore) * 0.2, 1),
                'delay_minutes' => $employee->delays ?? 0,
                'max_acceptable_delays' => $displayMaxAcceptableDelays ?? round($maxAcceptableDelays),
                'excess_delay' => max(0, ($employee->delays ?? 0) - $maxAcceptableDelays)
            ];
        }

        if ($workingHoursScore < 100) {
            $deductions['working_hours'] = [
                'percentage' => round(100 - $workingHoursScore, 1),
                'weight' => 35,
                'deduction' => round((100 - $workingHoursScore) * 0.35, 1),
                'average_hours' => $employee->average_working_hours ?? 0,
                'required_hours' => 8,
                'hours_percentage' => round(($employee->average_working_hours ?? 0) / 8 * 100, 1)
            ];
        }

        return [
            'attendance_score' => round($attendanceScore, 1),
            'punctuality_score' => round($punctualityScore, 1),
            'working_hours_score' => round($workingHoursScore, 1),
            'permissions_score' => round($permissionsScore, 1),
            'overall_score' => $overallScore,
            'trend' => round($trend, 1),
            'performance_level' => $this->getPerformanceLevel($overallScore),
            'areas_for_improvement' => $this->getAreasForImprovement($attendanceScore, $punctualityScore, $workingHoursScore, $permissionsScore),
            'delay_status' => [
                'minutes' => $employee->delays ?? 0,
                'is_good' => ($employee->delays ?? 0) <= $maxAcceptableDelays,
                'percentage' => min(100, (($employee->delays ?? 0) / $maxAcceptableDelays) * 100)
            ],
            'permissions_status' => [
                'minutes' => $employee->permissions ?? 0,
                'is_good' => ($employee->permissions ?? 0) <= $maxAcceptablePermissions,
                'percentage' => min(100, (($employee->permissions ?? 0) / $maxAcceptablePermissions) * 100)
            ],
            'show_deductions' => $overallScore > 0,
            'deductions' => $deductions
        ];
    }

    private function calculatePreviousPeriodScore($employee, $startDate)
    {
        $currentPeriodStart = Carbon::parse($startDate);

        $previousStart = Carbon::parse($startDate)->subMonth();
        $previousEnd = Carbon::parse($startDate)->subDay();

        $employee->comparison_periods = [
            'current_period' => [
                'start' => $currentPeriodStart->format('Y-m-d'),
                'end' => Carbon::parse($startDate)->addMonth()->subDay()->format('Y-m-d'),
                'label' => 'من ' . $currentPeriodStart->format('Y-m-d') . ' إلى ' . Carbon::parse($startDate)->addMonth()->subDay()->format('Y-m-d')
            ],
            'previous_period' => [
                'start' => $previousStart->format('Y-m-d'),
                'end' => $previousEnd->format('Y-m-d'),
                'label' => 'من ' . $previousStart->format('Y-m-d') . ' إلى ' . $previousEnd->format('Y-m-d')
            ]
        ];


        $specialCases = SpecialCase::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$previousStart, $previousEnd])
            ->get()
            ->mapWithKeys(function ($case) {
                return [Carbon::parse($case->date)->format('Y-m-d') => $case];
            })
            ->all();

        $previousStats = $this->getAttendanceData($employee->employee_id, $previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d'));

        $totalDays = $previousStats->filter(function ($record) {
            return $record->status === 'حضـور' || $record->status === 'غيــاب';
        })->count();

        $presentDays = 0;
        $totalDelayMinutes = 0;
        $totalWorkingHours = 0;
        $daysWithHours = 0;

        foreach ($previousStats as $record) {
            $date = Carbon::parse($record->attendance_date)->format('Y-m-d');

            if (isset($specialCases[$date])) {
                $specialCase = $specialCases[$date];

                if ($record->status === 'حضـور' || $record->status === 'غيــاب') {
                    $presentDays++;
                    $daysWithHours++;

                    $totalDelayMinutes += $specialCase->late_minutes ?? 0;

                    if ($specialCase->check_in && $specialCase->check_out) {
                        $checkIn = Carbon::parse($specialCase->check_in);
                        $checkOut = Carbon::parse($specialCase->check_out);
                        $hours = $checkOut->diffInHours($checkIn);
                        $totalWorkingHours += $hours;
                    }
                }
            } else {
                if ($record->status === 'حضـور' && $record->entry_time) {
                    $presentDays++;
                    $totalDelayMinutes += $record->delay_minutes ?? 0;

                    if ($record->working_hours) {
                        $daysWithHours++;
                        $totalWorkingHours += $record->working_hours;
                    }
                }
            }
        }


        if ($totalDays == 0 || $presentDays == 0) {
            $employee->previous_scores = [
                'attendance_score' => 0,
                'punctuality_score' => 0,
                'working_hours_score' => 0,
                'permissions_score' => 0
            ];

            $employee->previous_period_stats = [
                'total_working_days' => $totalDays,
                'actual_attendance_days' => $presentDays,
                'attendance_percentage' => 0,
                'delays' => $totalDelayMinutes,
                'average_working_hours' => 0
            ];

            $this->calculateTotalStatistics($employee);

            return 0;
        }

        $prevAttendanceScore = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;



        $monthsDifference = max(1, $previousStart->diffInMonths($previousEnd));

        if ($monthsDifference < 1 && $previousStart->format('m') != $previousEnd->format('m')) {
            $monthsDifference = 1;
        }


        $baseMaxAcceptableDelays = 120;
        $maxAcceptableDelays = $baseMaxAcceptableDelays * $monthsDifference;

        $prevPunctualityScore = 0;
        if ($prevAttendanceScore > 0) {
            $prevPunctualityScore = 100;
            if ($totalDelayMinutes > $maxAcceptableDelays) {
                $excessDelays = $totalDelayMinutes - $maxAcceptableDelays;
                $prevPunctualityScore = max(0, 100 - (($excessDelays / $maxAcceptableDelays) * 100));
            }
        }

        $prevWorkingHoursScore = 0;
        if ($totalDays > 0 && $prevAttendanceScore > 0) {
            $attendanceRate = $presentDays / $totalDays;
            $daysWithHours = $previousStats->where('working_hours', '>', 0)->count();
            $avgHours = $daysWithHours > 0 ? $totalWorkingHours / $daysWithHours : 0;
            $avgHoursRate = $avgHours / 8;
            $prevWorkingHoursScore = min(100, ($attendanceRate * $avgHoursRate * 100));
        }


        $baseMaxAcceptablePermissions = 180;
        $maxAcceptablePermissions = $baseMaxAcceptablePermissions * $monthsDifference;

        $prevPermissions = PermissionRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('departure_time', [$previousStart, $previousEnd])
            ->count();
        $prevPermissionsScore = 100;
        if ($prevPermissions > $maxAcceptablePermissions) {
            $excessPermissions = $prevPermissions - $maxAcceptablePermissions;
            $prevPermissionsScore = max(0, 100 - (($excessPermissions / $maxAcceptablePermissions) * 100));
        }

        $employee->previous_scores = [
            'attendance_score' => round($prevAttendanceScore, 1),
            'punctuality_score' => round($prevPunctualityScore, 1),
            'working_hours_score' => round($prevWorkingHoursScore, 1),
            'permissions_score' => round($prevPermissionsScore, 1)
        ];

        $employee->previous_period_stats = [
            'total_working_days' => $totalDays,
            'actual_attendance_days' => $presentDays,
            'attendance_percentage' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0,
            'delays' => $totalDelayMinutes,
            'average_working_hours' => $daysWithHours > 0 ? round($totalWorkingHours / $daysWithHours, 2) : 0
        ];

        $this->calculateTotalStatistics($employee);

        return round(($prevAttendanceScore * 0.45) + ($prevPunctualityScore * 0.2) + ($prevWorkingHoursScore * 0.35), 1);
    }

    private function calculateTotalStatistics($employee)
    {
        $totalWorkingDays = ($employee->total_working_days ?? 0) +
            ($employee->previous_period_stats['total_working_days'] ?? 0);

        $totalAttendanceDays = ($employee->actual_attendance_days ?? 0) +
            ($employee->previous_period_stats['actual_attendance_days'] ?? 0);

        $totalDelays = ($employee->delays ?? 0) +
            ($employee->previous_period_stats['delays'] ?? 0);

        $currentWorkingHours = ($employee->average_working_hours ?? 0) * ($employee->actual_attendance_days ?? 0);
        $previousWorkingHours = ($employee->previous_period_stats['average_working_hours'] ?? 0) *
            ($employee->previous_period_stats['actual_attendance_days'] ?? 0);

        $totalHours = $currentWorkingHours + $previousWorkingHours;
        $totalDays = ($employee->actual_attendance_days ?? 0) +
            ($employee->previous_period_stats['actual_attendance_days'] ?? 0);

        $averageWorkingHours = $totalDays > 0 ? $totalHours / $totalDays : 0;

        $totalAttendancePercentage = $totalWorkingDays > 0 ?
            ($totalAttendanceDays / $totalWorkingDays) * 100 : 0;

        $employee->total_periods_stats = [
            'total_working_days' => $totalWorkingDays,
            'total_attendance_days' => $totalAttendanceDays,
            'total_attendance_percentage' => round($totalAttendancePercentage, 1),
            'total_delays' => $totalDelays,
            'average_working_hours' => round($averageWorkingHours, 2)
        ];
    }

    private function getPerformanceLevel($score)
    {
        if ($score >= 90) return 'ممتاز';
        if ($score >= 80) return 'جيد جداً';
        if ($score >= 70) return 'جيد';
        if ($score >= 60) return 'مقبول';
        return 'يحتاج إلى تحسين';
    }

    private function getAreasForImprovement($attendanceScore, $punctualityScore, $workingHoursScore, $permissionsScore)
    {
        // إذا كانت جميع القيم صفرًا (لا توجد بيانات كافية)، نعيد مصفوفة فارغة
        if ($attendanceScore == 0 && $punctualityScore == 0 && $workingHoursScore == 0) {
            return [];
        }

        $areas = [];

        if ($attendanceScore < 80) {
            $areas[] = 'تحسين نسبة الحضور';
        }
        if ($punctualityScore < 80) {
            $areas[] = 'الالتزام بمواعيد الحضور';
        }
        if ($workingHoursScore < 80) {
            $areas[] = 'زيادة ساعات العمل الفعلية';
        }
        if ($permissionsScore < 80) {
            $areas[] = 'تقليل عدد الأذونات';
        }

        return $areas;
    }

    private function predictFuturePerformance($employee)
    {
        // Check if performance metrics exist
        if (!isset($employee->performance_metrics) || empty($employee->performance_metrics)) {
            // Return default predictions if no metrics are available
            return [
                'predicted_attendance' => 0,
                'trend_direction' => 'ثابت',
                'trend_percentage' => 0,
                'improvement_percentage' => 0,
                'current_period' => ['label' => 'غير محدد'],
                'previous_period' => ['label' => 'غير محدد'],
                'prediction_period' => ['label' => 'غير محدد'],
                'current_score' => 0,
                'previous_score' => 0,
                'recommendations' => ['غير متوفر'],
                'metric_predictions' => [
                    'attendance' => ['current' => 0, 'previous' => 0, 'predicted' => 0, 'improvement' => 0, 'improvement_percentage' => 0],
                    'punctuality' => ['current' => 0, 'previous' => 0, 'predicted' => 0, 'improvement' => 0, 'improvement_percentage' => 0],
                    'working_hours' => ['current' => 0, 'previous' => 0, 'predicted' => 0, 'improvement' => 0, 'improvement_percentage' => 0],
                    'permissions' => ['current' => 0, 'previous' => 0, 'predicted' => 0, 'improvement' => 0, 'improvement_percentage' => 0],
                    'summary' => ['based_on_periods' => ['current' => 'غير محدد', 'previous' => 'غير محدد'], 'prediction_for' => 'غير محدد']
                ],
                'periods_details' => []
            ];
        }

        $currentMetrics = $employee->performance_metrics;
        $currentScore = $currentMetrics['overall_score'] ?? 0;

        // Extract start date from comparison_periods if available, otherwise use current date
        $startDate = isset($employee->comparison_periods['current_period']['start'])
            ? $employee->comparison_periods['current_period']['start']
            : now()->format('Y-m-d');

        $prevOverallScore = isset($employee->previous_scores) ?
            (($employee->previous_scores['attendance_score'] * 0.45) +
                ($employee->previous_scores['punctuality_score'] * 0.2) +
                ($employee->previous_scores['working_hours_score'] * 0.35)) :
            $this->calculatePreviousPeriodScore($employee, $startDate);

        $trend = $currentScore - $prevOverallScore;

        $improvementPercentage = 0;
        if ($prevOverallScore > 0) {
            $improvementPercentage = round((($currentScore - $prevOverallScore) / $prevOverallScore) * 100, 1);
        } elseif ($currentScore > 0) {
            $improvementPercentage = 100;
        }

        $predictedScore = $currentScore;

        if ($trend > 0) {
            // If improving, allow moderate improvement
            $predictedImprovement = min(5, $trend * 0.3);
            $predictedScore = min(100, $currentScore + $predictedImprovement);
        } else if ($trend < 0) {
            // If declining, predict continued decline but at a slower rate
            $predictedDecline = $trend * 0.5; // Use 50% of the current decline rate
            $predictedScore = max(0, $currentScore + $predictedDecline); // Remove minimum limit of 50
        }

        $predictedScore = round(min(100, max(0, $predictedScore)), 1);

        $periodsDetails = [
            'current_period' => [
                'start' => null,
                'end' => null,
                'label' => 'غير محدد'
            ],
            'previous_period' => [
                'start' => null,
                'end' => null,
                'label' => 'غير محدد'
            ]
        ];

        if (isset($employee->comparison_periods)) {
            $periodsDetails = $employee->comparison_periods;
        } else {
            $now = now();
            $currentPeriodStart = $now->day >= 26
                ? $now->copy()->startOfDay()->setDay(26)
                : $now->copy()->subMonth()->startOfDay()->setDay(26);

            $currentPeriodEnd = $now->day >= 26
                ? $now->copy()->addMonth()->startOfDay()->setDay(25)->endOfDay()
                : $now->copy()->startOfDay()->setDay(25)->endOfDay();

            $previousPeriodStart = $currentPeriodStart->copy()->subMonth();
            $previousPeriodEnd = $currentPeriodEnd->copy()->subMonth();

            $periodsDetails = [
                'current_period' => [
                    'start' => $currentPeriodStart->format('Y-m-d'),
                    'end' => $currentPeriodEnd->format('Y-m-d'),
                    'label' => 'من ' . $currentPeriodStart->format('Y-m-d') . ' إلى ' . $currentPeriodEnd->format('Y-m-d')
                ],
                'previous_period' => [
                    'start' => $previousPeriodStart->format('Y-m-d'),
                    'end' => $previousPeriodEnd->format('Y-m-d'),
                    'label' => 'من ' . $previousPeriodStart->format('Y-m-d') . ' إلى ' . $previousPeriodEnd->format('Y-m-d')
                ]
            ];
        }

        $currentEndDate = isset($periodsDetails['current_period']['end']) ?
            Carbon::parse($periodsDetails['current_period']['end']) : Carbon::now();
        $predictionStart = $currentEndDate->copy()->addDay();
        $predictionEnd = $currentEndDate->copy()->addMonth();

        $periodsDetails['prediction_period'] = [
            'start' => $predictionStart->format('Y-m-d'),
            'end' => $predictionEnd->format('Y-m-d'),
            'label' => 'من ' . $predictionStart->format('Y-m-d') . ' إلى ' . $predictionEnd->format('Y-m-d')
        ];

        $prevAttendanceScore = isset($employee->previous_scores) ?
            $employee->previous_scores['attendance_score'] : 0;
        $prevPunctualityScore = isset($employee->previous_scores) ?
            $employee->previous_scores['punctuality_score'] : 0;
        $prevWorkingHoursScore = isset($employee->previous_scores) ?
            $employee->previous_scores['working_hours_score'] : 0;

        $predictions = [
            'attendance' => [
                'current' => $currentMetrics['attendance_score'] ?? 0,
                'previous' => $prevAttendanceScore,
                'predicted' => min(100, max(0, $this->calculateMetricPrediction(
                    $currentMetrics['attendance_score'] ?? 0,
                    $prevAttendanceScore
                ))),
                'improvement' => round(($currentMetrics['attendance_score'] ?? 0) - $prevAttendanceScore, 1),
                'improvement_percentage' => round(($currentMetrics['attendance_score'] ?? 0) - $prevAttendanceScore, 1)
            ],
            'punctuality' => [
                'current' => $currentMetrics['punctuality_score'] ?? 0,
                'previous' => $prevPunctualityScore,
                'predicted' => min(100, max(0, $this->calculateMetricPrediction(
                    $currentMetrics['punctuality_score'] ?? 0,
                    $prevPunctualityScore
                ))),
                'improvement' => round(($currentMetrics['punctuality_score'] ?? 0) - $prevPunctualityScore, 1),
                'improvement_percentage' => round(($currentMetrics['punctuality_score'] ?? 0) - $prevPunctualityScore, 1)
            ],
            'working_hours' => [
                'current' => $currentMetrics['working_hours_score'] ?? 0,
                'previous' => $prevWorkingHoursScore,
                'predicted' => min(100, max(0, $this->calculateMetricPrediction(
                    $currentMetrics['working_hours_score'] ?? 0,
                    $prevWorkingHoursScore
                ))),
                'improvement' => round(($currentMetrics['working_hours_score'] ?? 0) - $prevWorkingHoursScore, 1),
                'improvement_percentage' => round(($currentMetrics['working_hours_score'] ?? 0) - $prevWorkingHoursScore, 1)
            ],
            'permissions' => [
                'current' => $currentMetrics['permissions_score'] ?? 0,
                'previous' => 100,
                'predicted' => 100,
                'improvement' => 0,
                'improvement_percentage' => 0
            ],
            'summary' => [
                'based_on_periods' => [
                    'current' => $periodsDetails['current_period']['label'],
                    'previous' => $periodsDetails['previous_period']['label']
                ],
                'prediction_for' => $periodsDetails['prediction_period']['label'],
                'calculation_method' => [
                    'description' => 'تم حساب التنبؤ بناءً على تحليل الفرق في الأداء بين الفترة الحالية والفترة السابقة، ' .
                        'واستخدام هذا الاتجاه لتوقع الأداء المستقبلي مع مراعاة معدل تحسن أو تراجع أبطأ.'
                ],
                'current_vs_previous' => [
                    'current_score' => $currentScore,
                    'previous_score' => round($prevOverallScore, 1),
                    'difference' => round($trend, 1),
                    'percentage_change' => $improvementPercentage
                ]
            ]
        ];

        return [
            'predicted_attendance' => $predictedScore,
            'trend_direction' => $trend > 0 ? 'تحسن' : ($trend < 0 ? 'تراجع' : 'ثابت'),
            'trend_percentage' => round(abs($currentScore - $prevOverallScore), 1),
            'improvement_percentage' => round($currentScore - $prevOverallScore, 1),
            'current_period' => $periodsDetails['current_period'],
            'previous_period' => $periodsDetails['previous_period'],
            'prediction_period' => $periodsDetails['prediction_period'],
            'current_score' => $currentScore,
            'previous_score' => round($prevOverallScore, 1),
            'recommendations' => $this->getRecommendations($predictedScore, $trend, $predictions, $currentScore),
            'metric_predictions' => $predictions,
            'periods_details' => $periodsDetails
        ];
    }

    private function calculateMetricPrediction($currentScore, $previousScore)
    {
        $trend = $currentScore - $previousScore;

        if ($trend > 0) {
            $predictedChange = min(5, $trend * 0.25);
            return min(100, $currentScore + $predictedChange);
        } else if ($trend < 0) {
            if ($currentScore >= 90) {
                $predictedChange = max(-2, $trend * 0.15);
            } else {
                $predictedChange = max(-4, $trend * 0.25);
            }
            return max(50, $currentScore + $predictedChange);
        }

        return $currentScore;
    }

    private function getRecommendations($predictedScore, $trend, $predictions, $currentScore)
    {
        $recommendations = [];

        if ($currentScore >= 90) {
            if ($trend >= 0) {
                return ['الحفاظ على مستوى الأداء الممتاز'];
            } else {
                return ['الحفاظ على مستوى الأداء الممتاز والانتباه لعدم التراجع'];
            }
        }

        foreach ($predictions as $metric => $data) {
            // Skip 'summary' key which doesn't have the 'predicted' field
            if ($metric === 'summary') {
                continue;
            }

            // Ensure the predicted key exists
            $predictedValue = $data['predicted'] ?? 0;
            $currentValue = $data['current'] ?? 0;

            if ($predictedValue < 80 || $currentValue < 80) {
                switch ($metric) {
                    case 'attendance':
                        $recommendations[] = 'تحسين نسبة الحضور';
                        break;
                    case 'punctuality':
                        $recommendations[] = 'الالتزام بمواعيد الحضور';
                        break;
                    case 'working_hours':
                        $recommendations[] = 'زيادة ساعات العمل الفعلية';
                        break;
                    case 'permissions':
                        $recommendations[] = 'تقليل عدد الأذونات';
                        break;
                }
            }
        }

        if ($predictedScore < 70) {
            $recommendations[] = 'يحتاج إلى متابعة مباشرة وخطة تحسين عاجلة';
        } elseif ($predictedScore < 85 && $predictedScore >= 70) {
            $recommendations[] = 'يحتاج إلى تحسين في بعض جوانب الأداء';
        }

        if ($trend < -5) {
            $recommendations[] = 'مراجعة أسباب تراجع الأداء ووضع خطة تصحيحية';
        }

        return $recommendations ?: ['الحفاظ على مستوى الأداء الحالي'];
    }
}
