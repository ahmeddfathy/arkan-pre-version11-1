@extends('layouts.app')

@section('title', 'تفاصيل تصنيف المهارات')

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
                            <i class="fas fa-list-alt ml-2"></i>
                            تفاصيل التصنيف: <span class="font-weight-normal">{{ $skillCategory->name }}</span>
                        </h5>
                        <div class="d-flex flex-wrap">
                            <a href="{{ route('skill-categories.edit', $skillCategory) }}" class="btn btn-light btn-sm ml-2 mb-1">
                                <i class="fas fa-edit ml-1"></i>
                                تعديل
                            </a>
                            <a href="{{ route('skill-categories.index') }}" class="btn btn-light btn-sm mb-1">
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
                                <i class="fas fa-tag text-info ml-1"></i>
                                اسم التصنيف:
                            </div>
                            <div class="col-md-8">
                                <h6 class="text-primary mb-0">{{ $skillCategory->name }}</h6>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-align-left text-secondary ml-1"></i>
                                الوصف:
                            </div>
                            <div class="col-md-8">
                                @if($skillCategory->description)
                                    <div class="alert alert-light border-left border-primary">
                                        {{ $skillCategory->description }}
                                    </div>
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-minus-circle ml-1"></i>
                                        لا يوجد وصف متاح لهذا التصنيف
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-star text-warning ml-1"></i>
                                عدد المهارات:
                            </div>
                            <div class="col-md-8">
                                <span class="points-badge">
                                    <i class="fas fa-trophy ml-1"></i>
                                    {{ $skillCategory->skills->count() }} مهارة
                                </span>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 font-weight-bold text-md-right">
                                <i class="fas fa-toggle-on text-success ml-1"></i>
                                حالة التصنيف:
                            </div>
                            <div class="col-md-8">
                                <span class="status-badge {{ $skillCategory->skills->count() > 0 ? 'active' : 'inactive' }}">
                                    <i class="fas fa-{{ $skillCategory->skills->count() > 0 ? 'check-circle' : 'times-circle' }} ml-1"></i>
                                    {{ $skillCategory->skills->count() > 0 ? 'مستخدم' : 'غير مستخدم' }}
                                </span>
                                @if($skillCategory->skills->count() == 0)
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle ml-1"></i>
                                        لم يتم إضافة أي مهارات لهذا التصنيف بعد
                                    </small>
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
                                    {{ $skillCategory->created_at->format('d/m/Y') }} في {{ $skillCategory->created_at->format('H:i') }}
                                </span>
                                <small class="text-muted d-block mt-1">
                                    منذ {{ $skillCategory->created_at->diffForHumans() }}
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
                                    {{ $skillCategory->updated_at->format('d/m/Y') }} في {{ $skillCategory->updated_at->format('H:i') }}
                                </span>
                                <small class="text-muted d-block mt-1">
                                    منذ {{ $skillCategory->updated_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <form action="{{ route('skill-categories.destroy', $skillCategory) }}" method="POST" class="d-inline" id="deleteForm"
                                      data-category-name="{{ $skillCategory->name }}" data-skills-count="{{ $skillCategory->skills->count() }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" id="deleteBtn">
                                        <i class="fas fa-trash-alt ml-1"></i>
                                        حذف التصنيف
                                    </button>
                                </form>
                            </div>
                            <div>
                                <a href="{{ route('skill-categories.edit', $skillCategory) }}" class="btn btn-primary ml-2">
                                    <i class="fas fa-edit ml-1"></i>
                                    تعديل التصنيف
                                </a>
                                <a href="{{ route('skills.create') }}?category_id={{ $skillCategory->id }}" class="btn btn-success">
                                    <i class="fas fa-plus-circle ml-1"></i>
                                    إضافة مهارة جديدة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                @if($skillCategory->skills->count() > 0)
                <!-- Skills Table Card -->
                <div class="card mt-4 slide-up" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <h6 class="mb-0 text-white">
                            <i class="fas fa-star ml-1"></i>
                            المهارات في هذا التصنيف ({{ $skillCategory->skills->count() }})
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <i class="fas fa-star ml-1"></i>
                                            المهارة
                                        </th>
                                        <th>
                                            <i class="fas fa-trophy ml-1"></i>
                                            النقاط القصوى
                                        </th>
                                        <th>
                                            <i class="fas fa-toggle-on ml-1"></i>
                                            الحالة
                                        </th>
                                        <th>
                                            <i class="fas fa-cogs ml-1"></i>
                                            الإجراءات
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($skillCategory->skills as $skill)
                                        <tr>
                                            <td>
                                                <strong class="text-primary">{{ $skill->name }}</strong>
                                                @if($skill->description)
                                                    <br>
                                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($skill->description, 60) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="points-badge">
                                                    <i class="fas fa-star ml-1"></i>
                                                    {{ $skill->max_points }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge {{ $skill->is_active ? 'active' : 'inactive' }}">
                                                    <i class="fas fa-{{ $skill->is_active ? 'check-circle' : 'times-circle' }} ml-1"></i>
                                                    {{ $skill->is_active ? 'نشط' : 'غير نشط' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('skills.show', $skill) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('skills.edit', $skill) }}" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

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
                                <a href="{{ route('skill-categories.edit', $skillCategory) }}" class="btn btn-outline-primary btn-block">
                                    <i class="fas fa-edit ml-1"></i>
                                    تعديل التصنيف
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('skills.create') }}?category_id={{ $skillCategory->id }}" class="btn btn-outline-success btn-block">
                                    <i class="fas fa-plus-circle ml-1"></i>
                                    إضافة مهارة جديدة
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('skills.index') }}" class="btn btn-outline-info btn-block">
                                    <i class="fas fa-list ml-1"></i>
                                    عرض جميع المهارات
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

        const categoryName = deleteForm.dataset.categoryName;
        const skillsCount = parseInt(deleteForm.dataset.skillsCount);

        if (skillsCount > 0) {
            alert('لا يمكن حذف تصنيف "' + categoryName + '" لأنه يحتوي على ' + skillsCount + ' مهارة.\n\nيجب حذف أو نقل جميع المهارات أولاً.');
            return false;
        }

        let confirmMessage = 'هل أنت متأكد من رغبتك في حذف تصنيف "' + categoryName + '"?\n\n';
        confirmMessage += 'تحذير: هذا الإجراء لا يمكن التراجع عنه!\n\n';
        confirmMessage += 'سيتم حذف:\n';
        confirmMessage += '• بيانات التصنيف\n';
        confirmMessage += '• جميع المراجع المرتبطة به\n\n';
        confirmMessage += 'اكتب "حذف" للتأكيد:';

        const userInput = prompt(confirmMessage);

        if (userInput === 'حذف') {
            // Add loading state
            deleteBtn.classList.add('loading');
            deleteBtn.disabled = true;

            // Submit the form
            this.submit();
        } else if (userInput !== null) {
            alert('لم يتم حذف التصنيف. يجب كتابة "حذف" للتأكيد.');
        }
    });


});
</script>
@endpush
