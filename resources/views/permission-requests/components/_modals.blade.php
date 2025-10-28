<!-- Create Modal -->
<div class="modal fade" id="createPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('permission-requests.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title">طلب استئذان جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
                    <div class="mb-4">
                        <label class="form-label fw-bold">نوع الطلب</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="registration_type" id="self_registration" value="self" checked>
                            <label class="btn btn-outline-primary" for="self_registration">
                                <i class="fas fa-user me-2"></i>لنفسي
                            </label>

                            <input type="radio" class="btn-check" name="registration_type" id="other_registration" value="other">
                            <label class="btn btn-outline-primary" for="other_registration">
                                <i class="fas fa-users me-2"></i>لموظف آخر
                            </label>
                        </div>
                    </div>

                    <div class="mb-4" id="employee_select_container" style="display: none;">
                        <label for="user_id" class="form-label">اختر الموظف</label>
                        <select name="user_id" id="user_id" class="form-select">
                            <option value="" disabled selected>اختر موظف...</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="departure_time" class="form-label">وقت المغادرة</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="departure_time"
                            name="departure_time"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="return_time" class="form-label">وقت العودة</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="return_time"
                            name="return_time"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">السبب</label>
                        <textarea class="form-control"
                            id="reason"
                            name="reason"
                            required
                            rows="3"
                            maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إرسال الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editPermissionModal" tabindex="-1" aria-labelledby="editPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPermissionModalLabel">تعديل طلب الاستئذان</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="departure_time" class="form-label">وقت المغادرة</label>
                        <input type="datetime-local" class="form-control" id="departure_time" name="departure_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="return_time" class="form-label">وقت العودة</label>
                        <input type="datetime-local" class="form-control" id="return_time" name="return_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">السبب</label>
                        <textarea class="form-control" id="reason" name="reason" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Respond Modal -->
<div class="modal fade" id="respondModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="respondForm" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title">الرد على الطلب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="response_type" id="response_type">

                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="response_status" name="status" required>
                            <option value="approved">موافق</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>

                    <div class="mb-3" id="rejection_reason_container" style="display: none;">
                        <label class="form-label">سبب الرفض</label>
                        <textarea class="form-control"
                            id="rejection_reason"
                            name="rejection_reason"
                            maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الرد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modify Response Modal -->
<div class="modal fade" id="modifyResponseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="modifyResponseForm" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="response_type" id="modify_response_type">

                <div class="modal-header border-0">
                    <h5 class="modal-title">تعديل الرد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="modify_status" name="status" required>
                            <option value="approved">موافق</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>

                    <div class="mb-3" id="modify_reason_container" style="display: none;">
                        <label class="form-label">سبب الرفض</label>
                        <textarea class="form-control"
                            id="modify_reason"
                            name="rejection_reason"
                            maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<!-- Modal for employees who exceeded the allowed limit in the team -->
<div class="modal fade" id="teamExceededLimitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">الموظفين المتجاوزين للحد المسموح في الفريق</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الموظف</th>
                                <th>الدقائق المستخدمة</th>
                                <th>تجاوز الحد بـ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statistics['team']['exceeded_employees'] ?? [] as $index => $employee)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $employee['name'] }}</td>
                                <td>{{ $employee['total_minutes'] }} دقيقة</td>
                                <td class="text-danger">{{ $employee['total_minutes'] - 180 }} دقيقة</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for employees who exceeded the allowed limit -->
<div class="modal fade" id="exceededLimitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">الموظفين المتجاوزين للحد المسموح</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الموظف</th>
                                <th>الدقائق المستخدمة</th>
                                <th>تجاوز الحد بـ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statistics['hr']['exceeded_employees'] ?? [] as $index => $employee)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $employee['name'] }}</td>
                                <td>{{ $employee['total_minutes'] }} دقيقة</td>
                                <td class="text-danger">{{ $employee['total_minutes'] - 180 }} دقيقة</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
