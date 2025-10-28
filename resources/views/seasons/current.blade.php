@extends('layouts.app')

@section('title', 'السيزون الحالي')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/seasons.css') }}">
@endpush

@section('content')
<div class="seasons-container">
    <div class="container">
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        @if($currentSeason)
            <!-- Current Season Banner -->
            <div class="season-detail-banner" style="background-image: url('{{ $currentSeason->banner_image ? asset('storage/'.$currentSeason->banner_image) : asset('img/default-season-banner.jpg') }}');">
                <div class="season-detail-overlay">
                    <span class="season-badge badge-current">🏆 السيزون الحالي</span>
                    <h1 class="season-detail-title">{{ $currentSeason->name }}</h1>

                    @if($currentSeason->description)
                        <p class="season-detail-description">{{ $currentSeason->description }}</p>
                    @endif

                    <div class="season-timer">
                        <i class="fas fa-hourglass-half ml-2"></i>
                        {{ $currentSeason->remaining_time }}
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

                <div style="text-align: center; margin-top: 1.5rem;">
                    <h3 style="color: #374151; font-size: 1.25rem; margin-bottom: 1rem;">
                        <i class="fas fa-chart-line" style="color: #667eea;"></i>
                        نسبة إتمام السيزون
                    </h3>
                    <div class="progress-bar-container" style="max-width: 600px; margin: 0 auto; height: 30px;">
                        <div class="progress-bar-fill" style="width: {{ $currentSeason->progress_percentage }}%; background: linear-gradient(90deg, {{ $currentSeason->color_theme }}, {{ $currentSeason->color_theme }}dd); height: 30px;">
                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white; font-weight: 700; font-size: 1rem;">
                                {{ $currentSeason->progress_percentage }}%
                            </div>
                        </div>
                    </div>
                    <small style="display: block; margin-top: 0.75rem; color: #6b7280;">
                        اكتمل {{ $currentSeason->progress_percentage }}% من السيزون
                    </small>
                </div>
            </div>

            <!-- Season Information -->
            <div class="detail-card">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    معلومات السيزون
                </h2>

                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                                @if($currentSeason->image)
                                    <img src="{{ asset('storage/'.$currentSeason->image) }}" alt="{{ $currentSeason->name }}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                                @endif
                                <div>
                                    <h4 style="margin: 0; color: #1f2937; font-size: 1.3rem;">{{ $currentSeason->name }}</h4>
                                    <small style="color: #6b7280; display: block; margin-top: 0.25rem;">
                                        <i class="fas fa-calendar-alt" style="color: #667eea;"></i>
                                        {{ $currentSeason->start_date->format('Y/m/d') }} - {{ $currentSeason->end_date->format('Y/m/d') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-palette ml-1"></i>
                                لون السيزون:
                            </div>
                            <div class="info-value">
                                <span class="color-display" style="background-color: {{ $currentSeason->color_theme }};"></span>
                                {{ $currentSeason->color_theme }}
                            </div>
                        </div>
                    </div>
                </div>

                @if($currentSeason->rewards)
                    <div class="detail-section" style="margin-top: 2rem;">
                        <h3 class="section-title">
                            <i class="fas fa-gift"></i>
                            مكافآت السيزون
                        </h3>
                        <ul style="list-style: none; padding: 0;">
                            @foreach($currentSeason->rewards as $reward)
                                <li class="info-row">
                                    <i class="fas fa-star" style="color: #f59e0b; margin-left: 0.5rem;"></i>
                                    {{ $reward }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

        @elseif($upcomingSeason)
            <!-- Upcoming Season -->
            <div class="detail-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div style="text-align: center; padding: 2rem;">
                    <span class="season-badge badge-upcoming" style="position: static; font-size: 1rem; padding: 0.75rem 1.5rem; margin-bottom: 1.5rem;">
                        🔜 السيزون القادم
                    </span>
                    <h2 style="font-size: 2.5rem; margin-bottom: 1rem; color: white;">{{ $upcomingSeason->name }}</h2>

                    @if($upcomingSeason->description)
                        <p style="font-size: 1.2rem; max-width: 600px; margin: 0 auto 1.5rem; opacity: 0.95;">
                            {{ $upcomingSeason->description }}
                        </p>
                    @endif

                    <div style="background: rgba(0,0,0,0.3); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 12px; display: inline-block;">
                        <i class="fas fa-clock" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                        <p style="font-size: 1.1rem; margin: 0; font-weight: 600;">{{ $upcomingSeason->remaining_time }}</p>
                        <small style="opacity: 0.9;">حتى بداية السيزون</small>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <small style="opacity: 0.9;">
                            <i class="fas fa-calendar-check"></i>
                            يبدأ في: {{ $upcomingSeason->start_date->format('Y/m/d H:i') }}
                        </small>
                    </div>
                </div>
            </div>

        @else
            <!-- No Season -->
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h2 style="color: #374151; margin: 1rem 0 0.5rem;">لا يوجد سيزون حالي</h2>
                <p style="color: #6b7280; font-size: 1.1rem;">
                    لا يوجد سيزون نشط حالياً. تابعنا للحصول على تحديثات حول السيزون القادم!
                </p>
            </div>
        @endif

        <!-- Past Seasons -->
        @if($pastSeasons->isNotEmpty())
            <div class="detail-card">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    السيزونات السابقة
                </h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                    @foreach($pastSeasons as $pastSeason)
                        <div style="background: #f9fafb; border-radius: 12px; padding: 1.5rem; border: 2px solid #e5e7eb; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.1)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                            <span class="season-badge badge-expired" style="position: static; margin-bottom: 1rem;">منتهي</span>

                            <h4 style="color: #1f2937; margin: 0 0 0.75rem 0; font-size: 1.2rem;">
                                {{ $pastSeason->name }}
                            </h4>

                            <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem;">
                                <i class="fas fa-calendar-alt" style="color: #667eea;"></i>
                                {{ $pastSeason->start_date->format('Y/m/d') }} - {{ $pastSeason->end_date->format('Y/m/d') }}
                            </div>

                            <div class="progress-bar-container" style="margin-bottom: 1rem;">
                                <div class="progress-bar-fill" style="width: 100%; background: linear-gradient(90deg, #6b7280, #4b5563);"></div>
                            </div>

                            <a href="{{ route('seasons.show', $pastSeason) }}" class="season-btn btn-view" style="width: 100%; justify-content: center;">
                                <i class="fas fa-eye"></i>
                                عرض التفاصيل
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($currentSeason)
            const countDownDate = new Date("{{ $currentSeason->end_date->format('Y-m-d H:i:s') }}").getTime();

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
                    document.getElementById("countdown").innerHTML = "<div style='text-align: center; font-size: 2rem; color: #667eea; font-weight: 700; padding: 2rem;'>🎉 انتهى السيزون! 🎉</div>";
                }
            };

            countdownFunction();
            const x = setInterval(countdownFunction, 1000);
        @endif
    });
</script>
@endpush
