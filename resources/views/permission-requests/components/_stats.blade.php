
@php
use App\Models\PermissionRequest;

@endphp

<!-- Display the minutes used in the specified period -->
<div class="alert alert-info mb-4">
    <i class="fas fa-calendar-alt me-2"></i>
    Period: {{ $dateStart->format('Y-m-d') }} to {{ $dateEnd->format('Y-m-d') }}
    <br>
    @php
    $periodUsedMinutes = PermissionRequest::where('user_id', Auth::id())
    ->where('status', 'approved')
    ->whereBetween('departure_time', [$dateStart, $dateEnd])
    ->sum('minutes_used');
    @endphp
    <i class="fas fa-clock me-2"></i>
    استخدمت {{ $periodUsedMinutes }} دقيقة في هذه الفترة
    @if($periodUsedMinutes > 180)
    <span class="text-danger">
        (تجاوزت الحد الشهري بـ {{ $periodUsedMinutes - 180 }} دقيقة)
    </span>
    @endif
</div>
