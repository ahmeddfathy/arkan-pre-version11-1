@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/call-logs.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="call-logs-container">
    <div class="container">
        <!-- Page Header -->
        <div class="arkan-phone-header">
            <div class="header-content">
                <div class="header-text">
                    <h1><i class="fas fa-phone-alt phone-icon-animated"></i> تفاصيل المكالمة</h1>
                    <p>عرض تفاصيل شاملة عن المكالمة مع العميل</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('call-logs.edit', $callLog) }}" class="arkan-call-btn arkan-call-btn-warning">
                    <i class="fas fa-edit"></i> تعديل
                </a>
                <a href="{{ route('call-logs.index') }}" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> العودة للقائمة
                </a>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="row">
            <!-- Call Information Card -->
            <div class="col-md-6">
                <div class="arkan-info-card">
                    <div style="padding: 2rem;">
                        <h3 style="color: var(--call-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-info-circle"></i> معلومات المكالمة
                        </h3>

                        <div class="info-box primary">
                            <div class="info-label">
                                <i class="fas fa-user"></i> العميل
                            </div>
                            <div class="info-value">
                                <a href="{{ route('clients.show', $callLog->client) }}" style="color: var(--call-primary); text-decoration: none; font-weight: 600;">
                                    {{ $callLog->client->name }}
                                </a>
                                @if($callLog->client->company_name)
                                    <br><small style="color: var(--gray-500);">{{ $callLog->client->company_name }}</small>
                                @endif
                            </div>
                        </div>

                        <div class="info-box info">
                            <div class="info-label">
                                <i class="fas fa-user-tie"></i> الموظف المسؤول
                            </div>
                            <div class="info-value">{{ $callLog->employee->name }}</div>
                        </div>

                        @if($callLog->creator && $callLog->creator->id !== $callLog->employee->id)
                            <div class="info-box">
                                <div class="info-label">
                                    <i class="fas fa-user-plus"></i> منشئ السجل
                                </div>
                                <div class="info-value">{{ $callLog->creator->name }}</div>
                            </div>
                        @endif

                        <div class="info-box">
                            <div class="info-label">
                                <i class="fas fa-calendar-alt"></i> تاريخ ووقت المكالمة
                            </div>
                            <div class="info-value">
                                <strong>{{ $callLog->call_date->format('Y-m-d') }}</strong>
                                <span style="margin: 0 0.5rem;">-</span>
                                <strong>{{ $callLog->call_date->format('H:i') }}</strong>
                            </div>
                        </div>

                        <div class="info-box warning">
                            <div class="info-label">
                                <i class="fas fa-phone"></i> نوع التواصل
                            </div>
                            <div class="info-value">
                                <span class="contact-type-badge contact-{{ $callLog->contact_type }}">
                                    <i class="fas fa-{{ $callLog->contact_type == 'call' ? 'phone' : ($callLog->contact_type == 'email' ? 'envelope' : ($callLog->contact_type == 'whatsapp' ? 'whatsapp' : 'handshake')) }}"></i>
                                    {{ $callLog->contact_type_arabic }}
                                </span>
                            </div>
                        </div>

                        @if($callLog->duration_minutes)
                            <div class="info-box">
                                <div class="info-label">
                                    <i class="fas fa-stopwatch"></i> مدة المكالمة
                                </div>
                                <div class="info-value">{{ $callLog->duration_formatted }}</div>
                            </div>
                        @endif

                        <div class="info-box {{ $callLog->status == 'successful' ? 'success' : ($callLog->status == 'failed' ? 'warning' : 'primary') }}">
                            <div class="info-label">
                                <i class="fas fa-check-circle"></i> حالة المكالمة
                            </div>
                            <div class="info-value">
                                <span class="status-badge status-{{ $callLog->status }}">
                                    <i class="fas fa-{{ $callLog->status == 'successful' ? 'check-circle' : ($callLog->status == 'failed' ? 'times-circle' : 'clock') }}"></i>
                                    {{ $callLog->status_arabic }}
                                </span>
                            </div>
                        </div>

                        @if($callLog->outcome)
                            <div class="info-box">
                                <div class="info-label">
                                    <i class="fas fa-flag-checkered"></i> نتيجة المكالمة
                                </div>
                                <div class="info-value">
                                    <div class="outcome-text">
                                        {{ $callLog->outcome }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="info-box">
                            <div class="info-label">
                                <i class="fas fa-plus-circle"></i> تاريخ الإنشاء
                            </div>
                            <div class="info-value">
                                {{ $callLog->created_at->format('Y-m-d H:i') }}
                                <br><small style="color: var(--gray-500);">{{ $callLog->created_at->diffForHumans() }}</small>
                            </div>
                        </div>

                        @if($callLog->updated_at->ne($callLog->created_at))
                            <div class="info-box">
                                <div class="info-label">
                                    <i class="fas fa-edit"></i> آخر تحديث
                                </div>
                                <div class="info-value">
                                    {{ $callLog->updated_at->format('Y-m-d H:i') }}
                                    <br><small style="color: var(--gray-500);">{{ $callLog->updated_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Call Details Card -->
            <div class="col-md-6">
                <div class="arkan-info-card">
                    <div style="padding: 2rem;">
                        <h3 style="color: var(--call-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-comments"></i> تفاصيل المحادثة
                        </h3>

                        <div class="info-box primary">
                            <div class="info-label">
                                <i class="fas fa-comment-alt"></i> ملخص المكالمة
                            </div>
                            <div class="info-value">
                                <div class="call-summary">
                                    {{ $callLog->call_summary }}
                                </div>
                            </div>
                        </div>

                        @if($callLog->notes)
                            <div class="info-box warning">
                                <div class="info-label">
                                    <i class="fas fa-sticky-note"></i> ملاحظات إضافية
                                </div>
                                <div class="info-value">
                                    <div class="additional-notes">
                                        {{ $callLog->notes }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Information -->
        <div class="row">
            <div class="col-12">
                <div class="arkan-info-card">
                    <div style="padding: 2rem;">
                        <h3 style="color: var(--call-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-link"></i> معلومات مرتبطة
                        </h3>

                        <div class="row">
                            <div class="col-md-6">
                                <h6 style="color: var(--gray-700); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-phone-alt"></i> مكالمات أخرى مع هذا العميل
                                </h6>
                                @php
                                    $otherCalls = $callLog->client->callLogs()
                                        ->where('id', '!=', $callLog->id)
                                        ->latest('call_date')
                                        ->limit(5)
                                        ->get();
                                @endphp

                                @if($otherCalls->count() > 0)
                                    <div style="border: 1px solid var(--gray-200); border-radius: var(--radius-lg); overflow: hidden;">
                                        @foreach($otherCalls as $otherCall)
                                            <div style="padding: 1rem; border-bottom: 1px solid var(--gray-200); {{ $loop->last ? 'border-bottom: none;' : '' }}">
                                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                                    <div>
                                                        <small style="color: var(--gray-500);">{{ $otherCall->call_date->format('Y-m-d H:i') }}</small>
                                                        <br>{{ Str::limit($otherCall->call_summary, 50) }}
                                                    </div>
                                                    <span class="status-badge status-{{ $otherCall->status }}">
                                                        {{ $otherCall->status_arabic }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div style="margin-top: 1rem;">
                                        <a href="{{ route('call-logs.index', ['client_id' => $callLog->client->id]) }}" class="arkan-call-btn arkan-call-btn-primary">
                                            <i class="fas fa-list"></i> عرض جميع المكالمات
                                        </a>
                                    </div>
                                @else
                                    <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                        <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 0.5rem; color: var(--gray-400);"></i>
                                        <p>هذه هي المكالمة الوحيدة مع هذا العميل</p>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <h6 style="color: var(--gray-700); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-ticket-alt"></i> تذاكر مفتوحة للعميل
                                </h6>
                                @php
                                    $openTickets = $callLog->client->tickets()
                                        ->whereIn('client_tickets.status', ['open', 'in_progress'])
                                        ->latest('client_tickets.created_at')
                                        ->limit(3)
                                        ->get();
                                @endphp

                                @if($openTickets->count() > 0)
                                    <div style="border: 1px solid var(--gray-200); border-radius: var(--radius-lg); overflow: hidden;">
                                        @foreach($openTickets as $ticket)
                                            <div style="padding: 1rem; border-bottom: 1px solid var(--gray-200); {{ $loop->last ? 'border-bottom: none;' : '' }}">
                                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                                    <div>
                                                        <small style="color: var(--gray-500);">{{ $ticket->ticket_number ?? '#' . $ticket->id }}</small>
                                                        <br>{{ Str::limit($ticket->title ?? $ticket->subject ?? 'بدون عنوان', 40) }}
                                                    </div>
                                                    <span class="outcome-badge {{ $ticket->status == 'open' ? 'outcome-follow_up_needed' : 'outcome-successful' }}">
                                                        {{ $ticket->status == 'open' ? 'مفتوحة' : 'قيد المعالجة' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div style="margin-top: 1rem;">
                                        <a href="{{ route('client-tickets.index', ['client_id' => $callLog->client->id]) }}" class="arkan-call-btn arkan-call-btn-warning">
                                            <i class="fas fa-ticket-alt"></i> عرض جميع التذاكر
                                        </a>
                                    </div>
                                @else
                                    <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                        <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 0.5rem; color: var(--call-primary);"></i>
                                        <p>لا توجد تذاكر مفتوحة للعميل</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="arkan-info-card">
            <div style="padding: 1.5rem; text-align: center;">
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="{{ route('call-logs.edit', $callLog) }}" class="arkan-call-btn arkan-call-btn-warning">
                        <i class="fas fa-edit"></i> تعديل المكالمة
                    </a>
                    <a href="{{ route('call-logs.create', ['client_id' => $callLog->client->id]) }}" class="arkan-call-btn arkan-call-btn-success">
                        <i class="fas fa-plus"></i> إضافة مكالمة جديدة لنفس العميل
                    </a>
                    <form method="POST" action="{{ route('call-logs.destroy', $callLog) }}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="arkan-call-btn arkan-call-btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا السجل؟')">
                            <i class="fas fa-trash"></i> حذف المكالمة
                        </button>
                    </form>
                    <a href="{{ route('call-logs.index') }}" class="btn-secondary">
                        <i class="fas fa-list"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
