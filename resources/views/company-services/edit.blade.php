@extends('layouts.app')

@section('title', 'تعديل الخدمة')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/company-services-design.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>✏️ تعديل الخدمة</h1>
            <p>تحديث بيانات الخدمة: {{ $companyService->name }}</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="service-card">
                    <div class="service-card-header">
                        <h2>تعديل بيانات الخدمة</h2>
                    </div>
                    <div class="service-card-body">
                        <form action="{{ route('company-services.update', $companyService) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">اسم الخدمة <span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $companyService->name) }}" required>
                                        @error('name')
                                            <small style="color: #dc2626;">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="points">النقاط <span class="text-danger">*</span></label>
                                        <input type="number" id="points" name="points" class="form-control" value="{{ old('points', $companyService->points) }}" min="0" max="1000" required>
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
                                        <input type="number" id="max_points_per_project" name="max_points_per_project" class="form-control"
                                               value="{{ old('max_points_per_project', $companyService->max_points_per_project) }}" min="0" max="10000" placeholder="0 = بلا حدود">
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
                                                <option value="{{ $department }}" {{ old('department', $companyService->department) == $department ? 'selected' : '' }}>{{ $department }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="checkbox-group">
                                            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $companyService->is_active) ? 'checked' : '' }}>
                                            <label for="is_active">خدمة نشطة</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">الوصف</label>
                                <textarea id="description" name="description" class="form-control" rows="4">{{ old('description', $companyService->description) }}</textarea>
                                @error('description')
                                    <small style="color: #dc2626;">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Service Info -->
                            <div class="service-info-grid" style="margin-top: 2rem;">
                                <div class="service-info-item">
                                    <h5><i class="fas fa-calendar-alt"></i> تاريخ الإنشاء</h5>
                                    <p>{{ $companyService->created_at->format('Y/m/d') }}</p>
                                </div>
                                <div class="service-info-item">
                                    <h5><i class="fas fa-clock"></i> آخر تحديث</h5>
                                    <p>{{ $companyService->updated_at->format('Y/m/d') }}</p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div style="margin-top: 2rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                                <button type="submit" class="services-btn btn-warning">
                                    <i class="fas fa-save"></i>
                                    تحديث الخدمة
                                </button>

                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="{{ route('company-services.show', $companyService) }}" class="services-btn btn-info">
                                        <i class="fas fa-eye"></i>
                                        عرض
                                    </a>
                                    <a href="{{ route('company-services.index') }}" class="services-btn btn-secondary">
                                        <i class="fas fa-arrow-right"></i>
                                        عودة
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
