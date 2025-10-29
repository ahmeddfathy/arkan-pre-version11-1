@extends('layouts.app')

@section('title', 'تعديل الاجتماع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/meetings/meetings-modern.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>✏️ تعديل الاجتماع</h1>
            <p>قم بتحديث بيانات الاجتماع</p>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <div class="form-header">
                <h2>📝 تعديل تفاصيل الاجتماع</h2>
            </div>

            <div class="form-body">
                <form method="POST" action="{{ route('meetings.update', $meeting) }}">
                    @csrf
                    @method('PUT')

                    <!-- Title -->
                    <div class="form-group">
                        <label for="title" class="form-label">
                            <i class="fas fa-heading"></i>
                            عنوان الاجتماع
                        </label>
                        <input id="title" class="form-control" type="text" name="title" value="{{ old('title', $meeting->title) }}" required autofocus placeholder="أدخل عنوان الاجتماع..." />
                        @error('title')
                            <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left"></i>
                            وصف الاجتماع
                        </label>
                        <textarea id="description" name="description" class="form-textarea" rows="3" placeholder="أدخل وصف الاجتماع (اختياري)...">{{ old('description', $meeting->description) }}</textarea>
                        @error('description')
                            <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Type -->
                    <div class="form-group">
                        <label for="type" class="form-label">
                            <i class="fas fa-tag"></i>
                            نوع الاجتماع
                        </label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="internal" {{ old('type', $meeting->type) == 'internal' ? 'selected' : '' }}>اجتماع داخلي</option>
                            <option value="client" {{ old('type', $meeting->type) == 'client' ? 'selected' : '' }}>اجتماع مع عميل</option>
                        </select>
                        @error('type')
                            <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Client -->
                    @if($canViewClients)
                    <div class="form-group client-field {{ $meeting->type == 'client' ? '' : 'hidden' }}">
                        <label for="client_search" class="form-label">
                            <i class="fas fa-user-tie"></i>
                            العميل
                        </label>
                        <input
                            type="text"
                            id="client_search"
                            class="form-control"
                            list="clients_list"
                            placeholder="ابحث بالاسم أو الكود أو اختر من القائمة..."
                            autocomplete="off"
                            value="{{ old('client_id') ? $clients->firstWhere('id', old('client_id'))?->name . ' - ' . $clients->firstWhere('id', old('client_id'))?->client_code : ($meeting->client ? $meeting->client->name . ' - ' . $meeting->client->client_code : '') }}"
                        />
                        <input type="hidden" id="client_id" name="client_id" value="{{ old('client_id', $meeting->client_id) }}" />
                        <datalist id="clients_list">
                            @foreach($clients as $client)
                                <option value="{{ $client->name }} - {{ $client->client_code }}" data-id="{{ $client->id }}">
                                    {{ $client->name }} - {{ $client->client_code }}
                                </option>
                            @endforeach
                        </datalist>
                        <p style="color: #6b7280; font-size: 0.85rem; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> اختر العميل لعرض مشاريعه فقط
                        </p>
                        @error('client_id')
                            <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif

                    <!-- Project -->
                    <div class="form-group project-field {{ $meeting->type == 'client' ? '' : 'hidden' }}">
                        <label for="project_search" class="form-label">
                            <i class="fas fa-project-diagram"></i>
                            المشروع
                        </label>
                        <input
                            type="text"
                            id="project_search"
                            class="form-control"
                            list="projects_list"
                            placeholder="ابحث بالاسم أو الكود أو اختر من القائمة..."
                            autocomplete="off"
                            value="{{ old('project_id') ? $projects->firstWhere('id', old('project_id'))?->name . ' - ' . $projects->firstWhere('id', old('project_id'))?->code : ($meeting->project ? $meeting->project->name . ' - ' . $meeting->project->code : '') }}"
                        />
                        <input type="hidden" id="project_id" name="project_id" value="{{ old('project_id', $meeting->project_id) }}" />
                        <datalist id="projects_list">
                            @foreach($projects as $project)
                                <option value="{{ $project->name }} - {{ $project->code }}" data-id="{{ $project->id }}" data-client-id="{{ $project->client_id }}">
                                    {{ $project->name }} - {{ $project->code }}
                                    @if($canViewClients && $project->client) ({{ $project->client->name }}) @endif
                                </option>
                            @endforeach
                        </datalist>
                        <p style="color: #6b7280; font-size: 0.85rem; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> اختر المشروع ليتم تحديد العميل تلقائياً
                        </p>
                        @error('project_id')
                            <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Time -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_time" class="form-label">
                                    <i class="fas fa-clock"></i>
                                    وقت البدء
                                </label>
                                <input id="start_time" class="form-control" type="datetime-local" name="start_time" value="{{ old('start_time', $meeting->start_time->format('Y-m-d\TH:i')) }}" required />
                                @error('start_time')
                                    <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_time" class="form-label">
                                    <i class="fas fa-clock"></i>
                                    وقت الانتهاء
                                </label>
                                <input id="end_time" class="form-control" type="datetime-local" name="end_time" value="{{ old('end_time', $meeting->end_time->format('Y-m-d\TH:i')) }}" required />
                                @error('end_time')
                                    <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="form-group">
                        <label for="location" class="form-label">
                            <i class="fas fa-map-marker-alt"></i>
                            المكان
                        </label>
                        <input id="location" class="form-control" type="text" name="location" value="{{ old('location', $meeting->location) }}" placeholder="أدخل مكان الاجتماع (اختياري)..." />
                        @error('location')
                            <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Participants -->
                    <div class="form-group">
                        <label for="participants" class="form-label">
                            <i class="fas fa-users"></i>
                            المشاركين
                        </label>
                        <select id="participants" name="participants[]" class="form-select select2" multiple required style="height: auto; min-height: 120px;">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ (is_array(old('participants')) && in_array($user->id, old('participants'))) || (empty(old('participants')) && in_array($user->id, $selectedParticipants)) ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        <p style="color: #6b7280; font-size: 0.85rem; margin-top: 0.5rem;">اضغط مع CTRL أو CMD لاختيار متعدد</p>
                        @error('participants')
                            <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <a href="{{ route('meetings.show', $meeting) }}" class="meetings-btn btn-delete">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </a>
                        <button type="submit" class="meetings-btn">
                            <i class="fas fa-save"></i>
                            تحديث الاجتماع
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Define data outside DOMContentLoaded
    const editMeetingClientData = {!! json_encode($clients->map(function($c) {
        return ['display' => $c->name . ' - ' . $c->client_code, 'id' => (string)$c->id];
    })->values()) !!};
    const editMeetingProjectData = {!! json_encode($projects->map(function($p) {
        return [
            'display' => $p->name . ' - ' . $p->code,
            'id' => (string)$p->id,
            'client_id' => (string)$p->client_id
        ];
    })->values()) !!};
    const editCanViewClientsData = {!! json_encode($canViewClients) !!};
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        const clientField = document.querySelector('.client-field');
        const projectField = document.querySelector('.project-field');
        const clientSearchInput = document.getElementById('client_search');
        const clientIdInput = document.getElementById('client_id');
        const projectSearchInput = document.getElementById('project_search');
        const projectIdInput = document.getElementById('project_id');

        const canViewClients = editCanViewClientsData;

        // Build client map: "name - code" => id
        const clientsMap = new Map();
        editMeetingClientData.forEach(client => {
            clientsMap.set(client.display, client.id);
        });

        // Build project map: "name - code" => {id, client_id}
        const projectsMap = new Map();
        editMeetingProjectData.forEach(project => {
            projectsMap.set(project.display, {id: project.id, client_id: project.client_id});
        });

        // Initial setup: if there's a pre-selected client, filter projects on load
        if (typeSelect.value === 'client' && clientIdInput && clientIdInput.value) {
            updateProjectsList(clientIdInput.value);
        }

        // Event listener for meeting type
        typeSelect.addEventListener('change', function() {
            if (this.value === 'client') {
                if (canViewClients && clientField) clientField.classList.remove('hidden');
                if (projectField) projectField.classList.remove('hidden');
            } else {
                if (canViewClients && clientField) clientField.classList.add('hidden');
                if (projectField) projectField.classList.add('hidden');
                if (clientSearchInput) {
                    clientSearchInput.value = '';
                    clientIdInput.value = '';
                }
                if (projectSearchInput) {
                    projectSearchInput.value = '';
                    projectIdInput.value = '';
                }
            }
        });

        // Event listener for client search input
        if (clientSearchInput) {
            clientSearchInput.addEventListener('input', function() {
                const selectedValue = this.value.trim();

                // Check if the value matches a client in our map
                if (clientsMap.has(selectedValue)) {
                    const selectedClientId = clientsMap.get(selectedValue);
                    clientIdInput.value = selectedClientId;

                    // Update available projects based on selected client
                    updateProjectsList(selectedClientId);
                } else {
                    clientIdInput.value = '';
                    // Show all projects if no client is selected
                    updateProjectsList('');
                }
            });

            // Event listener for blur (when user leaves the input)
            clientSearchInput.addEventListener('blur', function() {
                // If the value doesn't match any client, clear the input
                if (!clientsMap.has(this.value.trim()) && this.value.trim() !== '') {
                    setTimeout(() => {
                        if (!clientsMap.has(this.value.trim())) {
                            this.value = '';
                            clientIdInput.value = '';
                            updateProjectsList('');
                        }
                    }, 200);
                }
            });
        }

        // Event listener for project search input
        if (projectSearchInput) {
            projectSearchInput.addEventListener('input', function() {
                const selectedValue = this.value.trim();

                // Check if the value matches a project in our map
                if (projectsMap.has(selectedValue)) {
                    const projectData = projectsMap.get(selectedValue);
                    projectIdInput.value = projectData.id;

                    // Auto-select the client if project has one
                    if (projectData.client_id && clientSearchInput) {
                        for (let [clientDisplay, clientId] of clientsMap) {
                            if (clientId === projectData.client_id) {
                                clientSearchInput.value = clientDisplay;
                                clientIdInput.value = clientId;
                                break;
                            }
                        }
                    }
                } else {
                    projectIdInput.value = '';
                }
            });

            // Event listener for blur
            projectSearchInput.addEventListener('blur', function() {
                if (!projectsMap.has(this.value.trim()) && this.value.trim() !== '') {
                    setTimeout(() => {
                        if (!projectsMap.has(this.value.trim())) {
                            this.value = '';
                            projectIdInput.value = '';
                        }
                    }, 200);
                }
            });
        }

        // Function to update projects datalist based on selected client
        function updateProjectsList(selectedClientId) {
            if (!projectSearchInput) return;

            const projectsList = document.getElementById('projects_list');
            if (!projectsList) return;

            // Clear existing options
            projectsList.innerHTML = '';

            // Add filtered projects
            editMeetingProjectData.forEach(project => {
                if (selectedClientId === '' || project.client_id === selectedClientId) {
                    const option = document.createElement('option');
                    option.value = project.display;
                    option.setAttribute('data-id', project.id);
                    option.setAttribute('data-client-id', project.client_id);
                    projectsList.appendChild(option);
                }
            });

            // Clear project selection if current project doesn't match filter
            if (projectIdInput.value) {
                const currentProjectData = Array.from(projectsMap.values()).find(p => p.id === projectIdInput.value);
                if (currentProjectData && selectedClientId && currentProjectData.client_id !== selectedClientId) {
                    projectSearchInput.value = '';
                    projectIdInput.value = '';
                }
            }
        }
    });
</script>
@endpush
@endsection
