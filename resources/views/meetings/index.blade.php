@extends('layouts.app')

@section('title', 'إدارة الاجتماعات')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/meetings/meetings-modern.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📅 إدارة الاجتماعات</h1>
            <p>عرض وإدارة جميع اجتماعاتك مع الفريق والعملاء</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        <!-- Statistics Section -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number" style="color: #667eea;">{{ $stats['total'] }}</div>
                <div class="stat-label">
                    <i class="fas fa-calendar-alt"></i>
                    إجمالي الاجتماعات
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-number" style="color: #10b981;">{{ $stats['upcoming'] }}</div>
                <div class="stat-label">
                    <i class="fas fa-clock"></i>
                    اجتماعات قادمة
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-number" style="color: #f59e0b;">{{ $stats['current'] }}</div>
                <div class="stat-label">
                    <i class="fas fa-video"></i>
                    جارية الآن
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-number" style="color: #6366f1;">{{ $stats['completed'] }}</div>
                <div class="stat-label">
                    <i class="fas fa-check-circle"></i>
                    مكتملة
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-number" style="color: #8b5cf6;">{{ $stats['client_meetings'] }}</div>
                <div class="stat-label">
                    <i class="fas fa-handshake"></i>
                    اجتماعات عملاء
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-number" style="color: #06b6d4;">{{ $stats['internal_meetings'] }}</div>
                <div class="stat-label">
                    <i class="fas fa-building"></i>
                    اجتماعات داخلية
                </div>
            </div>

            @if($stats['pending_approval'] > 0)
            <div class="stat-card">
                <div class="stat-number" style="color: #f59e0b;">{{ $stats['pending_approval'] }}</div>
                <div class="stat-label">
                    <i class="fas fa-hourglass-half"></i>
                    في انتظار الموافقة
                </div>
            </div>
            @endif

            @if($stats['cancelled'] > 0)
            <div class="stat-card">
                <div class="stat-number" style="color: #ef4444;">{{ $stats['cancelled'] }}</div>
                <div class="stat-label">
                    <i class="fas fa-times-circle"></i>
                    ملغاة
                </div>
            </div>
            @endif
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="{{ route('meetings.index') }}" id="filterForm">
                <div class="filters-row">
                    <!-- Date Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-calendar-alt"></i>
                            فلتر حسب الفترة
                        </label>
                        <select name="filter" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="" {{ request('filter') === null ? 'selected' : '' }}>الكل</option>
                            <option value="today" {{ request('filter') === 'today' ? 'selected' : '' }}>اليوم</option>
                            <option value="week" {{ request('filter') === 'week' ? 'selected' : '' }}>هذا الأسبوع</option>
                        </select>
                    </div>

                    <!-- Specific Date Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-calendar"></i>
                            تاريخ محدد
                        </label>
                        <input type="date" name="date" value="{{ request('date') }}" class="filter-select" onchange="document.getElementById('filterForm').submit()" />
                    </div>

                    <!-- Clear Date Button -->
                    @if(request('date'))
                    <div class="filter-group">
                        <label class="filter-label" style="opacity: 0;">مسح</label>
                        <a href="{{ route('meetings.index', array_filter(['filter' => request('filter')])) }}" class="meetings-btn btn-delete" style="padding: 0.75rem 1rem; font-size: 0.85rem;">
                            <i class="fas fa-times"></i>
                            مسح التاريخ
                        </a>
                    </div>
                    @endif

                    <!-- Create Button -->
                    <div class="filter-group" style="margin-right: auto;">
                        <label class="filter-label" style="opacity: 0;">إنشاء</label>
                        <a href="{{ route('meetings.create') }}" class="meetings-btn">
                            <i class="fas fa-plus-circle"></i>
                            إنشاء اجتماع جديد
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabs Navigation -->
        <div class="tabs-navigation">
            <div class="tabs-row">
                <button type="button" class="tab-btn active" data-tab="all">
                    <i class="fas fa-list"></i>
                    جميع الاجتماعات
                </button>
                <button type="button" class="tab-btn" data-tab="current">
                    <i class="fas fa-calendar-day"></i>
                    اجتماعات اليوم
                </button>
                <button type="button" class="tab-btn" data-tab="upcoming">
                    <i class="fas fa-calendar-alt"></i>
                    الاجتماعات القادمة
                </button>
                <button type="button" class="tab-btn" data-tab="past">
                    <i class="fas fa-history"></i>
                    الاجتماعات السابقة
                </button>
                <button type="button" class="tab-btn" data-tab="client">
                    <i class="fas fa-handshake"></i>
                    اجتماعات العملاء
                </button>
                <button type="button" class="tab-btn" data-tab="internal">
                    <i class="fas fa-building"></i>
                    الاجتماعات الداخلية
                </button>
            </div>
        </div>

        <!-- All Meetings Tab -->
        <div class="tab-content" id="all-content">
            <div class="meetings-table-container">
                <div class="table-header">
                    <h2>📋 قائمة جميع الاجتماعات</h2>
                </div>

                <div class="table-responsive">
                    <table class="meetings-table">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>النوع</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>الحالة</th>
                                <th>حالة الموافقة</th>
                                <th>المشاركين</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $allMeetings = $meetings->sortByDesc('start_time');
                            @endphp

                            @forelse($allMeetings as $meeting)
                            <tr>
                                <td>
                                    <div class="meeting-info">
                                        <div class="meeting-avatar">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="meeting-details">
                                            <h4>{{ $meeting->title }}</h4>
                                            <p>{{ Str::limit($meeting->description, 40) ?: 'لا يوجد وصف' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($meeting->type === 'internal')
                                    <span class="status-badge status-scheduled">
                                        <i class="fas fa-building"></i>
                                        داخلي
                                    </span>
                                    @else
                                    <span class="status-badge status-approved">
                                        <i class="fas fa-handshake"></i>
                                        عميل
                                    </span>
                                    @endif
                                </td>
                                <td>{{ $meeting->start_time->format('Y-m-d') }}</td>
                                <td>{{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}</td>
                                <td>
                                    @if($meeting->status === 'cancelled')
                                    <span class="status-badge status-cancelled">
                                        <i class="fas fa-times-circle"></i>
                                        ملغي
                                    </span>
                                    @elseif($meeting->status === 'completed')
                                    <span class="status-badge status-completed">
                                        <i class="fas fa-check-circle"></i>
                                        مكتمل
                                    </span>
                                    @elseif($meeting->start_time <= now() && $meeting->end_time >= now())
                                        <span class="status-badge status-ongoing">
                                            <i class="fas fa-circle"></i>
                                            جاري الآن
                                        </span>
                                        @elseif($meeting->start_time > now())
                                        <span class="status-badge status-scheduled">
                                            <i class="fas fa-clock"></i>
                                            قادم
                                        </span>
                                        @else
                                        <span class="status-badge status-completed">
                                            <i class="fas fa-check"></i>
                                            انتهى
                                        </span>
                                        @endif
                                </td>
                                <td>
                                    @if($meeting->approval_status === 'pending')
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-hourglass-half"></i>
                                        في انتظار
                                    </span>
                                    @elseif($meeting->approval_status === 'rejected')
                                    <span class="status-badge status-rejected">
                                        <i class="fas fa-times"></i>
                                        مرفوض
                                    </span>
                                    @elseif($meeting->approval_status === 'approved' || $meeting->approval_status === 'auto_approved')
                                    <span class="status-badge status-approved">
                                        <i class="fas fa-check-double"></i>
                                        موافق عليه
                                    </span>
                                    @else
                                    <span class="status-badge status-scheduled">
                                        <i class="fas fa-calendar-check"></i>
                                        مجدول
                                    </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-scheduled">
                                        <i class="fas fa-users"></i>
                                        {{ $meeting->participants->count() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
                                        <a href="{{ route('meetings.show', $meeting) }}" class="meetings-btn btn-view" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-eye"></i>
                                            عرض
                                        </a>
                                        @if($meeting->created_by === Auth::id())
                                        <a href="{{ route('meetings.edit', $meeting) }}" class="meetings-btn btn-edit" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i>
                                            تعديل
                                        </a>
                                        <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="meetings-btn btn-delete" style="padding: 0.6rem 1rem; font-size: 0.85rem;" onclick="return confirm('هل أنت متأكد من حذف هذا الاجتماع؟')">
                                                <i class="fas fa-trash"></i>
                                                حذف
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>لا توجد اجتماعات</h4>
                                    <p>لم يتم العثور على أي اجتماعات</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Today's Meetings Tab -->
        <div class="tab-content hidden" id="current-content">
            <div class="meetings-table-container">
                <div class="table-header">
                    <h2>📅 اجتماعات اليوم</h2>
                </div>

                <div class="table-responsive">
                    <table class="meetings-table">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>النوع</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>المشاركين</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $today = \Carbon\Carbon::today();
                            $todayMeetings = $meetings->filter(function($meeting) use ($today) {
                            return $meeting->start_time->isSameDay($today);
                            })->sortBy('start_time');
                            @endphp

                            @forelse($todayMeetings as $meeting)
                            <tr>
                                <td>
                                    <div class="meeting-info">
                                        <div class="meeting-avatar">
                                            <i class="fas fa-video"></i>
                                        </div>
                                        <div class="meeting-details">
                                            <h4>{{ $meeting->title }}</h4>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($meeting->type === 'internal')
                                    <span class="status-badge status-scheduled">داخلي</span>
                                    @else
                                    <span class="status-badge status-approved">عميل</span>
                                    @endif
                                </td>
                                <td>{{ $meeting->start_time->format('Y-m-d') }}</td>
                                <td>{{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}</td>
                                <td>
                                    <span class="status-badge status-ongoing">
                                        <i class="fas fa-users"></i>
                                        {{ $meeting->participants->count() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
                                        <a href="{{ route('meetings.show', $meeting) }}" class="meetings-btn btn-view" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-eye"></i>
                                            عرض
                                        </a>
                                        @if($meeting->created_by === Auth::id())
                                        <a href="{{ route('meetings.edit', $meeting) }}" class="meetings-btn btn-edit" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i>
                                            تعديل
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h4>لا توجد اجتماعات اليوم</h4>
                                    <p>اجتماعات اليوم ستظهر هنا</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Upcoming Meetings Tab -->
        <div class="tab-content hidden" id="upcoming-content">
            <div class="meetings-table-container">
                <div class="table-header">
                    <h2>📅 الاجتماعات القادمة</h2>
                </div>

                <div class="table-responsive">
                    <table class="meetings-table">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>النوع</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>الحالة</th>
                                <th>المشاركين</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $upcomingMeetings = $meetings->filter(function($meeting) {
                            return $meeting->start_time > now();
                            })->sortBy('start_time');
                            @endphp

                            @forelse($upcomingMeetings as $meeting)
                            <tr>
                                <td>
                                    <div class="meeting-info">
                                        <div class="meeting-avatar">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <div class="meeting-details">
                                            <h4>{{ $meeting->title }}</h4>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($meeting->type === 'internal')
                                    <span class="status-badge status-scheduled">داخلي</span>
                                    @else
                                    <span class="status-badge status-approved">عميل</span>
                                    @endif
                                </td>
                                <td>{{ $meeting->start_time->format('Y-m-d') }}</td>
                                <td>{{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}</td>
                                <td>
                                    @if($meeting->approval_status === 'pending')
                                    <span class="status-badge status-pending">في انتظار</span>
                                    @elseif($meeting->approval_status === 'rejected')
                                    <span class="status-badge status-rejected">مرفوض</span>
                                    @elseif($meeting->approval_status === 'approved' || $meeting->approval_status === 'auto_approved')
                                    <span class="status-badge status-approved">موافق عليه</span>
                                    @else
                                    <span class="status-badge status-scheduled">مجدول</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-scheduled">
                                        <i class="fas fa-users"></i>
                                        {{ $meeting->participants->count() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
                                        <a href="{{ route('meetings.show', $meeting) }}" class="meetings-btn btn-view" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-eye"></i>
                                            عرض
                                        </a>
                                        @if($meeting->created_by === Auth::id())
                                        <a href="{{ route('meetings.edit', $meeting) }}" class="meetings-btn btn-edit" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i>
                                            تعديل
                                        </a>
                                        <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="meetings-btn btn-delete" style="padding: 0.6rem 1rem; font-size: 0.85rem;" onclick="return confirm('هل أنت متأكد من حذف هذا الاجتماع؟')">
                                                <i class="fas fa-trash"></i>
                                                حذف
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h4>لا توجد اجتماعات قادمة</h4>
                                    <p>الاجتماعات المجدولة ستظهر هنا</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Past Meetings Tab -->
        <div class="tab-content hidden" id="past-content">
            <div class="meetings-table-container">
                <div class="table-header">
                    <h2>📜 الاجتماعات السابقة</h2>
                </div>

                <div class="table-responsive">
                    <table class="meetings-table">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>النوع</th>
                                <th>التاريخ</th>
                                <th>الحالة</th>
                                <th>عدد الملاحظات</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $pastMeetings = $meetings->filter(function($meeting) {
                            return $meeting->end_time < now();
                                })->sortByDesc('start_time');
                                @endphp

                                @forelse($pastMeetings as $meeting)
                                <tr>
                                    <td>
                                        <div class="meeting-info">
                                            <div class="meeting-avatar">
                                                <i class="fas fa-history"></i>
                                            </div>
                                            <div class="meeting-details">
                                                <h4>{{ $meeting->title }}</h4>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($meeting->type === 'internal')
                                        <span class="status-badge status-scheduled">داخلي</span>
                                        @else
                                        <span class="status-badge status-approved">عميل</span>
                                        @endif
                                    </td>
                                    <td>{{ $meeting->start_time->format('Y-m-d') }}</td>
                                    <td>
                                        @if($meeting->is_completed)
                                        <span class="status-badge status-completed">
                                            <i class="fas fa-check-circle"></i>
                                            مكتمل
                                        </span>
                                        @else
                                        <span class="status-badge status-pending">
                                            <i class="fas fa-exclamation-circle"></i>
                                            غير مكتمل
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-badge status-scheduled">
                                            <i class="fas fa-sticky-note"></i>
                                            {{ is_array($meeting->notes) ? count($meeting->notes) : 0 }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
                                            <a href="{{ route('meetings.show', $meeting) }}" class="meetings-btn btn-view" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                                <i class="fas fa-eye"></i>
                                                عرض
                                            </a>
                                            @if($meeting->created_by === Auth::id())
                                            @if($meeting->is_completed)
                                            <form action="{{ route('meetings.reset-status', $meeting) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="meetings-btn btn-edit" style="padding: 0.6rem 1rem; font-size: 0.85rem;" onclick="return confirm('هل تريد إعادة تعيين حالة الاجتماع؟')">
                                                    <i class="fas fa-undo"></i>
                                                    إعادة تعيين
                                                </button>
                                            </form>
                                            @else
                                            <form action="{{ route('meetings.mark-completed', $meeting) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="meetings-btn" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                                    <i class="fas fa-check"></i>
                                                    تحديد كمكتمل
                                                </button>
                                            </form>
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <h4>لا توجد اجتماعات سابقة</h4>
                                        <p>الاجتماعات المنتهية ستظهر هنا</p>
                                    </td>
                                </tr>
                                @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Client Meetings Tab -->
        <div class="tab-content hidden" id="client-content">
            <div class="meetings-table-container">
                <div class="table-header">
                    <h2>🤝 اجتماعات العملاء</h2>
                </div>

                <div class="table-responsive">
                    <table class="meetings-table">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>العميل</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>المشاركين</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $clientMeetings = $meetings->filter(function($meeting) {
                            return $meeting->type === 'client';
                            })->sortByDesc('start_time');
                            @endphp

                            @forelse($clientMeetings as $meeting)
                            <tr>
                                <td>
                                    <div class="meeting-info">
                                        <div class="meeting-avatar">
                                            <i class="fas fa-handshake"></i>
                                        </div>
                                        <div class="meeting-details">
                                            <h4>{{ $meeting->title }}</h4>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($meeting->client)
                                    <span class="status-badge status-approved">
                                        <i class="fas fa-building"></i>
                                        {{ $meeting->client->name }}
                                    </span>
                                    @else
                                    <span class="text-muted">غير محدد</span>
                                    @endif
                                </td>
                                <td>{{ $meeting->start_time->format('Y-m-d') }}</td>
                                <td>{{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}</td>
                                <td>
                                    <span class="status-badge status-ongoing">
                                        <i class="fas fa-users"></i>
                                        {{ $meeting->participants->count() }}
                                    </span>
                                </td>
                                <td>
                                    @if($meeting->start_time > now())
                                    <span class="status-badge status-scheduled">قادمة</span>
                                    @elseif($meeting->start_time <= now() && $meeting->end_time >= now())
                                        <span class="status-badge status-ongoing">جارية</span>
                                        @elseif($meeting->is_completed)
                                        <span class="status-badge status-completed">مكتملة</span>
                                        @else
                                        <span class="status-badge status-pending">منتهية</span>
                                        @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
                                        <a href="{{ route('meetings.show', $meeting) }}" class="meetings-btn btn-view" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-eye"></i>
                                            عرض
                                        </a>
                                        @if($meeting->created_by === Auth::id())
                                        <a href="{{ route('meetings.edit', $meeting) }}" class="meetings-btn btn-edit" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i>
                                            تعديل
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-handshake"></i>
                                    <h4>لا توجد اجتماعات عملاء</h4>
                                    <p>اجتماعات العملاء ستظهر هنا</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Internal Meetings Tab -->
        <div class="tab-content hidden" id="internal-content">
            <div class="meetings-table-container">
                <div class="table-header">
                    <h2>🏢 الاجتماعات الداخلية</h2>
                </div>

                <div class="table-responsive">
                    <table class="meetings-table">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>المشاركين</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $internalMeetings = $meetings->filter(function($meeting) {
                            return $meeting->type === 'internal';
                            })->sortByDesc('start_time');
                            @endphp

                            @forelse($internalMeetings as $meeting)
                            <tr>
                                <td>
                                    <div class="meeting-info">
                                        <div class="meeting-avatar">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="meeting-details">
                                            <h4>{{ $meeting->title }}</h4>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $meeting->start_time->format('Y-m-d') }}</td>
                                <td>{{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}</td>
                                <td>
                                    <span class="status-badge status-ongoing">
                                        <i class="fas fa-users"></i>
                                        {{ $meeting->participants->count() }}
                                    </span>
                                </td>
                                <td>
                                    @if($meeting->start_time > now())
                                    <span class="status-badge status-scheduled">قادمة</span>
                                    @elseif($meeting->start_time <= now() && $meeting->end_time >= now())
                                        <span class="status-badge status-ongoing">جارية</span>
                                        @elseif($meeting->is_completed)
                                        <span class="status-badge status-completed">مكتملة</span>
                                        @else
                                        <span class="status-badge status-pending">منتهية</span>
                                        @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
                                        <a href="{{ route('meetings.show', $meeting) }}" class="meetings-btn btn-view" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-eye"></i>
                                            عرض
                                        </a>
                                        @if($meeting->created_by === Auth::id())
                                        <a href="{{ route('meetings.edit', $meeting) }}" class="meetings-btn btn-edit" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i>
                                            تعديل
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-building"></i>
                                    <h4>لا توجد اجتماعات داخلية</h4>
                                    <p>الاجتماعات الداخلية ستظهر هنا</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tab = this.getAttribute('data-tab');

                // Remove active class from all buttons
                tabButtons.forEach(btn => btn.classList.remove('active'));

                // Add active class to clicked button
                this.classList.add('active');

                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });

                // Show selected tab content
                const selectedContent = document.getElementById(tab + '-content');
                if (selectedContent) {
                    selectedContent.classList.remove('hidden');
                }
            });
        });
    });
</script>
@endpush

@endsection