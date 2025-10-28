<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('إضافة وردية جديدة') }}
        </h2>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/work-shifts-bootstrap.css') }}">

        @endpush

    <div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">إضافة وردية جديدة</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('work-shifts.store') }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('اسم الوردية') }}</label>
                                    <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus />
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="check_in_time" class="form-label">{{ __('وقت الحضور') }}</label>
                                    <input id="check_in_time" class="form-control" type="time" name="check_in_time" value="{{ old('check_in_time') }}" required />
                                    @error('check_in_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="check_out_time" class="form-label">{{ __('وقت الانصراف') }}</label>
                                    <input id="check_out_time" class="form-control" type="time" name="check_out_time" value="{{ old('check_out_time') }}" required />
                                    @error('check_out_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Break Time Fields -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="break_start_time" class="form-label">{{ __('وقت بداية البريك') }}</label>
                                            <input id="break_start_time" class="form-control" type="time" name="break_start_time" value="{{ old('break_start_time') }}" />
                                            @error('break_start_time')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="break_end_time" class="form-label">{{ __('وقت نهاية البريك') }}</label>
                                            <input id="break_end_time" class="form-control" type="time" name="break_end_time" value="{{ old('break_end_time') }}" />
                                            @error('break_end_time')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="break_duration_minutes" class="form-label">{{ __('مدة البريك (بالدقائق)') }}</label>
                                    <input id="break_duration_minutes" class="form-control" type="number" name="break_duration_minutes" value="{{ old('break_duration_minutes') }}" min="0" />
                                    @error('break_duration_minutes')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" checked>
                                        <label class="form-check-label" for="is_active">{{ __('نشطة') }}</label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('work-shifts.index') }}" class="btn btn-secondary">
                                        {{ __('إلغاء') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('حفظ') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @endpush
</x-app-layout>
