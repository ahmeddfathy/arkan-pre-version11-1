@extends('layouts.app')

@section('title', 'تعديل حد شهري')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/project-limits.css') }}">
@endpush

@section('content')
<div class="limits-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header-limits">
            <h1>✏️ تعديل حد شهري</h1>
            <p>تحديث الحد الأقصى للمشاريع الشهرية</p>
        </div>

        <!-- Back Button -->
        <div class="mb-4">
            <a href="{{ route('project-limits.index') }}" class="btn btn-custom btn-gradient-light">
                <i class="fas fa-arrow-right"></i>
                رجوع للقائمة
            </a>
        </div>

        <!-- Form -->
        <div class="row">
            <div class="col-lg-9 mx-auto">
                <div class="modern-form-card">
                    <form action="{{ route('project-limits.update', $projectLimit) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- نوع الحد (للعرض فقط) -->
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="fas fa-tags"></i>
                                نوع الحد
                            </label>
                            <div style="background: linear-gradient(135deg, #f9fafb, #ffffff); border: 2px solid #e5e7eb; border-radius: 10px; padding: 0.75rem 1rem; font-weight: 600;">
                                @if($projectLimit->limit_type === 'company')
                                    🏢 {{ $projectLimit->limit_type_text }}
                                @elseif($projectLimit->limit_type === 'department')
                                    🏛️ {{ $projectLimit->limit_type_text }}
                                @elseif($projectLimit->limit_type === 'team')
                                    👥 {{ $projectLimit->limit_type_text }}
                                @else
                                    👤 {{ $projectLimit->limit_type_text }}
                                @endif
                            </div>
                            <input type="hidden" name="limit_type" value="{{ $projectLimit->limit_type }}">
                        </div>

                        <!-- الشهر -->
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="fas fa-calendar"></i>
                                الشهر
                            </label>
                            <select name="month" class="form-control-modern form-select @error('month') is-invalid @enderror">
                                <option value="" {{ is_null($projectLimit->month) ? 'selected' : '' }}>📅 جميع الشهور (حد عام)</option>
                                <option value="1" {{ $projectLimit->month == 1 ? 'selected' : '' }}>يناير</option>
                                <option value="2" {{ $projectLimit->month == 2 ? 'selected' : '' }}>فبراير</option>
                                <option value="3" {{ $projectLimit->month == 3 ? 'selected' : '' }}>مارس</option>
                                <option value="4" {{ $projectLimit->month == 4 ? 'selected' : '' }}>أبريل</option>
                                <option value="5" {{ $projectLimit->month == 5 ? 'selected' : '' }}>مايو</option>
                                <option value="6" {{ $projectLimit->month == 6 ? 'selected' : '' }}>يونيو</option>
                                <option value="7" {{ $projectLimit->month == 7 ? 'selected' : '' }}>يوليو</option>
                                <option value="8" {{ $projectLimit->month == 8 ? 'selected' : '' }}>أغسطس</option>
                                <option value="9" {{ $projectLimit->month == 9 ? 'selected' : '' }}>سبتمبر</option>
                                <option value="10" {{ $projectLimit->month == 10 ? 'selected' : '' }}>أكتوبر</option>
                                <option value="11" {{ $projectLimit->month == 11 ? 'selected' : '' }}>نوفمبر</option>
                                <option value="12" {{ $projectLimit->month == 12 ? 'selected' : '' }}>ديسمبر</option>
                            </select>
                            @error('month')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- الكيان (للعرض فقط) -->
                        @if($projectLimit->limit_type !== 'company')
                            <div class="mb-4">
                                <label class="form-label-modern">
                                    @if($projectLimit->limit_type === 'department')
                                        <i class="fas fa-sitemap"></i>
                                        القسم
                                    @elseif($projectLimit->limit_type === 'team')
                                        <i class="fas fa-users"></i>
                                        الفريق
                                    @else
                                        <i class="fas fa-user"></i>
                                        الموظف
                                    @endif
                                </label>
                                <div style="background: linear-gradient(135deg, #f9fafb, #ffffff); border: 2px solid #e5e7eb; border-radius: 10px; padding: 0.75rem 1rem; font-weight: 600;">
                                    {{ $projectLimit->entity_display_name }}
                                </div>
                                <input type="hidden" name="entity_id" value="{{ $projectLimit->entity_id }}">
                                <input type="hidden" name="entity_name" value="{{ $projectLimit->entity_name }}">
                            </div>
                        @endif

                        <!-- الحد الشهري -->
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="fas fa-chart-line"></i>
                                الحد الشهري للمشاريع <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   name="monthly_limit"
                                   class="form-control-modern form-control @error('monthly_limit') is-invalid @enderror"
                                   value="{{ old('monthly_limit', $projectLimit->monthly_limit) }}"
                                   min="0"
                                   style="font-size: 1.2rem; font-weight: 600;"
                                   required>
                            <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i>
                                عدد المشاريع المسموح بها شهرياً
                            </small>
                            @error('monthly_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ملاحظات -->
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="fas fa-sticky-note"></i>
                                ملاحظات
                            </label>
                            <textarea name="notes"
                                      class="form-control-modern form-control @error('notes') is-invalid @enderror"
                                      rows="4"
                                      placeholder="ملاحظات إضافية (اختياري)">{{ old('notes', $projectLimit->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- تفعيل/تعطيل -->
                        <div class="mb-4" style="background: #f9fafb; padding: 1.5rem; border-radius: 12px; border: 2px solid #e5e7eb;">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="is_active"
                                       id="isActive"
                                       value="1"
                                       style="width: 3rem; height: 1.5rem; cursor: pointer;"
                                       {{ old('is_active', $projectLimit->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive" style="margin-right: 1rem; cursor: pointer;">
                                    <strong style="font-size: 1.1rem;">✅ تفعيل هذا الحد</strong>
                                    <small class="d-block text-muted" style="margin-top: 0.5rem;">
                                        <i class="fas fa-info-circle"></i>
                                        سيتم تطبيق الحد مباشرة بعد الحفظ
                                    </small>
                                </label>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-3 justify-content-end mt-4 pt-3" style="border-top: 2px solid #e5e7eb;">
                            <a href="{{ route('project-limits.index') }}" class="btn btn-custom" style="background: #6c757d; color: white; padding: 12px 28px;">
                                <i class="fas fa-times"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-custom btn-gradient-primary">
                                <i class="fas fa-save"></i>
                                حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
