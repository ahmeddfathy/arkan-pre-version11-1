@extends('layouts.app')

@section('title', 'إدارة المواسم')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/seasons.css') }}">
@endpush

@section('content')
<div class="seasons-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>🏆 إدارة المواسم</h1>
            <p>عرض وإدارة جميع المواسم مع التواريخ والإحصائيات</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-row">
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-filter"></i>
                        تصفية حسب الحالة
                    </label>
                    <select class="filter-select" onchange="filterSeasons(this.value)">
                        <option value="all">جميع المواسم</option>
                        <option value="current">السيزون الحالي</option>
                        <option value="upcoming">المواسم القادمة</option>
                        <option value="expired">المواسم المنتهية</option>
                        <option value="active">المواسم النشطة</option>
                    </select>
                </div>

                <div class="filter-group" style="flex: 0;">
                    <label class="filter-label" style="opacity: 0;">إضافة</label>
                    <a href="{{ route('seasons.create') }}" class="add-season-btn">
                        <i class="fas fa-plus-circle"></i>
                        إضافة سيزون جديد
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card total">
                <div class="stat-icon">📊</div>
                <div class="stat-number">{{ $seasons->count() }}</div>
                <div class="stat-label">إجمالي المواسم</div>
            </div>
            <div class="stat-card current">
                <div class="stat-icon">⚡</div>
                <div class="stat-number">{{ $seasons->where('is_current', true)->count() }}</div>
                <div class="stat-label">السيزون الحالي</div>
            </div>
            <div class="stat-card upcoming">
                <div class="stat-icon">🔜</div>
                <div class="stat-number">{{ $seasons->where('is_upcoming', true)->count() }}</div>
                <div class="stat-label">المواسم القادمة</div>
            </div>
            <div class="stat-card expired">
                <div class="stat-icon">✅</div>
                <div class="stat-number">{{ $seasons->where('is_expired', true)->count() }}</div>
                <div class="stat-label">المواسم المنتهية</div>
            </div>
        </div>

        <!-- Seasons Grid -->
        <div class="seasons-grid" id="seasonsGrid">
            @forelse ($seasons as $season)
                <div class="season-card" data-status="{{ $season->is_current ? 'current' : ($season->is_upcoming ? 'upcoming' : ($season->is_expired ? 'expired' : 'other')) }}" data-active="{{ $season->is_active ? 'yes' : 'no' }}">
                    <div class="season-header" style="background-image: url('{{ $season->banner_image ? asset('storage/'.$season->banner_image) : asset('img/default-season-banner.jpg') }}');">
                        <div class="season-overlay">
                            <h3 class="season-title">{{ $season->name }}</h3>
                        </div>
                        @if($season->is_current)
                            <span class="season-badge badge-current">السيزون الحالي</span>
                        @elseif($season->is_upcoming)
                            <span class="season-badge badge-upcoming">قادم</span>
                        @elseif($season->is_expired)
                            <span class="season-badge badge-expired">منتهي</span>
                        @endif
                    </div>

                    <div class="season-body">
                        <div class="season-info">
                            <div class="season-date">
                                <i class="far fa-calendar-alt"></i>
                                {{ $season->start_date->format('Y/m/d') }} - {{ $season->end_date->format('Y/m/d') }}
                            </div>

                            <div class="season-status">
                                @if($season->is_active)
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check-circle"></i>
                                        نشط
                                    </span>
                                @else
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-pause-circle"></i>
                                        غير نشط
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="season-progress">
                            <div class="progress-label">
                                <span>نسبة الإنجاز</span>
                                <span>{{ $season->progress_percentage }}%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: {{ $season->progress_percentage }}%; background: linear-gradient(90deg, {{ $season->color_theme }}, {{ $season->color_theme }}dd);"></div>
                            </div>
                        </div>

                        <div class="season-actions">
                            <a href="{{ route('seasons.show', $season) }}" class="season-btn btn-view">
                                <i class="fas fa-eye"></i>
                                عرض
                            </a>
                            <a href="{{ route('seasons.edit', $season) }}" class="season-btn btn-edit">
                                <i class="fas fa-edit"></i>
                                تعديل
                            </a>
                            <form action="{{ route('seasons.destroy', $season) }}" method="POST" style="flex: 1;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="season-btn btn-delete" style="width: 100%;" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا السيزون؟')">
                                    <i class="fas fa-trash"></i>
                                    حذف
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state" style="grid-column: 1/-1;">
                    <i class="fas fa-trophy"></i>
                    <h3>لا توجد مواسم مضافة حتى الآن</h3>
                    <p>ابدأ بإضافة أول سيزون من خلال زر "إضافة سيزون جديد"</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function filterSeasons(status) {
        const cards = document.querySelectorAll('.season-card');

        cards.forEach(card => {
            const cardStatus = card.getAttribute('data-status');
            const cardActive = card.getAttribute('data-active');

            if (status === 'all') {
                card.style.display = 'block';
            } else if (status === 'active') {
                card.style.display = cardActive === 'yes' ? 'block' : 'none';
            } else {
                card.style.display = cardStatus === status ? 'block' : 'none';
            }
        });
    }
</script>
@endpush
