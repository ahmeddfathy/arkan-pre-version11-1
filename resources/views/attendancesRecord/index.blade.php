@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{asset('css/attendance.css')}}">

<div class="container mt-5 attendance-section">
  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    @if(session('duplicate_count'))
    <br>
    <small>
      <i class="fas fa-info-circle"></i>
      تم تخطي {{ session('duplicate_count') }} من السجلات المتكررة.
      <a href="#" data-bs-toggle="modal" data-bs-target="#duplicatesModal" class="details-btn">عرض التفاصيل</a>
    </small>
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @elseif(session('error'))
  <div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
  </div>
  @endif

  <!-- Page Title -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title"><i class="fas fa-clipboard-list me-2"></i> سجلات الحضور والانصراف</h3>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#importModal1">
      <i class="fas fa-file-import me-2"></i> استيراد سجلات الحضور
    </button>
  </div>

  <!-- Employee Filter -->
  <div class="card mb-4">
    <div class="card-header">
      <h4 class="mb-0"><i class="fas fa-filter me-2"></i> تصفية النتائج</h4>
    </div>
    <div class="card-body">
      <form action="{{ route('attendance.records.index') }}" method="GET" class="row align-items-end" id="filter-form">
        <div class="col-md-4 mb-3">
          <label class="form-label"><i class="fas fa-user me-2"></i> الموظف:</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text"
              list="employees-list"
              id="employee-search"
              class="form-control"
              placeholder="اختر الموظف..."
              value="{{ $selectedEmployeeName }}"
              onchange="updateEmployeeFilter(this)">
          </div>

          <datalist id="employees-list">
            @foreach($employees as $employee)
            <option data-value="{{ $employee->employee_id }}" value="{{ $employee->name }}">
            @endforeach
          </datalist>

          <input type="hidden"
            name="employee_filter"
            id="employee_filter"
            value="{{ request('employee_filter') }}">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label"><i class="fas fa-calendar-alt me-2"></i> الشهر:</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
            <input type="month"
              name="month"
              class="form-control"
              value="{{ $selectedMonth }}"
              onchange="this.form.submit()">
          </div>
        </div>
        <div class="col-md-4 mb-3 d-flex">
          <button type="submit" class="btn btn-primary me-2">
            <i class="fas fa-search me-2"></i> بحث
          </button>
          @if(request('employee_filter'))
          <a href="{{ route('attendance.records.index') }}" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i> مسح الفلتر
          </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Statistics Cards -->
  @if(request('employee_filter'))
  <div class="statistics-section mb-4">
    <h4 class="mb-3"><i class="fas fa-chart-bar me-2"></i> إحصائيات الحضور والغياب - {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}</h4>
    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="card h-100 border-primary">
          <div class="card-body text-center">
            <h5 class="card-title text-primary"><i class="fas fa-check-circle me-2"></i> أيام الحضور</h5>
            <h2 class="display-4 text-primary">{{ $attendanceStats['present_days'] }}</h2>
            <p class="text-muted">يوم</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card h-100 border-danger">
          <div class="card-body text-center">
            <h5 class="card-title text-danger"><i class="fas fa-times-circle me-2"></i> أيام الغياب</h5>
            <h2 class="display-4 text-danger">{{ $attendanceStats['absent_days'] }}</h2>
            <p class="text-muted">يوم</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card h-100 border-warning">
          <div class="card-body text-center">
            <h5 class="card-title text-warning"><i class="fas fa-exclamation-triangle me-2"></i> أيام المخالفات</h5>
            <h2 class="display-4 text-warning">{{ $attendanceStats['violation_days'] }}</h2>
            <p class="text-muted">يوم</p>
          </div>
        </div>
      </div>
    </div>
    <div class="row mt-3">
      <div class="col-md-12">
        <div class="card border-success mb-3">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-percentage me-2"></i> معدل الحضور</h5>
          </div>
          <div class="card-body text-center">
            <h2 class="display-4 text-success">{{ $attendanceStats['attendance_rate'] ?? 0 }}%</h2>
            <p class="text-muted">معدل الحضور الشهري</p>
          </div>
        </div>
      </div>
    </div>
    <div class="row mt-3">
      <div class="col-md-12">
        <div class="card border-info">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i> إحصائيات التأخير</h5>
          </div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col-md-3">
                <div class="mb-3">
                  <h6 class="text-muted"><i class="fas fa-calendar-times me-2"></i> عدد أيام التأخير</h6>
                  <h3 class="text-info">{{ $attendanceStats['late_days'] }}</h3>
                  <small class="text-muted">يوم</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="mb-3">
                  <h6 class="text-muted"><i class="fas fa-hourglass-half me-2"></i> إجمالي دقائق التأخير</h6>
                  <h3 class="text-info">{{ $attendanceStats['total_delay_minutes'] }}</h3>
                  <small class="text-muted">دقيقة</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="mb-3">
                  <h6 class="text-muted"><i class="fas fa-chart-line me-2"></i> متوسط التأخير اليومي</h6>
                  <h3 class="text-info">{{ $attendanceStats['avg_delay_minutes'] }}</h3>
                  <small class="text-muted">دقيقة</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="mb-3">
                  <h6 class="text-muted"><i class="fas fa-exclamation-circle me-2"></i> أقصى تأخير</h6>
                  <h3 class="text-info">{{ $attendanceStats['max_delay_minutes'] }}</h3>
                  <small class="text-muted">دقيقة</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Attendance Records Table -->
  <div class="card">
    <div class="card-header">
      <h4 class="mb-0"><i class="fas fa-list-alt me-2"></i> سجلات الحضور</h4>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th><i class="fas fa-id-card me-1"></i> رقم الموظف</th>
              <th><i class="fas fa-calendar me-1"></i> تاريخ الحضور</th>
              <th><i class="fas fa-calendar-day me-1"></i> اليوم</th>
              <th><i class="fas fa-info-circle me-1"></i> الحالة</th>
              <th><i class="fas fa-user-clock me-1"></i> الوردية</th>
              <th><i class="fas fa-business-time me-1"></i> ساعات العمل</th>
              <th><i class="fas fa-sign-in-alt me-1"></i> وقت الدخول</th>
              <th><i class="fas fa-sign-out-alt me-1"></i> وقت الخروج</th>
              <th><i class="fas fa-clock me-1"></i> دقائق التأخير</th>
              <th><i class="fas fa-walking me-1"></i> دقائق الخروج المبكر</th>
              <th><i class="fas fa-stopwatch me-1"></i> ساعات العمل الفعلية</th>
              <th><i class="fas fa-plus-circle me-1"></i> ساعات العمل الإضافي</th>
              <th><i class="fas fa-exclamation-triangle me-1"></i> الجزاء</th>
              <th><i class="fas fa-comment me-1"></i> ملاحظات</th>
            </tr>
          </thead>
          <tbody>
            @forelse($records as $record)
            <tr>
              <td>{{ $record->employee_id }}</td>
              <td>{{ \Carbon\Carbon::parse($record->attendance_date)->format('Y-m-d') }}</td>
              <td>{{ $record->day }}</td>
              <td>
                <span class="badge {{ $record->status == 'حضـور' ? 'bg-success' : ($record->status == 'غيــاب' ? 'bg-danger' : 'bg-warning') }} text-white">
                  {{ $record->status }}
                </span>
              </td>
              <td>{{ $record->shift }}</td>
              <td>{{ $record->shift_hours }}</td>
              <td>
                @if($record->entry_time)
                  <span class="text-success"><i class="fas fa-check-circle me-1"></i> {{ $record->entry_time }}</span>
                @else
                  <span class="text-muted"><i class="fas fa-times-circle me-1"></i> لم يسجل</span>
                @endif
              </td>
              <td>
                @if($record->exit_time)
                  <span class="text-success"><i class="fas fa-check-circle me-1"></i> {{ $record->exit_time }}</span>
                @else
                  <span class="text-muted"><i class="fas fa-times-circle me-1"></i> لم يسجل</span>
                @endif
              </td>
              <td>
                @if($record->delay_minutes > 0)
                <span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i> {{ $record->delay_minutes }}</span>
                @else
                <span class="text-success"><i class="fas fa-check-circle me-1"></i> {{ $record->delay_minutes }}</span>
                @endif
              </td>
              <td>
                @if($record->early_minutes > 0)
                <span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> {{ $record->early_minutes }}</span>
                @else
                <span class="text-success"><i class="fas fa-check-circle me-1"></i> {{ $record->early_minutes }}</span>
                @endif
              </td>
              <td>{{ $record->working_hours }}</td>
              <td>
                @if($record->overtime_hours > 0)
                <span class="text-success"><i class="fas fa-plus-circle me-1"></i> {{ $record->overtime_hours }}</span>
                @else
                {{ $record->overtime_hours }}
                @endif
              </td>
              <td>
                @if($record->penalty > 0)
                <span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i> {{ $record->penalty }}</span>
                @else
                <span class="text-success"><i class="fas fa-check-circle me-1"></i> {{ $record->penalty }}</span>
                @endif
              </td>
              <td>{{ $record->notes ?: '-' }}</td>
            </tr>
            @empty
            <tr>
              <td colspan="14" class="text-center py-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <p class="text-muted">لا توجد سجلات حضور</p>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-center mt-4">
        {{ $records->links() }}
      </div>
    </div>
  </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal1" tabindex="-1" role="dialog" aria-labelledby="importModal1Label" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModal1Label"><i class="fas fa-file-import me-2"></i> استيراد سجلات الحضور</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('attendance.import') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label for="importFile" class="form-label"><i class="fas fa-file-excel me-2"></i> اختر ملف الإكسل:</label>
            <div class="custom-file">
              <input type="file" name="file" class="form-control" id="importFile" required>
            </div>
            <small class="form-text text-muted mt-2">
              <i class="fas fa-info-circle me-1"></i> يرجى اختيار ملف بتنسيق .xlsx أو .csv
            </small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times me-2"></i> إغلاق
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-file-import me-2"></i> استيراد
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@if(session('duplicates'))
<div class="modal fade" id="duplicatesModal" tabindex="-1" aria-labelledby="duplicatesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="duplicatesModalLabel">
          <i class="fas fa-exclamation-triangle text-warning me-2"></i>
          السجلات المتكررة ({{ session('duplicate_count') }})
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
        <div class="alert alert-warning">
          <i class="fas fa-info-circle me-2"></i> تم تخطي السجلات التالية لأنها موجودة مسبقاً:
        </div>
        <div class="duplicate-records">
          @foreach(explode("\n", session('duplicates')) as $record)
          @if(!empty(trim($record)) && str_contains($record, 'الموظف'))
          <div class="duplicate-record-item p-2 border-bottom">
            <i class="fas fa-user me-2"></i> {{ str_replace('- ', '', $record) }}
          </div>
          @endif
          @endforeach
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-2"></i> إغلاق
        </button>
      </div>
    </div>
  </div>
</div>
@endif

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="{{asset('js/attendance.js')}}"></script>

@endsection
