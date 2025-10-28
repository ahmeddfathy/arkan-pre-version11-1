<!-- Search and Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('absence-requests.index') }}" class="row g-3">
            @if(Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']))
            <div class="col-md-3">
                <label for="employee_name" class="form-label">بحث عن موظف</label>
                <input type="text" class="form-control" id="employee_name" name="employee_name"
                    value="{{ request('employee_name') }}" placeholder="ادخل اسم الموظف"
                    list="employee_names">
                <datalist id="employee_names">
                    @foreach($users as $user)
                    <option value="{{ $user->name }}">
                        @endforeach
                </datalist>
            </div>
            @endif

            <div class="col-md-2">
                <label for="status" class="form-label">تصفية حسب الحالة</label>
                <select class="form-select" id="status" name="status">
                    <option value="">كل الحالات</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>معلق</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>موافق عليه</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="from_date" class="form-label">من تاريخ</label>
                <input type="date" class="form-control" id="from_date" name="from_date"
                    value="{{ request('from_date', $currentMonthStart->format('Y-m-d')) }}">
            </div>

            <div class="col-md-2">
                <label for="to_date" class="form-label">إلى تاريخ</label>
                <input type="date" class="form-control" id="to_date" name="to_date"
                    value="{{ request('to_date', $currentMonthEnd->format('Y-m-d')) }}">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>تطبيق الفلتر
                </button>
                <a href="{{ route('absence-requests.index') }}" class="btn btn-secondary ms-2">
                    <i class="fas fa-undo me-2"></i>إعادة تعيين
                </a>
            </div>
        </form>
    </div>
</div>
