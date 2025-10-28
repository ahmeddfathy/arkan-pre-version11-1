@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/client-tickets.css') }}">
@endpush

@section('content')
<div class="client-tickets-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-ticket-alt"></i> تذاكر العملاء</h1>
            <p>إدارة ومتابعة جميع تذاكر الدعم</p>
            <div class="card-tools">
                <a href="{{ route('client-tickets.create') }}" class="arkan-btn arkan-btn-primary">
                    <i class="fas fa-plus"></i> إضافة تذكرة جديدة
                </a>
                <a href="{{ route('client-tickets.dashboard') }}" class="arkan-btn btn-info">
                    <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="background: #d1fae5; color: #065f46; border: 1px solid #10b981; border-radius: 10px; margin-bottom: 1.5rem;">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; border-radius: 10px; margin-bottom: 1.5rem;">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="client-ticket-card mt-5">
            <div class="table-header">
                <h2>📋 قائمة التذاكر</h2>
            </div>

            <!-- Filters -->
            <div class="arkan-filters">
                <form method="GET" action="{{ route('client-tickets.index') }}" class="row">
                    <div class="col-md-2">
                        <select name="project_id" class="form-control">
                            <option value="">جميع المشاريع</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">جميع الحالات</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>مفتوحة</option>
                            <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>معينة</option>
                            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>محلولة</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>مغلقة</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="priority" class="form-control">
                            <option value="">جميع الأولويات</option>
                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>منخفضة</option>
                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>متوسطة</option>
                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>عالية</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="department" class="form-control">
                            <option value="">جميع الأقسام</option>
                            @foreach($departments as $department)
                                <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                                    {{ $department }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="assigned_to" class="form-control">
                            <option value="">جميع الموظفين</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('assigned_to') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="arkan-btn btn-info" style="margin-left: 0.5rem;">
                            <i class="fas fa-search"></i> بحث
                        </button>
                        <a href="{{ route('client-tickets.index') }}" class="arkan-btn btn-secondary">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive ">
                @if($tickets->count() > 0)
                    <table class="arkan-table">
                        <thead>
                            <tr>
                                <th>رقم التذكرة</th>
                                <th>المشروع</th>
                                <th>العنوان</th>
                                <th>الحالة</th>
                                <th>الأولوية</th>
                                <th>القسم</th>
                                <th>المعين إليه</th>
                                <th>تاريخ الإنشاء</th>
                                <th>العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $ticket)
                                <tr>
                                    <td>
                                        <code>{{ $ticket->ticket_number }}</code>
                                    </td>
                                    <td>
                                        @if($ticket->project)
                                            <a href="{{ route('projects.show', $ticket->project) }}" class="text-decoration-none text-cyan">
                                                {{ $ticket->project->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">غير مرتبط بمشروع</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $ticket->title }}</strong>
                                        <br><small class="text-muted">{{ Str::limit($ticket->description, 50) }}</small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $ticket->status }}">
                                            {{ $ticket->status_arabic }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-{{ $ticket->priority }}">
                                            {{ $ticket->priority_arabic }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ $ticket->department_arabic }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($ticket->assignedEmployee)
                                            {{ $ticket->assignedEmployee->name }}
                                        @else
                                            <span class="text-muted">غير معين</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $ticket->created_at->format('Y-m-d') }}<br>
                                        <small class="text-muted">{{ $ticket->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('client-tickets.show', $ticket) }}" class="arkan-btn btn-info" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('client-tickets.edit', $ticket) }}" class="arkan-btn btn-warning" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($ticket->status !== 'resolved')
                                                <button type="button" class="arkan-btn btn-success" title="حل"
                                                        onclick="showResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            <form method="POST" action="{{ route('client-tickets.destroy', $ticket) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="arkan-btn arkan-btn-danger" title="حذف"
                                                        onclick="return confirm('هل أنت متأكد من حذف هذه التذكرة؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="card-footer">
                        {{ $tickets->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-3x mb-3"></i>
                        <h4>لا توجد تذاكر</h4>
                        <p>لم يتم العثور على أي تذاكر تطابق المعايير المحددة</p>
                        <a href="{{ route('client-tickets.create') }}" class="arkan-btn arkan-btn-primary">
                            <i class="fas fa-plus"></i> إضافة أول تذكرة
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Resolve Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="resolveForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">حل التذكرة</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>التذكرة: <strong><span id="ticketNumber"></span></strong></p>
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
@endsection

@push('scripts')
<script>
function showResolveModal(ticketId, ticketNumber) {
    document.getElementById('ticketNumber').textContent = ticketNumber;
    document.getElementById('resolveForm').action = `/client-tickets/${ticketId}/resolve`;
    $('#resolveModal').modal('show');
}
</script>
@endpush
