<?php

namespace App\Http\Controllers;

use App\Models\User;

use App\Models\SalarySheet;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequests;
use App\Models\Violation;
use App\Models\AttendanceRecord;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\AttendanceReportService;
use App\Services\Auth\RoleCheckService;

class DashboardController extends Controller
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;
    }
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

                $attendanceRecord = new AttendanceRecord([
                    'attendance_date' => $record->date->format('Y-m-d'),
                    'status' => $status,
                    'entry_time' => $entryTime,
                    'exit_time' => $exitTime,
                    'delay_minutes' => $record->late_minutes,
                    'early_minutes' => $record->early_minutes,
                    'working_hours' => $workingHours,
                    'employee_id' => $record->employee_id,
                    'penalty' => 0, // Default penalty
                    'notes' => "تم إنشاؤه من نظام تسجيل الحضور المباشر"
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

    public function index()
    {
        $user = Auth::user();
        $isHR = $this->roleCheckService->userHasRole('hr');

        // تسجيل نشاط دخول لوحة التحكم
        activity()
            ->causedBy($user)
            ->withProperties([
                'action_type' => 'view_dashboard',
                'page' => $isHR ? 'hr_dashboard' : 'user_dashboard',
                'user_role' => $user->role,
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ])
            ->log($isHR ? 'دخل على لوحة تحكم الموارد البشرية' : 'دخل على لوحة التحكم الشخصية');

        $attendanceStats = [
            'present_days' => 0,
            'absent_days' => 0,
            'violation_days' => 0,
            'late_days' => 0,
            'total_delay_minutes' => 0,
            'avg_delay_minutes' => 0,
            'max_delay_minutes' => 0
        ];

        $now = now();
        $startDate = $now->copy()->subMonth()->setDay(26)->startOfDay();
        $endDate = $now->copy()->setDay(25)->endOfDay();

        $attendanceStats['period'] = [
            'month' => $now->translatedFormat('F'),
            'year' => $now->year
        ];

        $salaryFiles = SalarySheet::where('employee_id', $user->employee_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // استخدام النظام البديل للحضور (يستخدم Attendance إذا لم يكن هناك AttendanceRecord)
        $attendanceRecords = $this->getAttendanceData($user->employee_id, $startDate, $endDate);

        $totalWorkDays = $attendanceRecords->filter(function ($record) {
            return $record->status === 'حضـور' || $record->status === 'غيــاب';
        })->count();

        $attendanceStats['present_days'] = $attendanceRecords->filter(function ($record) {
            return $record->status === 'حضـور' && $record->entry_time !== null;
        })->count();

        $attendanceStats['total_work_days'] = $totalWorkDays;

        $attendanceStats['absent_days'] = $attendanceRecords->filter(function ($record) {
            return $record->status === 'غيــاب';
        })->count();

        $attendanceStats['violation_days'] = $attendanceRecords->filter(function ($record) {
            return ($record->penalty ?? 0) > 0;
        })->count();

        $lateRecords = $attendanceRecords->filter(function ($record) {
            return ($record->delay_minutes ?? 0) > 0 && $record->entry_time !== null;
        });

        $attendanceStats['late_days'] = $lateRecords->count();
        $attendanceStats['total_delay_minutes'] = $lateRecords->sum('delay_minutes');
        $attendanceStats['avg_delay_minutes'] = $lateRecords->count() > 0
            ? round($lateRecords->average('delay_minutes'), 1)
            : 0;
        $attendanceStats['max_delay_minutes'] = $lateRecords->max('delay_minutes') ?? 0;

        if ($isHR) {
            // استخدام النظام البديل لإحصائيات اليوم
            $today = Carbon::today();

            // التحقق من وجود بيانات في AttendanceRecord لليوم
            $todayRecordsCount = AttendanceRecord::whereDate('attendance_date', $today)->count();

            if ($todayRecordsCount == 0) {
                // استخدام Attendance كبديل
                $todayAttendance = Attendance::where('date', $today->format('Y-m-d'))->get();

                $presentToday = $todayAttendance->filter(function($record) {
                    return $record->check_in !== null;
                })->count();

                $absentToday = $todayAttendance->filter(function($record) {
                    return $record->check_in === null;
                })->count();

                $lateToday = $todayAttendance->filter(function($record) {
                    return ($record->late_minutes ?? 0) > 0;
                })->count();

                // الحضور المتأخر - الموظفين اللي حضروا ومتأخرين
                $presentLateToday = $todayAttendance->filter(function($record) {
                    return $record->check_in !== null && ($record->late_minutes ?? 0) > 0;
                })->count();

                // الحضور في الوقت - الموظفين اللي حضروا وماتأخروش
                $presentOnTimeToday = $todayAttendance->filter(function($record) {
                    return $record->check_in !== null && ($record->late_minutes ?? 0) == 0;
                })->count();
            } else {
                // استخدام AttendanceRecord
                $presentToday = AttendanceRecord::whereDate('attendance_date', $today)
                    ->where('status', 'حضـور')
                    ->count();
                $absentToday = AttendanceRecord::whereDate('attendance_date', $today)
                    ->where('status', 'غيــاب')
                    ->count();
                $lateToday = AttendanceRecord::whereDate('attendance_date', $today)
                    ->where('delay_minutes', '>', 0)
                    ->count();

                // الحضور المتأخر - الموظفين اللي حضروا ومتأخرين
                $presentLateToday = AttendanceRecord::whereDate('attendance_date', $today)
                    ->where('status', 'حضـور')
                    ->where('delay_minutes', '>', 0)
                    ->count();

                // الحضور في الوقت - الموظفين اللي حضروا وماتأخروش
                $presentOnTimeToday = AttendanceRecord::whereDate('attendance_date', $today)
                    ->where('status', 'حضـور')
                    ->where(function($query) {
                        $query->where('delay_minutes', '=', 0)
                              ->orWhereNull('delay_minutes');
                    })
                    ->count();
            }

            $todayStats = [
                'totalEmployees' => User::where('role', 'employee')->where('employee_status', 'active')->count(),
                'presentToday' => $presentToday,
                'presentOnTimeToday' => $presentOnTimeToday,
                'presentLateToday' => $presentLateToday,
                'absentToday' => $absentToday,
                'lateToday' => $lateToday
            ];

            $todayRequests = [
                'absenceRequests' => AbsenceRequest::whereDate('absence_date', Carbon::today())->count(),
                'permissionRequests' => PermissionRequest::whereDate('created_at', Carbon::today())->count(),
                'overtimeRequests' => OverTimeRequests::whereDate('overtime_date', Carbon::today())->count(),
                'violations' => Violation::whereDate('created_at', Carbon::today())->count()
            ];

            return view('dashboard', compact('todayStats', 'todayRequests', 'attendanceStats'));
        }

        return view('profile.dashboard-user', compact('attendanceStats', 'salaryFiles', 'startDate', 'endDate'));
    }

    public function previewAttendance($employee_id, AttendanceReportService $reportService)
    {
        $user = Auth::user();
        if (!$employee_id || $user->employee_id != $employee_id) {
            abort(403, 'غير مصرح بالوصول');
        }

        $attendanceStats = [
            'present_days' => 0,
            'absent_days' => 0,
            'violation_days' => 0,
            'late_days' => 0,
            'total_delay_minutes' => 0,
            'avg_delay_minutes' => 0,
            'max_delay_minutes' => 0,
            'total_work_days' => 0,
            'period' => []
        ];

        $now = now();

        // تحديد الفترة الزمنية
        if (request('start_date') && request('end_date')) {
            $startDate = Carbon::parse(request('start_date'))->startOfDay();
            $endDate = Carbon::parse(request('end_date'))->endOfDay();

            $attendanceStats['period'] = [
                'month' => $startDate->translatedFormat('F'),
                'year' => $startDate->year
            ];
        } elseif (request('month')) {
            $year = request('year') ?: $now->year;
            $startDate = Carbon::create($year, request('month'), 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $attendanceStats['period'] = [
                'month' => $startDate->translatedFormat('F'),
                'year' => $year
            ];
        } elseif (request('year')) {
            $startDate = Carbon::create(request('year'), 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();

            $attendanceStats['period'] = [
                'month' => $startDate->translatedFormat('F'),
                'year' => request('year')
            ];
        } else {
            $startDate = $now->copy()->subMonth()->setDay(26)->startOfDay();
            $endDate = $now->copy()->setDay(25)->endOfDay();

            $attendanceStats['period'] = [
                'month' => $startDate->translatedFormat('F'),
                'year' => $startDate->year
            ];
        }

        // استخدام النظام البديل للحضور
        $statsRecords = $this->getAttendanceData($employee_id, $startDate, $endDate);

        // تطبيق فلتر الحالة إذا وجد
        if (request('status')) {
            $statsRecords = $statsRecords->filter(function ($record) {
                return $record->status === request('status');
            });
        }

        $totalWorkDays = $statsRecords->filter(function ($record) {
            return $record->status === 'حضـور' || $record->status === 'غيــاب';
        })->count();

        $attendanceStats['present_days'] = $statsRecords->filter(function ($record) {
            return $record->status === 'حضـور' && $record->entry_time !== null;
        })->count();

        $attendanceStats['total_work_days'] = $totalWorkDays;

        $attendanceStats['absent_days'] = $statsRecords->filter(function ($record) {
            return $record->status === 'غيــاب';
        })->count();

        $attendanceStats['violation_days'] = $statsRecords->filter(function ($record) {
            return ($record->penalty ?? 0) > 0;
        })->count();

        $lateRecords = $statsRecords->filter(function ($record) {
            return ($record->delay_minutes ?? 0) > 0 && $record->entry_time !== null;
        });

        $attendanceStats['late_days'] = $lateRecords->count();
        $attendanceStats['total_delay_minutes'] = $lateRecords->sum('delay_minutes');
        $attendanceStats['avg_delay_minutes'] = $lateRecords->count() > 0
            ? round($lateRecords->average('delay_minutes'), 1)
            : 0;
        $attendanceStats['max_delay_minutes'] = $lateRecords->max('delay_minutes') ?? 0;

        $threeMonthsStats = [];
        $currentDate = now();

        for ($i = 0; $i < 3; $i++) {
            $monthDate = $currentDate->copy()->subMonths($i);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd = $monthDate->copy()->endOfMonth();

            // استخدام النظام البديل لكل شهر
            $monthRecords = $this->getAttendanceData($employee_id, $monthStart, $monthEnd);

            // تطبيق فلتر الحالة إذا وجد
            if (request('status')) {
                $monthRecords = $monthRecords->filter(function ($record) {
                    return $record->status === request('status');
                });
            }

            $totalWorkDays = $monthRecords->filter(function ($record) {
                return $record->status === 'حضـور' || $record->status === 'غيــاب';
            })->count();

            $threeMonthsStats[] = [
                'month' => $monthDate->translatedFormat('F'),
                'year' => $monthDate->year,
                'total_days' => $totalWorkDays,
                'present_days' => $monthRecords->where('status', 'حضـور')->count(),
                'absent_days' => $monthRecords->where('status', 'غيــاب')->count()
            ];
        }

        // للحصول على السجلات للعرض في الجدول مع pagination
        // نحتاج نستخدم query builder مش collection
        $recordsCount = AttendanceRecord::where('employee_id', $employee_id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        if ($recordsCount == 0) {
            // استخدام Attendance
            $attendanceQuery = Attendance::where('employee_id', $employee_id)
                ->whereBetween('date', [$startDate, $endDate]);

            if (request('status')) {
                if (request('status') === 'حضـور') {
                    $attendanceQuery->whereNotNull('check_in');
                } elseif (request('status') === 'غيــاب') {
                    $attendanceQuery->whereNull('check_in');
                }
            }

            $attendanceRecords = $attendanceQuery->orderBy('date', 'desc')
                ->paginate(15)
                ->withQueryString();

            // تحويل السجلات للصيغة الموحدة
            $attendanceRecords->getCollection()->transform(function($record) {
                $status = 'غيــاب';
                if ($record->check_in) {
                    $status = 'حضـور';
                }

                $workingHours = null;
                if ($record->check_in && $record->check_out) {
                    $workingHours = $record->check_out->diffInHours($record->check_in);
                }

                $entryTime = $record->check_in ? $record->check_in->format('H:i:s') : null;
                $exitTime = $record->check_out ? $record->check_out->format('H:i:s') : null;

                $attendanceRecord = new AttendanceRecord([
                    'attendance_date' => $record->date->format('Y-m-d'),
                    'status' => $status,
                    'entry_time' => $entryTime,
                    'exit_time' => $exitTime,
                    'delay_minutes' => $record->late_minutes,
                    'early_minutes' => $record->early_minutes,
                    'working_hours' => $workingHours,
                    'employee_id' => $record->employee_id,
                    'penalty' => 0,
                    'notes' => "تم إنشاؤه من نظام تسجيل الحضور المباشر"
                ]);

                return $attendanceRecord;
            });
        } else {
            // استخدام AttendanceRecord
            $recordsQuery = AttendanceRecord::where('employee_id', $employee_id)
                ->whereBetween('attendance_date', [$startDate, $endDate]);

            if (request('status')) {
                $recordsQuery->where('status', request('status'));
            }

            $attendanceRecords = $recordsQuery->orderBy('attendance_date', 'desc')
                ->paginate(15)
                ->withQueryString();
        }

        return $reportService->previewAttendance(
            $employee_id,
            $attendanceStats,
            $startDate,
            $endDate,
            $threeMonthsStats,
            $attendanceRecords
        );
    }
}
