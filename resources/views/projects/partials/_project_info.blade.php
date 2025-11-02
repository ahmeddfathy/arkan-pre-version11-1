<link rel="stylesheet" href="{{ asset('css/projects/project-info.css') }}">

<div class="row g-4">
    <div class="col-md-6">
        <div class="project-info-container shadow-sm">
            <!-- Modern Project Info Header -->
            <div class="project-info-header">
                <div class="d-flex align-items-center">
                    <div class="info-header-icon me-3">
                        <i class="fas fa-info-circle text-white"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-white fw-bold">
                            📋 معلومات المشروع
                        </h5>
                        <p class="mb-0 text-white-50 small">
                            <i class="fas fa-database me-1"></i>
                            البيانات الأساسية للمشروع
                        </p>
                    </div>
                </div>

                <!-- Header Decoration -->
                <div class="info-header-decoration"></div>
            </div>
            <!-- Project Info Body -->
            <div class="project-info-body">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="info-group-modern">
                            <label class="info-label-modern">اسم المشروع</label>
                            <div class="info-value-modern">{{ $project->name }}</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="info-group-modern">
                            <label class="info-label-modern">نوع المشروع</label>
                            <div class="info-value-modern">
                                @if($project->package_id)
                                <span class="info-badge-modern package-badge">
                                    <i class="fas fa-box me-2"></i>
                                    باقة: {{ optional($project->package)->name }}
                                </span>
                                @else
                                <span class="info-badge-modern service-badge">
                                    <i class="fas fa-cogs me-2"></i>
                                    خدمات منفصلة
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="info-group-modern">
                            <label class="info-label-modern">كود المشروع</label>
                            <div class="info-value-modern">
                                @if($project->code)
                                <span class="info-badge-modern code-badge">
                                    <i class="fas fa-hashtag me-2"></i>
                                    {{ $project->code }}
                                </span>
                                @else
                                <span class="text-muted-modern">غير محدد</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group-modern">
                            <label class="info-label-modern">حالة المشروع</label>
                            <div class="info-value-modern">
                                @if($project->status == 'جديد')
                                <span class="info-badge-modern status-new">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    {{ $project->status }}
                                </span>
                                @elseif($project->status == 'جاري التنفيذ')
                                <span class="info-badge-modern status-progress">
                                    <i class="fas fa-sync-alt me-2"></i>
                                    {{ $project->status }}
                                </span>
                                @elseif($project->status == 'مكتمل')
                                <span class="info-badge-modern status-completed">
                                    <i class="fas fa-check-circle me-2"></i>
                                    {{ $project->status }}
                                </span>
                                @else
                                <span class="info-badge-modern status-cancelled">
                                    <i class="fas fa-times-circle me-2"></i>
                                    {{ $project->status }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if($project->description)
                <div class="mt-4">
                    <div class="info-group">
                        <label class="info-label">وصف المشروع:</label>
                        <div class="info-value description-text">{{ $project->description }}</div>
                    </div>
                </div>
                @endif

                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">تاريخ البداية:</label>
                            <div class="info-value">
                                @if($project->start_date)
                                <div class="date-info d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        {{ $project->start_date->format('Y-m-d') }}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-info position-relative" onclick="showDateHistory('start_date')" title="عرض تاريخ التعديلات">
                                        <i class="fas fa-history"></i>
                                        @if($project->hasDateHistory('start_date'))
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $project->getDateHistoryCount('start_date') }}
                                        </span>
                                        @endif
                                    </button>
                                </div>
                                @else
                                <span class="text-muted-modern">غير محدد</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">تواريخ التسليم:</label>
                            <div class="info-value">
                                @if($project->client_agreed_delivery_date)
                                <div class="date-info mb-1 d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-handshake me-2"></i>
                                        <small class="text-muted">متفق مع العميل:</small> {{ $project->client_agreed_delivery_date->format('Y-m-d') }}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-info ms-2 position-relative" onclick="showDateHistory('client_agreed_delivery_date')" title="عرض تاريخ التعديلات">
                                        <i class="fas fa-history"></i>
                                        @if($project->hasDateHistory('client_agreed_delivery_date'))
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $project->getDateHistoryCount('client_agreed_delivery_date') }}
                                        </span>
                                        @endif
                                    </button>
                                </div>
                                @endif
                                @if($project->team_delivery_date)
                                <div class="date-info mb-1 d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-users me-2"></i>
                                        <small class="text-muted">محدد من الفريق:</small> {{ $project->team_delivery_date->format('Y-m-d') }}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-info ms-2 position-relative" onclick="showDateHistory('team_delivery_date')" title="عرض تاريخ التعديلات">
                                        <i class="fas fa-history"></i>
                                        @if($project->hasDateHistory('team_delivery_date'))
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $project->getDateHistoryCount('team_delivery_date') }}
                                        </span>
                                        @endif
                                    </button>
                                </div>
                                @endif
                                @if($project->actual_delivery_date)
                                <div class="date-info d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <small class="text-muted">التسليم الفعلي:</small> {{ $project->actual_delivery_date->format('Y-m-d') }}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-info ms-2 position-relative" onclick="showDateHistory('actual_delivery_date')" title="عرض تاريخ التعديلات">
                                        <i class="fas fa-history"></i>
                                        @if($project->hasDateHistory('actual_delivery_date'))
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $project->getDateHistoryCount('actual_delivery_date') }}
                                        </span>
                                        @endif
                                    </button>
                                </div>
                                @endif
                                @if(!$project->client_agreed_delivery_date && !$project->team_delivery_date && !$project->actual_delivery_date)
                                <span class="text-muted-modern">غير محدد</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">المسؤول:</label>
                            <div class="info-value">{{ $project->manager ?? 'غير محدد' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">تاريخ الاستلام:</label>
                            <div class="info-value">
                                @if($project->received_date)
                                <div class="date-info">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    {{ $project->received_date instanceof \Carbon\Carbon ? $project->received_date->setTimezone('Africa/Cairo')->format('Y-m-d H:i') : $project->received_date }}
                                </div>
                                @else
                                <span class="text-muted-modern">غير محدد</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">تاريخ بداية فترة التحضير:</label>
                            <div class="info-value">
                                @if($project->preparation_start_date)
                                <div class="date-info">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    {{ $project->preparation_start_date->format('Y-m-d') }}
                                </div>
                                @else
                                <span class="text-muted-modern">غير محدد</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="info-label">ملاحظات:</label>
                            <div class="info-value">{{ $project->note ?? 'لا توجد ملاحظات' }}</div>
                        </div>
                    </div>
                </div>

                @php
                $previousProjects = $project->client->projects()
                ->where('id', '!=', $project->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
                @endphp

                @if($previousProjects->count() > 0)
                <div class="mt-4">
                    <div class="info-group previous-projects-section">
                        <label class="info-label">
                            <i class="fas fa-history me-2 text-danger"></i>
                            <span class="text-danger fw-bold">المشاريع السابقة لهذا العميل:</span>
                        </label>
                        <div class="previous-projects-list">
                            @foreach($previousProjects as $prevProject)
                            <div class="previous-project-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <a href="{{ route('projects.show', $prevProject) }}" class="project-link">
                                            @if($prevProject->code)
                                            <strong>{{ $prevProject->code }}</strong> -
                                            @endif
                                            {{ $prevProject->name }}
                                        </a>
                                        <div class="project-meta">
                                            <span class="badge badge-sm status-badge-{{ str_replace(' ', '-', strtolower($prevProject->status)) }}">
                                                {{ $prevProject->status }}
                                            </span>
                                            <span class="text-muted small ms-2">
                                                @if($prevProject->start_date)
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                {{ $prevProject->start_date->format('Y-m-d') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <a href="{{ route('projects.show', $prevProject) }}" class="btn-view-project" title="عرض المشروع">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                            @endforeach

                            @if($project->client->projects()->count() > 6)
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    إجمالي {{ $project->client->projects()->count() }} مشروع لهذا العميل
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .previous-projects-section {
        border-left: 4px solid #dc3545 !important;
        padding-left: 1rem !important;
        background: rgba(220, 53, 69, 0.03) !important;
    }

    .previous-projects-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .previous-project-item {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        border: 1px solid rgba(102, 126, 234, 0.1);
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
    }

    .previous-project-item:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        border-color: rgba(102, 126, 234, 0.3);
        transform: translateX(-5px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }

    .previous-project-item:last-child {
        margin-bottom: 0;
    }

    .project-link {
        color: #333;
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: block;
        margin-bottom: 0.5rem;
    }

    .project-link:hover {
        color: #667eea;
        text-decoration: underline;
    }

    .project-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .badge-sm {
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 12px;
    }

    .status-badge-جديد {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
    }

    .status-badge-جاري-التنفيذ {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
    }

    .status-badge-مكتمل {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
    }

    .status-badge-ملغي {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .btn-view-project {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 0.875rem;
    }

    .btn-view-project:hover {
        background: linear-gradient(135deg, #764ba2, #667eea);
        transform: scale(1.1);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .previous-projects-list::-webkit-scrollbar {
        width: 6px;
    }

    .previous-projects-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .previous-projects-list::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 10px;
    }

    .previous-projects-list::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #764ba2, #667eea);
    }
</style>