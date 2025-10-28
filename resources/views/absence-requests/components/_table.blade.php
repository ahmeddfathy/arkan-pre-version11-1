    <!-- User's Personal Absence Requests Table (For All Users) -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> طلباتي
                        @php
                        $maxAllowedDays = Auth::user()->getMaxAllowedAbsenceDays();
                        @endphp
                        <small class="ms-2">(عدد أيام الاجازة المعتمدة: {{ $myAbsenceDays }} من أصل {{ $maxAllowedDays }} يوم)</small>
                    </h5>
                    @if($canCreateAbsence)
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createAbsenceModal">
                        <i class="fas fa-plus"></i> طلب جديد
                    </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>تاريخ الاجازة</th>
                                <th>السبب</th>
                                <th>رد المدير</th>
                                <th>سبب رفض المدير</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myRequests as $request)
                            <tr class="request-row">
                                <td>{{ \Carbon\Carbon::parse($request->absence_date)->format('Y-m-d') }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->manager_status === 'approved' ? 'success' :
                                        ($request->manager_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->manager_status === 'approved' ? 'موافق عليه' :
                                            ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?: '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->hr_status === 'approved' ? 'success' :
                                        ($request->hr_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->hr_status === 'approved' ? 'موافق عليه' :
                                            ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?: '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->status === 'approved' ? 'success' :
                                        ($request->status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->status === 'approved' ? 'موافق عليه' :
                                            ($request->status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>
                                    <!-- زر عرض الطلب -->
                                    <a href="{{ route('absence-requests.show', $request->id) }}"
                                       class="btn btn-sm btn-info me-1"
                                       title="عرض الطلب">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>

                                    @if((Auth::id() === $request->user_id && $request->manager_status === 'pending' && $request->hr_status === 'pending' && $canUpdateAbsence) ||
                                    (Auth::id() === $request->user_id && Auth::user()->hasRole('hr') && $request->hr_status === 'approved' && $canUpdateAbsence))
                                    <button class="btn btn-sm btn-warning edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAbsenceModal"
                                        data-request="{{ json_encode([
                                            'id' => $request->id,
                                            'absence_date' => \Carbon\Carbon::parse($request->absence_date)->format('Y-m-d'),
                                            'reason' => $request->reason
                                        ]) }}">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    @endif

                                    @if((Auth::id() === $request->user_id && $request->manager_status === 'pending' && $request->hr_status === 'pending' && $canDeleteAbsence) ||
                                    (Auth::id() === $request->user_id && Auth::user()->hasRole('hr') && $request->hr_status === 'approved' && $canDeleteAbsence))
                                    <form action="{{ route('absence-requests.destroy', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">لا توجد طلبات اجازة</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Absence Requests (For Managers & HR Only) -->
    @if(Auth::user()->hasRole(['hr', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']))
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> طلبات اجازة التيم
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الموظف</th>
                                <th>أيام الاجازة المعتمدة</th>
                                <th>تاريخ الاجازة</th>
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
                            <tr class="request-row">
                                <td>{{ $request->user->name }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        @php
                                        $maxDays = $request->user->getMaxAllowedAbsenceDays();
                                        $approvedDays = $absenceDaysCount[$request->user_id] ?? 0;
                                        @endphp
                                        {{ $approvedDays }} من أصل {{ $maxDays }} يوم
                                    </span>
                                </td>
                                <td>{{ $request->absence_date }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->manager_status === 'approved' ? 'success' :
                                        ($request->manager_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->manager_status === 'approved' ? 'موافق' :
                                            ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->hr_status === 'approved' ? 'success' :
                                        ($request->hr_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->hr_status === 'approved' ? 'موافق' :
                                            ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->status === 'approved' ? 'success' :
                                        ($request->status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                                $request->status === 'approved' ? 'موافق' :
                                            ($request->status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>
                                    <!-- زر عرض الطلب -->
                                    <a href="{{ route('absence-requests.show', $request->id) }}"
                                       class="btn btn-sm btn-info me-1"
                                       title="عرض الطلب">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>

                                    @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) ||
                                    (Auth::user()->hasRole('hr') && $canRespondAsManager && Auth::user()->ownedTeams->contains(function($team) use ($request) {
                                    return $team->users->contains('id', $request->user_id);
                                    })))
                                    @if($request->manager_status === 'pending')
                                    @if($canRespondAsManager)
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager">
                                        <i class="fas fa-reply"></i> رد المدير
                                    </button>
                                    @endif
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager"
                                        data-status="{{ $request->manager_status }}"
                                        data-reason="{{ $request->manager_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد المدير
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="manager">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                    @endif

                                    @if(Auth::user()->hasRole('hr'))
                                    @if($request->hr_status === 'pending')
                                    @if($canRespondAsHR)
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @endif
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="hr">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                    @endif

                                    @if((Auth::id() === $request->user_id && $request->manager_status === 'pending' && $request->hr_status === 'pending' && $canUpdateAbsence) ||
                                    (Auth::id() === $request->user_id && Auth::user()->hasRole('hr') && $request->hr_status === 'approved' && $canUpdateAbsence))
                                    <button class="btn btn-sm btn-warning edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAbsenceModal"
                                        data-request="{{ json_encode([
                                            'id' => $request->id,
                                            'absence_date' => \Carbon\Carbon::parse($request->absence_date)->format('Y-m-d'),
                                            'reason' => $request->reason
                                        ]) }}">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    @endif

                                    @if((Auth::id() === $request->user_id && $request->manager_status === 'pending' && $request->hr_status === 'pending' && $canDeleteAbsence) ||
                                    (Auth::id() === $request->user_id && Auth::user()->hasRole('hr') && $request->hr_status === 'approved' && $canDeleteAbsence))
                                    <form action="{{ route('absence-requests.destroy', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center">لا توجد طلبات اجازة</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($teamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $teamRequests->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- HR Company Requests Table -->
    @if(Auth::user()->hasRole('hr'))
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="fas fa-building"></i> طلبات موظفي الشركة</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الموظف</th>
                                <th>الفريق</th>
                                <th>تاريخ الاجازة</th>
                                <th>السبب</th>
                                <th>أيام الاجازة المعتمدة</th>
                                <th>رد المدير</th>
                                <th>سبب رفض المدير</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hrRequests as $request)
                            <tr>
                                <td>
                                    <div>{{ $request->user->name }}</div>
                                    <small class="text-muted">{{ $request->user->employee_id }}</small>
                                </td>
                                <td>{{ $request->user->currentTeam ? $request->user->currentTeam->name : 'بدون فريق' }}</td>
                                <td>{{ $request->absence_date }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        @php
                                        $maxDays = $request->user->getMaxAllowedAbsenceDays();
                                        $approvedDays = $hrAbsenceDaysCount[$request->user_id] ?? 0;
                                        @endphp
                                        {{ $approvedDays }} من أصل {{ $maxDays }} يوم
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->manager_status === 'approved' ? 'success' :
                                        ($request->manager_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->manager_status === 'approved' ? 'موافق' :
                                            ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->hr_status === 'approved' ? 'success' :
                                        ($request->hr_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->hr_status === 'approved' ? 'موافق' :
                                            ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->status === 'approved' ? 'success' :
                                        ($request->status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                                $request->status === 'approved' ? 'موافق' :
                                            ($request->status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>
                                    <!-- زر عرض الطلب -->
                                    <a href="{{ route('absence-requests.show', $request->id) }}"
                                       class="btn btn-sm btn-info me-1"
                                       title="عرض الطلب">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>

                                    @if($canRespondAsHR)
                                    @if($request->hr_status === 'pending')
                                    <button class="btn btn-sm btn-success respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="hr">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                    @endif

                                    <!-- Audit History Button -->
                                    <a href="{{ route('absence-requests.audits', $request->id) }}" class="btn btn-sm btn-dark mt-1" title="عرض تاريخ التغييرات">
                                        <i class="fas fa-history"></i> سجل التغييرات
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center">لا توجد طلبات اجازة</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $hrRequests->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
    @endif

    <!-- Employees without team requests table (Visible only to HR) -->
    @if(Auth::user()->hasRole('hr') && $noTeamRequests->count() > 0)
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> طلبات الموظفين بدون فريق
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الموظف</th>
                                <th>أيام الاجازة المعتمدة</th>
                                <th>تاريخ الاجازة</th>
                                <th>السبب</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($noTeamRequests as $request)
                            <tr class="request-row">
                                <td>{{ $request->user->name }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        @php
                                        $maxDays = $request->user->getMaxAllowedAbsenceDays();
                                        $approvedDays = $noTeamAbsenceDaysCount[$request->user_id] ?? 0;
                                        @endphp
                                        {{ $approvedDays }} من أصل {{ $maxDays }} يوم
                                    </span>
                                </td>
                                <td>{{ $request->absence_date }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->hr_status === 'approved' ? 'موافق' : ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->status === 'approved' ? 'موافق' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>
                                    <!-- زر عرض الطلب -->
                                    <a href="{{ route('absence-requests.show', $request->id) }}"
                                       class="btn btn-sm btn-info me-1"
                                       title="عرض الطلب">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>

                                    @if($request->hr_status === 'pending')
                                    @if($canRespondAsHR)
                                    <button class="btn btn-sm btn-success respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @endif
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="hr">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif

                                    <!-- Audit History Button -->
                                    <a href="{{ route('absence-requests.audits', $request->id) }}" class="btn btn-sm btn-dark mt-1" title="عرض تاريخ التغييرات">
                                        <i class="fas fa-history"></i> سجل التغييرات
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $noTeamRequests->appends(['employee_name' => request('employee_name'), 'status' => request('status')])->links() }}
                </div>
            </div>
        </div>
    </div>
    @endif
