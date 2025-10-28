@extends('layouts.app')

@section('title', 'تفاصيل تقييم KPI')

@push('styles')
<link href="{{ asset('css/evaluation-criteria-modern.css') }}" rel="stylesheet">
<style>
    .deleted-criterion {
        opacity: 0.7;
        background-color: #fff3cd !important;
        border-left: 4px solid #ffc107;
    }
    .deleted-criterion .criteria-name {
        color: #856404;
    }
</style>
@endpush

@section('content')
<div class="evaluation-container">
    <!-- Header Section -->
    <div class="modern-card modern-card-header-white mb-4" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
        <div class="modern-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="icon-container me-3">
                    <i class="fas fa-file-alt floating" style="font-size: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                </div>
                <div>
                    <h2 class="mb-1 gradient-text">📈 تفاصيل تقييم KPI</h2>
                    <p class="text-muted mb-0">عرض تفاصيل ونتائج تقييم أداء الموظف بناءً على KPI</p>
                </div>
            </div>
            <div>
                <a href="{{ route('kpi-evaluation.index') }}" class="btn btn-modern btn-outline-secondary me-2">
                    <i class="fas fa-arrow-right me-2"></i>
                    العودة للقائمة
                </a>
                <a href="{{ route('kpi-evaluation.create') }}" class="btn btn-modern btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    تقييم جديد
                </a>
            </div>
        </div>
    </div>

    <!-- Evaluation Overview -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        معلومات التقييم
                    </h4>
                </div>
                <div class="modern-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="info-label">الموظف المُقيّم:</label>
                                <div class="info-value">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3">
                                            {{ substr($kpiEvaluation->user->name ?? 'N/A', 0, 1) }}
                                        </div>
                                        <div>
                                            <strong>{{ $kpiEvaluation->user->name ?? 'N/A' }}</strong>
                                            @if($kpiEvaluation->user->department)
                                                <br><small class="text-muted">{{ $kpiEvaluation->user->department }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="info-item mb-3">
                                <label class="info-label">المُقيِّم:</label>
                                <div class="info-value">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3" style="background: linear-gradient(135deg, #00b894 0%, #00a085 100%);">
                                            {{ substr($kpiEvaluation->reviewer->name ?? 'N/A', 0, 1) }}
                                        </div>
                                        <div>
                                            <strong>{{ $kpiEvaluation->reviewer->name ?? 'N/A' }}</strong>
                                            @if($kpiEvaluation->reviewer->department)
                                                <br><small class="text-muted">{{ $kpiEvaluation->reviewer->department }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="info-item mb-3">
                                <label class="info-label">الدور:</label>
                                <div class="info-value">
                                    <span class="badge-modern">
                                        {{ $kpiEvaluation->role->display_name ?? $kpiEvaluation->role->name ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="info-label">شهر التقييم:</label>
                                <div class="info-value">
                                    @if($kpiEvaluation->review_month)
                                        @php
                                            try {
                                                $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $kpiEvaluation->review_month);
                                                echo '<strong>' . $monthDate->locale('ar')->translatedFormat('F Y') . '</strong>';
                                            } catch (\Exception $e) {
                                                echo '<strong>' . $kpiEvaluation->review_month . '</strong>';
                                            }
                                        @endphp
                                    @else
                                        <strong class="text-muted">غير محدد</strong>
                                    @endif
                                </div>
                            </div>

                            <div class="info-item mb-3">
                                <label class="info-label">تاريخ الإنشاء:</label>
                                <div class="info-value">
                                    {{ $kpiEvaluation->created_at->locale('ar')->translatedFormat('l، d F Y \ف\ي H:i') }}
                                    <br><small class="text-muted">{{ $kpiEvaluation->created_at->locale('ar')->diffForHumans() }}</small>
                                </div>
                            </div>

                            @if($kpiEvaluation->notes)
                                <div class="info-item mb-3">
                                    <label class="info-label">ملاحظات:</label>
                                    <div class="info-value">
                                        <div class="notes-box">
                                            {{ $kpiEvaluation->notes }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Score Summary -->
            <div class="stats-card mb-3">
                <div class="stats-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number score-main">{{ $kpiEvaluation->total_after_deductions }}</h3>
                    <p class="stats-label">النقاط النهائية</p>
                </div>
                <div class="score-rating">
                    @if($kpiEvaluation->total_after_deductions >= 90)
                        <span class="rating excellent">ممتاز</span>
                    @elseif($kpiEvaluation->total_after_deductions >= 80)
                        <span class="rating very-good">جيد جداً</span>
                    @elseif($kpiEvaluation->total_after_deductions >= 70)
                        <span class="rating good">جيد</span>
                    @elseif($kpiEvaluation->total_after_deductions >= 60)
                        <span class="rating satisfactory">مقبول</span>
                    @else
                        <span class="rating needs-improvement">يحتاج تحسين</span>
                    @endif
                </div>
            </div>

            <div class="modern-card">
                <div class="modern-card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-chart-pie me-2"></i>
                        ملخص النقاط
                    </h6>

                    <div class="score-breakdown">
                        <div class="score-item">
                            <span class="score-label">النقاط الأساسية:</span>
                            <span class="score-value positive">{{ $kpiEvaluation->total_score }}</span>
                        </div>

                        @if($kpiEvaluation->total_bonus > 0)
                            <div class="score-item">
                                <span class="score-label">النقاط الإضافية:</span>
                                <span class="score-value bonus">+{{ $kpiEvaluation->total_bonus }}</span>
                            </div>
                        @endif

                        @if($kpiEvaluation->total_deductions > 0)
                            <div class="score-item">
                                <span class="score-label">الخصومات:</span>
                                <span class="score-value negative">-{{ $kpiEvaluation->total_deductions }}</span>
                            </div>
                        @endif

                        <hr class="my-2">
                        <div class="score-item total">
                            <span class="score-label"><strong>المجموع النهائي:</strong></span>
                            <span class="score-value final"><strong>{{ $kpiEvaluation->total_after_deductions }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Detailed Criteria Scores -->
    @if($criteriaWithScores && $criteriaWithScores->isNotEmpty())
        <div class="modern-card">
            <div class="modern-card-header">
                <h4 class="mb-0">
                    <i class="fas fa-list-ul me-2"></i>
                    تفاصيل بنود التقييم
                </h4>
            </div>
            <div class="modern-card-body p-0">
                @foreach($criteriaWithScores->groupBy('criteria_type') as $type => $criteria)
                    <div class="criteria-section">
                        <div class="criteria-header">
                            @switch($type)
                                @case('positive')
                                    <i class="fas fa-plus-circle text-success me-2"></i>
                                    <span>البنود الإيجابية</span>
                                    @break
                                @case('negative')
                                    <i class="fas fa-minus-circle text-danger me-2"></i>
                                    <span>البنود السلبية (خصومات)</span>
                                    @break
                                @case('bonus')
                                    <i class="fas fa-star text-warning me-2"></i>
                                    <span>البنود الإضافية (مكافآت)</span>
                                    @break
                            @endswitch
                        </div>

                        <div class="criteria-list">
                            @foreach($criteria as $criterion)
                                <div class="criteria-item {{ isset($criterion->is_historical) && $criterion->is_historical && str_contains($criterion->criteria_name, 'محذوف') ? 'deleted-criterion' : '' }}">
                                    <div class="criteria-info">
                                        <h6 class="criteria-name">
                                            {{ $criterion->criteria_name }}
                                            @if(isset($criterion->is_historical) && $criterion->is_historical && str_contains($criterion->criteria_name, 'محذوف'))
                                                <span class="badge bg-warning text-dark ms-2">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    بند محذوف
                                                </span>
                                            @endif
                                        </h6>
                                        @if($criterion->criteria_description)
                                            <p class="criteria-description">{{ $criterion->criteria_description }}</p>
                                        @endif
                                        @if(isset($criterion->note) && !empty($criterion->note))
                                            <div class="criteria-note mt-2">
                                                <div class="note-container p-2" style="background-color: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">
                                                    <small class="text-muted d-block mb-1">
                                                        <i class="fas fa-sticky-note me-1"></i>
                                                        ملاحظة المُقيِّم:
                                                    </small>
                                                    <span class="note-text" style="font-size: 13px; line-height: 1.4;">{{ $criterion->note }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="criteria-score">
                                        <div class="score-display {{ $type }}">
                                            <span class="score">{{ $criterion->score }}</span>
                                            <span class="max-score">/ {{ $criterion->max_points }}</span>
                                        </div>
                                        <div class="score-percentage">
                                            @php
                                                $score = (int) $criterion->score;
                                                $maxPoints = (int) $criterion->max_points;
                                                $percentage = $maxPoints > 0 ? round(($score / $maxPoints) * 100) : 0;
                                            @endphp
                                            {{ $percentage }}%
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<style>
.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

.info-item {
    padding: 0.5rem 0;
}

.info-label {
    font-weight: 600;
    color: var(--color-muted);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    display: block;
}

.info-value {
    font-size: 1rem;
}

.notes-box {
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 10px;
    padding: 1rem;
    font-style: italic;
}

.score-main {
    font-size: 3rem !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.score-rating {
    margin-top: 0.5rem;
}

.rating {
    padding: 0.3rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
    font-weight: bold;
    text-transform: uppercase;
}

.rating.excellent { background: #00b894; color: white; }
.rating.very-good { background: #00a3ff; color: white; }
.rating.good { background: #fdcb6e; color: white; }
.rating.satisfactory { background: #fd79a8; color: white; }
.rating.needs-improvement { background: #e17055; color: white; }

.score-breakdown {
    font-size: 0.9rem;
}

.score-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.score-value.positive { color: #00b894; font-weight: bold; }
.score-value.bonus { color: #fdcb6e; font-weight: bold; }
.score-value.negative { color: #e17055; font-weight: bold; }
.score-value.final { color: #667eea; font-weight: bold; font-size: 1.1rem; }

.criteria-section {
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.criteria-section:last-child {
    border-bottom: none;
}

.criteria-header {
    padding: 1rem;
    background: rgba(102, 126, 234, 0.05);
    font-weight: 600;
    display: flex;
    align-items: center;
}

.criteria-list {
    padding: 0;
}

.criteria-item {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.criteria-item:last-child {
    border-bottom: none;
}

.criteria-info {
    flex-grow: 1;
}

.criteria-name {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
}

.criteria-description {
    margin: 0;
    color: var(--color-muted);
    font-size: 0.9rem;
}

.criteria-score {
    text-align: center;
    min-width: 100px;
}

.score-display {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.score-display.positive .score { color: #00b894; }
.score-display.negative .score { color: #e17055; }
.score-display.bonus .score { color: #fdcb6e; }

.max-score {
    color: var(--color-muted);
    font-size: 0.9rem;
}

.score-percentage {
    font-size: 0.8rem;
    color: var(--color-muted);
}

@media (max-width: 768px) {
    .modern-card-header {
        flex-direction: column;
        text-align: center;
    }

    .modern-card-header > div {
        margin-bottom: 1rem;
    }

    .modern-card-header > div:last-child {
        margin-bottom: 0;
    }

    .criteria-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .criteria-score {
        margin-top: 1rem;
        text-align: left;
    }

    .score-main {
        font-size: 2rem !important;
    }
}
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer for animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });

    // Apply fade-in animation to all cards
    document.querySelectorAll('.modern-card, .stats-card').forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = `all 0.6s ease ${index * 0.1}s`;
        observer.observe(el);
    });

    // Add hover effects to criteria items
    document.querySelectorAll('.criteria-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(102, 126, 234, 0.05)';
            this.style.transform = 'translateX(5px)';
        });

        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.transform = '';
        });
    });

    // Animate score displays
    document.querySelectorAll('.score').forEach(scoreEl => {
        const finalScore = parseInt(scoreEl.textContent);
        let currentScore = 0;
        const increment = finalScore / 30; // Animation duration

        const timer = setInterval(() => {
            currentScore += increment;
            if (currentScore >= finalScore) {
                currentScore = finalScore;
                clearInterval(timer);
            }
            scoreEl.textContent = Math.floor(currentScore);
        }, 50);
    });
});
</script>
@endpush
