@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/client-tickets.css') }}">
@endpush

@section('content')
<div class="client-tickets-container">
    <div class="container">
        <!-- Page Header -->
        <div class="ticket-page-header">
            <div>
                <h1>
                    <i class="fas fa-ticket-alt"></i>
                    التذكرة: <code class="ticket-number">{{ $clientTicket->ticket_number }}</code>
                </h1>
                <p class="text-white-50 mb-0">
                    <i class="fas fa-clock"></i> تم الإنشاء في {{ $clientTicket->created_at->format('Y-m-d H:i') }}
                </p>
            </div>
            <div class="header-actions">
                <span class="status-badge status-{{ $clientTicket->status }}">
                    {{ $clientTicket->status_arabic }}
                </span>
                <span class="priority-badge priority-{{ $clientTicket->priority }}">
                    {{ $clientTicket->priority_arabic }}
                </span>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-md-8">
                <!-- Ticket Details Card -->
                <div class="client-ticket-card">
                    <div class="table-header">
                        <h2><i class="fas fa-info-circle"></i> تفاصيل التذكرة</h2>
                    </div>
                    <div class="card-body">
                        <h3 class="text-cyan mb-3">{{ $clientTicket->title }}</h3>

                        <div class="mb-4">
                            <strong class="d-block mb-2"><i class="fas fa-align-right"></i> الوصف:</strong>
                            <div class="p-3 bg-light rounded">{{ $clientTicket->description }}</div>
                        </div>

                        @if($clientTicket->resolution_notes)
                            <div class="mb-4">
                                <strong class="d-block mb-2"><i class="fas fa-check-circle text-success"></i> ملاحظات الحل:</strong>
                                <div class="p-3 rounded" style="background: #d1fae5; border-right: 4px solid #059669;">
                                    {{ $clientTicket->resolution_notes }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Timeline/Activity -->
                <div class="client-ticket-card" style="margin-top: 2rem;">
                    <div class="table-header">
                        <h2><i class="fas fa-history"></i> السجل الزمني</h2>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($clientTicket->activities->sortByDesc('created_at') as $activity)
                                <div class="timeline-item d-flex">
                                    <div class="timeline-icon">
                                        <i class="fas fa-{{ $activity->icon ?? 'circle' }}"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <span class="timeline-user">{{ $activity->user->name ?? 'النظام' }}</span>
                                            <span class="timeline-date">{{ $activity->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="timeline-body mt-2">
                                            {{ $activity->description }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if($clientTicket->activities->count() == 0)
                                <div class="text-center py-3 text-muted">
                                    <i class="fas fa-info-circle"></i> لا توجد أنشطة مسجلة بعد
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                @if($clientTicket->comments && $clientTicket->comments->count() > 0)
                <div class="client-ticket-card" style="margin-top: 2rem;">
                    <div class="table-header">
                        <h2><i class="fas fa-comments"></i> التعليقات ({{ $clientTicket->comments->count() }})</h2>
                    </div>
                    <div class="card-body">
                        <div class="comments-section">
                            @foreach($clientTicket->comments->sortByDesc('created_at') as $comment)
                                <div class="comment-item">
                                    <div class="comment-header">
                                        <span class="comment-author">{{ $comment->user->name }}</span>
                                        <span class="comment-date">{{ $comment->created_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <div class="comment-body">{{ $comment->comment }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Project Info -->
                <div class="client-ticket-card mb-3">
                    <div class="table-header" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                        <h2 style="font-size: 1.2rem;"><i class="fas fa-project-diagram"></i> المشروع</h2>
                    </div>
                    <div class="card-body">
                        @if($clientTicket->project)
                            <p class="mb-2"><strong>اسم المشروع:</strong></p>
                            <a href="{{ route('projects.show', $clientTicket->project) }}" class="text-decoration-none">
                                <div class="p-2 bg-cyan-light rounded">
                                    {{ $clientTicket->project->name }}
                                </div>
                            </a>
                            @if($clientTicket->project->description)
                                <p class="mt-2 mb-0"><strong>الوصف:</strong></p>
                                <p class="text-muted small">{{ Str::limit($clientTicket->project->description, 100) }}</p>
                            @endif
                        @else
                            <p class="text-muted text-center py-3">
                                <i class="fas fa-info-circle"></i><br>
                                غير مرتبطة بمشروع
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Ticket Info -->
                <div class="client-ticket-card mb-3">
                    <div class="table-header" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
                        <h2 style="font-size: 1.2rem;"><i class="fas fa-cog"></i> معلومات التذكرة</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <p class="mb-1"><strong>القسم المختص:</strong></p>
                            <span class="badge badge-secondary">{{ $clientTicket->department_arabic }}</span>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1"><strong>تم الإنشاء بواسطة:</strong></p>
                            <p class="mb-0">{{ $clientTicket->creator->name }}</p>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1"><strong>المعينين:</strong></p>
                            @if($clientTicket->activeAssignments && $clientTicket->activeAssignments->count() > 0)
                                @foreach($clientTicket->activeAssignments as $assignment)
                                    <span class="badge badge-info mr-1 mb-1">{{ $assignment->user->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">غير معين</span>
                            @endif
                        </div>
                        @if($clientTicket->resolved_at)
                            <div class="mb-3">
                                <p class="mb-1"><strong>تاريخ الحل:</strong></p>
                                <p class="mb-0">{{ $clientTicket->resolved_at->format('Y-m-d H:i') }}</p>
                            </div>
                            <div>
                                <p class="mb-1"><strong>وقت الحل:</strong></p>
                                <span class="badge badge-info">{{ $clientTicket->created_at->diffForHumans($clientTicket->resolved_at, true) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Team Management -->
                <div class="client-ticket-card mb-3">
                    <div class="table-header" style="background: linear-gradient(135deg, #facc15, #eab308);">
                        <h2 style="font-size: 1.2rem;"><i class="fas fa-users"></i> إدارة الفريق</h2>
                    </div>
                    <div class="card-body">
                        @if($clientTicket->activeAssignments && $clientTicket->activeAssignments->count() > 0)
                            <div class="mb-3">
                                <h6 class="mb-2"><strong>الأعضاء الحاليين:</strong></h6>
                                @foreach($clientTicket->activeAssignments as $assignment)
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                        <div>
                                            <strong>{{ $assignment->user->name }}</strong>
                                            <small class="text-muted d-block">معين منذ {{ $assignment->assigned_at->diffForHumans() }}</small>
                                        </div>
                                        <form method="POST" action="{{ route('client-tickets.remove-user', $clientTicket) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $assignment->user_id }}">
                                            <button type="submit" class="arkan-btn arkan-btn-danger btn-group-sm"
                                                    onclick="return confirm('هل تريد إزالة {{ $assignment->user->name }} من التذكرة؟')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Add User Form -->
                        <form method="POST" action="{{ route('client-tickets.add-user', $clientTicket) }}">
                            @csrf
                            <div class="form-group mb-0">
                                <label><strong>إضافة عضو جديد:</strong></label>
                                <select name="user_id" class="form-control mb-2" required>
                                    <option value="">اختر الموظف</option>
                                    @foreach($employees as $employee)
                                        @if(!$clientTicket->activeAssignments->pluck('user_id')->contains($employee->id))
                                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button type="submit" class="arkan-btn btn-success" style="width: 100%;">
                                    <i class="fas fa-plus"></i> إضافة
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Actions -->
                <div class="client-ticket-card">
                    <div class="table-header">
                        <h2 style="font-size: 1.2rem;"><i class="fas fa-tasks"></i> إجراءات</h2>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons" style="flex-direction: column;">
                            <a href="{{ route('client-tickets.edit', $clientTicket) }}" class="arkan-btn btn-warning" style="width: 100%; margin-bottom: 0.5rem;">
                                <i class="fas fa-edit"></i> تعديل التذكرة
                            </a>
                            @if($clientTicket->status !== 'resolved')
                                <button type="button" class="arkan-btn btn-success" style="width: 100%; margin-bottom: 0.5rem;"
                                        onclick="$('#resolveModal').modal('show')">
                                    <i class="fas fa-check"></i> حل التذكرة
                                </button>
                            @endif
                            <a href="{{ route('client-tickets.index') }}" class="arkan-btn btn-secondary" style="width: 100%; margin-bottom: 0.5rem;">
                                <i class="fas fa-list"></i> العودة للقائمة
                            </a>
                            <form method="POST" action="{{ route('client-tickets.destroy', $clientTicket) }}" style="width: 100%;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="arkan-btn arkan-btn-danger" style="width: 100%;"
                                        onclick="return confirm('هل أنت متأكد من حذف هذه التذكرة؟')">
                                    <i class="fas fa-trash"></i> حذف التذكرة
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resolve Modal -->
@if($clientTicket->status !== 'resolved')
<div class="modal fade" id="resolveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('client-tickets.resolve', $clientTicket) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">حل التذكرة</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>التذكرة: <strong>{{ $clientTicket->ticket_number }}</strong></p>
                    <div class="form-group">
                        <label><strong>ملاحظات الحل *</strong></label>
                        <textarea name="resolution_notes" class="form-control" rows="4"
                                  placeholder="اكتب تفاصيل كيفية حل المشكلة..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="arkan-btn btn-success">
                        <i class="fas fa-check"></i> حل التذكرة
                    </button>
                    <button type="button" class="arkan-btn btn-secondary" data-dismiss="modal">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
