@extends('layouts.app')

@section('title', 'تعديل التقييم')

@push('styles')
<link href="{{ asset('css/employee-evaluations.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit ml-2"></i>
                        تعديل تقييم الموظف: {{ $evaluation->user->name }}
                    </h5>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('employee-evaluations.update', $evaluation) }}" id="evaluationForm">
                        @csrf
                        @method('PUT')

                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">الموظف</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" value="{{ $evaluation->user->name }}" readonly>
                                <small class="form-text text-muted">لا يمكن تغيير الموظف بعد إنشاء التقييم</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="evaluation_period" class="col-md-3 col-form-label text-md-right">فترة التقييم <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input id="evaluation_period" type="text" class="form-control @error('evaluation_period') is-invalid @enderror" name="evaluation_period" value="{{ old('evaluation_period', $evaluation->evaluation_period) }}" required>
                                <small class="form-text text-muted">مثال: الربع الأول 2023، النصف الأول 2023، سنة 2023</small>
                                @error('evaluation_period')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="evaluation_date" class="col-md-3 col-form-label text-md-right">تاريخ التقييم <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input id="evaluation_date" type="date" class="form-control @error('evaluation_date') is-invalid @enderror" name="evaluation_date" value="{{ old('evaluation_date', $evaluation->evaluation_date->format('Y-m-d')) }}" required>
                                @error('evaluation_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="notes" class="col-md-3 col-form-label text-md-right">ملاحظات عامة</label>
                            <div class="col-md-9">
                                <textarea id="notes" class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3">{{ old('notes', $evaluation->notes) }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3">
                            <i class="fas fa-star ml-2"></i>
                            تقييم المهارات
                        </h5>

                        @if($skillCategories->isEmpty() || $skillCategories->pluck('skills')->flatten()->isEmpty())
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle ml-2"></i>
                                لا توجد مهارات نشطة لتقييمها. يرجى إضافة مهارات أولاً.
                                <a href="{{ route('skills.create') }}" class="btn btn-sm btn-primary mt-2">إضافة مهارة جديدة</a>
                            </div>
                        @else
                            @foreach($skillCategories as $category)
                                @if($category->skills->isNotEmpty())
                                    <div class="skill-category">
                                        <h6>{{ $category->name }}</h6>

                                        @foreach($category->skills as $skill)
                                            <div class="skill-item">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <input type="hidden" name="skills[]" value="{{ $skill->id }}">
                                                            <label>{{ $skill->name }} <small class="text-muted">({{ $skill->max_points }} نقطة)</small></label>
                                                            <p class="small text-muted">{{ $skill->description }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>النقاط <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control" name="points[]" min="0" max="{{ $skill->max_points }}"
                                                                value="{{ old('points.'.$loop->index, isset($existingDetails[$skill->id]) ? $existingDetails[$skill->id]['points'] : 0) }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>ملاحظات</label>
                                                            <textarea class="form-control" name="comments[]" rows="2">{{ old('comments.'.$loop->index, isset($existingDetails[$skill->id]) ? $existingDetails[$skill->id]['comments'] : '') }}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach

                            <div class="form-group row mb-0 mt-4">
                                <div class="col-md-6 offset-md-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save ml-1"></i>
                                        حفظ التغييرات
                                    </button>
                                    <a href="{{ route('employee-evaluations.show', $evaluation) }}" class="btn btn-secondary mr-2">
                                        <i class="fas fa-times ml-1"></i>
                                        إلغاء
                                    </a>
                                </div>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
