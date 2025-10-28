@extends('layouts.app')

@section('content')
<div class="season-statistics-container">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 animate-fade-in-up">
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>إحصائيات جميع الموظفين في المواسم</h3>
                        </div>
                        <div>
                            <form action="{{ route('seasons.statistics.all-users') }}" method="GET" class="form-inline">
                                <div class="form-group mx-2">
                                    <label for="season_id" class="ml-2">اختر الموسم:</label>
                                    <select name="season_id" id="season_id" class="modern-form-control" onchange="this.form.submit()">
                                        @foreach($seasons as $season)
                                            <option value="{{ $season->id }}" {{ $selectedSeason && $selectedSeason->id == $season->id ? 'selected' : '' }}>
                                                {{ $season->name }}
                                                @if($season->is_current) (الموسم الحالي) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    @if(!$selectedSeason)
                        <div class="modern-alert modern-alert-info animate-scale-in">الرجاء اختيار موسم لعرض الإحصائيات.</div>
                    @else
                        <h4>إحصائيات الموظفين في {{ $selectedSeason->name }}</h4>

                        @if(!$statistics || empty($statistics['users']))
                            <div class="modern-alert modern-alert-warning animate-scale-in">لا توجد بيانات متاحة لهذا الموسم.</div>
                        @else
                            <!-- فلترة الموظفين -->
                            <div class="mb-4 animate-slide-in-right">
                                <div class="modern-search">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="userFilter" class="form-control" placeholder="البحث عن موظف...">
                                </div>
                            </div>

                            <!-- جدول الموظفين -->
                            <div class="table-responsive animate-fade-in-up">
                                <table class="modern-table table" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>الموظف</th>
                                            <th>النقاط</th>
                                            <th>الشارة الحالية</th>
                                            <th>المشاريع المكتملة</th>
                                            <th>المهام المكتملة</th>
                                            <th>نسبة الإنجاز</th>
                                            <th>الوقت المستغرق</th>
                                            <th>تفاصيل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($statistics['users'] as $userStat)
                                            <tr>
                                                <td>{{ $userStat['user']->name }}</td>

                                                <!-- النقاط -->
                                                <td class="text-center">
                                                    @if(isset($userStat['points_and_badges']) && $userStat['points_and_badges']['season_points'])
                                                        <span class="badge bg-warning text-dark fs-6">
                                                            <i class="fas fa-star me-1"></i>
                                                            {{ number_format($userStat['points_and_badges']['season_points']['total_points']) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">0</span>
                                                    @endif
                                                </td>

                                                <!-- الشارة الحالية -->
                                                <td class="text-center">
                                                    @if(isset($userStat['points_and_badges']) && $userStat['points_and_badges']['season_points']['current_badge'])
                                                        <div class="d-flex align-items-center justify-content-center">
                                                            @if($userStat['points_and_badges']['season_points']['current_badge']->icon)
                                                                <img src="{{ asset('storage/' . $userStat['points_and_badges']['season_points']['current_badge']->icon) }}"
                                                                     alt="{{ $userStat['points_and_badges']['season_points']['current_badge']->name }}"
                                                                     style="width: 25px; height: 25px; border-radius: 50%;"
                                                                     class="me-2">
                                                            @else
                                                                <div class="badge-placeholder-mini me-2" style="width: 25px; height: 25px; background-color: {{ $userStat['points_and_badges']['season_points']['current_badge']->color_code ?? '#ffc107' }}; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                                                    <i class="fas fa-medal text-white" style="font-size: 10px;"></i>
                                                                </div>
                                                            @endif
                                                            <small class="text-muted">{{ $userStat['points_and_badges']['season_points']['current_badge']->name }}</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>

                                                <td>{{ $userStat['projects']['completed'] }}/{{ $userStat['projects']['total'] }}</td>
                                                <td>{{ $userStat['tasks']['completed'] }}/{{ $userStat['tasks']['total'] }}</td>
                                                <td>
                                                    <div class="modern-progress">
                                                        <div class="modern-progress-bar" role="progressbar" style="width: {{ $userStat['tasks']['completion_percentage'] }}%;"
                                                             aria-valuenow="{{ $userStat['tasks']['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                                                            {{ $userStat['tasks']['completion_percentage'] }}%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $userStat['time_spent']['formatted'] }}</td>
                                                <td>
                                                    <a href="{{ route('seasons.statistics.user', ['userId' => $userStat['user_id'], 'season_id' => $selectedSeason->id]) }}"
                                                       class="modern-btn btn-sm">
                                                        <i class="fas fa-eye"></i> تفاصيل
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- رسم بياني تنافسي -->
                            <div class="modern-card mt-5 animate-scale-in">
                                <div class="modern-card-header">
                                    <h5 class="mb-0">📊 الترتيب التنافسي للموظفين</h5>
                                </div>
                                <div class="card-body chart-container">
                                    <canvas id="employeeRankingChart" height="200"></canvas>
                                </div>
                            </div>
                        @endif

                        <div class="mt-4 animate-slide-in-right">
                            <a href="{{ route('seasons.statistics.company', ['season_id' => $selectedSeason->id]) }}" class="modern-btn">
                                <i class="fas fa-chart-line"></i> عرض إحصائيات الشركة
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@if($selectedSeason && $statistics && !empty($statistics['users']))
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // فلترة جدول الموظفين
        document.addEventListener('DOMContentLoaded', function() {
            const userFilter = document.getElementById('userFilter');
            const table = document.getElementById('usersTable');

            if (userFilter) {
                userFilter.addEventListener('keyup', function() {
                    const value = this.value.toLowerCase();
                    const rows = table.querySelectorAll('tbody tr');

                    rows.forEach(row => {
                        const name = row.cells[0].textContent.toLowerCase();
                        row.style.display = name.includes(value) ? '' : 'none';
                    });
                });
            }

            // رسم بياني للموظفين
            const ctx = document.getElementById('employeeRankingChart').getContext('2d');
            const users = @json(array_map(function($u) { return $u['user']->name; }, $statistics['users']));
            const completedTasks = @json(array_map(function($u) { return $u['tasks']['completed']; }, $statistics['users']));
            const completionPercentages = @json(array_map(function($u) { return $u['tasks']['completion_percentage']; }, $statistics['users']));

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: users,
                    datasets: [{
                        label: 'المهام المكتملة',
                        data: completedTasks,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }, {
                        label: 'نسبة الإنجاز %',
                        data: completionPercentages,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
    @endpush
@endif
@endsection

@push('styles')
<link href="{{ asset('css/season-statistics.css') }}" rel="stylesheet">
@endpush
