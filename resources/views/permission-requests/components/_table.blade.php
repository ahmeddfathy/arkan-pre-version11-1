@php
use App\Models\PermissionRequest;
use Carbon\Carbon;
@endphp

<!-- Table for my requests -->
<div class="row justify-content-center mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clock"></i> طلبات الاستئذان
                    <small class="ms-2">
                        @php
                        $totalUsedMinutes = PermissionRequest::where('user_id', Auth::id())
                        ->where('status', 'approved')
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->sum('minutes_used');
                        @endphp
                        <span title="الحد الشهري المسموح: 180 دقيقة">
                            استخدمت {{ $totalUsedMinutes }} دقيقة في الفترة من {{ $dateStart->format('Y-m-d') }} إلى {{ $dateEnd->format('Y-m-d') }}
                            @if($totalUsedMinutes > 180)
                            <span class="text-danger">
                                (تجاوزت الحد الشهري بـ {{ $totalUsedMinutes - 180 }} دقيقة)
                            </span>
                            @endif
                        </span>
                    </small>
                </h5>
                <!-- زر إنشاء طلب جديد -->
                @if(Auth::user()->hasPermissionTo('create_permission'))
                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                    <i class="fas fa-plus"></i> طلب جديد
                </button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>وقت المغادرة</th>
                            <th>وقت العودة</th>
                            <th>المدة</th>
                            <th>السبب</th>
                            <th>رد المدير</th>
                            <th>رد HR</th>
                            <th>الحالة النهائية</th>
                            <th>حالة العودة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myRequests as $request)
                        <tr class="request-row">
                            <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                            <td>{{ $request->minutes_used }} دقيقة</td>
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
                                    {{ $request->status === 'approved' ? 'موافق' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                </span>
                            </td>
                            <td>{{ $request->getReturnStatusLabel() }}</td>
                            <td>
                                <div class="action-buttons">
                                    <!-- زر عرض الطلب -->
                                    <a href="{{ route('permission-requests.show', $request->id) }}"
                                       class="btn btn-sm btn-info me-1"
                                       title="عرض الطلب">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>

                                    @if(($request->status === 'pending' && Auth::id() === $request->user_id && $request->manager_status === 'pending' && $request->hr_status === 'pending') || (Auth::user()->hasRole('hr') && Auth::user()->hasPermissionTo('manager_respond_permission_request')))
                                    <!-- زر تعديل الطلب -->
                                    <button type="button"
                                        class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editPermissionModal"
                                        data-request-id="{{ $request->id }}"
                                        data-departure-time="{{ $request->departure_time }}"
                                        data-return-time="{{ $request->return_time }}"
                                        data-reason="{{ $request->reason }}">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>

                                    <!-- زر حذف الطلب -->
                                    <button type="button"
                                        class="btn btn-danger btn-sm delete-request"
                                        data-request-id="{{ $request->id }}">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                    @endif

                                    @if($request->status === 'approved')
                                    <div class="btn-group" role="group">
                                        @php
                                        $returnTime = \Carbon\Carbon::parse($request->return_time);
                                        $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
                                        $endOfWorkDay = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);
                                        $isBeforeEndOfDay = $now->lt($endOfWorkDay);
                                        $isSameDay = $now->isSameDay($returnTime);
                                        $departureTime = \Carbon\Carbon::parse($request->departure_time);
                                        $isAfterDeparture = $now->gte($departureTime);
                                        @endphp

                                        <div class="d-flex align-items-center">
                                            @if($request->shouldShowCountdown())
                                            <div class="countdown-timer countdown" data-return-time="{{ $returnTime->format('Y-m-d H:i:s') }}" data-shift-end-time="{{ $request->getShiftEndTime()->setDateFrom($request->departure_time)->format('Y-m-d H:i:s') }}">
                                                <div class="timer-label">الوقت المتبقي</div>
                                                <div class="timer-value"></div>
                                            </div>
                                            @else
                                            <!-- عداد بديل للتحقق من المشكلة -->
                                            <div class="p-2 bg-light border rounded mb-2">
                                                <p class="m-0"><small>العداد غير ظاهر لأن:</small></p>
                                                <p class="m-0"><small>قيمة returned_on_time: [{{ $request->returned_on_time === null ? 'null' : $request->returned_on_time }}]</small></p>
                                                <p class="m-0"><small>{{ $request->returned_on_time == 1 ? 'العودة مسجلة (عاد)' : ($request->returned_on_time == 2 ? 'لم يعد في الوقت المحدد' : 'غير محدد') }}</small></p>
                                                <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->isSameDay($returnTime) ? 'ليس في نفس اليوم' : '' }}</small></p>
                                                <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->lt(\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0)) ? 'تجاوز نهاية يوم العمل' : '' }}</small></p>
                                                <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->gte(\Carbon\Carbon::parse($request->departure_time)) ? 'لم يبدأ وقت المغادرة بعد' : '' }}</small></p>
                                            </div>
                                            @endif

                                            @php
                                            $hasStarted = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->gte(\Carbon\Carbon::parse($request->departure_time));
                                            @endphp

                                            @if($hasStarted && !in_array($request->returned_on_time, [1, 2]))
                                            <button type="button"
                                                class="btn btn-success btn-sm return-btn me-2"
                                                data-request-id="{{ $request->id }}"
                                                data-status="1">
                                                <i class="fas fa-check me-1"></i>رجع
                                            </button>

                                            <!-- تعديل زر "لم يرجع" -->
                                            @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
                                            <button type="button"
                                                class="btn btn-danger btn-sm return-btn me-2"
                                                data-request-id="{{ $request->id }}"
                                                data-status="2">
                                                <i class="fas fa-times me-1"></i>لم يرجع
                                            </button>
                                            @endif
                                            @else
                                            <div class="alert alert-warning py-1 px-2 mb-0">
                                                <small><i class="fas fa-exclamation-triangle me-1"></i>
                                                    @if(!$hasStarted)
                                                    لم يبدأ وقت المغادرة بعد
                                                    @else
                                                    تم تسجيل حالة العودة
                                                    @endif
                                                </small>
                                            </div>
                                            @endif

                                            <button type="button"
                                                class="btn btn-secondary btn-sm reset-btn"
                                                data-request-id="{{ $request->id }}"
                                                data-status="0"
                                                {{ $request->returned_on_time === false ? 'disabled' : '' }}>
                                                <i class="fas fa-undo me-1"></i>إعادة تعيين
                                            </button>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">لا توجد طلبات استئذان</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- Table for team requests -->
