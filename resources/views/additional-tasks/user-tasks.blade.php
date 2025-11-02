@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/additional-tasks.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="additional-tasks-container" style="width: 100%; padding: 0 2rem;">
    <div class="modern-card">
        <!-- Header -->
        <div class="modern-header">
            <div class="modern-header-content">
                <div class="flex justify-between items-center">
                    <div>
                        <h1>🌟 المهام الإضافية</h1>
                        <p>
                            <i class="fas fa-gift"></i> مهام إضافية اختيارية للحصول على نقاط إضافية
                        </p>
                    </div>
                    <div class="badge-modern badge-info">
                        <i class="fas fa-star"></i>
                        تقدم، أكمل، واحصل على نقاط!
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div style="padding: 2rem;">
            @if(session('success'))
            <div class="alert-modern alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="alert-modern alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            @if(session('info'))
            <div class="alert-modern alert-info">
                <i class="fas fa-info-circle"></i>
                <span>{{ session('info') }}</span>
            </div>
            @endif

            <!-- Tabs -->
            <div class="tabs-container">
                <button onclick="showTab('available')" id="available-tab" class="tab-button active">
                    <i class="fas fa-gift"></i> المهام المتاحة
                    @if($availableTasks->count() > 0)
                    <span class="tab-count">{{ $availableTasks->count() }}</span>
                    @endif
                </button>
                <button onclick="showTab('accepted')" id="accepted-tab" class="tab-button">
                    <i class="fas fa-check-circle"></i> المقبولة
                    @if($acceptedTasks->count() > 0)
                    <span class="tab-count">{{ $acceptedTasks->count() }}</span>
                    @endif
                </button>
                <button onclick="showTab('pending')" id="pending-tab" class="tab-button">
                    <i class="fas fa-clock"></i> في الانتظار
                    @if($pendingTasks->count() > 0)
                    <span class="tab-count">{{ $pendingTasks->count() }}</span>
                    @endif
                </button>
                <button onclick="showTab('rejected')" id="rejected-tab" class="tab-button">
                    <i class="fas fa-times-circle"></i> المرفوضة
                    @if($rejectedTasks->count() > 0)
                    <span class="tab-count">{{ $rejectedTasks->count() }}</span>
                    @endif
                </button>
            </div>

            <!-- Available Tasks Tab -->
            <div id="available-content" class="tab-content">
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-xl font-semibold text-neutral-900 mb-2">
                                <i class="fas fa-gift text-primary-500"></i> المهام المتاحة للتقديم
                            </h3>
                            <p class="text-neutral-600 text-sm">مهام إضافية اختيارية يمكنك التقديم عليها لكسب نقاط إضافية</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="toggleView('cards', 'available')" id="available-cards-btn"
                                class="btn-modern btn-primary btn-sm">
                                <i class="fas fa-th-large"></i> كاردات
                            </button>
                            <button onclick="toggleView('table', 'available')" id="available-table-btn"
                                class="btn-modern btn-secondary btn-sm">
                                <i class="fas fa-table"></i> جدول
                            </button>
                        </div>
                    </div>

                    <!-- معلومات المهام -->
                    <div class="alert-modern alert-success">
                        <i class="fas fa-star"></i>
                        <div>
                            <h4 class="font-semibold mb-2">ما هي المهام الإضافية؟</h4>
                            <div class="text-sm">
                                <p>• <strong>اختيارية:</strong> غير إجبارية، يمكنك اختيار ما يناسبك</p>
                                <p>• <strong>نقاط إضافية:</strong> كل مهمة تعطي نقاط تُضاف لرصيدك</p>
                                <p>• <strong>شارات:</strong> النقاط تساعد في ترقية شارتك</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($availableTasks->count() > 0)
                <!-- Cards View -->
                <div id="available-cards-view" class="grid grid-cols-1 gap-6">
                    @foreach($availableTasks as $task)
                    <div class="task-card">
                        <div class="flex items-start justify-between mb-5">
                            <div class="flex items-center gap-4 flex-1">
                                @if($task->icon)
                                @php
                                $iconColor = $task->color_code ?? '#667eea';
                                $iconColorDark = $task->color_code ? $task->color_code . 'dd' : '#764ba2';
                                @endphp
                                <div class="task-icon" style="background: linear-gradient(135deg, {{ $iconColor }} 0%, {{ $iconColorDark }} 100%);">
                                    <i class="{{ $task->icon }}"></i>
                                </div>
                                @else
                                <div class="task-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="task-title">{{ $task->title }}</h4>
                                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                                        <span class="badge-modern badge-warning" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">
                                            <i class="fas fa-hand-paper"></i> يتطلب تقديم
                                        </span>
                                        @if($task->creator)
                                        <span class="text-xs text-primary-600 font-medium">
                                            <i class="fas fa-user-circle"></i> {{ $task->creator->name }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($task->description)
                        <div class="mb-5 p-4 bg-neutral-50 rounded-lg border border-neutral-200">
                            <p class="task-description mb-0">{{ $task->description }}</p>
                        </div>
                        @endif

                        <div class="grid grid-cols-3 gap-4 mb-5">
                            <div class="text-center p-4 stat-box stat-box-green">
                                <div class="text-xs text-neutral-600 mb-2 font-medium">
                                    <i class="fas fa-star" style="color: #f59e0b;"></i> النقاط
                                </div>
                                <div class="text-2xl font-bold text-success-600">{{ number_format($task->points) }}</div>
                            </div>
                            <div class="text-center p-4 stat-box stat-box-blue">
                                <div class="text-xs text-neutral-600 mb-2 font-medium">
                                    <i class="fas fa-clock" style="color: #3b82f6;"></i> الوقت المتبقي
                                </div>
                                @if($task->isExpired())
                                <span class="badge-modern badge-error text-sm">انتهت</span>
                                @else
                                @php $hoursRemaining = $task->timeRemainingInHours(); @endphp
                                @if($hoursRemaining > 24)
                                <span class="badge-modern badge-success text-sm">{{ round($hoursRemaining / 24, 1) }} يوم</span>
                                @elseif($hoursRemaining > 1)
                                <span class="badge-modern badge-warning text-sm">{{ round($hoursRemaining, 2) }} ساعة</span>
                                @else
                                <span class="badge-modern badge-error text-sm">أقل من ساعة</span>
                                @endif
                                @endif
                            </div>
                            @if($task->assignment_type === 'application_required' && $task->max_participants)
                            <div class="text-center p-4 stat-box stat-box-purple">
                                <div class="text-xs text-neutral-600 mb-2 font-medium">
                                    <i class="fas fa-users" style="color: #8b5cf6;"></i> المشاركين
                                </div>
                                <div class="text-lg font-bold" style="color: #8b5cf6;">
                                    {{ $task->getApprovedParticipantsCount() }}/{{ $task->max_participants }}
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-neutral-200">
                            <button onclick="showApplyModal({{ $task->id }}, '{{ $task->title }}')"
                                class="btn-modern btn-warning flex-1" style="padding: 0.875rem 1.5rem;">
                                <i class="fas fa-hand-paper"></i> تقديم طلب
                            </button>
                            <a href="{{ route('additional-tasks.show', $task) }}" class="btn-modern btn-primary" style="padding: 0.875rem 1.5rem;">
                                <i class="fas fa-eye"></i> التفاصيل
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Table View -->
                <div id="available-table-view" class="hidden">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>المهمة</th>
                                <th>النقاط</th>
                                <th>الوقت المتبقي</th>
                                <th>النوع</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($availableTasks as $task)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($task->icon)
                                        <div class="task-icon" style="background-color: {{ $task->color_code ?? '#3b82f6' }}; width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="{{ $task->icon }}"></i>
                                        </div>
                                        @else
                                        <div class="task-icon" style="width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="fas fa-tasks"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <div class="font-semibold text-neutral-900">{{ $task->title }}</div>
                                            @if($task->description)
                                            <div class="text-sm text-neutral-500">{{ \Illuminate\Support\Str::limit($task->description, 50) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold text-success-600">{{ number_format($task->points) }}</div>
                                </td>
                                <td>
                                    @if($task->isExpired())
                                    <span class="badge-modern badge-error">انتهت</span>
                                    @else
                                    @php $hoursRemaining = $task->timeRemainingInHours(); @endphp
                                    @if($hoursRemaining > 24)
                                    <span class="badge-modern badge-success">{{ round($hoursRemaining / 24, 1) }} يوم</span>
                                    @elseif($hoursRemaining > 1)
                                    <span class="badge-modern badge-warning">{{ round($hoursRemaining, 2) }} ساعة</span>
                                    @else
                                    <span class="badge-modern badge-error">أقل من ساعة</span>
                                    @endif
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-modern badge-warning">
                                        <i class="fas fa-hand-paper"></i> يتطلب تقديم
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button onclick="showApplyModal({{ $task->id }}, '{{ $task->title }}')"
                                            class="btn-modern btn-warning btn-sm">
                                            <i class="fas fa-hand-paper"></i> تقديم
                                        </button>
                                        <a href="{{ route('additional-tasks.show', $task) }}" class="btn-modern btn-ghost btn-sm">
                                            <i class="fas fa-eye"></i> عرض
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <h3 class="empty-state-title">لا توجد مهام متاحة</h3>
                    <p class="empty-state-description">لا توجد مهام إضافية متاحة للتقديم في الوقت الحالي. تحقق لاحقاً!</p>
                </div>
                @endif
            </div>

            <!-- Accepted Tasks Tab -->
            <div id="accepted-content" class="tab-content hidden">
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-xl font-semibold text-neutral-900 mb-2">
                                <i class="fas fa-check-circle text-success-600"></i> المهام المقبولة
                            </h3>
                            <p class="text-neutral-600 text-sm">المهام التي تم قبولك فيها ومُخصصة لك</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="toggleView('cards', 'accepted')" id="accepted-cards-btn"
                                class="btn-modern btn-primary btn-sm">
                                <i class="fas fa-th-large"></i> كاردات
                            </button>
                            <button onclick="toggleView('table', 'accepted')" id="accepted-table-btn"
                                class="btn-modern btn-secondary btn-sm">
                                <i class="fas fa-table"></i> جدول
                            </button>
                        </div>
                    </div>
                </div>

                @if($acceptedTasks->count() > 0)
                <!-- Cards View -->
                <div id="accepted-cards-view" class="grid grid-cols-1 gap-4">
                    @foreach($acceptedTasks as $taskUser)
                    @php $task = $taskUser->additionalTask; @endphp
                    <div class="task-card" style="background: linear-gradient(135deg, #f0fdf4 0%, #f0fff4 100%); border-color: #10b981;">
                        <div class="flex items-start justify-between flex-wrap gap-4">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                @if($task->icon)
                                @php
                                $iconColor = $task->color_code ?? '#10b981';
                                $iconColorDark = $task->color_code ? $task->color_code . 'dd' : '#059669';
                                @endphp
                                <div class="task-icon" style="background: linear-gradient(135deg, {{ $iconColor }} 0%, {{ $iconColorDark }} 100%);">
                                    <i class="{{ $task->icon }}"></i>
                                </div>
                                @else
                                <div class="task-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-3">
                                        <h4 class="text-xl font-bold text-neutral-900 mb-0">{{ $task->title }}</h4>
                                        <span class="badge-modern badge-success" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">
                                            <i class="fas fa-check-circle"></i> مقبولة
                                        </span>
                                    </div>
                                    @if($task->description)
                                    <p class="text-neutral-600 mb-4 p-3 bg-white rounded-lg border border-neutral-200">{{ $task->description }}</p>
                                    @endif

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="p-3 bg-white rounded-lg border border-green-200">
                                            <div class="text-xs text-neutral-600 mb-1">
                                                <i class="fas fa-star text-yellow-500"></i> النقاط
                                            </div>
                                            <div class="text-xl font-bold text-success-600">{{ number_format($task->points) }}</div>
                                        </div>
                                        <div class="p-3 bg-white rounded-lg border border-blue-200">
                                            <div class="text-xs text-neutral-600 mb-1">
                                                <i class="fas fa-clock text-blue-500"></i> الوقت المتبقي
                                            </div>
                                            @if($task->isExpired())
                                            <span class="badge-modern badge-error text-sm">انتهت</span>
                                            @else
                                            @php $hoursRemaining = $task->timeRemainingInHours(); @endphp
                                            @if($hoursRemaining > 24)
                                            <span class="text-success-600 font-bold">{{ round($hoursRemaining / 24, 1) }} يوم</span>
                                            @elseif($hoursRemaining > 1)
                                            <span class="text-warning-600 font-bold">{{ round($hoursRemaining, 2) }} ساعة</span>
                                            @else
                                            <span class="text-error-500 font-bold">أقل من ساعة</span>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('additional-tasks.show', $task) }}" class="btn-modern btn-primary" style="padding: 0.875rem 1.5rem;">
                                <i class="fas fa-eye"></i> التفاصيل
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Table View -->
                <div id="accepted-table-view" class="hidden">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>المهمة</th>
                                <th>النقاط</th>
                                <th>الوقت المتبقي</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($acceptedTasks as $taskUser)
                            @php $task = $taskUser->additionalTask; @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($task->icon)
                                        <div class="task-icon" style="background-color: {{ $task->color_code ?? '#10b981' }}; width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="{{ $task->icon }}"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <div class="font-semibold text-neutral-900">{{ $task->title }}</div>
                                            @if($task->description)
                                            <div class="text-sm text-neutral-500">{{ \Illuminate\Support\Str::limit($task->description, 50) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold text-success-600">{{ number_format($task->points) }}</div>
                                </td>
                                <td>
                                    @if($task->isExpired())
                                    <span class="badge-modern badge-error">انتهت</span>
                                    @else
                                    @php $hoursRemaining = $task->timeRemainingInHours(); @endphp
                                    @if($hoursRemaining > 24)
                                    <span class="badge-modern badge-success">{{ round($hoursRemaining / 24, 1) }} يوم</span>
                                    @elseif($hoursRemaining > 1)
                                    <span class="badge-modern badge-warning">{{ round($hoursRemaining, 2) }} ساعة</span>
                                    @else
                                    <span class="badge-modern badge-error">أقل من ساعة</span>
                                    @endif
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-modern badge-success">
                                        <i class="fas fa-check-circle"></i> مقبولة
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('additional-tasks.show', $task) }}" class="btn-modern btn-ghost btn-sm">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
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
                    <h3 class="empty-state-title">لا توجد مهام مقبولة</h3>
                    <p class="empty-state-description">قدم على المهام المتاحة للحصول على نقاط إضافية</p>
                </div>
                @endif
            </div>

            <!-- Pending Tasks Tab -->
            <div id="pending-content" class="tab-content hidden">
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-xl font-semibold text-neutral-900 mb-2">
                                <i class="fas fa-clock" style="color: var(--warning);"></i> في انتظار الموافقة
                            </h3>
                            <p class="text-neutral-600 text-sm">الطلبات التي قدمتها وفي انتظار موافقة الإدارة</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="toggleView('cards', 'pending')" id="pending-cards-btn"
                                class="btn-modern btn-primary btn-sm">
                                <i class="fas fa-th-large"></i> كاردات
                            </button>
                            <button onclick="toggleView('table', 'pending')" id="pending-table-btn"
                                class="btn-modern btn-secondary btn-sm">
                                <i class="fas fa-table"></i> جدول
                            </button>
                        </div>
                    </div>
                </div>

                @if($pendingTasks->count() > 0)
                <!-- Cards View -->
                <div id="pending-cards-view" class="grid grid-cols-1 gap-4">
                    @foreach($pendingTasks as $taskUser)
                    @php $task = $taskUser->additionalTask; @endphp
                    <div class="task-card" style="background: linear-gradient(135deg, #fff9f0 0%, #fffbf5 100%); border-color: #f59e0b;">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                @if($task->icon)
                                @php
                                $iconColor = $task->color_code ?? '#f59e0b';
                                $iconColorDark = $task->color_code ? $task->color_code . 'dd' : '#e0a800';
                                @endphp
                                <div class="task-icon" style="background: linear-gradient(135deg, {{ $iconColor }} 0%, {{ $iconColorDark }} 100%);">
                                    <i class="{{ $task->icon }}"></i>
                                </div>
                                @else
                                <div class="task-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #e0a800 100%);">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xl font-bold text-neutral-900 mb-3">{{ $task->title }}</h4>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 text-sm text-neutral-700">
                                            <i class="fas fa-calendar text-warning-600"></i>
                                            <span class="font-medium">تقدمت في: {{ $taskUser->applied_at->format('Y-m-d H:i') }}</span>
                                        </div>
                                        @if($taskUser->user_notes)
                                        <div class="p-3 bg-white rounded-lg border border-neutral-200">
                                            <div class="flex items-start gap-2 text-sm">
                                                <i class="fas fa-comment text-primary-600 mt-1"></i>
                                                <div>
                                                    <div class="font-medium text-neutral-700 mb-1">ملاحظاتك:</div>
                                                    <div class="text-neutral-600">{{ $taskUser->user_notes }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-3">
                                <span class="badge-modern badge-warning" style="padding: 0.625rem 1.25rem; font-size: 0.9rem;">
                                    <i class="fas fa-clock"></i> في الانتظار
                                </span>
                                <div class="text-center p-3 stat-box stat-box-green">
                                    <div class="text-xs text-neutral-600 mb-1">النقاط</div>
                                    <div class="text-xl font-bold text-success-600">{{ number_format($task->points) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Table View -->
                <div id="pending-table-view" class="hidden">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>المهمة</th>
                                <th>النقاط</th>
                                <th>تاريخ التقديم</th>
                                <th>ملاحظاتك</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingTasks as $taskUser)
                            @php $task = $taskUser->additionalTask; @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($task->icon)
                                        <div class="task-icon" style="background-color: {{ $task->color_code ?? '#f59e0b' }}; width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="{{ $task->icon }}"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <div class="font-semibold text-neutral-900">{{ $task->title }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold text-success-600">{{ number_format($task->points) }}</div>
                                </td>
                                <td>
                                    <div class="text-sm text-neutral-900">{{ $taskUser->applied_at->format('Y-m-d H:i') }}</div>
                                    <div class="text-xs text-neutral-500">{{ $taskUser->applied_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    @if($taskUser->user_notes)
                                    <div class="text-sm text-neutral-600">{{ \Illuminate\Support\Str::limit($taskUser->user_notes, 50) }}</div>
                                    @else
                                    <div class="text-sm text-neutral-500">لا توجد ملاحظات</div>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('additional-tasks.show', $task) }}" class="btn-modern btn-ghost btn-sm">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="empty-state-title">لا توجد طلبات في الانتظار</h3>
                    <p class="empty-state-description">لم تقدم على أي مهام تتطلب موافقة</p>
                </div>
                @endif
            </div>

            <!-- Rejected Tasks Tab -->
            <div id="rejected-content" class="tab-content hidden">
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-xl font-semibold text-neutral-900 mb-2">
                                <i class="fas fa-times-circle text-error-500"></i> المهام المرفوضة
                            </h3>
                            <p class="text-neutral-600 text-sm">الطلبات التي تم رفضها من قبل الإدارة</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="toggleView('cards', 'rejected')" id="rejected-cards-btn"
                                class="btn-modern btn-primary btn-sm">
                                <i class="fas fa-th-large"></i> كاردات
                            </button>
                            <button onclick="toggleView('table', 'rejected')" id="rejected-table-btn"
                                class="btn-modern btn-secondary btn-sm">
                                <i class="fas fa-table"></i> جدول
                            </button>
                        </div>
                    </div>
                </div>

                @if($rejectedTasks->count() > 0)
                <!-- Cards View -->
                <div id="rejected-cards-view" class="grid grid-cols-1 gap-4">
                    @foreach($rejectedTasks as $taskUser)
                    @php $task = $taskUser->additionalTask; @endphp
                    <div class="task-card" style="background: linear-gradient(135deg, #fff5f5 0%, #ffeaea 100%); border-color: #ef4444;">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                @if($task->icon)
                                @php
                                $iconColor = $task->color_code ?? '#ef4444';
                                $iconColorDark = $task->color_code ? $task->color_code . 'dd' : '#dc2626';
                                @endphp
                                <div class="task-icon" style="background: linear-gradient(135deg, {{ $iconColor }} 0%, {{ $iconColorDark }} 100%);">
                                    <i class="{{ $task->icon }}"></i>
                                </div>
                                @else
                                <div class="task-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-3">
                                        <h4 class="text-xl font-bold text-neutral-900 mb-0">{{ $task->title }}</h4>
                                        <span class="badge-modern badge-error" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">
                                            <i class="fas fa-times"></i> مرفوض
                                        </span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 text-sm text-neutral-700">
                                            <i class="fas fa-calendar-times text-error-500"></i>
                                            <span class="font-medium">تم الرفض في: {{ $taskUser->updated_at->format('Y-m-d H:i') }}</span>
                                        </div>
                                        @if($taskUser->admin_notes)
                                        <div class="p-3 bg-white rounded-lg border border-red-200">
                                            <div class="flex items-start gap-2 text-sm">
                                                <i class="fas fa-comment-alt text-error-500 mt-1"></i>
                                                <div>
                                                    <div class="font-medium text-error-600 mb-1">سبب الرفض:</div>
                                                    <div class="text-neutral-700">{{ $taskUser->admin_notes }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Table View -->
                <div id="rejected-table-view" class="hidden">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>المهمة</th>
                                <th>تاريخ الرفض</th>
                                <th>سبب الرفض</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rejectedTasks as $taskUser)
                            @php $task = $taskUser->additionalTask; @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($task->icon)
                                        <div class="task-icon" style="background-color: {{ $task->color_code ?? '#ef4444' }}; width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="{{ $task->icon }}"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <div class="font-semibold text-neutral-900">{{ $task->title }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm text-neutral-900">{{ $taskUser->updated_at->format('Y-m-d H:i') }}</div>
                                    <div class="text-xs text-neutral-500">{{ $taskUser->updated_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    @if($taskUser->admin_notes)
                                    <div class="text-sm text-error-500">{{ \Illuminate\Support\Str::limit($taskUser->admin_notes, 50) }}</div>
                                    @else
                                    <div class="text-sm text-neutral-500">لا يوجد سبب محدد</div>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('additional-tasks.show', $task) }}" class="btn-modern btn-ghost btn-sm">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h3 class="empty-state-title">لا توجد مهام مرفوضة</h3>
                    <p class="empty-state-description">لم يتم رفض أي من طلباتك</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Apply Modal -->
<div id="applyModal" class="modal-overlay">
    <div class="modal-content">
        <button type="button" onclick="hideApplyModal()" class="modal-close">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <i class="fas fa-hand-paper" style="color: var(--warning);"></i>
            <h3 class="modal-title">تقديم طلب للمشاركة</h3>
        </div>
        <form action="#" method="POST" id="applyForm">
            @csrf

            <div class="form-group">
                <label class="form-label">المهمة</label>
                <div id="apply_task_title" class="p-3 bg-neutral-100 rounded-lg font-medium"></div>
            </div>

            <div class="form-group">
                <label for="user_notes" class="form-label">ملاحظاتك (اختياري)</label>
                <textarea id="user_notes" name="user_notes" rows="3"
                    class="form-input form-textarea"
                    placeholder="اشرح لماذا تريد المشاركة في هذه المهمة"></textarea>
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" onclick="hideApplyModal()" class="btn-modern btn-secondary">
                    إلغاء
                </button>
                <button type="submit" class="btn-modern btn-warning">
                    <i class="fas fa-paper-plane"></i> تقديم الطلب
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab functionality
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(function(content) {
            content.classList.add('hidden');
        });

        // Remove active class from all tab buttons
        document.querySelectorAll('.tab-button').forEach(function(tab) {
            tab.classList.remove('active');
        });

        // Show selected tab content
        document.getElementById(tabName + '-content').classList.remove('hidden');

        // Add active class to selected tab
        document.getElementById(tabName + '-tab').classList.add('active');
    }

    // View toggle functionality
    function toggleView(viewType, tabName) {
        const cardsView = document.getElementById(tabName + '-cards-view');
        const tableView = document.getElementById(tabName + '-table-view');
        const cardsBtn = document.getElementById(tabName + '-cards-btn');
        const tableBtn = document.getElementById(tabName + '-table-btn');

        if (viewType === 'cards') {
            if (cardsView) cardsView.classList.remove('hidden');
            if (tableView) tableView.classList.add('hidden');
            if (cardsBtn) {
                cardsBtn.classList.remove('btn-secondary');
                cardsBtn.classList.add('btn-primary');
            }
            if (tableBtn) {
                tableBtn.classList.remove('btn-primary');
                tableBtn.classList.add('btn-secondary');
            }
            localStorage.setItem('userTasksView_' + tabName, 'cards');
        } else {
            if (cardsView) cardsView.classList.add('hidden');
            if (tableView) tableView.classList.remove('hidden');
            if (cardsBtn) {
                cardsBtn.classList.remove('btn-primary');
                cardsBtn.classList.add('btn-secondary');
            }
            if (tableBtn) {
                tableBtn.classList.remove('btn-secondary');
                tableBtn.classList.add('btn-primary');
            }
            localStorage.setItem('userTasksView_' + tabName, 'table');
        }
    }

    // Apply modal
    function showApplyModal(taskId, taskTitle) {
        document.getElementById('apply_task_title').textContent = taskTitle;
        document.getElementById('applyForm').action = `/additional-tasks/${taskId}/apply`;
        document.getElementById('applyModal').classList.add('active');
    }

    function hideApplyModal() {
        document.getElementById('applyModal').classList.remove('active');
        document.getElementById('user_notes').value = '';
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('applyModal');
        if (event.target === modal) {
            hideApplyModal();
        }
    });

    // Initialize views from localStorage
    function initializeViews() {
        const tabs = ['available', 'accepted', 'pending', 'rejected'];
        tabs.forEach(tabName => {
            const savedView = localStorage.getItem('userTasksView_' + tabName) || 'cards';
            toggleView(savedView, tabName);
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        showTab('available');
        initializeViews();
    });
</script>
@endsection