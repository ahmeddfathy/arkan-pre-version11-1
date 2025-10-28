@extends('layouts.app')

@section('title', 'السيزون الحالي')

@push('styles')
<style>
    .season-banner {
        position: relative;
        height: 300px;
        background-size: cover;
        background-position: center;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .season-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.8));
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 30px;
        color: white;
    }

    .season-title {
        font-size: 36px;
        font-weight: bold;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        margin-bottom: 10px;
    }

    .season-description {
        font-size: 18px;
        max-width: 600px;
        margin-bottom: 20px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }

    .season-timer {
        background-color: rgba(0,0,0,0.6);
        padding: 10px 20px;
        border-radius: 5px;
        display: inline-block;
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .progress-container {
        height: 10px;
        background-color: rgba(255,255,255,0.2);
        border-radius: 5px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .progress-bar {
        height: 100%;
        border-radius: 5px;
    }

    .upcoming-season {
        background: linear-gradient(135deg, #2c3e50, #3498db);
        border-radius: 10px;
        padding: 20px;
        color: white;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    }

    .past-seasons {
        display: flex;
        overflow-x: auto;
        padding: 10px 0;
        margin: 0 -10px;
    }

    .past-season-card {
        min-width: 200px;
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin: 0 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }

    .past-season-card:hover {
        transform: translateY(-5px);
    }

    .season-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .badge-current {
        background-color: #28a745;
        color: white;
    }

    .badge-upcoming {
        background-color: #007bff;
        color: white;
    }

    .badge-expired {
        background-color: #6c757d;
        color: white;
    }

    .no-season {
        background: linear-gradient(135deg, #485563, #29323c);
        border-radius: 10px;
        padding: 40px;
        color: white;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .countdown {
        display: flex;
        justify-content: center;
        margin: 20px 0;
    }

    .countdown-item {
        background-color: rgba(0,0,0,0.6);
        padding: 10px;
        border-radius: 5px;
        margin: 0 5px;
        min-width: 60px;
        text-align: center;
    }

    .countdown-number {
        font-size: 24px;
        font-weight: bold;
    }

    .countdown-label {
        font-size: 12px;
        opacity: 0.8;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if ($currentSeason)
                <div class="season-banner" style="background-image: url('{{ $currentSeason->banner_image ? asset('storage/'.$currentSeason->banner_image) : asset('img/default-season-banner.jpg') }}');">
                    <div class="season-overlay">
                        <span class="season-badge badge-current">السيزون الحالي</span>
                        <h1 class="season-title">{{ $currentSeason->name }}</h1>
                        <p class="season-description">{{ $currentSeason->description }}</p>

                        <div class="season-timer">
                            <i class="fas fa-clock ml-2"></i>
                            {{ $currentSeason->remaining_time }}
                        </div>

                        <div class="progress-container">
                            <div class="progress-bar" style="width: {{ $currentSeason->progress_percentage }}%; background-color: {{ $currentSeason->color_theme }}"></div>
                        </div>
                        <small class="text-white">اكتمل {{ $currentSeason->progress_percentage }}% من السيزون</small>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div id="countdown" class="countdown">
                            <div class="countdown-item">
                                <div class="countdown-number" id="days">--</div>
                                <div class="countdown-label">أيام</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-number" id="hours">--</div>
                                <div class="countdown-label">ساعات</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-number" id="minutes">--</div>
                                <div class="countdown-label">دقائق</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-number" id="seconds">--</div>
                                <div class="countdown-label">ثواني</div>
                            </div>
                        </div>

                        <h4 class="text-center mb-4">معلومات السيزون</h4>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-left: 15px;">
                                        <img src="{{ $currentSeason->image ? asset('storage/'.$currentSeason->image) : asset('img/default-season.jpg') }}" alt="{{ $currentSeason->name }}" class="img-fluid">
                                    </div>
                                    <div>
                                        <h5 class="mb-0">{{ $currentSeason->name }}</h5>
                                        <small class="text-muted">
                                            {{ $currentSeason->start_date->format('Y/m/d') }} - {{ $currentSeason->end_date->format('Y/m/d') }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $currentSeason->progress_percentage }}%; background-color: {{ $currentSeason->color_theme }};" aria-valuenow="{{ $currentSeason->progress_percentage }}" aria-valuemin="0" aria-valuemax="100">
                                        {{ $currentSeason->progress_percentage }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($currentSeason->rewards)
                            <div class="mt-4">
                                <h5>مكافآت السيزون</h5>
                                <ul class="list-group">
                                    @foreach($currentSeason->rewards as $reward)
                                        <li class="list-group-item">{{ $reward }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif ($upcomingSeason)
                <div class="upcoming-season">
                    <span class="season-badge badge-upcoming">السيزون القادم</span>
                    <h2>{{ $upcomingSeason->name }}</h2>
                    <p>{{ $upcomingSeason->description }}</p>
                    <div class="season-timer">
                        <i class="fas fa-clock ml-2"></i>
                        {{ $upcomingSeason->remaining_time }}
                    </div>
                </div>
            @else
                <div class="no-season">
                    <h2><i class="fas fa-calendar-times ml-2"></i> لا يوجد سيزون حالي</h2>
                    <p>لا يوجد سيزون نشط حالياً. تابعنا للحصول على تحديثات حول السيزون القادم!</p>
                </div>
            @endif

            @if($pastSeasons->isNotEmpty())
                <h3 class="mt-5 mb-3">السيزونات السابقة</h3>
                <div class="past-seasons">
                    @foreach($pastSeasons as $pastSeason)
                        <div class="past-season-card">
                            <span class="season-badge badge-expired">منتهي</span>
                            <h5>{{ $pastSeason->name }}</h5>
                            <small class="text-muted">{{ $pastSeason->start_date->format('Y/m/d') }} - {{ $pastSeason->end_date->format('Y/m/d') }}</small>
                            <hr>
                            <a href="{{ route('seasons.show', $pastSeason) }}" class="btn btn-sm btn-outline-secondary">عرض التفاصيل</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($currentSeason)
            // تحديث العد التنازلي كل ثانية
            const countDownDate = new Date("{{ $currentSeason->end_date->format('Y-m-d H:i:s') }}").getTime();

            const countdownFunction = function() {
                // الوقت الحالي
                const now = new Date().getTime();

                // الفرق بين الوقت الحالي وتاريخ الانتهاء
                const distance = countDownDate - now;

                // حسابات الوقت للأيام والساعات والدقائق والثواني
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // عرض النتيجة
                document.getElementById("days").innerHTML = days;
                document.getElementById("hours").innerHTML = hours;
                document.getElementById("minutes").innerHTML = minutes;
                document.getElementById("seconds").innerHTML = seconds;

                // إذا انتهى العد التنازلي
                if (distance < 0) {
                    clearInterval(x);
                    document.getElementById("countdown").innerHTML = "انتهى السيزون!";
                }
            };

            // تشغيل الدالة فوراً ثم كل ثانية
            countdownFunction();
            const x = setInterval(countdownFunction, 1000);
        @endif
    });
</script>
@endpush
