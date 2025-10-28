@extends('layouts.app')

@section('title', 'إدارة العملاء')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/clients.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>👥 إدارة العملاء</h1>
            <p>قائمة جميع العملاء المسجلين في النظام</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Statistics Row -->
        @php
            $totalClients = $clients->total();
            $monthClients = $clients->where('created_at', '>=', now()->startOfMonth())->count();
        @endphp

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $totalClients }}</div>
                <div class="stat-label">إجمالي العملاء</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $monthClients }}</div>
                <div class="stat-label">عملاء هذا الشهر</div>
            </div>
        </div>

        <!-- Clients Table -->
        <div class="clients-table-container">
            <div class="table-header">
                <h2>📋 قائمة العملاء</h2>
            </div>

            @if($clients->count() > 0)
                <table class="clients-table">
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>اسم الشركة</th>
                            <th>البريد الإلكتروني</th>
                            <th>رقم الهاتف</th>
                            <th>تاريخ الإضافة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                        <tr>
                            <td>
                                <div class="client-info">
                                    <div class="client-avatar">
                                        {{ substr($client->name, 0, 1) }}
                                    </div>
                                    <div class="client-details">
                                        <h4>{{ $client->name }}</h4>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="color: #6b7280; font-weight: 500;">
                                    {{ $client->company_name ?? 'غير محدد' }}
                                </div>
                            </td>
                            <td>
                                @if(is_array($client->emails) && count($client->emails) > 0)
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        @foreach(array_slice($client->emails, 0, 2) as $email)
                                            <span class="email-badge">
                                                <i class="fas fa-envelope"></i>
                                                {{ $email }}
                                            </span>
                                        @endforeach
                                        @if(count($client->emails) > 2)
                                            <small style="color: #6b7280;">+{{ count($client->emails) - 2 }} أخرى</small>
                                        @endif
                                    </div>
                                @else
                                    <span style="color: #9ca3af;">لا يوجد</span>
                                @endif
                            </td>
                            <td>
                                @if(is_array($client->phones) && count($client->phones) > 0)
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        @foreach(array_slice($client->phones, 0, 2) as $phone)
                                            <span class="phone-badge">
                                                <i class="fas fa-phone"></i>
                                                {{ $phone }}
                                            </span>
                                        @endforeach
                                        @if(count($client->phones) > 2)
                                            <small style="color: #6b7280;">+{{ count($client->phones) - 2 }} أخرى</small>
                                        @endif
                                    </div>
                                @else
                                    <span style="color: #9ca3af;">لا يوجد</span>
                                @endif
                            </td>
                            <td>
                                <div style="color: #6b7280; font-size: 0.9rem;">
                                    {{ $client->created_at->format('Y/m/d') }}
                                </div>
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="{{ route('clients.show', $client) }}" class="clients-btn btn-info" title="عرض">
                                        <i class="fas fa-eye"></i>
                                        عرض
                                    </a>
                                    <a href="{{ route('clients.edit', $client) }}" class="clients-btn btn-warning" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                        تعديل
                                    </a>
                                    <form action="{{ route('clients.destroy', $client) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="clients-btn btn-danger"
                                                onclick="return confirm('هل أنت متأكد من حذف هذا العميل؟')"
                                                title="حذف">
                                            <i class="fas fa-trash"></i>
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                @if($clients->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $clients->links() }}
                    </div>
                @endif
            @else
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-users"></i>
                        <h4>لا يوجد عملاء</h4>
                        <p>لم يتم إضافة أي عملاء حتى الآن</p>
                        <a href="{{ route('clients.create') }}" class="clients-btn btn-primary">
                            <i class="fas fa-plus"></i>
                            إضافة أول عميل
                        </a>
                    </td>
                </tr>
            @endif
        </div>
    </div>
</div>

<!-- FAB Button -->
<a href="{{ route('clients.create') }}" class="fab" title="إضافة عميل جديد">
    <i class="fas fa-plus"></i>
</a>
@endsection
