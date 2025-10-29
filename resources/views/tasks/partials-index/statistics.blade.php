@php
    // حساب الإحصائيات لصفحة المهام الرئيسية
    $statsData = [
        'total' => 0,
        'new' => 0,
        'in_progress' => 0,
        'paused' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'estimated_hours' => 0,
        'actual_hours' => 0,
        'total_points' => 0,
        'total_users' => 0
    ];

    foreach($tasks as $task) {
        $statsData['total']++;

        // تحديد الحالة
        $status = 'new';
        if (isset($task->user_status) && $task->user_status !== 'not_assigned') {
            $status = $task->user_status;
        } elseif (isset($task->pivot->status)) {
            $status = $task->pivot->status;
        } elseif (isset($task->status)) {
            $status = $task->status;
        }

        // عد المهام حسب الحالة
        if (isset($statsData[$status])) {
            $statsData[$status]++;
        }

        // حساب الوقت
        $isTemplate = isset($task->is_template) && $task->is_template;

        if ($isTemplate) {
            $estimatedHours = $task->estimated_hours ?? 0;
            $estimatedMinutes = $task->estimated_minutes ?? 0;
            $actualHours = $task->actual_hours ?? 0;
            $actualMinutes = $task->actual_minutes ?? 0;
        } else {
            $estimatedHours = isset($task->pivot->estimated_hours) ? $task->pivot->estimated_hours : ($task->estimated_hours ?? 0);
            $estimatedMinutes = isset($task->pivot->estimated_minutes) ? $task->pivot->estimated_minutes : ($task->estimated_minutes ?? 0);
            $actualHours = isset($task->pivot->actual_hours) ? $task->pivot->actual_hours : ($task->actual_hours ?? 0);
            $actualMinutes = isset($task->pivot->actual_minutes) ? $task->pivot->actual_minutes : ($task->actual_minutes ?? 0);
        }

        // تحويل لساعات عشرية
        $statsData['estimated_hours'] += $estimatedHours + ($estimatedMinutes / 60);
        $statsData['actual_hours'] += $actualHours + ($actualMinutes / 60);

        // حساب النقاط
        $statsData['total_points'] += $task->points ?? 10;

        // حساب عدد المستخدمين
        if (isset($task->users) && $task->users->count() > 0) {
            $statsData['total_users'] += $task->users->count();
        }
    }

    // حساب النسب المئوية
    $completionPercentage = $statsData['total'] > 0 ? round(($statsData['completed'] / $statsData['total']) * 100) : 0;
    $inProgressPercentage = $statsData['total'] > 0 ? round(($statsData['in_progress'] / $statsData['total']) * 100) : 0;
    $timeEfficiencyPercentage = $statsData['estimated_hours'] > 0 ? round(($statsData['actual_hours'] / $statsData['estimated_hours']) * 100) : 0;
@endphp

