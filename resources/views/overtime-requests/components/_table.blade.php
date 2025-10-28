<!-- My requests table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-clock"></i> طلبات العمل الإضافي الخاصة بي
        </h5>
        @if($canCreateOvertime && Auth::user()->hasPermissionTo('create_overtime'))
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createOvertimeModal">
            <i class="fas fa-plus"></i> طلب جديد
        </button>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>الوقت</th>
                    <th>المدة</th>
                    <th>السبب</th>
                    <th>رد المدير</th>
                    <th>سبب رفض المدير</th>
                    <th>رد HR</th>
                    <th>سبب رفض HR</th>
                    <th>الحالة النهائية</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($myRequests as $request)
                <tr>
                    <td>{{ $request->overtime_date->format('Y-m-d') }}</td>
                    <td>
                        {{ Carbon\Carbon::parse($request->start_time)->format('H:i') }} -
                        {{ Carbon\Carbon::parse($request->end_time)->format('H:i') }}
                    </td>
                    <td>{{ $request->getFormattedDuration() }}</td>
                    <td>{{ Str::limit($request->reason, 30) }}</td>
                    <td>
                        <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($request->manager_status) }}
                        </span>
                    </td>
                    <td>{{ Str::limit($request->manager_rejection_reason, 30) }}</td>
                    <td>
                        <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($request->hr_status) }}
                        </span>
                    </td>
                    <td>{{ Str::limit($request->hr_rejection_reason, 30) }}</td>
                    <td>
                        <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <!-- زر عرض الطلب -->
                            <a href="{{ route('overtime-requests.show', $request->id) }}"
                               class="btn btn-sm btn-info"
                               title="عرض الطلب">
                                <i class="fas fa-eye"></i> عرض
                            </a>

                            @if((Auth::user()->hasRole('hr') && Auth::user()->hasPermissionTo('manager_respond_overtime_request')) ||
                            (Auth::user()->hasRole('hr') && $request->manager_status == 'pending' && Auth::user()->hasPermissionTo('update_overtime')) ||
                            ($request->canUpdate(Auth::user()) && $request->hr_status === 'pending' && $request->manager_status == 'pending' && Auth::user()->hasPermissionTo('update_overtime')))
                            <button type="button" class="btn btn-primary edit-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#editOvertimeModal"
                                data-request="{{ json_encode([
                                            'id' => $request->id,
                                            'overtime_date' => $request->overtime_date->format('Y-m-d'),
                                            'start_time' => \Carbon\Carbon::parse($request->start_time)->format('H:i'),
                                            'end_time' => \Carbon\Carbon::parse($request->end_time)->format('H:i'),
                                            'reason' => $request->reason
                                        ]) }}">
                                <i class="fas fa-edit"></i> تعديل
                            </button>
                            @endif

                            @if((Auth::user()->hasRole('hr') && Auth::user()->hasPermissionTo('manager_respond_overtime_request')) ||
                            (Auth::user()->hasRole('hr') && $request->manager_status == 'pending' && Auth::user()->hasPermissionTo('delete_overtime')) ||
                            ($request->canDelete(Auth::user()) && $request->hr_status === 'pending' && $request->manager_status == 'pending' && Auth::user()->hasPermissionTo('delete_overtime')))
                            <form action="{{ route('overtime-requests.destroy', $request->id) }}"
                                method="POST"
                                class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center">No overtime requests found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center mt-4">
        @if($myRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $myRequests->links() }}
        @endif
    </div>

</div>

