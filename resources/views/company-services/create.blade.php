@extends('layouts.app')

@section('title', 'إضافة خدمة جديدة')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/company-services-design.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>➕ إضافة خدمة جديدة</h1>
            <p>إنشاء خدمة جديدة في نظام إدارة الخدمات</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="service-card">
                    <div class="service-card-header">
                        <h2>معلومات الخدمة</h2>
                    </div>
                    <div class="service-card-body">
                        <form action="{{ route('company-services.store') }}" method="POST">
                            @csrf

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <ul style="margin: 0.5rem 0 0 0; padding-right: 1.5rem;">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">اسم الخدمة <span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                                        @error('name')
                                            <small style="color: #dc2626;">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="points">النقاط <span class="text-danger">*</span></label>
                                        <input type="number" id="points" name="points" class="form-control" min="0" max="1000" value="{{ old('points', 0) }}" required>
                                        @error('points')
                                            <small style="color: #dc2626;">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_points_per_project">الحد الأقصى للنقاط لكل مشروع</label>
                                        <input type="number" id="max_points_per_project" name="max_points_per_project" class="form-control" min="0" max="10000" value="{{ old('max_points_per_project', 0) }}"
                                               placeholder="0 = بلا حدود">
                                        <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                                            <i class="fas fa-info-circle"></i>
                                            اتركه 0 إذا كنت لا تريد تحديد حد أقصى للنقاط في المشروع الواحد
                                        </small>
                                        @error('max_points_per_project')
                                            <small style="color: #dc2626;">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="department">القسم</label>
                                        <select id="department" name="department" class="form-control">
                                            <option value="">-- كل الأقسام --</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department }}" {{ old('department') == $department ? 'selected' : '' }}>{{ $department }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="checkbox-group">
                                            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                            <label for="is_active">خدمة نشطة</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">الوصف</label>
                                <textarea id="description" name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                                @error('description')
                                    <small style="color: #dc2626;">{{ $message }}</small>
                                @enderror
                            </div>

                            <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                                <button type="submit" class="services-btn">
                                    <i class="fas fa-save"></i> حفظ الخدمة
                                </button>
                                <a href="{{ route('company-services.index') }}" class="services-btn btn-secondary">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
