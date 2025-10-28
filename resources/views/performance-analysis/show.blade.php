@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/performance-analysis.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="full-width-content">
    <!-- Header -->
    <div class="page-header mb-4">
        <div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('performance-analysis.index') }}" class="text-white text-decoration-underline">تحليل الأداء</a>
                                    </li>
                                    <li class="breadcrumb-item active text-white">{{ $project->name }}</li>
                                </ol>
                            </nav>
                            <h2 class="mb-1 text-white">
                                <i class="fas fa-chart-bar me-2"></i>
                                {{ $project->name }}
                            </h2>
                            <p class="mb-0 text-white" style="opacity: 0.9;">
                                كود المشروع: <strong>{{ $project->code ?? 'غير محدد' }}</strong> |
                                العميل: <strong>{{ $project->client->name ?? 'غير محدد' }}</strong>
                            </p>
                        </div>
                            <div class="d-flex gap-2">
                            <a href="{{ route('projects.show', $project->id) }}" class="btn btn-light btn-action">
                                <i class="fas fa-external-link-alt me-2"></i>
                                عرض المشروع
                            </a>
                        </div>
                    </div>
        </div>
    </div>

    <!-- Project Info -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card-modern">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات المشروع
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>اسم المشروع:</strong></td>
                                    <td>{{ $project->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>كود المشروع:</strong></td>
                                    <td>{{ $project->code ?? 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>العميل:</strong></td>
                                    <td>{{ $project->client->name ?? 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>الحالة:</strong></td>
                                    <td>
                                        @php
                                        $statusColors = [
                                        'جديد' => 'success',
                                        'قيد التنفيذ' => 'warning',
                                        'مكتمل' => 'primary',
                                        'معلق' => 'secondary',
                                        'ملغي' => 'danger'
                                        ];
                                        $color = $statusColors[$project->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ $project->status }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>تاريخ البداية:</strong></td>
                                    <td>{{ $project->start_date ? $project->start_date->format('Y-m-d') : 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>تاريخ التسليم:</strong></td>
                                    <td>{{ $project->team_delivery_date ? $project->team_delivery_date->format('Y-m-d') : 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>المدير:</strong></td>
                                    <td>{{ $project->manager }}</td>
                                </tr>
                                <tr>
                                    <td><strong>عاجل:</strong></td>
                                    <td>
                                        @if($project->is_urgent)
                                        <span class="badge bg-danger">نعم</span>
                                        @else
                                        <span class="badge bg-success">لا</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @if($project->description)
                    <div class="mt-3">
                        <strong>الوصف:</strong>
                        <p class="text-muted mt-1">{{ $project->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-modern">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-chart-pie me-2"></i>
                        إحصائيات سريعة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h3 class="text-primary">{{ count($servicesWithData) }}</h3>
                        <p class="text-muted mb-0">إجمالي الخدمات</p>
                    </div>
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="text-success">{{ collect($servicesWithData)->where('service_status', 'مكتملة')->count() }}</h5>
                            <small class="text-muted">مكتملة</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-warning">{{ collect($servicesWithData)->where('service_status', 'قيد التنفيذ')->count() }}</h5>
                            <small class="text-muted">قيد التنفيذ</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Data -->
    <div class="card-modern">
        <div class="card-body p-3" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
            <h5 class="mb-0 text-white">
                <i class="fas fa-database me-2"></i>
                بيانات الخدمات
            </h5>
        </div>
        <div class="card-body p-0">
                    @if(count($servicesWithData) > 0)
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="servicesTabs" role="tablist">
                        @foreach($servicesWithData as $index => $serviceData)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $index === 0 ? 'active' : '' }}"
                                id="service-{{ $index }}-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#service-{{ $index }}"
                                type="button"
                                role="tab"
                                aria-controls="service-{{ $index }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                <div class="d-flex align-items-center">
                                    <strong>{{ $serviceData['service']->name }}</strong>
                                    <span class="badge bg-{{ $serviceData['service_status'] === 'مكتملة' ? 'success' : ($serviceData['service_status'] === 'قيد التنفيذ' ? 'warning' : 'secondary') }} ms-2">
                                        {{ $serviceData['service_status'] }}
                                    </span>
                                    @if($serviceData['service']->dataFields && $serviceData['service']->dataFields->count() > 0)
                                    <span class="badge bg-info ms-1">
                                        {{ $serviceData['service']->dataFields->count() }} حقول
                                    </span>
                                    @endif
                                </div>
                            </button>
                        </li>
                        @endforeach
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="servicesTabsContent">
                        @foreach($servicesWithData as $index => $serviceData)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                            id="service-{{ $index }}"
                            role="tabpanel"
                            aria-labelledby="service-{{ $index }}-tab">
                            <div class="p-4">
                                <!-- إحصائيات التعديلات والأخطاء -->
                                @if(isset($serviceData['stats']))
                                <div class="row mb-4">
                                    <!-- التعديلات -->
                                    <div class="col-md-6">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <h6 class="card-title text-primary">
                                                    <i class="fas fa-edit me-2"></i>
                                                    التعديلات
                                                </h6>
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <span class="fs-2 fw-bold text-primary">{{ $serviceData['stats']['revisions']['total'] }}</span>
                                                    <i class="fas fa-clipboard-list fa-3x text-primary opacity-25"></i>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge bg-secondary">{{ $serviceData['stats']['revisions']['new'] }} جديد</span>
                                                    <span class="badge bg-primary">{{ $serviceData['stats']['revisions']['in_progress'] }} قيد التنفيذ</span>
                                                    <span class="badge bg-success">{{ $serviceData['stats']['revisions']['completed'] }} مكتمل</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- الأخطاء -->
                                    <div class="col-md-6">
                                        <div class="card border-danger">
                                            <div class="card-body">
                                                <h6 class="card-title text-danger">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    الأخطاء
                                                </h6>
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <span class="fs-2 fw-bold text-danger">{{ $serviceData['stats']['errors']['total'] }}</span>
                                                    <i class="fas fa-bug fa-3x text-danger opacity-25"></i>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge bg-danger">{{ $serviceData['stats']['errors']['critical'] }} جوهري</span>
                                                    <span class="badge bg-warning text-dark">{{ $serviceData['stats']['errors']['normal'] }} عادي</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- حقول البيانات الديناميكية -->
                                @if($serviceData['service']->dataFields && $serviceData['service']->dataFields->count() > 0)
                                <div class="row">
                                    @foreach($serviceData['service']->dataFields as $field)
                                    <div class="col-md-6 mb-3">
                                        <div class="card border">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    {{ $field->field_label }}
                                                    @if($field->is_required)
                                                    <span class="text-danger">*</span>
                                                    @endif
                                                    <span class="badge bg-{{ $field->field_type === 'boolean' ? 'primary' : ($field->field_type === 'date' ? 'info' : ($field->field_type === 'dropdown' ? 'warning' : 'success')) }} ms-2">
                                                        {{ $field->field_type }}
                                                    </span>
                                                </h6>
                                                <div class="mt-2">
                                                    @php
                                                    $value = $serviceData['service_data'][$field->field_name] ?? null;
                                                    @endphp
                                                    @if($field->field_type === 'boolean')
                                                    @if($value === true || $value === 'true' || $value === '1')
                                                    <span class="badge bg-success">نعم</span>
                                                    @elseif($value === false || $value === 'false' || $value === '0')
                                                    <span class="badge bg-danger">لا</span>
                                                    @else
                                                    <span class="text-muted">غير محدد</span>
                                                    @endif
                                                    @elseif($field->field_type === 'date')
                                                    @if($value)
                                                    <span class="badge bg-info">{{ $value }}</span>
                                                    @else
                                                    <span class="text-muted">غير محدد</span>
                                                    @endif
                                                    @elseif($field->field_type === 'dropdown')
                                                    @if($value)
                                                    <span class="badge bg-warning">{{ $value }}</span>
                                                    @else
                                                    <span class="text-muted">غير محدد</span>
                                                    @endif
                                                    @elseif($field->field_type === 'text')
                                                    @if($value)
                                                    <div class="bg-light p-2 rounded">
                                                        {{ $value }}
                                                    </div>
                                                    @else
                                                    <span class="text-muted">غير محدد</span>
                                                    @endif
                                                    @endif
                                                </div>
                                                @if($field->description)
                                                <small class="text-muted">{{ $field->description }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">لا توجد حقول بيانات محددة لهذه الخدمة</p>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>لا توجد خدمات</h5>
                <p>لم يتم ربط أي خدمات بهذا المشروع</p>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
