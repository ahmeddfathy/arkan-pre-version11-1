@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/crm-dashboard.css') }}" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-chart-line"></i>
                لوحة تحكم إدارة علاقات العملاء
            </h1>
            <p>نظرة شاملة على جميع أنشطة العملاء والمكالمات والتذاكر</p>
        </div>
        <div class="action-buttons">
            <a href="{{ route('clients.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> إضافة عميل جديد
            </a>
            <a href="{{ route('call-logs.create') }}" class="btn btn-success">
                <i class="fas fa-phone"></i> تسجيل مكالمة
            </a>
            <a href="{{ route('client-tickets.create') }}" class="btn btn-warning">
                <i class="fas fa-ticket-alt"></i> إنشاء تذكرة
            </a>
        </div>
    </div>

    <!-- Statistics Cards Row -->
    <div class="row mb-4">
        <!-- Total Clients -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card border-left-primary h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stats-label text-primary">
                                إجمالي العملاء
                            </div>
                            <div class="stats-number">{{ $stats['total_clients'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-primary stats-icon"></i>
                        </div>
                    </div>
                    <div class="row no-gutters align-items-center mt-3">
                        <div class="col">
                            <a href="{{ route('clients.index') }}" class="btn btn-outline-primary btn-sm">
                                عرض الكل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Calls -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card border-left-success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stats-label text-success">
                                إجمالي المكالمات
                            </div>
                            <div class="stats-number">{{ $stats['total_calls'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-phone fa-2x text-success stats-icon"></i>
                        </div>
                    </div>
                    <div class="row no-gutters align-items-center mt-3">
                        <div class="col">
                            <a href="{{ route('call-logs.index') }}" class="btn btn-outline-success btn-sm">
                                عرض الكل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Follow-up Needed -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card border-left-warning h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stats-label text-warning">
                                تحتاج متابعة
                            </div>
                            <div class="stats-number">{{ $stats['calls_needing_followup'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning stats-icon"></i>
                        </div>
                    </div>
                    <div class="row no-gutters align-items-center mt-3">
                        <div class="col">
                            <a href="{{ route('call-logs.index') }}" class="btn btn-outline-warning btn-sm">
                                عرض الكل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Open Tickets -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card border-left-danger h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stats-label text-danger">
                                التذاكر المفتوحة
                            </div>
                            <div class="stats-number">{{ $stats['open_tickets'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-danger stats-icon"></i>
                        </div>
                    </div>
                    <div class="row no-gutters align-items-center mt-3">
                        <div class="col">
                            <a href="{{ route('client-tickets.index', ['status' => 'open']) }}" class="btn btn-outline-danger btn-sm">
                                عرض الكل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Clients -->
        <div class="col-lg-4 mb-4">
            <div class="content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6>
                        <i class="fas fa-user-plus"></i>
                        أحدث العملاء
                    </h6>
                    <a href="{{ route('clients.index') }}" class="btn btn-sm btn-primary">
                        عرض الكل
                    </a>
                </div>
                <div class="card-body">
                    @if($recentClients && $recentClients->count() > 0)
                        @foreach($recentClients as $client)
                            <div class="item-container border-left-primary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="item-title">
                                            <a href="{{ route('clients.show', $client) }}">
                                                {{ $client->name }}
                                            </a>
                                        </h6>
                                        @if($client->company_name)
                                            <p class="item-subtitle">{{ $client->company_name }}</p>
                                        @endif
                                        @if($client->source)
                                            <span class="badge badge-info">{{ $client->source }}</span>
                                        @endif
                                        <div class="item-meta">{{ $client->created_at->diffForHumans() }}</div>
                                    </div>
                                    <div>
                                        <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-users fa-3x"></i>
                            <h6>لا توجد عملاء حديثون</h6>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Calls -->
        <div class="col-lg-4 mb-4">
            <div class="content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="text-success">
                        <i class="fas fa-phone"></i>
                        أحدث المكالمات
                    </h6>
                    <a href="{{ route('call-logs.index') }}" class="btn btn-sm btn-success">
                        عرض الكل
                    </a>
                </div>
                <div class="card-body">
                    @if($recentCalls && $recentCalls->count() > 0)
                        @foreach($recentCalls as $call)
                            <div class="item-container border-left-success">
                                <div>
                                    <h6 class="item-title">
                                        @if($call->client)
                                            <a href="{{ route('clients.show', $call->client) }}">
                                                {{ $call->client->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">عميل محذوف</span>
                                        @endif
                                    </h6>
                                    <p class="item-subtitle">{{ Str::limit($call->call_summary, 60) }}</p>
                                    <div class="mb-2">
                                        <span class="badge badge-{{ $call->contact_type == 'call' ? 'primary' : 'info' }}">
                                            {{ $call->contact_type_arabic }}
                                        </span>
                                        @if($call->outcome == 'follow_up_needed')
                                            <span class="badge badge-warning ml-1">تحتاج متابعة</span>
                                        @endif
                                    </div>
                                    <div class="item-meta">{{ $call->call_date->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-phone fa-3x"></i>
                            <h6>لا توجد مكالمات حديثة</h6>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Tickets -->
        <div class="col-lg-4 mb-4">
            <div class="content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="text-warning">
                        <i class="fas fa-ticket-alt"></i>
                        أحدث التذاكر
                    </h6>
                    <a href="{{ route('client-tickets.index') }}" class="btn btn-sm btn-warning">
                        عرض الكل
                    </a>
                </div>
                <div class="card-body">
                    @if($recentTickets && $recentTickets->count() > 0)
                        @foreach($recentTickets as $ticket)
                            <div class="item-container border-left-warning">
                                <div>
                                    <h6 class="item-title">
                                        <a href="{{ route('client-tickets.show', $ticket) }}">
                                            <code>{{ $ticket->ticket_number }}</code>
                                        </a>
                                    </h6>
                                    <p class="item-subtitle">{{ Str::limit($ticket->title, 50) }}</p>
                                    <div class="mb-2">
                                        <span class="badge badge-{{ $ticket->status_color }}">
                                            {{ $ticket->status_arabic }}
                                        </span>
                                        <span class="badge badge-{{ $ticket->priority_color }} ml-1">
                                            {{ $ticket->priority_arabic }}
                                        </span>
                                    </div>
                                    <div class="item-meta">
                                        {{ $ticket->client?->name ?? 'غير محدد' }} - {{ $ticket->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-ticket-alt fa-3x"></i>
                            <h6>لا توجد تذاكر حديثة</h6>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Statistics Row -->
    <div class="row">
        <!-- Call Success Rate -->
        <div class="col-lg-6 mb-4">
            <div class="content-card">
                <div class="card-header">
                    <h6 class="text-primary">
                        <i class="fas fa-chart-pie"></i>
                        إحصائيات المكالمات
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>المكالمات الناجحة</span>
                                    <span class="text-success font-weight-bold">{{ $stats['successful_calls'] ?? 0 }}</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" data-width="{{ $stats['success_rate'] ?? 0 }}"></div>
                                </div>
                                <small class="text-muted">{{ number_format($stats['success_rate'] ?? 0, 1) }}% نسبة النجاح</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>مكالمات اليوم</span>
                                    <span class="text-primary font-weight-bold">{{ $stats['today_calls'] ?? 0 }}</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-primary" data-width="{{ ($stats['today_calls'] ?? 0) > 0 ? 100 : 0 }}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-6 mb-4">
            <div class="content-card">
                <div class="card-header">
                    <h6 class="text-info">
                        <i class="fas fa-bolt"></i>
                        إجراءات سريعة
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="list-group list-group-flush">
                                <a href="{{ route('call-logs.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-phone text-primary"></i>
                                        جميع سجلات المكالمات
                                    </div>
                                    @if(isset($stats['total_calls']) && $stats['total_calls'] > 0)
                                        <span class="badge badge-primary">{{ $stats['total_calls'] }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('client-tickets.index', ['status' => 'open']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-ticket-alt text-danger"></i>
                                        التذاكر المفتوحة
                                    </div>
                                    @if(($stats['open_tickets'] ?? 0) > 0)
                                        <span class="badge badge-danger">{{ $stats['open_tickets'] }}</span>
                                    @endif
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="list-group list-group-flush">
                                <a href="{{ route('client-tickets.index', ['assigned_to' => auth()->id()]) }}" class="list-group-item list-group-item-action">
                                    <i class="fas fa-user text-info"></i>
                                    التذاكر المعينة لي
                                </a>
                                <a href="{{ route('clients.export') }}" class="list-group-item list-group-item-action">
                                    <i class="fas fa-download text-success"></i>
                                    تصدير بيانات العملاء
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
$(document).ready(function() {
    // Set progress bar widths from data attributes with animation
    $('.progress-bar[data-width]').each(function() {
        var width = $(this).data('width');
        $(this).animate({
            width: width + '%'
        }, 1000);
    });

    // Animate statistics numbers
    $('.stats-number').each(function() {
        var $this = $(this);
        var countTo = parseInt($this.text());

        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'linear',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(countTo);
            }
        });
    });

    // Auto-refresh dashboard every 10 minutes
    setInterval(function() {
        location.reload();
    }, 600000);

    // Add smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if( target.length ) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });

    // Add loading state for buttons
    $('.btn').on('click', function() {
        if(!$(this).hasClass('no-loading')) {
            $(this).prop('disabled', true);
            var originalText = $(this).html();
            $(this).html('<span class="loading"></span> جاري التحميل...');

            setTimeout(() => {
                $(this).prop('disabled', false);
                $(this).html(originalText);
            }, 3000);
        }
    });

    // Add tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize popovers if any
    $('[data-toggle="popover"]').popover();

    // Responsive table handling
    if($(window).width() < 768) {
        $('.table-responsive').addClass('mobile-view');
    }
});
</script>
@endpush
