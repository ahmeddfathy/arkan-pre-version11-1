@extends('layouts.app')

@section('content')
<head>
    <link rel="stylesheet" href="{{ asset('css/employee-profile.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<div class="d-none" data-user-id="{{ $user->id }}"></div>

<div class="employee-profile-container">
    <!-- Header Section -->
    <div class="profile-header">
        <div class="profile-cover-image">
            <div class="profile-avatar-section">
                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="profile-avatar">
                @if($user->employee_status == 'active')
                    <div class="status-indicator active"></div>
                @endif
            </div>
        </div>

        <div class="profile-header-info">
            <h1 class="employee-name">{{ $user->name }}</h1>
            <p class="employee-title">{{ $user->job_progression ?? 'موظف' }}</p>
            <p class="employee-department">{{ $user->department ?? 'غير محدد' }}</p>
            <div class="employee-id">ID: {{ $user->employee_id }}</div>

            @if(!$isOwnProfile)
                <div class="profile-actions">
                    <button class="btn btn-primary btn-sm">
                        <i class="fas fa-envelope"></i> إرسال رسالة
                    </button>
                </div>
            @endif
        </div>
    </div>

        <!-- Date Filter Section -->
    <div class="date-filter-section mb-4">
        <div class="filter-container">
            <div class="filter-header">
                <h5 class="filter-title">
                    <i class="fas fa-calendar-alt"></i>
                    فلترة البيانات حسب الفترة
                </h5>
            </div>

            <form id="dateFilterForm" class="filter-form">
                <div class="date-inputs-group">
                    <div class="date-input-wrapper">
                        <label for="start_date" class="date-label">من تاريخ</label>
                        <input type="date" class="date-input" id="start_date" name="start_date"
                               value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                    </div>

                    <div class="date-separator">
                        <i class="fas fa-arrow-left"></i>
                    </div>

                    <div class="date-input-wrapper">
                        <label for="end_date" class="date-label">إلى تاريخ</label>
                        <input type="date" class="date-input" id="end_date" name="end_date"
                               value="{{ request('end_date', now()->format('Y-m-d')) }}">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter-apply">
                        <i class="fas fa-search"></i>
                        فلترة
                    </button>
                    <button type="button" class="btn-filter-reset" id="resetFilter">
                        <i class="fas fa-undo"></i>
                        إعادة تعيين
                    </button>
                </div>
            </form>
        </div>
    </div>



    <!-- Navigation Tabs -->
    <div class="profile-navigation">
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="fas fa-chart-bar"></i> نظرة عامة
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                    <i class="fas fa-user"></i> المعلومات الشخصية
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">
                    <i class="fas fa-trophy"></i> الأداء والإنجازات
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab">
                    <i class="fas fa-calendar-check"></i> الحضور والانصراف
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects" type="button" role="tab">
                    <i class="fas fa-project-diagram"></i> المشاريع والمهام
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
                    <i class="fas fa-file-alt"></i> الطلبات
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="skills-tab" data-bs-toggle="tab" data-bs-target="#skills" type="button" role="tab">
                    <i class="fas fa-graduation-cap"></i> المهارات والتقييمات
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities" type="button" role="tab">
                    <i class="fas fa-briefcase"></i> الأنشطة المهنية
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                    <i class="fas fa-users"></i> التواصل الاجتماعي
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Content -->
    <div class="tab-content profile-content" id="profileTabsContent">

        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <!-- Current Filter Period Display -->
            <div class="current-filter-display mb-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>الفترة المحددة:</strong>
                    من <span id="displayStartDate">{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}</span>
                    إلى <span id="displayEndDate">{{ request('end_date', now()->format('Y-m-d')) }}</span>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="scrollToFilter()">
                        <i class="fas fa-edit"></i> تغيير الفترة
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Quick Stats -->
                <div class="col-12 mb-4">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-primary">
                                <div class="stat-icon">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <div class="stat-details">
                                    <h3 data-stat="total-projects">{{ $profileData['performance_stats']['total_projects'] }}</h3>
                                    <p>مشاريع إجمالية</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-success">
                                <div class="stat-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="stat-details">
                                    <h3 data-stat="total-tasks">{{ $profileData['performance_stats']['total_tasks'] }}</h3>
                                    <p>مهام إجمالية</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-warning">
                                <div class="stat-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-details">
                                    <h3 data-stat="total-points">{{ $profileData['performance_stats']['total_points'] }}</h3>
                                    <p>نقاط الموسم</p>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Score Card -->
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-success">
                                <div class="stat-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stat-details">
                                    <h3 data-stat="performance-score">{{ $profileData['performance_metrics']['overall_score'] }}%</h3>
                                    <p>نقاط الأداء</p>
                                    <small class="performance-level">{{ $profileData['performance_metrics']['performance_level'] }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-info">
                                <div class="stat-icon">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="stat-details">
                                    <h3 data-stat="attendance-rate">{{ $profileData['attendance_data']['period_summary']['attendance_rate'] }}%</h3>
                                    <p>معدل الحضور</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Season Info -->
                @if($profileData['performance_stats']['season_stats'])
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-calendar"></i> الموسم الحالي</h5>
                        </div>
                        <div class="card-body">
                            <h6>{{ $profileData['performance_stats']['current_season'] }}</h6>
                            @php $stats = $profileData['performance_stats']['season_stats'] @endphp
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">المشاريع المكتملة</small>
                                    <div class="h5">{{ $stats['projects_completed'] ?? 0 }}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">المهام المكتملة</small>
                                    <div class="h5">{{ $stats['tasks_completed'] ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Recent Activity -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-clock"></i> النشاط الأخير</h5>
                        </div>
                        <div class="card-body">
                            <div class="activity-timeline">
                                @foreach($profileData['projects_tasks']['recent_tasks']->take(5) as $taskUser)
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6>{{ $taskUser->task->name ?? 'مهمة' }}</h6>
                                        <small class="text-muted">{{ $taskUser->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Information Tab -->
        <div class="tab-pane fade" id="personal" role="tabpanel" aria-labelledby="personal-tab">
            <div class="row">
                <!-- Basic Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user"></i> المعلومات الأساسية</h5>
                        </div>
                        <div class="card-body">
                            @php $basic = $profileData['personal_info']['basic_info'] @endphp
                            <div class="info-row">
                                <span class="info-label">الاسم:</span>
                                <span class="info-value">{{ $basic['name'] }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">البريد الإلكتروني:</span>
                                <span class="info-value">{{ $basic['email'] }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الموظف:</span>
                                <span class="info-value">{{ $basic['employee_id'] }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">القسم:</span>
                                <span class="info-value">{{ $basic['department'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الجنس:</span>
                                <span class="info-value">{{ $basic['gender'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">تاريخ الميلاد:</span>
                                <span class="info-value">{{ $basic['date_of_birth'] ? $basic['date_of_birth']->format('Y-m-d') : 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الهاتف:</span>
                                <span class="info-value">{{ $basic['phone_number'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">العنوان:</span>
                                <span class="info-value">{{ $basic['address'] ?? 'غير محدد' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-briefcase"></i> معلومات التوظيف</h5>
                        </div>
                        <div class="card-body">
                            @php $employment = $profileData['personal_info']['employment_info'] @endphp
                            <div class="info-row">
                                <span class="info-label">تاريخ بداية العمل:</span>
                                <span class="info-value">{{ $employment['start_date_of_employment'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">آخر عقد - بداية:</span>
                                <span class="info-value">{{ $employment['last_contract_start_date'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">آخر عقد - نهاية:</span>
                                <span class="info-value">{{ $employment['last_contract_end_date'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">المسمى الوظيفي:</span>
                                <span class="info-value">{{ $employment['job_progression'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">حالة الموظف:</span>
                                <span class="info-value">{{ $employment['employee_status'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">المستوى التعليمي:</span>
                                <span class="info-value">{{ $employment['education_level'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الحالة الاجتماعية:</span>
                                <span class="info-value">{{ $employment['marital_status'] ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">عدد الأطفال:</span>
                                <span class="info-value">{{ $employment['number_of_children'] ?? '0' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Work Details -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-cogs"></i> تفاصيل العمل</h5>
                        </div>
                        <div class="card-body">
                            @php $work = $profileData['personal_info']['work_details'] @endphp
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-row">
                                        <span class="info-label">نوبة العمل:</span>
                                        <span class="info-value">{{ $work['work_shift'] }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-row">
                                        <span class="info-label">الفريق الحالي:</span>
                                        <span class="info-value">{{ $work['current_team'] }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-row">
                                        <span class="info-label">الأدوار:</span>
                                        <span class="info-value">
                                            @if(!empty($work['roles']))
                                                @foreach($work['roles'] as $role)
                                                    <span class="badge badge-primary">{{ $role }}</span>
                                                @endforeach
                                            @else
                                                غير محدد
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance & Achievements Tab -->
        <div class="tab-pane fade" id="performance" role="tabpanel" aria-labelledby="performance-tab">
            <div class="row">
                <!-- Performance Metrics -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> مؤشرات الأداء للفترة المحددة</h5>
                        </div>
                        <div class="card-body">
                            @php $metrics = $profileData['performance_metrics'] @endphp
                            <div class="row">
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="performance-metric">
                                        <div class="metric-icon bg-primary">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div class="metric-details">
                                            <h4>{{ $metrics['attendance_score'] }}%</h4>
                                            <p>نسبة الحضور</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="performance-metric">
                                        <div class="metric-icon bg-success">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="metric-details">
                                            <h4>{{ $metrics['punctuality_score'] }}%</h4>
                                            <p>الالتزام بالمواعيد</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="performance-metric">
                                        <div class="metric-icon bg-warning">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                        <div class="metric-details">
                                            <h4>{{ $metrics['working_hours_score'] }}%</h4>
                                            <p>ساعات العمل</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="performance-metric">
                                        <div class="metric-icon bg-info">
                                            <i class="fas fa-hand-paper"></i>
                                        </div>
                                        <div class="metric-details">
                                            <h4>{{ $metrics['permissions_score'] }}%</h4>
                                            <p>نقاط الأذونات</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Overall Performance -->
                            <div class="overall-performance mt-4">
                                <div class="card bg-gradient-primary text-white">
                                    <div class="card-body text-center">
                                        <h2 class="mb-2">{{ $metrics['overall_score'] }}%</h2>
                                        <h5 class="mb-0">{{ $metrics['performance_level'] }}</h5>
                                        <small>التقييم العام للأداء</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Performance Details -->
                            <div class="performance-details mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-{{ $metrics['delay_status']['is_good'] ? 'success' : 'warning' }}">
                                            <div class="card-body">
                                                <h6><i class="fas fa-exclamation-triangle"></i> حالة التأخير</h6>
                                                <p class="mb-1">إجمالي دقائق التأخير: <strong>{{ $metrics['delay_status']['minutes'] }}</strong> دقيقة</p>
                                                <p class="mb-1">الحد المسموح: <strong>{{ $metrics['delay_status']['max_acceptable'] }}</strong> دقيقة</p>
                                                <span class="badge bg-{{ $metrics['delay_status']['is_good'] ? 'success' : 'warning' }}">
                                                    {{ $metrics['delay_status']['is_good'] ? 'ضمن المعدل المقبول' : 'يتطلب تحسين' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-{{ $metrics['permissions_status']['is_good'] ? 'success' : 'warning' }}">
                                            <div class="card-body">
                                                <h6><i class="fas fa-hand-paper"></i> حالة الأذونات</h6>
                                                <p class="mb-1">إجمالي دقائق الأذونات: <strong>{{ $metrics['permissions_status']['minutes'] }}</strong> دقيقة</p>
                                                <p class="mb-1">الحد المسموح: <strong>{{ $metrics['permissions_status']['max_acceptable'] }}</strong> دقيقة</p>
                                                <span class="badge bg-{{ $metrics['permissions_status']['is_good'] ? 'success' : 'warning' }}">
                                                    {{ $metrics['permissions_status']['is_good'] ? 'ضمن المعدل المقبول' : 'يتطلب تحسين' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Badges Section -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-medal"></i> الشارات والإنجازات</h5>
                        </div>
                        <div class="card-body">
                            @php $badges = $profileData['badges_achievements'] @endphp

                            @if($badges['current_season_badge'])
                            <div class="current-badge mb-3">
                                <h6>الشارة الحالية</h6>
                                <div class="badge-display">
                                    <span class="badge badge-lg badge-warning">{{ $badges['current_season_badge']->name ?? 'لا توجد شارة' }}</span>
                                </div>
                            </div>
                            @endif

                            <div class="badges-stats">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">إجمالي الشارات</small>
                                        <div class="h5">{{ $badges['total_badges'] }}</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">الشارات النشطة</small>
                                        <div class="h5">{{ $badges['active_badges'] }}</div>
                                    </div>
                                </div>
                            </div>

                            @if($badges['recent_badges']->count() > 0)
                            <div class="recent-badges mt-3">
                                <h6>الشارات الأخيرة</h6>
                                @foreach($badges['recent_badges']->take(5) as $userBadge)
                                <div class="badge-item">
                                    <span class="badge badge-info">{{ $userBadge->badge->name ?? 'شارة' }}</span>
                                    <small class="text-muted">{{ $userBadge->earned_at ? $userBadge->earned_at->diffForHumans() : '' }}</small>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Season Points -->
                @if($badges['season_points'])
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> نقاط الموسم</h5>
                        </div>
                        <div class="card-body">
                            @php $points = $badges['season_points'] @endphp
                            <div class="points-grid">
                                <div class="point-item">
                                    <div class="point-value">{{ $points['total_points'] }}</div>
                                    <div class="point-label">إجمالي النقاط</div>
                                </div>
                                <div class="point-item">
                                    <div class="point-value">{{ $points['tasks_completed'] }}</div>
                                    <div class="point-label">مهام مكتملة</div>
                                </div>
                                <div class="point-item">
                                    <div class="point-value">{{ $points['projects_completed'] }}</div>
                                    <div class="point-label">مشاريع مكتملة</div>
                                </div>
                                <div class="point-item">
                                    <div class="point-value">{{ number_format($points['minutes_worked'] / 60, 1) }}</div>
                                    <div class="point-label">ساعات عمل</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Attendance Tab -->
        <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
            <div class="row">
                <!-- Monthly Summary -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-calendar-alt"></i> ملخص الحضور للفترة المحددة</h5>
                            <small>{{ $profileData['date_range']['days_count'] }} يوم</small>
                        </div>
                        <div class="card-body">
                            @php $attendance = $profileData['attendance_data']['period_summary'] @endphp
                            <div class="row">
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="attendance-stat">
                                        <div class="stat-number">{{ $attendance['working_days'] }}</div>
                                        <div class="stat-label">أيام العمل</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="attendance-stat text-success">
                                        <div class="stat-number">{{ $attendance['present_days'] }}</div>
                                        <div class="stat-label">أيام الحضور</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="attendance-stat text-danger">
                                        <div class="stat-number">{{ $attendance['absent_days'] }}</div>
                                        <div class="stat-label">أيام الغياب</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="attendance-stat text-warning">
                                        <div class="stat-number">{{ $attendance['late_days'] }}</div>
                                        <div class="stat-label">أيام التأخير</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="attendance-stat text-info">
                                        <div class="stat-number">{{ $attendance['attendance_rate'] }}%</div>
                                        <div class="stat-label">معدل الحضور</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="attendance-stat">
                                        <div class="stat-number">{{ $profileData['attendance_data']['max_allowed_absence_days'] }}</div>
                                        <div class="stat-label">أيام الغياب المسموح</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects & Tasks Tab -->
        <div class="tab-pane fade" id="projects" role="tabpanel" aria-labelledby="projects-tab">
            <div class="row">
                @php $projectsData = $profileData['projects_tasks'] @endphp

                <!-- Summary Cards -->
                <div class="col-12 mb-4">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-primary">
                                <div class="stat-icon"><i class="fas fa-project-diagram"></i></div>
                                <div class="stat-details">
                                    <h3>{{ $projectsData['summary']['total_projects'] }}</h3>
                                    <p>إجمالي المشاريع</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-info">
                                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                                <div class="stat-details">
                                    <h3>{{ $projectsData['summary']['total_tasks'] }}</h3>
                                    <p>إجمالي المهام</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-success">
                                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="stat-details">
                                    <h3>{{ $projectsData['summary']['completion_rate'] }}%</h3>
                                    <p>معدل الإنجاز</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-warning">
                                <div class="stat-icon"><i class="fas fa-star"></i></div>
                                <div class="stat-details">
                                    <h3>{{ $projectsData['summary']['total_points_earned'] }}</h3>
                                    <p>النقاط المكتسبة</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Task Types Breakdown -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-chart-pie"></i> تفصيل أنواع المهام</h5>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary active" onclick="showTaskTypeView('cards')" id="cards-btn">
                                    <i class="fas fa-th-large"></i> بطاقات
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="showTaskTypeView('chart')" id="chart-btn">
                                    <i class="fas fa-chart-bar"></i> مخطط
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Cards View -->
                            <div id="task-type-cards-view" class="row">
                                <!-- Regular Tasks -->
                                <div class="col-md-4 mb-3">
                                    <div class="task-type-card">
                                        <div class="task-type-header bg-primary">
                                            <i class="fas fa-clipboard-list"></i>
                                            <h6>المهام العادية</h6>
                                        </div>
                                        <div class="task-type-stats">
                                            <div class="stat-row">
                                                <span>الإجمالي:</span>
                                                <strong>{{ $projectsData['regular_tasks_by_status']['total'] }}</strong>
                                            </div>
                                            <div class="stat-row">
                                                <span>مكتمل:</span>
                                                <strong class="text-success">{{ $projectsData['regular_tasks_by_status']['مكتمل'] }}</strong>
                                            </div>
                                            <div class="stat-row">
                                                <span>جاري التنفيذ:</span>
                                                <strong class="text-warning">{{ $projectsData['regular_tasks_by_status']['جاري التنفيذ'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Tasks -->
                                <div class="col-md-4 mb-3">
                                    <div class="task-type-card">
                                        <div class="task-type-header bg-success">
                                            <i class="fas fa-plus-circle"></i>
                                            <h6>المهام الإضافية</h6>
                                        </div>
                                        <div class="task-type-stats">
                                            <div class="stat-row">
                                                <span>الإجمالي:</span>
                                                <strong>{{ $projectsData['additional_tasks_by_status']['total'] }}</strong>
                                            </div>
                                            <div class="stat-row">
                                                <span>مكتمل:</span>
                                                <strong class="text-success">{{ $projectsData['additional_tasks_by_status']['completed'] }}</strong>
                                            </div>
                                            <div class="stat-row">
                                                <span>النقاط:</span>
                                                <strong class="text-info">{{ $projectsData['additional_tasks_by_status']['total_points'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Tasks -->
                                <div class="col-md-4 mb-3">
                                    <div class="task-type-card">
                                        <div class="task-type-header bg-info">
                                            <i class="fas fa-file-alt"></i>
                                            <h6>مهام القوالب</h6>
                                        </div>
                                        <div class="task-type-stats">
                                            <div class="stat-row">
                                                <span>الإجمالي:</span>
                                                <strong>{{ $projectsData['template_tasks_by_status']['total'] }}</strong>
                                            </div>
                                            <div class="stat-row">
                                                <span>مكتمل:</span>
                                                <strong class="text-success">{{ $projectsData['template_tasks_by_status']['completed'] }}</strong>
                                            </div>
                                            <div class="stat-row">
                                                <span>الساعات:</span>
                                                <strong class="text-warning">{{ $projectsData['summary']['total_hours_worked'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart View -->
                            <div id="task-type-chart-view" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-center mb-3">توزيع أنواع المهام</h6>
                                        <canvas id="taskTypesDistributionChart" width="300" height="300"></canvas>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-center mb-3">معدل الإنجاز بالأنواع</h6>
                                        <canvas id="taskCompletionChart" width="300" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projects Overview -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-project-diagram"></i> المشاريع</h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleProjectsChart()">
                                <i class="fas fa-chart-doughnut"></i> مخطط
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Projects Stats -->
                            <div id="projects-stats-view">
                                @php $projectsStats = $profileData['projects_tasks']['projects_by_status'] @endphp
                                <div class="status-breakdown">
                                    @foreach($projectsStats as $status => $count)
                                    <div class="status-item">
                                        <span class="status-label">{{ $status }}:</span>
                                        <span class="status-count">{{ $count }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Projects Chart -->
                            <div id="projects-chart-view" style="display: none;">
                                <canvas id="projectsChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Tasks Overview -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-tasks"></i> جميع المهام</h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleTasksChart()">
                                <i class="fas fa-chart-pie"></i> مخطط
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Tasks Stats -->
                            <div id="tasks-stats-view">
                                <div class="all-tasks-summary">
                                    <div class="task-type-summary">
                                        <div class="task-type-row">
                                            <span class="task-type-label">المهام العادية:</span>
                                            <span class="task-type-count text-primary">{{ $projectsData['regular_tasks_by_status']['total'] }}</span>
                                        </div>
                                        <div class="task-type-row">
                                            <span class="task-type-label">المهام الإضافية:</span>
                                            <span class="task-type-count text-success">{{ $projectsData['additional_tasks_by_status']['total'] }}</span>
                                        </div>
                                        <div class="task-type-row">
                                            <span class="task-type-label">مهام القوالب:</span>
                                            <span class="task-type-count text-info">{{ $projectsData['template_tasks_by_status']['total'] }}</span>
                                        </div>
                                        <hr>
                                        <div class="task-type-row total-row">
                                            <span class="task-type-label"><strong>الإجمالي:</strong></span>
                                            <span class="task-type-count text-dark"><strong>{{ $projectsData['summary']['total_tasks'] }}</strong></span>
                                        </div>
                                        <div class="task-type-row">
                                            <span class="task-type-label">مكتمل:</span>
                                            <span class="task-type-count text-success">{{ $projectsData['summary']['total_completed_tasks'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tasks Chart -->
                            <div id="tasks-chart-view" style="display: none;">
                                <canvas id="allTasksChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Projects -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> المشاريع الأخيرة</h5>
                        </div>
                        <div class="card-body">
                            @if($profileData['projects_tasks']['recent_projects']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>اسم المشروع</th>
                                            <th>العميل</th>
                                            <th>الحالة</th>
                                            <th>الموسم</th>
                                            <th>تاريخ الإنشاء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($profileData['projects_tasks']['recent_projects'] as $project)
                                        <tr>
                                            <td>{{ $project->name }}</td>
                                            <td>{{ $project->client->name ?? 'غير محدد' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $project->status == 'مكتمل' ? 'success' : ($project->status == 'جاري التنفيذ' ? 'warning' : 'secondary') }}">
                                                    {{ $project->status }}
                                                </span>
                                            </td>
                                            <td>{{ $project->season->name ?? 'غير محدد' }}</td>
                                            <td>{{ $project->created_at->format('Y-m-d') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-muted text-center">لا توجد مشاريع</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requests Tab -->
        <div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests-tab">
            <div class="row">
                <!-- Absence Requests -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-calendar-times"></i> طلبات الغياب</h5>
                        </div>
                        <div class="card-body">
                            @php $absenceRequests = $profileData['requests_history']['absence_requests'] @endphp
                            <div class="requests-summary">
                                <div class="request-stat">
                                    <span class="request-count">{{ $absenceRequests['total'] }}</span>
                                    <span class="request-label">إجمالي</span>
                                </div>
                                <div class="request-stat text-success">
                                    <span class="request-count">{{ $absenceRequests['approved'] }}</span>
                                    <span class="request-label">موافق عليها</span>
                                </div>
                                <div class="request-stat text-warning">
                                    <span class="request-count">{{ $absenceRequests['pending'] }}</span>
                                    <span class="request-label">قيد المراجعة</span>
                                </div>
                                <div class="request-stat text-danger">
                                    <span class="request-count">{{ $absenceRequests['rejected'] }}</span>
                                    <span class="request-label">مرفوضة</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permission Requests -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-clock"></i> طلبات الأذونات</h5>
                        </div>
                        <div class="card-body">
                            @php $permissionRequests = $profileData['requests_history']['permission_requests'] @endphp
                            <div class="requests-summary">
                                <div class="request-stat">
                                    <span class="request-count">{{ $permissionRequests['total'] }}</span>
                                    <span class="request-label">إجمالي</span>
                                </div>
                                <div class="request-stat text-success">
                                    <span class="request-count">{{ $permissionRequests['approved'] }}</span>
                                    <span class="request-label">موافق عليها</span>
                                </div>
                                <div class="request-stat text-warning">
                                    <span class="request-count">{{ $permissionRequests['pending'] }}</span>
                                    <span class="request-label">قيد المراجعة</span>
                                </div>
                                <div class="request-stat text-danger">
                                    <span class="request-count">{{ $permissionRequests['rejected'] }}</span>
                                    <span class="request-label">مرفوضة</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overtime Requests -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-clock"></i> طلبات العمل الإضافي</h5>
                        </div>
                        <div class="card-body">
                            @php $overtimeRequests = $profileData['requests_history']['overtime_requests'] @endphp
                            <div class="requests-summary">
                                <div class="request-stat">
                                    <span class="request-count">{{ $overtimeRequests['total'] }}</span>
                                    <span class="request-label">إجمالي</span>
                                </div>
                                <div class="request-stat text-success">
                                    <span class="request-count">{{ $overtimeRequests['approved'] }}</span>
                                    <span class="request-label">موافق عليها</span>
                                </div>
                                <div class="request-stat text-warning">
                                    <span class="request-count">{{ $overtimeRequests['pending'] }}</span>
                                    <span class="request-label">قيد المراجعة</span>
                                </div>
                                <div class="request-stat text-danger">
                                    <span class="request-count">{{ $overtimeRequests['rejected'] }}</span>
                                    <span class="request-label">مرفوضة</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Tab -->
        <!-- Skills & Evaluations Tab -->
        <div class="tab-pane fade" id="skills" role="tabpanel" aria-labelledby="skills-tab">
            <div class="row">
                @php $skillsData = $profileData['skills_evaluations'] @endphp

                <!-- Skills Performance -->
                @if($skillsData['skills_performance']->count() > 0)
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-star"></i> أداء المهارات</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($skillsData['skills_performance']->take(6) as $skillData)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="skill-item">
                                        <div class="skill-header d-flex justify-content-between">
                                            <h6>{{ $skillData['skill']->name }}</h6>
                                            @php
                                                $badgeClass = $skillData['percentage'] >= 80 ? 'success' : ($skillData['percentage'] >= 60 ? 'warning' : 'danger');
                                            @endphp
                                            <span class="badge bg-{{ $badgeClass }}">
                                                {{ $skillData['percentage'] }}%
                                            </span>
                                        </div>
                                        <div class="skill-progress mt-2">
                                            <div class="progress">
                                                @php
                                                    $widthStyle = 'width: ' . $skillData['percentage'] . '%;';
                                                @endphp
                                                <div class="progress-bar bg-{{ $badgeClass }}"
                                                     style="{{ $widthStyle }}"></div>
                                            </div>
                                        </div>
                                        <div class="skill-details small text-muted mt-1">
                                            {{ $skillData['average_points'] }}/{{ $skillData['max_points'] }} نقطة
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="col-12">
                    <div class="text-center text-muted">
                        <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                        <p>لا توجد تقييمات مهارات في هذه الفترة</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Professional Activities Tab -->
        <div class="tab-pane fade" id="activities" role="tabpanel" aria-labelledby="activities-tab">
            <div class="row">
                @php $activities = $profileData['professional_activities'] @endphp

                <!-- Activity Stats -->
                <div class="col-12 mb-4">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-primary">
                                <div class="stat-icon"><i class="fas fa-phone"></i></div>
                                <div class="stat-details">
                                    <h3>{{ $activities['activity_stats']['calls_stats']['total'] }}</h3>
                                    <p>المكالمات</p>
                                    <small>{{ $activities['activity_stats']['calls_stats']['total_duration'] }} دقيقة</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-success">
                                <div class="stat-icon"><i class="fas fa-users"></i></div>
                                <div class="stat-details">
                                    <h3>{{ $activities['activity_stats']['meetings_stats']['total'] }}</h3>
                                    <p>الاجتماعات</p>
                                    <small>{{ $activities['activity_stats']['meetings_stats']['completed'] }} مكتمل</small>
                                </div>
                            </div>
                        </div>
                
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card bg-info">
                                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                                <div class="stat-details">
                                    <h3>{{ $activities['activity_stats']['tickets_stats']['assigned'] }}</h3>
                                    <p>التذاكر</p>
                                    <small>{{ $activities['activity_stats']['tickets_stats']['resolved'] }} محلولة</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
            <div class="row">
                <!-- Social Stats -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar"></i> إحصائيات التواصل</h5>
                        </div>
                        <div class="card-body">
                            @php $social = $profileData['social_stats'] @endphp
                            <div class="row">
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="social-stat">
                                        <div class="stat-number">{{ $social['posts_count'] }}</div>
                                        <div class="stat-label">المنشورات</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="social-stat">
                                        <div class="stat-number">{{ $social['followers_count'] }}</div>
                                        <div class="stat-label">المتابعون</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="social-stat">
                                        <div class="stat-number">{{ $social['following_count'] }}</div>
                                        <div class="stat-label">المتابَعون</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="social-stat">
                                        <div class="stat-number">{{ $social['likes_given'] }}</div>
                                        <div class="stat-label">الإعجابات</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="social-stat">
                                        <div class="stat-number">{{ $social['comments_made'] }}</div>
                                        <div class="stat-label">التعليقات</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Posts -->
                @if($social['recent_posts']->count() > 0)
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-newspaper"></i> المنشورات الأخيرة</h5>
                        </div>
                        <div class="card-body">
                            @foreach($social['recent_posts'] as $post)
                            <div class="post-preview">
                                <div class="post-content">{{ Str::limit($post->content, 100) }}</div>
                                <div class="post-meta">
                                    <small class="text-muted">{{ $post->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="profile-actions-fixed">
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
            <i class="fas fa-download"></i> تصدير
        </button>
        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
            <li><a class="dropdown-item" href="#" onclick="printProfile()"><i class="fas fa-print"></i> طباعة</a></li>
            <li><a class="dropdown-item" href="#" onclick="exportPDF()"><i class="fas fa-file-pdf"></i> تصدير PDF</a></li>
        </ul>
    </div>
</div>

@endsection

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="{{ asset('js/employee-profile.js') }}"></script>

<script>
    // بيانات الرسوم البيانية
    const projectsData = @json($profileData['projects_tasks']['projects_by_status']);
    const tasksData = {
        regular: @json($profileData['projects_tasks']['regular_tasks_by_status']),
        additional: @json($profileData['projects_tasks']['additional_tasks_by_status']),
        template: @json($profileData['projects_tasks']['template_tasks_by_status'])
    };

    let projectsChart = null;
    let allTasksChart = null;
    let taskTypesDistributionChart = null;
    let taskCompletionChart = null;

    // تبديل مخطط المشاريع
    function toggleProjectsChart() {
        const statsView = document.getElementById('projects-stats-view');
        const chartView = document.getElementById('projects-chart-view');

        if (chartView.style.display === 'none') {
            statsView.style.display = 'none';
            chartView.style.display = 'block';
            initProjectsChart();
        } else {
            statsView.style.display = 'block';
            chartView.style.display = 'none';
        }
    }

    // تبديل مخطط المهام
    function toggleTasksChart() {
        const statsView = document.getElementById('tasks-stats-view');
        const chartView = document.getElementById('tasks-chart-view');

        if (chartView.style.display === 'none') {
            statsView.style.display = 'none';
            chartView.style.display = 'block';
            initAllTasksChart();
        } else {
            statsView.style.display = 'block';
            chartView.style.display = 'none';
        }
    }

    // تبديل عرض أنواع المهام
    function showTaskTypeView(viewType) {
        const cardsView = document.getElementById('task-type-cards-view');
        const chartView = document.getElementById('task-type-chart-view');
        const cardsBtn = document.getElementById('cards-btn');
        const chartBtn = document.getElementById('chart-btn');

        if (viewType === 'cards') {
            cardsView.style.display = 'block';
            chartView.style.display = 'none';
            cardsBtn.classList.add('active');
            chartBtn.classList.remove('active');
        } else {
            cardsView.style.display = 'none';
            chartView.style.display = 'block';
            cardsBtn.classList.remove('active');
            chartBtn.classList.add('active');
            initTaskTypeCharts();
        }
    }

    // إنشاء مخطط المشاريع
    function initProjectsChart() {
        if (projectsChart) {
            projectsChart.destroy();
        }

        const ctx = document.getElementById('projectsChart').getContext('2d');
        const labels = Object.keys(projectsData);
        const data = Object.values(projectsData);

        projectsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#007bff', '#28a745', '#17a2b8', '#dc3545'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // إنشاء مخطط جميع المهام
    function initAllTasksChart() {
        if (allTasksChart) {
            allTasksChart.destroy();
        }

        const ctx = document.getElementById('allTasksChart').getContext('2d');

        allTasksChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['المهام العادية', 'المهام الإضافية', 'مهام القوالب'],
                datasets: [{
                    data: [
                        tasksData.regular.total,
                        tasksData.additional.total,
                        tasksData.template.total
                    ],
                    backgroundColor: ['#007bff', '#28a745', '#17a2b8'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // إنشاء مخططات أنواع المهام
    function initTaskTypeCharts() {
        if (taskTypesDistributionChart) {
            taskTypesDistributionChart.destroy();
        }
        if (taskCompletionChart) {
            taskCompletionChart.destroy();
        }

        // مخطط توزيع أنواع المهام
        const ctx1 = document.getElementById('taskTypesDistributionChart').getContext('2d');
        taskTypesDistributionChart = new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['المهام العادية', 'المهام الإضافية', 'مهام القوالب'],
                datasets: [{
                    data: [
                        tasksData.regular.total,
                        tasksData.additional.total,
                        tasksData.template.total
                    ],
                    backgroundColor: ['#007bff', '#28a745', '#17a2b8'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // مخطط معدل الإنجاز
        const ctx2 = document.getElementById('taskCompletionChart').getContext('2d');
        taskCompletionChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['العادية', 'الإضافية', 'القوالب'],
                datasets: [{
                    label: 'مكتمل',
                    data: [
                        tasksData.regular['مكتمل'] || 0,
                        tasksData.additional.completed || 0,
                        tasksData.template.completed || 0
                    ],
                    backgroundColor: '#28a745'
                }, {
                    label: 'الإجمالي',
                    data: [
                        tasksData.regular.total,
                        tasksData.additional.total,
                        tasksData.template.total
                    ],
                    backgroundColor: '#e9ecef'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }
</script>
@endpush
