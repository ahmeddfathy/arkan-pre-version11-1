<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TaskUser;
use App\Models\Task;
use App\Models\TemplateTaskUser;
use App\Models\TaskTimeLog;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\TaskRevision;
use App\Models\ProjectServiceUser;
use App\Services\TimeTracking\TimeTrackingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeReportController extends Controller
{
    protected $timeTrackingService;

    public function __construct(TimeTrackingService $timeTrackingService)
    {
        $this->timeTrackingService = $timeTrackingService;
    }

    public function index(Request $request)
    {
        // تسجيل النشاط - دخول صفحة تقارير الموظفين
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'employee_reports_index',
                    'employee_id' => $request->input('employee_id'),
                    'selected_date' => $request->input('selected_date'),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة تقارير الموظفين');
        }

        $user = Auth::user();
        $isManager = in_array($user->role, ['admin', 'hr', 'manager']);

        if ($isManager) {
            $employees = User::whereNotIn('role', ['admin'])->get();
        } else {
            $employees = User::where('id', Auth::id())->get();
        }

        $selectedEmployeeId = $request->input('employee_id', $isManager ? null : Auth::id());

        $selectedDate = $request->input('selected_date')
            ? Carbon::parse($request->input('selected_date'))
            : Carbon::today();

        $taskData = $this->getTaskData($selectedEmployeeId, $selectedDate, $selectedDate->copy()->endOfDay());

        // الحصول على time logs للتاريخ المحدد
        $timeLogsData = null;
        if ($selectedEmployeeId) {
            $timeLogsData = $this->getTimeLogsData($selectedEmployeeId, $selectedDate);
        }

        // الحصول على بيانات الإجازات والأذون للتاريخ المحدد
        $absenceData = null;
        $permissionData = null;
        if ($selectedEmployeeId) {
            $absenceData = $this->getAbsenceData($selectedEmployeeId, $selectedDate);
            $permissionData = $this->getPermissionData($selectedEmployeeId, $selectedDate);
        }

        // الحصول على بيانات التعديلات للتاريخ المحدد
        $revisionsData = null;
        if ($selectedEmployeeId) {
            $revisionsData = $this->getRevisionsData($selectedEmployeeId, $selectedDate);
        }

        // الحصول على المشاريع المسلمة في هذا اليوم
        $deliveredProjectsData = null;
        if ($selectedEmployeeId) {
            $deliveredProjectsData = $this->getDeliveredProjectsData($selectedEmployeeId, $selectedDate);
        }

        $shiftData = null;
        if ($selectedEmployeeId) {
            $shiftData = $this->getShiftData($selectedEmployeeId, $selectedDate, $taskData, $timeLogsData, $absenceData, $revisionsData);
        }

        return view('employee-reports.index', compact(
            'employees',
            'selectedEmployeeId',
            'selectedDate',
            'taskData',
            'timeLogsData',
            'shiftData',
            'absenceData',
            'permissionData',
            'revisionsData',
            'deliveredProjectsData',
            'isManager'
        ));
    }


    public function show($id, Request $request)
    {
        // تسجيل النشاط - عرض تفاصيل تقرير موظف معين
        if (\Illuminate\Support\Facades\Auth::check()) {
            $employee = User::find($id);
            activity()
                ->performedOn($employee)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'target_employee_id' => $id,
                    'target_employee_name' => $employee ? $employee->name : null,
                    'selected_date' => $request->input('selected_date', Carbon::today()->format('Y-m-d')),
                    'action_type' => 'view_employee_report',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تقرير الموظف');
        }

        return redirect()->route('employee-reports.index', ['employee_id' => $id, 'selected_date' => $request->input('selected_date', Carbon::today()->format('Y-m-d'))]);
    }


    private function getTaskData($employeeId, $startDate, $endDate)
    {
        if (!$employeeId) {
            return [];
        }

        $taskData = [];

        // الحصول على Time Logs للتاريخ المحدد أولاً لتحديد المهام التي تم العمل عليها فعلياً
        $timeLogs = TaskTimeLog::where('user_id', $employeeId)
            ->where('work_date', $startDate->format('Y-m-d'))
            ->with(['taskUser.task', 'templateTaskUser.templateTask'])
            ->get();

        // إذا لم يوجد time logs للتاريخ المحدد، لا تعرض أي مهام
        if ($timeLogs->isEmpty()) {
            return [];
        }

        // جمع معرفات المهام التي تم العمل عليها فعلياً
        $workedTaskIds = [];
        $workedTemplateTaskIds = [];
        $taskTimeData = [];

        foreach ($timeLogs as $log) {
            if ($log->task_type === 'regular' && $log->task_user_id) {
                $workedTaskIds[] = $log->task_user_id;
                $taskTimeData['regular'][$log->task_user_id][] = $log;
            } elseif ($log->task_type === 'template' && $log->template_task_user_id) {
                $workedTemplateTaskIds[] = $log->template_task_user_id;
                $taskTimeData['template'][$log->template_task_user_id][] = $log;
            }
        }

        // 1. المهام العادية - فقط التي تم العمل عليها
        if (!empty($workedTaskIds)) {
            $taskUsers = TaskUser::whereIn('id', $workedTaskIds)
                ->where('user_id', $employeeId)
                ->with(['task', 'task.project'])
                ->get();

            foreach ($taskUsers as $taskUser) {
                if (!$taskUser->task) {
                    continue;
                }

                $task = $taskUser->task;
                $taskLogs = $taskTimeData['regular'][$taskUser->id] ?? [];

                // حساب الوقت الفعلي من Time Logs لهذا التاريخ فقط
                $actualMinutesForDate = 0;
                foreach ($taskLogs as $log) {
                    $actualMinutesForDate += $log->duration_minutes ?? 0;
                }

                // إذا لم يكن هناك وقت مسجل لهذا التاريخ، تجاهل المهمة
                if ($actualMinutesForDate == 0) {
                    continue;
                }

                $hours = intdiv($actualMinutesForDate, 60);
                $minutes = $actualMinutesForDate % 60;

                // إنشاء Gantt Data بناءً على الوقت الفعلي المسجل فقط
                $ganttData = [];
                $currentDate = clone $startDate;

                while ($currentDate <= $endDate) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $dayMinutes = 0;

                    // فقط إضافة الوقت للتاريخ المحدد إذا كان هناك عمل فعلي
                    if ($dateStr === $startDate->format('Y-m-d')) {
                        $dayMinutes = $actualMinutesForDate;
                    }

                    $ganttData[$dateStr] = [
                        'date' => $dateStr,
                        'minutes' => $dayMinutes,
                        'formatted' => $this->formatMinutes($dayMinutes)
                    ];

                    $currentDate->addDay();
                }

                // الحصول على معلومات التسليم من ProjectServiceUser
                $projectDeliveryInfo = null;
                $deliveredToday = false;
                if ($task->project) {
                    $projectServiceUser = ProjectServiceUser::where('project_id', $task->project->id)
                        ->where('user_id', $employeeId)
                        ->first();

                    if ($projectServiceUser && $projectServiceUser->delivered_at) {
                        $projectDeliveryInfo = $projectServiceUser->delivered_at;
                        $deliveredToday = $projectServiceUser->delivered_at->format('Y-m-d') === $startDate->format('Y-m-d');
                    }
                }

                $taskData[] = [
                    'task' => (object)[
                        'id' => $task->id,
                        'name' => $task->name,
                        'description' => $task->description,
                        'status' => $task->status,
                    ],
                    'taskUser' => $taskUser,
                    'project' => $task->project ?? null,
                    'project_delivered_at' => $projectDeliveryInfo,
                    'project_delivered_today' => $deliveredToday,
                    'totalTime' => [
                        'hours' => $hours,
                        'minutes' => $minutes,
                        'formatted' => ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : ($hours > 0 ? '' : '0m'))
                    ],
                    'ganttData' => $ganttData,
                    'startDate' => $taskUser->start_date,
                    'dueDate' => $taskUser->due_date,
                    'status' => $taskUser->status,
                    'type' => 'regular',
                    'actual_work_for_date' => $actualMinutesForDate
                ];
            }
        }

        // 2. مهام القوالب - فقط التي تم العمل عليها
        if (!empty($workedTemplateTaskIds)) {
            $templateTaskUsers = TemplateTaskUser::whereIn('id', $workedTemplateTaskIds)
                ->where('user_id', $employeeId)
                ->with(['templateTask', 'templateTask.template', 'project'])
                ->get();

            foreach ($templateTaskUsers as $templateTaskUser) {
                if (!$templateTaskUser->templateTask) {
                    continue;
                }

                $templateTask = $templateTaskUser->templateTask;
                $templateLogs = $taskTimeData['template'][$templateTaskUser->id] ?? [];

                // حساب الوقت الفعلي من Time Logs لهذا التاريخ فقط
                $actualMinutesForDate = 0;
                foreach ($templateLogs as $log) {
                    $actualMinutesForDate += $log->duration_minutes ?? 0;
                }

                // إذا لم يكن هناك وقت مسجل لهذا التاريخ، تجاهل المهمة
                if ($actualMinutesForDate == 0) {
                    continue;
                }

                $hours = intdiv($actualMinutesForDate, 60);
                $minutes = $actualMinutesForDate % 60;

                // إنشاء Gantt Data بناءً على الوقت الفعلي المسجل فقط
                $ganttData = [];
                $currentDate = clone $startDate;

                while ($currentDate <= $endDate) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $dayMinutes = 0;

                    // فقط إضافة الوقت للتاريخ المحدد إذا كان هناك عمل فعلي
                    if ($dateStr === $startDate->format('Y-m-d')) {
                        $dayMinutes = $actualMinutesForDate;
                    }

                    $ganttData[$dateStr] = [
                        'date' => $dateStr,
                        'minutes' => $dayMinutes,
                        'formatted' => $this->formatMinutes($dayMinutes),
                        'logs' => []
                    ];

                    $currentDate->addDay();
                }

                // الحصول على معلومات التسليم من ProjectServiceUser
                $projectDeliveryInfo = null;
                $deliveredToday = false;
                if ($templateTaskUser->project) {
                    $projectServiceUser = ProjectServiceUser::where('project_id', $templateTaskUser->project->id)
                        ->where('user_id', $employeeId)
                        ->first();

                    if ($projectServiceUser && $projectServiceUser->delivered_at) {
                        $projectDeliveryInfo = $projectServiceUser->delivered_at;
                        $deliveredToday = $projectServiceUser->delivered_at->format('Y-m-d') === $startDate->format('Y-m-d');
                    }
                }

                $taskData[] = [
                    'task' => (object)[
                        'id' => 'template_' . $templateTask->id,
                        'name' => $templateTask->name,
                        'description' => $templateTask->description,
                        'status' => $templateTaskUser->status,
                    ],
                    'taskUser' => $templateTaskUser,
                    'project' => $templateTaskUser->project,
                    'project_delivered_at' => $projectDeliveryInfo,
                    'project_delivered_today' => $deliveredToday,
                    'template' => $templateTask->template,
                    'totalTime' => [
                        'hours' => $hours,
                        'minutes' => $minutes,
                        'formatted' => ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : ($hours > 0 ? '' : '0m'))
                    ],
                    'ganttData' => $ganttData,
                    'startDate' => $templateTaskUser->started_at,
                    'dueDate' => null,
                    'status' => $templateTaskUser->status,
                    'type' => 'template',
                    'actual_work_for_date' => $actualMinutesForDate
                ];
            }
        }

        return $taskData;
    }

    /**
     * الحصول على بيانات time logs للمستخدم في تاريخ محدد
     */
    private function getTimeLogsData($employeeId, $date)
    {
        if (!$employeeId) {
            return null;
        }

        $timeLogs = TaskTimeLog::where('user_id', $employeeId)
            ->where('work_date', $date->format('Y-m-d'))
            ->with(['taskUser.task', 'templateTaskUser.templateTask'])
            ->orderBy('started_at')
            ->get();

        $processedLogs = [];
        $totalMinutes = 0;

        foreach ($timeLogs as $log) {
            // تحديد اسم المهمة حسب النوع
            $taskName = 'مهمة غير محددة';
            $taskType = $log->task_type;
            $taskStatus = 'unknown';
            $projectName = null;
            $projectDeliveredAt = null;

            if ($log->task_type === 'regular' && $log->taskUser && $log->taskUser->task) {
                $taskName = $log->taskUser->task->name;
                $taskStatus = $log->taskUser->status;

                // جلب معلومات المشروع
                if ($log->taskUser->task->project) {
                    $projectName = $log->taskUser->task->project->name;

                    // جلب تاريخ التسليم من ProjectServiceUser
                    $projectServiceUser = ProjectServiceUser::where('project_id', $log->taskUser->task->project->id)
                        ->where('user_id', $employeeId)
                        ->first();

                    if ($projectServiceUser && $projectServiceUser->delivered_at) {
                        $projectDeliveredAt = $projectServiceUser->delivered_at;
                    }
                }
            } elseif ($log->task_type === 'template' && $log->templateTaskUser && $log->templateTaskUser->templateTask) {
                $taskName = $log->templateTaskUser->templateTask->name;
                $taskStatus = $log->templateTaskUser->status;

                // جلب معلومات المشروع
                if ($log->templateTaskUser->project) {
                    $projectName = $log->templateTaskUser->project->name;

                    // جلب تاريخ التسليم من ProjectServiceUser
                    $projectServiceUser = ProjectServiceUser::where('project_id', $log->templateTaskUser->project->id)
                        ->where('user_id', $employeeId)
                        ->first();

                    if ($projectServiceUser && $projectServiceUser->delivered_at) {
                        $projectDeliveredAt = $projectServiceUser->delivered_at;
                    }
                }
            }

            $duration = $log->duration_minutes ?? 0;
            $totalMinutes += $duration;

            $processedLogs[] = [
                'id' => $log->id,
                'task_name' => $taskName,
                'task_type' => $taskType,
                'task_status' => $taskStatus,
                'project_name' => $projectName,
                'project_delivered_at' => $projectDeliveredAt,
                'started_at' => $log->started_at,
                'stopped_at' => $log->stopped_at,
                'duration_minutes' => $duration,
                'duration_formatted' => $log->formatted_duration,
                'is_active' => $log->isActive(),
                'start_time' => $log->started_at->format('H:i'),
                'end_time' => $log->stopped_at ? $log->stopped_at->format('H:i') : null,
            ];
        }

        return [
            'logs' => $processedLogs,
            'total_minutes' => $totalMinutes,
            'total_formatted' => $this->formatMinutes($totalMinutes),
            'sessions_count' => count($processedLogs),
            'active_sessions' => array_filter($processedLogs, function($log) {
                return $log['is_active'];
            })
        ];
    }

    /**
     * تنسيق الدقائق إلى صيغة مقروءة
     */
    private function formatMinutes($minutes)
    {
        if ($minutes == 0) {
            return '0m';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }

        if ($mins > 0) {
            $parts[] = $mins . 'm';
        }

        return implode(' ', $parts);
    }

    /**
     * الحصول على بيانات الشيفت مع توضيح فترات العمل والراحة بناءً على TaskTimeLog الحقيقية والتعديلات
     */
    private function getShiftData($employeeId, $date, $taskData = [], $timeLogsData = null, $absenceData = null, $revisionsData = null)
    {
        $employee = User::with('workShift')->find($employeeId);

        if (!$employee || !$employee->workShift) {
            return null;
        }

        $workShift = $employee->workShift;

        // تحويل وقت البداية والنهاية للشيفت
        $shiftStart = Carbon::parse($workShift->check_in_time);
        $shiftEnd = Carbon::parse($workShift->check_out_time);

        // التعامل مع الشيفت الذي يمتد لليوم التالي
        if ($shiftEnd->lt($shiftStart)) {
            $shiftEnd->addDay();
        }

        // الحصول على بيانات البريك
        $breakStart = null;
        $breakEnd = null;
        $breakDuration = 0;

        if ($workShift->break_start_time && $workShift->break_end_time) {
            $breakStart = Carbon::parse($workShift->break_start_time);
            $breakEnd = Carbon::parse($workShift->break_end_time);

            // التعامل مع البريك الذي يمتد لليوم التالي
            if ($breakEnd->lt($breakStart)) {
                $breakEnd->addDay();
            }

            $breakDuration = $breakEnd->diffInMinutes($breakStart);
        }

        // حساب إجمالي ساعات الشيفت
        $totalShiftMinutes = $shiftEnd->diffInMinutes($shiftStart);
        $totalShiftHours = intdiv($totalShiftMinutes, 60);
        $remainingMinutes = $totalShiftMinutes % 60;

        // إنشاء جدول زمني دقيق بالدقيقة الواحدة بناءً على TaskTimeLog
        $timeline = [];
        $currentTime = clone $shiftStart;
        $now = Carbon::now();
        $isToday = $date->isToday();

        // إنشاء timeline بفاصل دقيقة واحدة
        while ($currentTime->lt($shiftEnd)) {
            $nextTime = clone $currentTime;
            $nextTime->addMinute();

            if ($nextTime->gt($shiftEnd)) {
                $nextTime = clone $shiftEnd;
            }

            // إنشاء الوقت الكامل للتاريخ المحدد
            $slotStartTime = $date->copy()->setTime($currentTime->hour, $currentTime->minute, 0);
            $slotEndTime = $date->copy()->setTime($nextTime->hour, $nextTime->minute, 0);

            $timeSlot = [
                'start' => $currentTime->format('H:i'),
                'end' => $nextTime->format('H:i'),
                'start_timestamp' => $slotStartTime->timestamp,
                'end_timestamp' => $slotEndTime->timestamp,
                'duration_minutes' => $nextTime->diffInMinutes($currentTime),
                'has_work' => false,
                'tasks' => [],
                'status' => 'idle', // idle, working, future
                'is_future' => $isToday && $slotStartTime->gt($now),
                'is_current' => $isToday && $slotStartTime->lte($now) && $slotEndTime->gt($now),
                'minute_index' => $currentTime->diffInMinutes($shiftStart) // فهرس الدقيقة من بداية الشيفت
            ];

            // تحديد الحالة بناءً على الوقت
            if ($timeSlot['is_future']) {
                $timeSlot['status'] = 'future';
            } else {
                // فحص إذا كان الوقت يقع ضمن فترة البريك
                if ($breakStart && $breakEnd && $this->isTimeInBreakPeriod($slotStartTime, $slotEndTime, $breakStart, $breakEnd)) {
                    $timeSlot['status'] = 'break';
                    $timeSlot['is_break'] = true;
                } else {
                    // فحص إذا كان هناك عمل فعلي في هذه الدقيقة باستخدام TaskTimeLog الحقيقية أو التعديلات
                    $timeSlot = $this->checkRealWorkInMinute($timeSlot, $timeLogsData, $slotStartTime, $slotEndTime, $revisionsData);
                }
            }

            $timeline[] = $timeSlot;
            $currentTime = clone $nextTime;
        }

        // حساب إحصائيات (استثناء الفترات المستقبلية)
        $pastSlots = array_filter($timeline, function($slot) {
            return $slot['status'] !== 'future';
        });

        $workingSlots = array_filter($timeline, function($slot) {
            return $slot['status'] === 'working';
        });

        $idleSlots = array_filter($timeline, function($slot) {
            return $slot['status'] === 'idle';
        });

        $breakSlots = array_filter($timeline, function($slot) {
            return $slot['status'] === 'break';
        });

        $futureSlots = array_filter($timeline, function($slot) {
            return $slot['status'] === 'future';
        });

        $workingMinutes = count($workingSlots);
        $idleMinutes = count($idleSlots);
        $breakMinutes = count($breakSlots);
        $futureMinutes = count($futureSlots);
        $pastMinutes = $workingMinutes + $idleMinutes + $breakMinutes;

        return [
            'employee' => $employee,
            'work_shift' => $workShift,
            'shift_start' => $shiftStart->format('H:i'),
            'shift_end' => $shiftEnd->format('H:i'),
            'break_start' => $breakStart ? $breakStart->format('H:i') : null,
            'break_end' => $breakEnd ? $breakEnd->format('H:i') : null,
            'break_duration' => $breakDuration,
            'total_shift_duration' => [
                'hours' => $totalShiftHours,
                'minutes' => $remainingMinutes,
                'total_minutes' => $totalShiftMinutes,
                'formatted' => $this->formatMinutes($totalShiftMinutes)
            ],
            'working_time' => [
                'minutes' => $workingMinutes,
                'formatted' => $this->formatMinutes($workingMinutes)
            ],
            'idle_time' => [
                'minutes' => $idleMinutes,
                'formatted' => $this->formatMinutes($idleMinutes)
            ],
            'break_time' => [
                'minutes' => $breakMinutes,
                'formatted' => $this->formatMinutes($breakMinutes)
            ],
            'future_time' => [
                'minutes' => $futureMinutes,
                'formatted' => $this->formatMinutes($futureMinutes)
            ],
            'past_time' => [
                'minutes' => $pastMinutes,
                'formatted' => $this->formatMinutes($pastMinutes)
            ],
            'timeline' => $timeline,
            'date' => $date->format('Y-m-d'),
            'has_tasks' => count($taskData) > 0,
            'has_break' => $breakStart && $breakEnd,
            'has_absence' => $absenceData !== null,
            'timeline_minutes' => $totalShiftMinutes // إجمالي الدقائق للتايم لاين
        ];
    }

    /**
     * فحص وجود عمل فعلي في فترة زمنية محددة
     */
    private function checkWorkInTimeSlot($timeSlot, $taskData, $slotStartTime, $slotEndTime)
    {
        foreach ($taskData as $task) {
            // التحقق من أن المهمة لها أوقات بدء وانتهاء
            $taskStartTime = null;
            $taskEndTime = null;

            // للمهام العادية
            if ($task['type'] === 'regular' && isset($task['startDate']) && $task['startDate']) {
                $taskStartTime = Carbon::parse($task['startDate']);

                // إذا كانت المهمة مكتملة، استخدم تاريخ الاكتمال
                if ($task['status'] === 'completed' && isset($task['taskUser']->completed_date)) {
                    $taskEndTime = Carbon::parse($task['taskUser']->completed_date);
                }
                // إذا كانت قيد التنفيذ، استخدم الوقت الحالي أو تاريخ الاستحقاق
                elseif ($task['status'] === 'in_progress') {
                    $taskEndTime = $task['dueDate'] ? Carbon::parse($task['dueDate']) : Carbon::now();
                }
            }
            // للمهام المبنية على القوالب
            elseif ($task['type'] === 'template' && isset($task['startDate']) && $task['startDate']) {
                $taskStartTime = Carbon::parse($task['startDate']);

                if ($task['status'] === 'completed' && isset($task['taskUser']->completed_at)) {
                    $taskEndTime = Carbon::parse($task['taskUser']->completed_at);
                } elseif ($task['status'] === 'in_progress') {
                    $taskEndTime = Carbon::now();
                }
            }

            // التحقق من التداخل الزمني
            if ($taskStartTime && $taskEndTime && $this->isTimeOverlapping($taskStartTime, $taskEndTime, $slotStartTime, $slotEndTime)) {
                $timeSlot['has_work'] = true;
                $timeSlot['status'] = 'working';
                $timeSlot['tasks'][] = [
                    'name' => $task['task']->name,
                    'type' => $task['type'] ?? 'regular',
                    'status' => $task['status']
                ];
            }
        }

        return $timeSlot;
    }

    /**
     * فحص وجود عمل فعلي في دقيقة واحدة محددة باستخدام TaskTimeLog الحقيقية والتعديلات
     */
    private function checkRealWorkInMinute($timeSlot, $timeLogsData, $slotStartTime, $slotEndTime, $revisionsData = null)
    {
        // أولاً: فحص جلسات العمل (time logs)
        if ($timeLogsData && isset($timeLogsData['logs'])) {
            foreach ($timeLogsData['logs'] as $log) {
                $logStart = Carbon::parse($log['started_at']);
                $logEnd = $log['stopped_at'] ? Carbon::parse($log['stopped_at']) : Carbon::now();

                // التحقق الدقيق: هل هذه الدقيقة تقع داخل جلسة عمل فعلية؟
                if ($logStart->lte($slotStartTime) && $logEnd->gt($slotStartTime)) {
                    $timeSlot['has_work'] = true;
                    $timeSlot['status'] = 'working';

                    $timeSlot['tasks'][] = [
                        'name' => $log['task_name'],
                        'type' => $log['task_type'],
                        'status' => $log['task_status'],
                        'is_active' => $log['is_active'],
                        'log_start' => $log['start_time'],
                        'log_end' => $log['end_time'],
                        'session_id' => $log['id']
                    ];

                    // إذا كانت جلسة نشطة، أضف معلومات إضافية
                    if ($log['is_active']) {
                        $timeSlot['is_active_session'] = true;
                        $timeSlot['active_since'] = $logStart->format('H:i');
                    }

                    return $timeSlot; // وُجد عمل فعلي
                }
            }
        }

        // ثانياً: فحص التعديلات والمراجعات (نشطة، متوقفة، مكتملة)
        if ($revisionsData && isset($revisionsData['revisions'])) {
            foreach ($revisionsData['revisions'] as $revision) {
                // فحص التنفيذ (نشط، متوقف، أو مكتمل)
                if ($revision['is_executor'] && $revision['started_at']) {
                    $revisionStart = Carbon::parse($revision['started_at']);
                    $revisionEnd = null;
                    $isActive = false;

                    // تحديد وقت النهاية بناءً على الحالة
                    if ($revision['status'] === 'in_progress' && isset($revision['current_session_start'])) {
                        // نشط: من current_session_start إلى الآن (الجلسة الحالية فقط)
                        $revisionStart = Carbon::parse($revision['current_session_start']);
                        $revisionEnd = Carbon::now();
                        $isActive = true;
                    } elseif ($revision['status'] === 'in_progress' && !isset($revision['current_session_start'])) {
                        // نشط ولكن لا توجد جلسة حالية (غير متوقع)
                        continue;
                    } elseif ($revision['status'] === 'paused') {
                        // متوقف: نستخدم actual_minutes لتقدير الوقت الفعلي
                        // نفترض أن العمل كان من started_at بمقدار actual_minutes
                        if (!isset($revision['actual_minutes']) || $revision['actual_minutes'] == 0) {
                            continue;
                        }
                        $actualMinutes = $revision['actual_minutes'];
                        $revisionEnd = $revisionStart->copy()->addMinutes($actualMinutes);
                    } elseif ($revision['status'] === 'completed') {
                        // مكتمل: نستخدم actual_minutes لتقدير الوقت الفعلي
                        if (!isset($revision['actual_minutes']) || $revision['actual_minutes'] == 0) {
                            continue;
                        }
                        $actualMinutes = $revision['actual_minutes'];
                        $revisionEnd = $revisionStart->copy()->addMinutes($actualMinutes);
                    }

                    // التحقق: هل هذه الدقيقة تقع داخل فترة عمل التعديل؟
                    if ($revisionEnd && $revisionStart->lte($slotStartTime) && $revisionEnd->gt($slotStartTime)) {
                        $timeSlot['has_work'] = true;
                        $timeSlot['status'] = 'working';
                        $timeSlot['is_revision_work'] = true;

                        $timeSlot['tasks'][] = [
                            'name' => $revision['title'],
                            'type' => 'revision',
                            'work_type' => 'تنفيذ',
                            'status' => $revision['status'],
                            'is_active' => $isActive,
                            'log_start' => $revisionStart->format('H:i'),
                            'log_end' => $revisionEnd ? $revisionEnd->format('H:i') : null,
                            'session_id' => 'revision_' . $revision['id']
                        ];

                        if ($isActive) {
                            $timeSlot['is_active_session'] = true;
                            $timeSlot['active_since'] = $revisionStart->format('H:i');
                        }

                        return $timeSlot; // وُجد تعديل
                    }
                }

                // فحص المراجعة (نشطة، متوقفة، أو مكتملة)
                if ($revision['is_reviewer'] && $revision['review_started_at']) {
                    $reviewStart = Carbon::parse($revision['review_started_at']);
                    $reviewEnd = null;
                    $isActive = false;

                    // تحديد وقت النهاية بناءً على الحالة
                    if ($revision['review_status'] === 'in_progress' && isset($revision['review_current_session_start'])) {
                        // نشطة: من review_current_session_start إلى الآن (الجلسة الحالية فقط)
                        $reviewStart = Carbon::parse($revision['review_current_session_start']);
                        $reviewEnd = Carbon::now();
                        $isActive = true;
                    } elseif ($revision['review_status'] === 'in_progress' && !isset($revision['review_current_session_start'])) {
                        // نشطة ولكن لا توجد جلسة حالية (غير متوقع)
                        continue;
                    } elseif ($revision['review_status'] === 'paused') {
                        // متوقفة: نستخدم review_actual_minutes لتقدير الوقت الفعلي
                        if (!isset($revision['review_actual_minutes']) || $revision['review_actual_minutes'] == 0) {
                            continue;
                        }
                        $actualMinutes = $revision['review_actual_minutes'];
                        $reviewEnd = $reviewStart->copy()->addMinutes($actualMinutes);
                    } elseif (in_array($revision['review_status'], ['completed', 'approved', 'rejected'])) {
                        // مكتملة: نستخدم review_actual_minutes لتقدير الوقت الفعلي
                        if (!isset($revision['review_actual_minutes']) || $revision['review_actual_minutes'] == 0) {
                            continue;
                        }
                        $actualMinutes = $revision['review_actual_minutes'];
                        $reviewEnd = $reviewStart->copy()->addMinutes($actualMinutes);
                    }

                    // التحقق: هل هذه الدقيقة تقع داخل فترة المراجعة؟
                    if ($reviewEnd && $reviewStart->lte($slotStartTime) && $reviewEnd->gt($slotStartTime)) {
                        $timeSlot['has_work'] = true;
                        $timeSlot['status'] = 'working';
                        $timeSlot['is_revision_work'] = true;
                        $timeSlot['is_review_work'] = true;

                        $timeSlot['tasks'][] = [
                            'name' => $revision['title'],
                            'type' => 'revision',
                            'work_type' => 'مراجعة',
                            'status' => $revision['review_status'],
                            'is_active' => $isActive,
                            'log_start' => $reviewStart->format('H:i'),
                            'log_end' => $reviewEnd ? $reviewEnd->format('H:i') : null,
                            'session_id' => 'review_' . $revision['id']
                        ];

                        if ($isActive) {
                            $timeSlot['is_active_session'] = true;
                            $timeSlot['active_since'] = $reviewStart->format('H:i');
                        }

                        return $timeSlot; // وُجدت مراجعة
                    }
                }
            }
        }

        return $timeSlot;
    }

    /**
     * فحص وجود عمل فعلي في فترة زمنية محددة باستخدام time logs
     */
    private function checkWorkInTimeSlotWithTimeLogs($timeSlot, $timeLogsData, $slotStartTime, $slotEndTime)
    {
        if (!$timeLogsData || !isset($timeLogsData['logs'])) {
            return $timeSlot;
        }

        foreach ($timeLogsData['logs'] as $log) {
            $logStart = Carbon::parse($log['started_at']);
            $logEnd = $log['stopped_at'] ? Carbon::parse($log['stopped_at']) : Carbon::now();

            // التحقق من التداخل الزمني
            if ($this->isTimeOverlapping($logStart, $logEnd, $slotStartTime, $slotEndTime)) {
                $timeSlot['has_work'] = true;
                $timeSlot['status'] = 'working';

                // حساب الدقائق الفعلية المتداخلة
                $overlapStart = $logStart->gt($slotStartTime) ? $logStart : $slotStartTime;
                $overlapEnd = $logEnd->lt($slotEndTime) ? $logEnd : $slotEndTime;
                $overlapMinutes = $overlapStart->diffInMinutes($overlapEnd);

                $timeSlot['tasks'][] = [
                    'name' => $log['task_name'],
                    'type' => $log['task_type'],
                    'status' => $log['task_status'],
                    'is_active' => $log['is_active'],
                    'overlap_minutes' => $overlapMinutes,
                    'log_start' => $log['start_time'],
                    'log_end' => $log['end_time'],
                ];

                // إضافة معلومات تفصيلية للفترة
                $timeSlot['actual_work_minutes'] = ($timeSlot['actual_work_minutes'] ?? 0) + $overlapMinutes;
            }
        }

        return $timeSlot;
    }

    /**
     * التحقق من التداخل الزمني بين فترتين
     */
    private function isTimeOverlapping($taskStart, $taskEnd, $slotStart, $slotEnd)
    {
        // التحقق من التداخل: المهمة تبدأ قبل انتهاء الفترة وتنتهي بعد بداية الفترة
        return $taskStart->lt($slotEnd) && $taskEnd->gt($slotStart);
    }

    /**
     * التحقق من أن الوقت يقع ضمن فترة البريك
     */
    private function isTimeInBreakPeriod($slotStartTime, $slotEndTime, $breakStart, $breakEnd)
    {
        // إنشاء وقت البريك الكامل للتاريخ المحدد
        $breakStartFull = $slotStartTime->copy()->setTime($breakStart->hour, $breakStart->minute, 0);
        $breakEndFull = $slotStartTime->copy()->setTime($breakEnd->hour, $breakEnd->minute, 0);

        // التعامل مع البريك الذي يمتد لليوم التالي
        if ($breakEndFull->lt($breakStartFull)) {
            $breakEndFull->addDay();
        }

        // التحقق من التداخل
        return $this->isTimeOverlapping($breakStartFull, $breakEndFull, $slotStartTime, $slotEndTime);
    }

    /**
     * الحصول على بيانات الإجازات المتوافق عليها للموظف في تاريخ محدد
     */
    private function getAbsenceData($employeeId, $date)
    {
        if (!$employeeId) {
            return null;
        }

        $absenceRequest = AbsenceRequest::where('user_id', $employeeId)
            ->where('absence_date', $date->format('Y-m-d'))
            ->where('status', 'approved')
            ->with('user')
            ->first();

        if (!$absenceRequest) {
            return null;
        }

        return [
            'id' => $absenceRequest->id,
            'date' => $date->format('Y-m-d'),
            'reason' => $absenceRequest->reason,
            'status' => $absenceRequest->status,
            'manager_status' => $absenceRequest->manager_status,
            'hr_status' => $absenceRequest->hr_status,
            'created_at' => $absenceRequest->created_at,
            'type' => 'absence'
        ];
    }

    /**
     * الحصول على بيانات طلبات الأذون المتوافق عليها للموظف في تاريخ محدد
     */
    private function getPermissionData($employeeId, $date)
    {
        if (!$employeeId) {
            return null;
        }

        $permissionRequests = PermissionRequest::where('user_id', $employeeId)
            ->whereDate('departure_time', $date->format('Y-m-d'))
            ->where('status', 'approved')
            ->with('user')
            ->orderBy('departure_time')
            ->get();

        if ($permissionRequests->isEmpty()) {
            return null;
        }

        $processedPermissions = [];
        $totalMinutesUsed = 0;

        foreach ($permissionRequests as $permission) {
            $departureTime = Carbon::parse($permission->departure_time);
            $returnTime = Carbon::parse($permission->return_time);
            $minutesUsed = $permission->minutes_used ?? $permission->calculateMinutesUsed();
            $totalMinutesUsed += $minutesUsed;

            $processedPermissions[] = [
                'id' => $permission->id,
                'departure_time' => $departureTime,
                'return_time' => $returnTime,
                'departure_time_formatted' => $departureTime->format('H:i'),
                'return_time_formatted' => $returnTime->format('H:i'),
                'reason' => $permission->reason,
                'minutes_used' => $minutesUsed,
                'minutes_formatted' => $this->formatMinutes($minutesUsed),
                'status' => $permission->status,
                'manager_status' => $permission->manager_status,
                'hr_status' => $permission->hr_status,
                'returned_on_time' => $permission->returned_on_time,
                'return_status_label' => $permission->getReturnStatusLabel(),
                'created_at' => $permission->created_at,
                'type' => 'permission'
            ];
        }

        return [
            'requests' => $processedPermissions,
            'total_requests' => count($processedPermissions),
            'total_minutes_used' => $totalMinutesUsed,
            'total_time_formatted' => $this->formatMinutes($totalMinutesUsed),
            'date' => $date->format('Y-m-d')
        ];
    }

    /**
     * الحصول على بيانات التعديلات التي عمل عليها الموظف في تاريخ محدد
     */
    private function getRevisionsData($employeeId, $date)
    {
        if (!$employeeId) {
            return null;
        }

        // الحصول على التعديلات المسندة للموظف في هذا التاريخ (كمنفذ أو مراجع)
        $revisions = TaskRevision::with([
            'creator:id,name',
            'reviewer:id,name',
            'assignedUser:id,name',
            'executorUser:id,name',
            'project:id,name',
            'taskUser.task',
            'templateTaskUser.templateTask'
        ])
        ->where(function($query) use ($employeeId) {
            $query->where('assigned_to', $employeeId) // منفذ مباشر
                ->orWhere('executor_user_id', $employeeId) // منفذ
                ->orWhereRaw("JSON_CONTAINS(reviewers, JSON_OBJECT('reviewer_id', ?), '$')", [$employeeId]) // مراجع
                ->orWhereHas('taskUser', function($q) use ($employeeId) {
                    $q->where('user_id', $employeeId);
                })
                ->orWhereHas('templateTaskUser', function($q) use ($employeeId) {
                    $q->where('user_id', $employeeId);
                });
        })
        ->where(function($query) use ($date, $employeeId) {
            $dateStr = $date->format('Y-m-d');

            // التعديلات التي تم العمل عليها (تنفيذ أو مراجعة):
            $query
                // === للتنفيذ ===
                // 1. بدأت في هذا التاريخ
                ->whereDate('started_at', $dateStr)
                // 2. اكتملت في هذا التاريخ
                ->orWhereDate('completed_at_work', $dateStr)
                // 3. تم إيقافها في هذا التاريخ
                ->orWhereDate('paused_at', $dateStr)
                // 4. استؤنفت في هذا التاريخ
                ->orWhereDate('resumed_at', $dateStr)
                // 5. نشطة حالياً وبدأت قبل أو في هذا التاريخ
                ->orWhere(function($q) use ($dateStr) {
                    $q->where('status', 'in_progress')
                      ->whereNotNull('current_session_start')
                      ->whereDate('current_session_start', '<=', $dateStr);
                })
                // 6. متوقفة ولكن تم العمل عليها في هذا التاريخ
                ->orWhere(function($q) use ($dateStr) {
                    $q->where('status', 'paused')
                      ->whereDate('started_at', '<=', $dateStr)
                      ->where(function($subQ) use ($dateStr) {
                          $subQ->whereDate('paused_at', '>=', $dateStr)
                               ->orWhereNull('paused_at');
                      });
                })
                // === للمراجعة ===
                // 7. بدأت المراجعة في هذا التاريخ
                ->orWhereDate('review_started_at', $dateStr)
                // 8. اكتملت المراجعة في هذا التاريخ
                ->orWhereDate('review_completed_at', $dateStr)
                // 9. تم إيقاف المراجعة في هذا التاريخ
                ->orWhereDate('review_paused_at', $dateStr)
                // 10. استؤنفت المراجعة في هذا التاريخ
                ->orWhereDate('review_resumed_at', $dateStr)
                // 11. المراجعة نشطة حالياً وبدأت قبل أو في هذا التاريخ
                ->orWhere(function($q) use ($dateStr) {
                    $q->where('review_status', 'in_progress')
                      ->whereNotNull('review_current_session_start')
                      ->whereDate('review_current_session_start', '<=', $dateStr);
                })
                // 12. المراجعة متوقفة ولكن تم العمل عليها في هذا التاريخ
                ->orWhere(function($q) use ($dateStr) {
                    $q->where('review_status', 'paused')
                      ->whereDate('review_started_at', '<=', $dateStr)
                      ->where(function($subQ) use ($dateStr) {
                          $subQ->whereDate('review_paused_at', '>=', $dateStr)
                               ->orWhereNull('review_paused_at');
                      });
                });
        })
        ->orderBy('started_at', 'desc')
        ->get();

        if ($revisions->isEmpty()) {
            return null;
        }

        $processedRevisions = [];
        $totalMinutes = 0;

        foreach ($revisions as $revision) {
            // تحديد إذا كان المستخدم منفذ أو مراجع
            $isExecutor = in_array($employeeId, [
                $revision->assigned_to,
                $revision->executor_user_id
            ]);

            // التحقق من كونه مراجع في النظام الجديد
            $isReviewer = false;
            if ($revision->reviewers && is_array($revision->reviewers)) {
                foreach ($revision->reviewers as $reviewerData) {
                    if ($reviewerData['reviewer_id'] == $employeeId) {
                        $isReviewer = true;
                        break;
                    }
                }
            }

            // حساب الوقت المستغرق في هذا التعديل
            $revisionMinutes = 0;
            $workType = '';

            if ($isExecutor) {
                // وقت التنفيذ
                $revisionMinutes = $revision->actual_minutes ?? 0;

                // إضافة الوقت من الجلسة الحالية إذا كانت نشطة
                if ($revision->hasActiveSession()) {
                    $sessionMinutes = abs(now()->diffInMinutes($revision->current_session_start));
                    $revisionMinutes += $sessionMinutes;
                }

                $workType = 'تنفيذ';
            }

            if ($isReviewer) {
                // وقت المراجعة
                $reviewMinutes = $revision->review_actual_minutes ?? 0;

                // إضافة الوقت من جلسة المراجعة الحالية إذا كانت نشطة
                if ($revision->hasActiveReviewSession()) {
                    $sessionMinutes = abs(now()->diffInMinutes($revision->review_current_session_start));
                    $reviewMinutes += $sessionMinutes;
                }

                $revisionMinutes += $reviewMinutes;
                $workType = $isExecutor ? 'تنفيذ ومراجعة' : 'مراجعة';
            }

            $totalMinutes += $revisionMinutes;

            // تحديد اسم المهمة المرتبطة
            $taskName = 'غير محددة';
            if ($revision->task_type === 'regular' && $revision->taskUser && $revision->taskUser->task) {
                $taskName = $revision->taskUser->task->name;
            } elseif ($revision->task_type === 'template' && $revision->templateTaskUser && $revision->templateTaskUser->templateTask) {
                $taskName = $revision->templateTaskUser->templateTask->name;
            }

            $processedRevisions[] = [
                'id' => $revision->id,
                'title' => $revision->title,
                'description' => $revision->description,
                'task_name' => $taskName,
                'task_type' => $revision->task_type,
                'revision_type' => $revision->revision_type,
                'revision_type_text' => $revision->revision_type_text,
                'revision_source' => $revision->revision_source,
                'revision_source_text' => $revision->revision_source_text,
                'status' => $revision->status,
                'review_status' => $revision->review_status ?? 'new',
                'status_text' => $revision->status_text,
                'status_color' => $revision->status_color,
                'project' => $revision->project,
                'created_by' => $revision->creator,
                'assigned_to' => $revision->assignedUser,
                'current_reviewer' => $revision->getCurrentReviewer(),
                'executor_user' => $revision->executorUser,
                'work_type' => $workType, // تنفيذ، مراجعة، أو تنفيذ ومراجعة
                'is_executor' => $isExecutor,
                'is_reviewer' => $isReviewer,
                'started_at' => $revision->started_at,
                'review_started_at' => $revision->review_started_at,
                'completed_at' => $revision->completed_at_work,
                'review_completed_at' => $revision->review_completed_at,
                'paused_at' => $revision->paused_at,
                'review_paused_at' => $revision->review_paused_at,
                'current_session_start' => $revision->current_session_start,
                'review_current_session_start' => $revision->review_current_session_start,
                'actual_minutes' => $revision->actual_minutes ?? 0, // الوقت الفعلي للتنفيذ فقط
                'review_actual_minutes' => $revision->review_actual_minutes ?? 0, // الوقت الفعلي للمراجعة فقط
                'total_minutes' => $revisionMinutes, // الوقت الإجمالي (تنفيذ + مراجعة)
                'actual_time_formatted' => $this->formatMinutes($revisionMinutes),
                'is_active' => $revision->status === 'in_progress' || $revision->review_status === 'in_progress',
            ];
        }

        return [
            'revisions' => $processedRevisions,
            'total_revisions' => count($processedRevisions),
            'total_minutes' => $totalMinutes,
            'total_time_formatted' => $this->formatMinutes($totalMinutes),
            'date' => $date->format('Y-m-d'),
            'active_revision' => collect($processedRevisions)->firstWhere('is_active', true)
        ];
    }

    /**
     * الحصول على المشاريع المسلمة في تاريخ محدد
     */
    private function getDeliveredProjectsData($employeeId, $date)
    {
        if (!$employeeId) {
            return null;
        }

        // البحث عن المشاريع التي تم تسليمها في هذا التاريخ
        $deliveredProjects = ProjectServiceUser::where('user_id', $employeeId)
            ->whereDate('delivered_at', $date->format('Y-m-d'))
            ->with(['project', 'service', 'administrativeApprover', 'technicalApprover'])
            ->get();

        if ($deliveredProjects->isEmpty()) {
            return null;
        }

        $processedProjects = [];

        foreach ($deliveredProjects as $projectServiceUser) {
            // الحصول على الاعتمادات المطلوبة
            $requiredApprovals = $projectServiceUser->getRequiredApprovals();

            $processedProjects[] = [
                'id' => $projectServiceUser->id,
                'project' => $projectServiceUser->project,
                'service' => $projectServiceUser->service,
                'delivered_at' => $projectServiceUser->delivered_at,
                'delivered_time' => $projectServiceUser->delivered_at->format('H:i'),
                'project_share' => $projectServiceUser->project_share,
                'project_share_label' => $projectServiceUser->getProjectShareLabel(),
                'administrative_approval' => $projectServiceUser->administrative_approval,
                'administrative_approval_at' => $projectServiceUser->administrative_approval_at,
                'administrative_approver' => $projectServiceUser->administrativeApprover,
                'technical_approval' => $projectServiceUser->technical_approval,
                'technical_approval_at' => $projectServiceUser->technical_approval_at,
                'technical_approver' => $projectServiceUser->technicalApprover,
                'needs_administrative' => $requiredApprovals['needs_administrative'],
                'needs_technical' => $requiredApprovals['needs_technical'],
                'has_all_approvals' => $projectServiceUser->hasAllRequiredApprovals(),
            ];
        }

        return [
            'projects' => $processedProjects,
            'total_projects' => count($processedProjects),
            'date' => $date->format('Y-m-d')
        ];
    }
}
