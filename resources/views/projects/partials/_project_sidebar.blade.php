<!-- Project Details Sidebar -->
<div id="projectSidebar" class="project-sidebar">
    <div class="sidebar-overlay" onclick="closeProjectSidebar()"></div>

    <div class="sidebar-content">
        <!-- Header -->
        <div class="sidebar-header">
            <h3 class="sidebar-title">
                <i class="fas fa-project-diagram me-2"></i>
                <span id="sidebarProjectName">تفاصيل المشروع</span>
            </h3>
            <button type="button" class="sidebar-close-btn" onclick="closeProjectSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Loading State -->
        <div id="sidebarLoading" class="sidebar-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
            <p class="mt-3">جاري تحميل البيانات...</p>
        </div>

        <!-- Content -->
        <div id="sidebarContent" class="sidebar-body" style="display: none;">
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs sidebar-tabs" id="sidebarTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                        <i class="fas fa-info-circle me-1"></i>
                        نظرة عامة
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab">
                        <i class="fas fa-cogs me-1"></i>
                        الخدمات
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="participants-tab" data-bs-toggle="tab" data-bs-target="#participants" type="button" role="tab">
                        <i class="fas fa-users me-1"></i>
                        المشاركين
                    </button>
                </li>
                <li class="nav-item" role="presentation" id="preparation-history-tab-li" style="display: none;">
                    <button class="nav-link" id="preparation-history-tab" data-bs-toggle="tab" data-bs-target="#preparation-history" type="button" role="tab">
                        <i class="fas fa-history me-1"></i>
                        تاريخ التحضير
                    </button>
                </li>
                <li class="nav-item" role="presentation" id="custom-fields-tab-li" style="display: none;">
                    <button class="nav-link" id="custom-fields-tab" data-bs-toggle="tab" data-bs-target="#custom-fields" type="button" role="tab">
                        <i class="fas fa-list-ul me-1"></i>
                        حقول مخصصة
                    </button>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content sidebar-tab-content" id="sidebarTabContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="info-section">
                        <h6 class="info-section-title">
                            <i class="fas fa-building text-primary"></i>
                            معلومات أساسية
                        </h6>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">الكود:</span>
                                <span class="info-value" id="projectCode">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">الحالة:</span>
                                <span class="info-value" id="projectStatus">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">العميل:</span>
                                <span class="info-value" id="projectClient">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">المدير:</span>
                                <span class="info-value" id="projectManager">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h6 class="info-section-title">
                            <i class="fas fa-calendar text-success"></i>
                            التواريخ
                        </h6>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">تاريخ البدء:</span>
                                <span class="info-value" id="projectStartDate">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">تسليم الفريق:</span>
                                <span class="info-value" id="projectTeamDelivery">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">التسليم المتفق مع العميل:</span>
                                <span class="info-value" id="projectClientDelivery">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">التسليم الفعلي:</span>
                                <span class="info-value" id="projectActualDelivery">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Preparation Period Section -->
                    <div class="info-section" id="preparationSection" style="display: none;">
                        <h6 class="info-section-title">
                            <i class="fas fa-clock text-purple"></i>
                            فترة التحضير
                        </h6>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">الحالة:</span>
                                <span class="info-value" id="preparationStatus">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">تاريخ البداية:</span>
                                <span class="info-value" id="preparationStartDate">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">تاريخ النهاية:</span>
                                <span class="info-value" id="preparationEndDate">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">عدد الأيام:</span>
                                <span class="info-value" id="preparationDays">-</span>
                            </div>
                            <div class="info-item" id="preparationRemainingSection" style="display: none;">
                                <span class="info-label">الأيام المتبقية:</span>
                                <span class="info-value" id="preparationRemaining">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Section: Revisions & Errors -->
                    <div class="info-section">
                        <h6 class="info-section-title">
                            <i class="fas fa-chart-bar text-info"></i>
                            إحصائيات المشروع
                        </h6>
                        <div class="stats-grid">
                            <!-- التعديلات -->
                            <div class="stat-card stat-revisions">
                                <div class="stat-header">
                                    <i class="fas fa-edit"></i>
                                    <span class="stat-title">التعديلات</span>
                                </div>
                                <div class="stat-body">
                                    <div class="stat-main-number" id="revisionsTotal">0</div>
                                    <div class="stat-details">
                                        <div class="stat-detail-item">
                                            <span class="badge bg-secondary" id="revisionsNew">0 جديد</span>
                                        </div>
                                        <div class="stat-detail-item">
                                            <span class="badge bg-primary" id="revisionsInProgress">0 قيد التنفيذ</span>
                                        </div>
                                        <div class="stat-detail-item">
                                            <span class="badge bg-success" id="revisionsCompleted">0 مكتمل</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- الأخطاء -->
                            <div class="stat-card stat-errors">
                                <div class="stat-header">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span class="stat-title">الأخطاء</span>
                                </div>
                                <div class="stat-body">
                                    <div class="stat-main-number" id="errorsTotal">0</div>
                                    <div class="stat-details">
                                        <div class="stat-detail-item">
                                            <span class="badge bg-danger" id="errorsCritical">0 جوهري</span>
                                        </div>
                                        <div class="stat-detail-item">
                                            <span class="badge bg-warning" id="errorsNormal">0 عادي</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="info-section">
                        <h6 class="info-section-title">
                            <i class="fas fa-align-right text-secondary"></i>
                            الوصف
                        </h6>
                        <p id="projectDescription" class="text-muted">-</p>
                    </div>
                </div>

                <!-- Services Tab -->
                <div class="tab-pane fade" id="services" role="tabpanel">
                    <div id="servicesList" class="services-list">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>

                <!-- Participants Tab -->
                <div class="tab-pane fade" id="participants" role="tabpanel">
                    <div class="info-section">
                        <h6 class="info-section-title">
                            <i class="fas fa-users text-primary"></i>
                            المشاركين في المشروع
                        </h6>
                        <div id="participantsList" class="participants-list">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Preparation History Tab -->
                <div class="tab-pane fade" id="preparation-history" role="tabpanel">
                    <div class="info-section">
                        <h6 class="info-section-title">
                            <i class="fas fa-history text-info"></i>
                            تاريخ فترات التحضير
                        </h6>
                        <div id="preparationHistoryContent">
                            <p class="text-center text-muted">لا توجد سجلات</p>
                        </div>
                    </div>
                </div>

                <!-- Custom Fields Tab -->
                <div class="tab-pane fade" id="custom-fields" role="tabpanel">
                    <!-- Sub Tabs Navigation -->
                    <ul class="nav nav-pills mb-3" id="customFieldsSubTabs" role="tablist" style="background: #f8f9fa; padding: 0.5rem; border-radius: 8px; flex-wrap: wrap; gap: 0.5rem;">
                        <!-- Dynamic sub-tabs will be added here -->
                    </ul>

                    <!-- Sub Tabs Content -->
                    <div class="tab-content" id="customFieldsSubTabContent">
                        <!-- Dynamic content will be added here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div id="sidebarError" class="sidebar-error" style="display: none;">
            <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
            <p>حدث خطأ أثناء تحميل البيانات</p>
            <button class="btn btn-primary btn-sm" onclick="closeProjectSidebar()">إغلاق</button>
        </div>
    </div>