@if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users"></i> طلبات الفريق
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>وقت المغادرة</th>
                            <th>وقت العودة</th>
                            <th>المدة</th>
                            <th>الدقائق المتبقية</th>
                            <th>السبب</th>
                            <th>رد المدير</th>
                            <th>سبب رفض المدير</th>
                            <th>رد HR</th>
                            <th>سبب رفض HR</th>
                            <th>الحالة النهائية</th>
                            <th>حالة العودة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teamRequests as $request)
                        <tr class="request-row">
                            <!-- بيانات الطلب -->
                            <td>{{ $request->user->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                            <td>{{ $request->minutes_used }} دقيقة</td>
                            <td>
                                @php
                                $userTotalMinutes = PermissionRequest::where('user_id', $request->user_id)
                                ->where('status', 'approved')
                                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                                ->sum('minutes_used');
                                @endphp
                                <span title="الحد الشهري المسموح: 180 دقيقة">
                                    استخدم {{ $userTotalMinutes }} دقيقة في الفترة المحددة
                                    @if($userTotalMinutes > 180)
                                    <br>
                                    <small class="text-danger">
                                        (تجاوز الحد بـ {{ $userTotalMinutes - 180 }} دقيقة)
                                    </small>
                                    @endif
                                </span>
                            </td>
                            <td>{{ $request->reason }}</td>
                            <td>
                                <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ $request->manager_status === 'approved' ? 'موافق' : ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                </span>
                            </td>
                            <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
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
                            <td>{{ $request->getReturnStatusLabel() }}</td>
                            <td>
                                <div class="action-buttons">
                                    <!-- زر عرض الطلب -->
                                    <a href="{{ route('permission-requests.show', $request->id) }}"
                                       class="btn btn-sm btn-info me-1"
                                       title="عرض الطلب">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>

                                    <!-- أزرار الرد للمدراء و HR -->
                                    @if($request->canRespondAsManager(Auth::user()))
                                    @if($request->manager_status === 'pending')
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager">
                                        <i class="fas fa-reply"></i> رد المدير
                                    </button>
                                    @else
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-warning modify-response-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modifyResponseModal"
                                            data-request-id="{{ $request->id }}"
                                            data-response-type="manager"
                                            data-status="{{ $request->manager_status }}"
                                            data-reason="{{ $request->manager_rejection_reason }}">
                                            <i class="fas fa-edit"></i> تعديل الرد
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="resetStatus('{{ $request->id }}', 'manager')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </div>
                                    @endif
                                    @endif

                                    @if(Auth::user()->hasRole('hr'))
                                    @if($request->hr_status === 'pending' && $request->canRespondAsHR(Auth::user()))
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @else
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-warning modify-response-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modifyResponseModal"
                                            data-request-id="{{ $request->id }}"
                                            data-response-type="hr"
                                            data-status="{{ $request->hr_status }}"
                                            data-reason="{{ $request->hr_rejection_reason }}">
                                            <i class="fas fa-edit"></i> تعديل رد HR
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="resetStatus('{{ $request->id }}', 'hr')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </div>
                                    @endif
                                    @endif

                                    <!-- الحالة الخاصة: إذا كان مستخدم HR لديه صلاحية الاستجابة كمدير -->
                                    @if(Auth::user()->hasRole('hr') && Auth::user()->hasPermissionTo('manager_respond_permission_request') && $request->manager_status === 'pending' && !$request->canRespondAsManager(Auth::user()))
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager">
                                        <i class="fas fa-reply"></i> رد المدير
                                    </button>
                                    @endif

                                    <!-- أزرار حالة العودة -->
                                    @if($request->status === 'approved')
                                    <div class="btn-group" role="group">
                                        @php
                                        $returnTime = \Carbon\Carbon::parse($request->return_time);
                                        $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
                                        $endOfWorkDay = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);
                                        $isBeforeEndOfDay = $now->lt($endOfWorkDay);
                                        $isSameDay = $now->isSameDay($returnTime);
                                        $departureTime = \Carbon\Carbon::parse($request->departure_time);
                                        $isAfterDeparture = $now->gte($departureTime);
                                        @endphp

                                        <div class="d-flex align-items-center">
                                            @if($request->shouldShowCountdown())
                                            <div class="countdown-timer countdown" data-return-time="{{ $returnTime->format('Y-m-d H:i:s') }}" data-shift-end-time="{{ $request->getShiftEndTime()->setDateFrom($request->departure_time)->format('Y-m-d H:i:s') }}">
                                                <div class="timer-label">الوقت المتبقي</div>
                                                <div class="timer-value"></div>
                                            </div>
                                            @else
                                            <!-- عداد بديل للتحقق من المشكلة -->
                                            <div class="p-2 bg-light border rounded mb-2">
                                                <p class="m-0"><small>العداد غير ظاهر لأن:</small></p>
                                                <p class="m-0"><small>قيمة returned_on_time: [{{ $request->returned_on_time === null ? 'null' : $request->returned_on_time }}]</small></p>
                                                <p class="m-0"><small>{{ $request->returned_on_time == 1 ? 'العودة مسجلة (عاد)' : ($request->returned_on_time == 2 ? 'لم يعد في الوقت المحدد' : 'غير محدد') }}</small></p>
                                                <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->isSameDay($returnTime) ? 'ليس في نفس اليوم' : '' }}</small></p>
                                                <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->lt(\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0)) ? 'تجاوز نهاية يوم العمل' : '' }}</small></p>
                                                <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->gte(\Carbon\Carbon::parse($request->departure_time)) ? 'لم يبدأ وقت المغادرة بعد' : '' }}</small></p>
                                            </div>
                                            @endif

                                            @php
                                            $hasStarted = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->gte(\Carbon\Carbon::parse($request->departure_time));
                                            @endphp

                                            @if($hasStarted && !in_array($request->returned_on_time, [1, 2]))
                                            <button type="button"
                                                class="btn btn-success btn-sm return-btn me-2"
                                                data-request-id="{{ $request->id }}"
                                                data-status="1">
                                                <i class="fas fa-check me-1"></i>رجع
                                            </button>

                                            <!-- تعديل زر "لم يرجع" -->
                                            @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
                                            <button type="button"
                                                class="btn btn-danger btn-sm return-btn me-2"
                                                data-request-id="{{ $request->id }}"
                                                data-status="2">
                                                <i class="fas fa-times me-1"></i>لم يرجع
                                            </button>
                                            @endif
                                            @endif

                                            <button type="button"
                                                class="btn btn-secondary btn-sm reset-btn"
                                                data-request-id="{{ $request->id }}"
                                                data-status="0"
                                                {{ $request->returned_on_time === false ? 'disabled' : '' }}>
                                                <i class="fas fa-undo me-1"></i>إعادة تعيين
                                            </button>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center">لا توجد طلبات استئذان</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $teamRequests->links() }}
            </div>
        </div>
    </div>
