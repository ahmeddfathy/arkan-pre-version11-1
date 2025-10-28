@extends('layouts.app')

@section('content')
<div class="activity-dashboard">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-clock"></i>
                        النشاط اليومي للموظفين
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('activity-log.user-daily') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-user-clock"></i>
                            النشاط اليومي
                        </a>
                    </div>
                </div>

                <!-- إحصائيات سريعة -->
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $totalStats['total_users'] }}</h3>
                                    <p>إجمالي الموظفين النشطين</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $totalStats['active_users'] }}</h3>
                                    <p>دخل السيستم</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $totalStats['inactive_users'] }}</h3>
                                    <p>لم يدخل السيستم</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-times"></i>
                                </div>
                            </div>
                        </div>
                        <div class="small-box bg-warning">
                                <div class="inner">
                                    <h4 class="text-white">{{ $totalStats['date_range'] }}</h4>
                                    <p class="text-white">فترة التقرير</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- فلاتر البحث -->
                    <form method="GET" action="{{ route('activity-log.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="days">فترة التقرير</label>
                                <select name="days" id="days" class="form-control" onchange="toggleDateInput()">
                                    @foreach($daysOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $daysFilter == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                    <option value="custom" {{ request('date') ? 'selected' : '' }}>تاريخ محدد</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="date_container" @if(!request('date')) style="display: none;" @endif>
                                <label for="date">التاريخ المحدد</label>
                                <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}">
                            </div>
                            <div class="col-md-3">
                                <label for="user_id">مستخدم محدد</label>
                                <select name="user_id" id="user_id" class="form-control">
                                    <option value="">جميع المستخدمين</option>
                                    @foreach($allUsers as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i>
                                        بحث
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <a href="{{ route('activity-log.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    مسح الفلاتر
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- جدول المستخدمين مع عدد النشاطات -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>المستخدم</th>
                                    <th>إجمالي النشاطات</th>
                                    <th>نشاطات المهام</th>
                                    <th>نشاطات المشاريع</th>
                                    <th>آخر النشاطات</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userStats as $userStat)
                                    <tr class="{{ $userStat['status_class'] }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $userStat['user']->profile_photo_url }}"
                                                     alt="{{ $userStat['user']->name }}"
                                                     class="rounded-circle mr-3"
                                                     width="40" height="40">
                                                <div>
                                                    <strong>{{ $userStat['user']->name }}</strong>
                                                    @if(!$userStat['has_activity'])
                                                        <i class="fas fa-user-times text-danger ml-2" title="لم يدخل السيستم"></i>
                                                    @else
                                                        <i class="fas fa-user-check text-success ml-2" title="دخل السيستم"></i>
                                                    @endif
                                                    <br>
                                                    <small class="text-muted">{{ $userStat['user']->email }}</small>
                                                    @if($userStat['user']->employee_id)
                                                        <br>
                                                        <small class="text-muted">رقم الموظف: {{ $userStat['user']->employee_id }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($userStat['has_activity'])
                                                <span class="badge badge-primary badge-lg">
                                                    {{ $userStat['total_activities'] }}
                                                </span>
                                            @else
                                                <span class="badge badge-danger">
                                                    لا يوجد نشاط
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-success">
                                                {{ $userStat['tasks_activities'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ $userStat['projects_activities'] }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($userStat['recent_activities']->count() > 0)
                                                <div class="activities-list">
                                                    @foreach($userStat['recent_activities']->take(3) as $activity)
                                                        <div class="activity-item mb-1">
                                                            <small class="badge badge-time">
                                                                {{ $activity->created_at->format('H:i') }}
                                                            </small>
                                                            <small class="text-muted ml-2">
                                                                {{ Str::limit($activity->description, 30) }}
                                                            </small>
                                                        </div>
                                                    @endforeach
                                                    @if($userStat['recent_activities']->count() > 3)
                                                        <small class="text-muted">
                                                            و {{ $userStat['recent_activities']->count() - 3 }} نشاطات أخرى...
                                                        </small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">لا توجد نشاطات</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical btn-group-sm" role="group">
                                                @if($userStat['has_activity'])
                                                    <a href="{{ route('activity-log.user-daily', ['user_id' => $userStat['user']->id]) }}"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="عرض النشاط اليومي">
                                                        <i class="fas fa-user-clock"></i>
                                                        النشاط اليومي
                                                    </a>
                                                @else
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        لم يدخل السيستم
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <br>
                                            <span class="text-muted">لا توجد موظفين نشطين</span>
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
</div>
</div>
@endsection

<!-- تضمين ملف CSS الخاص بـ Activity Log -->
<link rel="stylesheet" href="{{ asset('css/activity-log.css') }}">

@push('styles')
<style>
    /* إضافات خاصة بهذه الصفحة فقط */
    .activity-dashboard {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .page-title {
        text-align: center;
        margin-bottom: 2rem;
        color: white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
function toggleDateInput() {
    const daysSelect = document.getElementById('days');
    const dateContainer = document.getElementById('date_container');

    if (daysSelect.value === 'custom') {
        dateContainer.style.display = 'block';
    } else {
        dateContainer.style.display = 'none';
    }
}

// تأكد من إظهار حقل التاريخ عند التحميل إذا كان custom مختار
document.addEventListener('DOMContentLoaded', function() {
    toggleDateInput();
});
</script>
@endpush
