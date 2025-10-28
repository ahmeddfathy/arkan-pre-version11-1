<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <span class="arkan-title">{{ __('إدارة الورديات') }}</span>
        </h2>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Cairo:wght@400;600;700&display=swap">
        <link rel="stylesheet" href="{{ asset('css/work-shifts-bootstrap.css') }}">
    @endpush

    <div class="py-5">
        <div class="container">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title-header">قائمة الورديات</h3>
                    <div class="logo-decoration"></div>
                    <a href="{{ route('work-shifts.create') }}" class="btn-add-shift">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        إضافة وردية جديدة
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>وقت الحضور</th>
                                    <th>وقت الانصراف</th>
                                    <th>وقت البريك</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($workShifts as $workShift)
                                    <tr>
                                        <td>{{ $workShift->name }}</td>
                                        <td>{{ $workShift->check_in_time->format('h:i A') }}</td>
                                        <td>{{ $workShift->check_out_time->format('h:i A') }}</td>
                                        <td>
                                            @if($workShift->break_start_time && $workShift->break_end_time)
                                                {{ $workShift->break_start_time->format('h:i A') }} - {{ $workShift->break_end_time->format('h:i A') }}
                                                @if($workShift->break_duration_minutes)
                                                    <br><small class="text-muted">({{ $workShift->break_duration_minutes }} دقيقة)</small>
                                                @endif
                                            @else
                                                <span class="text-muted">لا يوجد</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $workShift->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $workShift->is_active ? 'نشطة' : 'غير نشطة' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions-container">
                                                <a href="{{ route('work-shifts.edit', $workShift) }}" class="btn-action btn-edit">
                                                    تعديل
                                                </a>

                                                <form action="{{ route('work-shifts.toggle-status', $workShift) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-action {{ $workShift->is_active ? 'btn-disable' : 'btn-enable' }}">
                                                        {{ $workShift->is_active ? 'تعطيل' : 'تفعيل' }}
                                                    </button>
                                                </form>

                                                <form action="{{ route('work-shifts.destroy', $workShift) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوردية؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-action btn-delete">
                                                        حذف
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            لا توجد ورديات مضافة حتى الآن
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($workShifts->count() > 0)
                <div class="pagination-container mx-auto my-3">
                    <span class="pagination-dot active"></span>
                    <span class="pagination-dot"></span>
                    <span class="pagination-dot"></span>
                </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @endpush
</x-app-layout>
