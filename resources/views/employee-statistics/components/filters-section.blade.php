@php
use Carbon\Carbon;
@endphp
<!-- Filters -->
<div class="card-body">
    <form method="GET" class="row g-3 mb-4">
        <!-- فلتر القسم للمدراء و HR -->
        @if(Auth::user()->hasRole(['hr', 'company_manager', 'department_manager']))
        <div class="col-md-3">
            <label for="department" class="form-label">القسم</label>
            <select class="form-select" id="department" name="department">
                <option value="">كل الأقسام</option>
                @foreach($departments as $dept)
                <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>
                    {{ $dept }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- فلتر الموظفين -->
        @if(Auth::user()->hasRole(['hr', 'company_manager', 'department_manager', 'team_leader']))
        <div class="col-md-3">
            <label for="search" class="form-label">اختر الموظف</label>
            <select class="form-select" id="search" name="search">
                <option value="">كل الموظفين</option>
                @foreach($allUsers as $emp)
                <option value="{{ $emp->employee_id }}"
                    {{ request('search') == $emp->employee_id ? 'selected' : '' }}>
                    {{ $emp->employee_id }} - {{ $emp->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="col-md-3">
            <label for="start_date" class="form-label">من تاريخ</label>
            <input type="date"
                class="form-control"
                id="start_date"
                name="start_date"
                value="{{ $startDate }}">
        </div>

        <div class="col-md-3">
            <label for="end_date" class="form-label">إلى تاريخ</label>
            <input type="date"
                class="form-control"
                id="end_date"
                name="end_date"
                value="{{ $endDate }}">
        </div>

        <div class="col-md-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search me-1"></i> بحث
            </button>
            <a href="{{ route('employee-statistics.index') }}" class="btn btn-secondary">
                <i class="fas fa-undo me-1"></i> إعادة تعيين
            </a>
        </div>
    </form>

    <!-- عرض الفترة الحالية -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-calendar-alt me-2"></i>
        الفترة: {{ Carbon::parse($startDate)->format('Y-m-d') }} إلى {{ Carbon::parse($endDate)->format('Y-m-d') }}
    </div>
</div>