</div>

<style>
.project-sidebar {
    position: fixed;
    top: 0;
    right: -100%;
    width: 100%;
    height: 100%;
    z-index: 9999;
}

.project-sidebar.active {
    right: 0;
}

.sidebar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.sidebar-content {
    position: absolute;
    top: 0;
    right: 0;
    width: 600px;
    max-width: 90%;
    height: 100%;
    background: white;
    box-shadow: -5px 0 30px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    direction: rtl;
    text-align: right;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.sidebar-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
}

.sidebar-title i {
    color: white;
}

.sidebar-close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
}

.sidebar-tabs {
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
    border-bottom: 2px solid #e9ecef;
    padding: 0 1.5rem;
    direction: rtl;
    justify-content: flex-start;
}

.sidebar-tabs .nav-link {
    color: #6c757d;
    font-weight: 500;
    border: none;
    padding: 1rem 1.5rem;
}

.sidebar-tabs .nav-link.active {
    color: #667eea;
    background: transparent;
    border-bottom: 3px solid #667eea;
}

.sidebar-tab-content {
    padding: 1.5rem;
    background: transparent;
}

/* Sub-tabs styling */
#customFieldsSubTabs {
    display: flex;
    flex-wrap: wrap;
    background: white !important;
    padding: 0.75rem !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

