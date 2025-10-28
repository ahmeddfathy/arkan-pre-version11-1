@extends('layouts.app')

@section('title', 'تقييماتي')

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
                        <i class="fas fa-user-star ml-2"></i>
                        تقييماتي
                    </h5>
                    <div>
                        <a href="{{ route('employee-evaluations.index') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-list ml-1"></i>
                            جميع التقييمات
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
                            <i class="fas fa-user-star ml-2" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            <h6>لا توجد تقييمات لك حتى الآن</h6>
                            <p class="mb-0">سيتم عرض تقييماتك هنا عند إضافتها</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
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
                                            <td>{{ $evaluation->evaluation_period }}</td>
                                            <td>{{ $evaluation->evaluation_date->format('Y-m-d') }}</td>
                                            <td>{{ $evaluation->evaluator->name }}</td>
                                            <td>
                                                <a href="{{ route('employee-evaluations.show', $evaluation) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                                </a>
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
