@extends('layouts.app')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="dashboard-header-content">
            <h1 class="text-3xl font-bold mb-2">مرحباً، {{ Auth::user()->name }}</h1>
            <p class="text-white/80">
                إحصائيات الفترة من {{ $startDate->format('d/m/Y') }} إلى {{ $endDate->format('d/m/Y') }}
            </p>
            <p class="text-white/60 text-sm">
                شهر {{ $attendanceStats['period']['month'] }} {{ $attendanceStats['period']['year'] }}
            </p>
            <div class="dashboard-date">
                <i class="fas fa-calendar-alt"></i>
                <span>{{ now()->format('Y/m/d') }}</span>
            </div>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check text-white text-xl"></i>
            </div>
            <div class="stat-value">{{ $attendanceStats['present_days'] }}</div>
            <div class="stat-label">أيام الحضور</div>
            <div class="stat-sublabel">من أصل {{ $attendanceStats['total_work_days'] }} يوم عمل</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-times text-white text-xl"></i>
            </div>
            <div class="stat-value">{{ $attendanceStats['absent_days'] }}</div>
            <div class="stat-label">أيام الاجازة</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock text-white text-xl"></i>
            </div>
            <div class="stat-value">{{ $attendanceStats['late_days'] }}</div>
            <div class="stat-label">مرات التأخير</div>
            <div class="stat-sublabel">{{ $attendanceStats['total_delay_minutes'] }} دقيقة</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle text-white text-xl"></i>
            </div>
            <div class="stat-value">{{ $attendanceStats['violation_days'] }}</div>
            <div class="stat-label">المخالفات</div>
        </div>
    </div>

    <div class="action-cards">
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-file-alt text-white text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">طلبات الوقت الإضافي</h3>
            <a href="{{ route('overtime-requests.index') }}" class="btn-dashboard">
                عرض طلبات الوقت الإضافي
            </a>
        </div>
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-user-clock text-white text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">طلبات الإذن</h3>
            <a href="{{ route('permission-requests.index') }}" class="btn-dashboard">
                إدارة الأذونات
            </a>
        </div>
    </div>

    <div class="action-cards">
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-user-minus text-white text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">طلبات الاجازة</h3>
            <a href="{{ route('absence-requests.index') }}" class="btn-dashboard">
                إدارة الاجازات
            </a>
        </div>
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-bell text-white text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">الإشعارات</h3>
            <a href="{{ route('notifications') }}" class="btn-dashboard">
                عرض الإشعارات
            </a>
        </div>
    </div>

    <div class="action-cards">
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-file-pdf text-white text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">تقرير الحضور</h3>
            @if(Auth::user()->employee_id)
            <a href="{{ route('user.previewAttendance', Auth::user()->employee_id) }}" class="btn-dashboard">
                عرض التقرير
            </a>
            @else
            <div class="alert alert-warning">
                لا يوجد رقم موظف مسجل
            </div>
            @endif
        </div>
        <!-- <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-envelope text-white text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">Chat</h3>
            <a href="{{ route('chat.index') }}" class="btn-dashboard">
                Open Chat
            </a>
        </div> -->
    </div>

    <div class="action-cards">
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-chart-line text-white text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">إحصائيات الموظف</h3>
            <a href="{{ route('employee-statistics.index') }}" class="btn-dashboard">
                عرض الإحصائيات
            </a>
        </div>
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-trophy text-white text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">المسابقة</h3>
            <a href="{{ route('employee-competition.index') }}" class="btn-dashboard">
                تابع المسابقه
            </a>
        </div>
    </div>


    @if($salaryFiles->count() > 0)
    <div>

    </div>
    <div class="container mt-5 ">
        <h3 class="text-center mb-4 ">كشوف الرواتب الخاصة بك</h3>
        <div class="row ">
            @foreach($salaryFiles as $file)

            <div class="col-md-4 mb-4">
                <div class="card shadow-lg h-100 border-0">
                    <div class="card-body text-center position-relative">
                        <div class="salary-icon bg-gradient-primary text-white mb-3 d-inline-flex align-items-center justify-content-center">
                            <i class="fas fa-file-invoice-dollar fa-2x"></i>
                        </div>
                        <h5 class="card-title text-dark mb-3">{{ $file->month }}</h5>
                        <a href="{{ url('/salary-sheet/' . $file->employee_id . '/' . $file->month . '/' . basename($file->file_path)) }}"
                            class="btn btn-primary btn-sm px-4 rounded-pill" target="_blank">
                            عرض كشف الراتب
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="container mt-5">
        <div class="alert alert-info text-center" role="alert">
            <p class="mb-0">لا توجد كشوف رواتب متاحة حالياً. يرجى التحقق مرة أخرى لاحقاً!</p>
        </div>
    </div>
    @endif

    @endsection

    @push('styles')
        <link rel="stylesheet" href="{{asset('css/dashboard-user.css')}}">
    @endpush
