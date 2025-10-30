@extends('layouts.app')

@section('title', 'إحصائيات توقيف المشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-pause.css') }}?v={{ time() }}">
<style>
/* Additional Stats Page Styles */
.stats-main-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.stats-chart-box {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stats-chart-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.chart-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.chart-header h3 {
    color: #495057;
    font-size: 1.4rem;
    font-weight: 600;
    margin: 0;
}

.chart-header i {
    color: #007bff;
    font-size: 1.5rem;
}

.reason-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.reason-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.3s ease;
}

.reason-item:last-child {
    border-bottom: none;
}

.reason-item:hover {
    background-color: #f8f9fa;
}

.reason-label {
    font-weight: 600;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 10px;
}

.reason-label i {
    color: #6c757d;
    font-size: 1.1rem;
}

.reason-count {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 1.1rem;
    min-width: 50px;
    text-align: center;
}

.stats-table {
    width: 100%;
    border-collapse: collapse;
}

.stats-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.stats-table th {
    padding: 15px;
    text-align: right;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.stats-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.3s ease;
}

.stats-table tbody tr:hover {
    background-color: #f8f9fa;
}

.stats-table td {
    padding: 15px;
    color: #495057;
}

.back-button-container {
    margin-bottom: 20px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 25px;
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.btn-back:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.empty-data {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-data i {
    font-size: 3rem;
    opacity: 0.3;
    margin-bottom: 15px;
}

.empty-data p {
    font-size: 1.1rem;
    margin: 0;
}
</style>
@endpush

@section('content')
<div class="pause-container">
    <!-- Header Section -->
    <div class="pause-page-header slide-up">
        <i class="fas fa-chart-bar header-icon"></i>
        <h1>
            <i class="fas fa-chart-bar"></i>
            إحصائيات توقيف المشاريع
        </h1>
        <p>تحليل شامل لحالات توقيف المشاريع والأسباب وتوزيعها</p>
    </div>

    <!-- Back Button -->
    <div class="back-button-container fade-in">
        <a href="{{ route('projects.pause.index') }}" class="btn-back">
            <i class="fas fa-arrow-right"></i>
            العودة لقائمة المشاريع
        </a>
    </div>

    <!-- Main Statistics Cards -->
    <div class="row stats-row fade-in">
        <div class="col-md-4 mb-3">
            <div class="stat-card bg-gradient-danger">
                <div class="stat-icon">
                    <i class="fas fa-pause-circle"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ $stats['total_paused'] ?? 0 }}</h3>
                    <p>مشاريع موقوفة حالياً</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="stat-card bg-gradient-success">
                <div class="stat-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ $stats['total_active'] ?? 0 }}</h3>
                    <p>مشاريع نشطة</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="stat-card bg-gradient-info">
                <div class="stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ $stats['pause_percentage'] ?? 0 }}%</h3>
                    <p>نسبة التوقيف</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Details -->
    <div class="stats-main-grid slide-up">
        <!-- Pause Reasons Distribution -->
        <div class="stats-chart-box">
            <div class="chart-header">
                <i class="fas fa-chart-pie"></i>
                <h3>توزيع أسباب التوقيف</h3>
            </div>

            @if(!empty($stats['by_reason']))
        <ul class="reason-list">
                    @foreach($stats['by_reason'] as $reason => $data)
            <li class="reason-item">
                        <span class="reason-label">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ $data['label'] }}
                        </span>
                <span class="reason-count">{{ $data['count'] }}</span>
            </li>
            @endforeach
        </ul>
            @else
                <div class="empty-data">
                    <i class="fas fa-inbox"></i>
                    <p>لا توجد بيانات</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Paused Projects -->
    <div class="projects-table-container slide-up">
        <div class="table-header">
            <h3>
            <i class="fas fa-history"></i>
            آخر المشاريع الموقوفة
        </h3>
        </div>

        @if(!empty($stats['recent_pauses']) && count($stats['recent_pauses']) > 0)
            <div class="table-responsive">
                <table class="stats-table">
            <thead>
                <tr>
                            <th><i class="fas fa-project-diagram"></i> اسم المشروع</th>
                            <th><i class="fas fa-qrcode"></i> الكود</th>
                            <th><i class="fas fa-exclamation-triangle"></i> سبب التوقيف</th>
                            <th><i class="fas fa-user"></i> توقف بواسطة</th>
                            <th><i class="fas fa-info-circle"></i> الحالة</th>
                            <th><i class="fas fa-clock"></i> تاريخ التوقيف</th>
                            <th><i class="fas fa-hourglass-half"></i> مدة التوقيف</th>
                </tr>
            </thead>
            <tbody>
                        @foreach($stats['recent_pauses'] as $pause)
                        <tr>
                            <td>
                                <strong>{{ $pause->project->name ?? '-' }}</strong>
                            </td>
                            <td>
                                <span style="color: #007bff; font-weight: 600;">
                                    {{ $pause->project->code ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="pause-reason-badge">
                                    <i class="fas fa-pause"></i>
                                    {{ $pause->pause_reason }}
                                </span>
                            </td>
                    <td>{{ $pause->pausedBy->name ?? '-' }}</td>
                            <td>
                                @if($pause->is_active)
                                    <span class="status-badge موقوف">
                                        <i class="fas fa-pause-circle"></i>
                                        موقوف حالياً
                                    </span>
                                @else
                                    <span class="status-badge مكتمل">
                                        <i class="fas fa-play-circle"></i>
                                        تم الاستئناف
                                    </span>
                                    @if($pause->resumed_at)
                                        <div style="font-size: 0.8rem; color: #6c757d; margin-top: 3px;">
                                            بواسطة: {{ $pause->resumedBy->name ?? '-' }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 0.9rem;">
                                    <i class="fas fa-calendar-alt" style="color: #6c757d;"></i>
                                    {{ $pause->paused_at ? $pause->paused_at->format('Y-m-d H:i') : '-' }}
                                    <div style="font-size: 0.85rem; color: #6c757d; margin-top: 3px;">
                                        {{ $pause->paused_at ? $pause->paused_at->diffForHumans() : '' }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($pause->paused_at)
                                    <span class="badge" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white; padding: 6px 15px; border-radius: 20px; font-weight: 600;">
                                        <i class="fas fa-hourglass-half"></i>
                                        {{ $pause->duration_short }}
                                    </span>
                                    @if(!$pause->is_active && $pause->resumed_at)
                                        <div style="font-size: 0.8rem; color: #6c757d; margin-top: 3px;">
                                            {{ $pause->resumed_at->format('Y-m-d H:i') }}
                                        </div>
                                    @endif
                                @else
                                    -
                                @endif
                    </td>
                </tr>
                        @endforeach
            </tbody>
        </table>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>لا توجد مشاريع موقوفة</h3>
                <p>جميع المشاريع تعمل بشكل طبيعي</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-fade animations on load
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.slide-up, .fade-in');
    elements.forEach((el, index) => {
        setTimeout(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>
@endpush
