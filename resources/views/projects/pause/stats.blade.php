@extends('layouts.app')

@section('title', 'إحصائيات توقيف المشاريع')

@push('styles')
<style>
.stats-dashboard {
    padding: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.stats-header {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.stats-header h1 {
    color: #667eea;
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.stat-box {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-box:hover {
    transform: translateY(-5px);
}

.stat-box h3 {
    color: #374151;
    font-size: 1.2rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-value {
    font-size: 3rem;
    font-weight: bold;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.reason-list {
    list-style: none;
    padding: 0;
}

.reason-item {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.reason-item:last-child {
    border-bottom: none;
}

.reason-label {
    font-weight: 600;
    color: #374151;
}

.reason-count {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: bold;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background: #667eea;
    color: white;
}
</style>
@endpush

@section('content')
<div class="stats-dashboard">
    <div class="stats-header">
        <h1><i class="fas fa-chart-bar"></i> إحصائيات توقيف المشاريع</h1>
        <a href="{{ route('projects.pause.index') }}" class="back-btn">
            <i class="fas fa-arrow-right"></i>
            العودة للقائمة
        </a>
    </div>

    <div class="stats-grid">
        <!-- إجمالي المشاريع الموقوفة -->
        <div class="stat-box">
            <h3><i class="fas fa-pause-circle"></i> المشاريع الموقوفة حالياً</h3>
            <div class="stat-value">{{ $stats['total_paused'] ?? 0 }}</div>
        </div>

        <!-- إجمالي المشاريع النشطة -->
        <div class="stat-box">
            <h3><i class="fas fa-play-circle"></i> المشاريع النشطة</h3>
            <div class="stat-value">{{ $stats['total_active'] ?? 0 }}</div>
        </div>

        <!-- نسبة التوقيف -->
        <div class="stat-box">
            <h3><i class="fas fa-percentage"></i> نسبة التوقيف</h3>
            <div class="stat-value">{{ $stats['pause_percentage'] ?? 0 }}%</div>
        </div>
    </div>

    <!-- أسباب التوقيف -->
    <div class="chart-container" style="margin-top: 2rem;">
        <h3 style="color: #374151; margin-bottom: 1.5rem;">
            <i class="fas fa-list"></i>
            توزيع أسباب التوقيف
        </h3>
        <ul class="reason-list">
            @foreach(($stats['by_reason'] ?? []) as $reason => $data)
            <li class="reason-item">
                <span class="reason-label">{{ $data['label'] }}</span>
                <span class="reason-count">{{ $data['count'] }}</span>
            </li>
            @endforeach
        </ul>
    </div>

    <!-- آخر المشاريع الموقوفة -->
    <div class="chart-container">
        <h3 style="color: #374151; margin-bottom: 1.5rem;">
            <i class="fas fa-history"></i>
            آخر المشاريع الموقوفة
        </h3>
        <table class="table" style="width: 100%;">
            <thead>
                <tr>
                    <th>المشروع</th>
                    <th>السبب</th>
                    <th>توقف بواسطة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($stats['recent_pauses'] ?? []) as $pause)
                <tr>
                    <td>{{ $pause->project->name ?? '-' }}</td>
                    <td>{{ $pause->pause_reason }}</td>
                    <td>{{ $pause->pausedBy->name ?? '-' }}</td>
                    <td>{{ $pause->paused_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #9ca3af;">
                        لا توجد بيانات
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

