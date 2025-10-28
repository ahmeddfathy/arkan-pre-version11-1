    <!-- Team details modal -->
    @if((Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr'])) && !empty($teamStatistics))
    <div class="modal fade" id="teamDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل موظفي الفريق</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>الموظف</th>
                                    <th>إجمالي الطلبات</th>
                                    <th>الطلبات المعتمدة</th>
                                    <th>الطلبات المرفوضة</th>
                                    <th>الطلبات المعلقة</th>
                                    <th>الساعات المطلوبة</th>
                                    <th>الساعات المعتمدة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teamStatistics['team_employees'] as $employee)
                                <tr>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $employee->total_requests }}</td>
                                    <td>{{ $employee->approved_requests }}</td>
                                    <td>{{ $employee->rejected_requests }}</td>
                                    <td>{{ $employee->pending_requests }}</td>
                                    <td>{{ number_format($employee->total_requested_hours, 1) }}</td>
                                    <td>{{ number_format($employee->approved_hours, 1) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    @endif


    <!-- Create Modal -->
    <div class="modal fade" id="createOvertimeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">طلب عمل إضافي جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('overtime-requests.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'company_manager', 'hr']))
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="registration_type" id="self_registration" value="self" checked>
                                <label class="form-check-label" for="self_registration">لنفسي</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="registration_type" id="other_registration" value="other">
                                <label class="form-check-label" for="other_registration">لموظف آخر</label>
                            </div>
                        </div>

                        <div class="mb-3 d-none" id="employee_select_container">
                            <label for="employee_id" class="form-label">اختر الموظف</label>
                            <select class="form-select" id="employee_id" name="employee_id">
                                <option value="">اختر موظف...</option>
                                @foreach($users as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">الرجاء اختيار موظف.</div>
                        </div>
                    </div>
                    @endif

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="overtime_date" class="form-label">تاريخ العمل الإضافي</label>
                            <input type="date" class="form-control" id="overtime_date" name="overtime_date" required>
                            <div class="invalid-feedback">الرجاء اختيار تاريخ صحيح.</div>
                        </div>

                        <div class="mb-3">
                            <label for="start_time" class="form-label">وقت البداية</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                            <div class="invalid-feedback">الرجاء اختيار وقت البداية.</div>
                        </div>

                        <div class="mb-3">
                            <label for="end_time" class="form-label">وقت النهاية</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                            <div class="invalid-feedback">الرجاء اختيار وقت النهاية.</div>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">السبب</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            <div class="invalid-feedback">الرجاء كتابة السبب.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-primary">إرسال الطلب</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editOvertimeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل طلب العمل الإضافي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editOvertimeForm" method="POST" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_overtime_date" class="form-label">تاريخ العمل الإضافي</label>
                            <input type="date" class="form-control" id="edit_overtime_date" name="overtime_date" required>
                            <div class="invalid-feedback">الرجاء اختيار تاريخ صحيح.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_start_time" class="form-label">وقت البداية</label>
                                    <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                                    <div class="invalid-feedback">الرجاء اختيار وقت البداية.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_end_time" class="form-label">وقت النهاية</label>
                                    <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                                    <div class="invalid-feedback">الرجاء اختيار وقت نهاية بعد وقت البداية.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">السبب</label>
                            <textarea class="form-control" id="edit_reason" name="reason" rows="3" required maxlength="255"></textarea>
                            <div class="invalid-feedback">الرجاء كتابة سبب العمل الإضافي.</div>
                            <div class="form-text">
                                <span id="reasonCharCount">0</span>/255 حرف
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div class="modal fade" id="respondOvertimeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">الرد على الطلب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="respondOvertimeForm" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="response_type" name="response_type">

                        <div class="mb-3">
                            <label for="response_status" class="form-label">الحالة</label>
                            <select class="form-select" id="response_status" name="status" required>
                                <option value="approved">موافق</option>
                                <option value="rejected">مرفوض</option>
                            </select>
                            <div class="invalid-feedback">الرجاء اختيار الرد.</div>
                        </div>

                        <div class="mb-3" id="rejection_reason_container" style="display: none;">
                            <label for="rejection_reason" class="form-label">سبب الرفض</label>
                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" maxlength="255"></textarea>
                            <div class="invalid-feedback">الرجاء كتابة سبب الرفض.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
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
                <div class="modal-header">
                    <h5 class="modal-title">تعديل الرد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="modifyResponseForm" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="modify_response_type" name="response_type">

                        <div class="mb-3">
                            <label for="modify_status" class="form-label">الحالة</label>
                            <select class="form-select" id="modify_status" name="status" required>
                                <option value="approved">موافق</option>
                                <option value="rejected">مرفوض</option>
                            </select>
                            <div class="invalid-feedback">الرجاء اختيار الرد.</div>
                        </div>

                        <div class="mb-3" id="modify_rejection_reason_container" style="display: none;">
                            <label for="modify_rejection_reason" class="form-label">سبب الرفض</label>
                            <textarea class="form-control" id="modify_rejection_reason" name="rejection_reason" maxlength="255"></textarea>
                            <div class="invalid-feedback">الرجاء كتابة سبب الرفض.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add the HR modal -->
    @if(Auth::user()->hasRole('hr'))
    <div id="departmentDetailsModal" class="modal fade" tabindex="-1" aria-labelledby="departmentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="departmentDetailsModalLabel">تفاصيل القسم: <span id="departmentName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <input type="hidden" id="departmentData" data-employees='@json($hrStatistics["department_employees"])'>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>الموظف</th>
                                    <th>إجمالي الطلبات</th>
                                    <th>الطلبات المعتمدة</th>
                                    <th>الطلبات المرفوضة</th>
                                    <th>الطلبات المعلقة</th>
                                    <th>الساعات المطلوبة</th>
                                    <th>الساعات المعتمدة</th>
                                </tr>
                            </thead>
                            <tbody id="departmentEmployees">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    @endif
