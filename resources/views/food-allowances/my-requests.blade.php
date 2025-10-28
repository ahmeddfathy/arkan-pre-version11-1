@push('styles')
<link href="{{ asset('css/food-allowances.css') }}" rel="stylesheet">
@endpush

@extends('layouts.app')
@section('content')
<div class="container">
    <div class="food-allowance-card animate-fade-in">
        <div class="food-allowance-header">
            <h3 class="food-allowance-title">
                <i class="fas fa-utensils"></i>
                طلبات الأكل الخاصة بي
            </h3>
            <div class="header-actions">
                <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left"></i>
                    العودة للوحة التحكم
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success m-4">{{ session('success') }}</div>
        @endif

        <div class="p-4">
            <!-- Filter Section -->
            <form method="GET" class="food-allowance-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label>من تاريخ:</label>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label>إلى تاريخ:</label>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            فلتر
                        </button>
                    </div>
                </div>
            </form>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="summary-card">
                        <h4>إجمالي مصروفات الأكل</h4>
                        <div class="total">{{ number_format($totalAmount) }} جنيه</div>
                        <div class="period">في الفترة المحددة</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-card">
                        <h4>عدد الطلبات</h4>
                        <div class="total">{{ $allowances->count() }}</div>
                        <div class="period">طلب</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-card">
                        <h4>أنواع الأكل</h4>
                        <div class="total">{{ $foodTypes->count() }}</div>
                        <div class="period">نوع مختلف</div>
                    </div>
                </div>
            </div>

            <!-- Food Types Summary -->
            @if($foodTypes->isNotEmpty())
            <h4 class="mb-3">إحصائيات أنواع الأكل</h4>
            <div class="table-responsive mb-4">
                <table class="food-allowance-table">
                    <thead>
                        <tr>
                            <th>نوع الأكل</th>
                            <th>عدد الطلبات</th>
                            <th>إجمالي المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($foodTypes as $foodType => $data)
                        <tr>
                            <td>
                                <span class="food-type-badge">{{ $foodType }}</span>
                            </td>
                            <td>{{ $data['count'] }}</td>
                            <td>
                                <span class="amount-display">{{ number_format($data['total_amount']) }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <!-- My Requests Table -->
            <h4 class="mb-3">تفاصيل طلباتي</h4>
            <div class="table-responsive">
                <table class="food-allowance-table">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>نوع الأكل</th>
                            <th>المبلغ</th>
                            <th>سجلت بواسطة</th>
                            <th>تاريخ التسجيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allowances as $row)
                        <tr>
                            <td>{{ $row->date }}</td>
                            <td>
                                <span class="food-type-badge">{{ $row->food_type }}</span>
                            </td>
                            <td>
                                <span class="amount-display">{{ number_format($row->amount) }}</span>
                            </td>
                            <td>
                                <div class="employee-card">
                                    <div class="employee-avatar">
                                        @if($row->creator->profile_photo_url)
                                            <img src="{{ $row->creator->profile_photo_url }}" alt="{{ $row->creator->name }}">
                                        @else
                                            {{ substr($row->creator->name, 0, 1) }}
                                        @endif
                                    </div>
                                    <div class="employee-info">
                                        <h4>{{ $row->creator->name ?? '-' }}</h4>
                                        <p>{{ $row->creator->employee_id ?? 'HR' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $row->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                                    <h5>لا توجد طلبات أكل في هذه الفترة</h5>
                                    <p class="text-muted">جرب تغيير الفترة الزمنية أو تواصل مع HR لتسجيل طلباتك</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
