@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('content')

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card user-profile-card">
                <!-- User Header -->
                <div class="profile-header">
                    <div class="profile-cover"></div>
                    <div class="profile-avatar">
                        <div class="avatar-circle">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    </div>
                    <div class="profile-info text-center">
                        <h2 class="mb-0">{{ $user->name }}</h2>
                        <p class="text-muted">{{ $user->department }}</p>
                    </div>
                </div>

                <!-- User Details -->
                <div class="card-body pt-0">
                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Email</label>
                                    <p>{{ $user->email }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Employee ID</label>
                                    <p>{{ $user->employee_id }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Phone Number</label>
                                    <p>{{ $user->phone_number }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Age</label>
                                    <p>{{ $user->age }} years old</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Date of Birth</label>
                                    <p>{{ $user->date_of_birth }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <div class="detail-info">
                                    <label>National ID</label>
                                    <p>{{ $user->national_id_number }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Start Date</label>
                                    <p>{{ $user->start_date_of_employment }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Work Shift Information -->
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="detail-info">
                                    <label>وردية العمل</label>
                                    @if($user->workShift)
                                        <p>{{ $user->workShift->name }} ({{ $user->workShift->check_in_time->format('h:i A') }} - {{ $user->workShift->check_out_time->format('h:i A') }})</p>
                                    @else
                                        <p>غير محدد</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- User Roles -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-user-shield"></i> الأدوار الحالية للمستخدم
                                </div>
                                <div class="card-body">
                                    @if($user->roles && $user->roles->count())
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($user->roles as $role)
                                                <span class="badge bg-primary">{{ $role->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-danger">لا يوجد أدوار لهذا المستخدم</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
