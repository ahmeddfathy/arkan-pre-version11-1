@extends('layouts.app')

@section('title', $season->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/seasons.css') }}">
@endpush

@section('content')
<div class="seasons-container">
    <div class="container">
        <!-- Success Messages -->
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- Season Detail Banner -->
        <div class="season-detail-banner" style="background-image: url('{{ $season->banner_image ? asset('storage/'.$season->banner_image) : asset('img/default-season-banner.jpg') }}');">
            <div class="season-detail-overlay">
                @if($season->is_current)
                    <span class="season-badge badge-current">السيزون الحالي</span>
                @elseif($season->is_upcoming)
                    <span class="season-badge badge-upcoming">السيزون القادم</span>
                @elseif($season->is_expired)
                    <span class="season-badge badge-expired">السيزون المنتهي</span>
                @endif

                <h1 class="season-detail-title">{{ $season->name }}</h1>

                @if($season->description)
                    <p class="season-detail-description">{{ $season->description }}</p>
                @endif

                <div class="season-timer">
                    <i class="fas fa-clock ml-2"></i>
                    {{ $season->remaining_time }}
                </div>
            </div>
        </div>

        <!-- Countdown Timer -->
        <div class="detail-card">
            <div id="countdown" class="countdown">
                <div class="countdown-item">
                    <span class="countdown-number" id="days">--</span>
                    <span class="countdown-label">أيام</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="hours">--</span>
                    <span class="countdown-label">ساعات</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="minutes">--</span>
                    <span class="countdown-label">دقائق</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="seconds">--</span>
                    <span class="countdown-label">ثواني</span>
                </div>
            </div>
        </div>

        <!-- Season Details -->
        <div class="detail-card">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                تفاصيل السيزون
            </h2>

            <div class="row">
                <div class="col-md-6">
                    <div class="detail-section">
                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-trophy ml-1"></i>
                                اسم السيزون:
                            </div>
                            <div class="info-value">{{ $season->name }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-calendar-check ml-1"></i>
                                تاريخ البداية:
                            </div>
                            <div class="info-value">{{ $season->start_date->format('Y/m/d H:i') }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-calendar-times ml-1"></i>
                                تاريخ النهاية:
                            </div>
                            <div class="info-value">{{ $season->end_date->format('Y/m/d H:i') }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-hourglass-half ml-1"></i>
                                الوقت المتبقي:
                            </div>
                            <div class="info-value">{{ $season->remaining_time }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="detail-section">
                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-toggle-on ml-1"></i>
                                حالة السيزون:
                            </div>
                            <div class="info-value">
                                @if($season->is_active)
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check-circle"></i>
                                        نشط
                                    </span>
                                @else
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-pause-circle"></i>
                                        غير نشط
                                    </span>
                                @endif

                                @if($season->is_current)
                                    <span class="season-badge badge-current" style="position: static; margin-right: 0.5rem;">جاري حالياً</span>
                                @elseif($season->is_upcoming)
                                    <span class="season-badge badge-upcoming" style="position: static; margin-right: 0.5rem;">قادم</span>
                                @elseif($season->is_expired)
                                    <span class="season-badge badge-expired" style="position: static; margin-right: 0.5rem;">منتهي</span>
                                @endif
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-palette ml-1"></i>
                                لون السيزون:
                            </div>
                            <div class="info-value">
                                <span class="color-display" style="background-color: {{ $season->color_theme }};"></span>
                                {{ $season->color_theme }}
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-chart-line ml-1"></i>
                                نسبة التقدم:
                            </div>
                            <div class="info-value">
                                <div class="progress-bar-container" style="margin-top: 0.5rem;">
                                    <div class="progress-bar-fill" style="width: {{ $season->progress_percentage }}%; background: linear-gradient(90deg, {{ $season->color_theme }}, {{ $season->color_theme }}dd);"></div>
                                </div>
                                <div style="text-align: center; margin-top: 0.5rem; font-weight: 600; color: {{ $season->color_theme }};">
                                    {{ $season->progress_percentage }}%
                                </div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-image ml-1"></i>
                                صورة السيزون:
                            </div>
                            <div class="info-value">
                                @if($season->image)
                                    <img src="{{ asset('storage/'.$season->image) }}" alt="{{ $season->name }}" style="width: 80px; height: 80px; border-radius: 10px; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                @else
                                    <span style="color: #9ca3af;">لا توجد صورة</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($season->rewards)
                <div class="detail-section" style="margin-top: 2rem;">
                    <h3 class="section-title">
                        <i class="fas fa-gift"></i>
                        مكافآت السيزون
                    </h3>
                    <ul style="list-style: none; padding: 0;">
                        @foreach($season->rewards as $reward)
                            <li class="info-row">
                                <i class="fas fa-star" style="color: #f59e0b; margin-left: 0.5rem;"></i>
                                {{ $reward }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="action-buttons" style="margin-top: 2rem;">
                <a href="{{ route('seasons.index') }}" class="action-btn btn-primary-action">
                    <i class="fas fa-arrow-right"></i>
                    العودة للقائمة
                </a>
                <a href="{{ route('seasons.edit', $season) }}" class="action-btn btn-primary-action">
                    <i class="fas fa-edit"></i>
                    تعديل السيزون
                </a>
                <form action="{{ route('seasons.destroy', $season) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="action-btn btn-danger-action" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا السيزون؟')">
                        <i class="fas fa-trash-alt"></i>
                        حذف السيزون
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تحديث العد التنازلي كل ثانية
        const countDownDate = new Date("{{ $season->is_upcoming ? $season->start_date->format('Y-m-d H:i:s') : $season->end_date->format('Y-m-d H:i:s') }}").getTime();

        const countdownFunction = function() {
            const now = new Date().getTime();
            const distance = countDownDate - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("days").innerHTML = days >= 0 ? days : 0;
            document.getElementById("hours").innerHTML = hours >= 0 ? hours : 0;
            document.getElementById("minutes").innerHTML = minutes >= 0 ? minutes : 0;
            document.getElementById("seconds").innerHTML = seconds >= 0 ? seconds : 0;

            if (distance < 0) {
                clearInterval(x);
                document.getElementById("countdown").innerHTML = "<div style='text-align: center; font-size: 1.5rem; color: #667eea; font-weight: 600;'>{{ $season->is_upcoming ? 'بدأ السيزون!' : 'انتهى السيزون!' }}</div>";
            }
        };

        countdownFunction();
        const x = setInterval(countdownFunction, 1000);
    });
</script>
@endpush
