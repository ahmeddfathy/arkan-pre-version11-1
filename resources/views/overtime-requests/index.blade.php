@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/overtime-requests.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<!-- Add Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div class="container">
    @include('shared.alerts')
    @include('overtime-requests.components._search')
    @include('overtime-requests.components._table')
    @include('overtime-requests.components._modals')
    @include('overtime-requests.components._statstics')


    @include('overtime-requests.components.chart-data')
    @include('overtime-requests.components.scripts')

</div>





@endsection
