@extends('layouts.app')

@section('content')

<head>
    <link rel="stylesheet" href="{{asset('css/dashboard-hr.css')}}">
</head>



<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="dashboard-header-content">
            <h1 class="text-3xl font-bold text-white mb-2 w-full">مرحباً، {{ Auth::user()->name }}</h1>
            <p class="text-white/80">
                @if(Auth::user()->hasRole('hr'))
                إدارة حضور وأداء الفريق
                @else
                متابعة الحضور والانصراف
                @endif
            </p>
        </div>
    </div>

    <div class="dashboard-cards-container">
        @if(Auth::user()->hasRole('hr'))
        <!-- صف 1: إحصائيات اليوم للمدير و طلبات اليوم -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon bg-primary">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">إحصائيات اليوم</h3>
                    <p class="text-gray-600 mb-4">عرض إحصائيات الحضور اليومية</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-details">
                                    <h3>{{ $todayStats['totalEmployees'] }}</h3>
                                    <p>إجمالي الموظفين</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-details">
                                    <h3>{{ $todayStats['presentToday'] }}</h3>
                                    <p>الحضور اليوم</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #28a745;">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-details">
                                    <h3>{{ $todayStats['presentOnTimeToday'] ?? 0 }}</h3>
                                    <p>الحضور في الوقت</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: #ffc107;">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                                <div class="stat-details">
                                    <h3>{{ $todayStats['presentLateToday'] ?? 0 }}</h3>
                                    <p>الحضور المتأخر</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon" style="background-color: #FF9500;">
                        <i class="fas fa-clipboard-list text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">طلبات اليوم</h3>
                    <p class="text-gray-600 mb-4">عرض جميع الطلبات المقدمة اليوم</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <div class="stat-value">{{ $todayRequests['absenceRequests'] }}</div>
                                <div class="stat-label">طلبات الاجازة</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <div class="stat-value">{{ $todayRequests['permissionRequests'] }}</div>
                                <div class="stat-label">طلبات الاستئذان</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-business-time"></i>
                                </div>
                                <div class="stat-value">{{ $todayRequests['overtimeRequests'] }}</div>
                                <div class="stat-label">طلبات العمل الإضافي</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="stat-value">{{ $todayRequests['violations'] }}</div>
                                <div class="stat-label">المخالفات</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- صف جديد: طلبات الأكل -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon" style="background-color: #FF6B35;">
                        <i class="fas fa-utensils text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">إدارة طلبات الأكل</h3>
                    <p class="text-gray-600 mb-4">إدارة وتسجيل طلبات الأكل للموظفين</p>
                    <div class="row g-2">
                        <div class="col-12">
                            <a href="{{ route('food-allowances.index') }}" class="btn-dashboard d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-utensils me-2"></i>
                                إدارة طلبات الأكل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- صف 2: إحصائيات الشهر والإجراءات السريعة -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon" style="background-color: #4CAF50;">
                        <i class="fas fa-calendar-check text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">إحصائياتي الشهرية</h3>
                    <p class="text-gray-600 mb-4">ملخص أداء الحضور والانصراف الشهري</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-check text-white text-xl"></i>
                                </div>
                                <div class="stat-value">{{ $attendanceStats['present_days'] }}</div>
                                <div class="stat-label">أيام الحضور</div>
                                <div class="stat-sublabel">من أصل {{ $attendanceStats['total_work_days'] }} يوم</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-times text-white text-xl"></i>
                                </div>
                                <div class="stat-value">{{ $attendanceStats['absent_days'] }}</div>
                                <div class="stat-label">أيام الغياب</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-clock text-white text-xl"></i>
                                </div>
                                <div class="stat-value">{{ $attendanceStats['late_days'] }}</div>
                                <div class="stat-label">مرات التأخير</div>
                                <div class="stat-sublabel">{{ $attendanceStats['total_delay_minutes'] }} دقيقة</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                                </div>
                                <div class="stat-value">{{ $attendanceStats['violation_days'] }}</div>
                                <div class="stat-label">المخالفات</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon" style="background-color: #3F51B5;">
                        <i class="fas fa-tasks text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">الإجراءات السريعة</h3>
                    <p class="text-gray-600 mb-4">إجراءات سريعة للوصول إلى الوظائف المتكررة</p>
                    <div class="row g-2">
                        @if(Auth::user()->hasRole('hr'))
                        <div class="col-6">
                            <a href="{{ route('users.index') }}" class="btn-dashboard">
                                <i class="fas fa-users"></i>
                                إدارة الموظفين
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('salary-sheets.index') }}" class="btn-dashboard">
                                <i class="fas fa-file-invoice-dollar"></i>
                                كشوف المرتبات
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('work-shifts.index') }}" class="btn-dashboard">
                                <i class="fas fa-clock"></i>
                                إدارة الورديات
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('users.assign-work-shifts') }}" class="btn-dashboard">
                                <i class="fas fa-user-clock"></i>
                                تعيين الورديات
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('admin.notifications.index') }}" class="btn-dashboard">
                                <i class="fas fa-gavel"></i>
                                القرارات الإدارية
                            </a>
                        </div>
                        @endif

                        @if(Auth::user()->role === 'employee')
                        <div class="col-6">
                            <a href="{{ route('attendances.create') }}" class="btn-dashboard">
                                <i class="fas fa-clock"></i>
                                تسجيل حضور
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('notifications.unread') }}" class="btn-dashboard">
                                <i class="fas fa-gavel"></i>
                                القرارات الإدارية
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- صف 3: الطلبات المعلقة والتقارير -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon" style="background-color: #E91E63;">
                        <i class="fas fa-bell text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">الطلبات المعلقة</h3>
                    <p class="text-gray-600 mb-4">عرض الطلبات التي تنتظر الموافقة</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('overtime-requests.index') }}" class="btn-dashboard">
                                <i class="fas fa-clock"></i>
                                طلبات العمل الإضافي
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('absence-requests.index') }}" class="btn-dashboard">
                                <i class="fas fa-user-clock"></i>
                                طلبات الاجازة
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('permission-requests.index') }}" class="btn-dashboard">
                                <i class="fas fa-door-open"></i>
                                طلبات الاستئذان
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-file-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">التقارير والمستندات</h3>
                    <p class="text-gray-600 mb-4">الوصول وإدارة المستندات المهمة</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="/attendance" class="btn-dashboard">سجلات الحضور</a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('salary-sheets.index') }}" class="btn-dashboard">كشوف المرتبات</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- صف 4: إحصائيات الموظفين وأعياد الميلاد -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon bg-success">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">إحصائيات الموظفين</h3>
                    <p class="text-gray-600 mb-4">عرض تقارير وإحصائيات مفصلة عن أداء الموظفين</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('employee-statistics.index') }}" class="btn-dashboard">
                                <i class="fas fa-chart-line"></i>
                                عرض الإحصائيات
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('employee-competition.index') }}" class="btn-dashboard">
                                <i class="fas fa-trophy"></i>
                                المسابقة
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon" style="background-color: #FF69B4;">
                        <i class="fas fa-birthday-cake text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">أعياد الميلاد</h3>
                    <p class="text-gray-600 mb-4">عرض وإدارة أعياد ميلاد الموظفين</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('employee-birthdays.index') }}" class="btn-dashboard">
                                <i class="fas fa-calendar-day"></i>
                                عرض أعياد الميلاد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- صف 5: الحالات الخاصة وسجلات التدقيق -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon" style="background-color: #9370DB;">
                        <i class="fas fa-user-shield text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">الحالات الخاصة</h3>
                    <p class="text-gray-600 mb-4">عرض وإدارة الحالات الخاصة للموظفين</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('special-cases.index') }}" class="btn-dashboard">
                                <i class="fas fa-users-cog"></i>
                                إدارة الحالات الخاصة
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('special-cases.create') }}" class="btn-dashboard">
                                <i class="fas fa-plus-circle"></i>
                                إضافة حالة خاصة
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="action-card">
                    <div class="action-icon" style="background-color: #2F4F4F;">
                        <i class="fas fa-history text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">سجلات التدقيق</h3>
                    <p class="text-gray-600 mb-4">عرض سجلات التدقيق والتغييرات في النظام</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('audit-log.index') }}" class="btn-dashboard">
                                <i class="fas fa-clipboard-list"></i>
                                سجلات النظام
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="#" class="btn-dashboard dropdown-toggle" id="auditsDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-file-alt"></i>
                                سجلات الطلبات
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="auditsDropdown">
                                <li><a class="dropdown-item" href="{{ route('audit-log.index', ['request_type' => 'absence']) }}">طلبات الاجازة</a></li>
                                <li><a class="dropdown-item" href="{{ route('audit-log.index', ['request_type' => 'permission']) }}">طلبات الاستئذان</a></li>
                                <li><a class="dropdown-item" href="{{ route('audit-log.index', ['request_type' => 'overtime']) }}">طلبات العمل الإضافي</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<!-- CSS moved to dashboard-user.css -->
@endpush
@endsection