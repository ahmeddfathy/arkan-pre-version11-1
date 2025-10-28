@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">@yield('page-title')</h1>
        <div class="page-actions">
            @yield('page-actions')
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="notification-container">
        @yield('notification-content')
    </div>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
<link href="{{ asset('css/admin/notifications.css') }}" rel="stylesheet">
<link href="{{ asset('css/admin/notification-index.css') }}" rel="stylesheet">
@stack('page-styles')
@endpush

@push('scripts')
<script src="https://kit.fontawesome.com/5b0474bd56.js" crossorigin="anonymous"></script>
@stack('page-scripts')
@endpush
