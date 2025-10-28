@extends('layouts.app')

@section('title', 'تفاصيل ربط الدور بالتقييم')

@push('styles')
<link href="{{ asset('css/role-evaluation-mapping.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-eye"></i>
                        تفاصيل ربط الدور بالتقييم
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('role-evaluation-mapping.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                            العودة للقائمة
                        </a>
                        <a href="{{ route('role-evaluation-mapping.edit', $roleEvaluationMapping) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i>
                            تعديل
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">الدور المراد تقييمه:</label>
                                <p class="form-control-plaintext">
                                    <span class="status-badge badge-role-evaluate">
                                        {{ $roleEvaluationMapping->roleToEvaluate->display_name ?? $roleEvaluationMapping->roleToEvaluate->name }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">الدور المقيم:</label>
                                <p class="form-control-plaintext">
                                    <span class="status-badge badge-role-evaluator">
                                        {{ $roleEvaluationMapping->evaluatorRole->display_name ?? $roleEvaluationMapping->evaluatorRole->name }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">اسم القسم:</label>
                                <p class="form-control-plaintext">
                                    <span class="status-badge bg-primary">
                                        <i class="fas fa-building"></i>
                                        {{ $roleEvaluationMapping->department_name }}
                                    </span>
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">إمكانية التقييم:</label>
                                <p class="form-control-plaintext">
                                    @if($roleEvaluationMapping->can_evaluate)
                                        <span class="status-badge badge-permission-yes">
                                            <i class="fas fa-check-circle"></i>
                                            يمكن التقييم
                                        </span>
                                    @else
                                        <span class="status-badge bg-danger">
                                            <i class="fas fa-times-circle"></i>
                                            لا يمكن التقييم
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">إمكانية المشاهدة:</label>
                                <p class="form-control-plaintext">
                                    @if($roleEvaluationMapping->can_view)
                                        <span class="status-badge badge-permission-yes">
                                            <i class="fas fa-eye"></i>
                                            يمكن المشاهدة
                                        </span>
                                    @else
                                        <span class="status-badge bg-danger">
                                            <i class="fas fa-eye-slash"></i>
                                            لا يمكن المشاهدة
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">تاريخ الإنشاء:</label>
                                <p class="form-control-plaintext">
                                    <i class="fas fa-calendar-plus text-muted"></i>
                                    {{ $roleEvaluationMapping->created_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">آخر تحديث:</label>
                                <p class="form-control-plaintext">
                                    <i class="fas fa-calendar-edit text-muted"></i>
                                    {{ $roleEvaluationMapping->updated_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Card -->
                    <div class="preview-section">
                        <h5 class="preview-title">
                            <i class="fas fa-info-circle"></i>
                            ملخص الربط
                        </h5>
                        <p class="preview-content">
                            هذا الربط يسمح لدور
                            <strong class="text-success">{{ $roleEvaluationMapping->evaluatorRole->display_name ?? $roleEvaluationMapping->evaluatorRole->name }}</strong>
                            بتقييم دور
                            <strong class="text-info">{{ $roleEvaluationMapping->roleToEvaluate->display_name ?? $roleEvaluationMapping->roleToEvaluate->name }}</strong>
                            في قسم
                            <strong class="text-primary">{{ $roleEvaluationMapping->department_name }}</strong>.
                        </p>
                        @if($roleEvaluationMapping->can_evaluate)
                            <p class="text-success">
                                <i class="fas fa-check"></i>
                                يمكن للدور المقيم إنشاء تقييمات جديدة.
                            </p>
                        @endif
                        @if($roleEvaluationMapping->can_view)
                            <p class="text-info">
                                <i class="fas fa-eye"></i>
                                يمكن للدور المقيم مشاهدة التقييمات الموجودة.
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Linked Criteria Section -->
                @if(isset($linkedCriteria) && count($linkedCriteria) > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list-check text-success"></i>
                            البنود المربوطة للتقييم
                        </h6>
                    </div>
                    <div class="card-body">
                        @php
                            $criteriaByType = $linkedCriteria->groupBy(function($item) {
                                return $item->criteria->criteria_type;
                            });
                        @endphp

                        @foreach($criteriaByType as $type => $criteria)
                            <div class="criteria-type-section mb-3">
                                <h6 class="text-{{ $type === 'positive' ? 'success' : 'warning' }}">
                                    <i class="fas fa-{{ $type === 'positive' ? 'plus-circle' : 'minus-circle' }}"></i>
                                    {{ $type === 'positive' ? 'البنود الإيجابية' : 'البنود السلبية' }}
                                </h6>
                                <div class="criteria-list">
                                    @foreach($criteria as $criteriaEvaluator)
                                        <div class="criteria-item-display mb-2 p-3 bg-light border rounded">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong>{{ $criteriaEvaluator->criteria->criteria_name }}</strong>
                                                    @if($criteriaEvaluator->criteria->criteria_description)
                                                        <br><small class="text-muted">{{ $criteriaEvaluator->criteria->criteria_description }}</small>
                                                    @endif
                                                    @if($criteriaEvaluator->criteria->category)
                                                        <span class="badge bg-secondary ms-2">{{ $criteriaEvaluator->criteria->category }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-left">
                                                    <span class="badge bg-info">{{ $criteriaEvaluator->criteria->max_points }} نقطة</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        <h6 class="mt-2">لا توجد بنود مربوطة</h6>
                        <p class="text-muted">لم يتم ربط أي بنود تقييم بهذا الدور</p>
                        <a href="{{ route('role-evaluation-mapping.edit', $roleEvaluationMapping) }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> ربط البنود
                        </a>
                    </div>
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="action-buttons mt-4">
                    <a href="{{ route('role-evaluation-mapping.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> العودة للقائمة
                    </a>
                    <a href="{{ route('role-evaluation-mapping.edit', $roleEvaluationMapping) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                    <form method="POST" action="{{ route('role-evaluation-mapping.destroy', $roleEvaluationMapping) }}" style="display: inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الربط؟')">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
