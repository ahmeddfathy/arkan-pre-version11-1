@extends('layouts.app')

@section('title', 'إدارة الفريق')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/teams.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">{{ __('إدارة الفريق: ') }} {{ $team->name }}</h2>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> {{ __('العودة إلى قائمة الفرق') }}
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- معلومات الفريق -->
    <div class="team-info-card">
        <div class="team-info-section">
            <h5>
                <i class="fas fa-info-circle"></i> {{ __('معلومات الفريق') }}
            </h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <p class="mb-1">
                        <strong>{{ __('اسم الفريق:') }}</strong>
                        <span class="text-muted">{{ $team->name }}</span>
                    </p>
                </div>
                <div class="col-md-6 mb-3">
                    <p class="mb-1">
                        <strong>{{ __('المالك:') }}</strong>
                        <img src="{{ $team->owner->profile_photo_url }}"
                            alt="{{ $team->owner->name }}"
                            class="member-avatar">
                        <span class="text-muted">{{ $team->owner->name }}</span>
                    </p>
                </div>
                <div class="col-md-6 mb-3">
                    <p class="mb-1">
                        <strong>{{ __('عدد الأعضاء:') }}</strong>
                        <span class="text-muted">{{ $team->users->count() }}</span>
                    </p>
                </div>
                @if($team->personal_team)
                <div class="col-md-6 mb-3">
                    <span class="team-badge personal">
                        {{ __('فريق شخصي') }}
                    </span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- إضافة عضو جديد -->
    <div class="add-member-form">
        <h5 class="team-info-section">
            <i class="fas fa-user-plus"></i> {{ __('إضافة عضو جديد') }}
        </h5>
        <form action="{{ route('admin.teams.add-member', $team) }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="user_id" class="form-label">{{ __('اختر المستخدم') }}</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">{{ __('اختر مستخدم') }}</option>
                        @foreach($availableUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->department ?? 'غير محدد' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="role" class="form-label">{{ __('الدور') }}</label>
                    <select name="role" id="role" class="form-select">
                        @if(!empty($roles))
                        @foreach($roles as $role)
                        <option value="{{ $role['key'] }}">{{ $role['name'] }}</option>
                        @endforeach
                        @else
                        <option value="editor">{{ __('Editor') }}</option>
                        @endif
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> {{ __('إضافة العضو') }}
            </button>
        </form>
    </div>

    <!-- قائمة الأعضاء -->
    <div class="members-table-container">
        <div class="card-header">
            <h5>
                <i class="fas fa-users"></i> {{ __('أعضاء الفريق') }}
            </h5>
        </div>
        @if($team->users->isEmpty())
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <h4>{{ __('لا يوجد أعضاء في هذا الفريق') }}</h4>
        </div>
        @else
        <table class="members-table">
            <thead>
                <tr>
                    <th>{{ __('الاسم') }}</th>
                    <th>{{ __('القسم') }}</th>
                    <th>{{ __('الدور') }}</th>
                    <th>{{ __('الإجراءات') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($team->users as $user)
                @if($user)
                <tr>
                    <td>
                        <div class="member-info-cell">
                            <img src="{{ $user->profile_photo_url }}"
                                alt="{{ $user->name ?? 'User' }}"
                                class="member-avatar">
                            <div class="member-details">
                                <div class="member-name">{{ $user->name ?? 'مستخدم محذوف' }}</div>
                                @if($team->user_id === $user->id)
                                <span class="member-owner-badge">{{ __('(المالك)') }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="member-department">{{ $user->department ?? 'غير محدد' }}</span>
                    </td>
                    <td>
                        @if($user->membership && Laravel\Jetstream\Jetstream::hasRoles())
                        @php
                        $role = Laravel\Jetstream\Jetstream::findRole($user->membership->role);
                        @endphp
                        <span class="member-role">{{ $role ? $role->name : __('Editor') }}</span>
                        @else
                        <span class="member-role">{{ __('Editor') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="member-actions">
                            @if($team->user_id !== $user->id)
                            <form action="{{ route('admin.teams.remove-member', [$team, $user]) }}"
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('هل أنت متأكد من إزالة هذا العضو من الفريق؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn remove">
                                    <i class="fas fa-trash"></i> {{ __('إزالة') }}
                                </button>
                            </form>
                            @else
                            <button class="action-btn" disabled>
                                <span>{{ __('مالك الفريق') }}</span>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- نقل ملكية الفريق -->
    @if(Gate::allows('transferTeamOwnership', $team) || auth()->user()->hasRole('hr'))
    <div class="transfer-ownership-section">
        <h5 class="team-info-section">
            <i class="fas fa-exchange-alt"></i> {{ __('نقل ملكية الفريق') }}
        </h5>
        <form action="{{ route('admin.teams.transfer-ownership', $team) }}"
            method="POST"
            onsubmit="return confirm('هل أنت متأكد من نقل ملكية هذا الفريق؟');">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="transfer_user_id" class="form-label">{{ __('اختر المالك الجديد') }}</label>
                    <select name="user_id"
                        id="transfer_user_id"
                        class="form-select"
                        required>
                        <option value="">{{ __('اختر مستخدم') }}</option>
                        @php
                        $allUsers = \App\Models\User::where('employee_status', 'active')->orderBy('name')->get();
                        @endphp
                        @foreach($allUsers as $user)
                        @if($team->user_id !== $user->id)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->department ?? 'غير محدد' }})</option>
                        @endif
                        @endforeach
                    </select>
                    <small class="form-text">
                        {{ __('سيتم إضافة المستخدم للفريق تلقائياً إذا لم يكن عضواً') }}
                    </small>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-exchange-alt"></i> {{ __('نقل الملكية') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif

    @if(auth()->user()->hasRole('hr') && !$team->personal_team)
    <div class="team-info-card" style="border-left-color: #dc2626; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 50%);">
        <h5 class="team-info-section" style="color: #991b1b;">
            <i class="fas fa-trash-alt"></i> {{ __('منطقة الخطر') }}
        </h5>
        <p class="text-danger mb-3">
            <strong>{{ __('حذف الفريق نهائياً') }}</strong><br>
            <small>{{ __('سيتم حذف الفريق وجميع البيانات المرتبطة به. هذا الإجراء لا يمكن التراجع عنه.') }}</small>
        </p>
        <form action="{{ route('admin.teams.destroy', $team) }}"
            method="POST"
            onsubmit="return confirm('هل أنت متأكد تماماً من حذف فريق \'{{ $team->name }}\'؟\n\nسيتم حذف:\n- جميع أعضاء الفريق من الفريق\n- جميع البيانات المرتبطة بالفريق\n\nهذا الإجراء لا يمكن التراجع عنه!');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> {{ __('حذف الفريق نهائياً') }}
            </button>
        </form>
    </div>
    @endif
</div>
@endsection