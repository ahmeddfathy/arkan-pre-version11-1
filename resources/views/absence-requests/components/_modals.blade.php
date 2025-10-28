    <!-- Create Modal -->
    <div class="modal fade" id="createAbsenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إنشاء طلب اجازة جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('absence-requests.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <!-- اختيار الموظف (يظهر فقط للمدراء) -->
                        @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
                        <div class="mb-3">
                            <label for="user_id" class="form-label">الموظف</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">اختر الموظف...</option>
                                <option value="{{ Auth::id() }}">أنا ({{ Auth::user()->name }})</option>
                                @foreach($users->where('id', '!=', Auth::id()) as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">
                                يرجى اختيار الموظف
                            </div>
                        </div>
                        @endif

                        <!-- تاريخ الاجازة -->
                        <div class="mb-3">
                            <label for="absence_date" class="form-label">تاريخ الاجازة</label>
                            <input type="date" class="form-control" id="absence_date" name="absence_date"
                                required>
                            <div class="invalid-feedback">
                                يرجى اختيار تاريخ الاجازة
                            </div>
                        </div>

                        <!-- سبب الاجازة -->
                        <div class="mb-3">
                            <label for="reason" class="form-label">سبب الاجازة</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            <div class="invalid-feedback">
                                يرجى كتابة سبب الاجازة
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إنشاء الطلب</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editAbsenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editAbsenceForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل طلب الاجازة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_absence_date" class="form-label">تاريخ الاجازة</label>
                            <input type="date"
                                class="form-control"
                                id="edit_absence_date"
                                name="absence_date"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">السبب</label>
                            <textarea class="form-control"
                                id="edit_reason"
                                name="reason"
                                required
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">تحديث الطلب</button>
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
                    <div class="modal-header">
                        <h5 class="modal-title" id="responseTitle">الرد على الطلب</h5>
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

                        <div class="mb-3" id="response_reason_container" style="display: none;">
                            <label class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="response_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
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
                <form id="modifyResponseForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="response_type" id="modify_response_type">

                    <div class="modal-header">
                        <h5 class="modal-title">تعديل الرد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modify_status" class="form-label">الحالة</label>
                            <select class="form-select" id="modify_status" name="status" required>
                                <option value="approved">موافق</option>
                                <option value="rejected">مرفوض</option>
                            </select>
                        </div>

                        <div class="mb-3" id="modify_reason_container" style="display: none;">
                            <label for="modify_reason" class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="modify_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
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

    <!-- Exceeded Limit Employees Modal -->
    <div class="modal fade" id="exceededLimitModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">الموظفون الذين تجاوزوا الحد المسموح للاجازة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">اسم الموظف</th>
                                    <th scope="col">العمر</th>
                                    <th scope="col">الحد المسموح</th>
                                    <th scope="col">أيام الاجازة الفعلية</th>
                                    <th scope="col">نسبة التجاوز</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($statistics['team']['exceeded_employees']) && count($statistics['team']['exceeded_employees']) > 0)
                                @foreach($statistics['team']['exceeded_employees'] as $index => $employee)
                                @php
                                $age = $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->age : null;
                                $maxDays = $age && $age >= 50 ? 45 : 21;
                                $excessPercentage = round(($employee->total_days / $maxDays) * 100 - 100, 1);
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $age ?? 'غير محدد' }}</td>
                                    <td>{{ $maxDays }} يوم</td>
                                    <td>{{ $employee->total_days }} يوم</td>
                                    <td><span class="text-danger">+{{ $excessPercentage }}%</span></td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="6" class="text-center">لا يوجد موظفين تجاوزوا الحد المسموح للاجازة</td>
                                </tr>
                                @endif
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
