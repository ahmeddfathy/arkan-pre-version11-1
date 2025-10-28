@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/special-cases.css') }}">
@endpush

@section('content')
<div class="container fade-in">
    <div class="special-cases-container">
        <div class="special-cases-header">
            <h2>إضافة حالة خاصة جديدة</h2>
            <a href="{{ route('special-cases.index') }}" class="special-cases-btn special-cases-btn-primary">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>

        <div class="special-cases-card">
            <div class="special-cases-card-header">
                <span>بيانات الحالة الخاصة</span>
            </div>
            <div class="special-cases-card-body">
                <form action="{{ route('special-cases.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="special-cases-form-group">
                                <label class="special-cases-form-label">الموظف</label>
                                <select name="employee_id" class="special-cases-form-control" required>
                                    <option value="">اختر الموظف</option>
                                    @foreach($employees as $employee)
                                    <option value="{{ $employee->employee_id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="special-cases-form-group">
                                <label class="special-cases-form-label">التاريخ</label>
                                <input type="date" name="date" class="special-cases-form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="special-cases-form-group">
                                <label class="special-cases-form-label">وقت الحضور</label>
                                <input type="time" name="check_in" class="special-cases-form-control">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="special-cases-form-group">
                                <label class="special-cases-form-label">وقت الانصراف</label>
                                <input type="time" name="check_out" class="special-cases-form-control">
                            </div>
                        </div>
                    </div>

                    <div class="special-cases-form-group">
                        <label class="special-cases-form-label">السبب</label>
                        <textarea name="reason" class="special-cases-form-control" rows="4" required></textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="special-cases-btn special-cases-btn-success px-5">
                            <i class="fas fa-save me-1"></i> حفظ البيانات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
