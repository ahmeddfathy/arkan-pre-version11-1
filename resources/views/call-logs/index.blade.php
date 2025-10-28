@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/call-logs.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="call-logs-container">
    <!-- Page Header -->
    <div class="arkan-phone-header">
        <div class="header-content">
            <div class="header-text">
                <h1><i class="fas fa-phone phone-icon-animated"></i> سجل المكالمات</h1>
                <p>إدارة ومتابعة جميع اتصالات العملاء والتواصل معهم</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('call-logs.create') }}" class="btn-add-client">
                <i class="fas fa-plus-circle"></i> إضافة مكالمة جديدة
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="arkan-call-filters">
        <form method="GET" action="{{ route('call-logs.index') }}" class="row">
            <div class="col-md-2">
                <select name="client_id" class="form-control">
                    <option value="">جميع العملاء</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="contact_type" class="form-control">
                    <option value="">جميع الأنواع</option>
                    <option value="call" {{ request('contact_type') == 'call' ? 'selected' : '' }}>مكالمة هاتفية</option>
                    <option value="email" {{ request('contact_type') == 'email' ? 'selected' : '' }}>بريد إلكتروني</option>
                    <option value="whatsapp" {{ request('contact_type') == 'whatsapp' ? 'selected' : '' }}>واتساب</option>
                    <option value="meeting" {{ request('contact_type') == 'meeting' ? 'selected' : '' }}>اجتماع</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-control">
                    <option value="">جميع الحالات</option>
                    <option value="successful" {{ request('status') == 'successful' ? 'selected' : '' }}>تمت بنجاح</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>فشلت</option>
                    <option value="needs_followup" {{ request('status') == 'needs_followup' ? 'selected' : '' }}>تحتاج متابعة</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="outcome" class="form-control"
                       value="{{ request('outcome') }}"
                       placeholder="بحث في النتيجة">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="من تاريخ">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="إلى تاريخ">
            </div>
            <div class="col-md-2">
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn-view">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <a href="{{ route('call-logs.index') }}" class="btn-secondary">
                        <i class="fas fa-undo"></i> إعادة تعيين
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Container -->
    <div class="call-log-card">
        @if($callLogs->count() > 0)
            <div class="table-responsive">
                <table class="arkan-call-table">
                    <thead>
                        <tr>
                            <th>التاريخ والوقت</th>
                            <th>العميل</th>
                            <th>نوع التواصل</th>
                            <th>الموظف</th>
                            <th>المدة</th>
                            <th>الحالة</th>
                            <th>النتيجة</th>
                            <th>الملخص</th>
                            <th>العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($callLogs as $callLog)
                            <tr>
                                <td>
                                    <strong>{{ $callLog->call_date->format('Y-m-d') }}</strong><br>
                                    <small style="color: var(--gray-500);">{{ $callLog->call_date->format('H:i') }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('clients.show', $callLog->client) }}" style="text-decoration: none; color: var(--arkan-primary);">
                                        {{ $callLog->client->name }}
                                    </a>
                                    @if($callLog->client->company_name)
                                        <br><small style="color: var(--gray-500);">{{ $callLog->client->company_name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="contact-type-badge contact-{{ $callLog->contact_type }}">
                                        {{ $callLog->contact_type_arabic }}
                                    </span>
                                </td>
                                <td>{{ $callLog->employee->name }}</td>
                                <td>
                                    @if($callLog->duration_minutes)
                                        {{ $callLog->duration_formatted }}
                                    @else
                                        <span style="color: var(--gray-500);">غير محدد</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $callLog->status }}">
                                        <i class="fas fa-{{ $callLog->status == 'successful' ? 'check-circle' : ($callLog->status == 'failed' ? 'times-circle' : 'clock') }}"></i>
                                        {{ $callLog->status_arabic }}
                                    </span>
                                </td>
                                <td>
                                    @if($callLog->outcome)
                                        <div title="{{ $callLog->outcome }}">
                                            {{ Str::limit($callLog->outcome, 30) }}
                                        </div>
                                    @else
                                        <span style="color: var(--gray-500);">غير محدد</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($callLog->call_summary, 40) }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('call-logs.show', $callLog) }}" class="btn-action btn-view" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('call-logs.edit', $callLog) }}" class="btn-action btn-edit" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('call-logs.destroy', $callLog) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action btn-delete" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا السجل؟')">
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

            <div style="padding: 2rem; background: var(--gray-50); text-align: center;">
                {{ $callLogs->appends(request()->query())->links() }}
            </div>
        @else
            <div class="no-clients">
                <i class="fas fa-phone fa-3x" style="opacity: 0.7; margin-bottom: 2rem;"></i>
                <h3>لا توجد مكالمات</h3>
                <p>لم يتم العثور على أي مكالمات تطابق المعايير المحددة</p>
                <a href="{{ route('call-logs.create') }}" class="btn-add-first">
                    <i class="fas fa-plus"></i> إضافة أول مكالمة
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