<!-- Statistics Cards -->
<div class="stats-cards-container mb-4">
    <!-- Total Tasks Card -->
    <div class="stats-card total-tasks" data-filter="all">
        <div class="stats-card-header">
            <div class="stats-card-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stats-card-badge">الكل</div>
        </div>
        <div class="stats-card-body">
            <div class="stats-card-title">إجمالي المهام</div>
            <div class="stats-card-value" id="stat-total">{{ $statsData['total'] }}</div>
        </div>
        <div class="stats-card-footer">
            <div class="stats-progress">
                <div class="stats-progress-bar">
                    <div class="stats-progress-fill" style="width: 100%;"></div>
                </div>
            </div>
            <span class="stats-percentage">100%</span>
        </div>
    </div>

    <!-- New Tasks Card -->
    <div class="stats-card new-tasks" data-filter="new">
        <div class="stats-card-header">
            <div class="stats-card-icon">
                <i class="fas fa-circle-plus"></i>
            </div>
            <div class="stats-card-badge">جديدة</div>
        </div>
        <div class="stats-card-body">
            <div class="stats-card-title">مهام جديدة</div>
            <div class="stats-card-value" id="stat-new">{{ $statsData['new'] }}</div>
        </div>
        <div class="stats-card-footer">
            <div class="stats-progress">
                <div class="stats-progress-bar">
                    <div class="stats-progress-fill" style="width: {{ $statsData['total'] > 0 ? round(($statsData['new'] / $statsData['total']) * 100) : 0 }}%;"></div>
                </div>
            </div>
            <span class="stats-percentage">{{ $statsData['total'] > 0 ? round(($statsData['new'] / $statsData['total']) * 100) : 0 }}%</span>
        </div>
    </div>

    <!-- In Progress Tasks Card -->
    <div class="stats-card in-progress-tasks" data-filter="in_progress">
        <div class="stats-card-header">
            <div class="stats-card-icon">
                <i class="fas fa-play-circle"></i>
            </div>
            <div class="stats-card-badge">قيد التنفيذ</div>
        </div>
        <div class="stats-card-body">
            <div class="stats-card-title">قيد التنفيذ</div>
            <div class="stats-card-value" id="stat-in-progress">{{ $statsData['in_progress'] }}</div>
        </div>
        <div class="stats-card-footer">
            <div class="stats-progress">
                <div class="stats-progress-bar">
                    <div class="stats-progress-fill" style="width: {{ $inProgressPercentage }}%;"></div>
                </div>
            </div>
            <span class="stats-percentage">{{ $inProgressPercentage }}%</span>
        </div>
    </div>

    <!-- Completed Tasks Card -->
    <div class="stats-card completed-tasks" data-filter="completed">
        <div class="stats-card-header">
            <div class="stats-card-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stats-card-badge">مكتملة</div>
        </div>
        <div class="stats-card-body">
            <div class="stats-card-title">مهام مكتملة</div>
            <div class="stats-card-value" id="stat-completed">{{ $statsData['completed'] }}</div>
        </div>
        <div class="stats-card-footer">
            <div class="stats-progress">
                <div class="stats-progress-bar">
                    <div class="stats-progress-fill" style="width: {{ $completionPercentage }}%;"></div>
                </div>
            </div>
            <span class="stats-percentage">{{ $completionPercentage }}%</span>
        </div>
    </div>

    <!-- Paused Tasks Card -->
    <div class="stats-card paused-tasks" data-filter="paused">
        <div class="stats-card-header">
            <div class="stats-card-icon">
                <i class="fas fa-pause-circle"></i>
            </div>
            <div class="stats-card-badge">متوقفة</div>
        </div>
        <div class="stats-card-body">
            <div class="stats-card-title">مهام متوقفة</div>
            <div class="stats-card-value" id="stat-paused">{{ $statsData['paused'] }}</div>
        </div>
        <div class="stats-card-footer">
            <div class="stats-progress">
                <div class="stats-progress-bar">
                    <div class="stats-progress-fill" style="width: {{ $statsData['total'] > 0 ? round(($statsData['paused'] / $statsData['total']) * 100) : 0 }}%;"></div>
                </div>
            </div>
            <span class="stats-percentage">{{ $statsData['total'] > 0 ? round(($statsData['paused'] / $statsData['total']) * 100) : 0 }}%</span>
        </div>
    </div>

    <!-- Time Tracking Card -->
    <div class="stats-card time-tracking">
        <div class="stats-card-header">
            <div class="stats-card-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stats-card-badge">الوقت</div>
        </div>
        <div class="stats-card-body">
            <div class="stats-card-title">متابعة الوقت</div>
            <div class="time-comparison">
                <div class="time-item">
                    <span class="time-label">مقدر:</span>
                    <span class="time-value">{{ floor($statsData['estimated_hours']) }}س {{ round(($statsData['estimated_hours'] - floor($statsData['estimated_hours'])) * 60) }}د</span>
                </div>
                <div class="time-item">
                    <span class="time-label">فعلي:</span>
                    <span class="time-value">{{ floor($statsData['actual_hours']) }}س {{ round(($statsData['actual_hours'] - floor($statsData['actual_hours'])) * 60) }}د</span>
                </div>
            </div>
        </div>
        <div class="stats-card-footer">
            <div class="stats-progress">
                <div class="stats-progress-bar">
                    <div class="stats-progress-fill" style="width: {{ min($timeEfficiencyPercentage, 100) }}%;"></div>
                </div>
            </div>
            <span class="stats-percentage">{{ $timeEfficiencyPercentage }}%</span>
        </div>
    </div>

    <!-- Points Card -->
    <div class="stats-card points-card">
        <div class="stats-card-header">
            <div class="stats-card-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stats-card-badge">النقاط</div>
        </div>
        <div class="stats-card-body">
            <div class="stats-card-title">إجمالي النقاط</div>
            <div class="stats-card-value" id="stat-points">{{ $statsData['total_points'] }}</div>
        </div>
        <div class="stats-card-footer">
            <div class="stats-progress">
                <div class="stats-progress-bar">
                    <div class="stats-progress-fill" style="width: 100%;"></div>
                </div>
            </div>
            <span class="stats-percentage">{{ $statsData['total'] > 0 ? round($statsData['total_points'] / $statsData['total'], 1) : 0 }} نقطة/مهمة</span>
        </div>
    </div>
</div>

