@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/clients.css') }}">
@endpush

@section('content')
<div class="clients-container client-details">
    <!-- Header Section -->
    <div class="client-page-header">
        <div class="client-avatar-section">
            <div class="avatar-circle">
                @if($client->logo)
                    <img src="{{ asset('storage/' . $client->logo) }}" alt="{{ $client->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                @else
                    {{ strtoupper(substr($client->name, 0, 2)) }}
                @endif
            </div>
            <div class="client-header-info">
                <h2>{{ $client->name }}</h2>
                @if($client->company_name)
                    <p class="company-name">
                        <i class="fas fa-building"></i> {{ $client->company_name }}
                    </p>
                @endif
                <p class="creation-date">
                    <i class="fas fa-calendar-plus"></i> عضو منذ {{ $client->created_at->format('Y-m-d') }}
                </p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('clients.edit', $client) }}" class="btn-edit">
                <i class="fas fa-edit"></i> تعديل
            </a>
            <a href="{{ route('call-logs.create', ['client_id' => $client->id]) }}" class="btn-add-call">
                <i class="fas fa-phone"></i> إضافة مكالمة
            </a>
            <a href="{{ route('client-tickets.create', ['client_id' => $client->id]) }}" class="btn-add-ticket">
                <i class="fas fa-ticket-alt"></i> إضافة تذكرة
            </a>
        </div>
    </div>
    <!-- Main Content -->
    <div class="details-grid">
        <!-- Client Basic Info -->
        <div class="detail-card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> معلومات العميل</h3>
            </div>
            <div class="card-body">
                <div class="contact-list">
                    <div class="contact-item">
                        <span><strong>كود العميل:</strong></span>
                        <code class="contact-value">{{ $client->client_code }}</code>
                    </div>

                    @if($client->source)
                        <div class="contact-item">
                            <span><strong>مصدر العميل:</strong></span>
                            <span class="badge badge-info">{{ $client->source }}</span>
                        </div>
                    @endif

                    @if($client->emails && count($client->emails) > 0)
                        <div class="contact-item">
                            <span><strong>الإيميلات:</strong></span>
                            <div class="email-list">
                                @foreach($client->emails as $email)
                                    <div class="email-tag">
                                        <i class="fas fa-envelope"></i> {{ $email }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($client->phones && count($client->phones) > 0)
                        <div class="contact-item">
                            <span><strong>أرقام الهاتف:</strong></span>
                            <div class="phone-list">
                                @foreach($client->phones as $phone)
                                    <div class="phone-tag">
                                        <i class="fas fa-phone"></i> {{ $phone }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="contact-item">
                        <span><strong>تاريخ الإضافة:</strong></span>
                        <span class="created-date">{{ $client->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Interests -->
        <div class="detail-card">
            <div class="card-header">
                <h3><i class="fas fa-heart"></i> اهتمامات العميل</h3>
            </div>
            <div class="card-body">
                @if($client->interests && count($client->interests) > 0)
                    <div style="margin-bottom: 1.5rem;">
                        @foreach($client->interests as $interest)
                            <span class="badge badge-secondary m-1" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: var(--arkan-primary-light); color: var(--arkan-primary-dark); border-radius: 20px;">
                                {{ $interest }}
                                <form method="POST" action="{{ route('clients.remove-interest', $client) }}" class="d-inline" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="interest" value="{{ $interest }}">
                                    <button type="submit" class="btn btn-sm p-0" onclick="return confirm('هل أنت متأكد؟')" style="background: none; border: none; color: var(--arkan-danger); font-size: 0.75rem;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="no-data">لا توجد اهتمامات مسجلة</p>
                @endif

                <form method="POST" action="{{ route('clients.add-interest', $client) }}" style="margin-top: 1rem;">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="interest" class="form-input" placeholder="إضافة اهتمام جديد" required>
                        <button type="submit" class="btn-add-client" style="margin-right: 0.5rem;">إضافة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="clients-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-phone"></i>
            </div>
            <div class="stat-info">
                <h3>{{ $stats['total_calls'] }}</h3>
                <p>إجمالي المكالمات</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: var(--arkan-success);">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-info">
                <h3>{{ $stats['successful_calls'] }}</h3>
                <p>مكالمات ناجحة</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: var(--arkan-warning);">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="stat-info">
                <h3>{{ $stats['open_tickets'] }}</h3>
                <p>التذاكر المفتوحة</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: var(--arkan-danger);">
                <i class="fas fa-exclamation"></i>
            </div>
            <div class="stat-info">
                <h3>{{ $stats['follow_up_needed'] }}</h3>
                <p>تحتاج متابعة</p>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="clients-table-container">
        <div style="padding: 0; border-bottom: 1px solid var(--gray-200);">
            <ul class="nav nav-tabs" id="client-tabs" role="tablist" style="border: none; padding: 1rem; background: var(--gray-50);">
                <li class="nav-item" style="margin-left: 0.5rem;">
                    <a class="nav-link active" id="call-logs-tab" data-toggle="tab" href="#call-logs" role="tab"
                       style="border: none; border-radius: var(--radius-lg); padding: 0.75rem 1.5rem; background: var(--arkan-primary); color: white; transition: var(--transition);">
                        <i class="fas fa-phone"></i> سجل المكالمات
                    </a>
                </li>
                <li class="nav-item" style="margin-left: 0.5rem;">
                    <a class="nav-link" id="tickets-tab" data-toggle="tab" href="#tickets" role="tab"
                       style="border: none; border-radius: var(--radius-lg); padding: 0.75rem 1.5rem; background: var(--gray-200); color: var(--gray-700); transition: var(--transition);">
                        <i class="fas fa-ticket-alt"></i> التذاكر المفتوحة
                    </a>
                </li>
                <li class="nav-item" style="margin-left: 0.5rem;">
                    <a class="nav-link" id="projects-tab" data-toggle="tab" href="#projects" role="tab"
                       style="border: none; border-radius: var(--radius-lg); padding: 0.75rem 1.5rem; background: var(--gray-200); color: var(--gray-700); transition: var(--transition);">
                        <i class="fas fa-project-diagram"></i> المشاريع
                    </a>
                </li>
            </ul>
        </div>
        <div style="padding: 2rem;">
            <div class="tab-content" id="client-tabs-content">
                <!-- Call Logs Tab -->
                <div class="tab-pane fade show active" id="call-logs" role="tabpanel">
                    @if($recentCallLogs->count() > 0)
                        <div class="table-responsive">
                            <table class="clients-table">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>نوع التواصل</th>
                                        <th>الموظف</th>
                                        <th>النتيجة</th>
                                        <th>الملخص</th>
                                        <th>العمليات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentCallLogs as $log)
                                        <tr class="client-row">
                                            <td class="created-date">{{ $log->call_date->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <span class="email-tag" style="background: var(--arkan-primary); color: white;">{{ $log->contact_type_arabic }}</span>
                                            </td>
                                            <td>{{ $log->employee->name }}</td>
                                            <td>
                                                <span class="badge" style="background: {{ $log->outcome == 'successful' ? 'var(--arkan-success)' : ($log->outcome == 'follow_up_needed' ? 'var(--arkan-warning)' : 'var(--gray-400)') }}; color: white; padding: 0.25rem 0.75rem; border-radius: 12px;">
                                                    {{ $log->outcome_arabic }}
                                                </span>
                                            </td>
                                            <td>{{ Str::limit($log->call_summary, 50) }}</td>
                                            <td>
                                                <a href="{{ route('call-logs.show', $log) }}" class="btn-action btn-view">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="text-align: center; margin-top: 2rem;">
                            <a href="{{ route('call-logs.index', ['client_id' => $client->id]) }}" class="btn-view">
                                عرض جميع المكالمات
                            </a>
                        </div>
                    @else
                        <div class="no-clients">
                            <i class="fas fa-phone fa-3x" style="opacity: 0.5; margin-bottom: 1rem;"></i>
                            <p>لا توجد مكالمات مسجلة لهذا العميل</p>
                        </div>
                    @endif
                </div>

                <!-- Tickets Tab -->
                <div class="tab-pane fade" id="tickets" role="tabpanel">
                    @if($openTickets->count() > 0)
                        <div class="table-responsive">
                            <table class="clients-table">
                                <thead>
                                    <tr>
                                        <th>رقم التذكرة</th>
                                        <th>العنوان</th>
                                        <th>الحالة</th>
                                        <th>الأولوية</th>
                                        <th>المعين إليه</th>
                                        <th>العمليات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($openTickets as $ticket)
                                        <tr class="client-row">
                                            <td>
                                                <code style="background: var(--gray-100); padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); color: var(--gray-800);">
                                                    {{ $ticket->ticket_number }}
                                                </code>
                                            </td>
                                            <td>{{ $ticket->title }}</td>
                                            <td>
                                                <span class="badge" style="background: var(--arkan-info); color: white; padding: 0.25rem 0.75rem; border-radius: 12px;">
                                                    {{ $ticket->status_arabic }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: var(--arkan-warning); color: white; padding: 0.25rem 0.75rem; border-radius: 12px;">
                                                    {{ $ticket->priority_arabic }}
                                                </span>
                                            </td>
                                            <td>{{ $ticket->assignedEmployee->name ?? 'غير معين' }}</td>
                                            <td>
                                                <a href="{{ route('client-tickets.show', $ticket) }}" class="btn-action btn-view">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="text-align: center; margin-top: 2rem;">
                            <a href="{{ route('client-tickets.index', ['client_id' => $client->id]) }}" class="btn-submit">
                                عرض جميع التذاكر
                            </a>
                        </div>
                    @else
                        <div class="no-clients">
                            <i class="fas fa-ticket-alt fa-3x" style="opacity: 0.5; margin-bottom: 1rem;"></i>
                            <p>لا توجد تذاكر مفتوحة لهذا العميل</p>
                        </div>
                    @endif
                </div>

                <!-- Projects Tab -->
                <div class="tab-pane fade" id="projects" role="tabpanel">
                    @if($client->projects->count() > 0)
                        <div class="table-responsive">
                            <table class="clients-table">
                                <thead>
                                    <tr>
                                        <th>اسم المشروع</th>
                                        <th>الحالة</th>
                                        <th>تاريخ البدء</th>
                                        <th>تاريخ الانتهاء</th>
                                        <th>العمليات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($client->projects as $project)
                                        <tr class="client-row">
                                            <td><strong>{{ $project->name }}</strong></td>
                                            <td>
                                                <span class="badge" style="background: var(--arkan-accent); color: white; padding: 0.25rem 0.75rem; border-radius: 12px;">
                                                    {{ $project->status }}
                                                </span>
                                            </td>
                                            <td class="created-date">{{ $project->start_date ? $project->start_date->format('Y-m-d') : '-' }}</td>
                                            <td class="created-date">
                                                @php
                                                    $deliveryDate = $project->client_agreed_delivery_date ?? $project->team_delivery_date;
                                                @endphp
                                                {{ $deliveryDate ? $deliveryDate->format('Y-m-d') : '-' }}
                                            </td>
                                            <td>
                                                <a href="{{ route('projects.show', $project) }}" class="btn-action btn-view">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="no-clients">
                            <i class="fas fa-project-diagram fa-3x" style="opacity: 0.5; margin-bottom: 1rem;"></i>
                            <p>لا توجد مشاريع لهذا العميل</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 2rem;">
        <a href="{{ route('clients.index') }}" class="btn-back">
            <i class="fas fa-arrow-left"></i> العودة للقائمة
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Enhanced Bootstrap tabs with animations
    $('#client-tabs a').on('click', function (e) {
        e.preventDefault();

        // Remove active class from all tabs
        $('#client-tabs a').removeClass('active').css({
            'background': 'var(--gray-200)',
            'color': 'var(--gray-700)'
        });

        // Add active class to current tab
        $(this).addClass('active').css({
            'background': 'var(--arkan-primary)',
            'color': 'white'
        });

        // Show tab content
        $(this).tab('show');
    });

    // Add hover effects to tabs
    $('#client-tabs a').hover(
        function() {
            if (!$(this).hasClass('active')) {
                $(this).css({
                    'background': 'var(--gray-300)',
                    'transform': 'translateY(-2px)',
                    'box-shadow': 'var(--shadow-md)'
                });
            }
        },
        function() {
            if (!$(this).hasClass('active')) {
                $(this).css({
                    'background': 'var(--gray-200)',
                    'transform': 'translateY(0)',
                    'box-shadow': 'none'
                });
            }
        }
    );
});
</script>
@endpush
