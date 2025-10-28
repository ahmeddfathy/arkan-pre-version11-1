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
                إدارة طلبات ومدفوعات الأكل
            </h3>
        </div>

        @if(session('success'))
            <div class="alert alert-success m-4">{{ session('success') }}</div>
        @endif

        <div class="p-4">
            <!-- Filter Section -->
            <form method="GET" class="food-allowance-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label>من تاريخ:</label>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>إلى تاريخ:</label>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            فلتر
                        </button>
                    </div>
                </div>
            </form>

            <!-- Add Form -->
            <form method="POST" action="{{ route('food-allowances.store') }}" class="food-allowance-form">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label>الموظف:</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">اختر الموظف</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>تاريخ الطلب:</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label>قيمة الطلب:</label>
                        <input type="number" name="amount" step="0.01" min="0" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label>نوع الأكل:</label>
                        <select name="food_type" class="form-select" required>
                            <option value="">اختر نوع الأكل</option>
                            <option value="فطار">فطار</option>
                            <option value="غداء">غداء</option>
                            <option value="عشاء">عشاء</option>
                            <option value="وجبة خفيفة">وجبة خفيفة</option>
                            <option value="مشروبات">مشروبات</option>
                            <option value="حلويات">حلويات</option>
                            <option value="أخرى">أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-success">
                            <i class="fas fa-plus"></i>
                            تسجيل طلب الأكل
                        </button>
                    </div>
                </div>
            </form>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="summary-card">
                        <h4>إجمالي قيمة طلبات الأكل في الفترة المحددة</h4>
                        <div class="total">{{ number_format($totals->sum()) }} جنيه</div>
                        <div class="period">من {{ $startDate }} إلى {{ $endDate }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="summary-card">
                        <h4>عدد الموظفين الذين طلبوا أكل</h4>
                        <div class="total">{{ $totals->count() }}</div>
                        <div class="period">موظف</div>
                    </div>
                </div>
            </div>

            <!-- Totals Table -->
            <h4 class="mb-3">إجمالي قيمة طلبات الأكل لكل موظف في الفترة المحددة</h4>
            <div class="table-responsive mb-4">
                <table class="food-allowance-table">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>إجمالي قيمة الطلبات (جنيه)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>
                                    <div class="employee-card">
                                        <div class="employee-avatar">
                                            @if($user->profile_photo_url)
                                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                                            @else
                                                {{ substr($user->name, 0, 1) }}
                                            @endif
                                        </div>
                                        <div class="employee-info">
                                            <h4>{{ $user->name }}</h4>
                                            <p>{{ $user->employee_id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="amount-display">{{ $totals[$user->id] ?? 0 }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- All Records Table -->
            <h4 class="mb-3">سجل طلبات الأكل</h4>
            <div class="table-responsive">
                <table class="food-allowance-table">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>تاريخ الطلب</th>
                            <th>نوع الأكل</th>
                            <th>قيمة الطلب</th>
                            <th>تمت بواسطة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allowances as $row)
                            <tr>
                                <td>
                                    <div class="employee-card">
                                        <div class="employee-avatar">
                                            @if($row->user->profile_photo_url)
                                                <img src="{{ $row->user->profile_photo_url }}" alt="{{ $row->user->name }}">
                                            @else
                                                {{ substr($row->user->name, 0, 1) }}
                                            @endif
                                        </div>
                                        <div class="employee-info">
                                            <h4>{{ $row->user->name ?? '-' }}</h4>
                                            <p>{{ $row->user->employee_id ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $row->date }}</td>
                                <td>
                                    <span class="food-type-badge">{{ $row->food_type }}</span>
                                </td>
                                <td>
                                    <span class="amount-display">{{ $row->amount }}</span>
                                </td>
                                <td>{{ $row->creator->name ?? '-' }}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal{{ $row->id }}">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    <form method="POST" action="{{ route('food-allowances.destroy', $row->id) }}" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذا الطلب؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modals -->
@foreach($allowances as $row)
    <div class="modal fade food-allowance-modal" id="editModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('food-allowances.update', $row->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit"></i>
                            تعديل طلب الأكل
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>تاريخ الطلب:</label>
                            <input type="date" name="date" class="form-control" value="{{ $row->date }}" required>
                        </div>
                        <div class="mb-3">
                            <label>نوع الأكل:</label>
                            <select name="food_type" class="form-select" required>
                                <option value="">اختر نوع الأكل</option>
                                <option value="فطار" {{ $row->food_type == 'فطار' ? 'selected' : '' }}>فطار</option>
                                <option value="غداء" {{ $row->food_type == 'غداء' ? 'selected' : '' }}>غداء</option>
                                <option value="عشاء" {{ $row->food_type == 'عشاء' ? 'selected' : '' }}>عشاء</option>
                                <option value="وجبة خفيفة" {{ $row->food_type == 'وجبة خفيفة' ? 'selected' : '' }}>وجبة خفيفة</option>
                                <option value="مشروبات" {{ $row->food_type == 'مشروبات' ? 'selected' : '' }}>مشروبات</option>
                                <option value="حلويات" {{ $row->food_type == 'حلويات' ? 'selected' : '' }}>حلويات</option>
                                <option value="أخرى" {{ $row->food_type == 'أخرى' ? 'selected' : '' }}>أخرى</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>قيمة الطلب:</label>
                            <input type="number" name="amount" step="0.01" min="0" class="form-control" value="{{ $row->amount }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> حفظ التعديلات
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
