@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/special-cases.css') }}">
@endpush

@section('content')
<div class="container fade-in">
    <div class="special-cases-container">
        <div class="special-cases-header">
            <h2>تعديل الحالة الخاصة</h2>
            <a href="{{ route('special-cases.index') }}" class="special-cases-btn special-cases-btn-primary">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>

        <div class="special-cases-card">
            <div class="special-cases-card-header">
                <span>تعديل بيانات الحالة الخاصة</span>
            </div>
            <div class="special-cases-card-body">
                <form action="{{ route('special-cases.update', $specialCase) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="special-cases-form-group">
                                <label class="special-cases-form-label">الموظف</label>
                                <select name="employee_id" class="special-cases-form-control" required>
                                    <option value="">اختر الموظف</option>
                                    @foreach($employees as $employee)
                                    <option value="{{ $employee->employee_id }}"
                                        {{ $specialCase->employee_id == $employee->employee_id ? 'selected' : '' }}>
                                        {{ $employee->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="special-cases-form-group">
                                <label class="special-cases-form-label">التاريخ</label>
                                <input type="date" name="date" class="special-cases-form-control" value="{{ $specialCase->date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="special-cases-form-group">
                                <label class="special-cases-form-label">وقت الحضور</label>
                                <input type="time" name="check_in" class="special-cases-form-control" value="{{ $specialCase->check_in }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="special-cases-form-group">
                                <label class="special-cases-form-label">وقت الانصراف</label>
                                <input type="time" name="check_out" class="special-cases-form-control" value="{{ $specialCase->check_out }}">
                            </div>
                        </div>
                    </div>

                    <div class="special-cases-form-group">
                        <label class="special-cases-form-label">السبب</label>
                        <textarea name="reason" class="special-cases-form-control" rows="4" required>{{ $specialCase->reason }}</textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="special-cases-btn special-cases-btn-success px-5">
                            <i class="fas fa-save me-1"></i> حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
