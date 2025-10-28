@extends('layouts.app')

@section('title', 'اختيار الدور - إضافة بنود التقييم')

@push('styles')
    <link href="{{ asset('css/evaluation-criteria-modern.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid evaluation-container">
    <div class="row">
        <div class="col-12">
            <!-- 🎯 Header Section -->
            <div class="modern-card mb-5 fade-in-up">
                <div class="text-center p-5" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%); border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
                    <div class="d-inline-block p-3 rounded-circle mb-4 floating" style="background: linear-gradient(135deg, #667eea, #764ba2); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);">
                        <i class="fas fa-users-cog fa-3x text-white"></i>
                    </div>
                    <h1 class="display-5 fw-bold mb-3" style="color: #2c3e50;">
                        ✨ اختر الدور لإضافة بنود التقييم
                    </h1>
                    <p class="lead mb-4" style="color: #6c757d;">
                        حدد الدور المطلوب لإضافة بنود التقييم الخاصة به بطريقة سهلة وسريعة
                    </p>
                    <a href="{{ route('evaluation-criteria.index') }}" class="btn btn-modern btn-primary-modern">
                        <i class="fas fa-arrow-left me-2"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>

            <div class="modern-card slide-in-right">
                <div class="modern-card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-magic me-2"></i>
                        اختر الدور المطلوب
                    </h3>
                </div>
                <div class="modern-card-body">
                    <!-- 💡 Info Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="p-4 rounded-4 border-0" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="p-2 rounded-circle bg-white">
                                            <i class="fas fa-lightbulb text-warning fa-lg"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark">💡 تعليمات سريعة</h6>
                                        <p class="mb-0 text-dark">اختر الدور الذي تريد إضافة بنود التقييم له. سيتم توجيهك إلى صفحة إضافة البنود مع تحديد الدور مسبقاً.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🔍 Filter Section -->
                    <div class="form-section-modern">
                        <h6><i class="fas fa-filter"></i> فلترة الأدوار</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <form method="GET" action="{{ route('evaluation-criteria.select-role') }}">
                                    <div class="form-floating-modern">
                                        <select name="department" class="form-select-modern" onchange="this.form.submit()">
                                            <option value="">🏢 جميع الأقسام</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                                                    {{ $department }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label>اختر القسم للفلترة</label>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <a href="{{ route('evaluation-criteria.select-role') }}" class="btn btn-modern btn-warning-modern w-100">
                                    <i class="fas fa-undo me-2"></i>
                                    إعادة تعيين الفلتر
                                </a>
                            </div>
                        </div>
                    </div>

                    @if($roles->count() > 0)
                        <!-- 🎯 Roles Grid -->
                        <div class="role-selection-grid">
                            @foreach($roles as $role)
                                <div class="role-card-modern magic-hover fade-in-up" style="animation-delay: {{ $loop->index * 0.1 }}s">
                                    <!-- 🎨 Role Icon -->
                                    <div class="role-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>

                                    <!-- 📋 Role Info -->
                                    <div class="text-center mb-3">
                                        <h5 class="fw-bold mb-2">{{ $role->display_name ?? $role->name }}</h5>

                                        @if(isset($role->department))
                                            <p class="text-muted small mb-2">
                                                <i class="fas fa-building me-1"></i>{{ $role->department }}
                                            </p>
                                        @endif

                                        @if(isset($role->departments) && $role->departments->count() > 1)
                                            <p class="text-muted small mb-2">
                                                <i class="fas fa-layer-group me-1"></i>
                                                <span class="badge badge-modern badge-primary-modern">{{ $role->departments->count() }} أقسام</span>
                                            </p>
                                        @endif

                                        @if($role->description)
                                            <p class="text-muted small mb-3">{{ Str::limit($role->description, 60) }}</p>
                                        @endif
                                    </div>

                                    <!-- 📊 Stats -->
                                    @if(isset($roleCriteriaCounts[$role->id]))
                                        <div class="stats-card mb-3">
                                            <div class="stats-number">{{ $roleCriteriaCounts[$role->id] }}</div>
                                            <div class="text-muted small">
                                                <i class="fas fa-list me-1"></i>بند موجود
                                            </div>
                                        </div>
                                    @endif

                                    <!-- 🚀 Action Buttons -->
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('evaluation-criteria.create', ['role_id' => $role->id]) }}"
                                           class="btn btn-modern btn-primary-modern">
                                            <i class="fas fa-plus me-2"></i>
                                            إضافة بنود للدور
                                        </a>

                                        @if(isset($roleCriteriaCounts[$role->id]) && $roleCriteriaCounts[$role->id] > 0)
                                            <a href="{{ route('evaluation-criteria.index', ['role_id' => $role->id]) }}"
                                               class="btn btn-modern btn-success-modern">
                                                <i class="fas fa-eye me-2"></i>
                                                عرض البنود الموجودة
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- 📄 Pagination -->
                        @if(method_exists($roles, 'links'))
                            <div class="d-flex justify-content-center mt-5">
                                {{ $roles->appends(request()->query())->links() }}
                            </div>
                        @endif
                    @else
                        <!-- 😢 Empty State -->
                        <div class="text-center py-5">
                            <div class="d-inline-block p-4 rounded-circle mb-4" style="background: linear-gradient(135deg, #ffeaa7, #fab1a0);">
                                <i class="fas fa-search fa-3x text-white"></i>
                            </div>
                            <h4 class="fw-bold mb-3">🤷‍♂️ لا توجد أدوار متاحة</h4>
                            <p class="text-muted mb-4">
                                لم يتم العثور على أدوار
                                @if(request('department'))
                                    في قسم "<strong>{{ request('department') }}</strong>"
                                @endif
                            </p>
                            @if(request('department'))
                                <a href="{{ route('evaluation-criteria.select-role') }}" class="btn btn-modern btn-primary-modern">
                                    <i class="fas fa-undo me-2"></i>
                                    عرض جميع الأدوار
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 🎯 إضافة تأثير النقر على بطاقات الأدوار
            const roleCards = document.querySelectorAll('.role-card-modern');

            roleCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // تجنب النقر إذا كان على رابط
                    if (e.target.tagName !== 'A' && !e.target.closest('a')) {
                        const primaryButton = this.querySelector('.btn-primary-modern');
                        if (primaryButton) {
                            // إضافة تأثير بصري قبل التنقل
                            this.style.transform = 'scale(0.95)';
                            setTimeout(() => {
                                primaryButton.click();
                            }, 150);
                        }
                    }
                });

                // تأثير hover للماوس
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // 🎨 تأثير تدرجي لظهور العناصر
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in-up');
                    }
                });
            }, observerOptions);

            // مراقبة جميع بطاقات الأدوار
            roleCards.forEach(card => {
                observer.observe(card);
            });
        });
    </script>
@endpush
@endsection
