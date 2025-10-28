@if(session('success'))
<input type="hidden" id="session-success" value="{{ session('success') }}">
@endif
@if(session('error'))
<input type="hidden" id="session-error" value="{{ session('error') }}">
@endif

<div class="row">
    <div class="col-12">
        <div class="project-header-container shadow-sm" style="border-radius: 16px; background: #fff; border: 1px solid #e9ecef; overflow: hidden;">
            <!-- Modern Project Header -->
            <div class="project-main-header {{ $project->is_urgent ? 'urgent-header' : 'normal-header' }}" style="padding: 1.5rem 2rem; position: relative;">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Header Title Section -->
                    <div class="d-flex align-items-center">
                        <div class="project-header-icon me-3" style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            @if($project->is_urgent)
                                <i class="fas fa-exclamation-triangle text-white" style="font-size: 24px;"></i>
                            @else
                                <i class="fas fa-project-diagram text-white" style="font-size: 24px;"></i>
                            @endif
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h3 class="mb-0 text-white fw-bold me-3" style="font-size: 1.5rem;">
                                    📁 {{ $project->name }}
                                </h3>
                                @if($project->is_urgent)
                                    <span class="urgent-badge-modern" style="background: rgba(255,193,7,0.9); color: #856404; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; margin-left: 8px;">
                                        <i class="fas fa-bolt me-1" style="font-size: 10px;"></i>
                                        مستعجل
                                    </span>
                                @endif
                                @if($project->preparation_enabled && $project->isInPreparationPeriod())
                                    <span class="preparation-badge-modern" style="background: rgba(103, 126, 234, 0.9); color: #fff; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; margin-left: 8px;">
                                        <i class="fas fa-clock me-1" style="font-size: 10px;"></i>
                                        فترة تحضير (باقي {{ $project->remaining_preparation_days }} يوم)
                                    </span>
                                @endif
                            </div>
                            <p class="mb-0 text-white-50 small">
                                <i class="fas fa-info-circle me-1"></i>
                                صفحة تفاصيل وإدارة المشروع
                            </p>
                        </div>
                    </div>

                    <!-- Header Actions -->
                    <div class="d-flex align-items-center gap-3">
                        <a href="{{ route('projects.edit', $project) }}"
                           class="btn btn-light btn-sm d-flex align-items-center shadow-sm"
                           style="border-radius: 8px; font-weight: 500;">
                            <i class="fas fa-edit me-2 text-success"></i>
                            تعديل المشروع
                        </a>
                        <a href="{{ route('projects.index') }}"
                           class="btn btn-outline-light btn-sm d-flex align-items-center"
                           style="border-radius: 8px; font-weight: 500; border-color: rgba(255,255,255,0.3);">
                            <i class="fas fa-arrow-right me-2"></i>
                            العودة للقائمة
                        </a>
                    </div>
                </div>

                <!-- Header Decoration -->
                <div class="project-header-decoration" style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #fff 0%, rgba(255,255,255,0.7) 50%, #fff 100%);"></div>

                @if($project->is_urgent)
                    <!-- Urgent Project Animation -->
                    <div class="urgent-pulse" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(45deg, transparent 30%, rgba(255,193,7,0.1) 50%, transparent 70%); animation: urgentShimmer 3s infinite;"></div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Project Header Modern Styles ===== */
.project-header-container {
    transition: all 0.3s ease;
}

.project-main-header {
    position: relative;
    overflow: hidden;
}

.project-main-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 3s ease;
}

.project-main-header:hover::before {
    left: 100%;
}

.project-header-icon {
    transition: all 0.3s ease;
}

.project-header-icon:hover {
    transform: rotate(10deg) scale(1.05);
    background: rgba(255,255,255,0.3) !important;
}

.urgent-badge-modern {
    transition: all 0.3s ease;
    animation: urgentBounce 2s infinite;
}

.urgent-badge-modern:hover {
    transform: scale(1.05);
}

@keyframes urgentShimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

@keyframes urgentBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-2px); }
}

/* Header Background Classes */
.urgent-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.normal-header {
    background: linear-gradient(135deg, #495057 0%, #343a40 100%) !important;
}

/* Header Button Enhancements */
.project-main-header .btn {
    transition: all 0.3s ease;
}

.project-main-header .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
</style>
