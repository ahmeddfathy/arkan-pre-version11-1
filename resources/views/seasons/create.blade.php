@extends('layouts.app')

@section('title', 'إضافة سيزون جديد')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/seasons.css') }}">
@endpush

@section('content')
<div class="seasons-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>➕ إضافة سيزون جديد</h1>
            <p>أنشئ سيزوناً جديداً مع كل التفاصيل اللازمة</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="form-card">
                    <div class="form-header">
                        <h2>
                            <i class="fas fa-trophy"></i>
                            بيانات السيزون
                        </h2>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('seasons.store') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- اسم السيزون -->
                        <div class="form-group">
                            <label for="name" class="form-label">
                                <i class="fas fa-heading ml-1"></i>
                                اسم السيزون <span style="color: #ef4444;">*</span>
                            </label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus placeholder="مثال: سيزون الإنجاز 2024">
                            @error('name')
                                <span class="invalid-feedback" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                    {{ $message }}
                                </span>
                            @enderror
                            <small style="color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                مثال: سيزون الإنجاز 2024، سيزون التميز، إلخ.
                            </small>
                        </div>

                        <!-- الوصف -->
                        <div class="form-group">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-right ml-1"></i>
                                الوصف
                            </label>
                            <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" rows="4" placeholder="اكتب وصفاً تفصيلياً للسيزون...">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                    {{ $message }}
                                </span>
                            @enderror>
                        </div>

                        <div class="row">
                            <!-- تاريخ البداية -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date" class="form-label">
                                        <i class="fas fa-calendar-check ml-1"></i>
                                        تاريخ البداية <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input id="start_date" type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" name="start_date" value="{{ old('start_date') }}" required>
                                    @error('start_date')
                                        <span class="invalid-feedback" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- تاريخ النهاية -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date" class="form-label">
                                        <i class="fas fa-calendar-times ml-1"></i>
                                        تاريخ النهاية <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input id="end_date" type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" name="end_date" value="{{ old('end_date') }}" required>
                                    @error('end_date')
                                        <span class="invalid-feedback" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- لون السيزون -->
                        <div class="form-group">
                            <label for="color_theme" class="form-label">
                                <i class="fas fa-palette ml-1"></i>
                                لون السيزون
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span class="color-display" id="colorPreview" style="background-color: #667eea; width: 50px; height: 50px;"></span>
                                <input id="color_theme" type="color" class="form-control @error('color_theme') is-invalid @enderror" name="color_theme" value="{{ old('color_theme', '#667eea') }}" style="max-width: 150px;">
                            </div>
                            @error('color_theme')
                                <span class="invalid-feedback" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- صورة السيزون -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="image" class="form-label">
                                        <i class="fas fa-image ml-1"></i>
                                        صورة السيزون (أيقونة)
                                    </label>
                                    <input id="image" type="file" class="form-control @error('image') is-invalid @enderror" name="image" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                                    @error('image')
                                        <span class="invalid-feedback" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                    <small style="color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                        صورة أيقونة السيزون (اختياري). الحجم المثالي: 200×200 بكسل
                                    </small>
                                    <img id="imagePreview" class="image-preview" src="#" alt="معاينة الصورة" style="display: none;">
                                </div>
                            </div>

                            <!-- صورة الغلاف -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="banner_image" class="form-label">
                                        <i class="fas fa-panorama ml-1"></i>
                                        صورة الغلاف (Banner)
                                    </label>
                                    <input id="banner_image" type="file" class="form-control @error('banner_image') is-invalid @enderror" name="banner_image" accept="image/*" onchange="previewImage(this, 'bannerPreview')">
                                    @error('banner_image')
                                        <span class="invalid-feedback" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                    <small style="color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                                        صورة غلاف السيزون (اختياري). الحجم المثالي: 1200×400 بكسل
                                    </small>
                                    <img id="bannerPreview" class="image-preview" src="#" alt="معاينة الغلاف" style="display: none;">
                                </div>
                            </div>
                        </div>

                        <!-- حالة السيزون -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-toggle-on ml-1"></i>
                                حالة السيزون
                            </label>
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 8px; border: 2px solid #e5e7eb;">
                                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer;">
                                    <span style="font-weight: 500; color: #374151;">
                                        تفعيل السيزون
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons" style="margin-top: 2rem;">
                            <button type="submit" class="action-btn btn-primary-action">
                                <i class="fas fa-save"></i>
                                حفظ السيزون
                            </button>
                            <a href="{{ route('seasons.index') }}" class="action-btn" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white;">
                                <i class="fas fa-times"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // معاينة اللون
        const colorInput = document.getElementById('color_theme');
        const colorPreview = document.getElementById('colorPreview');

        colorInput.addEventListener('input', function() {
            colorPreview.style.backgroundColor = this.value;
        });
    });

    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);

        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }

            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
