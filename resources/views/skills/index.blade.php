@extends('layouts.app')

@section('title', 'إدارة المهارات')

@push('styles')
<link href="{{ asset('css/skills.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="skills-container">
    <div class="container">
        <div class="row justify-content-center fade-in">
            <div class="col-md-12">
                <div class="card skills-table">
                    <div class="card-header skills-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-star ml-2"></i>
                            إدارة المهارات
                        </h5>
                        <div class="d-flex flex-wrap">
                            <a href="{{ route('skill-categories.index') }}" class="btn btn-light btn-sm ml-2 mb-1">
                                <i class="fas fa-list-alt ml-1"></i>
                                تصنيفات المهارات
                            </a>
                            <a href="{{ route('skills.create') }}" class="btn btn-light btn-sm mb-1">
                                <i class="fas fa-plus-circle ml-1"></i>
                                إضافة مهارة جديدة
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success slide-up">
                                <i class="fas fa-check-circle ml-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger slide-up">
                                <i class="fas fa-exclamation-triangle ml-2"></i>
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="row">
                            @forelse ($skills as $skill)
                                <div class="col-lg-4 col-md-6 mb-4 slide-up">
                                    <div class="card h-100 skill-card {{ $skill->is_active ? '' : 'inactive' }}">
                                        <div class="card-body">
                                            <span class="category-badge">
                                                <i class="fas fa-tag ml-1"></i>
                                                {{ optional($skill->category)->name ?? 'بدون تصنيف' }}
                                            </span>
                                            <h5 class="card-title mt-3">
                                                <i class="fas fa-star text-warning ml-1"></i>
                                                {{ $skill->name }}
                                            </h5>
                                            <p class="card-text text-muted">
                                                {{ $skill->description ? \Illuminate\Support\Str::limit($skill->description, 120) : 'لا يوجد وصف متاح لهذه المهارة حالياً' }}
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span class="points-badge">
                                                    <i class="fas fa-trophy ml-1"></i>
                                                    {{ $skill->max_points }} نقطة
                                                </span>
                                                <span class="status-badge {{ $skill->is_active ? 'active' : 'inactive' }}">
                                                    <i class="fas fa-{{ $skill->is_active ? 'check-circle' : 'times-circle' }} ml-1"></i>
                                                    {{ $skill->is_active ? 'نشط' : 'غير نشط' }}
                                                </span>
                                            </div>
                                            @if($skill->created_at)
                                            <div class="text-muted small mb-2">
                                                <i class="fas fa-calendar-alt ml-1"></i>
                                                تم الإنشاء: {{ $skill->created_at->format('d/m/Y') }}
                                            </div>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-light">
                                            <div class="d-flex justify-content-between">
                                                <a href="{{ route('skills.show', $skill) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye ml-1"></i>
                                                    عرض
                                                </a>
                                                <a href="{{ route('skills.edit', $skill) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit ml-1"></i>
                                                    تعديل
                                                </a>
                                                <form action="{{ route('skills.destroy', $skill) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('هل أنت متأكد من رغبتك في حذف مهارة {{ $skill->name }}؟\n\nتحذير: سيتم حذف جميع البيانات المرتبطة بهذه المهارة!')">
                                                        <i class="fas fa-trash-alt ml-1"></i>
                                                        حذف
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="empty-state">
                                        <i class="fas fa-star"></i>
                                        <h4 class="mt-3 mb-2">لا توجد مهارات مضافة</h4>
                                        <p class="text-muted mb-4">لم يتم إضافة أي مهارات حتى الآن. ابدأ بإضافة أول مهارة!</p>
                                        <a href="{{ route('skills.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus-circle ml-1"></i>
                                            إضافة مهارة جديدة
                                        </a>
                                    </div>
                                </div>
                            @endforelse
                        </div>

                        @if($skills->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-bar ml-2"></i>
                                            إحصائيات المهارات
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <div class="alert alert-primary mb-0">
                                                    <strong style="font-size: 1.5rem;">{{ $skills->count() }}</strong>
                                                    <br>
                                                    <small>إجمالي المهارات</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="alert alert-success mb-0">
                                                    <strong style="font-size: 1.5rem;">{{ $skills->where('is_active', true)->count() }}</strong>
                                                    <br>
                                                    <small>المهارات النشطة</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="alert alert-warning mb-0">
                                                    <strong style="font-size: 1.5rem;">{{ $skills->where('is_active', false)->count() }}</strong>
                                                    <br>
                                                    <small>المهارات غير النشطة</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="alert alert-info mb-0">
                                                    <strong style="font-size: 1.5rem;">{{ $skills->sum('max_points') }}</strong>
                                                    <br>
                                                    <small>إجمالي النقاط</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading state to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.type === 'submit' || this.href) {
                this.classList.add('loading');
            }
        });
    });


});
</script>
@endpush
