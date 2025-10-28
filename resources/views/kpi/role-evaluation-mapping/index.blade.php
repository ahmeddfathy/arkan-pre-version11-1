@extends('layouts.app')

@section('title', 'ربط الأدوار بالتقييم')

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
                        <i class="fas fa-link"></i>
                        ربط الأدوار بالتقييم
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('role-evaluation-mapping.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i>
                            إضافة ربط جديد
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Department Filter -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <form method="GET" action="{{ route('role-evaluation-mapping.index') }}">
                                <div class="input-group">
                                    <select name="department" class="form-select" onchange="this.form.submit()">
                                        <option value="">جميع الأقسام</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department }}" {{ $selectedDepartment == $department ? 'selected' : '' }}>
                                                {{ $department }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('role-evaluation-mapping.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-refresh"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </div>

                    @if($evaluationMatrix->count() > 0)
                        @foreach($evaluationMatrix as $departmentName => $mappings)
                            <div class="department-card">
                                <div class="department-header">
                                    <h5 class="department-title">
                                        <i class="fas fa-building"></i>
                                        {{ $departmentName }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-container">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>الدور المراد تقييمه</th>
                                                    <th>الدور المقيم</th>
                                                    <th>البنود المربوطة</th>
                                                    <th>يمكن التقييم</th>
                                                    <th>يمكن المشاهدة</th>
                                                    <th>الإجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($mappings as $mapping)
                                                    <tr>
                                                        <td>
                                                            <span class="status-badge badge-role-evaluate">
                                                                {{ $mapping->roleToEvaluate->display_name ?? $mapping->roleToEvaluate->name }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="status-badge badge-role-evaluator">
                                                                {{ $mapping->evaluatorRole->display_name ?? $mapping->evaluatorRole->name }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $linkedCount = App\Models\CriteriaEvaluatorRole::where('evaluator_role_id', $mapping->evaluator_role_id)
                                                                    ->where('department_name', $mapping->department_name)
                                                                    ->whereHas('criteria', function($q) use ($mapping) {
                                                                        $q->where('role_id', $mapping->role_to_evaluate_id);
                                                                    })
                                                                    ->count();
                                                            @endphp
                                                            <span class="status-badge {{ $linkedCount > 0 ? 'badge-criteria-linked' : 'badge-criteria-none' }}">
                                                                {{ $linkedCount }} بند
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if($mapping->can_evaluate)
                                                                <span class="status-badge badge-permission-yes">نعم</span>
                                                            @else
                                                                <span class="status-badge badge-permission-no">لا</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($mapping->can_view)
                                                                <span class="status-badge badge-permission-yes">نعم</span>
                                                            @else
                                                                <span class="status-badge badge-permission-no">لا</span>
                                                            @endif
                                                        </td>
                                                                                                                <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="{{ route('role-evaluation-mapping.show', $mapping) }}"
                                                                    class="btn btn-info btn-sm" title="عرض">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="{{ route('role-evaluation-mapping.edit', $mapping) }}"
                                                                    class="btn btn-warning btn-sm" title="تعديل">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form method="POST" action="{{ route('role-evaluation-mapping.destroy', $mapping) }}"
                                                                      style="display: inline;"
                                                                       onsubmit="return confirm('هل أنت متأكد من حذف هذا الربط؟')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger btn-sm" title="حذف">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h4 class="empty-title">لا توجد ربطات أدوار</h4>
                            <p class="empty-description">لم يتم إضافة أي ربطات أدوار بعد.</p>
                            <a href="{{ route('role-evaluation-mapping.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                إضافة أول ربط
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
