@extends('layouts.app')

@section('title', 'إدارة الفرق')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/teams.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">{{ __('إدارة الفرق') }}</h2>
                <a href="{{ route('admin.teams.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> {{ __('إنشاء فريق جديد') }}
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($teams->isEmpty())
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <h4>{{ __('لا توجد فرق متاحة') }}</h4>
        <p>{{ __('ابدأ بإنشاء فريق جديد') }}</p>
        <a href="{{ route('admin.teams.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> {{ __('إنشاء فريق جديد') }}
        </a>
    </div>
    @else
    <div class="row">
        @foreach($teams as $team)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="team-card">
                <div class="team-card-header">
                    <h3 class="team-name">{{ $team->name }}</h3>
                    @if($team->personal_team)
                    <span class="team-badge personal">
                        {{ __('فريق شخصي') }}
                    </span>
                    @endif
                </div>

                <div class="team-info">
                    <div class="team-info-item">
                        <strong>{{ __('المالك:') }}</strong>
                        @if($team->owner)
                        <img src="{{ $team->owner->profile_photo_url }}" alt="{{ $team->owner->name }}">
                        <span>{{ $team->owner->name }}</span>
                        @else
                        <span>{{ __('غير محدد') }}</span>
                        @endif
                    </div>
                    <div class="team-info-item">
                        <strong>{{ __('عدد الأعضاء:') }}</strong>
                        <span>{{ $team->users->count() }}</span>
                    </div>
                </div>

                <div class="team-members-preview">
                    @foreach($team->users->take(5) as $user)
                    @if($user)
                    <div class="member-preview">
                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                        <span>{{ $user->name }}</span>
                    </div>
                    @endif
                    @endforeach
                    @if($team->users->count() > 5)
                    <span class="members-count">+{{ $team->users->count() - 5 }} {{ __('أعضاء آخرين') }}</span>
                    @endif
                </div>

                <div class="team-actions">
                    <a href="{{ route('admin.teams.show', $team) }}" class="team-action-btn primary">
                        <i class="fas fa-eye"></i> {{ __('عرض التفاصيل') }}
                    </a>
                    @if(!$team->personal_team)
                    <form action="{{ route('admin.teams.destroy', $team) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('هل أنت متأكد من حذف فريق \'{{ $team->name }}\'؟ سيتم حذف جميع البيانات المرتبطة به.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="team-action-btn danger">
                            <i class="fas fa-trash"></i> {{ __('حذف الفريق') }}
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