<!-- Team requests table -->
@if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-users"></i> طلبات العمل الإضافي للفريق
        </h5>
        <span class="badge bg-primary ms-2">{{ $myOvertimeHours }} ساعة معتمدة</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
                        <th>الموظف</th>
                        @endif
                        <th>التاريخ</th>
                        <th>الوقت</th>
                        <th>المدة</th>
                        <th>السبب</th>
                        <th>رد المدير</th>
                        <th>سبب رفض المدير</th>
                        <th>رد HR</th>
                        <th>سبب رفض HR</th>
                        <th>الحالة النهائية</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teamRequests as $request)
                    <tr>
                        @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
                        <td>{{ $request->user->name }}</td>
                        @endif
                        <td>{{ $request->overtime_date->format('Y-m-d') }}</td>
                        <td>
                            {{ Carbon\Carbon::parse($request->start_time)->format('H:i') }} -
                            {{ Carbon\Carbon::parse($request->end_time)->format('H:i') }}
                        </td>
                        <td>{{ $request->getFormattedDuration() }}</td>
                        <td>{{ Str::limit($request->reason, 30) }}</td>
                        <td>
                            <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($request->manager_status) }}
                            </span>
                        </td>
                        <td>{{ Str::limit($request->manager_rejection_reason, 30) }}</td>
                        <td>
                            <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($request->hr_status) }}
                            </span>
                        </td>
                        <td>{{ Str::limit($request->hr_rejection_reason, 30) }}</td>
                        <td>
                            <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <!-- زر عرض الطلب -->
                                <a href="{{ route('overtime-requests.show', $request->id) }}"
                                   class="btn btn-sm btn-info"
                                   title="عرض الطلب">
                                    <i class="fas fa-eye"></i> عرض
                                </a>

                                @if($request->canUpdate(Auth::user()) && Auth::user()->hasPermissionTo('update_overtime'))
                                <button type="button" class="btn btn-primary edit-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editOvertimeModal"
                                    data-request="{{ json_encode([
                                            'id' => $request->id,
                                            'overtime_date' => $request->overtime_date->format('Y-m-d'),
                                            'start_time' => \Carbon\Carbon::parse($request->start_time)->format('H:i'),
                                            'end_time' => \Carbon\Carbon::parse($request->end_time)->format('H:i'),
                                            'reason' => $request->reason
                                        ]) }}">
                                    <i class="fas fa-edit"></i> تعديل
                                </button>
                                @endif

                                @if($request->canDelete(Auth::user()) && Auth::user()->hasPermissionTo('delete_overtime'))
                                <form action="{{ route('overtime-requests.destroy', $request->id) }}"
                                    method="POST"
                                    class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                </form>
                                @endif

                                @if($canRespondAsManager && !Auth::user()->hasRole('hr') && Auth::user()->hasPermissionTo('manager_respond_overtime_request'))
                                @if($request->manager_status === 'pending')
                                <button type="button" class="btn btn-info respond-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#respondOvertimeModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="manager">
                                    <i class="fas fa-reply"></i> <strong>رد المدير</strong>
                                </button>
                                @else
                                <button type="button" class="btn btn-warning modify-response-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modifyResponseModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="manager"
                                    data-current-status="{{ $request->manager_status }}"
                                    data-current-reason="{{ $request->manager_rejection_reason }}">
                                    <i class="fas fa-edit"></i> <strong>تعديل رد المدير</strong>
                                </button>
                                <button type="button" class="btn btn-secondary reset-btn"
                                    onclick="resetStatus('{{ $request->id }}', 'manager')">
                                    <i class="fas fa-undo"></i> <strong>إعادة تعيين المدير</strong>
                                </button>
                                @endif
                                @endif

                                <!-- الحالة الخاصة: إذا كان مستخدم HR لديه صلاحية الاستجابة كمدير -->
                                @if(Auth::user()->hasRole('hr') && Auth::user()->hasPermissionTo('manager_respond_overtime_request') && $request->user_id !== Auth::id())
                                @if($request->manager_status === 'pending')
                                <button type="button" class="btn btn-info respond-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#respondOvertimeModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="manager">
                                    <i class="fas fa-reply"></i> <strong>رد المدير</strong>
                                </button>
                                @else
                                <button type="button" class="btn btn-warning modify-response-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modifyResponseModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="manager"
                                    data-current-status="{{ $request->manager_status }}"
                                    data-current-reason="{{ $request->manager_rejection_reason }}">
                                    <i class="fas fa-edit"></i> <strong>تعديل رد المدير</strong>
                                </button>
                                <button type="button" class="btn btn-secondary reset-btn"
                                    onclick="resetStatus('{{ $request->id }}', 'manager')">
                                    <i class="fas fa-undo"></i> <strong>إعادة تعيين المدير</strong>
                                </button>
                                @endif
                                @endif

                                @if($canRespondAsHR && Auth::user()->hasPermissionTo('hr_respond_overtime_request'))
                                @if($request->hr_status === 'pending')
                                <button type="button" class="btn btn-success respond-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#respondOvertimeModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="hr">
                                    <i class="fas fa-reply"></i> <strong>رد HR</strong>
                                </button>
                                @elseif($request->hr_status !== 'pending')
                                <button type="button" class="btn btn-sm btn-warning modify-response-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modifyResponseModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="hr"
                                    data-current-status="{{ $request->hr_status }}"
                                    data-current-reason="{{ $request->hr_rejection_reason }}">
                                    <i class="fas fa-edit"></i> <strong>تعديل رد HR</strong>
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary reset-btn"
                                    onclick="resetStatus('{{ $request->id }}', 'hr')">
                                    <i class="fas fa-undo"></i> <strong>إعادة تعيين HR</strong>
                                </button>
                                @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']) ? '11' : '10' }}"
                            class="text-center">
                            لا توجد طلبات عمل إضافي
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-4">
            @if($teamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $teamRequests->links() }}
            @endif
        </div>
    </div>
</div>
@endif