#customFieldsSubTabs .nav-link {
    color: #6c757d;
    font-weight: 600;
    border-radius: 10px;
    padding: 0.65rem 1.25rem;
    border: 1px solid transparent;
    font-size: 0.9rem;
}

#customFieldsSubTabs .nav-link.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

#customFieldsSubTabContent {
    margin-top: 1rem;
}

.info-section {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    direction: rtl;
    text-align: right;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08),
                0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.info-section-title {
    font-weight: 600;
    font-size: 1.05rem;
    margin-bottom: 1.25rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid rgba(102, 126, 234, 0.1);
}

.info-section-title i {
    font-size: 1.1rem;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    text-align: right;
    padding: 0.75rem;
    background: rgba(102, 126, 234, 0.03);
    border-radius: 10px;
    border-right: 3px solid rgba(102, 126, 234, 0.3);
}

.info-label {
    font-size: 0.85rem;
    color: #667eea;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 1rem;
    color: #333;
    font-weight: 600;
}

.participants-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.participant-card {
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    border: 1px solid rgba(102, 126, 234, 0.1);
    direction: rtl;
    text-align: right;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08),
                0 2px 8px rgba(0, 0, 0, 0.04);
}

.participant-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.75rem;
}

.participant-name {
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
    font-size: 1.05rem;
}

.participant-name i {
    color: #667eea;
}

.participant-email {
    font-size: 0.85rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.participant-email::before {
    content: "✉";
    color: #667eea;
    font-size: 0.9rem;
}

.participant-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e9ecef;
}

.participant-detail {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.5rem;
    background: rgba(102, 126, 234, 0.03);
    border-radius: 8px;
}

.participant-detail .info-label {
    font-size: 0.75rem;
}

.participant-detail .info-value {
    font-size: 0.9rem;
}

/* ============================================
   إحصائيات المشروع (التعديلات والأخطاء)
   ============================================ */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    direction: rtl;
}

.stat-card {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    border-radius: 12px;
    padding: 1rem;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
}

.stat-revisions {
    border-color: rgba(102, 126, 234, 0.3);
}

.stat-errors {
    border-color: rgba(220, 53, 69, 0.3);
}

.stat-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(102, 126, 234, 0.2);
}

.stat-header i {
    font-size: 1.2rem;
    color: #667eea;
}

.stat-errors .stat-header i {
    color: #dc3545;
}

.stat-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #333;
}

.stat-body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.stat-main-number {
    font-size: 2rem;
    font-weight: 800;
    color: #667eea;
    text-align: center;
    line-height: 1;
}

.stat-errors .stat-main-number {
    color: #dc3545;
}

.stat-details {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.stat-detail-item {
    display: flex;
    justify-content: center;
}

.stat-detail-item .badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
    font-weight: 600;
    border-radius: 20px;
    min-width: 90px;
    text-align: center;
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* ============================================
   قائمة الخدمات
   ============================================ */
.services-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
}

.service-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 2px solid rgba(102, 126, 234, 0.1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid rgba(102, 126, 234, 0.1);
}

.service-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.service-title i {
    font-size: 1.5rem;
    color: #667eea;
}

.service-name {
    font-size: 1.15rem;
    font-weight: 700;
    color: #333;
}

.service-status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.service-stats-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.service-stat-box {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    border-radius: 12px;
    padding: 1rem;
    border: 2px solid transparent;
    text-align: center;
}

.service-stat-box.revisions-box {
    border-color: rgba(102, 126, 234, 0.3);
}

.service-stat-box.errors-box {
    border-color: rgba(220, 53, 69, 0.3);
}

.service-stat-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.75rem;
}

.service-stat-label i {
    font-size: 1rem;
}

.service-stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.75rem;
}

.revisions-box .service-stat-number {
    color: #667eea;
}

.errors-box .service-stat-number {
    color: #dc3545;
}

.service-stat-details {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.4rem;
}

.service-stat-details .badge {
    font-size: 0.7rem;
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
}

@media (max-width: 768px) {
    .service-stats-container {
        grid-template-columns: 1fr;
    }
}

.sidebar-loading, .sidebar-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 300px;
    text-align: center;
    color: #6c757d;
    direction: rtl;
}

