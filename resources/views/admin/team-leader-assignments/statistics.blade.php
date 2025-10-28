@extends('layouts.app')

@section('title', 'إحصائيات توزيع Team Leaders')

@section('content')
@push('styles')
<style>
    .team-leader-stats-container {
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .stats-header {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }

    .stats-header h2 {
        font-size: 32px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 10px;
    }

    .stats-header p {
        color: #6c757d;
        font-size: 16px;
    }

    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .stat-value {
        font-size: 36px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 14px;
        color: #718096;
        font-weight: 500;
    }

    .leaders-table-container {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .table-title {
        font-size: 24px;
        font-weight: 700;
        color: #2d3748;
    }

    .reset-btn {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
    }

    .reset-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
    }

    .leaders-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .leaders-table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        text-align: right;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .leaders-table thead th:first-child {
        border-top-right-radius: 10px;
    }

    .leaders-table thead th:last-child {
        border-top-left-radius: 10px;
    }

    .leaders-table tbody tr {
        background: #f8f9fa;
        transition: all 0.3s ease;
    }

    .leaders-table tbody tr:hover {
        background: #e9ecef;
        transform: scale(1.01);
    }

    .leaders-table tbody td {
        padding: 20px 15px;
        border: none;
        color: #2d3748;
    }

    .leaders-table tbody tr td:first-child {
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
    }

    .leaders-table tbody tr td:last-child {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
    }

    .leader-name {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .leader-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 18px;
    }

    .leader-info {
        display: flex;
        flex-direction: column;
    }

    .leader-info strong {
        font-size: 16px;
        color: #2d3748;
        margin-bottom: 2px;
    }

    .leader-info small {
        color: #718096;
        font-size: 13px;
    }

    .projects-badge {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .last-assignment-badge {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
    }

    .next-badge {
        background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 700;
        display: inline-block;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 4px 15px rgba(48, 207, 208, 0.3);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(48, 207, 208, 0.5);
        }
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state i {
        font-size: 80px;
        color: #cbd5e0;
        margin-bottom: 20px;
    }

    .empty-state h4 {
        color: #4a5568;
        font-size: 22px;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #718096;
        font-size: 16px;
    }

    .alert-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
    }
</style>
@endpush

<div class="team-leader-stats-container">
    <div class="container-fluid">
        <!-- Header -->
        <div class="stats-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-chart-line me-2"></i>
                        إحصائيات توزيع Team Leaders
                    </h2>
                    <p>نظام التوزيع التلقائي الدوري (Round Robin) للمشاريع الجديدة</p>
                </div>
                <a href="{{ route('projects.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-right me-2"></i>
                    العودة للمشاريع
                </a>
            </div>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Overview Statistics -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">{{ $stats['total_team_leaders'] ?? 0 }}</div>
                <div class="stat-label">إجمالي Team Leaders</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-value">
                    {{ isset($stats['statistics']) ? collect($stats['statistics'])->sum('projects_count') : 0 }}
                </div>
                <div class="stat-label">إجمالي المشاريع المُعينة</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="stat-value">
                    @if(isset($stats['statistics']) && count($stats['statistics']) > 0)
                        {{ number_format(collect($stats['statistics'])->avg('projects_count'), 1) }}
                    @else
                        0
                    @endif
                </div>
                <div class="stat-label">متوسط المشاريع لكل Team Leader</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="stat-value" id="nextLeaderName">
                    @if(isset($stats['last_assigned_id']) && isset($stats['statistics']))
                        @php
                            $currentIndex = collect($stats['statistics'])->search(function($stat) use ($stats) {
                                return $stat['team_leader_id'] == $stats['last_assigned_id'];
                            });

                            if ($currentIndex === false || $currentIndex >= count($stats['statistics']) - 1) {
                                $nextLeader = $stats['statistics'][0] ?? null;
                            } else {
                                $nextLeader = $stats['statistics'][$currentIndex + 1] ?? null;
                            }
                        @endphp
                        {{ $nextLeader ? Str::limit($nextLeader['team_leader_name'], 15) : 'N/A' }}
                    @else
                        N/A
                    @endif
                </div>
                <div class="stat-label">Team Leader القادم</div>
            </div>
        </div>

        <!-- Team Leaders Table -->
        <div class="leaders-table-container">
            <div class="table-header">
                <h3 class="table-title">
                    <i class="fas fa-list-ul me-2"></i>
                    تفاصيل Team Leaders
                </h3>
                <button type="button" class="reset-btn" onclick="resetRoundRobin()">
                    <i class="fas fa-redo-alt me-2"></i>
                    إعادة تعيين دورة التوزيع
                </button>
            </div>

            @if(isset($stats['statistics']) && count($stats['statistics']) > 0)
                <div class="table-responsive">
                    <table class="leaders-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Team Leader</th>
                                <th>عدد المشاريع</th>
                                <th>آخر تعيين</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['statistics'] as $index => $stat)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="leader-name">
                                            <div class="leader-avatar">
                                                {{ strtoupper(substr($stat['team_leader_name'], 0, 1)) }}
                                            </div>
                                            <div class="leader-info">
                                                <strong>{{ $stat['team_leader_name'] }}</strong>
                                                <small>ID: {{ $stat['team_leader_id'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="projects-badge">
                                            <i class="fas fa-project-diagram"></i>
                                            {{ $stat['projects_count'] }} مشروع
                                        </span>
                                    </td>
                                    <td>
                                        @if($stat['last_assignment'])
                                            <span class="last-assignment-badge">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                {{ \Carbon\Carbon::parse($stat['last_assignment'])->format('Y-m-d') }}
                                            </span>
                                        @else
                                            <span class="text-muted">لم يتم التعيين بعد</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($stats['last_assigned_id']) && $stat['team_leader_id'] == $stats['last_assigned_id'])
                                            @php
                                                $currentIndex = $index;
                                                $nextIndex = ($currentIndex + 1) % count($stats['statistics']);
                                                $isNext = $nextIndex == ($index);
                                            @endphp
                                            @if($isNext || ($currentIndex == count($stats['statistics']) - 1 && $index == 0))
                                                <span class="next-badge">
                                                    <i class="fas fa-arrow-left me-1"></i>
                                                    القادم في الدورة
                                                </span>
                                            @else
                                                <span class="text-muted">آخر تعيين</span>
                                            @endif
                                        @else
                                            <span class="text-muted">في الانتظار</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h4>لا يوجد Team Leaders في النظام</h4>
                    <p>لم يتم العثور على أي مستخدم لديه دور Team Leader (رول 3)</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function resetRoundRobin() {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم إعادة تعيين دورة التوزيع وسيبدأ التعيين من أول Team Leader',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#f5576c',
        confirmButtonText: 'نعم، إعادة تعيين',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('{{ route("team-leader-assignments.reset") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'تم بنجاح!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#667eea'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'خطأ!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#f5576c'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'خطأ!',
                    text: 'حدث خطأ أثناء إعادة التعيين',
                    icon: 'error',
                    confirmButtonColor: '#f5576c'
                });
            });
        }
    });
}
</script>
@endpush

@endsection

