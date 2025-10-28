@extends('layouts.app')

@section('title', 'تفاصيل الخدمة')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/company-services-design.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>🔍 تفاصيل الخدمة</h1>
            <p>عرض معلومات تفصيلية عن الخدمة</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="service-card">
                    <div class="service-card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h2>{{ $companyService->name }}</h2>
                            <span class="status-badge {{ $companyService->is_active ? 'active' : 'inactive' }}">
                                @if($companyService->is_active)
                                    <i class="fas fa-check-circle"></i> نشط
                                @else
                                    <i class="fas fa-times-circle"></i> غير نشط
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="service-card-body">
                        <!-- Service Information Grid -->
                        <div class="service-info-grid">
                            <div class="service-info-item">
                                <h5><i class="fas fa-hashtag"></i> رقم الخدمة</h5>
                                <p>{{ $companyService->id }}</p>
                            </div>

                            <div class="service-info-item">
                                <h5><i class="fas fa-star"></i> النقاط المستحقة</h5>
                                <p>
                                    <span class="points-badge">
                                        <i class="fas fa-star"></i> {{ $companyService->points }} نقطة
                                    </span>
                                </p>
                            </div>

                            <div class="service-info-item">
                                <h5><i class="fas fa-shield-alt"></i> حد النقاط لكل مشروع</h5>
                                <p>
                                    @if($companyService->max_points_per_project > 0)
                                        <span class="limit-badge">
                                            <i class="fas fa-shield-alt"></i> {{ $companyService->max_points_per_project }} نقطة
                                        </span>
                                    @else
                                        <span class="unlimited-badge">
                                            <i class="fas fa-infinity"></i> بلا حدود
                                        </span>
                                    @endif
                                </p>
                            </div>

                            <div class="service-info-item">
                                <h5><i class="fas fa-building"></i> القسم</h5>
                                <p>{{ $companyService->department ?? 'كل الأقسام' }}</p>
                            </div>

                            <div class="service-info-item">
                                <h5><i class="fas fa-calendar-alt"></i> تاريخ الإنشاء</h5>
                                <p>{{ $companyService->created_at->format('Y/m/d') }}</p>
                            </div>

                            <div class="service-info-item">
                                <h5><i class="fas fa-clock"></i> آخر تحديث</h5>
                                <p>{{ $companyService->updated_at->format('Y/m/d') }}</p>
                            </div>
                        </div>

                        <!-- Description Section -->
                        <div class="service-description">
                            <h5><i class="fas fa-align-left"></i> الوصف</h5>
                            <p>{{ $companyService->description ?? 'لا يوجد وصف متاح' }}</p>
                        </div>

                        <!-- Required Roles Section -->
                        <div class="roles-section">
                            <h5><i class="fas fa-user-tag"></i> الأدوار المطلوبة للخدمة</h5>

                            @if($companyService->requiredRoles->count() > 0)
                                <div class="roles-list">
                                    @foreach($companyService->requiredRoles as $role)
                                        <div class="role-badge">
                                            <i class="fas fa-user-tag"></i>
                                            {{ $role->name }}
                                            <button type="button" class="remove-role-btn"
                                                    data-role-id="{{ $role->id }}"
                                                    data-role-name="{{ $role->name }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p style="color: #6b7280; margin-bottom: 1rem;">
                                    <i class="fas fa-info-circle"></i> لا توجد أدوار مطلوبة محددة لهذه الخدمة
                                </p>
                            @endif

                            <!-- Add Role Form -->
                            <form id="addRoleForm" class="add-role-form">
                                @csrf
                                <div class="form-group">
                                    <label><i class="fas fa-plus-circle"></i> إضافة دور مطلوب</label>
                                    <select name="role_id" id="roleSelect" class="form-control">
                                        <option value="">-- اختر دور --</option>
                                        @foreach($allRoles as $role)
                                            @if(!$companyService->requiredRoles->contains($role->id))
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="services-btn btn-success">
                                    <i class="fas fa-plus"></i>
                                    إضافة
                                </button>
                            </form>
                        </div>

                        <!-- Action Buttons -->
                        <div style="margin-top: 2rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <form action="{{ route('company-services.toggle-status', $companyService) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="services-btn {{ $companyService->is_active ? 'btn-danger' : 'btn-success' }}">
                                        @if($companyService->is_active)
                                            <i class="fas fa-ban"></i> إلغاء تنشيط
                                        @else
                                            <i class="fas fa-check-circle"></i> تنشيط
                                        @endif
                                    </button>
                                </form>
                            </div>

                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="{{ route('company-services.edit', $companyService) }}" class="services-btn btn-warning">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </a>
                                <a href="{{ route('company-services.index') }}" class="services-btn btn-primary">
                                    <i class="fas fa-list"></i>
                                    قائمة الخدمات
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const serviceId = {{ $companyService->id }};

    // إضافة دور
    $('#addRoleForm').submit(function(e) {
        e.preventDefault();
        const roleId = $('#roleSelect').val();

        if (!roleId) {
            alert('الرجاء اختيار دور');
            return;
        }

        $.ajax({
            url: `/company-services/${serviceId}/roles`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                role_id: roleId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            },
            error: function(xhr) {
                alert('حدث خطأ أثناء إضافة الدور');
            }
        });
    });

    // حذف دور
    $(document).on('click', '.remove-role-btn', function() {
        const roleId = $(this).data('role-id');
        const roleName = $(this).data('role-name');

        if (confirm(`هل أنت متأكد من حذف الدور "${roleName}"؟`)) {
            $.ajax({
                url: `/company-services/${serviceId}/roles/${roleId}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert('حدث خطأ أثناء حذف الدور');
                }
            });
        }
    });
});
</script>
@endpush
@endsection
