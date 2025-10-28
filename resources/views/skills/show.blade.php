@extends('layouts.app')

@section('title', 'تفاصيل المهارة')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/skills.css') }}">
<style>
<style>
    .details-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .details-header {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .details-header h2 {
        margin: 0 0 0.5rem 0;
        font-size: 1.8rem;
        font-weight: 600;
    }

    .details-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 1rem;
    }

    .details-body {
        padding: 2rem;
    }

    .detail-row {
        padding: 1.5rem;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: flex-start;
        gap: 2rem;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        min-width: 180px;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detail-label i {
        color: #667eea;
        font-size: 1.1rem;
    }

    .detail-value {
        flex: 1;
        color: #1f2937;
    }

    .description-box {
        background: #f9fafb;
        border-right: 4px solid #667eea;
        padding: 1rem;
        border-radius: 8px;
        line-height: 1.6;
    }

    .no-description {
        color: #9ca3af;
        font-style: italic;
    }

    .actions-footer {
        background: #f9fafb;
        padding: 1.5rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        border-top: 2px solid #e5e7eb;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .quick-action-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }

    .quick-action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .quick-action-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .quick-action-title {
        color: #1f2937;
        font-weight: 600;
        margin: 0;
    }

    .info-alerts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .alert-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-right: 4px solid;
    }

    .alert-card h4 {
        font-size: 1rem;
        margin: 0 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alert-card ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .alert-card ul li {
        padding: 0.5rem 0;
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .alert-card ul li i {
        margin-top: 0.25rem;
        flex-shrink: 0;
    }

    .alert-info-custom {
        border-right-color: #3b82f6;
    }

    .alert-info-custom h4 {
        color: #1e40af;
    }

    .alert-info-custom h4 i {
        color: #3b82f6;
    }

    .alert-info-custom ul li {
        color: #1f2937;
    }

    .alert-info-custom ul li i {
        color: #3b82f6;
    }

    .alert-warning-custom {
        border-right-color: #f59e0b;
    }

    .alert-warning-custom h4 {
        color: #92400e;
    }

    .alert-warning-custom h4 i {
        color: #f59e0b;
    }

    .alert-warning-custom ul li {
        color: #92400e;
    }

    .alert-warning-custom ul li i {
        color: #f59e0b;
    }
</style>
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>⭐ تفاصيل المهارة</h1>
            <p>معلومات تفصيلية عن المهارة</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $skill->max_points }}</div>
                <div class="stat-label">النقاط القصوى</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    @if($skill->is_active)
                        نشط
                    @else
                        غير نشط
                    @endif
                </div>
                <div class="stat-label">الحالة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    {{ $skill->created_at->diffInDays(now()) }}
                </div>
                <div class="stat-label">عمر المهارة (أيام)</div>
            </div>
        </div>

        <!-- Details Container -->
        <div class="details-container">
            <div class="details-header">
                <h2>{{ $skill->name }}</h2>
                <p>معلومات كاملة عن المهارة</p>
            </div>

            <div class="details-body">
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-star"></i>
                        اسم المهارة:
                    </div>
                    <div class="detail-value">
                        <h4 style="margin: 0; color: #667eea;">{{ $skill->name }}</h4>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-tags"></i>
                        التصنيف:
                    </div>
                    <div class="detail-value">
                        <span class="status-badge status-new">
                            <i class="fas fa-tag ml-1"></i>
                            {{ optional($skill->category)->name ?? 'بدون تصنيف' }}
                        </span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-trophy"></i>
                        الحد الأقصى للنقاط:
                    </div>
                    <div class="detail-value">
                        <span class="status-badge status-in-progress">
                            <i class="fas fa-star ml-1"></i>
                            {{ $skill->max_points }} نقطة
                        </span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-toggle-on"></i>
                        الحالة:
                    </div>
                    <div class="detail-value">
                        @if($skill->is_active)
                            <span class="status-badge status-completed">
                                <i class="fas fa-check-circle ml-1"></i>
                                نشط
                            </span>
                        @else
                            <span class="status-badge status-cancelled">
                                <i class="fas fa-times-circle ml-1"></i>
                                غير نشط
                            </span>
                            <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle ml-1"></i>
                                المهارات غير النشطة لا يمكن استخدامها في التقييمات الجديدة
                            </small>
                        @endif
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-align-left"></i>
                        الوصف:
                    </div>
                    <div class="detail-value">
                        @if($skill->description)
                            <div class="description-box">
                                {{ $skill->description }}
                            </div>
                        @else
                            <span class="no-description">
                                <i class="fas fa-minus-circle ml-1"></i>
                                لا يوجد وصف متاح لهذه المهارة
                            </span>
                        @endif
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-calendar-plus"></i>
                        تاريخ الإنشاء:
                    </div>
                    <div class="detail-value">
                        <span class="status-badge status-new">
                            <i class="fas fa-clock ml-1"></i>
                            {{ $skill->created_at->format('d/m/Y') }} في {{ $skill->created_at->format('H:i') }}
                        </span>
                        <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                            منذ {{ $skill->created_at->diffForHumans() }}
                        </small>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-calendar-check"></i>
                        آخر تحديث:
                    </div>
                    <div class="detail-value">
                        <span class="status-badge status-in-progress">
                            <i class="fas fa-clock ml-1"></i>
                            {{ $skill->updated_at->format('d/m/Y') }} في {{ $skill->updated_at->format('H:i') }}
                        </span>
                        <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                            منذ {{ $skill->updated_at->diffForHumans() }}
                        </small>
                    </div>
                </div>
            </div>

            <div class="actions-footer">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <form action="{{ route('skills.destroy', $skill) }}"
                          method="POST"
                          id="deleteForm"
                          data-skill-name="{{ $skill->name }}"
                          data-skill-active="{{ $skill->is_active ? 'true' : 'false' }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="services-btn" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="fas fa-trash-alt ml-1"></i>
                            حذف المهارة
                        </button>
                    </form>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="{{ route('skills.edit', $skill) }}" class="services-btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="fas fa-edit ml-1"></i>
                        تعديل المهارة
                    </a>
                    <a href="{{ route('skills.create') }}" class="services-btn" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-plus-circle ml-1"></i>
                        إضافة مهارة جديدة
                    </a>
                </div>
            </div>
        </div>

        <!-- Information Alerts -->
        <div class="info-alerts">
            <div class="alert-card alert-info-custom">
                <h4>
                    <i class="fas fa-lightbulb"></i>
                    نصائح الاستخدام
                </h4>
                <ul>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>يمكن استخدام هذه المهارة في تقييم الموظفين</span>
                    </li>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>النقاط المحددة تؤثر على إجمالي التقييم</span>
                    </li>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>تأكد من تحديث الوصف عند الحاجة</span>
                    </li>
                </ul>
            </div>

            <div class="alert-card alert-warning-custom">
                <h4>
                    <i class="fas fa-exclamation-triangle"></i>
                    تنبيهات مهمة
                </h4>
                <ul>
                    <li>
                        <i class="fas fa-info-circle"></i>
                        <span>حذف المهارة سيؤثر على التقييمات السابقة</span>
                    </li>
                    <li>
                        <i class="fas fa-ban"></i>
                        <span>تعطيل المهارة يمنع استخدامها في التقييمات</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>تأكد من صحة البيانات قبل الحفظ</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>⚡ إجراءات سريعة</h2>
            </div>
            <div style="padding: 2rem;">
                <div class="quick-actions-grid">
                    <a href="{{ route('skills.edit', $skill) }}" class="quick-action-card">
                        <div class="quick-action-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white;">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h4 class="quick-action-title">تعديل البيانات</h4>
                    </a>

                    <a href="{{ route('skills.create') }}" class="quick-action-card">
                        <div class="quick-action-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                            <i class="fas fa-copy"></i>
                        </div>
                        <h4 class="quick-action-title">نسخ كمهارة جديدة</h4>
                    </a>

                    <a href="{{ route('skill-categories.show', optional($skill->category)->id ?? '#') }}"
                       class="quick-action-card {{ !$skill->category ? 'disabled' : '' }}"
                       style="{{ !$skill->category ? 'opacity: 0.5; pointer-events: none;' : '' }}">
                        <div class="quick-action-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h4 class="quick-action-title">عرض التصنيف</h4>
                    </a>

                    <a href="{{ route('skills.index') }}" class="quick-action-card">
                        <div class="quick-action-icon" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white;">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <h4 class="quick-action-title">العودة للمهارات</h4>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('deleteForm');

    deleteForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const skillName = deleteForm.dataset.skillName;
        const isActive = deleteForm.dataset.skillActive === 'true';

        let message = 'هل تريد حذف مهارة "<strong>' + skillName + '</strong>"؟<br><br>' +
                     '<span style="color: #dc2626;">تحذير: هذا الإجراء لا يمكن التراجع عنه!</span><br><br>' +
                     'سيتم حذف:<br>' +
                     '• بيانات المهارة<br>' +
                     '• جميع التقييمات المرتبطة بها<br>' +
                     '• النقاط المحسوبة للموظفين';

        if (isActive) {
            message += '<br><br><span style="color: #f59e0b;">ملاحظة: هذه المهارة نشطة حالياً وقد تؤثر على التقييمات الجارية.</span>';
        }

        Swal.fire({
            title: 'هل أنت متأكد؟',
            html: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            input: 'text',
            inputPlaceholder: 'اكتب "حذف" للتأكيد',
            inputValidator: (value) => {
                if (value !== 'حذف') {
                    return 'يجب كتابة "حذف" للتأكيد'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                deleteForm.submit();
            }
        });
    });
});
</script>
@endpush
