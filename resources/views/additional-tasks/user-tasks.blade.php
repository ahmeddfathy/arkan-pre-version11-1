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
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        @if($task->icon)
                                            <div class="task-icon" style="background-color: {{ $task->color_code ?? '#3b82f6' }};">
                                                <i class="{{ $task->icon }}"></i>
                                            </div>
                                        @else
                                            <div class="task-icon">
                                                <i class="fas fa-tasks"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h4 class="task-title">{{ $task->title }}</h4>
                                            <div class="text-sm text-neutral-500 mb-1">
                                                <i class="fas fa-hand-paper" style="color: var(--warning);"></i> يتطلب تقديم
                                            </div>
                                            @if($task->creator)
                                                <div class="text-xs text-primary-600">
                                                    <i class="fas fa-user"></i> بواسطة: {{ $task->creator->name }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if($task->description)
                                    <p class="task-description mb-4">{{ $task->description }}</p>
                                @endif

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <div class="text-xs text-neutral-500 mb-1">النقاط</div>
                                        <div class="text-lg font-bold text-success-600">{{ number_format($task->points) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-neutral-500 mb-1">الوقت المتبقي</div>
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
                                    </div>
                                </div>

                                @if($task->assignment_type === 'application_required' && $task->max_participants)
                                    <div class="alert-modern alert-warning mb-4">
                                        <i class="fas fa-users"></i>
                                        <span>الحد الأقصى: {{ $task->max_participants }} (متاح: {{ $task->max_participants - $task->getApprovedParticipantsCount() }})</span>
                                    </div>
                                @endif

                                <div class="flex gap-2">
                                    <button onclick="showApplyModal({{ $task->id }}, '{{ $task->title }}')"
                                            class="btn-modern btn-warning flex-1">
                                        <i class="fas fa-hand-paper"></i> تقديم طلب
                                    </button>
                                    <a href="{{ route('additional-tasks.show', $task) }}" class="btn-modern btn-ghost">
                                        <i class="fas fa-eye"></i>
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
                            <div class="task-card" style="border-color: var(--success-light);">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-4 flex-1">
                                        @if($task->icon)
                                            <div class="task-icon" style="background-color: {{ $task->color_code ?? '#10b981' }};">
                                                <i class="{{ $task->icon }}"></i>
                                            </div>
                                        @else
                                            <div class="task-icon" style="background-color: var(--success);">
                                                <i class="fas fa-tasks"></i>
                                            </div>
                                        @endif
                                        <div class="flex-1">
                                            <h4 class="text-xl font-semibold text-neutral-900">{{ $task->title }}</h4>
                                            @if($task->description)
                                                <p class="text-neutral-600 mt-1">{{ $task->description }}</p>
                                            @endif

                                            <div class="flex items-center gap-4 mt-3 flex-wrap">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-star" style="color: var(--warning);"></i>
                                                    <span class="font-bold text-success-600">{{ number_format($task->points) }} نقطة</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-clock text-primary-500"></i>
                                                    @if($task->isExpired())
                                                        <span class="badge-modern badge-error">انتهت</span>
                                                    @else
                                                        @php $hoursRemaining = $task->timeRemainingInHours(); @endphp
                                                        @if($hoursRemaining > 24)
                                                            <span class="text-success-600">{{ round($hoursRemaining / 24, 1) }} يوم متبقي</span>
                                                        @elseif($hoursRemaining > 1)
                                                            <span style="color: var(--warning);">{{ round($hoursRemaining, 2) }} ساعة</span>
                                                        @else
                                                            <span class="text-error-500">أقل من ساعة</span>
                                                        @endif
                                                    @endif
                                                </div>
                                                <span class="badge-modern badge-success">
                                                    <i class="fas fa-check-circle"></i> مقبولة
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="{{ route('additional-tasks.show', $task) }}" class="btn-modern btn-ghost">
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
                            <div class="task-card" style="background: var(--warning-light); border-color: var(--warning);">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        @if($task->icon)
                                            <div class="task-icon" style="background-color: {{ $task->color_code ?? '#f59e0b' }};">
                                                <i class="{{ $task->icon }}"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h4 class="text-lg font-semibold text-neutral-900">{{ $task->title }}</h4>
                                            <div class="text-sm text-neutral-600">
                                                <i class="fas fa-calendar"></i>
                                                تقدمت في: {{ $taskUser->applied_at->format('Y-m-d H:i') }}
                                            </div>
                                            @if($taskUser->user_notes)
                                                <div class="text-sm text-neutral-600 mt-1">
                                                    <i class="fas fa-comment"></i>
                                                    ملاحظاتك: {{ $taskUser->user_notes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="badge-modern badge-warning">
                                            <i class="fas fa-clock"></i> في الانتظار
                                        </span>
                                        <span class="font-bold text-success-600">{{ number_format($task->points) }} نقطة</span>
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
                            <div class="task-card" style="background: var(--error-light); border-color: var(--error);">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        @if($task->icon)
                                            <div class="task-icon" style="background-color: {{ $task->color_code ?? '#ef4444' }};">
                                                <i class="{{ $task->icon }}"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h4 class="text-lg font-semibold text-neutral-900">{{ $task->title }}</h4>
                                            <div class="text-sm text-neutral-600">
                                                <i class="fas fa-times-circle text-error-500"></i>
                                                تم الرفض في: {{ $taskUser->updated_at->format('Y-m-d H:i') }}
                                            </div>
                                            @if($taskUser->admin_notes)
                                                <div class="text-sm text-error-500 mt-1">
                                                    <i class="fas fa-comment"></i>
                                                    سبب الرفض: {{ $taskUser->admin_notes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="badge-modern badge-error">
                                        <i class="fas fa-times"></i> مرفوض
                                    </span>
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
