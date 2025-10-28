@php
// Prepare the statistics data for JavaScript
// Personal statistics
$personalData = json_encode([
'total_requests' => $personalStatistics['total_requests'] ?? 0,
'approved_requests' => $personalStatistics['approved_requests'] ?? 0,
'pending_requests' => $personalStatistics['pending_requests'] ?? 0,
]);

// Team statistics
$teamData = '';
if (Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']) && !empty($teamStatistics)) {
$teamData = json_encode([
'total_requests' => $teamStatistics['total_requests'] ?? 0,
'approved_requests' => $teamStatistics['approved_requests'] ?? 0,
'pending_requests' => $teamStatistics['pending_requests'] ?? 0,
]);
}

// HR statistics
$hrData = $dayOfWeekData = $monthlyTrendsData = $departmentsData = '';
if (Auth::user()->hasRole('hr') && !empty($hrStatistics)) {
$hrData = json_encode([
'total_company_requests' => $hrStatistics['total_company_requests'] ?? 0,
'pending_requests' => $hrStatistics['pending_requests'] ?? 0,
'rejected_requests' => $hrStatistics['rejected_requests'] ?? 0,
]);

// Day of week data
$dayOfWeekData = json_encode($hrStatistics['day_of_week_stats'] ?? []);

// Monthly trends data
$monthlyTrendsData = json_encode($hrStatistics['monthly_trends'] ?? []);

// Departments data
$departmentsData = json_encode($hrStatistics['departments_stats'] ?? []);
}
@endphp

@push('scripts')
<script>
    // Add data attributes to chart elements
    document.addEventListener('DOMContentLoaded', function() {
        // Personal statistics
        const personalStatsChart = document.getElementById('personalStatsChart');
        if (personalStatsChart) {
            personalStatsChart.setAttribute('data-statistics', '{!! $personalData !!}');
        }

        // Team statistics
        const teamStatsChart = document.getElementById('teamStatsChart');
        if (teamStatsChart && '{!! $teamData !!}' !== '') {
            teamStatsChart.setAttribute('data-statistics', '{!! $teamData !!}');
        }

        // HR statistics
        const hrStatsChart = document.getElementById('hrStatsChart');
        if (hrStatsChart && '{!! $hrData !!}' !== '') {
            hrStatsChart.setAttribute('data-statistics', '{!! $hrData !!}');
        }

        // Day of week statistics
        const dayOfWeekChart = document.getElementById('dayOfWeekChart');
        if (dayOfWeekChart && '{!! $dayOfWeekData !!}' !== '') {
            dayOfWeekChart.setAttribute('data-statistics', '{!! $dayOfWeekData !!}');
        }

        // Monthly trends
        const monthlyTrendsChart = document.getElementById('monthlyTrendsChart');
        if (monthlyTrendsChart && '{!! $monthlyTrendsData !!}' !== '') {
            monthlyTrendsChart.setAttribute('data-statistics', '{!! $monthlyTrendsData !!}');
        }

        // Departments statistics
        const deptStatsChart = document.getElementById('departmentsStatsChart');
        if (deptStatsChart && '{!! $departmentsData !!}' !== '') {
            deptStatsChart.setAttribute('data-statistics', '{!! $departmentsData !!}');
        }
    });
</script>
@endpush
