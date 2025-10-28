@php

    $canManageParticipants = $canManageParticipants ?? false;
    $canViewTeamSuggestion = $canViewTeamSuggestion ?? false;


    $canViewParticipants = true;
@endphp



@if($canViewParticipants)
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-gradient-primary text-white border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        المشاركون في المشروع
                        @if(!$canManageParticipants)
                            <span class="badge bg-light text-dark ms-2">عرض فقط</span>
                        @endif
                    </h5>
                    @if($canManageParticipants)
                        <div class="header-actions">
                            <span class="badge bg-primary me-2">
                                <i class="fas fa-check-circle me-1"></i>
                                إدارة كاملة
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">

                @if(isset($packageServices) && count($packageServices))
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th class="border-0" style="width: 15%;">
                                        <i class="fas fa-cogs me-2"></i>
                                        الخدمة
                                    </th>
                                    <th class="border-0" style="width: 50%;">
                                        <i class="fas fa-users me-2"></i>
                                        المشاركون الحاليون
                                    </th>
                                    @if($canManageParticipants)
                                        <th class="border-0" style="width: 35%;">
                                            <i class="fas fa-user-plus me-2"></i>
                                            إضافة مشاركين جدد
                                        </th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($packageServices as $service)
                                <tr class="service-row">
                                    <td class="align-middle">
                                        <div class="service-info">
                                            <span class="badge bg-primary px-2 py-1 d-inline-block">
                                                <i class="fas fa-cog me-1" style="font-size: 0.8rem;"></i>
                                                <span style="font-size: 0.85rem;">{{ $service->name }}</span>
                                            </span>
                                            @if(isset($service->department))
                                                <div class="text-muted small mt-1">
                                                    <i class="fas fa-building me-1"></i>
                                                    {{ $service->department }}
                                                </div>
                                            @endif
                                            @php
                                                $requiredRoles = $service->requiredRoles ?? collect();
                                            @endphp
                                            @if($requiredRoles->count() > 0)
                                                <div class="mt-2">
                                                    <small class="text-info"><i class="fas fa-user-tag me-1"></i>الأدوار المطلوبة:</small>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach($requiredRoles as $role)
                                                            <span class="badge bg-info text-dark" style="font-size: 0.7rem;">{{ $role->name }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        @php
                                            $currentParticipants = $project->serviceParticipants->where('service_id', $service->id);
                                        @endphp
                                        <div class="participants-container">
                                            @forelse($currentParticipants as $participant)
                                                <div class="participant-card">
                                                    @if($canManageParticipants)
                                                        @php
                                                            // البحث في البيانات الجاهزة من الكونترولر
                                                            $hasTemplateTasks = collect($participantsWithTaskStatus)
                                                                ->where('user_id', $participant->user_id)
                                                                ->where('service_id', $service->id)
                                                                ->first()['has_template_tasks'] ?? false;
                                                        @endphp

                                                        <!-- زرار الحذف - في الأعلى على اليمين -->
                                                        <span class="remove-participant-btn"
                                                            data-user-id="{{ $participant->user_id }}"
                                                            data-service-id="{{ $service->id }}"
                                                            data-project-id="{{ $project->id }}"
                                                            data-user-name="{{ optional($participant->user)->name ?? '---' }}"
                                                            data-service-name="{{ $service->name }}">
                                                            <i class="fas fa-times cursor-pointer" style="color: #dc3545;"></i>
                                                        </span>

                                                        @if(!$hasTemplateTasks)
                                                            <!-- زرار إضافة مهام قوالب - يظهر فقط إذا لم تكن هناك مهام -->
                                                            @if($participant->user)
                                                            <button class="btn btn-xs btn-outline-light p-0 template-tasks-btn"
                                                                    onclick="showTemplateTasksModal('{{ $participant->user->id }}', '{{ $participant->user->name }}', '{{ $service->id }}', '{{ $service->name }}', '{{ $project->id }}')"
                                                                    title="إضافة مهام قوالب"
                                                                    style="font-size: 10px; padding: 1px 3px !important; border-radius: 3px;">
                                                                <i class="fas fa-tasks" style="font-size: 10px; color: #17a2b8;"></i>
                                                            </button>
                                                            @endif
                                                        @endif
                                                    @endif

                                                    <div class="participant-header">
                                                        <div class="participant-avatar">
                                                            <i class="fas fa-user-circle fa-2x text-primary"></i>
                                                        </div>
                                                        <div class="participant-info">
                                                            <div class="participant-name">
                                                                <strong>{{ optional($participant->user)->name ?? '---' }}</strong>
                                                                @if($participant->project_share !== null)
                                                                    @if($participant->project_share == 0.00)
                                                                        <span class="badge bg-secondary text-white ms-2" style="font-size: 10px;">
                                                                            <i class="fas fa-eye me-1"></i>
                                                                            {{ $participant->getProjectShareLabel() }}
                                                                        </span>
                                                                    @else
                                                                        <span class="badge {{ $participant->project_share < 1.00 ? 'bg-warning text-dark' : 'bg-success' }} ms-2" style="font-size: 10px;">
                                                                            <i class="fas fa-chart-pie me-1"></i>
                                                                            {{ $participant->getProjectShareLabel() }}
                                                                        </span>
                                                                    @endif
                                                                @endif
                                                                @if($participant->role_id && $participant->role)
                                                                    <span class="badge bg-info text-dark ms-2" style="font-size: 10px;">
                                                                        <i class="fas fa-user-tag me-1"></i>
                                                                        {{ $participant->role->name }}
                                                                    </span>
                                                                @elseif($service->requiredRoles && $service->requiredRoles->count() > 0 && $canManageParticipants)
                                                                    <button type="button" class="badge bg-secondary ms-2 border-0" style="font-size: 10px; cursor: pointer;"
                                                                            data-participant-id="{{ $participant->id }}"
                                                                            data-service-id="{{ $service->id }}"
                                                                            data-user-name="{{ $participant->user->name ?? '' }}"
                                                                            onclick="openAssignRoleModal(this)">
                                                                        <i class="fas fa-user-tag me-1"></i>
                                                                        تحديد دور
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            @if($participant->deadline)
                                                                <div class="deadline-info">
                                                                    <i class="fas fa-clock text-warning me-1"></i>
                                                                    <span class="deadline-date">{{ $participant->deadline->format('Y-m-d H:i') }}</span>
                                                                    @if($participant->isOverdue())
                                                                        <span class="status-badge overdue">متأخر</span>
                                                                    @elseif($participant->isDueSoon())
                                                                        <span class="status-badge due-soon">قريب</span>
                                                                    @else
                                                                        <span class="status-badge on-time">في الوقت</span>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                @empty
                                                    <span class="text-muted">لا يوجد مشاركون بعد</span>
                                                @endforelse
                                            </div>
                                    </td>
                                    @if($canManageParticipants)
                                    <td>
                                        <div class="add-participants-container">
                                            @if($canViewTeamSuggestion)
                                                <!-- زر الاقتراح الذكي -->
                                                <div class="mb-3 text-center">
                                                    <button type="button" class="btn btn-sm btn-warning smart-suggestion-btn"
                                                            data-service-id="{{ $service->id }}"
                                                            data-service-name="{{ $service->name }}"
                                                            data-project-id="{{ $project->id }}">
                                                        <i class="fas fa-brain me-1"></i>
                                                        اقتراح ذكي للفريق
                                                    </button>
                                                </div>
                                            @endif

                                            @if(isset($isOperationAssistantOnly) && $isOperationAssistantOnly)
                                                {{-- للمساعد التشغيلي فقط: إما فرق أو موظفين حسب وجود الفرق --}}
                                            @endif

                                            @if(isset($isOperationAssistantOnly) && $isOperationAssistantOnly)
                                                {{-- للمساعد التشغيلي فقط: إما فرق أو موظفين حسب وجود الفرق --}}
                                                @if(isset($departmentTeams[$service->department]) && $departmentTeams[$service->department]->count() > 0)
                                                    {{-- يوجد فرق للقسم: اختيار فريق فقط --}}
                                                    <form action="{{ route('projects.assignParticipants', $project) }}" method="POST" class="add-team-owner-form">
                                                        @csrf
                                                        <input type="hidden" name="service_id" value="{{ $service->id }}">
                                                        <div class="row g-2 align-items-center">
                                                            <div class="col">
                                                                <select name="team_id" class="form-select team-select-for-owner" data-service-id="{{ $service->id }}" required>
                                                                    <option value="">-- اختر فريق --</option>
                                                                    @foreach($departmentTeams[$service->department] as $team)
                                                                        <option value="{{ $team->id }}">{{ $team->name }} ({{ $team->owner_name }})</option>
                                                                    @endforeach
                                                                </select>
                                                                <small class="text-muted">سيتم إضافة مالك الفريق تلقائياً</small>
                                                            </div>
                                                            <div class="col-auto">
                                                                <button type="submit" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-users me-1"></i> إضافة الفريق
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @else
                                                    {{-- لا يوجد فرق للقسم: اختيار موظفين عادي --}}
                                                    <form action="{{ route('projects.assignParticipants', $project) }}" method="POST" class="add-participant-form">
                                                        @csrf
                                                        <div class="row g-2 align-items-center">
                                                            <div class="col">
                                                                <select name="participants[{{ $service->id }}][]" class="form-select participant-select" data-service="{{ $service->id }}">
                                                                    <option value="">-- اختر موظف --</option>
                                                                    @if(isset($serviceUsersMap[$service->id]))
                                                                        @foreach($serviceUsersMap[$service->id] as $user)
                                                                            @if(!$currentParticipants->pluck('user_id')->contains($user->id))
                                                                                <option value="{{ $user->id }}" data-user-roles="{{ $user->roles->pluck('name')->implode(',') }}">{{ $user->name }}</option>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                </select>
                                                            </div>

                                                            @if($service->requiredRoles && $service->requiredRoles->count() > 0)
                                                            <div class="col">
                                                                <select name="participant_role[{{ $service->id }}]" class="form-select" required>
                                                                    <option value="">-- اختر الدور --</option>
                                                                    @foreach($service->requiredRoles as $role)
                                                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            @endif

                                                            <div class="col-auto">
                                                                <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="openDeadlineModal(this)" title="تحديد موعد نهائي">
                                                                    <i class="fas fa-calendar-plus"></i>
                                                                </button>
                                                                <button type="submit" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-plus me-1"></i> إضافة
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @endif
                                            @else
                                                {{-- للمدراء والأدمن: النظام الكامل --}}
                                                <form action="{{ route('projects.assignParticipants', $project) }}" method="POST" class="add-participant-form">
                                                    @csrf
                                                    <div class="row g-2 align-items-center">
                                                        <!-- اختيار نوع الإضافة -->
                                                        <div class="col-12 mb-2">
                                                            <div class="btn-group w-100" role="group">
                                                                <input type="radio" class="btn-check" name="add_type_{{ $service->id }}" id="individual_{{ $service->id }}" value="individual" checked>
                                                                <label class="btn btn-outline-primary" for="individual_{{ $service->id }}">
                                                                    <i class="fas fa-user me-1"></i> فردي
                                                                </label>

                                                                @if(isset($departmentTeams[$service->department]) && $departmentTeams[$service->department]->count() > 0)
                                                                    <input type="radio" class="btn-check" name="add_type_{{ $service->id }}" id="team_{{ $service->id }}" value="team">
                                                                    <label class="btn btn-outline-info" for="team_{{ $service->id }}">
                                                                        <i class="fas fa-users me-1"></i> فريق
                                                                    </label>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- اختيار فردي -->
                                                        <div class="col individual-selection" id="individual_section_{{ $service->id }}">
                                                            <select name="participants[{{ $service->id }}][]" class="form-select participant-select" data-service="{{ $service->id }}">
                                                                <option value="">-- اختر موظف --</option>
                                                                @if(isset($serviceUsersMap[$service->id]))
                                                                    @foreach($serviceUsersMap[$service->id] as $user)
                                                                        @if(!$currentParticipants->pluck('user_id')->contains($user->id))
                                                                            <option value="{{ $user->id }}" data-user-roles="{{ $user->roles->pluck('name')->implode(',') }}">{{ $user->name }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                        </div>




                                                        <!-- اختيار الفريق -->
                                                        @if(isset($departmentTeams[$service->department]) && $departmentTeams[$service->department]->count() > 0)
                                                            <div class="col team-selection" id="team_section_{{ $service->id }}" style="display: none;">
                                                                <select name="team_id" class="form-select team-select" data-service-id="{{ $service->id }}">
                                                                    <option value="">-- اختر فريق --</option>
                                                                    @foreach($departmentTeams[$service->department] as $team)
                                                                        <option value="{{ $team->id }}">{{ $team->name }} ({{ $team->owner_name }})</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <!-- اختيار أعضاء الفريق -->
                                                            <div class="col team-members-selection" id="team_members_section_{{ $service->id }}" style="display: none;">
                                                                <select name="participants[{{ $service->id }}][]" class="form-select" id="team_members_select_{{ $service->id }}" multiple>
                                                                    <option value="">-- اختر أعضاء الفريق --</option>
                                                                </select>
                                                                <small class="text-muted">يمكنك اختيار أكثر من عضو بالضغط على Ctrl</small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="col-auto">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-plus me-1"></i> إضافة
                                                        </button>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                @elseif(isset($serviceUsersMap) && count($serviceUsersMap))
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>الخدمة</th>
                                    <th>المشاركون الحاليون</th>
                                    @if($canManageParticipants)
                                        <th>إضافة مشاركين جدد</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($project->services as $service)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary px-2 py-1 d-inline-block">
                                            <span style="font-size: 0.85rem;">{{ $service->name }}</span>
                                        </span>
                                        @if(isset($service->department))
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-building me-1"></i> قسم: {{ $service->department }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                                @php
                                                    $currentParticipants = $project->serviceParticipants->where('service_id', $service->id);
                                                @endphp
                                        <div class="d-flex flex-wrap gap-1">
                                                @forelse($currentParticipants as $participant)
                                                <div class="badge bg-primary mb-1 p-2 position-relative">
                                                    @if($canManageParticipants)
                                                        @php
                                                            // البحث في البيانات الجاهزة من الكونترولر
                                                            $hasTemplateTasks = collect($participantsWithTaskStatus)
                                                                ->where('user_id', $participant->user_id)
                                                                ->where('service_id', $service->id)
                                                                ->first()['has_template_tasks'] ?? false;
                                                        @endphp

                                                        <!-- زرار الحذف - في الأعلى على اليمين -->
                                                        <span class="remove-participant-btn-badge"
                                                            data-user-id="{{ $participant->user_id }}"
                                                            data-service-id="{{ $service->id }}"
                                                            data-project-id="{{ $project->id }}"
                                                            data-user-name="{{ optional($participant->user)->name ?? '---' }}"
                                                            data-service-name="{{ $service->name }}">
                                                            <i class="fas fa-times cursor-pointer" style="color: #dc3545;"></i>
                                                        </span>

                                                        @if(!$hasTemplateTasks)
                                                            <!-- زرار إضافة مهام قوالب - يظهر فقط إذا لم تكن هناك مهام -->
                                                            @if($participant->user)
                                                            <button class="btn btn-xs btn-outline-light p-0 template-tasks-btn-badge"
                                                                    onclick="showTemplateTasksModal('{{ $participant->user->id }}', '{{ $participant->user->name }}', '{{ $service->id }}', '{{ $service->name }}', '{{ $project->id }}')"
                                                                    title="إضافة مهام قوالب"
                                                                    style="font-size: 10px; padding: 1px 3px !important; border-radius: 3px;">
                                                                <i class="fas fa-tasks" style="font-size: 10px; color: #17a2b8;"></i>
                                                            </button>
                                                            @endif
                                                        @endif
                                                    @endif

                                                    <i class="fas fa-user me-1"></i>
                                                    {{ optional($participant->user)->name ?? '---' }}
                                                    @if($participant->project_share)
                                                        <span class="badge {{ $participant->project_share < 1.00 ? 'bg-warning text-dark' : 'bg-light text-dark' }} ms-1" style="font-size: 9px;">
                                                            {{ $participant->getProjectShareLabel() }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @empty
                                                    <span class="text-muted">لا يوجد مشاركون بعد</span>
                                                @endforelse
                                            </div>
                                    </td>
                                    @if($canManageParticipants)
                                    <td>
                                        <div class="add-participants-container">
                                            @if($canViewTeamSuggestion)
                                                <!-- زر الاقتراح الذكي -->
                                                <div class="mb-3 text-center">
                                                    <button type="button" class="btn btn-sm btn-warning smart-suggestion-btn"
                                                            data-service-id="{{ $service->id }}"
                                                            data-service-name="{{ $service->name }}"
                                                            data-project-id="{{ $project->id }}">
                                                        <i class="fas fa-brain me-1"></i>
                                                        اقتراح ذكي للفريق
                                                    </button>
                                                </div>
                                            @endif

                                            @if(isset($isOperationAssistantOnly) && $isOperationAssistantOnly)
                                                {{-- للمساعد التشغيلي فقط: إما فرق أو موظفين حسب وجود الفرق --}}
                                            @endif

                                            @if(isset($isOperationAssistantOnly) && $isOperationAssistantOnly)
                                                {{-- للمساعد التشغيلي فقط: إما فرق أو موظفين حسب وجود الفرق --}}
                                                @if(isset($departmentTeams[$service->department]) && $departmentTeams[$service->department]->count() > 0)
                                                    {{-- يوجد فرق للقسم: اختيار فريق فقط --}}
                                                    <form action="{{ route('projects.assignParticipants', $project) }}" method="POST" class="add-team-owner-form">
                                                        @csrf
                                                        <input type="hidden" name="service_id" value="{{ $service->id }}">
                                                        <div class="row g-2 align-items-center">
                                                            <div class="col">
                                                                <select name="team_id" class="form-select team-select-for-owner" data-service-id="{{ $service->id }}" required>
                                                                    <option value="">-- اختر فريق --</option>
                                                                    @foreach($departmentTeams[$service->department] as $team)
                                                                        <option value="{{ $team->id }}">{{ $team->name }} ({{ $team->owner_name }})</option>
                                                                    @endforeach
                                                                </select>
                                                                <small class="text-muted">سيتم إضافة مالك الفريق تلقائياً</small>
                                                            </div>
                                                            <div class="col-auto">
                                                                <button type="submit" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-users me-1"></i> إضافة الفريق
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @else
                                                    {{-- لا يوجد فرق للقسم: اختيار موظفين عادي --}}
                                                    <form action="{{ route('projects.assignParticipants', $project) }}" method="POST" class="add-participant-form">
                                                        @csrf
                                                        <div class="row g-2 align-items-center">
                                                            <div class="col">
                                                                <select name="participants[{{ $service->id }}][]" class="form-select">
                                                                    <option value="">-- اختر موظف --</option>
                                                                    @foreach($serviceUsersMap[$service->id] as $user)
                                                                        @if(!$currentParticipants->pluck('user_id')->contains($user->id))
                                                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-auto">
                                                                <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="openDeadlineModal(this)" title="تحديد موعد نهائي">
                                                                    <i class="fas fa-calendar-plus"></i>
                                                                </button>
                                                                <button type="submit" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-plus me-1"></i> إضافة
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @endif
                                            @else
                                                {{-- للمدراء والأدمن: النظام الكامل --}}
                                                <form action="{{ route('projects.assignParticipants', $project) }}" method="POST" class="add-participant-form">
                                                    @csrf
                                                    <div class="row g-2 align-items-center">
                                                        <!-- اختيار نوع الإضافة -->
                                                        <div class="col-12 mb-2">
                                                            <div class="btn-group w-100" role="group">
                                                                <input type="radio" class="btn-check" name="add_type_{{ $service->id }}" id="individual2_{{ $service->id }}" value="individual" checked>
                                                                <label class="btn btn-outline-primary" for="individual2_{{ $service->id }}">
                                                                    <i class="fas fa-user me-1"></i> فردي
                                                                </label>

                                                                @if(isset($departmentTeams[$service->department]) && $departmentTeams[$service->department]->count() > 0)
                                                                    <input type="radio" class="btn-check" name="add_type_{{ $service->id }}" id="team2_{{ $service->id }}" value="team">
                                                                    <label class="btn btn-outline-info" for="team2_{{ $service->id }}">
                                                                        <i class="fas fa-users me-1"></i> فريق
                                                                    </label>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- اختيار فردي -->
                                                        <div class="col individual-selection" id="individual2_section_{{ $service->id }}">
                                                            <select name="participants[{{ $service->id }}][]" class="form-select participant-select" data-service="{{ $service->id }}">
                                                                <option value="">-- اختر موظف --</option>
                                                                @foreach($serviceUsersMap[$service->id] as $user)
                                                                    @if(!$currentParticipants->pluck('user_id')->contains($user->id))
                                                                        <option value="{{ $user->id }}" data-user-roles="{{ $user->roles->pluck('name')->implode(',') }}">{{ $user->name }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        </div>



                                                        <!-- اختيار الفريق -->
                                                        @if(isset($departmentTeams[$service->department]) && $departmentTeams[$service->department]->count() > 0)
                                                            <div class="col team-selection" id="team2_section_{{ $service->id }}" style="display: none;">
                                                                <select name="team_id" class="form-select team-select" data-service-id="{{ $service->id }}">
                                                                    <option value="">-- اختر فريق --</option>
                                                                    @foreach($departmentTeams[$service->department] as $team)
                                                                        <option value="{{ $team->id }}">{{ $team->name }} ({{ $team->owner_name }})</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <!-- اختيار أعضاء الفريق -->
                                                            <div class="col team-members-selection" id="team2_members_section_{{ $service->id }}" style="display: none;">
                                                                <select name="participants[{{ $service->id }}][]" class="form-select" id="team2_members_select_{{ $service->id }}" multiple>
                                                                    <option value="">-- اختر أعضاء الفريق --</option>
                                                                </select>
                                                                <small class="text-muted">يمكنك اختيار أكثر من عضو بالضغط على Ctrl</small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="col-auto">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-plus me-1"></i> إضافة
                                                        </button>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                @else
                    @php
                        $serviceUsers = $project->serviceParticipants->groupBy('service_id');
                    @endphp
                    @if($serviceUsers->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>الخدمة</th>
                                        <th>المشاركون</th>
                                    </tr>
                                </thead>
                                <tbody>
                            @foreach($serviceUsers as $serviceId => $participants)
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary px-2 py-1 d-inline-block">
                                                    <span style="font-size: 0.85rem;">{{ optional(App\Models\CompanyService::find($serviceId))->name ?? 'خدمة غير معروفة' }}</span>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                @foreach($participants as $participant)
                                                        <div class="badge bg-primary mb-1 p-2 position-relative">
                                                            @if($canManageParticipants)
                                                                <span class="remove-participant-btn-badge"
                                                                    data-user-id="{{ $participant->user_id }}"
                                                                    data-service-id="{{ $serviceId }}"
                                                                    data-project-id="{{ $project->id }}"
                                                                    data-user-name="{{ optional($participant->user)->name ?? '---' }}"
                                                                    data-service-name="{{ optional(App\Models\CompanyService::find($serviceId))->name ?? 'خدمة غير معروفة' }}">
                                                                    <i class="fas fa-times cursor-pointer"></i>
                                                                </span>
                                                            @endif
                                                            <i class="fas fa-user me-1"></i>
                                                            {{ optional($participant->user)->name ?? '---' }}
                                                            @if($participant->project_share)
                                                                <span class="badge {{ $participant->project_share < 1.00 ? 'bg-warning text-dark' : 'bg-light text-dark' }} ms-1" style="font-size: 9px;">
                                                                    {{ $participant->getProjectShareLabel() }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                @endforeach
                                        </div>
                                            </td>
                                        </tr>
                            @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends fa-2x text-muted mb-2"></i>
                            <div class="text-muted">لا يوجد مشاركون بعد</div>
                        </div>
                    @endif
                @endif
            </div>
                </div>
    </div>
</div>

<!-- نافذة منبثقة للاقتراح الذكي -->
<div class="modal fade" id="smartSuggestionModal" tabindex="-1" aria-labelledby="smartSuggestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="smartSuggestionModalLabel">
                    <i class="fas fa-brain me-2"></i>
                    نظام الاقتراح الذكي للفرق
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="smartSuggestionContent">
                <div class="text-center">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">جاري التحليل...</span>
                    </div>
                    <p class="mt-2">جاري تحليل أحمال الفرق...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="selectRecommendedTeam" style="display: none;">
                    <i class="fas fa-check me-1"></i>
                    اختيار الفريق المقترح
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Team Suggestion Sidebar (بدلاً من Modal) -->
<div id="teamSuggestionSidebar" class="team-suggestion-sidebar" style="
    position: fixed;
    top: 0;
    right: -650px;
    width: 630px;
    height: 100vh;
    background: #ffffff;
    z-index: 1050;
    transition: right 0.4s cubic-bezier(0.4, 0.0, 0.2, 1);
    box-shadow: 0 8px 40px rgba(0,0,0,0.12);
    overflow-y: auto;
    border-left: 1px solid #e1e5e9;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    scrollbar-width: none;
    -ms-overflow-style: none;
">
    <!-- Sidebar Header -->
    <div class="sidebar-header" style="
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 24px 32px 16px 32px;
        border-bottom: 1px solid #e9ecef;
        position: sticky;
        top: 0;
        z-index: 10;
    ">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="flex-grow-1">
                <h4 id="teamSuggestionSidebarTitle" class="mb-2" style="font-weight: 600; color: white; font-size: 1.6rem; line-height: 1.3;">
                    <i class="fas fa-brain me-2"></i>
                    نظام الاقتراح الذكي للفرق
                </h4>
                <p id="teamSuggestionSidebarSubtitle" class="mb-0" style="font-size: 14px; color: rgba(255,255,255,0.8); margin: 0;">اختيار أفضل فريق للخدمة</p>
            </div>
                        <button onclick="closeTeamSuggestionSidebar()" class="btn rounded-circle p-2" style="
                width: 40px;
                height: 40px;
                border: none;
                background: rgba(255,255,255,0.2);
                color: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                backdrop-filter: blur(10px);
            ">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="teamSuggestionSidebarBadge" class="badge" style="background: linear-gradient(135deg, #ec4899, #f472b6); color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                <i class="fas fa-robot me-1"></i>اقتراح ذكي
            </span>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div id="teamSuggestionSidebarContent" style="padding: 0; min-height: calc(100vh - 180px);">
        <div class="text-center py-5">
            <div class="spinner-border text-warning" role="status">
                <span class="visually-hidden">جاري التحليل...</span>
            </div>
            <p class="mt-3 text-muted">جاري تحليل أحمال الفرق وحساب أفضل اقتراح...</p>
        </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer" style="
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 20px 32px;
        position: sticky;
        bottom: 0;
        z-index: 10;
    ">
        <div class="d-flex gap-3 justify-content-end">
            <button type="button" class="btn btn-secondary" onclick="closeTeamSuggestionSidebar()">
                <i class="fas fa-times me-2"></i>إغلاق
            </button>
            <button type="button" class="btn btn-primary" id="selectRecommendedTeamSidebar" style="display: none;">
                <i class="fas fa-check me-2"></i>اختيار الفريق المقترح
            </button>
        </div>
    </div>
</div>

<!-- Sidebar Overlay -->
<div id="teamSuggestionSidebarOverlay" class="team-suggestion-sidebar-overlay" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
" onclick="closeTeamSuggestionSidebar()"></div>



@endif

<link rel="stylesheet" href="{{ asset('css/projects/participants.css') }}">
<link rel="stylesheet" href="{{ asset('css/projects/team-suggestion-sidebar.css') }}">


@push('scripts')
<script type="application/json" id="team-members-data">@json($teamMembers ?? [])</script>
<script>
try {
    const teamDataElement = document.getElementById('team-members-data');
    window.teamMembersData = teamDataElement ? JSON.parse(teamDataElement.textContent) : {};
} catch (e) {
    console.error('خطأ في تحليل بيانات الفرق:', e);
    window.teamMembersData = {};
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const teamMembers = window.teamMembersData || {};

    // التعامل مع تغيير نوع الإضافة (فردي/فريق)
    document.querySelectorAll('input[name^="add_type_"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const serviceId = this.name.replace('add_type_', '');
            const isTeam = this.value === 'team';

            const individualSections = document.querySelectorAll('#individual_section_' + serviceId + ', #individual2_section_' + serviceId);
            const teamSections = document.querySelectorAll('#team_section_' + serviceId + ', #team2_section_' + serviceId);
            const membersSections = document.querySelectorAll('#team_members_section_' + serviceId + ', #team2_members_section_' + serviceId);

            individualSections.forEach(section => {
                section.style.display = isTeam ? 'none' : 'block';
            });

            teamSections.forEach(section => {
                section.style.display = isTeam ? 'block' : 'none';
            });

            membersSections.forEach(section => {
                section.style.display = 'none';
            });

            if (isTeam) {
                document.querySelectorAll('#team_members_select_' + serviceId + ', #team2_members_select_' + serviceId).forEach(select => {
                    select.innerHTML = '<option value="">-- اختر أعضاء الفريق --</option>';
                });
            }
        });
    });

    // التعامل مع اختيار الفريق
    document.querySelectorAll('.team-select').forEach(function(select) {
        select.addEventListener('change', function() {
            const serviceId = this.dataset.serviceId;
            const teamId = this.value;

            const membersSections = document.querySelectorAll('#team_members_section_' + serviceId + ', #team2_members_section_' + serviceId);
            const membersSelects = document.querySelectorAll('#team_members_select_' + serviceId + ', #team2_members_select_' + serviceId);

            if (teamId && teamMembers[teamId]) {
                membersSections.forEach(section => {
                    section.style.display = 'block';
                });

                // ملء قائمة الأعضاء
                membersSelects.forEach(membersSelect => {
                    membersSelect.innerHTML = '<option value="">-- اختر أعضاء الفريق --</option>';

                    teamMembers[teamId].forEach(member => {
                        const option = document.createElement('option');
                        option.value = member.id;
                        option.textContent = member.name;
                        membersSelect.appendChild(option);
                    });
                });
            } else {
                // إخفاء قسم اختيار الأعضاء
                membersSections.forEach(section => {
                    section.style.display = 'none';
                });

                membersSelects.forEach(select => {
                    select.innerHTML = '<option value="">-- اختر أعضاء الفريق --</option>';
                });
            }
        });
    });








});
</script>

<!-- Template Tasks Selection Sidebar (بدلاً من Modal) -->
<div id="templateTasksSidebar" class="template-tasks-sidebar" style="
    position: fixed;
    top: 0;
    right: -700px;
    width: 680px;
    height: 100vh;
    background: #ffffff;
    z-index: 1050;
    transition: right 0.4s cubic-bezier(0.4, 0.0, 0.2, 1);
    box-shadow: 0 8px 40px rgba(0,0,0,0.12);
    overflow-y: auto;
    border-left: 1px solid #e1e5e9;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    scrollbar-width: none;
    -ms-overflow-style: none;
">
    <!-- Sidebar Header -->
    <div class="sidebar-header" style="
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        padding: 24px 32px 16px 32px;
        border-bottom: 1px solid #e9ecef;
        position: sticky;
        top: 0;
        z-index: 10;
    ">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="flex-grow-1">
                <h4 id="templateTasksSidebarTitle" class="mb-2" style="font-weight: 600; color: white; font-size: 1.6rem; line-height: 1.3;">
                    <i class="fas fa-clipboard-list me-2"></i>
                    اختيار مهام القوالب للمستخدم
                </h4>
                <p id="templateTasksSidebarSubtitle" class="mb-0" style="font-size: 14px; color: rgba(255,255,255,0.8); margin: 0;">
                    <strong>المستخدم:</strong> <span id="sidebarSelectedUserName">-</span> |
                    <strong>الخدمة:</strong> <span id="sidebarSelectedServiceName">-</span>
                </p>
            </div>
            <button onclick="closeTemplateTasksSidebar()" class="btn rounded-circle p-2" style="
                width: 40px;
                height: 40px;
                border: none;
                background: rgba(255,255,255,0.2);
                color: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                backdrop-filter: blur(10px);
            ">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="templateTasksSidebarBadge" class="badge" style="background: linear-gradient(135deg, #10b981, #065f46); color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                <i class="fas fa-tasks me-1"></i>مهام القوالب
            </span>
        </div>
    </div>

    <!-- Alert Section -->
    <div class="px-4 pt-4">
        <div class="alert alert-info mb-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    <strong>المستخدم:</strong> <span id="sidebarSelectedUserName2">-</span><br>
                    <strong>الخدمة:</strong> <span id="sidebarSelectedServiceName2">-</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div id="templateTasksSidebarContent" style="padding: 0 24px 24px 24px; min-height: calc(100vh - 280px);">
        <div class="d-flex justify-content-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    </div>

    <!-- Select All Section -->
    <div class="px-4 py-3 border-top bg-light">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="sidebarSelectAllTasks">
            <label class="form-check-label fw-bold" for="sidebarSelectAllTasks">
                تحديد جميع المهام
            </label>
        </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer" style="
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 20px 32px;
        position: sticky;
        bottom: 0;
        z-index: 10;
    ">
        <div class="d-flex gap-3 justify-content-end">
            <button type="button" class="btn btn-secondary" onclick="closeTemplateTasksSidebar()">
                <i class="fas fa-times me-2"></i>إلغاء
            </button>
            <button type="button" class="btn btn-primary" id="sidebarAssignSelectedTasksBtn" disabled>
                <i class="fas fa-plus me-2"></i>
                إضافة المهام المختارة (<span id="sidebarSelectedTasksCount">0</span>)
            </button>
        </div>
    </div>
</div>

<!-- Sidebar Overlay -->
<div id="templateTasksSidebarOverlay" class="template-tasks-sidebar-overlay" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
" onclick="closeTemplateTasksSidebar()"></div>

<!-- Add Participant Sidebar -->
<div id="addParticipantSidebar" class="add-participant-sidebar">
    <div class="sidebar-overlay" id="addParticipantSidebarOverlay" onclick="closeAddParticipantSidebar()"></div>
    <div class="sidebar-content">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="sidebar-icon bg-success">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="ms-3">
                        <h5 class="mb-0" id="addParticipantSidebarTitle">إضافة مشارك جديد</h5>
                        <small class="text-muted" id="addParticipantSidebarSubtitle">إضافة موظف جديد للمشروع</small>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-secondary" onclick="closeAddParticipantSidebar()" style="border-radius: 8px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Sidebar Content -->
        <div class="sidebar-body" id="addParticipantSidebarContent">
            <form id="addParticipantForm">
                <input type="hidden" id="participantServiceId" name="service_id">
                <input type="hidden" id="participantUserId" name="user_id">
                <input type="hidden" id="participantUserName" name="user_name">

                <!-- Employee Info -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">معلومات الموظف</label>
                    <div class="p-3 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-user text-primary me-2"></i>
                            <span class="fw-semibold" id="selectedEmployeeName">-</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-cogs text-info me-2"></i>
                            <span class="text-muted" id="selectedServiceName">-</span>
                        </div>
                    </div>
                </div>


                <!-- Role Selection Section -->
                <div class="mb-4" id="roleSelectionSection" style="display: none;">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user-tag me-1"></i>
                        الدور المطلوب
                    </label>
                    <select class="form-select" id="participantRoleId" name="role_id" style="border-radius: 8px; padding: 12px 16px;">
                        <option value="">-- اختر الدور --</option>
                    </select>
                    <div class="form-text mt-2" style="font-size: 11px;">
                        <i class="fas fa-info-circle me-1"></i>
                        اختر الدور المناسب للموظف في هذه الخدمة
                    </div>
                </div>

                <!-- Project Share Section -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-chart-pie me-1"></i>
                        نسبة المشاركة في المشروع
                    </label>
                    <select class="form-select" id="participantProjectShare" name="project_share" style="border-radius: 8px; padding: 12px 16px;">
                        <option value="1.00" selected>مشروع كامل (100%)</option>
                        <option value="0.75">ثلاثة أرباع مشروع (75%)</option>
                        <option value="0.50">نص مشروع (50%)</option>
                        <option value="0.25">ربع مشروع (25%)</option>
                        <option value="0.00" style="background-color: #f8f9fa; color: #6c757d; font-style: italic;">
                            🔓 بدون محاسبة (0%) - وصول فقط
                        </option>
                    </select>
                    <div class="form-text mt-2" style="font-size: 11px;" id="projectShareHelp">
                        <i class="fas fa-info-circle me-1"></i>
                        تحديد نسبة المشاركة تساعد في حساب الأحمال والنقاط بدقة
                    </div>
                    <div class="alert alert-info mt-2 d-none" id="noAccountingAlert" style="font-size: 12px; padding: 8px 12px; border-radius: 6px;">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>وصول فقط:</strong> لن يتم حساب هذا الشخص في إحصائيات المشروع أو النقاط. مناسب للمتابعين أو المراقبين.
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const projectShareSelect = document.getElementById('participantProjectShare');
                    const projectShareHelp = document.getElementById('projectShareHelp');
                    const noAccountingAlert = document.getElementById('noAccountingAlert');

                    if (projectShareSelect) {
                        projectShareSelect.addEventListener('change', function() {
                            if (this.value === '0.00') {
                                projectShareHelp.classList.add('d-none');
                                noAccountingAlert.classList.remove('d-none');
                            } else {
                                projectShareHelp.classList.remove('d-none');
                                noAccountingAlert.classList.add('d-none');
                            }
                        });
                    }
                });
                </script>

                <!-- Deadline Section -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-calendar-alt me-1"></i>
                        الموعد النهائي (اختياري)
                    </label>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="noDeadlineCheck" name="no_deadline">
                        <label class="form-check-label" for="noDeadlineCheck">
                            لا يوجد موعد نهائي محدد
                        </label>
                    </div>

                    <div id="deadlineSection">
                        <input type="datetime-local" class="form-control" id="participantDeadline" name="deadline"
                               min="{{ date('Y-m-d\TH:i') }}"
                               style="border-radius: 8px; padding: 12px 16px;">
                        <div class="form-text mt-2" style="font-size: 11px;">اختر التاريخ والوقت المطلوب للانتهاء من المهام</div>
                    </div>
                </div>





                <!-- Action Buttons -->
                <div class="d-flex gap-2 pt-3" style="border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-success px-4 py-2" id="confirmAddParticipantBtn" style="border-radius: 8px; font-weight: 500; flex: 1;">
                        <i class="fas fa-user-plus me-1"></i>
                        إضافة المشارك
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-4 py-2" onclick="closeAddParticipantSidebar()" style="border-radius: 8px; font-weight: 500;">
                        <i class="fas fa-times me-1"></i>
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Participant Sidebar CSS -->
<link rel="stylesheet" href="{{ asset('css/projects/add-participant-sidebar.css') }}">

<!-- Team Suggestion Sidebar JavaScript -->
<script src="{{ asset('js/projects/team-suggestion-sidebar.js') }}"></script>

<!-- Modal لتحديد الدور -->
<div class="modal fade" id="assignRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-tag me-2"></i>
                    تحديد دور للمشارك
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignRoleForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="participantId" name="participant_id">
                    <input type="hidden" id="serviceIdForRole" name="service_id">

                    <div class="alert alert-info mb-3">
                        <i class="fas fa-user me-2"></i>
                        <strong>الموظف:</strong> <span id="participantNameDisplay"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user-tag me-1"></i>اختر الدور
                        </label>
                        <select class="form-select" id="roleIdSelect" name="role_id" required>
                            <option value="">-- اختر الدور --</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>إلغاء
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// جعل packageServicesData متغير عام ليمكن الوصول إليه من ملفات JavaScript الخارجية
var packageServicesData = @json($packageServices ?? collect());
window.packageServicesData = packageServicesData;

function openAssignRoleModal(element) {
    const participantId = element.dataset.participantId;
    const serviceId = element.dataset.serviceId;
    const participantName = element.dataset.userName;
    document.getElementById('participantId').value = participantId;
    document.getElementById('serviceIdForRole').value = serviceId;
    document.getElementById('participantNameDisplay').textContent = participantName;

    // ملء قائمة الأدوار
    const roleSelect = document.getElementById('roleIdSelect');
    roleSelect.innerHTML = '<option value="">-- اختر الدور --</option>';

    // البحث عن الخدمة للحصول على الأدوار المطلوبة
    const service = window.packageServicesData.find(s => s.id == serviceId);

    if (service && service.required_roles) {
        service.required_roles.forEach(role => {
            const option = document.createElement('option');
            option.value = role.id;
            option.textContent = role.name;
            roleSelect.appendChild(option);
        });
    }

    const modal = new bootstrap.Modal(document.getElementById('assignRoleModal'));
    modal.show();
}

document.getElementById('assignRoleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('{{ route("projects.updateParticipantRole", $project) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('assignRoleModal')).hide();

            Swal.fire({
                icon: 'success',
                title: 'تم بنجاح',
                text: 'تم تحديد الدور بنجاح',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: data.message || 'حدث خطأ'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'حدث خطأ غير متوقع'
        });
    });
});

