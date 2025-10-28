@extends('layouts.app')

@section('title', 'تصنيفات المهارات')

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
                            <i class="fas fa-list-alt ml-2"></i>
                            تصنيفات المهارات
                        </h5>
                        <div class="d-flex flex-wrap">
                            <a href="{{ route('skills.index') }}" class="btn btn-light btn-sm ml-2 mb-1">
                                <i class="fas fa-star ml-1"></i>
                                المهارات
                            </a>
                            <a href="{{ route('skill-categories.create') }}" class="btn btn-light btn-sm mb-1">
                                <i class="fas fa-plus-circle ml-1"></i>
                                إضافة تصنيف جديد
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

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">
                                            <i class="fas fa-hashtag ml-1"></i>
                                            #
                                        </th>
                                        <th width="20%">
                                            <i class="fas fa-tag ml-1"></i>
                                            الاسم
                                        </th>
                                        <th width="45%">
                                            <i class="fas fa-align-left ml-1"></i>
                                            الوصف
                                        </th>
                                        <th width="10%">
                                            <i class="fas fa-star ml-1"></i>
                                            عدد المهارات
                                        </th>
                                        <th width="20%">
                                            <i class="fas fa-cogs ml-1"></i>
                                            الإجراءات
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($categories as $index => $category)
                                        <tr class="slide-up" style="animation-delay: {{ $index * 100 }}ms;">
                                            <td>
                                                <span class="badge badge-light">{{ $index + 1 }}</span>
                                            </td>
                                            <td>
                                                <strong class="text-primary">{{ $category->name }}</strong>
                                            </td>
                                            <td>
                                                <span class="text-muted">
                                                    {{ $category->description ? \Illuminate\Support\Str::limit($category->description, 100) : 'بدون وصف متاح' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="points-badge">
                                                    <i class="fas fa-star ml-1"></i>
                                                    {{ $category->skills_count }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('skill-categories.show', $category) }}"
                                                       class="btn btn-sm btn-info"
                                                       title="عرض التفاصيل">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('skill-categories.edit', $category) }}"
                                                       class="btn btn-sm btn-primary"
                                                       title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('skill-categories.destroy', $category) }}"
                                                          method="POST"
                                                          class="d-inline"
                                                           data-category-name="{{ $category->name }}" data-skills-count="{{ $category->skills_count }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-danger"
                                                                title="حذف">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="fas fa-list-alt"></i>
                                                    <h4 class="mt-3 mb-2">لا توجد تصنيفات مهارات</h4>
                                                    <p class="text-muted mb-4">لم يتم إضافة أي تصنيفات للمهارات حتى الآن. ابدأ بإضافة أول تصنيف!</p>
                                                    <a href="{{ route('skill-categories.create') }}" class="btn btn-primary">
                                                        <i class="fas fa-plus-circle ml-1"></i>
                                                        إضافة تصنيف جديد
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($categories->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-bar ml-2"></i>
                                            إحصائيات التصنيفات
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <div class="alert alert-primary mb-0">
                                                    <strong style="font-size: 1.5rem;">{{ $categories->count() }}</strong>
                                                    <br>
                                                    <small>إجمالي التصنيفات</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="alert alert-success mb-0">
                                                    <strong style="font-size: 1.5rem;">{{ $categories->sum('skills_count') }}</strong>
                                                    <br>
                                                    <small>إجمالي المهارات</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="alert alert-info mb-0">
                                                    <strong style="font-size: 1.5rem;">{{ $categories->where('skills_count', '>', 0)->count() }}</strong>
                                                    <br>
                                                    <small>التصنيفات المستخدمة</small>
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

                <!-- Help Card -->
                <div class="card mt-4 slide-up" style="animation-delay: 0.3s;">
                    <div class="card-header">
                        <h6 class="mb-0 text-white">
                            <i class="fas fa-question-circle ml-1"></i>
                            نصائح لإدارة تصنيفات المهارات
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        قم بتنظيم المهارات في تصنيفات منطقية
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        استخدم أسماء واضحة ومفهومة للتصنيفات
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        اكتب وصفاً مفيداً لكل تصنيف
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-exclamation-triangle text-warning ml-1"></i>
                                        لا يمكن حذف التصنيفات التي تحتوي على مهارات
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-info-circle text-info ml-1"></i>
                                        يمكنك تعديل التصنيفات في أي وقت
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-star text-warning ml-1"></i>
                                        التصنيفات تساعد في تنظيم عملية التقييم
                                    </li>
                                </ul>
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


    // Handle delete forms
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.hasAttribute('data-category-name')) {
            e.preventDefault();

            const categoryName = form.dataset.categoryName;
            const skillsCount = parseInt(form.dataset.skillsCount);

            if (skillsCount > 0) {
                alert('لا يمكن حذف تصنيف "' + categoryName + '" لأنه يحتوي على ' + skillsCount + ' مهارة.\n\nيجب حذف أو نقل جميع المهارات أولاً.');
                return false;
            }

            let confirmMessage = 'هل أنت متأكد من رغبتك في حذف تصنيف "' + categoryName + '"?\n\n';
            confirmMessage += 'تحذير: هذا الإجراء لا يمكن التراجع عنه!\n\n';
            confirmMessage += 'اكتب "حذف" للتأكيد:';

            const userInput = prompt(confirmMessage);

            if (userInput === 'حذف') {
                form.submit();
            } else if (userInput !== null) {
                alert('لم يتم حذف التصنيف. يجب كتابة "حذف" للتأكيد.');
            }
        }
    });
});
</script>
@endpush
