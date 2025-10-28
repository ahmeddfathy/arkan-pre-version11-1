@extends('layouts.app')

@section('title', 'المرفقات المشاركة')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- رأس الصفحة -->
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="fas fa-share-alt me-3"></i>
                        المرفقات المشاركة
                    </h2>
                    <p class="mb-0 mt-2 opacity-75">الملفات التي تم مشاركتها معك</p>
                </div>

                <div class="card-body p-4">
                    <!-- معلومات المشاركة -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-user-circle fa-2x text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-0">مشارك بواسطة</h6>
                                    <p class="text-muted mb-0">{{ $shared_by->name }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-clock fa-2x text-info me-3"></i>
                                <div>
                                    <h6 class="mb-0">صالح حتى</h6>
                                    <p class="text-muted mb-0">
                                        @if($expires_at)
                                            {{ $expires_at->format('d/m/Y H:i') }}
                                        @else
                                            بلا انتهاء
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- إحصائيات سريعة -->
                    <div class="row text-center mb-4">
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h3 class="text-primary mb-1">{{ $attachments->count() }}</h3>
                                <small class="text-muted">ملف</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                                    <h3 class="text-success mb-1">{{ $attachments->sum('file_size_mb') }}</h3>
                                <small class="text-muted">MB إجمالي</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h3 class="text-info mb-1">{{ $attachments->pluck('service_type')->unique()->count() }}</h3>
                                <small class="text-muted">فئة</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قائمة الملفات -->
            <div class="card shadow border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-files-o me-2"></i>
                        الملفات المشاركة
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" id="downloadAllBtn">
                        <i class="fas fa-download me-1"></i>
                        تحميل الكل
                    </button>
                </div>

                <div class="card-body p-0">
                    @if($attachments->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد ملفات للعرض</h5>
                            <p class="text-muted">قد تكون المشاركة منتهية الصلاحية أو تم إلغاؤها</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($attachments->groupBy('service_type') as $serviceType => $serviceAttachments)
                                <!-- عنوان الفئة -->
                                <div class="list-group-item bg-light border-0">
                                    <h6 class="mb-0 text-primary">
                                        <i class="fas fa-folder me-2"></i>
                                        {{ $serviceType }}
                                        <span class="badge bg-primary ms-2">{{ $serviceAttachments->count() }}</span>
                                    </h6>
                                </div>

                                <!-- ملفات الفئة -->
                                @foreach($serviceAttachments as $attachment)
                                    <div class="list-group-item border-0 py-3 attachment-item" data-attachment-id="{{ $attachment->id }}">
                                        <div class="d-flex align-items-center">
                                            <!-- أيقونة نوع الملف -->
                                            <div class="me-3">
                                                @php
                                                    $extension = pathinfo($attachment->file_name, PATHINFO_EXTENSION);
                                                    $iconClass = match(strtolower($extension)) {
                                                        'pdf' => 'fas fa-file-pdf text-danger',
                                                        'doc', 'docx' => 'fas fa-file-word text-primary',
                                                        'xls', 'xlsx' => 'fas fa-file-excel text-success',
                                                        'ppt', 'pptx' => 'fas fa-file-powerpoint text-warning',
                                                        'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image text-info',
                                                        'zip', 'rar' => 'fas fa-file-archive text-secondary',
                                                        default => 'fas fa-file text-muted'
                                                    };
                                                @endphp
                                                <i class="{{ $iconClass }} fa-2x"></i>
                                            </div>

                                            <!-- معلومات الملف -->
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 file-name">{{ $attachment->file_name }}</h6>
                                                <div class="d-flex align-items-center text-muted small">
                                                    <span class="me-3">
                                                        <i class="fas fa-weight me-1"></i>
                                                        {{ $attachment->file_size_kb }} KB
                                                    </span>
                                                    <span class="me-3">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        {{ $attachment->created_at->format('d/m/Y') }}
                                                    </span>
                                                    @if($attachment->description)
                                                        <span class="text-info">
                                                            <i class="fas fa-comment me-1"></i>
                                                            {{ Str::limit($attachment->description, 50) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- أزرار العمل -->
                                            <div class="ms-3">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('projects.attachments.view', $attachment->id) }}"
                                                       target="_blank"
                                                       class="btn btn-outline-info btn-sm view-btn"
                                                       title="معاينة">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('shared-attachments.download', [$access_token, $attachment->id]) }}"
                                                       class="btn btn-outline-success btn-sm download-btn"
                                                       title="تحميل">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- شريط التقدم للتحميل -->
                                        <div class="download-progress mt-2" style="display: none;">
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <small class="text-muted">جاري التحميل...</small>
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- معلومات إضافية -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                            <h6>مشاركة آمنة</h6>
                            <small class="text-muted">هذه المشاركة محمية ومؤقتة</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-2x text-info mb-2"></i>
                            <h6>وصول محدود</h6>
                            <small class="text-muted">يمكنك الوصول للملفات خلال فترة الصلاحية</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- تحذير انتهاء الصلاحية -->
            @if($expires_at && $expires_at->diffInHours(now()) < 24)
                <div class="alert alert-warning mt-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>تنبيه:</strong> ستنتهي صلاحية هذه المشاركة خلال
                    <strong>{{ $expires_at->diffForHumans() }}</strong>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- تحسينات الواجهة -->
<style>
.attachment-item {
    transition: all 0.3s ease;
    border-radius: 8px;
    margin: 2px;
}

.attachment-item:hover {
    background-color: #f8f9fa;
    transform: translateX(-5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.file-name {
    font-weight: 600;
    color: #2c3e50;
}

.btn-group .btn {
    border-radius: 6px !important;
    margin: 0 2px;
}

.card {
    border-radius: 12px;
    overflow: hidden;
}

.progress {
    border-radius: 10px;
}

.list-group-item {
    border: none !important;
    border-bottom: 1px solid #e9ecef !important;
}

.list-group-item:last-child {
    border-bottom: none !important;
}

@keyframes downloadPulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.downloading {
    animation: downloadPulse 1s infinite;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحميل فردي للملفات
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const attachmentItem = this.closest('.attachment-item');
            const progressBar = attachmentItem.querySelector('.download-progress');

            // إظهار شريط التقدم
            progressBar.style.display = 'block';
            this.classList.add('downloading');

            // إخفاء شريط التقدم بعد 3 ثوان
            setTimeout(() => {
                progressBar.style.display = 'none';
                this.classList.remove('downloading');
            }, 3000);
        });
    });

    // تحميل جميع الملفات
    document.getElementById('downloadAllBtn').addEventListener('click', function() {
        const downloadBtns = document.querySelectorAll('.download-btn');

        if (confirm(`هل تريد تحميل جميع الملفات (${downloadBtns.length} ملف)؟`)) {
            downloadBtns.forEach((btn, index) => {
                setTimeout(() => {
                    btn.click();
                }, index * 500); // تأخير 500ms بين كل تحميل
            });
        }
    });

    // تحديث شريط التقدم (محاكاة)
    function updateProgressBar(progressElement) {
        let width = 0;
        const interval = setInterval(() => {
            width += Math.random() * 30;
            if (width >= 100) {
                width = 100;
                clearInterval(interval);
            }
            progressElement.querySelector('.progress-bar').style.width = width + '%';
        }, 200);
    }
});
</script>
@endsection
