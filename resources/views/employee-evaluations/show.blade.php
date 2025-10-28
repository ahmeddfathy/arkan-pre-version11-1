@extends('layouts.app')

@section('title', 'تفاصيل التقييم')

@push('styles')
<link href="{{ asset('css/employee-evaluations.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-star ml-2"></i>
                        تقييم الموظف: {{ $evaluation->user->name }}
                    </h5>
                    <div>
                        <a href="{{ route('employee-evaluations.edit', $evaluation) }}" class="btn btn-light btn-sm ml-2">
                            <i class="fas fa-edit ml-1"></i>
                            تعديل
                        </a>
                        <a href="{{ route('employee-evaluations.index') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-right ml-1"></i>
                            العودة للقائمة
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle ml-2"></i>
                                        معلومات التقييم
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">الموظف</th>
                                            <td>{{ $evaluation->user->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>فترة التقييم</th>
                                            <td>{{ $evaluation->evaluation_period }}</td>
                                        </tr>
                                        <tr>
                                            <th>تاريخ التقييم</th>
                                            <td>{{ $evaluation->evaluation_date->format('Y-m-d') }}</td>
                                        </tr>
                                        <tr>
                                            <th>المُقيّم</th>
                                            <td>{{ $evaluation->evaluator->name }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-pie ml-2"></i>
                                        النتيجة الإجمالية
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <h1 class="display-4 mb-3">{{ $totalPoints }} / {{ $maxTotalPoints }}</h1>
                                    <div class="progress mb-3">
                                        <div class="progress-bar {{ $percentageScore >= 90 ? 'bg-success' : ($percentageScore >= 70 ? 'bg-info' : ($percentageScore >= 50 ? 'bg-warning' : 'bg-danger')) }}"
                                             role="progressbar" style="width: {{ $percentageScore }}%;"
                                             aria-valuenow="{{ $percentageScore }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ $percentageScore }}%
                                        </div>
                                    </div>
                                    <span class="badge score-badge {{ $percentageScore >= 90 ? 'score-excellent' : ($percentageScore >= 70 ? 'score-good' : ($percentageScore >= 50 ? 'score-average' : 'score-poor')) }}">
                                        {{ $percentageScore >= 90 ? 'ممتاز' : ($percentageScore >= 70 ? 'جيد' : ($percentageScore >= 50 ? 'متوسط' : 'ضعيف')) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($evaluation->notes)
                        <div class="alert alert-secondary mb-4">
                            <h6 class="alert-heading">ملاحظات عامة:</h6>
                            <p class="mb-0">{{ $evaluation->notes }}</p>
                        </div>
                    @endif

                    <h5 class="mb-3">
                        <i class="fas fa-list-alt ml-2"></i>
                        تفاصيل التقييم
                    </h5>

                    @forelse($detailsByCategory as $categoryName => $details)
                        <div class="skill-category">
                            <h6>{{ $categoryName }}</h6>

                            @foreach($details as $detail)
                                <div class="skill-item">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>{{ $detail->skill->name }}</h6>
                                            <p class="small text-muted">{{ $detail->skill->description }}</p>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress mb-2">
                                                <div class="progress-bar {{ ($detail->points / $detail->skill->max_points) >= 0.7 ? 'bg-success' : 'bg-warning' }}"
                                                     role="progressbar"
                                                     style="width: {{ ($detail->points / $detail->skill->max_points) * 100 }}%;"
                                                     aria-valuenow="{{ $detail->points }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="{{ $detail->skill->max_points }}">
                                                    {{ $detail->points }} / {{ $detail->skill->max_points }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            @if($detail->comments)
                                                <p class="small mb-0"><strong>ملاحظات:</strong> {{ $detail->comments }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle ml-2"></i>
                            لا توجد تفاصيل لهذا التقييم.
                        </div>
                    @endforelse

                    <div class="mt-4">
                        <form action="{{ route('employee-evaluations.destroy', $evaluation) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا التقييم؟')">
                                <i class="fas fa-trash-alt ml-1"></i>
                                حذف التقييم
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
