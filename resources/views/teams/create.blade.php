@extends('layouts.app')

@section('title', 'إنشاء فريق جديد')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/teams.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">{{ __('إنشاء فريق جديد') }}</h2>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> {{ __('العودة إلى قائمة الفرق') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-users"></i> {{ __('تفاصيل الفريق') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.teams.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="form-label">
                                {{ __('اسم الفريق') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="user_id" class="form-label">
                                {{ __('مالك الفريق') }} <span class="text-danger">*</span>
                            </label>
                            <select name="user_id"
                                    id="user_id"
                                    class="form-select @error('user_id') is-invalid @enderror"
                                    required>
                                <option value="">{{ __('اختر مالك الفريق') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->department ?? 'غير محدد' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                {{ __('سيتم إضافة المالك تلقائياً كعضو في الفريق') }}
                            </small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.teams.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> {{ __('إلغاء') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __('إنشاء الفريق') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
