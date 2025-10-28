@extends('layouts.app')

@section('title', 'الملفات المشاركة')

@push('styles')
<link href="{{ asset('css/projects/attachment-shares.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- رأس الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-share-alt me-2"></i>
                            الملفات المشاركة
                        </h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-light btn-sm" onclick="refreshShares()">
                                <i class="fas fa-sync-alt"></i>
                                تحديث
                            </button>
                        </div>
                    </div>
                </div>

                <!-- الإحصائيات -->
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="stat-card bg-info text-white p-3 rounded">
                                <i class="fas fa-paper-plane fa-2x mb-2"></i>
                                <h5>{{ $statistics['sent_count'] ?? 0 }}</h5>
                                <p class="mb-0">مرسلة</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-success text-white p-3 rounded">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <h5>{{ $statistics['received_count'] ?? 0 }}</h5>
                                <p class="mb-0">مستقبلة</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-warning text-white p-3 rounded">
                                <i class="fas fa-eye fa-2x mb-2"></i>
                                <h5>{{ $statistics['total_views'] ?? 0 }}</h5>
                                <p class="mb-0">إجمالي المشاهدات</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-danger text-white p-3 rounded">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h5>{{ $statistics['expired_count'] ?? 0 }}</h5>
                                <p class="mb-0">منتهية الصلاحية</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- التبويبات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <ul class="nav nav-tabs card-header-tabs" id="sharesTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $currentType === 'received' ? 'active' : '' }}"
                                    id="received-tab" data-bs-toggle="tab" data-bs-target="#received"
                                    type="button" role="tab" aria-controls="received">
                                <i class="fas fa-inbox me-2"></i>
                                المشاركات المستقبلة ({{ $receivedShares->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $currentType === 'sent' ? 'active' : '' }}"
                                    id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent"
                                    type="button" role="tab" aria-controls="sent">
                                <i class="fas fa-paper-plane me-2"></i>
                                المشاركات المرسلة ({{ $sentShares->count() }})
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="sharesTabContent">
                        <!-- تبويب المشاركات المستقبلة -->
                        <div class="tab-pane fade {{ $currentType === 'received' ? 'show active' : '' }}"
                             id="received" role="tabpanel">
                            @if($receivedShares->count() > 0)
                                <div class="row">
                                    @foreach($receivedShares as $share)
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="share-card card h-100 {{ $share->isExpired() ? 'expired' : ($share->is_active ? 'active' : 'inactive') }}">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <img src="{{ $share->sharedBy->profile_photo_url }}"
                                                             alt="{{ $share->sharedBy->name }}"
                                                             class="rounded-circle me-2" width="30" height="30">
                                                        <span class="fw-bold">{{ $share->sharedBy->name }}</span>
                                                    </div>
                                                    <div class="share-status">
                                                        @if($share->isExpired())
                                                            <span class="badge bg-danger">منتهية الصلاحية</span>
                                                        @elseif($share->is_active)
                                                            <span class="badge bg-success">نشطة</span>
                                                        @else
                                                            <span class="badge bg-secondary">غير نشطة</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="card-body">
                                                    <h6 class="card-title">{{ $share->attachment->name ?? 'مرفق محذوف' }}</h6>

                                                    @if($share->description)
                                                        <p class="card-text text-muted small">{{ Str::limit($share->description, 100) }}</p>
                                                    @endif

                                                    <div class="share-info">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            {{ $share->created_at->diffForHumans() }}
                                                        </small>
                                                        @if($share->expires_at)
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>
                                                                ينتهي: {{ $share->expires_at->format('Y/m/d H:i') }}
                                                            </small>
                                                        @endif
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-eye me-1"></i>
                                                            {{ $share->view_count }} مشاهدة
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="card-footer bg-light">
                                                    @if($share->isValid() && $share->attachment)
                                                        <a href="{{ route('shared-attachments.view', $share->access_token) }}"
                                                           class="btn btn-primary btn-sm me-2">
                                                            <i class="fas fa-eye me-1"></i>
                                                            عرض
                                                        </a>
                                                        <a href="{{ route('shared-attachments.download', [$share->access_token, $share->attachment->id]) }}"
                                                           class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-download me-1"></i>
                                                            تحميل
                                                        </a>
                                                    @else
                                                        <span class="text-muted">غير متاح للعرض</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-center">
                                    {{ $receivedShares->appends(['type' => 'received'])->links() }}
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                    <h5 class="text-muted">لا توجد مشاركات مستقبلة</h5>
                                    <p class="text-muted">لم يتم مشاركة أي ملفات معك حتى الآن</p>
                                </div>
                            @endif
                        </div>

                        <!-- تبويب المشاركات المرسلة -->
                        <div class="tab-pane fade {{ $currentType === 'sent' ? 'show active' : '' }}"
                             id="sent" role="tabpanel">
                            @if($sentShares->count() > 0)
                                <div class="row">
                                    @foreach($sentShares as $share)
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="share-card card h-100 {{ $share->isExpired() ? 'expired' : ($share->is_active ? 'active' : 'inactive') }}">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <div class="share-info">
                                                        <h6 class="mb-1">{{ $share->attachment->name ?? 'مرفق محذوف' }}</h6>
                                                        <small class="text-muted">
                                                            شُورك مع {{ count($share->shared_with ?? []) }} مستخدم
                                                        </small>
                                                    </div>
                                                    <div class="share-actions">
                                                        @if($share->is_active)
                                                            <button class="btn btn-outline-danger btn-sm"
                                                                    onclick="cancelShare('{{ $share->access_token }}')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="card-body">

                                                    @if($share->description)
                                                        <p class="card-text text-muted small">{{ Str::limit($share->description, 100) }}</p>
                                                    @endif

                                                    <div class="share-info">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            {{ $share->created_at->diffForHumans() }}
                                                        </small>
                                                        @if($share->expires_at)
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>
                                                                ينتهي: {{ $share->expires_at->format('Y/m/d H:i') }}
                                                            </small>
                                                        @else
                                                            <br>
                                                            <small class="text-success">
                                                                <i class="fas fa-infinity me-1"></i>
                                                                بدون انتهاء صلاحية
                                                            </small>
                                                        @endif
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-eye me-1"></i>
                                                            {{ $share->view_count }} مشاهدة
                                                        </small>
                                                    </div>

                                                    <!-- المستخدمين المشارك معهم -->
                                                    <div class="shared-users mt-3">
                                                        <small class="text-muted d-block mb-1">شُورك مع:</small>
                                                        <div class="user-avatars">
                                                            @foreach($share->sharedWithUsers->take(3) as $user)
                                                                <img src="{{ $user->profile_photo_url }}"
                                                                     alt="{{ $user->name }}"
                                                                     class="rounded-circle me-1"
                                                                     width="25" height="25"
                                                                     title="{{ $user->name }}">
                                                            @endforeach
                                                            @if($share->sharedWithUsers->count() > 3)
                                                                <span class="more-users">+{{ $share->sharedWithUsers->count() - 3 }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="card-footer bg-light">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="share-status">
                                                            @if($share->isExpired())
                                                                <span class="badge bg-danger">منتهية الصلاحية</span>
                                                            @elseif($share->is_active)
                                                                <span class="badge bg-success">نشطة</span>
                                                            @else
                                                                <span class="badge bg-secondary">ملغاة</span>
                                                            @endif
                                                        </div>

                                                        @if($share->is_active && !$share->isExpired())
                                                            <button class="btn btn-outline-primary btn-sm"
                                                                    onclick="copyShareLink('{{ $share->access_token }}')">
                                                                <i class="fas fa-link me-1"></i>
                                                                نسخ الرابط
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-center">
                                    {{ $sentShares->appends(['type' => 'sent'])->links() }}
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-paper-plane fa-4x text-muted mb-3"></i>
                                    <h5 class="text-muted">لا توجد مشاركات مرسلة</h5>
                                    <p class="text-muted">لم تقم بمشاركة أي ملفات حتى الآن</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// نسخ رابط المشاركة
function copyShareLink(token) {
    const url = `${window.location.origin}/shared-attachments/${token}`;
    navigator.clipboard.writeText(url).then(() => {
        Toast.success('تم نسخ الرابط بنجاح');
    }).catch(() => {
        Toast.error('فشل في نسخ الرابط');
    });
}

// إلغاء مشاركة
function cancelShare(token) {
    if (!confirm('هل أنت متأكد من إلغاء هذه المشاركة؟')) {
        return;
    }

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
            Toast.success(data.message);
            location.reload();
        } else {
            Toast.error(data.message);
        }
    })
    .catch(() => {
        Toast.error('حدث خطأ أثناء إلغاء المشاركة');
    });
}

// تحديث الصفحة
function refreshShares() {
    location.reload();
}

// تذكر التبويب المحدد
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('type') || 'received';

    if (type === 'sent') {
        document.getElementById('sent-tab').click();
    } else {
        document.getElementById('received-tab').click();
    }
});

// تحديث URL عند تغيير التبويب
document.getElementById('received-tab').addEventListener('click', function() {
    const url = new URL(window.location);
    url.searchParams.set('type', 'received');
    window.history.pushState({}, '', url);
});

document.getElementById('sent-tab').addEventListener('click', function() {
    const url = new URL(window.location);
    url.searchParams.set('type', 'sent');
    window.history.pushState({}, '', url);
});
</script>
@endpush