// معالجة forms إضافة المشاركين - تم نقلها إلى project-participants.js
document.addEventListener('DOMContentLoaded', function() {

    // معالجة sidebar إضافة المشارك
    const sidebarForm = document.getElementById('addParticipantForm');
    if (sidebarForm) {
        sidebarForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Debug: طباعة البيانات المرسلة من الـ sidebar
            console.log('Sidebar Form Data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            // إعداد البيانات للـ backend
            const serviceId = formData.get('service_id');
            const userId = formData.get('user_id');
            const projectShare = formData.get('project_share');
            const deadline = formData.get('deadline');

            // إنشاء FormData جديد بالشكل المطلوب
            const submitData = new FormData();
            submitData.append('_token', '{{ csrf_token() }}');
            submitData.append(`participants[${serviceId}][]`, userId);
            if (projectShare) {
                submitData.append(`project_shares[${userId}]`, projectShare);
            }
            if (deadline) {
                submitData.append(`deadlines[${userId}]`, deadline);
            }

            // Debug: طباعة البيانات الجديدة
            console.log('Sidebar Submit Data:');
            for (let [key, value] of submitData.entries()) {
                console.log(key, value);
            }

            // إرسال البيانات
            fetch('{{ route("projects.assignParticipants", $project) }}', {
                method: 'POST',
                body: submitData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeAddParticipantSidebar();
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: data.message || 'تم إضافة المشارك بنجاح',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: data.message || 'حدث خطأ أثناء إضافة المشارك'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'حدث خطأ غير متوقع'
                });
            });
        });
    }
});

// تحديث الـ sidebar عند اختيار موظف
function updateSidebarForUser(userId, userName, serviceId, serviceName) {
    document.getElementById('participantUserId').value = userId;
    document.getElementById('participantUserName').value = userName;
    document.getElementById('participantServiceId').value = serviceId;
    document.getElementById('selectedEmployeeName').textContent = userName;
    document.getElementById('selectedServiceName').textContent = serviceName;
}
</script>

@endpush
