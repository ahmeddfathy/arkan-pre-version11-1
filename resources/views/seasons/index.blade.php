@extends('layouts.app')

@section('title', 'إدارة المواسم')

@push('styles')
<style>
    .season-card {
        transition: all 0.3s;
        border-radius: 10px;
        overflow: hidden;
    }
    .season-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .season-header {
        height: 120px;
        background-size: cover;
        background-position: center;
        position: relative;
    }
    .season-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.7));
        display: flex;
        align-items: flex-end;
        padding: 15px;
    }
    .season-title {
        color: white;
        margin: 0;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
    }
    .season-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
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
</style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy ml-2"></i>
                        إدارة المواسم
                    </h5>
                    <div>
                        <a href="{{ route('seasons.create') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus-circle ml-1"></i>
                            إضافة سيزون جديد
                        </a>
                    </div>
                </div>

                <div class="card-body">
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

                    <div class="row">
                        @forelse ($seasons as $season)
                            <div class="col-md-4 mb-4">
                                <div class="card season-card">
                                    <div class="season-header" style="background-image: url('{{ $season->banner_image ? asset('storage/'.$season->banner_image) : asset('img/default-season-banner.jpg') }}');">
                                        <div class="season-overlay">
                                            <h5 class="season-title">{{ $season->name }}</h5>
                                        </div>
                                        @if($season->is_current)
                                            <span class="season-badge badge-current">حالي</span>
                                        @elseif($season->is_upcoming)
                                            <span class="season-badge badge-upcoming">قادم</span>
                                        @elseif($season->is_expired)
                                            <span class="season-badge badge-expired">منتهي</span>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <i class="far fa-calendar-alt ml-1"></i>
                                                {{ $season->start_date->format('Y/m/d') }} - {{ $season->end_date->format('Y/m/d') }}
                                            </div>
                                            <div>
                                                @if($season->is_active)
                                                    <span class="badge badge-success">نشط</span>
                                                @else
                                                    <span class="badge badge-secondary">غير نشط</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="progress mb-3" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $season->progress_percentage }}%; background-color: {{ $season->color_theme }};" aria-valuenow="{{ $season->progress_percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <a href="{{ route('seasons.show', $season) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('seasons.edit', $season) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('seasons.destroy', $season) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا السيزون؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle ml-2"></i>
                                    لا توجد مواسم مضافة حتى الآن.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
