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
                            <h3>إحصائيات الشركة في المواسم</h3>
                        </div>
                        <div>
                            <form action="{{ route('seasons.statistics.company') }}" method="GET" class="form-inline">
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
                        <h4>إحصائيات الشركة في {{ $selectedSeason->name }}</h4>

                        @if(!$statistics)
                            <div class="modern-alert modern-alert-warning animate-scale-in">لا توجد بيانات متاحة لهذا الموسم.</div>
                        @else
                            <div class="row mt-4">
                                <!-- إحصائيات المشاريع -->
                                <div class="col-md-6 mb-4 animate-fade-in-up">
                                    <div class="stats-card h-100">
                                        <div class="modern-card-header" style="background: var(--primary-gradient);">
                                            <h5 class="mb-0">📁 المشاريع</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>إجمالي المشاريع:</span>
                                                <strong>{{ $statistics['projects']['total'] }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>المشاريع المكتملة:</span>
                                                <strong>{{ $statistics['projects']['completed'] }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>المشاريع قيد التنفيذ:</span>
                                                <strong>{{ $statistics['projects']['in_progress'] }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>المشاريع الجديدة:</span>
                                                <strong>{{ $statistics['projects']['new'] }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>المشاريع الملغاة:</span>
                                                <strong>{{ $statistics['projects']['cancelled'] }}</strong>
                                            </div>

                                            <div class="modern-progress mt-3">
                                                <div class="modern-progress-bar" role="progressbar" style="width: {{ $statistics['projects']['completion_percentage'] }}%;" aria-valuenow="{{ $statistics['projects']['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100">{{ $statistics['projects']['completion_percentage'] }}%</div>
                                            </div>
                                            <div class="text-center mt-2">نسبة إكمال المشاريع</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- إحصائيات المهام -->
                                <div class="col-md-6 mb-4 animate-fade-in-up" style="animation-delay: 0.1s;">
                                    <div class="stats-card h-100">
                                        <div class="modern-card-header" style="background: var(--success-gradient);">
                                            <h5 class="mb-0">✅ المهام</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>إجمالي المهام:</span>
                                                <strong>{{ $statistics['tasks']['total'] }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>المهام المكتملة:</span>
                                                <strong>{{ $statistics['tasks']['completed'] }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>المهام قيد التنفيذ:</span>
                                                <strong>{{ $statistics['tasks']['in_progress'] }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>المهام الجديدة:</span>
                                                <strong>{{ $statistics['tasks']['new'] }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>المهام المتوقفة مؤقتًا:</span>
                                                <strong>{{ $statistics['tasks']['paused'] }}</strong>
                                            </div>

                                            <div class="modern-progress mt-3">
                                                <div class="modern-progress-bar" style="background: var(--success-gradient);" role="progressbar" style="width: {{ $statistics['tasks']['completion_percentage'] }}%;" aria-valuenow="{{ $statistics['tasks']['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100">{{ $statistics['tasks']['completion_percentage'] }}%</div>
                                            </div>
                                            <div class="text-center mt-2">نسبة إكمال المهام</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- إحصائيات الوقت -->
                                <div class="col-md-6 mb-4 animate-fade-in-up" style="animation-delay: 0.2s;">
                                    <div class="stats-card h-100">
                                        <div class="modern-card-header" style="background: var(--warning-gradient);">
                                            <h5 class="mb-0">⏰ الوقت المستغرق</h5>
                                        </div>
                                        <div class="card-body text-center">
                                            <div class="display-4 mb-3">{{ $statistics['time_spent']['formatted'] }}</div>
                                            <div class="text-muted">إجمالي الوقت المستغرق في هذا الموسم</div>

                                            <div class="mt-4">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>إجمالي الساعات:</span>
                                                    <strong>{{ $statistics['time_spent']['hours'] }} ساعة</strong>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>إجمالي الدقائق:</span>
                                                    <strong>{{ $statistics['time_spent']['minutes'] }} دقيقة</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- أفضل المستخدمين -->
                                <div class="col-md-6 mb-4 animate-fade-in-up" style="animation-delay: 0.3s;">
                                    <div class="stats-card h-100">
                                        <div class="modern-card-header" style="background: var(--info-gradient);">
                                            <h5 class="mb-0">🏆 أفضل الموظفين أداءً</h5>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="mb-3">الموظفين حسب عدد المهام المكتملة:</h6>
                                            <ul class="list-group">
                                                @forelse($statistics['top_users'] as $user)
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>{{ $user->user->name }}</span>
                                                        <span class="badge badge-primary badge-pill">{{ $user->completed_tasks }} مهام</span>
                                                    </li>
                                                @empty
                                                    <li class="list-group-item">لا توجد بيانات متاحة</li>
                                                @endforelse
                                            </ul>

                                            <h6 class="mb-3 mt-4">الموظفين حسب الوقت المستغرق:</h6>
                                            <ul class="list-group">
                                                @forelse($statistics['users_by_time'] as $user)
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>{{ $user->user->name }}</span>
                                                        <span class="badge badge-info badge-pill">{{ floor($user->total_time / 60) }}h {{ $user->total_time % 60 }}m</span>
                                                    </li>
                                                @empty
                                                    <li class="list-group-item">لا توجد بيانات متاحة</li>
                                                @endforelse
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- إحصائيات الشارات والنقاط -->
                            @if($badgeStats)
                            <div class="row mt-5">
                                <div class="col-md-12 mb-4 animate-scale-in">
                                    <div class="modern-card badge-stats-card success-border">
                                        <div class="modern-card-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                            <h5 class="mb-0 text-white">
                                                <i class="fas fa-trophy me-2"></i>
                                                🏆 إحصائيات الشارات والنقاط في {{ $selectedSeason->name }}
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- إحصائيات عامة -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100 bg-light">
                                                        <div class="card-header bg-info text-white">
                                                            <h6 class="mb-0">الإحصائيات العامة</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between mb-3">
                                                                <span><i class="fas fa-users me-2"></i>الموظفون الحاصلون على نقاط:</span>
                                                                <strong class="text-primary">{{ $badgeStats['total_users_with_badges'] }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- أفضل الموظفين -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100 bg-light">
                                                        <div class="card-header bg-warning text-dark">
                                                            <h6 class="mb-0">🏆 أفضل 5 موظفين</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            @if($badgeStats['top_users']->count() > 0)
                                                                @foreach($badgeStats['top_users']->take(5) as $index => $topUser)
                                                                <div class="d-flex align-items-center mb-3 {{ $index < 3 ? 'border-bottom pb-2' : '' }}">
                                                                    <div class="rank-badge me-3">
                                                                        @if($index == 0)
                                                                            <span class="badge bg-warning text-dark fs-6">🥇 #1</span>
                                                                        @elseif($index == 1)
                                                                            <span class="badge bg-secondary text-white fs-6">🥈 #2</span>
                                                                        @elseif($index == 2)
                                                                            <span class="badge bg-warning text-dark fs-6">🥉 #3</span>
                                                                        @else
                                                                            <span class="badge bg-light text-dark fs-6">#{{ $index + 1 }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <div class="fw-bold">{{ $topUser->name }}</div>
                                                                        <div class="text-muted small">
                                                                            <i class="fas fa-star text-warning me-1"></i>{{ number_format($topUser->total_points) }} نقطة
                                                                            | <i class="fas fa-tasks text-info me-1"></i>{{ $topUser->tasks_completed }} مهمة
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                @endforeach
                                                            @else
                                                                <div class="text-center text-muted">
                                                                    <i class="fas fa-info-circle"></i>
                                                                    لا توجد بيانات متاحة
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- رسم بياني للنقاط -->
                                            @if($badgeStats['top_users']->count() > 0)
                                            <div class="row mt-4">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-header bg-primary text-white">
                                                            <h6 class="mb-0">📊 توزيع النقاط - أفضل 10 موظفين</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <canvas id="topUsersChart" height="300"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="mt-4 animate-slide-in-right">
                                <a href="{{ route('seasons.statistics.all-users', ['season_id' => $selectedSeason->id]) }}" class="modern-btn">
                                    <i class="fas fa-users"></i> عرض إحصائيات جميع الموظفين
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($selectedSeason && $statistics && $badgeStats && $badgeStats['top_users']->count() > 0)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // رسم بياني لأفضل الموظفين من حيث النقاط
            const topUsersCtx = document.getElementById('topUsersChart').getContext('2d');
            const topUsers = @json($badgeStats['top_users']->take(10)->values());

            const userNames = topUsers.map(user => user.name);
            const userPoints = topUsers.map(user => user.total_points);
            const userTasks = topUsers.map(user => user.tasks_completed);

            new Chart(topUsersCtx, {
                type: 'bar',
                data: {
                    labels: userNames,
                    datasets: [{
                        label: 'النقاط',
                        data: userPoints,
                        backgroundColor: 'rgba(255, 193, 7, 0.8)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    }, {
                        label: 'المهام المكتملة',
                        data: userTasks,
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'أفضل الموظفين - النقاط والمهام المكتملة'
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 0
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'النقاط'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'المهام المكتملة'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        });
    </script>
    @endpush
@endif

@push('styles')
<link href="{{ asset('css/season-statistics.css') }}" rel="stylesheet">
@endpush
