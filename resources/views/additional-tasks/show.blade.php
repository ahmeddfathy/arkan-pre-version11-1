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
                        <h1>{{ $additionalTask->title }}</h1>
                        <p>
                            <i class="fas fa-info-circle"></i> تفاصيل المهمة الإضافية
                        </p>
                    </div>
                    <div class="flex gap-3 items-center flex-wrap">
                        <!-- زر التقديم/القبول إذا كان متاح للمستخدم -->
                        @auth
                            @php
                                $user = auth()->user();
                                $alreadyApplied = $additionalTask->taskUsers()->where('user_id', $user->id)->exists();
                                $canApply = !$alreadyApplied &&
                                           $additionalTask->status === 'active' &&
                                           !$additionalTask->isExpired() &&
                                           ($additionalTask->target_type === 'all' ||
                                            ($additionalTask->target_type === 'department' && $additionalTask->target_department === $user->department));
                            @endphp

                            @if($canApply)
                                <button id="applyTaskBtn"
                                        data-task-id="{{ $additionalTask->id }}"
                                        data-task-title="{{ $additionalTask->title }}"
                                        class="btn-modern btn-warning">
                                    <i class="fas fa-hand-paper"></i> تقديم طلب
                                </button>
                            @elseif($alreadyApplied)
                                <div class="badge-modern badge-success">
                                    <i class="fas fa-check-circle"></i> سبق التقديم
                                </div>
                            @endif
                        @endauth

                        <a href="{{ route('additional-tasks.index') }}" class="btn-modern btn-secondary">
                            <i class="fas fa-arrow-left"></i> العودة للقائمة
                        </a>
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

            <!-- معلومات المهمة والإحصائيات -->
            <div class="stats-grid mb-8">
                <!-- معلومات المهمة -->
                <div class="stat-card" style="grid-column: span 2;">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h3 class="stat-title">تفاصيل المهمة</h3>
                    </div>

                    @if($additionalTask->description)
                        <div class="mb-4">
                            <h4 class="font-medium text-neutral-700 mb-2">الوصف:</h4>
                            <p class="text-neutral-600 leading-relaxed">{{ $additionalTask->description }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-neutral-700 mb-1">النقاط المكتسبة:</h4>
                            <div class="stat-value text-success-600">{{ number_format($additionalTask->points) }}</div>
                        </div>
                        <div>
                            <h4 class="font-medium text-neutral-700 mb-1">المدة الأصلية:</h4>
                            <div class="text-lg text-neutral-900">{{ $additionalTask->duration_hours }} ساعة</div>
                        </div>
                        <div>
                            <h4 class="font-medium text-neutral-700 mb-1">الاستهداف:</h4>
                            <div class="text-sm">
                                @if($additionalTask->target_type === 'all')
                                    <span class="badge-modern badge-primary">جميع الموظفين</span>
                                @else
                                    <span class="badge-modern badge-secondary">{{ $additionalTask->target_department }}</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-neutral-700 mb-1">نوع المهمة:</h4>
                            <div class="text-sm">
                                <span class="badge-modern badge-warning">
                                    <i class="fas fa-hand-paper"></i> يتطلب تقديم
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($additionalTask->max_participants)
                        <div class="alert-modern alert-warning mt-4">
                            <i class="fas fa-users"></i>
                            <span>الحد الأقصى للمشاركين: {{ $additionalTask->max_participants }}</span>
                        </div>
                    @endif
                </div>

                <!-- الحالة والوقت -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="stat-title">الحالة والتوقيت</h3>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h4 class="font-medium text-neutral-700 mb-2">الحالة الحالية:</h4>
                            @switch($additionalTask->status)
                                @case('active')
                                    <span class="badge-modern badge-success">
                                        <i class="fas fa-play"></i> نشط
                                    </span>
                                    @break
                                @case('expired')
                                    <span class="badge-modern badge-error">
                                        <i class="fas fa-clock"></i> منتهي
                                    </span>
                                    @break
                                @case('cancelled')
                                    <span class="badge-modern badge-neutral">
                                        <i class="fas fa-ban"></i> ملغي
                                    </span>
                                    @break
                            @endswitch
                        </div>

                        <div>
                            <h4 class="font-medium text-neutral-700 mb-2">الوقت المتبقي:</h4>
                            @if($additionalTask->isExpired())
                                <div class="badge-modern badge-error">
                                    <i class="fas fa-clock"></i> انتهت المهمة
                                </div>
                            @else
                                @php
                                    $hoursRemaining = $additionalTask->timeRemainingInHours();
                                @endphp
                                <div class="text-lg font-bold">
                                    @if($hoursRemaining > 24)
                                        <span class="badge-modern badge-success">{{ round($hoursRemaining / 24, 1) }} يوم</span>
                                    @elseif($hoursRemaining > 1)
                                        <span class="badge-modern badge-warning">{{ $hoursRemaining }} ساعة</span>
                                    @else
                                        <span class="badge-modern badge-error">أقل من ساعة</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if($additionalTask->extensions_count > 0)
                            <div>
                                <h4 class="font-medium text-neutral-700 mb-2">التمديدات:</h4>
                                <div class="text-primary-600">
                                    <i class="fas fa-history"></i> تم التمديد {{ $additionalTask->extensions_count }} مرة
                                </div>
                            </div>
                        @endif

                        <div class="text-xs text-neutral-500">
                            <div>بدأت: {{ $additionalTask->original_end_time->subHours($additionalTask->duration_hours)->format('Y-m-d H:i') }}</div>
                            <div>تنتهي: {{ $additionalTask->current_end_time->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                </div>

                <!-- إحصائيات المشاركة -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="stat-title">إحصائيات المشاركة</h3>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-neutral-600">إجمالي المشاركين:</span>
                            <span class="font-bold">{{ $stats['total_assigned'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">مقبول:</span>
                            <span class="font-bold text-green-600">{{ $stats['assigned'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">في الانتظار:</span>
                            <span class="font-bold text-yellow-600">{{ $stats['pending'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">مكتمل:</span>
                            <span class="font-bold text-success-600">{{ $stats['completed'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">فاشل:</span>
                            <span class="font-bold text-error-600">{{ $stats['failed'] }}</span>
                        </div>
                        <div class="pt-2 border-t">
                            <div class="flex justify-between">
                                <span class="text-neutral-600">معدل الإنجاز:</span>
                                <span class="font-bold text-secondary-600">{{ $stats['completion_rate'] }}%</span>
                            </div>
                        </div>
                    </div>

                    @if(true) {{-- جميع المهام تتطلب تقديم الآن --}}
                        <div class="mt-4 pt-4 border-t">
                            <div class="flex justify-between">
                                <span class="text-neutral-600">طلبات في الانتظار:</span>
                                <span class="font-bold text-warning-600">{{ $additionalTask->getPendingApplicationsCount() }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- قائمة المشاركين -->
            <div class="modern-card mt-8">
                <div style="padding: 2rem;">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold text-neutral-900">
                            <i class="fas fa-list text-primary-500"></i> المشاركين في المهمة
                        </h3>
                        @if(true) {{-- جميع المهام تتطلب تقديم الآن --}}
                            <a href="{{ route('additional-tasks.applications') }}"
                               class="btn-modern btn-warning">
                                <i class="fas fa-file-contract"></i> إدارة الطلبات
                            </a>
                        @endif
                    </div>

                @if($taskUsers->count() > 0)
                    <div class="table-modern">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>المستخدم</th>
                                    <th>الحالة</th>
                                    <th>تاريخ التقديم</th>
                                    <th>النقاط المكتسبة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($taskUsers as $taskUser)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                @if($taskUser->user->profile_photo_path)
                                                    <img src="{{ asset('storage/' . $taskUser->user->profile_photo_path) }}"
                                                         alt="{{ $taskUser->user->name }}"
                                                         class="h-8 w-8 rounded-full">
                                                @else
                                                    <div class="h-8 w-8 rounded-full bg-neutral-300 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-neutral-700">{{ substr($taskUser->user->name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="text-sm font-medium text-neutral-900">{{ $taskUser->user->name }}</div>
                                                    <div class="text-sm text-neutral-500">{{ $taskUser->user->department }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @switch($taskUser->status)
                                                @case('applied')
                                                    <span class="badge-modern badge-warning">
                                                        <i class="fas fa-clock"></i> في الانتظار
                                                    </span>
                                                    @break
                                                @case('approved')
                                                @case('assigned')
                                                    <span class="badge-modern badge-primary">
                                                        <i class="fas fa-check-circle"></i> مقبول
                                                    </span>
                                                    @break
                                                @case('completed')
                                                    <span class="badge-modern badge-success">
                                                        <i class="fas fa-check-circle"></i> مكتمل
                                                    </span>
                                                    @break
                                                @case('rejected')
                                                    <span class="badge-modern badge-error">
                                                        <i class="fas fa-times"></i> مرفوض
                                                    </span>
                                                    @break
                                                @case('failed')
                                                    <span class="badge-modern badge-neutral">
                                                        <i class="fas fa-exclamation"></i> فاشل
                                                    </span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td class="text-sm text-neutral-900">
                                            {{ $taskUser->applied_at ? $taskUser->applied_at->format('Y-m-d H:i') : '-' }}
                                        </td>
                                        <td class="text-sm font-bold text-success-600">
                                            {{ $taskUser->points_earned ? number_format($taskUser->points_earned) : '-' }}
                                        </td>
                                        <td class="text-sm font-medium">
                                            <div class="flex gap-2">
                                                @if($taskUser->status === 'applied')
                                                    <form method="POST" action="{{ route('additional-task-users.approve', $taskUser) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn-modern btn-success btn-sm">
                                                            <i class="fas fa-check"></i> قبول
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('additional-task-users.reject', $taskUser) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn-modern btn-error btn-sm">
                                                            <i class="fas fa-times"></i> رفض
                                                        </button>
                                                    </form>
                                                @elseif($taskUser->user_notes || $taskUser->admin_notes)
                                                    <button onclick="showNotesModal('{{ $taskUser->user->name }}', '{{ $taskUser->user_notes }}', '{{ $taskUser->admin_notes }}')"
                                                            class="btn-modern btn-primary btn-sm">
                                                        <i class="fas fa-comment"></i> ملاحظات
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $taskUsers->links() }}
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="empty-state-title">لا يوجد مشاركين</h4>
                        <p class="empty-state-description">
                            @if(true) {{-- جميع المهام تتطلب تقديم الآن --}}
                                لم يتقدم أحد على هذه المهمة بعد
                            @else
                                لم يتم تخصيص هذه المهمة لأي مستخدم
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Extend Time Modal -->
<div id="extendModal" class="modal-overlay modal-overlay-hidden">
    <div class="modal-content">
        <h3 class="modal-title">
            <i class="fas fa-clock"></i>
            تمديد وقت المهمة
        </h3>
        <form action="{{ route('additional-tasks.extend-time', $additionalTask) }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="additional_hours" class="form-label">عدد الساعات الإضافية</label>
                <input type="number" id="additional_hours" name="additional_hours" min="1" max="168" required
                       class="form-input">
            </div>

            <div class="form-group">
                <label for="reason" class="form-label">سبب التمديد</label>
                <textarea id="reason" name="reason" rows="3"
                          class="form-input"
                          placeholder="اختياري - اشرح سبب التمديد"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="hideExtendModal()"
                        class="btn-modern btn-secondary">
                    إلغاء
                </button>
                <button type="submit"
                        class="btn-modern btn-primary">
                    <i class="fas fa-clock"></i> تمديد الوقت
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Notes Modal -->
<div id="notesModal" class="modal-overlay modal-overlay-hidden">
    <div class="modal-content">
        <h3 class="modal-title">
            <i class="fas fa-comment"></i>
            الملاحظات
        </h3>

        <div class="space-y-4">
            <div>
                <h4 class="font-medium text-neutral-700 mb-2">المستخدم:</h4>
                <div id="modal_user_name" class="text-neutral-900"></div>
            </div>

            <div id="user_notes_section">
                <h4 class="font-medium text-neutral-700 mb-2">ملاحظات المستخدم:</h4>
                <div id="modal_user_notes" class="text-neutral-600 bg-neutral-50 p-3 rounded"></div>
            </div>

            <div id="admin_notes_section">
                <h4 class="font-medium text-neutral-700 mb-2">ملاحظات الإدارة:</h4>
                <div id="modal_admin_notes" class="text-neutral-600 bg-primary-50 p-3 rounded"></div>
            </div>
        </div>

        <div class="modal-actions">
            <button type="button" onclick="hideNotesModal()"
                    class="btn-modern btn-secondary">
                إغلاق
            </button>
        </div>
    </div>
</div>

<script>
function showExtendModal() {
    const modal = document.getElementById('extendModal');
    modal.classList.remove('modal-overlay-hidden');
    modal.classList.add('active');
}

function hideExtendModal() {
    const modal = document.getElementById('extendModal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.classList.add('modal-overlay-hidden');
    }, 300);
    document.getElementById('additional_hours').value = '';
    document.getElementById('reason').value = '';
}

function showNotesModal(userName, userNotes, adminNotes) {
    document.getElementById('modal_user_name').textContent = userName;

    if (userNotes) {
        document.getElementById('modal_user_notes').textContent = userNotes;
        document.getElementById('user_notes_section').style.display = 'block';
    } else {
        document.getElementById('user_notes_section').style.display = 'none';
    }

    if (adminNotes) {
        document.getElementById('modal_admin_notes').textContent = adminNotes;
        document.getElementById('admin_notes_section').style.display = 'block';
    } else {
        document.getElementById('admin_notes_section').style.display = 'none';
    }

    document.getElementById('notesModal').classList.remove('modal-overlay-hidden');
}

function hideNotesModal() {
    document.getElementById('notesModal').classList.add('modal-overlay-hidden');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const extendModal = document.getElementById('extendModal');
    const notesModal = document.getElementById('notesModal');

    if (event.target == extendModal) {
        hideExtendModal();
    }
    if (event.target == notesModal) {
        hideNotesModal();
    }
}
</script>
@endsection

<!-- Apply Modal -->
<div id="applyModal" class="modal-overlay modal-overlay-hidden">
    <div class="modal-content">
        <h3 class="modal-title">
            <i class="fas fa-hand-paper"></i>
            تقديم طلب للمشاركة
        </h3>
        <form action="#" method="POST" id="applyForm">
            @csrf

            <div class="form-group">
                <label class="form-label">المهمة</label>
                <div id="apply_task_title" class="text-neutral-900 font-medium"></div>
            </div>

            <div class="form-group">
                <label for="user_notes" class="form-label">ملاحظاتك (اختياري)</label>
                <textarea id="user_notes" name="user_notes" rows="3"
                          class="form-input"
                          placeholder="اشرح لماذا تريد المشاركة في هذه المهمة"></textarea>
            </div>

            <div class="modal-actions">
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
document.addEventListener('DOMContentLoaded', function() {
    // Apply modal functions
    function showApplyModal(taskId, taskTitle) {
        document.getElementById('apply_task_title').textContent = taskTitle;
        document.getElementById('applyForm').action = '/additional-tasks/' + taskId + '/apply';
        document.getElementById('applyModal').classList.remove('modal-overlay-hidden');
    }

    function hideApplyModal() {
        document.getElementById('applyModal').classList.add('modal-overlay-hidden');
        document.getElementById('user_notes').value = '';
    }

    // Apply button event listener
    const applyBtn = document.getElementById('applyTaskBtn');
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            const taskId = this.getAttribute('data-task-id');
            const taskTitle = this.getAttribute('data-task-title');
            showApplyModal(taskId, taskTitle);
        });
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const applyModal = document.getElementById('applyModal');

        if (event.target === applyModal) {
            hideApplyModal();
        }
    });

    // Hide modal function (global for button onclick)
    window.hideApplyModal = hideApplyModal;
});
</script>
