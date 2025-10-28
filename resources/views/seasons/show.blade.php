@extends('layouts.app')

@section('title', $season->name)

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
        <div class="col-md-10">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="season-banner" style="background-image: url('{{ $season->banner_image ? asset('storage/'.$season->banner_image) : asset('img/default-season-banner.jpg') }}');">
                <div class="season-overlay">
                    @if($season->is_current)
                        <span class="season-badge badge-current">السيزون الحالي</span>
                    @elseif($season->is_upcoming)
                        <span class="season-badge badge-upcoming">السيزون القادم</span>
                    @elseif($season->is_expired)
                        <span class="season-badge badge-expired">السيزون المنتهي</span>
                    @endif
                    <h1 class="season-title">{{ $season->name }}</h1>
                    <p class="season-description">{{ $season->description }}</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تفاصيل السيزون</h5>
                    <div>
                        <a href="{{ route('seasons.edit', $season) }}" class="btn btn-light btn-sm ml-2">
                            <i class="fas fa-edit ml-1"></i>
                            تعديل
                        </a>
                        <a href="{{ route('seasons.index') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-right ml-1"></i>
                            العودة للقائمة
                        </a>
                    </div>
                </div>

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

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-left: 15px;">
                                    <img src="{{ $season->image ? asset('storage/'.$season->image) : asset('img/default-season.jpg') }}" alt="{{ $season->name }}" class="img-fluid">
                                </div>
                                <div>
                                    <h5 class="mb-0">{{ $season->name }}</h5>
                                    <small class="text-muted">
                                        {{ $season->start_date->format('Y/m/d H:i') }} - {{ $season->end_date->format('Y/m/d H:i') }}
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>الحالة:</strong>
                                @if($season->is_active)
                                    <span class="badge badge-success">نشط</span>
                                @else
                                    <span class="badge badge-secondary">غير نشط</span>
                                @endif

                                @if($season->is_current)
                                    <span class="badge badge-primary">جاري حالياً</span>
                                @elseif($season->is_upcoming)
                                    <span class="badge badge-info">قادم</span>
                                @elseif($season->is_expired)
                                    <span class="badge badge-secondary">منتهي</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <strong>الوقت المتبقي:</strong> {{ $season->remaining_time }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>نسبة التقدم:</strong>
                            </div>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar" role="progressbar" style="width: {{ $season->progress_percentage }}%; background-color: {{ $season->color_theme }};" aria-valuenow="{{ $season->progress_percentage }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ $season->progress_percentage }}%
                                </div>
                            </div>
                            <div class="mb-3">
                                <strong>لون السيزون:</strong>
                                <span class="color-preview" style="width: 20px; height: 20px; display: inline-block; border-radius: 50%; background-color: {{ $season->color_theme }};"></span>
                                {{ $season->color_theme }}
                            </div>
                        </div>
                    </div>

                    @if($season->rewards)
                        <div class="mt-4">
                            <h5>مكافآت السيزون</h5>
                            <ul class="list-group">
                                @foreach($season->rewards as $reward)
                                    <li class="list-group-item">{{ $reward }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    <form action="{{ route('seasons.destroy', $season) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا السيزون؟')">
                            <i class="fas fa-trash-alt ml-1"></i>
                            حذف السيزون
                        </button>
                    </form>
                </div>
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
                document.getElementById("countdown").innerHTML = "{{ $season->is_upcoming ? 'بدأ السيزون!' : 'انتهى السيزون!' }}";
            }
        };

        // تشغيل الدالة فوراً ثم كل ثانية
        countdownFunction();
        const x = setInterval(countdownFunction, 1000);
    });
</script>
@endpush