/* Hide Scrollbar but keep functionality */
.sidebar-body {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.sidebar-body::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

@media (max-width: 768px) {
    .sidebar-content {
        width: 100%;
        max-width: 100%;
    }

    .sidebar-tabs {
        padding: 0 1rem;
    }

    .sidebar-tabs .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
}
</style>

<script>
let currentProjectSidebar = null;

function cleanupDynamicTabs() {
    // Clear sub-tabs
    const subTabsNav = document.getElementById('customFieldsSubTabs');
    const subTabsContent = document.getElementById('customFieldsSubTabContent');

    if (subTabsNav) {
        subTabsNav.innerHTML = '';
    }

    if (subTabsContent) {
        subTabsContent.innerHTML = '';
    }

    // Hide custom fields tab by default
    const customFieldsTabLi = document.getElementById('custom-fields-tab-li');
    if (customFieldsTabLi) {
        customFieldsTabLi.style.display = 'none';
    }
}

function openProjectSidebar(projectId) {
    const sidebar = document.getElementById('projectSidebar');
    const loading = document.getElementById('sidebarLoading');
    const content = document.getElementById('sidebarContent');
    const error = document.getElementById('sidebarError');

    // Show sidebar
    sidebar.classList.add('active');
    loading.style.display = 'flex';
    content.style.display = 'none';
    error.style.display = 'none';

    // Fetch data
    fetch(`/projects/${projectId}/sidebar-details`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                populateSidebar(result.data);
                loading.style.display = 'none';
                content.style.display = 'block';
            } else {
                throw new Error(result.message);
            }
        })
        .catch(err => {
            console.error('Error loading sidebar:', err);
            loading.style.display = 'none';
            error.style.display = 'flex';
        });
}

function closeProjectSidebar() {
    const sidebar = document.getElementById('projectSidebar');
    sidebar.classList.remove('active');
}

