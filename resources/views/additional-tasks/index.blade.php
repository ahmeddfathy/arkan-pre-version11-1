@extends('layouts.app')

@section('title', 'إدارة المهام الإضافية')

@push('styles')
<link href="{{ asset('css/additional-tasks.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="additional-tasks-container">
    <div style="width: 100%; padding: 0 2rem;">
        <!-- Page Header -->
        <div class="page-header-tasks">
            <h1>📋 إدارة المهام الإضافية</h1>
            <p>إدارة وعرض جميع المهام الإضافية لكسب النقاط والتحفيز</p>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div></div>
            <div class="d-flex gap-2">
                @if(isset($createCheck) && $createCheck['can_create'])
                    <a href="{{ route('additional-tasks.create') }}" class="btn-custom btn-gradient-primary">
                        <i class="fas fa-plus"></i>
                        إضافة مهمة جديدة
                    </a>
                @else
                    <div class="badge-modern badge-neutral">
                        <i class="fas fa-info-circle"></i>
                        رؤية المهام فقط (المستوى 2+ يمكنه الإنشاء)
                    </div>
                @endif
                @if(Auth::user()->hasRole(['admin', 'super-admin', 'hr', 'project_manager']))
                    <a href="{{ route('additional-tasks.applications') }}" class="btn-custom btn-gradient-light">
                        <i class="fas fa-file-contract"></i>
                        طلبات المهام
                    </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert-modern alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle fa-lg"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert-modern alert-error alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Statistics Row -->
        @php
            $activeCount = $tasks->where('status', 'active')->count();
            $expiredCount = $tasks->where('status', 'expired')->count();
            $completedTasksCount = $tasks->where('status', 'completed')->count();
            $cancelledCount = $tasks->where('status', 'cancelled')->count();
        @endphp
        <div class="stats-row-tasks">
            <div class="stat-card-modern" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <i class="fas fa-tasks fa-2x mb-3" style="opacity: 0.9;"></i>
                <div class="stat-number-modern">{{ $tasks->total() }}</div>
                <div class="stat-label-modern" style="color: rgba(255,255,255,0.9);">إجمالي المهام</div>
            </div>
            <div class="stat-card-modern" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white;">
                <i class="fas fa-play fa-2x mb-3" style="opacity: 0.9;"></i>
                <div class="stat-number-modern">{{ $activeCount }}</div>
                <div class="stat-label-modern" style="color: rgba(255,255,255,0.9);">مهام نشطة</div>
            </div>
            <div class="stat-card-modern" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white;">
                <i class="fas fa-check-circle fa-2x mb-3" style="opacity: 0.9;"></i>
                <div class="stat-number-modern">{{ $completedTasksCount }}</div>
                <div class="stat-label-modern" style="color: rgba(255,255,255,0.9);">مكتملة</div>
            </div>
            <div class="stat-card-modern" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
                <i class="fas fa-times-circle fa-2x mb-3" style="opacity: 0.9;"></i>
                <div class="stat-number-modern">{{ $expiredCount }}</div>
                <div class="stat-label-modern" style="color: rgba(255,255,255,0.9);">منتهية</div>
            </div>
        </div>

        <!-- Tasks Table -->
        <div class="tasks-table-container">
            <div class="tasks-table-header">
                <h2>📋 قائمة المهام الإضافية</h2>
            </div>

            @if($tasks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">المهمة</th>
                                <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">النوع</th>
                                <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600; text-align: center;">النقاط</th>
                                <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600;">المدة المتبقية</th>
                                <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600; text-align: center;">المشاركين</th>
                                <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600; text-align: center;">الحالة</th>
                                <th style="padding: 1rem 1.5rem; color: #374151; font-weight: 600; text-align: center;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                                <tr style="transition: all 0.3s ease; border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 1.5rem;">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            @if($task->icon)
                                                <div class="task-icon" style="background: {{ $task->color_code ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }};">
                                                    <i class="{{ $task->icon }}"></i>
                                                </div>
                                            @else
                                                <div class="task-icon">
                                                    <i class="fas fa-tasks"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">{{ $task->title }}</div>
                                                <div style="font-size: 0.875rem; color: #6b7280;">
                                                    <i class="fas fa-hand-paper" style="color: #ffc107;"></i> يتطلب تقديم
                                                    {{ $task->target_type === 'all' ? ' - للجميع' : ' - ' . $task->target_department }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.5rem;">
                                        <span class="badge-modern badge-warning">
                                            <i class="fas fa-hand-paper"></i> بالتقديم
                                        </span>
                                    </td>
                                    <td style="padding: 1.5rem; text-align: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                                            <strong style="font-size: 1.25rem; color: #28a745;">{{ number_format($task->points) }}</strong>
                                            <small style="color: #6b7280; font-size: 0.75rem;">نقطة</small>
                                        </div>
                                    </td>
                                    <td style="padding: 1.5rem;">
                                        @if($task->isExpired())
                                            <span class="badge-modern badge-error">
                                                <i class="fas fa-clock"></i> انتهت
                                            </span>
                                        @else
                                            @php
                                                $hoursRemaining = $task->timeRemainingInHours();
                                            @endphp
                                            <div>
                                                @if($hoursRemaining > 24)
                                                    <span class="badge-modern badge-success">
                                                        <i class="fas fa-calendar-check"></i>
                                                        {{ round($hoursRemaining / 24, 1) }} يوم
                                                    </span>
                                                @elseif($hoursRemaining > 1)
                                                    <span class="badge-modern badge-warning">
                                                        <i class="fas fa-clock"></i>
                                                        {{ $hoursRemaining }} ساعة
                                                    </span>
                                                @else
                                                    <span class="badge-modern badge-error">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        أقل من ساعة
                                                    </span>
                                                @endif
                                            </div>
                                            @if($task->extensions_count > 0)
                                                <div style="font-size: 0.75rem; color: #667eea; margin-top: 0.25rem;">
                                                    <i class="fas fa-history"></i> تم التمديد {{ $task->extensions_count }} مرة
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td style="padding: 1.5rem; text-align: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                                            <strong style="font-size: 1rem;">{{ $task->completed_count }}/{{ $task->task_users_count }}</strong>
                                            @if($task->assignment_type === 'application_required' && $task->max_participants)
                                                <small style="color: #6b7280; font-size: 0.75rem;">
                                                    الحد: {{ $task->max_participants }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: 1.5rem; text-align: center;">
                                        @switch($task->status)
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
                                    </td>
                                    <td style="padding: 1.5rem; text-align: center;">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('additional-tasks.show', $task) }}"
                                               class="btn btn-sm"
                                               style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; border-radius: 8px 0 0 8px; padding: 0.5rem 1rem; transition: all 0.3s ease;"
                                               title="عرض"
                                               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(23, 162, 184, 0.3)'"
                                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('additional-tasks.edit', $task) }}"
                                               class="btn btn-sm"
                                               style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 0; padding: 0.5rem 1rem; transition: all 0.3s ease;"
                                               title="تعديل"
                                               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.3)'"
                                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($task->canBeExtended())
                                                <button onclick="showExtendModal({{ $task->id }}, '{{ $task->title }}')"
                                                        class="btn btn-sm"
                                                        style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; border-radius: 0; padding: 0.5rem 1rem; transition: all 0.3s ease;"
                                                        title="تمديد"
                                                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(40, 167, 69, 0.3)'"
                                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                            @endif
                                            <form action="{{ route('additional-tasks.destroy', $task) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('هل أنت متأكد من حذف هذه المهمة؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm"
                                                        style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-radius: 0 8px 8px 0; padding: 0.5rem 1rem; transition: all 0.3s ease;"
                                                        title="حذف"
                                                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(220, 53, 69, 0.3)'"
                                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-3">
                    {{ $tasks->links() }}
                </div>
            @else
                <div style="text-align: center; padding: 5rem 2rem;">
                    <i class="fas fa-tasks" style="font-size: 5rem; color: #dee2e6; margin-bottom: 2rem;"></i>
                    <h4 style="color: #6b7280; font-weight: 600; margin-bottom: 1rem;">لا توجد مهام إضافية بعد</h4>
                    <p style="color: #9ca3af; margin-bottom: 2rem;">ابدأ بإضافة أول مهمة إضافية لتحفيز الموظفين</p>
                    @if(isset($createCheck) && $createCheck['can_create'])
                        <a href="{{ route('additional-tasks.create') }}" class="btn-custom btn-gradient-primary">
                            <i class="fas fa-plus"></i>
                            إضافة أول مهمة
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Extend Time Modal -->
<div id="extendModal" class="modal-overlay">
    <div class="modal-content">
        <button type="button" onclick="hideExtendModal()" class="modal-close">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <i class="fas fa-clock" style="color: #667eea; font-size: 1.5rem;"></i>
            <h3 class="modal-title">تمديد وقت المهمة</h3>
        </div>
        <form action="#" method="POST" id="extendForm">
            @csrf
            <input type="hidden" id="extend_task_id" name="task_id">

            <div class="form-group">
                <label class="form-label">المهمة</label>
                <div id="extend_task_title" style="padding: 1rem; background: #f9fafb; border-radius: 8px; font-weight: 600;"></div>
            </div>

            <div class="form-group">
                <label for="additional_hours" class="form-label">
                    <i class="fas fa-clock"></i>
                    عدد الساعات الإضافية
                </label>
                <input type="number" id="additional_hours" name="additional_hours" min="1" max="168" required
                       class="form-input">
            </div>

            <div class="form-group">
                <label for="reason" class="form-label">
                    <i class="fas fa-comment-alt"></i>
                    سبب التمديد
                </label>
                <textarea id="reason" name="reason" rows="3"
                          class="form-input form-textarea"
                          placeholder="اختياري - اشرح سبب التمديد"></textarea>
            </div>

            <div class="flex gap-3 justify-end" style="margin-top: 1.5rem;">
                <button type="button" onclick="hideExtendModal()" class="btn-custom" style="background: #6c757d; color: white; padding: 12px 28px;">
                    إلغاء
                </button>
                <button type="submit" class="btn-custom btn-gradient-primary">
                    <i class="fas fa-clock"></i> تمديد الوقت
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showExtendModal(taskId, taskTitle) {
    document.getElementById('extend_task_id').value = taskId;
    document.getElementById('extend_task_title').textContent = taskTitle;
    document.getElementById('extendForm').action = '/additional-tasks/' + taskId + '/extend-time';
    document.getElementById('extendModal').classList.add('active');
}

function hideExtendModal() {
    document.getElementById('extendModal').classList.remove('active');
    document.getElementById('additional_hours').value = '';
    document.getElementById('reason').value = '';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('extendModal');
    if (event.target === modal) {
        hideExtendModal();
    }
});
</script>
@endpush
@endsection
