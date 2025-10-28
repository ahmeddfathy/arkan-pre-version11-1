@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/special-cases.css') }}">
@endpush

@section('content')
<div class="container fade-in">
  <div class="special-cases-container">
    <div class="special-cases-header">
      <h2>الحالات الخاصة</h2>
    </div>

    <div class="special-cases-import-section">
      <h4 class="special-cases-import-title">استيراد ملفات الحضور والانصراف</h4>
      <form action="{{ route('special-cases.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
          <div class="col-md-5">
            <div class="special-cases-form-group">
              <label class="special-cases-form-label">ملف الحضور</label>
              <div class="special-cases-file-input">
                <input type="file" name="check_in_file" id="check_in_file" class="special-cases-form-control" required>
                <label for="check_in_file" class="special-cases-file-input-label w-100">
                  <i class="fas fa-upload me-2"></i> اختر ملف الحضور
                </label>
              </div>
            </div>
          </div>
          <div class="col-md-5">
            <div class="special-cases-form-group">
              <label class="special-cases-form-label">ملف الانصراف</label>
              <div class="special-cases-file-input">
                <input type="file" name="check_out_file" id="check_out_file" class="special-cases-form-control" required>
                <label for="check_out_file" class="special-cases-file-input-label w-100">
                  <i class="fas fa-upload me-2"></i> اختر ملف الانصراف
                </label>
              </div>
            </div>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="special-cases-btn special-cases-btn-primary w-100">
              <i class="fas fa-file-import me-1"></i> استيراد
            </button>
          </div>
        </div>
      </form>
    </div>

    <div class="special-cases-card">
      <div class="special-cases-card-header">
        <span>قائمة الحالات الخاصة</span>
        <a href="{{ route('special-cases.create') }}" class="special-cases-btn special-cases-btn-success">
          <i class="fas fa-plus-circle me-1"></i> إضافة حالة جديدة
        </a>
      </div>
      <div class="special-cases-card-body">
        <table class="special-cases-table">
          <thead>
            <tr>
              <th>الموظف</th>
              <th>التاريخ</th>
              <th>وقت الحضور</th>
              <th>وقت الانصراف</th>
              <th>دقائق التأخير</th>
              <th>دقائق الانصراف المبكر</th>
              <th>السبب</th>
              <th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            @foreach($specialCases as $case)
            <tr>
              <td>{{ $case->employee->name }}</td>
              <td>{{ $case->date->format('Y-m-d') }}</td>
              <td>{{ \Carbon\Carbon::parse($case->check_in)->format('H:i') }}</td>
              <td>{{ \Carbon\Carbon::parse($case->check_out)->format('H:i') }}</td>
              <td>
                @if($case->late_minutes > 0)
                <span class="special-cases-status special-cases-status-pending">{{ $case->late_minutes }}</span>
                @else
                {{ $case->late_minutes }}
                @endif
              </td>
              <td>
                @if($case->early_leave_minutes > 0)
                <span class="special-cases-status special-cases-status-pending">{{ $case->early_leave_minutes }}</span>
                @else
                {{ $case->early_leave_minutes }}
                @endif
              </td>
              <td>{{ $case->reason }}</td>
              <td>
                <div class="special-cases-actions">
                  <a href="{{ route('special-cases.edit', $case) }}" class="special-cases-btn special-cases-btn-primary special-cases-btn-sm">
                    <i class="fas fa-edit"></i>
                  </a>
                  <form action="{{ route('special-cases.destroy', $case) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السجل؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="special-cases-btn special-cases-btn-danger special-cases-btn-sm">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