function populateSidebar(data) {
    const project = data.project;

    // Clean up old dynamic tabs
    cleanupDynamicTabs();

    // Reset to first tab
    const overviewTab = document.getElementById('overview-tab');
    const overviewPane = document.getElementById('overview');
    if (overviewTab && overviewPane) {
        // Remove active class from all tabs
        document.querySelectorAll('.sidebar-tabs .nav-link').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });

        // Activate overview tab
        overviewTab.classList.add('active');
        overviewPane.classList.add('show', 'active');
    }

    // Update title
    document.getElementById('sidebarProjectName').textContent = project.name;

    // Overview Tab
    document.getElementById('projectCode').textContent = project.code || '-';
    document.getElementById('projectStatus').innerHTML = getStatusBadge(project.status);
    document.getElementById('projectClient').textContent = project.client_name;
    document.getElementById('projectManager').textContent = project.manager || '-';

    // Dates
    document.getElementById('projectStartDate').textContent = project.start_date || '-';
    document.getElementById('projectTeamDelivery').textContent = project.team_delivery_date || '-';
    document.getElementById('projectClientDelivery').textContent = project.client_agreed_delivery_date || '-';
    document.getElementById('projectActualDelivery').textContent = project.actual_delivery_date || '-';
    document.getElementById('projectDescription').textContent = project.description || 'لا يوجد وصف';

    // Preparation Period
    if (project.preparation_enabled) {
        document.getElementById('preparationSection').style.display = 'block';
        document.getElementById('preparationStatus').innerHTML = getPreparationStatusBadge(project.preparation_status);
        document.getElementById('preparationStartDate').textContent = project.preparation_start_date || '-';
        document.getElementById('preparationEndDate').textContent = project.preparation_end_date || '-';
        document.getElementById('preparationDays').textContent = project.preparation_days + ' يوم';

        if (project.preparation_status === 'active' && project.remaining_preparation_days > 0) {
            document.getElementById('preparationRemainingSection').style.display = 'block';
            document.getElementById('preparationRemaining').textContent = project.remaining_preparation_days + ' يوم';
        }
    }

    // Statistics (Revisions & Errors)
    if (data.stats) {
        // التعديلات
        if (data.stats.revisions) {
            document.getElementById('revisionsTotal').textContent = data.stats.revisions.total || 0;
            document.getElementById('revisionsNew').textContent = (data.stats.revisions.new || 0) + ' جديد';
            document.getElementById('revisionsInProgress').textContent = (data.stats.revisions.in_progress || 0) + ' قيد التنفيذ';
            document.getElementById('revisionsCompleted').textContent = (data.stats.revisions.completed || 0) + ' مكتمل';
        }

        // الأخطاء
        if (data.stats.errors) {
            document.getElementById('errorsTotal').textContent = data.stats.errors.total || 0;
            document.getElementById('errorsCritical').textContent = (data.stats.errors.critical || 0) + ' جوهري';
            document.getElementById('errorsNormal').textContent = (data.stats.errors.normal || 0) + ' عادي';
        }
    }

    // Services
    const servicesList = document.getElementById('servicesList');
    if (data.services && data.services.length > 0) {
        servicesList.innerHTML = data.services.map(service => {
            const stats = service.stats || { revisions: {}, errors: {} };
            const revisions = stats.revisions || {};
            const errors = stats.errors || {};

            return `
                <div class="service-card">
                    <div class="service-header">
                        <div class="service-title">
                            <i class="fas fa-cog"></i>
                            <span class="service-name">${service.name}</span>
                        </div>
                        <span class="service-status-badge badge ${getServiceStatusClass(service.status)}">
                            ${service.status}
                        </span>
                    </div>

                    <div class="service-stats-container">
                        <!-- التعديلات -->
                        <div class="service-stat-box revisions-box">
                            <div class="service-stat-label">
                                <i class="fas fa-edit"></i>
                                <span>التعديلات</span>
                            </div>
                            <div class="service-stat-number">${revisions.total || 0}</div>
                            <div class="service-stat-details">
                                <span class="badge bg-secondary">${revisions.new || 0} جديد</span>
                                <span class="badge bg-primary">${revisions.in_progress || 0} قيد التنفيذ</span>
                                <span class="badge bg-success">${revisions.completed || 0} مكتمل</span>
                            </div>
                        </div>

                        <!-- الأخطاء -->
                        <div class="service-stat-box errors-box">
                            <div class="service-stat-label">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>الأخطاء</span>
                            </div>
                            <div class="service-stat-number">${errors.total || 0}</div>
                            <div class="service-stat-details">
                                <span class="badge bg-danger">${errors.critical || 0} جوهري</span>
                                <span class="badge bg-warning">${errors.normal || 0} عادي</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        servicesList.innerHTML = '<p class="text-muted text-center py-4">لا توجد خدمات في هذا المشروع</p>';
    }

    // Participants
    const participantsList = document.getElementById('participantsList');
    if (data.participants.length > 0) {
        participantsList.innerHTML = data.participants.map(p => `
            <div class="participant-card">
                <div class="participant-header">
                    <div>
                        <div class="participant-name">
                            <i class="fas fa-user-circle text-primary me-2"></i>
                            ${p.user_name}
                        </div>
                        <div class="participant-email">${p.user_email}</div>
                    </div>
                    ${p.is_acknowledged ? '<span class="badge bg-success"><i class="fas fa-check"></i> مستلم</span>' : '<span class="badge bg-warning">لم يستلم</span>'}
                </div>
                <div class="participant-details">
                    <div class="participant-detail">
                        <span class="info-label">الخدمة:</span>
                        <span class="info-value">${p.service_name}</span>
                    </div>
                    <div class="participant-detail">
                        <span class="info-label">الدور:</span>
                        <span class="info-value">${p.role_name}</span>
                    </div>
                    <div class="participant-detail">
                        <span class="info-label">نسبة المشاركة:</span>
                        <span class="info-value">${p.project_share}</span>
                    </div>
                    <div class="participant-detail">
                        <span class="info-label">الموعد النهائي:</span>
                        <span class="info-value ${p.is_overdue ? 'text-danger' : ''}">
                            ${p.deadline || 'غير محدد'}
                            ${p.deadline && p.days_remaining !== null ? `<br><small>(${p.is_overdue ? 'متأخر ' + Math.abs(p.days_remaining) : 'باقي ' + p.days_remaining} يوم)</small>` : ''}
                        </span>
                    </div>
                </div>
            </div>
        `).join('');
    } else {
        participantsList.innerHTML = '<p class="text-muted text-center py-4">لا يوجد مشاركين في المشروع</p>';
    }

    // Preparation History
    const preparationHistoryContent = document.getElementById('preparationHistoryContent');
    const preparationHistoryTabLi = document.getElementById('preparation-history-tab-li');

    if (data.preparation_history && data.preparation_history.length > 0) {
        // إظهار التاب
        preparationHistoryTabLi.style.display = 'block';

        // إنشاء HTML للتاريخ
        preparationHistoryContent.innerHTML = `
            ${data.preparation_periods_count > 1 ? `
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>ملاحظة:</strong> هذا المشروع دخل في ${data.preparation_periods_count} فترة تحضير مختلفة
                </div>
            ` : ''}

            <div class="timeline">
                ${data.preparation_history.map((history, index) => `
                    <div class="timeline-item ${history.is_current ? 'current' : ''}">
                        <div class="timeline-marker">
                            <i class="fas ${history.is_current ? 'fa-star' : 'fa-clock'}"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h6 class="mb-1">
                                    ${history.is_current ? '<span class="badge bg-success me-2">الفترة الحالية</span>' : ''}
                                    ${history.is_active ? '<span class="badge bg-warning text-dark me-2">نشطة</span>' : ''}
                                    فترة تحضير #${data.preparation_history.length - index}
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>${history.user_name} • ${history.time_ago}
                                </small>
                            </div>
                            <div class="timeline-body mt-2">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted">تاريخ البداية:</small>
                                        <div class="fw-bold">${history.preparation_start_date || 'غير محدد'}</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">تاريخ النهاية:</small>
                                        <div class="fw-bold">${history.preparation_end_date || 'غير محدد'}</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">المدة:</small>
                                        <div class="fw-bold">
                                            <span class="badge bg-info">${history.duration_text}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">تاريخ السريان:</small>
                                        <div class="fw-bold">${history.effective_from || 'غير محدد'}</div>
                                    </div>
                                </div>
                                ${history.notes ? `
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small class="text-muted">
                                            <i class="fas fa-sticky-note me-1"></i>
                                            ${history.notes}
                                        </small>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    } else {
        // إخفاء التاب
        preparationHistoryTabLi.style.display = 'none';
        preparationHistoryContent.innerHTML = '<p class="text-center text-muted">لا توجد سجلات لفترات التحضير</p>';
    }

    // Generate Dynamic Custom Fields Sub-Tabs
    const subTabsNav = document.getElementById('customFieldsSubTabs');
    const subTabsContent = document.getElementById('customFieldsSubTabContent');
    const customFieldsTabLi = document.getElementById('custom-fields-tab-li');

    let hasCustomFields = false;
    let isFirstSubTab = true;

    // Project Custom Fields Sub-Tab
    if (data.project_custom_fields && data.project_custom_fields.length > 0) {
        hasCustomFields = true;

        // Add Sub-Tab Button
        const subTabButton = document.createElement('li');
        subTabButton.className = 'nav-item';
        subTabButton.setAttribute('role', 'presentation');
        subTabButton.innerHTML = `
            <button class="nav-link ${isFirstSubTab ? 'active' : ''}" id="customer-service-sub-tab" data-bs-toggle="tab" data-bs-target="#customer-service-sub" type="button" role="tab">
                <i class="fas fa-user-tie me-1"></i>
                خدمة العملاء
            </button>
        `;
        subTabsNav.appendChild(subTabButton);

        // Add Sub-Tab Content
        const subTabContent = document.createElement('div');
        subTabContent.className = `tab-pane fade ${isFirstSubTab ? 'show active' : ''}`;
        subTabContent.id = 'customer-service-sub';
        subTabContent.setAttribute('role', 'tabpanel');
        subTabContent.innerHTML = `
            <div class="info-section">
                <h6 class="info-section-title">
                    <i class="fas fa-user-tie text-primary"></i>
                    حقول خدمة العملاء
                </h6>
                <div class="info-grid">
                    ${data.project_custom_fields.map(field => `
                        <div class="info-item">
                            <span class="info-label">${field.label}:</span>
                            <span class="info-value">${field.value}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        subTabsContent.appendChild(subTabContent);
        isFirstSubTab = false;
    }

    // Service Data Fields Sub-Tabs
    if (data.service_data_fields && Object.keys(data.service_data_fields).length > 0) {
        hasCustomFields = true;

        Object.entries(data.service_data_fields).forEach(([serviceName, fields], index) => {
            const safeId = 'service-sub-' + index;

            // Add Sub-Tab Button
            const subTabButton = document.createElement('li');
            subTabButton.className = 'nav-item';
            subTabButton.setAttribute('role', 'presentation');
            subTabButton.innerHTML = `
                <button class="nav-link ${isFirstSubTab ? 'active' : ''}" id="${safeId}-tab" data-bs-toggle="tab" data-bs-target="#${safeId}" type="button" role="tab">
                    <i class="fas fa-cog me-1"></i>
                    ${serviceName}
                </button>
            `;
            subTabsNav.appendChild(subTabButton);

            // Add Sub-Tab Content
            const subTabContent = document.createElement('div');
            subTabContent.className = `tab-pane fade ${isFirstSubTab ? 'show active' : ''}`;
            subTabContent.id = safeId;
            subTabContent.setAttribute('role', 'tabpanel');
            subTabContent.innerHTML = `
                <div class="info-section">
                    <h6 class="info-section-title">
                        <i class="fas fa-cog text-info"></i>
                        ${serviceName}
                    </h6>
                    <div class="info-grid">
                        ${fields.map(field => `
                            <div class="info-item">
                                <span class="info-label">${field.label}:</span>
                                <span class="info-value">${field.value}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            subTabsContent.appendChild(subTabContent);
            isFirstSubTab = false;
        });
    }

    // Show/Hide Custom Fields Tab
    if (customFieldsTabLi) {
        customFieldsTabLi.style.display = hasCustomFields ? 'block' : 'none';
    }
}

function getStatusBadge(status) {
    const statusMap = {
        'جديد': 'bg-primary',
        'جاري التنفيذ': 'bg-info',
        'مكتمل': 'bg-success',
        'ملغي': 'bg-danger'
    };
    return `<span class="badge ${statusMap[status] || 'bg-secondary'}">${status}</span>`;
}

function getServiceStatusClass(status) {
    const statusMap = {
        'لم تبدأ': 'bg-secondary',
        'جاري التنفيذ': 'bg-primary',
        'مكتملة': 'bg-success',
        'متأخرة': 'bg-danger',
        'معلقة': 'bg-warning'
    };
    return statusMap[status] || 'bg-secondary';
}

function getPreparationStatusBadge(status) {
    const statusMap = {
        'active': '<span class="badge" style="background: linear-gradient(135deg, #667eea, #764ba2);"><i class="fas fa-spinner fa-pulse"></i> جارية</span>',
        'pending': '<span class="badge" style="background: linear-gradient(135deg, #f093fb, #f5576c);"><i class="fas fa-clock"></i> لم تبدأ</span>',
        'completed': '<span class="badge" style="background: linear-gradient(135deg, #4facfe, #00f2fe);"><i class="fas fa-check-circle"></i> انتهت</span>'
    };
    return statusMap[status] || '-';
}

// Close sidebar when clicking escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProjectSidebar();
    }
});
</script>

