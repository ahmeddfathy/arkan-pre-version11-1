@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/assign-shift.css') }}">
<link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const shiftRadios = document.querySelectorAll('.form-check-input');

        shiftRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const userId = this.name.match(/\[(\d+)\]/)[1];
                const workShiftId = this.value;

                // Show loading state
                const label = this.nextElementSibling;
                const shiftType = label.querySelector('.shift-type');

                // Create and add loading overlay
                const loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'loading-overlay';
                loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
                shiftType.appendChild(loadingOverlay);

                // Disable all radio buttons for this user while saving
                const userShiftOptions = this.closest('.shift-options').querySelectorAll('input[type="radio"]');
                userShiftOptions.forEach(radio => radio.disabled = true);

                // Send AJAX request
                fetch('{{ route("users.save-single-work-shift") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        work_shift_id: workShiftId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove loading overlay
                        loadingOverlay.remove();

                        // Re-enable radio buttons
                        userShiftOptions.forEach(radio => radio.disabled = false);

                        // Show toast notification
                        const toast = document.createElement('div');
                        toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3';
                        toast.setAttribute('role', 'alert');
                        toast.innerHTML = `
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="fas fa-check-circle me-2"></i> ${data.message}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        new bootstrap.Toast(toast).show();

                        // Remove toast after 3 seconds
                        setTimeout(() => {
                            toast.remove();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Remove loading overlay
                    loadingOverlay.remove();

                    // Re-enable radio buttons
                    userShiftOptions.forEach(radio => radio.disabled = false);

                    // Show error toast
                    const toast = document.createElement('div');
                    toast.className = 'toast align-items-center text-white bg-danger border-0 position-fixed bottom-0 end-0 m-3';
                    toast.setAttribute('role', 'alert');
                    toast.innerHTML = `
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-exclamation-circle me-2"></i> حدث خطأ أثناء حفظ التغييرات
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    new bootstrap.Toast(toast).show();
                });
            });
        });
    });
</script>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="page-title">
        <h2>Assign Work Shifts to Users</h2>
        <p class="text-muted">Assign appropriate work shifts for each employee</p>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Work Shifts Assignment Card -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-clock me-2"></i> Work Shift Management</h4>
                <form action="{{ route('users.assign-work-shifts') }}" method="GET" class="d-flex search-form">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by name..." value="{{ request('search') }}" list="userNames">
                        <datalist id="userNames">
                            @foreach($allUserNames as $name)
                                <option value="{{ $name }}">
                            @endforeach
                        </datalist>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    @if(request('search'))
                        <a href="{{ route('users.assign-work-shifts') }}" class="btn btn-clear">
                            <i class="fas fa-times"></i>
                            <span>Clear</span>
                        </a>
                    @endif
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Shift</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                        <tr>
                            <td data-label="Username">
                                <span class="user-name">{{ $user->name }}</span>
                            </td>
                            <td data-label="Email">
                                <span class="user-email">{{ $user->email }}</span>
                            </td>
                            <td data-label="Department">
                                <span class="department-badge">{{ $user->department ?? 'Not Specified' }}</span>
                            </td>
                            <td data-label="Shift">
                                <div class="shift-options">
                                    @foreach ($workShifts as $workShift)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                            id="shift_{{ $user->id }}_{{ $workShift->id }}"
                                            name="work_shifts[{{ $user->id }}]"
                                            value="{{ $workShift->id }}"
                                            {{ $user->work_shift_id == $workShift->id ? 'checked' : '' }}>
                                        <label class="form-check-label" for="shift_{{ $user->id }}_{{ $workShift->id }}">
                                            <span class="shift-type {{ strpos(strtolower($workShift->name), 'morning') !== false ? 'shift-morning' : 'shift-night' }}">
                                                <i class="fas {{ strpos(strtolower($workShift->name), 'morning') !== false ? 'fa-sun' : 'fa-moon' }} me-2"></i>
                                                {{ $workShift->name }} ({{ $workShift->check_in_time->format('h:i A') }} - {{ $workShift->check_out_time->format('h:i A') }})
                                            </span>
                                        </label>
                                    </div>
                                    @endforeach
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                            id="shift_{{ $user->id }}_none"
                                            name="work_shifts[{{ $user->id }}]"
                                            value=""
                                            {{ !$user->work_shift_id ? 'checked' : '' }}>
                                        <label class="form-check-label" for="shift_{{ $user->id }}_none">
                                            <span class="shift-type shift-none">
                                                <i class="fas fa-ban me-2"></i> No Shift
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="empty-state">
                                <i class="fas fa-users-slash me-2"></i> No users available at the moment
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
