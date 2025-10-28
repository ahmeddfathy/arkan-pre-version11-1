@extends('layouts.app')

@section('title', 'تقييمات الموظفين')

@push('styles')
<link href="{{ asset('css/employee-evaluations.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-star ml-2"></i>
                        تقييمات الموظفين
                    </h5>
                    <div>
                        <a href="{{ route('employee-evaluations.my-evaluations') }}" class="btn btn-light btn-sm ml-2">
                            <i class="fas fa-user ml-1"></i>
                            تقييماتي
                        </a>
                        <a href="{{ route('employee-evaluations.create') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus-circle ml-1"></i>
                            إنشاء تقييم جديد
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                                            @if($evaluations->isEmpty())
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle ml-2" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            <h6>لا توجد تقييمات مضافة حتى الآن</h6>
                            <p class="mb-0">ابدأ بإضافة تقييم جديد للموظفين</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الموظف</th>
                                        <th>فترة التقييم</th>
                                        <th>تاريخ التقييم</th>
                                        <th>المُقيّم</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($evaluations as $evaluation)
                                        <tr>
                                            <td>{{ $loop->iteration + ($evaluations->currentPage() - 1) * $evaluations->perPage() }}</td>
                                            <td>{{ $evaluation->user->name }}</td>
                                            <td>{{ $evaluation->evaluation_period }}</td>
                                            <td>{{ $evaluation->evaluation_date->format('Y-m-d') }}</td>
                                            <td>{{ $evaluation->evaluator->name }}</td>
                                            <td>
                                                <a href="{{ route('employee-evaluations.show', $evaluation) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('employee-evaluations.edit', $evaluation) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('employee-evaluations.destroy', $evaluation) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا التقييم؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $evaluations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