<style>
/* Timeline Styles for Preparation History */
.timeline {
    position: relative;
    padding: 1rem 0;
}

.timeline-item {
    position: relative;
    padding-right: 2.5rem;
    padding-bottom: 1.5rem;
    border-right: 2px solid #e5e7eb;
}

.timeline-item:last-child {
    border-right: none;
    padding-bottom: 0;
}

.timeline-item.current {
    border-right-color: #10b981;
}

.timeline-marker {
    position: absolute;
    right: -0.75rem;
    top: 0;
    width: 1.5rem;
    height: 1.5rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: #6b7280;
}

.timeline-item.current .timeline-marker {
    background: linear-gradient(135deg, #10b981, #059669);
    border-color: #10b981;
    color: white;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

.timeline-content {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.timeline-content:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transform: translateX(-2px);
}

.timeline-item.current .timeline-content {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.05));
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.timeline-header h6 {
    color: #1f2937;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.timeline-body {
    margin-top: 0.75rem;
}

.timeline-body .row {
    margin-bottom: 0.5rem;
}

.timeline-body small {
    color: #6b7280;
    font-size: 0.75rem;
    display: block;
    margin-bottom: 0.25rem;
}

.timeline-body .fw-bold {
    color: #1f2937;
    font-size: 0.875rem;
}

/* Badge Styles */
.badge.bg-success {
    background: linear-gradient(135deg, #10b981, #059669) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
}

.badge.bg-info {
    background: linear-gradient(135deg, #06b6d4, #0891b2) !important;
}
</style>

