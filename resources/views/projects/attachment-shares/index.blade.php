@extends('layouts.app')

@section('title', 'الملفات المشاركة')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-services.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📁 الملفات المشاركة</h1>
            <p>عرض وإدارة جميع الملفات المشاركة معك والملفات التي قمت بمشاركتها</p>
        </div>

        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $statistics['sent_count'] ?? 0 }}</div>
                <div class="stat-label">مرسلة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $statistics['received_count'] ?? 0 }}</div>
                <div class="stat-label">مستقبلة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $statistics['total_views'] ?? 0 }}</div>
                <div class="stat-label">إجمالي المشاهدات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $statistics['expired_count'] ?? 0 }}</div>
                <div class="stat-label">منتهية الصلاحية</div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-row">
                <div class="filter-group">
                    <button type="button"
                            class="filter-select {{ $currentType === 'received' ? 'active' : '' }}"
                            onclick="window.location.href='{{ route('attachment-shares.index', ['type' => 'received']) }}'">
                        <i class="fas fa-inbox"></i>
                        المشاركات المستقبلة ({{ $receivedShares->total() }})
                    </button>
                </div>
                <div class="filter-group">
                    <button type="button"
                            class="filter-select {{ $currentType === 'sent' ? 'active' : '' }}"
                            onclick="window.location.href='{{ route('attachment-shares.index', ['type' => 'sent']) }}'">
                        <i class="fas fa-paper-plane"></i>
                        المشاركات المرسلة ({{ $sentShares->total() }})
                    </button>
                </div>
            </div>
        </div>

        <!-- Shares Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>📋 {{ $currentType === 'received' ? 'المشاركات المستقبلة' : 'المشاركات المرسلة' }}</h2>
            </div>

            @if($currentType === 'received')
                <!-- Received Shares Table -->
                <table class="projects-table">
                    <thead>
                        <tr>
                            <th>اسم الملف</th>
                            <th>من</th>
                            <th>الحالة</th>
                            <th>تاريخ المشاركة</th>
                            <th>ينتهي في</th>
                            <th>المشاهدات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receivedShares as $share)
                        <tr class="project-row">
                            <td>
                                <div class="project-info">
                                    <div class="project-avatar">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="project-details">
                                        <h4>{{ $share->attachment->name ?? 'مرفق محذوف' }}</h4>
                                        @if($share->description)
                                            <p>{{ Str::limit($share->description, 50) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="client-info">
                                    {{ $share->sharedBy->name }}
                                </div>
                            </td>
                            <td>
                                <div style="text-align: center;">
                                    @if($share->isExpired())
                                        <span style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; display: inline-block;">
                                            منتهية الصلاحية
                                        </span>
                                    @elseif($share->is_active)
                                        <span style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; display: inline-block;">
                                            نشطة
                                        </span>
                                    @else
                                        <span style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; display: inline-block;">
                                            غير نشطة
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="color: #6b7280; font-size: 0.9rem;">
                                    {{ $share->created_at->format('Y/m/d') }}
                                    <div style="font-size: 0.8rem; color: #9ca3af; margin-top: 4px;">
                                        {{ $share->created_at->format('h:i A') }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($share->expires_at)
                                    <div style="color: #6b7280; font-size: 0.9rem;">
                                        {{ $share->expires_at->format('Y/m/d') }}
                                        <div style="font-size: 0.8rem; color: #9ca3af; margin-top: 4px;">
                                            {{ $share->expires_at->format('h:i A') }}
                                        </div>
                                    </div>
                                @else
                                    <span style="color: #9ca3af;">دائم</span>
                                @endif
                            </td>
                            <td>
                                <div style="text-align: center; font-weight: 600;">
                                    {{ $share->view_count }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                    @if($share->isValid() && $share->attachment)
                                        <a href="{{ route('shared-attachments.view', $share->access_token) }}"
                                           class="services-btn"
                                           style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;"
                                           title="عرض الملف">
                                            <i class="fas fa-eye"></i>
                                            عرض
                                        </a>
                                        <a href="{{ route('shared-attachments.download', [$share->access_token, $share->attachment->id]) }}"
                                           class="services-btn"
                                           style="background: linear-gradient(135deg, #10b981, #059669); color: white;"
                                           title="تحميل الملف">
                                            <i class="fas fa-download"></i>
                                            تحميل
                                        </a>
                                    @else
                                        <span style="color: #9ca3af;">غير متاح</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h4>لا توجد مشاركات مستقبلة</h4>
                                <p>لم يتم مشاركة أي ملفات معك حتى الآن</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                @if($receivedShares->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $receivedShares->appends(['type' => 'received'])->links() }}
                    </div>
                @endif
            @else
                <!-- Sent Shares Table -->
                <table class="projects-table">
                    <thead>
                        <tr>
                            <th>اسم الملف</th>
                            <th>شُورك مع</th>
                            <th>الحالة</th>
                            <th>تاريخ المشاركة</th>
                            <th>ينتهي في</th>
                            <th>المشاهدات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sentShares as $share)
                        <tr class="project-row">
                            <td>
                                <div class="project-info">
                                    <div class="project-avatar">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="project-details">
                                        <h4>{{ $share->attachment->name ?? 'مرفق محذوف' }}</h4>
                                        @if($share->description)
                                            <p>{{ Str::limit($share->description, 50) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="client-info">
                                    {{ $share->sharedWithUsers->count() }} مستخدم
                                    <div style="margin-top: 8px;">
                                        @foreach($share->sharedWithUsers->take(3) as $user)
                                            <img src="{{ $user->profile_photo_url }}"
                                                 alt="{{ $user->name }}"
                                                 class="rounded-circle me-1"
                                                 width="25" height="25"
                                                 title="{{ $user->name }}"
                                                 style="border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        @endforeach
                                        @if($share->sharedWithUsers->count() > 3)
                                            <span style="background: #e5e7eb; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; color: #374151;">
                                                +{{ $share->sharedWithUsers->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="text-align: center;">
                                    @if($share->isExpired())
                                        <span style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; display: inline-block;">
                                            منتهية الصلاحية
                                        </span>
                                    @elseif($share->is_active)
                                        <span style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; display: inline-block;">
                                            نشطة
                                        </span>
                                    @else
                                        <span style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; display: inline-block;">
                                            ملغاة
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="color: #6b7280; font-size: 0.9rem;">
                                    {{ $share->created_at->format('Y/m/d') }}
                                    <div style="font-size: 0.8rem; color: #9ca3af; margin-top: 4px;">
                                        {{ $share->created_at->format('h:i A') }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($share->expires_at)
                                    <div style="color: #6b7280; font-size: 0.9rem;">
                                        {{ $share->expires_at->format('Y/m/d') }}
                                        <div style="font-size: 0.8rem; color: #9ca3af; margin-top: 4px;">
                                            {{ $share->expires_at->format('h:i A') }}
                                        </div>
                                    </div>
                                @else
                                    <div style="color: #10b981; font-size: 0.9rem;">
                                        <i class="fas fa-infinity"></i>
                                        دائم
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div style="text-align: center; font-weight: 600;">
                                    {{ $share->view_count }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                    @if($share->is_active && !$share->isExpired())
                                        <button onclick="copyShareLink('{{ $share->access_token }}')"
                                                class="services-btn"
                                                style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;"
                                                title="نسخ الرابط">
                                            <i class="fas fa-link"></i>
                                            نسخ الرابط
                                        </button>
                                        <button onclick="cancelShare('{{ $share->access_token }}')"
                                                class="services-btn"
                                                style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;"
                                                title="إلغاء المشاركة">
                                            <i class="fas fa-times"></i>
                                            إلغاء
                                        </button>
                                    @else
                                        <span style="color: #9ca3af;">منتهية</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-paper-plane"></i>
                                <h4>لا توجد مشاركات مرسلة</h4>
                                <p>لم تقم بمشاركة أي ملفات حتى الآن</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                @if($sentShares->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $sentShares->appends(['type' => 'sent'])->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    /* تحسين شكل الأزرار في الفلاتر */
    .filter-select.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border-color: #3b82f6;
    }

    .filter-select:not(.active) {
        background: white;
        color: #374151;
        border: 2px solid #e5e7eb;
    }

    .filter-select:hover:not(.active) {
        border-color: #3b82f6;
        background: #f0f9ff;
    }

    .filter-select {
        width: 100%;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: center;
    }
</style>

<script>
// نسخ رابط المشاركة
function copyShareLink(token) {
    const url = `${window.location.origin}/shared-attachments/${token}`;
    navigator.clipboard.writeText(url).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'تم النسخ',
            text: 'تم نسخ الرابط بنجاح',
            timer: 2000,
            showConfirmButton: false
        });
    }).catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'فشل في نسخ الرابط'
        });
    });
}

// إلغاء مشاركة
function cancelShare(token) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'هل تريد إلغاء هذه المشاركة؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'نعم، إلغاء',
        cancelButtonText: 'تراجع'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'جاري الإلغاء...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/projects/shares/token/${token}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: data.message
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'حدث خطأ أثناء إلغاء المشاركة'
                });
            });
        }
    });
}
</script>
@endpush
