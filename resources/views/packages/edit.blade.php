@extends('layouts.app')

@section('title', 'تعديل الباقة')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/packages.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>✏️ تعديل الباقة</h1>
            <p>تحديث بيانات الباقة: {{ $package->name }}</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="package-card">
                    <div class="package-card-header">
                        <h2>تعديل بيانات الباقة</h2>
                    </div>
                    <div class="package-card-body">
                        <form action="{{ route('packages.update', $package) }}" method="POST" id="package-form">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label>اسم الباقة <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required value="{{ old('name', $package->name) }}" placeholder="أدخل اسم الباقة">
                            </div>

                            <div class="form-group">
                                <label>الوصف</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="وصف تفصيلي للباقة...">{{ old('description', $package->description) }}</textarea>
                            </div>

                            <!-- Total Points Display -->
                            <div class="total-points-display">
                                <h5>مجموع النقاط</h5>
                                <div class="total-points-number" id="total-points">0</div>
                            </div>

                            <!-- Services Selection -->
                            <div class="services-list-section">
                                <h5><i class="fas fa-cogs"></i> اختر الخدمات المتاحة</h5>

                                @if(count($services) > 0)
                                    <div class="services-grid">
                                        @foreach($services as $service)
                                            @php
                                                $isChecked = in_array($service->id, old('services', $selected));
                                            @endphp
                                            <div class="service-checkbox-item {{ $isChecked ? 'selected' : '' }}" onclick="toggleCheckbox({{ $service->id }})">
                                                <div class="service-checkbox-wrapper">
                                                    <input class="service-checkbox"
                                                           type="checkbox"
                                                           name="services[]"
                                                           value="{{ $service->id }}"
                                                           id="service-{{ $service->id }}"
                                                           data-points="{{ $service->points }}"
                                                           {{ $isChecked ? 'checked' : '' }}
                                                           onchange="updateTotalPoints(); updateItemStyle({{ $service->id }})">
                                                    <label class="service-checkbox-label" for="service-{{ $service->id }}">
                                                        {{ $service->name }}
                                                    </label>
                                                    <span class="service-points-badge">
                                                        <i class="fas fa-star"></i>
                                                        {{ $service->points }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p style="color: #6b7280; text-align: center; padding: 2rem;">
                                        <i class="fas fa-info-circle"></i> لا توجد خدمات متاحة
                                    </p>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div style="margin-top: 2rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                                <button type="submit" class="packages-btn btn-warning">
                                    <i class="fas fa-save"></i>
                                    تحديث الباقة
                                </button>

                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="{{ route('packages.show', $package) }}" class="packages-btn btn-info">
                                        <i class="fas fa-eye"></i>
                                        عرض
                                    </a>
                                    <a href="{{ route('packages.index') }}" class="packages-btn btn-secondary">
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

@push('scripts')
<script>
    function updateTotalPoints() {
        let total = 0;
        document.querySelectorAll('.service-checkbox:checked').forEach(function(cb) {
            total += parseInt(cb.getAttribute('data-points'));
        });
        document.getElementById('total-points').textContent = total;
    }

    function updateItemStyle(serviceId) {
        const checkbox = document.getElementById('service-' + serviceId);
        const item = checkbox.closest('.service-checkbox-item');
        if (checkbox.checked) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    }

    function toggleCheckbox(serviceId) {
        const checkbox = document.getElementById('service-' + serviceId);
        checkbox.checked = !checkbox.checked;
        updateTotalPoints();
        updateItemStyle(serviceId);
    }

    document.querySelectorAll('.service-checkbox').forEach(function(cb) {
        cb.addEventListener('change', updateTotalPoints);
        // Prevent double toggle when clicking the checkbox itself
        cb.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    updateTotalPoints();
</script>
@endpush
@endsection
