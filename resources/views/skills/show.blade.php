@extends('layouts.app')

@section('title', 'تفاصيل المهارة')

@push('styles')
<link href="{{ asset('css/skills.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="skills-container">
    <div class="container">
        <div class="row justify-content-center fade-in">
            <div class="col-md-8">
                <div class="card skill-details">
                    <div class="card-header skills-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-star ml-2"></i>
                            تفاصيل المهارة: <span class="font-weight-normal">{{ $skill->name }}</span>
                        </h5>
                        <div class="d-flex flex-wrap">
                            <a href="{{ route('skills.edit', $skill) }}" class="btn btn-light btn-sm ml-2 mb-1">
                                <i class="fas fa-edit ml-1"></i>
                                تعديل
                            </a>
                            <a href="{{ route('skills.index') }}" class="btn btn-light btn-sm mb-1">
                                <i class="fas fa-arrow-right ml-1"></i>
                                العودة للقائمة
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

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-star text-warning ml-1"></i>
                                اسم المهارة:
                            </div>
                            <div class="col-md-8">
                                <h6 class="text-primary mb-0">{{ $skill->name }}</h6>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-tags text-info ml-1"></i>
                                التصنيف:
                            </div>
                            <div class="col-md-8">
                                <span class="category-badge">
                                    <i class="fas fa-tag ml-1"></i>
                                    {{ optional($skill->category)->name ?? 'بدون تصنيف' }}
                                </span>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-trophy text-warning ml-1"></i>
                                الحد الأقصى للنقاط:
                            </div>
                            <div class="col-md-8">
                                <span class="points-badge">
                                    <i class="fas fa-star ml-1"></i>
                                    {{ $skill->max_points }} نقطة
                                </span>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-toggle-on text-success ml-1"></i>
                                الحالة:
                            </div>
                            <div class="col-md-8">
                                <span class="status-badge {{ $skill->is_active ? 'active' : 'inactive' }}">
                                    <i class="fas fa-{{ $skill->is_active ? 'check-circle' : 'times-circle' }} ml-1"></i>
                                    {{ $skill->is_active ? 'نشط' : 'غير نشط' }}
                                </span>
                                @if(!$skill->is_active)
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle ml-1"></i>
                                        المهارات غير النشطة لا يمكن استخدامها في التقييمات الجديدة
                                    </small>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-align-left text-secondary ml-1"></i>
                                الوصف:
                            </div>
                            <div class="col-md-8">
                                @if($skill->description)
                                    <div class="alert alert-light border-left border-primary">
                                        {{ $skill->description }}
                                    </div>
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-minus-circle ml-1"></i>
                                        لا يوجد وصف متاح لهذه المهارة
                                    </span>
                                @endif
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-calendar-plus text-info ml-1"></i>
                                تاريخ الإنشاء:
                            </div>
                            <div class="col-md-8">
                                <span class="badge badge-light">
                                    <i class="fas fa-clock ml-1"></i>
                                    {{ $skill->created_at->format('d/m/Y') }} في {{ $skill->created_at->format('H:i') }}
                                </span>
                                <small class="text-muted d-block mt-1">
                                    منذ {{ $skill->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-calendar-check text-success ml-1"></i>
                                آخر تحديث:
                            </div>
                            <div class="col-md-8">
                                <span class="badge badge-light">
                                    <i class="fas fa-clock ml-1"></i>
                                    {{ $skill->updated_at->format('d/m/Y') }} في {{ $skill->updated_at->format('H:i') }}
                                </span>
                                <small class="text-muted d-block mt-1">
                                    منذ {{ $skill->updated_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <form action="{{ route('skills.destroy', $skill) }}" method="POST" class="d-inline" id="deleteForm"
                                      data-skill-name="{{ $skill->name }}" data-skill-active="{{ $skill->is_active ? 'true' : 'false' }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" id="deleteBtn">
                                        <i class="fas fa-trash-alt ml-1"></i>
                                        حذف المهارة
                                    </button>
                                </form>
                            </div>
                            <div>
                                <a href="{{ route('skills.edit', $skill) }}" class="btn btn-primary ml-2">
                                    <i class="fas fa-edit ml-1"></i>
                                    تعديل المهارة
                                </a>
                                <a href="{{ route('skills.create') }}" class="btn btn-success">
                                    <i class="fas fa-plus-circle ml-1"></i>
                                    إضافة مهارة جديدة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Information Card -->
                <div class="card mt-4 slide-up" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <h6 class="mb-0 text-white">
                            <i class="fas fa-info-circle ml-1"></i>
                            معلومات إضافية
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-lightbulb ml-1"></i>
                                        نصائح الاستخدام
                                    </h6>
                                    <ul class="mb-0">
                                        <li>يمكن استخدام هذه المهارة في تقييم الموظفين</li>
                                        <li>النقاط المحددة تؤثر على إجمالي التقييم</li>
                                        <li>تأكد من تحديث الوصف عند الحاجة</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-exclamation-triangle ml-1"></i>
                                        تنبيهات
                                    </h6>
                                    <ul class="mb-0">
                                        <li>حذف المهارة سيؤثر على التقييمات السابقة</li>
                                        <li>تعطيل المهارة يمنع استخدامها في التقييمات الجديدة</li>
                                        <li>تأكد من صحة البيانات قبل الحفظ</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card mt-3 slide-up" style="animation-delay: 0.4s;">
                    <div class="card-header">
                        <h6 class="mb-0 text-white">
                            <i class="fas fa-bolt ml-1"></i>
                            إجراءات سريعة
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('skills.edit', $skill) }}" class="btn btn-outline-primary btn-block">
                                    <i class="fas fa-edit ml-1"></i>
                                    تعديل البيانات
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('skills.create') }}" class="btn btn-outline-success btn-block">
                                    <i class="fas fa-copy ml-1"></i>
                                    نسخ كمهارة جديدة
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('skill-categories.show', optional($skill->category)->id ?? '#') }}"
                                   class="btn btn-outline-info btn-block {{ !$skill->category ? 'disabled' : '' }}">
                                    <i class="fas fa-eye ml-1"></i>
                                    عرض التصنيف
                                </a>
                            </div>
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
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('deleteForm');
    const deleteBtn = document.getElementById('deleteBtn');

    // Enhanced delete confirmation
    deleteForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const skillName = deleteForm.dataset.skillName;
        const isActive = deleteForm.dataset.skillActive === 'true';

        let confirmMessage = 'هل أنت متأكد من رغبتك في حذف مهارة "' + skillName + '"?\n\n';
        confirmMessage += 'تحذير: هذا الإجراء لا يمكن التراجع عنه!\n\n';
        confirmMessage += 'سيتم حذف:\n';
        confirmMessage += '• بيانات المهارة\n';
        confirmMessage += '• جميع التقييمات المرتبطة بها\n';
        confirmMessage += '• النقاط المحسوبة للموظفين\n\n';

        if (isActive) {
            confirmMessage += 'ملاحظة: هذه المهارة نشطة حالياً وقد تؤثر على التقييمات الجارية.\n\n';
        }

        confirmMessage += 'اكتب "حذف" للتأكيد:';

        const userInput = prompt(confirmMessage);

        if (userInput === 'حذف') {
            // Add loading state
            deleteBtn.classList.add('loading');
            deleteBtn.disabled = true;

            // Submit the form
            this.submit();
        } else if (userInput !== null) {
            alert('لم يتم حذف المهارة. يجب كتابة "حذف" للتأكيد.');
        }
    });


});
</script>
@endpush
