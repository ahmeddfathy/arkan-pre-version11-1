@extends('layouts.app')

<!-- تضمين ملف CSS الخاص بـ Activity Log -->
<link rel="stylesheet" href="{{ asset('css/activity-log.css') }}">

@section('content')
<div class="activity-dashboard">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-user-clock"></i>
                        نشاط المستخدم اليومي
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('activity-log.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            العودة للسجل الكامل
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- فلاتر البحث -->
                    <form method="GET" action="{{ route('activity-log.user-daily') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="user_id">المستخدم</label>
                                <select name="user_id" id="user_id" class="form-control" required>
                                    <option value="">اختر المستخدم</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->employee_id ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date">التاريخ</label>
                                <input type="date" name="date" id="date" class="form-control" value="{{ request('date', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="subject_type">نوع النشاط</label>
                                <select name="subject_type" id="subject_type" class="form-control">
                                    <option value="">جميع الأنواع</option>
                                    <option value="App\Models\Task" {{ request('subject_type') == 'App\Models\Task' ? 'selected' : '' }}>المهام</option>
                                    <option value="App\Models\Project" {{ request('subject_type') == 'App\Models\Project' ? 'selected' : '' }}>المشاريع</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                        بحث
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    @if($selectedUser)
                        <!-- معلومات المستخدم -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $selectedUser->profile_photo_url }}"
                                                 alt="{{ $selectedUser->name }}"
                                                 class="rounded-circle mr-3"
                                                 width="60" height="60">
                                            <div>
                                                <h5 class="mb-1">{{ $selectedUser->name }}</h5>
                                                <p class="text-muted mb-1">{{ $selectedUser->email }}</p>
                                                @if($selectedUser->employee_id)
                                                    <small class="text-muted">رقم الموظف: {{ $selectedUser->employee_id }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- إحصائيات سريعة -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-primary">{{ $stats['total_activities'] }}</h3>
                                        <p class="mb-0">إجمالي النشاطات</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-success">{{ $stats['tasks_activities'] }}</h3>
                                        <p class="mb-0">نشاطات المهام</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-info">{{ $stats['projects_activities'] }}</h3>
                                        <p class="mb-0">نشاطات المشاريع</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-warning">{{ $stats['unique_items'] }}</h3>
                                        <p class="mb-0">عناصر مختلفة</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- النشاطات مرتبة بالوقت -->
                        @if($activities->count() > 0)
                            <div class="timeline">
                                @foreach($activities as $activity)
                                    <div class="timeline-item">
                                        <div class="timeline-marker">
                                            @if($activity->subject_type == 'App\Models\Task')
                                                <i class="fas fa-tasks text-success"></i>
                                            @elseif($activity->subject_type == 'App\Models\Project')
                                                <i class="fas fa-project-diagram text-info"></i>
                                            @else
                                                <i class="fas fa-circle text-secondary"></i>
                                            @endif
                                        </div>
                                        <div class="timeline-content">
                                            <div class="card">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">
                                                        @if($activity->subject_type == 'App\Models\Task')
                                                            <i class="fas fa-tasks text-success"></i>
                                                            مهمة
                                                        @elseif($activity->subject_type == 'App\Models\Project')
                                                            <i class="fas fa-project-diagram text-info"></i>
                                                            مشروع
                                                        @endif
                                                        - {{ $activity->description }}
                                                    </h6>
                                                    <span class="badge badge-time">
                                                        {{ $activity->created_at->format('H:i') }}
                                                    </span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <p class="mb-2">
                                                                <strong>الوصف:</strong> {{ $activity->description }}
                                                            </p>
                                                            @if($activity->event)
                                                                <p class="mb-2">
                                                                    <strong>الحدث:</strong>
                                                                    <span class="badge badge-primary">{{ $activity->event }}</span>
                                                                </p>
                                                            @endif
                                                            @if($activity->subject)
                                                                <p class="mb-2">
                                                                    <strong>العنصر:</strong>
                                                                    @if($activity->subject_type == 'App\Models\Task')
                                                                        <a href="{{ route('tasks.show', $activity->subject) }}" class="text-decoration-none">
                                                                            {{ $activity->subject->name ?? 'مهمة #' . $activity->subject_id }}
                                                                        </a>
                                                                    @elseif($activity->subject_type == 'App\Models\Project')
                                                                        <a href="{{ route('projects.show', $activity->subject) }}" class="text-decoration-none">
                                                                            {{ $activity->subject->name ?? 'مشروع #' . $activity->subject_id }}
                                                                        </a>
                                                                    @endif
                                                                </p>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-4 text-right">
                                                            <small class="text-muted">
                                                                {{ $activity->created_at->diffForHumans() }}
                                                            </small>
                                                            <br>
                                                            <a href="{{ route('activity-log.show', $activity) }}"
                                                               class="btn btn-sm btn-outline-primary mt-2">
                                                                <i class="fas fa-eye"></i>
                                                                تفاصيل
                                                            </a>
                                                        </div>
                                                    </div>

                                                    @if($activity->properties && count($activity->properties) > 0)
                                                        <div class="mt-3">
                                                            <button class="btn btn-sm btn-outline-info"
                                                                    data-toggle="collapse"
                                                                    data-target="#details-{{ $activity->id }}">
                                                                <i class="fas fa-info-circle"></i>
                                                                عرض التفاصيل
                                                            </button>
                                                            <div class="collapse mt-2" id="details-{{ $activity->id }}">
                                                                <div class="card">
                                                                    <div class="card-body">
                                                                        <pre class="mb-0 small">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">لا توجد نشاطات لهذا المستخدم في هذا التاريخ</h5>
                                <p class="text-muted">جرب تغيير التاريخ أو المستخدم</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">اختر مستخدم للعرض</h5>
                            <p class="text-muted">استخدم الفلاتر أعلاه لاختيار المستخدم والتاريخ</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 30px;
    }

    .timeline-marker {
        position: absolute;
        left: -22px;
        top: 20px;
        width: 40px;
        height: 40px;
        background: white;
        border: 3px solid #dee2e6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }

    .timeline-content {
        margin-left: 20px;
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .badge {
        font-size: 0.8em;
    }

    pre {
        max-height: 200px;
        overflow-y: auto;
        font-size: 0.8em;
    }
</style>
@endpush
