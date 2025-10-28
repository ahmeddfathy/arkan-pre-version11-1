@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/permission-managment.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container">
    @if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif
    @include('permission-requests.components._search')
    @include('permission-requests.components._stats')
    @include('permission-requests.components._table')
    @include('permission-requests.components._modals')

    @include('permission-requests.components._statstics')

                </div>
@endsection

@push('scripts')
<!-- Third-party Libraries -->
<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Permission Request Scripts -->
<script src="{{ asset('js/permission-requests/common.js') }}"></script>
<script src="{{ asset('js/permission-requests/countdown.js') }}"></script>
<script src="{{ asset('js/permission-requests/statistics.js') }}"></script>
<script src="{{ asset('js/permission-requests/department-chart.js') }}"></script>
<script src="{{ asset('js/permission-requests/modals.js') }}"></script>
<script src="{{ asset('js/permission-requests/table.js') }}"></script>

    @if(isset($statistics))
<script>
    window.permissionStatistics = {!! json_encode($statistics) !!};
</script>
    @endif
@endpush
