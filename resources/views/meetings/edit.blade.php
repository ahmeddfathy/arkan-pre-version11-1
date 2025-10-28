@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/meetings.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container">
    <h2 class="page-title">{{ __('تعديل الاجتماع') }}</h2>

    <div class="meetings-page">
        <div class="arkan-container">

            <form method="POST" action="{{ route('meetings.update', $meeting) }}">
                @csrf
                @method('PUT')

                <div class="arkan-form-group">
                    <label for="title" class="arkan-form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                        </svg>
                        {{ __('عنوان الاجتماع') }}
                    </label>
                    <input id="title" class="arkan-form-control" type="text" name="title" value="{{ old('title', $meeting->title) }}" required autofocus placeholder="أدخل عنوان الاجتماع..." />
                    @error('title')
                        <p class="text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="arkan-form-group">
                    <label for="description" class="arkan-form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                        </svg>
                        {{ __('وصف الاجتماع') }}
                    </label>
                    <textarea id="description" name="description" class="arkan-form-control" rows="3" placeholder="أدخل وصف الاجتماع (اختياري)...">{{ old('description', $meeting->description) }}</textarea>
                    @error('description')
                        <p class="text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="arkan-form-group">
                    <label for="type" class="arkan-form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ __('نوع الاجتماع') }}
                    </label>
                    <select id="type" name="type" class="arkan-form-control" required>
                        <option value="internal" {{ old('type', $meeting->type) == 'internal' ? 'selected' : '' }}>اجتماع داخلي</option>
                        <option value="client" {{ old('type', $meeting->type) == 'client' ? 'selected' : '' }}>اجتماع مع عميل</option>
                    </select>
                    @error('type')
                        <p class="text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if($canViewClients)
                <div class="arkan-form-group client-field {{ $meeting->type == 'client' ? '' : 'hidden' }}">
                    <label for="client_id" class="arkan-form-label">{{ __('العميل') }}</label>
                    <select id="client_id" name="client_id" class="arkan-form-control">
                        <option value="">اختر العميل (اختياري)</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $meeting->client_id) == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <p class="text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <div class="arkan-form-group project-field {{ $meeting->type == 'client' ? '' : 'hidden' }}">
                    <label for="project_id" class="arkan-form-label">{{ __('المشروع') }}</label>
                    <select id="project_id" name="project_id" class="arkan-form-control">
                        <option value="">اختر المشروع (اختياري)</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" data-client-id="{{ $project->client_id }}" {{ old('project_id', $meeting->project_id) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                                @if($canViewClients && $project->client) - {{ $project->client->name }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')
                        <p class="text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="arkan-form-group">
                            <label for="start_time" class="arkan-form-label">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                {{ __('وقت البدء') }}
                            </label>
                            <input id="start_time" class="arkan-form-control" type="datetime-local" name="start_time" value="{{ old('start_time', $meeting->start_time->format('Y-m-d\TH:i')) }}" required />
                            @error('start_time')
                                <p class="text-danger mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="arkan-form-group">
                            <label for="end_time" class="arkan-form-label">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                {{ __('وقت الانتهاء') }}
                            </label>
                            <input id="end_time" class="arkan-form-control" type="datetime-local" name="end_time" value="{{ old('end_time', $meeting->end_time->format('Y-m-d\TH:i')) }}" required />
                            @error('end_time')
                                <p class="text-danger mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="arkan-form-group">
                    <label for="location" class="arkan-form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        {{ __('المكان') }}
                    </label>
                    <input id="location" class="arkan-form-control" type="text" name="location" value="{{ old('location', $meeting->location) }}" placeholder="أدخل مكان الاجتماع (اختياري)..." />
                    @error('location')
                        <p class="text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="arkan-form-group">
                    <label for="participants" class="arkan-form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                        </svg>
                        {{ __('المشاركين') }}
                    </label>
                    <select id="participants" name="participants[]" class="arkan-form-control select2" multiple required>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (is_array(old('participants')) && in_array($user->id, old('participants'))) || (empty(old('participants')) && in_array($user->id, $selectedParticipants)) ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-muted mt-1">اضغط مع CTRL أو CMD لاختيار متعدد</p>
                    @error('participants')
                        <p class="text-danger mt-1">{{ $message }}</p>
                    @enderror
                    @error('participants.*')
                        <p class="text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="text-end mt-4">
                    <a href="{{ route('meetings.show', $meeting) }}" class="arkan-btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        {{ __('إلغاء') }}
                    </a>
                    <button type="submit" class="arkan-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        {{ __('تحديث الاجتماع') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        const clientField = document.querySelector('.client-field');
        const projectField = document.querySelector('.project-field');
        const clientSelect = document.getElementById('client_id');
        const projectSelect = document.getElementById('project_id');

        const canViewClients = {{ $canViewClients ? 'true' : 'false' }};

        // Event listener
        typeSelect.addEventListener('change', function() {
            if (this.value === 'client') {
                if (canViewClients && clientField) clientField.classList.remove('hidden');
                projectField.classList.remove('hidden');
            } else {
                if (canViewClients && clientField) clientField.classList.add('hidden');
                projectField.classList.add('hidden');
                if (clientSelect) clientSelect.value = '';
                projectSelect.value = '';
            }
        });

        // Event listener for client selection (filter projects) - only if client select exists
        if (clientSelect) {
            clientSelect.addEventListener('change', function() {
                const selectedClientId = this.value;
                const projectOptions = projectSelect.querySelectorAll('option');

                // Reset project selection
                projectSelect.value = '';

                // Show/hide project options based on selected client
                projectOptions.forEach(option => {
                    if (option.value === '') {
                        // Always show the default option
                        option.style.display = 'block';
                    } else if (selectedClientId === '') {
                        // Show all projects if no client selected
                        option.style.display = 'block';
                    } else {
                        // Show only projects for selected client
                        const optionClientId = option.getAttribute('data-client-id');
                        option.style.display = (optionClientId === selectedClientId) ? 'block' : 'none';
                    }
                });
            });

            // Event listener for project selection (auto-select client)
            projectSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value && selectedOption.hasAttribute('data-client-id')) {
                    const clientId = selectedOption.getAttribute('data-client-id');
                    if (clientId && clientSelect) {
                        clientSelect.value = clientId;
                    }
                }
            });
        }
    });
</script>
@endpush
