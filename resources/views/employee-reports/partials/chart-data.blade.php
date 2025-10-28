@php

$dateLabelsJson = [];
$timeDataJson = [];
$ganttTasksData = [];

if(isset($selectedDate) && isset($taskData)) {
    $startDate = $selectedDate;
    $endDate = $selectedDate;
    $dateStr = $selectedDate->format('Y-m-d');
    $dateLabelsJson[] = $dateStr;

    $totalMinutesForDay = 0;
    foreach ($taskData as $data) {
        if (isset($data['ganttData'][$dateStr])) {
            $totalMinutesForDay += $data['ganttData'][$dateStr]['minutes'];
        } else {
            // إضافة الوقت المسجل للمهمة حتى لو لم تكن في ganttData
            $totalMinutesForDay += ($data['totalTime']['hours'] ?? 0) * 60 + ($data['totalTime']['minutes'] ?? 0);
        }
    }

    $timeDataJson[] = $totalMinutesForDay / 60;

    foreach ($taskData as $index => $data) {
        $task = $data['task'] ?? null;
        $taskUser = $data['taskUser'] ?? null;

        if (!$task) continue;

        $taskStartDate = null;
        $taskEndDate = null;

        // بما أننا نعرض يوم واحد فقط، نستخدم التاريخ المحدد
        $taskStartDate = $selectedDate->format('Y-m-d');
        $taskEndDate = $selectedDate->format('Y-m-d');



        $progress = 0;
        switch ($data['status']) {
            case 'completed':
                $progress = 100;
                break;
            case 'in_progress':
                $progress = 60;
                break;
            case 'paused':
                $progress = 30;
                break;
            default:
                $progress = 10;
        }

        $totalMinutes = ($data['totalTime']['hours'] ?? 0) * 60 + ($data['totalTime']['minutes'] ?? 0);
        $timeSpentFormatted = $data['totalTime']['formatted'] ?? '0h 0m';

        $ganttTasksData[] = [
            'id' => 'task_' . $index,
            'name' => $task->name,
            'start' => $taskStartDate,
            'end' => $taskEndDate,
            'progress' => $progress,
            'status' => $data['status'],
            'type' => $data['type'] ?? 'regular',
            'time_spent' => $timeSpentFormatted,
            'project_name' => $data['project'] ? $data['project']->name : 'لا يوجد مشروع',
            'total_minutes' => $totalMinutes,
            'custom_class' => 'task-status-' . $data['status'] . ' task-type-' . ($data['type'] ?? 'regular')
        ];
    }
}

$chartData = [
    'taskData' => $taskData ?? [],
    'ganttTasks' => $ganttTasksData,
    'startDate' => isset($startDate) ? $startDate->format('Y-m-d') : null,
    'endDate' => isset($endDate) ? $endDate->format('Y-m-d') : null,
    'taskStatusData' => [
        'completed' => $completedTasks ?? 0,
        'inProgress' => $inProgressTasks ?? 0,
        'paused' => $pausedTasks ?? 0,
        'new' => $newTasks ?? 0,
        'cancelled' => $cancelledTasks ?? 0
    ],
    'timeData' => [
        'labels' => $dateLabelsJson,
        'values' => $timeDataJson
    ]
];

$chartDataJson = json_encode($chartData);
@endphp

<script>
    // بيانات المخططات
    window.employeeReportData = {!! $chartDataJson !!};

    // Debug: طباعة البيانات للتأكد من وصولها
    console.log('Employee Report Data:', window.employeeReportData);
    console.log('Task Data Count:', window.employeeReportData.taskData ? window.employeeReportData.taskData.length : 0);
    console.log('Gantt Tasks Count:', window.employeeReportData.ganttTasks ? window.employeeReportData.ganttTasks.length : 0);
</script>