<!-- Company employees requests table (Visible only to HR) -->
@if(Auth::user()->hasRole('hr') && Auth::user()->hasPermissionTo('hr_respond_overtime_request'))
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-building"></i> طلبات العمل الإضافي لموظفي الشركة
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>التاريخ</th>
                        <th>من</th>
                        <th>إلى</th>
                        <th>المدة</th>
                        <th>السبب</th>
                        <th>رد المدير</th>
                        <th>رد HR</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hrRequests as $request)
                    <tr>
                        <td>{{ $request->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->overtime_date)->format('Y-m-d') }}</td>
                        <td>{{ $request->start_time }}</td>
                        <td>{{ $request->end_time }}</td>
                        <td>{{ $request->getFormattedDuration() }}</td>
                        <td>{{ $request->reason }}</td>
                        <td>
                            <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ $request->manager_status === 'approved' ? 'موافق' : ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ $request->hr_status === 'approved' ? 'موافق' : ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ $request->status === 'approved' ? 'معتمد' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                            </span>
                        </td>
                        <td>
                            <!-- زر عرض الطلب -->
                            <a href="{{ route('overtime-requests.show', $request->id) }}"
                               class="btn btn-sm btn-info me-1"
                               title="عرض الطلب">
                                <i class="fas fa-eye"></i> عرض
                            </a>

                            @if($canRespondAsHR && $request->hr_status === 'pending')
                            <button class="btn btn-sm btn-success respond-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#respondOvertimeModal"
                                data-request-id="{{ $request->id }}"
                                data-response-type="hr">
                                <i class="fas fa-reply"></i> <strong>رد HR</strong>
                            </button>
                            @elseif($canRespondAsHR && $request->hr_status !== 'pending')
                            <button type="button" class="btn btn-sm btn-warning modify-response-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#modifyResponseModal"
                                data-request-id="{{ $request->id }}"
                                data-response-type="hr"
                                data-current-status="{{ $request->hr_status }}"
                                data-current-reason="{{ $request->hr_rejection_reason }}">
                                <i class="fas fa-edit"></i> <strong>تعديل رد HR</strong>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary reset-btn"
                                onclick="resetStatus('{{ $request->id }}', 'hr')">
                                <i class="fas fa-undo"></i> <strong>إعادة تعيين HR</strong>
                            </button>

                            <!-- Audit History Button -->
                            <a href="{{ route('overtime-requests.audits', $request->id) }}" class="btn btn-sm btn-dark mt-1" title="عرض تاريخ التغييرات">
                                <i class="fas fa-history"></i> سجل التغييرات
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">لا توجد طلبات</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @if($hrRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $hrRequests->links() }}
            @endif
        </div>
    </div>
</div>
@endif

<!-- Employees without team table - for HR only -->
@if(Auth::user()->hasRole('hr') && $noTeamRequests->count() > 0)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Requests from Employees Without Team</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Manager Status</th>
                        <th>Manager Rejection</th>
                        <th>HR Status</th>
                        <th>HR Rejection</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($noTeamRequests as $request)
                    <tr>
                        <td>{{ $request->user->name }}</td>
                        <td>{{ $request->overtime_date->format('Y-m-d') }}</td>
                        <td>
                            {{ Carbon\Carbon::parse($request->start_time)->format('H:i') }} -
                            {{ Carbon\Carbon::parse($request->end_time)->format('H:i') }}
                        </td>
                        <td>{{ $request->getFormattedDuration() }}</td>
                        <td>{{ Str::limit($request->reason, 30) }}</td>
                        <td>
                            <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($request->manager_status) }}
                            </span>
                        </td>
                        <td>{{ Str::limit($request->manager_rejection_reason, 30) }}</td>
                        <td>
                            <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($request->hr_status) }}
                            </span>
                        </td>
                        <td>{{ Str::limit($request->hr_rejection_reason, 30) }}</td>
                        <td>
                            <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <!-- زر عرض الطلب -->
                                <a href="{{ route('overtime-requests.show', $request->id) }}"
                                   class="btn btn-sm btn-info"
                                   title="عرض الطلب">
                                    <i class="fas fa-eye"></i> عرض
                                </a>

                                @if($canRespondAsHR)
                                @if($request->hr_status === 'pending')
                                <button type="button" class="btn btn-success respond-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#respondOvertimeModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="hr">
                                    <i class="fas fa-reply"></i> <strong>رد HR</strong>
                                </button>
                                @else
                                <button type="button" class="btn btn-warning modify-response-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modifyResponseModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="hr"
                                    data-current-status="{{ $request->hr_status }}"
                                    data-current-reason="{{ $request->hr_rejection_reason }}">
                                    <i class="fas fa-edit"></i> <strong>تعديل رد HR</strong>
                                </button>
                                <button type="button" class="btn btn-secondary reset-btn"
                                    onclick="resetStatus('{{ $request->id }}', 'hr')">
                                    <i class="fas fa-undo"></i> <strong>إعادة تعيين HR</strong>
                                </button>
                                @endif
                                @endif

                                <!-- Audit History Button -->
                                <a href="{{ route('overtime-requests.audits', $request->id) }}" class="btn btn-sm btn-dark mt-1" title="عرض تاريخ التغييرات">
                                    <i class="fas fa-history"></i> سجل التغييرات
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-center mt-4">
                @if($noTeamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $noTeamRequests->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endif
