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
                            <h3>إحصائيات {{ $user->name }} في المواسم</h3>
                        </div>
                        <div>
                            <form action="{{ route('seasons.statistics.user', ['userId' => $user->id]) }}" method="GET" class="form-inline">
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
                        <h4>إحصائيات {{ $user->name }} في {{ $selectedSeason->name }}</h4>

                        @if(!$statistics)
                            <div class="modern-alert modern-alert-warning animate-scale-in">لا توجد بيانات متاحة لهذا الموظف في هذا الموسم.</div>
                        @else
                            <!-- بطاقة النقاط والشارات -->
                            @if($pointsAndBadges && $pointsAndBadges['season_points'])
                            <div class="row mt-4">
                                <div class="col-md-12 mb-4 animate-scale-in">
                                    <div class="modern-card badge-stats-card">
                                        <div class="modern-card-header" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
                                            <h5 class="mb-0 text-white">
                                                <i class="fas fa-trophy me-2"></i>
                                                🏆 نقاط وشارات {{ $user->name }} في {{ $selectedSeason->name }}
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- النقاط -->
                                                <div class="col-md-8">
                                                    <div class="row">
                                                        <div class="col-md-3 text-center">
                                                            <div class="points-display">
                                                                <div class="points-number">{{ number_format($pointsAndBadges['season_points']['total_points']) }}</div>
                                                                <div class="points-label">إجمالي النقاط</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3 text-center">
                                                            <div class="points-display">
                                                                <div class="points-number">{{ $pointsAndBadges['season_points']['tasks_completed'] }}</div>
                                                                <div class="points-label">مهام مكتملة</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3 text-center">
                                                            <div class="points-display">
                                                                <div class="points-number">{{ $pointsAndBadges['season_points']['projects_completed'] }}</div>
                                                                <div class="points-label">مشاريع مكتملة</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3 text-center">
                                                            <div class="points-display">
                                                                <div class="points-number">{{ number_format($pointsAndBadges['season_points']['minutes_worked'] / 60, 1) }}</div>
                                                                <div class="points-label">ساعات عمل</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- الشارات -->
                                                <div class="col-md-4">
                                                    <div class="current-badge-display text-center">
                                                        @if($pointsAndBadges['season_points']['current_badge'])
                                                            <div class="badge-icon mb-2">
                                                                @if($pointsAndBadges['season_points']['current_badge']->icon)
                                                                    <img src="{{ asset('storage/' . $pointsAndBadges['season_points']['current_badge']->icon) }}"
                                                                         alt="{{ $pointsAndBadges['season_points']['current_badge']->name }}"
                                                                         class="badge-image" style="width: 60px; height: 60px;">
                                                                @else
                                                                    <div class="badge-placeholder" style="width: 60px; height: 60px; background-color: {{ $pointsAndBadges['season_points']['current_badge']->color_code ?? '#ffc107' }}; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                                                        <i class="fas fa-medal text-white" style="font-size: 24px;"></i>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="badge-name">{{ $pointsAndBadges['season_points']['current_badge']->name }}</div>
                                                            <div class="badge-level text-muted">المستوى {{ $pointsAndBadges['season_points']['current_badge']->level }}</div>
                                                        @else
                                                            <div class="no-badge">
                                                                <i class="fas fa-medal text-muted" style="font-size: 48px;"></i>
                                                                <div class="mt-2 text-muted">لا توجد شارة حالية</div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- الشارات الأخيرة -->
                                            @if($pointsAndBadges['recent_badges']->count() > 0)
                                            <hr>
                                            <div class="recent-badges">
                                                <h6 class="mb-3"><i class="fas fa-history me-2"></i>الشارات الأخيرة</h6>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($pointsAndBadges['recent_badges'] as $recentBadge)
                                                    <div class="badge-item-small" title="{{ $recentBadge->badge->name ?? 'شارة' }} - {{ $recentBadge->earned_at ? $recentBadge->earned_at->diffForHumans() : '' }}">
                                                        @if($recentBadge->badge && $recentBadge->badge->icon)
                                                            <img src="{{ asset('storage/' . $recentBadge->badge->icon) }}"
                                                                 alt="{{ $recentBadge->badge->name }}"
                                                                 style="width: 30px; height: 30px; border-radius: 50%;">
                                                        @else
                                                            <div class="badge-placeholder-small" style="width: 30px; height: 30px; background-color: {{ $recentBadge->badge->color_code ?? '#6c757d' }}; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                                                <i class="fas fa-medal text-white" style="font-size: 12px;"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

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

                                            <div class="progress mt-3">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $statistics['projects']['completion_percentage'] }}%;" aria-valuenow="{{ $statistics['projects']['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100">{{ $statistics['projects']['completion_percentage'] }}%</div>
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

                                            <div class="progress mt-3">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $statistics['tasks']['completion_percentage'] }}%;" aria-valuenow="{{ $statistics['tasks']['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100">{{ $statistics['tasks']['completion_percentage'] }}%</div>
                                            </div>
                                            <div class="text-center mt-2">نسبة إكمال المهام</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- إحصائيات مهام القوالب -->
                                <div class="col-md-6 mb-4 animate-fade-in-up" style="animation-delay: 0.2s;">
                                    <div class="stats-card h-100">
                                        <div class="modern-card-header" style="background: var(--info-gradient);">
                                            <h5 class="mb-0">📋 مهام القوالب</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>إجمالي مهام القوالب:</span>
                                                <strong>{{ $statistics['template_tasks']['total'] }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>مهام القوالب المكتملة:</span>
                                                <strong>{{ $statistics['template_tasks']['completed'] }}</strong>
                                            </div>

                                            <div class="progress mt-3">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $statistics['template_tasks']['completion_percentage'] }}%;" aria-valuenow="{{ $statistics['template_tasks']['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100">{{ $statistics['template_tasks']['completion_percentage'] }}%</div>
                                            </div>
                                            <div class="text-center mt-2">نسبة إكمال مهام القوالب</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- إحصائيات الوقت -->
                                <div class="col-md-6 mb-4 animate-fade-in-up" style="animation-delay: 0.3s;">
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
                            </div>

                            <!-- رسم بياني للتقدم -->
                            <div class="modern-card mt-4 animate-scale-in">
                                <div class="modern-card-header" style="background: var(--primary-gradient);">
                                    <h5 class="mb-0">📈 التقدم في الموسم</h5>
                                </div>
                                <div class="card-body chart-container">
                                    <canvas id="progressChart" height="200"></canvas>
                                </div>
                            </div>
                        @endif

                        <div class="mt-4 animate-slide-in-right">
                            <a href="{{ route('seasons.statistics.all-users', ['season_id' => $selectedSeason->id]) }}" class="modern-btn">
                                <i class="fas fa-users"></i> العودة لإحصائيات جميع الموظفين
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($selectedSeason && $statistics)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('progressChart').getContext('2d');

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['المشاريع المكتملة', 'المشاريع المتبقية', 'المهام المكتملة', 'المهام المتبقية'],
                    datasets: [{
                        data: [
                            {{ $statistics['projects']['completed'] }},
                            {{ $statistics['projects']['total'] - $statistics['projects']['completed'] }},
                            {{ $statistics['tasks']['completed'] }},
                            {{ $statistics['tasks']['total'] - $statistics['tasks']['completed'] }}
                        ],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(75, 192, 192, 0.2)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
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
