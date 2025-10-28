@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/client-tickets.css') }}">
@endpush

@section('content')
<div class="client-tickets-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header" style="margin-bottom: 2rem;">
            <h1><i class="fas fa-tachometer-alt"></i> لوحة تحكم التذاكر</h1>
            <p>نظرة شاملة على جميع تذاكر العملاء</p>
            <div class="card-tools">
                <a href="{{ route('client-tickets.create') }}" class="arkan-btn arkan-btn-primary">
                    <i class="fas fa-plus-circle"></i> إضافة تذكرة جديدة
                </a>
                <a href="{{ route('client-tickets.index') }}" class="arkan-btn btn-secondary">
                    <i class="fas fa-list-ul"></i> جميع التذاكر
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="dashboard-stat-card" style="border-top: 4px solid #dc2626;">
                <div class="dashboard-stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="dashboard-stat-info">
                    <h3>{{ $stats['open'] ?? 0 }}</h3>
                    <p>التذاكر المفتوحة</p>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="{{ route('client-tickets.index', ['status' => 'open']) }}" class="arkan-btn btn-info btn-group-sm">
                        عرض الكل <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            </div>

            <div class="dashboard-stat-card" style="border-top: 4px solid #0891b2;">
                <div class="dashboard-stat-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="dashboard-stat-info">
                    <h3>{{ $stats['in_progress'] ?? $stats['assigned'] ?? 0 }}</h3>
                    <p>قيد المعالجة</p>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="{{ route('client-tickets.index', ['status' => 'assigned']) }}" class="arkan-btn btn-info btn-group-sm">
                        عرض الكل <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            </div>

            <div class="dashboard-stat-card" style="border-top: 4px solid #059669;">
                <div class="dashboard-stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="dashboard-stat-info">
                    <h3>{{ $stats['resolved'] ?? 0 }}</h3>
                    <p>تم الحل</p>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="{{ route('client-tickets.index', ['status' => 'resolved']) }}" class="arkan-btn btn-info btn-group-sm">
                        عرض الكل <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            </div>

            <div class="dashboard-stat-card" style="border-top: 4px solid #6b7280;">
                <div class="dashboard-stat-icon" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="dashboard-stat-info">
                    <h3>{{ $stats['closed'] ?? 0 }}</h3>
                    <p>المغلقة</p>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="{{ route('client-tickets.index', ['status' => 'closed']) }}" class="arkan-btn btn-info btn-group-sm">
                        عرض الكل <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-container">
            <!-- Status Distribution Chart -->
            <div class="chart-card">
                <h5><i class="fas fa-chart-pie"></i> توزيع التذاكر حسب الحالة</h5>
                <div style="position: relative; height: 250px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Priority Distribution Chart -->
            <div class="chart-card">
                <h5><i class="fas fa-chart-bar"></i> توزيع التذاكر حسب الأولوية</h5>
                <div style="position: relative; height: 250px;">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Tickets -->
        <div class="client-ticket-card" style="margin-top: 2rem;">
            <div class="table-header">
                <h2><i class="fas fa-clock"></i> أحدث التذاكر</h2>
            </div>
            <div class="table-responsive">
                @if($recentTickets && $recentTickets->count() > 0)
                    <table class="arkan-table">
                        <thead>
                            <tr>
                                <th>رقم التذكرة</th>
                                <th>العنوان</th>
                                <th>الحالة</th>
                                <th>الأولوية</th>
                                <th>تاريخ الإنشاء</th>
                                <th>العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTickets as $ticket)
                                <tr>
                                    <td><code>{{ $ticket->ticket_number }}</code></td>
                                    <td>
                                        <strong>{{ $ticket->title }}</strong>
                                        <br><small class="text-muted">{{ Str::limit($ticket->description, 40) }}</small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $ticket->status }}">
                                            {{ $ticket->status_arabic }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-{{ $ticket->priority }}">
                                            {{ $ticket->priority_arabic }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $ticket->created_at->format('Y-m-d') }}<br>
                                        <small class="text-muted">{{ $ticket->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('client-tickets.show', $ticket) }}" class="arkan-btn btn-info" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-3x mb-3"></i>
                        <h4>لا توجد تذاكر</h4>
                        <p>لم يتم إنشاء أي تذاكر بعد</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['مفتوحة', 'قيد المعالجة', 'محلولة', 'مغلقة'],
            datasets: [{
                data: [
                    {{ $stats['open'] ?? 0 }},
                    {{ $stats['in_progress'] ?? $stats['assigned'] ?? 0 }},
                    {{ $stats['resolved'] ?? 0 }},
                    {{ $stats['closed'] ?? 0 }}
                ],
                backgroundColor: [
                    '#dc2626',
                    '#0891b2',
                    '#059669',
                    '#6b7280'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.5,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });

    // Priority Chart
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    new Chart(priorityCtx, {
        type: 'bar',
        data: {
            labels: ['منخفضة', 'متوسطة', 'عالية'],
            datasets: [{
                label: 'عدد التذاكر',
                data: [
                    {{ $priorityStats['low'] ?? 0 }},
                    {{ $priorityStats['medium'] ?? 0 }},
                    {{ $priorityStats['high'] ?? 0 }}
                ],
                backgroundColor: [
                    '#10b981',
                    '#f59e0b',
                    '#ef4444'
                ],
                borderWidth: 0,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.5,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
