<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequests;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\UserBadge;
use App\Models\UserSeasonPoint;
use App\Models\Season;
use App\Models\AttendanceRecord;
use App\Models\Attendance;
use App\Models\SpecialCase;
use App\Models\EmployeeEvaluation;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\EvaluationDetail;
use App\Models\CallLog;
use App\Models\Meeting;

use App\Models\ClientTicket;
use App\Models\AdditionalTask;
use App\Models\AdditionalTaskUser;
use App\Models\TaskTemplate;
use App\Models\TemplateTask;
use App\Models\TemplateTaskUser;
use App\Services\SeasonStatisticsService;
use App\Services\Auth\RoleCheckService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeProfileController extends Controller
{
    protected $seasonStatisticsService;
    protected $roleCheckService;

    public function __construct(SeasonStatisticsService $seasonStatisticsService, RoleCheckService $roleCheckService)
    {
        $this->middleware('auth');
        $this->seasonStatisticsService = $seasonStatisticsService;
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * عرض البروفايل الشامل للموظف
     */
    public function show(Request $request, User $user = null)
    {
        $user = $user ?? Auth::user();
        $currentUser = Auth::user();
        $isOwnProfile = $currentUser->id === $user->id;

        // تسجيل النشاط - عرض ملف الموظف الشامل
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($user)
                ->causedBy($currentUser)
                ->withProperties([
                    'profile_user_id' => $user->id,
                    'profile_user_name' => $user->name,
                    'is_own_profile' => $isOwnProfile,
                    'start_date' => $request->get('start_date'),
                    'end_date' => $request->get('end_date'),
                    'action_type' => 'view_profile',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد الملف الشخصي للموظف');
        }

        // التحقق من الصلاحيات
        if (!$isOwnProfile && !$this->roleCheckService->userHasRole(['admin', 'hr', 'manager'])) {
            abort(403, 'ليس لديك صلاحية لعرض هذا البروفايل');
        }

        // الحصول على فترة التاريخ من الطلب أو استخدام القيم الافتراضية
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // التأكد من صحة التواريخ
        try {
            $startDate = Carbon::parse($startDate);
            $endDate = Carbon::parse($endDate);

            // التأكد من أن تاريخ البداية ليس بعد تاريخ النهاية
            if ($startDate->gt($endDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }
        } catch (\Exception $e) {
            // في حالة وجود خطأ في التواريخ، استخدم القيم الافتراضية
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now();
        }

        // جلب البيانات الأساسية مع الفترة المحددة
        $profileData = $this->getProfileData($user, $startDate, $endDate);

        return view('employee.profile.comprehensive', compact(
            'user',
            'profileData',
            'isOwnProfile',
            'startDate',
            'endDate'
        ));
    }

    /**
     * جلب جميع بيانات البروفايل
     */
    private function getProfileData(User $user, Carbon $startDate = null, Carbon $endDate = null)
    {
        // إذا لم يتم تمرير التواريخ، استخدم الشهر الحالي
        if (!$startDate || !$endDate) {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now();
        }

        return [
            'personal_info' => $this->getPersonalInfo($user),
            'performance_stats' => $this->getPerformanceStats($user, $startDate, $endDate),
            'attendance_data' => $this->getAttendanceData($user, $startDate, $endDate),
            'projects_tasks' => $this->getProjectsAndTasks($user, $startDate, $endDate),
            'requests_history' => $this->getRequestsHistory($user, $startDate, $endDate),
            'social_stats' => $this->getSocialStats($user, $startDate, $endDate),
            'badges_achievements' => $this->getBadgesAndAchievements($user),
            'reviews_evaluations' => $this->getReviewsAndEvaluations($user, $startDate, $endDate),
            'skills_evaluations' => $this->getSkillsAndEvaluations($user, $startDate, $endDate),
            'professional_activities' => $this->getProfessionalActivities($user, $startDate, $endDate),
            'performance_metrics' => $this->calculatePerformanceMetrics($user, $startDate, $endDate),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_count' => $startDate->diffInDays($endDate) + 1
            ]
        ];
    }

    /**
     * المعلومات الشخصية والوظيفية
     */
    private function getPersonalInfo(User $user)
    {
        $workShift = $user->workShift;
        $currentTeam = $user->currentTeam;

        return [
            'basic_info' => [
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'department' => $user->department,
                'gender' => $user->gender,
                'date_of_birth' => $user->date_of_birth,
                'phone_number' => $user->phone_number,
                'address' => $user->address,
                'national_id_number' => $user->national_id_number,
            ],
            'employment_info' => [
                'start_date_of_employment' => $user->start_date_of_employment,
                'last_contract_start_date' => $user->last_contract_start_date,
                'last_contract_end_date' => $user->last_contract_end_date,
                'job_progression' => $user->job_progression,
                'employee_status' => $user->employee_status,
                'education_level' => $user->education_level,
                'marital_status' => $user->marital_status,
                'number_of_children' => $user->number_of_children,
            ],
            'work_details' => [
                'work_shift' => $workShift ? $workShift->name : 'غير محدد',
                'current_team' => $currentTeam ? $currentTeam->name : 'غير محدد',
                'roles' => $user->getRoleNames()->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ]
        ];
    }

        /**
     * إحصائيات الأداء
     */
    private function getPerformanceStats(User $user, Carbon $startDate, Carbon $endDate)
    {
        $currentSeason = Season::getCurrentSeason();
        $seasonId = $currentSeason ? $currentSeason->id : null;

        $seasonStats = $seasonId ? $this->seasonStatisticsService->getUserStatistics($user->id, $seasonId) : null;

        // Get total points from UserSeasonPoint model
        $userSeasonPoints = $seasonId ? UserSeasonPoint::where('user_id', $user->id)
            ->where('season_id', $seasonId)
            ->first() : null;

        return [
            'current_season' => $currentSeason ? $currentSeason->name : 'لا يوجد موسم نشط',
            'season_stats' => $seasonStats,
            'total_projects' => $user->projects()
                ->whereBetween('project_service_user.created_at', [$startDate, $endDate])
                ->count(),
            'completed_projects' => $user->projects()
                ->where('projects.status', 'مكتمل')
                ->whereBetween('projects.updated_at', [$startDate, $endDate])
                ->count(),
            'total_tasks' => TaskUser::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'completed_tasks' => TaskUser::where('user_id', $user->id)
                ->where('status', 'مكتمل')
                ->whereBetween('completed_date', [$startDate, $endDate])
                ->count(),
            'total_points' => $userSeasonPoints ? $userSeasonPoints->total_points : 0,
        ];
    }

            /**
     * Get attendance data from either AttendanceRecord or Attendance based on availability
     */
    private function getAttendanceRecords($employeeId, Carbon $startDate, Carbon $endDate)
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
     */
    private function getAttendanceFromCheckInSystem($employeeId, Carbon $startDate, Carbon $endDate)
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
                    'notes' => "تم إنشاؤه من نظام تسجيل الحضور المباشر"
                ]);

                return $attendanceRecord;
            });
        } catch (\Exception $e) {
            Log::error('Error fetching attendance data from check-in system: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * بيانات الحضور والانصراف
     */
    private function getAttendanceData(User $user, Carbon $startDate, Carbon $endDate)
    {
        // Get special cases for this period
        $specialCases = SpecialCase::where('employee_id', $user->employee_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->mapWithKeys(function ($case) {
                return [Carbon::parse($case->date)->format('Y-m-d') => $case];
            })
            ->all();

        // جلب بيانات الحضور
        $attendanceRecords = $this->getAttendanceRecords($user->employee_id, $startDate, $endDate);

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

        $avgWorkingHours = $daysWithHours > 0 ? $totalWorkingHours / $daysWithHours : 0;

        return [
            'period_summary' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'working_days' => $totalWorkDays,
                'present_days' => $actualAttendanceDays,
                'absent_days' => $totalWorkDays - $actualAttendanceDays,
                'late_days' => $totalDelayMinutes > 0 ? 1 : 0, // تحسين هذا لاحقاً
                'attendance_rate' => $totalWorkDays > 0 ? round(($actualAttendanceDays / $totalWorkDays) * 100, 2) : 0,
                'total_delay_minutes' => $totalDelayMinutes,
                'average_working_hours' => round($avgWorkingHours, 2),
            ],
            'recent_attendance' => $attendanceRecords->sortByDesc('attendance_date')->take(10),
            'max_allowed_absence_days' => $user->getMaxAllowedAbsenceDays(),
            'special_cases' => $specialCases,
        ];
    }

    /**
     * المشاريع والمهام
     */
    private function getProjectsAndTasks(User $user, Carbon $startDate, Carbon $endDate)
    {
        // المشاريع
        $projects = $user->projects()
            ->with(['client', 'season'])
            ->whereBetween('project_service_user.created_at', [$startDate, $endDate])
            ->latest('project_service_user.created_at')
            ->get();

        // 1. المهام العادية (Regular Tasks)
        $regularTasks = TaskUser::where('user_id', $user->id)
            ->with(['task.project', 'task.service'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        // 2. المهام الإضافية (Additional Tasks)
        $additionalTasks = AdditionalTaskUser::where('user_id', $user->id)
            ->with(['additionalTask', 'additionalTask.season'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        // 3. مهام القوالب (Template Tasks)
        $templateTasks = TemplateTaskUser::where('user_id', $user->id)
            ->with(['templateTask.template', 'season', 'project'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        // إحصائيات المشاريع
        $projectStats = [
            'جديد' => $user->projects()
                ->where('projects.status', 'جديد')
                ->whereBetween('project_service_user.created_at', [$startDate, $endDate])
                ->count(),
            'جاري التنفيذ' => $user->projects()
                ->where('projects.status', 'جاري التنفيذ')
                ->whereBetween('project_service_user.created_at', [$startDate, $endDate])
                ->count(),
            'مكتمل' => $user->projects()
                ->where('projects.status', 'مكتمل')
                ->whereBetween('project_service_user.created_at', [$startDate, $endDate])
                ->count(),
            'ملغي' => $user->projects()
                ->where('projects.status', 'ملغي')
                ->whereBetween('project_service_user.created_at', [$startDate, $endDate])
                ->count(),
        ];

        // إحصائيات المهام العادية
        $regularTaskStats = [
            'جديد' => $regularTasks->where('status', 'جديد')->count(),
            'جاري التنفيذ' => $regularTasks->where('status', 'جاري التنفيذ')->count(),
            'مكتمل' => $regularTasks->where('status', 'مكتمل')->count(),
            'ملغي' => $regularTasks->where('status', 'ملغي')->count(),
            'total' => $regularTasks->count(),
        ];

        // إحصائيات المهام الإضافية
        $additionalTaskStats = [
            'pending' => $additionalTasks->where('status', 'pending')->count(),
            'approved' => $additionalTasks->where('status', 'approved')->count(),
            'in_progress' => $additionalTasks->where('status', 'in_progress')->count(),
            'completed' => $additionalTasks->where('status', 'completed')->count(),
            'rejected' => $additionalTasks->where('status', 'rejected')->count(),
            'total' => $additionalTasks->count(),
            'total_points' => $additionalTasks->where('status', 'completed')->sum('points_earned'),
        ];

        // إحصائيات مهام القوالب
        $templateTaskStats = [
            'pending' => $templateTasks->where('status', 'pending')->count(),
            'in_progress' => $templateTasks->where('status', 'in_progress')->count(),
            'paused' => $templateTasks->where('status', 'paused')->count(),
            'completed' => $templateTasks->where('status', 'completed')->count(),
            'total' => $templateTasks->count(),
            'total_minutes' => $templateTasks->where('status', 'completed')->sum('actual_minutes'),
        ];

        // الإجماليات
        $totalTasksCount = $regularTaskStats['total'] + $additionalTaskStats['total'] + $templateTaskStats['total'];
        $totalCompletedTasks = $regularTaskStats['مكتمل'] + $additionalTaskStats['completed'] + $templateTaskStats['completed'];

        return [
            'projects' => $projects,
            'recent_projects' => $projects->take(5),
            'projects_by_status' => $projectStats,

            // المهام العادية
            'regular_tasks' => $regularTasks,
            'recent_regular_tasks' => $regularTasks->take(5),
            'regular_tasks_by_status' => $regularTaskStats,

            // المهام الإضافية
            'additional_tasks' => $additionalTasks,
            'recent_additional_tasks' => $additionalTasks->take(5),
            'additional_tasks_by_status' => $additionalTaskStats,

            // مهام القوالب
            'template_tasks' => $templateTasks,
            'recent_template_tasks' => $templateTasks->take(5),
            'template_tasks_by_status' => $templateTaskStats,

            // الإجماليات والملخص
            'summary' => [
                'total_projects' => $projects->count(),
                'total_tasks' => $totalTasksCount,
                'total_completed_tasks' => $totalCompletedTasks,
                'completion_rate' => $totalTasksCount > 0 ? round(($totalCompletedTasks / $totalTasksCount) * 100, 1) : 0,
                'total_points_earned' => $additionalTaskStats['total_points'],
                'total_hours_worked' => round($templateTaskStats['total_minutes'] / 60, 2),
            ],

            // للتوافق مع الكود الحالي
            'recent_tasks' => $regularTasks->take(10),
            'tasks_by_status' => $regularTaskStats,
        ];
    }

    /**
     * تاريخ الطلبات
     */
    private function getRequestsHistory(User $user, Carbon $startDate, Carbon $endDate)
    {
        // إحصائيات الأذونات (باستخدام departure_time كما في EmployeeStatisticsController)
        $permissionStats = [
            'total' => PermissionRequest::where('user_id', $user->id)->whereBetween('departure_time', [$startDate, $endDate])->count(),
            'approved' => PermissionRequest::where('user_id', $user->id)->whereBetween('departure_time', [$startDate, $endDate])->where('status', 'approved')->count(),
            'pending' => PermissionRequest::where('user_id', $user->id)->whereBetween('departure_time', [$startDate, $endDate])->where('status', 'pending')->count(),
            'rejected' => PermissionRequest::where('user_id', $user->id)->whereBetween('departure_time', [$startDate, $endDate])->where('status', 'rejected')->count(),
            'recent' => PermissionRequest::where('user_id', $user->id)->whereBetween('departure_time', [$startDate, $endDate])->latest('departure_time')->take(5)->get(),
            'total_minutes' => PermissionRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->get()
                ->sum(function($record) {
                    $departureTime = Carbon::parse($record->departure_time);
                    $returnTime = Carbon::parse($record->return_time);
                    return abs($returnTime->diffInMinutes($departureTime));
                })
        ];

        // إحصائيات الأوفر تايم (باستخدام overtime_date كما في EmployeeStatisticsController)
        $overtimeStats = [
            'total' => OverTimeRequests::where('user_id', $user->id)->whereBetween('overtime_date', [$startDate, $endDate])->count(),
            'approved' => OverTimeRequests::where('user_id', $user->id)->whereBetween('overtime_date', [$startDate, $endDate])->where('status', 'approved')->count(),
            'pending' => OverTimeRequests::where('user_id', $user->id)->whereBetween('overtime_date', [$startDate, $endDate])->where('status', 'pending')->count(),
            'rejected' => OverTimeRequests::where('user_id', $user->id)->whereBetween('overtime_date', [$startDate, $endDate])->where('status', 'rejected')->count(),
            'recent' => OverTimeRequests::where('user_id', $user->id)->whereBetween('overtime_date', [$startDate, $endDate])->latest('overtime_date')->take(5)->get(),
            'total_minutes' => OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$startDate, $endDate])
                ->get()
                ->sum(function($record) {
                    $startTime = Carbon::parse($record->start_time);
                    $endTime = Carbon::parse($record->end_time);
                    return abs($endTime->diffInMinutes($startTime));
                })
        ];

        // إحصائيات الإجازات (باستخدام absence_date كما في EmployeeStatisticsController)
        $absenceStats = [
            'total' => AbsenceRequest::where('user_id', $user->id)->whereBetween('absence_date', [$startDate, $endDate])->count(),
            'approved' => AbsenceRequest::where('user_id', $user->id)->whereBetween('absence_date', [$startDate, $endDate])->where('status', 'approved')->count(),
            'pending' => AbsenceRequest::where('user_id', $user->id)->whereBetween('absence_date', [$startDate, $endDate])->where('status', 'pending')->count(),
            'rejected' => AbsenceRequest::where('user_id', $user->id)->whereBetween('absence_date', [$startDate, $endDate])->where('status', 'rejected')->count(),
            'recent' => AbsenceRequest::where('user_id', $user->id)->whereBetween('absence_date', [$startDate, $endDate])->latest('absence_date')->take(5)->get(),
        ];

        // الإجازات السنوية (كما في EmployeeStatisticsController)
        $yearlyLeaves = AbsenceRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [
                Carbon::parse($startDate)->startOfYear(),
                Carbon::parse($endDate)->endOfYear()
            ])
            ->count();

        $currentMonthLeaves = AbsenceRequest::where('user_id', $user->id)
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

        return [
            'absence_requests' => $absenceStats,
            'permission_requests' => $permissionStats,
            'overtime_requests' => $overtimeStats,
            'yearly_summary' => [
                'taken_leaves' => $yearlyLeaves,
                'remaining_leaves' => $user->getMaxAllowedAbsenceDays() - $yearlyLeaves,
                'current_month_leaves' => $currentMonthLeaves,
                'max_allowed_leaves' => $user->getMaxAllowedAbsenceDays(),
            ]
        ];
    }

    /**
     * إحصائيات التواصل الاجتماعي
     */
    private function getSocialStats(User $user, Carbon $startDate, Carbon $endDate)
    {
        return [
            'posts_count' => $user->posts()->where('posts.is_active', true)->whereBetween('posts.created_at', [$startDate, $endDate])->count(),
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'likes_given' => $user->likes()->whereBetween('likes.created_at', [$startDate, $endDate])->count(),
            'comments_made' => $user->comments()->whereBetween('comments.created_at', [$startDate, $endDate])->count(),
            'recent_posts' => $user->posts()->where('posts.is_active', true)->whereBetween('posts.created_at', [$startDate, $endDate])->latest('posts.created_at')->take(3)->get(),
        ];
    }

    /**
     * الشارات والإنجازات
     */
    private function getBadgesAndAchievements(User $user)
    {
        $currentSeason = Season::getCurrentSeason();
        $seasonId = $currentSeason ? $currentSeason->id : null;

        $userBadges = UserBadge::where('user_id', $user->id)
            ->with('badge')
            ->latest()
            ->take(10)
            ->get();

        $currentSeasonPoints = $seasonId ? $user->getSeasonPoints($seasonId) : null;

        return [
            'current_season_badge' => $currentSeasonPoints ? $currentSeasonPoints->currentBadge : null,
            'total_badges' => $userBadges->count(),
            'active_badges' => $userBadges->where('is_active', true)->count(),
            'recent_badges' => $userBadges,
            'season_points' => $currentSeasonPoints ? [
                'total_points' => $currentSeasonPoints->total_points,
                'tasks_completed' => $currentSeasonPoints->tasks_completed,
                'projects_completed' => $currentSeasonPoints->projects_completed,
                'minutes_worked' => $currentSeasonPoints->minutes_worked,
            ] : null,
        ];
    }

    /**
     * التقييمات والمراجعات
     */
    private function getReviewsAndEvaluations(User $user, Carbon $startDate, Carbon $endDate)
    {
        // هنا يمكن إضافة المزيد من التقييمات حسب النماذج المتاحة
        return [
            'employee_evaluations' => $user->evaluations()->whereBetween('employee_evaluations.created_at', [$startDate, $endDate])->get() ?? collect(),
            'performance_reviews' => [], // يمكن إضافة النماذج المناسبة
            'feedback_received' => [], // يمكن إضافة نظام التغذية الراجعة
        ];
    }

    /**
     * تصدير البروفايل كـ PDF
     */
    public function exportPDF(User $user = null)
    {
        $user = $user ?? Auth::user();
        $profileData = $this->getProfileData($user);

        // يمكن استخدام مكتبة PDF مثل DomPDF أو TCPDF
        // return view('employee.profile.pdf', compact('user', 'profileData'));

        return response()->json([
            'success' => false,
            'message' => 'ميزة تصدير PDF قيد التطوير'
        ]);
    }

        /**
     * تحديث بيانات البروفايل (AJAX)
     */
    public function refreshData(Request $request, User $user = null)
    {
        try {
            $user = $user ?? Auth::user();
            $currentUser = Auth::user();
            $isOwnProfile = $currentUser->id === $user->id;

            // التحقق من الصلاحيات
            if (!$isOwnProfile && !$this->roleCheckService->userHasRole(['admin', 'hr', 'manager'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لعرض هذا البروفايل'
                ], 403);
            }

            // الحصول على فترة التاريخ من الطلب
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            // التأكد من صحة التواريخ
            try {
                $startDate = Carbon::parse($startDate);
                $endDate = Carbon::parse($endDate);

                if ($startDate->gt($endDate)) {
                    $temp = $startDate;
                    $startDate = $endDate;
                    $endDate = $temp;
                }
            } catch (\Exception $e) {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now();
            }

            $profileData = $this->getProfileData($user, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح',
                'profileData' => $profileData
            ]);

        } catch (\Exception $e) {
            Log::error('Error refreshing profile data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث البيانات'
            ], 500);
        }
    }

    /**
     * المهارات والتقييمات
     */
    private function getSkillsAndEvaluations(User $user, Carbon $startDate, Carbon $endDate)
    {
        // تقييمات الموظف
        $evaluations = EmployeeEvaluation::where('user_id', $user->id)
            ->whereBetween('evaluation_date', [$startDate, $endDate])
            ->with(['evaluator', 'evaluationDetails.skill.category'])
            ->latest('evaluation_date')
            ->get();

        // إحصائيات التقييمات
        $evaluationStats = [
            'total_evaluations' => $evaluations->count(),
            'average_score' => $evaluations->avg('score_percentage') ?? 0,
            'latest_evaluation' => $evaluations->first(),
            'evaluations_by_period' => $evaluations->groupBy(function($eval) {
                return $eval->evaluation_period;
            })->map->count(),
        ];

        // المهارات المقيمة
        $skillsData = [];
        if ($evaluations->count() > 0) {
            $allDetails = $evaluations->flatMap->evaluationDetails;
            $skillsGrouped = $allDetails->groupBy('skill_id');

            foreach ($skillsGrouped as $skillId => $details) {
                $skill = $details->first()->skill;
                if ($skill) {
                    $avgScore = $details->avg('points');
                    $maxPoints = $skill->max_points;
                    $percentage = $maxPoints > 0 ? round(($avgScore / $maxPoints) * 100, 1) : 0;

                    $skillsData[] = [
                        'skill' => $skill,
                        'category' => $skill->category,
                        'average_points' => round($avgScore, 1),
                        'max_points' => $maxPoints,
                        'percentage' => $percentage,
                        'evaluations_count' => $details->count()
                    ];
                }
            }
        }

        // ترتيب المهارات حسب النسبة
        usort($skillsData, function($a, $b) {
            return $b['percentage'] <=> $a['percentage'];
        });

        return [
            'evaluation_stats' => $evaluationStats,
            'recent_evaluations' => $evaluations->take(5),
            'skills_performance' => collect($skillsData),
            'skills_by_category' => collect($skillsData)->groupBy('category.name'),
        ];
    }

    /**
     * الأنشطة المهنية
     */
    private function getProfessionalActivities(User $user, Carbon $startDate, Carbon $endDate)
    {
        // سجلات المكالمات
        $callLogs = CallLog::where('employee_id', $user->id)
            ->whereBetween('call_date', [$startDate, $endDate])
            ->with('client')
            ->latest('call_date')
            ->get();

        // الاجتماعات
        $meetings = Meeting::whereHas('participants', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereBetween('start_time', [$startDate, $endDate])
            ->with(['client', 'creator'])
            ->latest('start_time')
            ->get();

        // إحصائيات المهام والوقت
        $taskStats = [
            'total_time_spent' => TaskUser::where('user_id', $user->id)
                ->whereHas('task', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum(DB::raw('(actual_hours * 60) + actual_minutes')),
            'template_time_spent' => TemplateTaskUser::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('actual_minutes')
        ];

        // تذاكر العملاء المخصصة للموظف
        $tickets = ClientTicket::where('assigned_to', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['project.client', 'assignedEmployee'])
            ->latest('created_at')
            ->get();

        // إحصائيات الأنشطة
        $activityStats = [
            'calls_stats' => [
                'total' => $callLogs->count(),
                'successful' => $callLogs->where('outcome', 'successful')->count(),
                'total_duration' => $callLogs->sum('duration_minutes'),
                'by_type' => $callLogs->groupBy('contact_type')->map->count(),
            ],
            'meetings_stats' => [
                'total' => $meetings->count(),
                'completed' => $meetings->where('is_completed', true)->count(),
                'internal' => $meetings->where('type', 'internal')->count(),
                'client' => $meetings->where('type', 'client')->count(),
            ],
            'time_tracking' => [
                'total_minutes' => $taskStats['total_time_spent'] + $taskStats['template_time_spent'],
                'total_hours' => round(($taskStats['total_time_spent'] + $taskStats['template_time_spent']) / 60, 2),
                'task_time' => $taskStats['total_time_spent'],
                'template_time' => $taskStats['template_time_spent'],
            ],
            'tickets_stats' => [
                'assigned' => $tickets->count(),
                'resolved' => $tickets->whereNotNull('resolved_at')->count(),
                'by_status' => $tickets->groupBy('status')->map->count(),
                'by_priority' => $tickets->groupBy('priority')->map->count(),
            ]
        ];

        return [
            'activity_stats' => $activityStats,
            'recent_calls' => $callLogs->take(10),
            'recent_meetings' => $meetings->take(5),

            'recent_tickets' => $tickets->take(5),
        ];
    }

    /**
     * حساب مؤشرات الأداء (مأخوذة من EmployeeStatisticsController)
     */
    private function calculatePerformanceMetrics(User $user, Carbon $startDate, Carbon $endDate)
    {
        // استخدام البيانات المحسوبة مسبقاً
        $attendanceData = $this->getAttendanceData($user, $startDate, $endDate);
        $requestsData = $this->getRequestsHistory($user, $startDate, $endDate);

        $attendanceSummary = $attendanceData['period_summary'];
        $attendanceRate = $attendanceSummary['attendance_rate'];
        $totalDelayMinutes = $attendanceSummary['total_delay_minutes'];
        $avgWorkingHours = $attendanceSummary['average_working_hours'];

        // حساب عدد الشهور في الفترة
        $monthsDifference = max(1, $startDate->diffInMonths($endDate));
        if ($monthsDifference < 1 && $startDate->format('m') != $endDate->format('m')) {
            $monthsDifference = 1;
        }

        // نقاط الأداء
        $attendanceScore = min(100, $attendanceRate);

        // نقاط الالتزام بالمواعيد
        $baseMaxAcceptableDelays = 120; // 120 دقيقة في الشهر
        $maxAcceptableDelays = $baseMaxAcceptableDelays * $monthsDifference;
        $punctualityScore = 100;
        if ($totalDelayMinutes > $maxAcceptableDelays) {
            $excessDelays = $totalDelayMinutes - $maxAcceptableDelays;
            $punctualityScore = max(0, 100 - (($excessDelays / $maxAcceptableDelays) * 100));
        }

        // نقاط ساعات العمل
        $workingHoursScore = 0;
        if ($attendanceRate > 0 && $avgWorkingHours > 0) {
            $avgHoursRate = $avgWorkingHours / 8;
            $workingHoursScore = min(100, ($attendanceRate / 100) * $avgHoursRate * 100);
        }

        // نقاط الأذونات
        $baseMaxAcceptablePermissions = 180; // 180 دقيقة في الشهر
        $maxAcceptablePermissions = $baseMaxAcceptablePermissions * $monthsDifference;
        $permissionMinutes = $requestsData['permission_requests']['total_minutes'];
        $permissionsScore = 100;
        if ($permissionMinutes > $maxAcceptablePermissions) {
            $excessPermissions = $permissionMinutes - $maxAcceptablePermissions;
            $permissionsScore = max(0, 100 - (($excessPermissions / $maxAcceptablePermissions) * 100));
        }

        // النتيجة الإجمالية
        $overallScore = round(($attendanceScore * 0.45) + ($punctualityScore * 0.2) + ($workingHoursScore * 0.35), 1);

        return [
            'attendance_score' => round($attendanceScore, 1),
            'punctuality_score' => round($punctualityScore, 1),
            'working_hours_score' => round($workingHoursScore, 1),
            'permissions_score' => round($permissionsScore, 1),
            'overall_score' => $overallScore,
            'performance_level' => $this->getPerformanceLevel($overallScore),
            'delay_status' => [
                'minutes' => $totalDelayMinutes,
                'is_good' => $totalDelayMinutes <= $maxAcceptableDelays,
                'max_acceptable' => $maxAcceptableDelays,
            ],
            'permissions_status' => [
                'minutes' => $permissionMinutes,
                'is_good' => $permissionMinutes <= $maxAcceptablePermissions,
                'max_acceptable' => $maxAcceptablePermissions,
            ],
            'period_months' => $monthsDifference,
        ];
    }

    /**
     * تحديد مستوى الأداء
     */
    private function getPerformanceLevel($score)
    {
        if ($score >= 90) return 'ممتاز';
        if ($score >= 80) return 'جيد جداً';
        if ($score >= 70) return 'جيد';
        if ($score >= 60) return 'مقبول';
        return 'يحتاج إلى تحسين';
    }
}
