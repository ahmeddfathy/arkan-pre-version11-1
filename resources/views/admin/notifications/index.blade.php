@extends('admin.notifications.layouts.notification-layout')

@section('page-title', 'إدارة الإشعارات')

@section('page-actions')
<a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i>
    إنشاء إشعار جديد
</a>
@endsection

@section('notification-content')
<!-- إحصائيات عامة -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stats-card">
            <h3>{{ $totalNotifications }}</h3>
            <p>إجمالي الإشعارات</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card success">
            <h3>{{ $readNotifications }}</h3>
            <p>تمت القراءة</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card warning">
            <h3>{{ $unreadNotifications }}</h3>
            <p>في انتظار القراءة</p>
        </div>
    </div>
</div>

<!-- فلتر البحث -->
<div class="filter-section">
    <form method="GET" action="{{ route('admin.notifications.index') }}" class="row g-3">
        <div class="col-md-4">
            <div class="form-floating">
                <select class="form-select" id="read_status" name="read_status">
                    <option value="">الكل</option>
                    <option value="read" {{ request('read_status') == 'read' ? 'selected' : '' }}>تمت القراءة</option>
                    <option value="unread" {{ request('read_status') == 'unread' ? 'selected' : '' }}>لم تتم القراءة</option>
                </select>
                <label for="read_status">حالة القراءة</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <select class="form-select" id="type" name="type">
                    <option value="">الكل</option>
                    <option value="administrative_decision" {{ request('type') == 'administrative_decision' ? 'selected' : '' }}>
                        قرار إداري
                    </option>
                    <option value="admin_broadcast" {{ request('type') == 'admin_broadcast' ? 'selected' : '' }}>
                        إشعار عادي
                    </option>
                </select>
                <label for="type">نوع الإشعار</label>
            </div>
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <button type="submit" class="btn btn-filter primary">
                <i class="fas fa-filter"></i>
                تصفية
            </button>
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-filter secondary ms-2">
                <i class="fas fa-redo"></i>
                إعادة تعيين
            </a>
        </div>
    </form>
</div>

<!-- قائمة الإشعارات -->
<div class="notification-list">
    @foreach($notifications as $notification)
    <div class="notification-item {{ $notification->type === 'administrative_decision' ? 'administrative' : '' }}">
        <div class="notification-content">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h5 class="notification-title">
                        <i class="fas {{ $notification->type === 'administrative_decision' ? 'fa-gavel' : 'fa-bell' }}"></i>
                        {{ $notification->data['title'] ?? '' }}
                    </h5>
                    <p class="card-text">{{ Str::limit($notification->data['message'] ?? '', 100) }}</p>
                    <div class="notification-meta">
                        @if($notification->type === 'administrative_decision')
                        <span class="notification-badge badge-admin">
                            <i class="fas fa-gavel me-1"></i>
                            قرار إداري
                        </span>
                        @else
                        <span class="notification-badge badge-normal">
                            <i class="fas fa-bell me-1"></i>
                            إشعار عادي
                        </span>
                        @endif
                        @if($notification->data['requires_acknowledgment'] ?? false)
                        <span class="notification-badge badge-warning">
                            <i class="fas fa-check-double me-1"></i>
                            يتطلب تأكيد
                        </span>
                        @endif
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="progress-container">
                        <div class="progress-bar-custom">
                            @php
                            $percentage = 0;
                            if ($notification->total_recipients > 0) {
                                $percentage = ($notification->read_count / $notification->total_recipients) * 100;
                            }
                            @endphp
                            <div class="progress-fill" style="width: {{ $percentage }}%;"></div>
                        </div>
                        <div class="progress-stats">
                            <strong>{{ $notification->read_count }}</strong>
                            <span>من</span>
                            <strong>{{ $notification->total_recipients }}</strong>
                            <span>قرأوا الإشعار</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="notification-actions">
                        <a href="{{ route('admin.notifications.show', $notification) }}"
                            class="action-btn btn-view"
                            title="عرض التفاصيل">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.notifications.edit', $notification) }}"
                            class="action-btn btn-edit"
                            title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.notifications.destroy', $notification) }}"
                            method="POST"
                            class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="action-btn btn-delete"
                                title="حذف"
                                onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <small class="text-muted">
                <i class="far fa-clock"></i>
                {{ $notification->created_at->format('Y-m-d H:i') }}
            </small>
        </div>
    </div>
    @endforeach

    @if($notifications->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-bell-slash"></i>
        </div>
        <h4>لا توجد إشعارات</h4>
        <p>لم يتم العثور على أي إشعارات تطابق معايير البحث</p>
        <a href="{{ route('admin.notifications.create') }}" class="btn btn-filter primary">
            <i class="fas fa-plus"></i>
            إنشاء إشعار جديد
        </a>
    </div>
    @endif
</div>

<div class="pagination-wrapper">
    {{ $notifications->links() }}
</div>
@endsection

@push('page-styles')
<link rel="stylesheet" href="{{ asset('css/admin/notifications.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/notification-index.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
@endpush

@push('page-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statBoxes = document.querySelectorAll('.stats-card');
        const notificationItems = document.querySelectorAll('.notification-item');

        // تأثيرات للإحصائيات
        statBoxes.forEach((box, index) => {
            box.style.opacity = '0';
            box.style.transform = 'translateY(20px)';
            setTimeout(() => {
                box.style.transition = 'all 0.5s ease';
                box.style.opacity = '1';
                box.style.transform = 'translateY(0)';
            }, 100 * (index + 1));
        });

        // تأثيرات للإشعارات
        notificationItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            setTimeout(() => {
                item.style.transition = 'all 0.5s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, 300 + (100 * index));
        });
    });
</script>
@endpush
