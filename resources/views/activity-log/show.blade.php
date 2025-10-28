@extends('layouts.app')

<!-- تضمين ملف CSS الخاص بـ Activity Log -->
<link rel="stylesheet" href="{{ asset('css/activity-log.css') }}">

@section('content')
<div class="activity-dashboard">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-eye"></i>
                        تفاصيل النشاط
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('activity-log.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            العودة
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>معلومات النشاط</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">التاريخ</th>
                                    <td>
                                        {{ $activity->created_at->format('Y-m-d H:i:s') }}
                                        <br>
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>الوصف</th>
                                    <td>
                                        <strong>{{ $activity->description }}</strong>
                                        @if($activity->event)
                                            <br>
                                            <span class="badge badge-info">{{ $activity->event }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>نوع النموذج</th>
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ class_basename($activity->subject_type) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>معرف النموذج</th>
                                    <td>{{ $activity->subject_id }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5>المستخدم المسؤول</h5>
                            @if($activity->causer)
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <img src="{{ $activity->causer->profile_photo_url }}"
                                         alt="{{ $activity->causer->name }}"
                                         class="rounded-circle mr-3"
                                         width="60" height="60">
                                    <div>
                                        <h6 class="mb-1">{{ $activity->causer->name }}</h6>
                                        <p class="text-muted mb-1">{{ $activity->causer->email }}</p>
                                        @if($activity->causer->employee_id)
                                            <small class="text-muted">رقم الموظف: {{ $activity->causer->employee_id }}</small>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    لم يتم تحديد المستخدم المسؤول
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($activity->properties && count($activity->properties) > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>تفاصيل التغييرات</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($activity->batch_uuid)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>معلومات الدفعة</h5>
                                <p class="text-muted">
                                    <strong>معرف الدفعة:</strong> {{ $activity->batch_uuid }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted">
                                تم إنشاء هذا السجل في {{ $activity->created_at->format('Y-m-d H:i:s') }}
                            </small>
                        </div>
                        <div>
                            @can('delete', $activity)
                                <form method="POST" action="{{ route('activity-log.destroy', $activity) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا النشاط؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                        حذف النشاط
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('styles')
<style>
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    pre {
        font-size: 0.9em;
        line-height: 1.4;
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
</style>
@endpush
