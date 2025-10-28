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

                <div class="mt-4">
                    <div class="info-group">
                        <label class="info-label">مجموع النقاط:</label>
                        <div class="info-value">
                            <span class="points-badge">
                                <i class="fas fa-star me-2"></i>
                                {{ $project->total_points }} نقطة
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="client-info-container shadow-sm">
            <!-- Modern Client Info Header -->
            <div class="client-info-header">
                <div class="d-flex align-items-center">
                    <div class="client-header-icon me-3">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-white fw-bold">
                            👤 معلومات العميل
                        </h5>
                        <p class="mb-0 text-white-50 small">
                            <i class="fas fa-address-card me-1"></i>
                            بيانات الاتصال والتواصل
                        </p>
                    </div>
                </div>

                <!-- Header Decoration -->
                <div class="client-header-decoration"></div>
            </div>
            <!-- Client Info Body -->
            <div class="client-info-body">
                <div class="info-group-modern mb-4">
                    <label class="info-label-modern">اسم العميل</label>
                    <div class="info-value-modern">{{ $project->client->name }}</div>
                </div>

                <div class="info-group-modern mb-4">
                    <label class="info-label-modern">كود العميل</label>
                    <div class="info-value-modern">
                        <span class="info-badge-modern client-code-badge">
                            <i class="fas fa-id-badge me-2"></i>
                            {{ $project->client->code }}
                        </span>
                    </div>
                </div>

                @if($project->client->company_name)
                    <div class="info-group-modern mb-4">
                        <label class="info-label-modern">اسم الشركة</label>
                        <div class="info-value-modern company-name">{{ $project->client->company_name }}</div>
                    </div>
                @endif

                <div class="info-group-modern mb-4">
                    <label class="info-label-modern">الإيميلات</label>
                    <div class="info-value-modern">
                        @if($project->client->emails)
                            <div class="contact-list-modern">
                                @foreach($project->client->emails as $email)
                                    <div class="contact-item-modern">
                                        <div class="contact-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <span class="contact-value">{{ $email }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted-modern">لا توجد إيميلات</span>
                        @endif
                    </div>
                </div>

                <div class="info-group-modern">
                    <label class="info-label-modern">أرقام الهاتف</label>
                    <div class="info-value-modern">
                        @if($project->client->phones)
                            <div class="contact-list-modern">
                                @foreach($project->client->phones as $phone)
                                    <div class="contact-item-modern">
                                        <div class="contact-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <span class="contact-value">{{ $phone }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted-modern">لا توجد أرقام هاتف</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


