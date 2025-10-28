@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/employee-statistics.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.employeesData = @json($employees->items());
        window.totalStats = @json($totalStats);
    </script>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i> إحصائيات الموظفين
                    </h5>
                </div>

                <div class="card-body">
                    @include('employee-statistics.partials.indicators')
                </div>

                @if(auth()->user()->hasRole('hr'))
                    @include('employee-statistics.components.rules-section')
                @endif

                @include('employee-statistics.components.filters-section')
                @include('employee-statistics.components.charts-section')
                @include('employee-statistics.components.employee-list')
            </div>
        </div>
    </div>
</div>

<!-- Modal التفاصيل -->

@include('employee-statistics.components.scripts')
@include('employee-statistics.components.modals')
@include('employee-statistics.partials.details-modal')
@endsection
