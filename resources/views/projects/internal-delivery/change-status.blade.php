@extends('layouts.app')

@section('title', 'التسليم الداخلي للمشروع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-internal-delivery.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>التسليم الداخلي للمشروع</h1>
            <p>قم بتحديث حالة المشروع ونوع التسليم</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Project Info Card -->
        <div class="projects-table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>معلومات المشروع</h2>
            </div>
            <div style="padding: 1.5rem; background: white;">
                <div class="project-info">
                    <div class="project-avatar" style="width: 50px; height: 50px; font-size: 1.2rem;">
                    </div>
                    <div class="project-details">
                        @if($project->code)
                        <div class="project-code-display">{{ $project->code }}</div>
                        @endif
                        <h4>{{ $project->name }}</h4>
                        @if($project->client)
                        <p style="margin-top: 0.5rem;">العميل: <strong>{{ $project->client->name }}</strong></p>
                        @endif
                    </div>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-weight: 600; color: #6b7280;">الحالة الحالية:</span>
                        @php
                        $statusClasses = [
                        'جديد' => 'status-new',
                        'جاري التنفيذ' => 'status-in-progress',
                        'مكتمل' => 'status-completed',
                        'ملغي' => 'status-cancelled'
                        ];
                        $statusClass = $statusClasses[$project->status] ?? 'status-new';
                        @endphp
                        <span class="status-badge {{ $statusClass }}">
                            {{ $project->status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>تحديث معلومات التسليم</h2>
            </div>

            <form action="{{ route('projects.internal-delivery.update-status', $project) }}" method="POST" style="padding: 2rem; background: white;">
                @csrf

                <!-- Alert Info -->
                <div class="alert alert-info" style="margin-bottom: 2rem;">
                    <i class="fas fa-info-circle"></i>
                    سيتم تسجيل تاريخ ووقت التسليم تلقائياً عند الحفظ
                </div>

                <!-- Status Selection -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 1rem; font-size: 1rem;">
                        اختر حالة المشروع الجديدة <span style="color: #ef4444;">*</span>
                    </label>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <label class="filter-group" style="cursor: pointer; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.3s;">
                            <input type="radio" name="status" value="جديد" {{ old('status', $project->status) == 'جديد' ? 'checked' : '' }} style="margin-left: 0.5rem;">
                            <span style="font-weight: 600;">جديد</span>
                        </label>

                        <label class="filter-group" style="cursor: pointer; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.3s;">
                            <input type="radio" name="status" value="جاري التنفيذ" {{ old('status', $project->status) == 'جاري التنفيذ' ? 'checked' : '' }} style="margin-left: 0.5rem;">
                            <span style="font-weight: 600;">جاري التنفيذ</span>
                        </label>

                        <label class="filter-group" style="cursor: pointer; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.3s;">
                            <input type="radio" name="status" value="مكتمل" {{ old('status', $project->status) == 'مكتمل' ? 'checked' : '' }} style="margin-left: 0.5rem;">
                            <span style="font-weight: 600;">مكتمل</span>
                        </label>

                        <label class="filter-group" style="cursor: pointer; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.3s;">
                            <input type="radio" name="status" value="ملغي" {{ old('status', $project->status) == 'ملغي' ? 'checked' : '' }} style="margin-left: 0.5rem;">
                            <span style="font-weight: 600;">ملغي</span>
                        </label>
                    </div>

                    @error('status')
                    <small class="text-danger" style="display: block; margin-top: 0.5rem;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Delivery Type -->
                <div class="filters-section" style="margin-bottom: 2rem;">
                    <div class="filter-group">
                        <label for="delivery_type" class="filter-label">
                            نوع التسليم (اختياري)
                        </label>
                        <select id="delivery_type" name="delivery_type" class="filter-select">
                            <option value="">اختر نوع التسليم</option>
                            <option value="مسودة" {{ old('delivery_type', $project->delivery_type) == 'مسودة' ? 'selected' : '' }}>تسليم مسودة</option>
                            <option value="كامل" {{ old('delivery_type', $project->delivery_type) == 'كامل' ? 'selected' : '' }}>تسليم كامل</option>
                        </select>
                        @error('delivery_type')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <!-- Delivery Notes -->
                <div class="filters-section" style="margin-bottom: 2rem;">
                    <div class="filter-group">
                        <label for="delivery_notes" class="filter-label">
                            ملاحظات التسليم (اختياري)
                        </label>
                        <textarea id="delivery_notes"
                            name="delivery_notes"
                            rows="4"
                            class="filter-select search-input"
                            placeholder="أضف أي ملاحظات خاصة بالتسليم..."
                            style="resize: vertical;">{{ old('delivery_notes', $project->delivery_notes) }}</textarea>
                        @error('delivery_notes')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1.5rem; border-top: 2px solid #e5e7eb;">
                    <a href="{{ route('projects.internal-delivery.index') }}" class="clear-filters-btn" style="width: auto;">
                        إلغاء
                    </a>
                    <button type="submit" class="search-btn" style="width: auto; padding: 0.75rem 2rem;">
                        حفظ التسليم
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects to radio buttons
        const radioLabels = document.querySelectorAll('label[style*="cursor: pointer"]');

        radioLabels.forEach(label => {
            const radio = label.querySelector('input[type="radio"]');

            if (radio) {
                // Check initial state
                if (radio.checked) {
                    label.style.borderColor = '#667eea';
                    label.style.background = 'linear-gradient(135deg, #f0f4ff 0%, #e8f0ff 100%)';
                }

                // Add change event
                radio.addEventListener('change', function() {
                    // Reset all labels
                    radioLabels.forEach(l => {
                        l.style.borderColor = '#e5e7eb';
                        l.style.background = 'white';
                    });

                    // Highlight selected
                    if (this.checked) {
                        label.style.borderColor = '#667eea';
                        label.style.background = 'linear-gradient(135deg, #f0f4ff 0%, #e8f0ff 100%)';
                    }
                });

                // Add hover effect
                label.addEventListener('mouseenter', function() {
                    if (!radio.checked) {
                        this.style.borderColor = '#9ca3af';
                    }
                });

                label.addEventListener('mouseleave', function() {
                    if (!radio.checked) {
                        this.style.borderColor = '#e5e7eb';
                    }
                });
            }
        });
    });
</script>
@endpush