</div>
@endif



<!-- Table for company employees (HR only) -->
@if(Auth::user()->hasRole('hr'))
<div class="row justify-content-center mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-building"></i> طلبات موظفي الشركه
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>وقت المغادرة</th>
                            <th>وقت العودة</th>
                            <th>المدة</th>
                            <th>الدقائق المتبقية</th>
                            <th>السبب</th>
                            <th>رد المدير</th>
                            <th>سبب رفض المدير</th>
                            <th>رد HR</th>
                            <th>سبب رفض HR</th>
                            <th>الحالة النهائية</th>
                            <th>حالة العودة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hrRequests as $request)
                        <tr class="request-row">
                            <td>{{ $request->user->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                            <td>{{ $request->minutes_used }} دقيقة</td>
                            <td>
                                @php
                                $userTotalMinutes = PermissionRequest::where('user_id', $request->user_id)
                                ->where('status', 'approved')
                                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                                ->sum('minutes_used');
                                @endphp
                                <span title="الحد الشهري المسموح: 180 دقيقة">
                                    استخدم {{ $userTotalMinutes }} دقيقة في الفترة المحددة
                                    @if($userTotalMinutes > 180)
                                    <br>
                                    <small class="text-danger">
                                        (تجاوز الحد بـ {{ $userTotalMinutes - 180 }} دقيقة)
                                    </small>
                                    @endif
                                </span>
                            </td>
                            <td>{{ $request->reason }}</td>
                            <td>
                                <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ $request->manager_status === 'approved' ? 'موافق' : ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                </span>
                            </td>
                            <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
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
                            <td>{{ $request->getReturnStatusLabel() }}</td>
                            <td>
                                <!-- زر عرض الطلب -->
                                <a href="{{ route('permission-requests.show', $request->id) }}"
                                   class="btn btn-sm btn-info me-1"
                                   title="عرض الطلب">
                                    <i class="fas fa-eye"></i> عرض
                                </a>

                                @if(Auth::user()->hasPermissionTo('hr_respond_permission_request'))
                                @if($request->hr_status === 'pending')
                                <button class="btn btn-sm btn-success respond-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#respondModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="hr">
                                    <i class="fas fa-reply"></i> رد HR
                                </button>
                                @else
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary"
                                        onclick="resetStatus('{{ $request->id }}', 'hr')">
                                        <i class="fas fa-undo"></i> إعادة تعيين
                                    </button>
                                </div>
                                @endif
                                @endif

                                <!-- Audit History Button -->
                                <a href="{{ route('permission-requests.audits', $request->id) }}" class="btn btn-sm btn-dark mt-1" title="عرض تاريخ التغييرات">
                                    <i class="fas fa-history"></i> سجل التغييرات
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center">لا توجد طلبات استئذان</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $hrRequests->links() }}
            </div>
        </div>
    </div>
</div>
@endif



<!-- Table for employees without a team (HR only) -->
@if(Auth::user()->hasRole('hr'))
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-users"></i> طلبات الموظفين (بدون فريق)
        </h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>وقت المغادرة</th>
                    <th>وقت العودة</th>
                    <th>المدة</th>
                    <th>الدقائق المتبقية</th>
                    <th>السبب</th>
                    <th>رد HR</th>
                    <th>سبب رفض HR</th>
                    <th>الحالة النهائية</th>
                    <th>حالة العودة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($noTeamRequests ?? [] as $request)
                <tr class="request-row">
                    <td>{{ $request->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                    <td>{{ $request->minutes_used }} دقيقة</td>
                    <td>
                        @php
                        $userTotalMinutes = PermissionRequest::where('user_id', $request->user_id)
                        ->where('status', 'approved')
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->sum('minutes_used');
                        @endphp
                        <span title="الحد الشهري المسموح: 180 دقيقة">
                            استخدم {{ $userTotalMinutes }} دقيقة في الفترة المحددة
                            @if($userTotalMinutes > 180)
                            <br>
                            <small class="text-danger">
                                (تجاوز الحد بـ {{ $userTotalMinutes - 180 }} دقيقة)
                            </small>
                            @endif
                        </span>
                    </td>
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
                    <td>{{ $request->getReturnStatusLabel() }}</td>
                    <td>
                        <div class="action-buttons">
                            <!-- زر عرض الطلب -->
                            <a href="{{ route('permission-requests.show', $request->id) }}"
                               class="btn btn-sm btn-info me-1"
                               title="عرض الطلب">
                                <i class="fas fa-eye"></i> عرض
                            </a>

                            @if($request->hr_status === 'pending')
                            @if(Auth::user()->hasPermissionTo('hr_respond_permission_request'))
                            <button class="btn btn-sm btn-success respond-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#respondModal"
                                data-request-id="{{ $request->id }}"
                                data-response-type="hr">
                                <i class="fas fa-reply"></i> رد HR
                            </button>
                            @endif
                            @else
                            <div class="btn-group">
                                <button class="btn btn-sm btn-warning modify-response-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modifyResponseModal"
                                    data-request-id="{{ $request->id }}"
                                    data-response-type="hr"
                                    data-status="{{ $request->hr_status }}"
                                    data-reason="{{ $request->hr_rejection_reason }}">
                                    <i class="fas fa-edit"></i> تعديل رد HR
                                </button>

                                <form action="{{ route('permission-requests.reset-hr-status', $request) }}"
                                    method="POST"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-sm btn-secondary"
                                        onclick="return confirm('هل أنت متأكد من إعادة تعيين الرد؟')">
                                        <i class="fas fa-undo"></i> إعادة تعيين
                                    </button>
                                </form>
                            </div>
                            @endif

                            <!-- أزرار حالة العودة -->
                            @if($request->status === 'approved')
                            <div class="btn-group" role="group">
                                @php
                                $returnTime = \Carbon\Carbon::parse($request->return_time);
                                $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
                                $endOfWorkDay = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);
                                $isBeforeEndOfDay = $now->lt($endOfWorkDay);
                                $isSameDay = $now->isSameDay($returnTime);
                                $departureTime = \Carbon\Carbon::parse($request->departure_time);
                                $isAfterDeparture = $now->gte($departureTime);
                                @endphp

                                <div class="d-flex align-items-center">
                                    @if($request->shouldShowCountdown())
                                    <div class="countdown-timer countdown" data-return-time="{{ $returnTime->format('Y-m-d H:i:s') }}" data-shift-end-time="{{ $request->getShiftEndTime()->setDateFrom($request->departure_time)->format('Y-m-d H:i:s') }}">
                                        <div class="timer-label">الوقت المتبقي</div>
                                        <div class="timer-value"></div>
                                    </div>
                                    @else
                                    <!-- عداد بديل للتحقق من المشكلة -->
                                    <div class="p-2 bg-light border rounded mb-2">
                                        <p class="m-0"><small>العداد غير ظاهر لأن:</small></p>
                                        <p class="m-0"><small>قيمة returned_on_time: [{{ $request->returned_on_time === null ? 'null' : $request->returned_on_time }}]</small></p>
                                        <p class="m-0"><small>{{ $request->returned_on_time == 1 ? 'العودة مسجلة (عاد)' : ($request->returned_on_time == 2 ? 'لم يعد في الوقت المحدد' : 'غير محدد') }}</small></p>
                                        <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->isSameDay($returnTime) ? 'ليس في نفس اليوم' : '' }}</small></p>
                                        <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->lt(\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0)) ? 'تجاوز نهاية يوم العمل' : '' }}</small></p>
                                        <p class="m-0"><small>{{ !\Carbon\Carbon::now()->setTimezone('Africa/Cairo')->gte(\Carbon\Carbon::parse($request->departure_time)) ? 'لم يبدأ وقت المغادرة بعد' : '' }}</small></p>
                                    </div>
                                    @endif

                                    @php
                                    $hasStarted = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->gte(\Carbon\Carbon::parse($request->departure_time));
                                    @endphp

                                    @if($hasStarted && !in_array($request->returned_on_time, [1, 2]))
                                    <button type="button"
                                        class="btn btn-success btn-sm return-btn me-2"
                                        data-request-id="{{ $request->id }}"
                                        data-status="1">
                                        <i class="fas fa-check me-1"></i>رجع
                                    </button>

                                    <!-- تعديل زر "لم يرجع" -->
                                    @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
                                    <button type="button"
                                        class="btn btn-danger btn-sm return-btn me-2"
                                        data-request-id="{{ $request->id }}"
                                        data-status="2">
                                        <i class="fas fa-times me-1"></i>لم يرجع
                                    </button>
                                    @endif
                                    @endif

                                    <button type="button"
                                        class="btn btn-secondary btn-sm reset-btn"
                                        data-request-id="{{ $request->id }}"
                                        data-status="0"
                                        {{ $request->returned_on_time === false ? 'disabled' : '' }}>
                                        <i class="fas fa-undo me-1"></i>إعادة تعيين
                                    </button>
                                </div>
                            </div>
                            @endif

                            <!-- Audit History Button -->
                            <a href="{{ route('permission-requests.audits', $request->id) }}" class="btn btn-sm btn-dark mt-1" title="عرض تاريخ التغييرات">
                                <i class="fas fa-history"></i> سجل التغييرات
                            </a>
                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center">لا توجد طلبات استئذان</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($noTeamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
        {{ $noTeamRequests->links() }}
        @endif
    </div>
</div>
</div>
@endif
