@extends('layouts.app')

@section('content')
<div class="services-analytics-container fade-in" data-project-id="{{ $project->id }}">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="text-white mb-1">إحصائيات خدمات المشروع</h2>
                    <h5 class="text-light mb-0">{{ $project->name }}</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>
                        العودة للمشروع
                    </a>
                    <a href="{{ route('projects.analytics', $project) }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-chart-line me-1"></i>
                        إحصائيات عامة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Progress Overview Cards -->
    <div class="overview-cards slide-up" data-loading-target>
        <div class="overview-card total-services bounce-in">
            <div class="card-icon total">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="card-title">إجمالي الخدمات</div>
            <div class="card-value" id="total-services">-</div>
        </div>

        <div class="overview-card completed-services bounce-in">
            <div class="card-icon completed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-title">الخدمات المكتملة</div>
            <div class="card-value" id="completed-services">-</div>
        </div>

        <div class="overview-card completion-rate bounce-in">
            <div class="card-icon rate">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="card-title">معدل الإكمال</div>
            <div class="card-value"><span id="completion-rate">-</span>%</div>
        </div>

        <div class="overview-card points-rate bounce-in">
            <div class="card-icon points">
                <i class="fas fa-star"></i>
            </div>
            <div class="card-title">معدل النقاط</div>
            <div class="card-value"><span id="points-rate">-</span>%</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4" data-loading-target>
        <div class="col-md-6">
            <div class="chart-card slide-up">
                <div class="chart-header">
                    <h6 class="chart-title">توزيع حالات الخدمات</h6>
                </div>
                <div class="chart-container">
                    <canvas id="service-status-chart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card slide-up">
                <div class="chart-header">
                    <h6 class="chart-title">معدل الإكمال حسب القسم</h6>
                </div>
                <div class="chart-container">
                    <canvas id="department-progress-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Progress -->
    <div class="department-progress-card fade-in mb-4" data-loading-target>
        <div class="department-progress-header">
            <h6 class="chart-title">تقدم الأقسام</h6>
        </div>
        <div class="p-0">
            <div id="department-progress-container" class="department-progress">
                <!-- Content will be loaded via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Project Revisions -->
    @if(isset($revisionStats) && $revisionStats['total'] > 0)
    <div class="revisions-section fade-in mb-4" data-loading-target>
        <div class="section-header">
            <h6 class="section-title">
                <i class="fas fa-clipboard-list me-2"></i>
                تعديلات المشروع
            </h6>
        </div>

        <div class="revisions-overview-cards">
            <div class="revision-card total">
                <div class="revision-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="revision-content">
                    <div class="revision-number">{{ $revisionStats['total'] }}</div>
                    <div class="revision-label">إجمالي التعديلات</div>
                </div>
            </div>

            <div class="revision-card pending">
                <div class="revision-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="revision-content">
                    <div class="revision-number">{{ $revisionStats['pending'] }}</div>
                    <div class="revision-label">معلقة</div>
                </div>
            </div>

            <div class="revision-card approved">
                <div class="revision-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="revision-content">
                    <div class="revision-number">{{ $revisionStats['approved'] }}</div>
                    <div class="revision-label">موافق عليها</div>
                </div>
            </div>

            <div class="revision-card rejected">
                <div class="revision-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="revision-content">
                    <div class="revision-number">{{ $revisionStats['rejected'] }}</div>
                    <div class="revision-label">مرفوضة</div>
                </div>
            </div>
        </div>

        @if($urgentRevisions->count() > 0 || $latestRevisions->count() > 0)
        <div class="revisions-alerts mt-4">
            @if($urgentRevisions->count() > 0)
            <div class="alert-card urgent">
                <div class="alert-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h6>تعديلات ملحة</h6>
                    <span class="alert-count">{{ $urgentRevisions->count() }}</span>
                </div>
                <div class="alert-content">
                    @foreach($urgentRevisions->take(3) as $revision)
                    <div class="alert-item">
                        <div class="alert-item-info">
                            <div class="alert-title">{{ Str::limit($revision->title, 30) }}</div>
                            <div class="alert-meta">{{ $revision->revision_date->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($latestRevisions->count() > 0)
            <div class="alert-card latest">
                <div class="alert-header">
                    <i class="fas fa-history"></i>
                    <h6>آخر التعديلات</h6>
                    <span class="alert-count">{{ $latestRevisions->count() }}</span>
                </div>
                <div class="alert-content">
                    @foreach($latestRevisions->take(3) as $revision)
                    <div class="alert-item">
                        <div class="alert-item-info">
                            <div class="alert-title">{{ Str::limit($revision->title, 30) }}</div>
                            <div class="alert-meta">
                                <span class="status-badge status-{{ $revision->status }}">
                                    @if($revision->status == 'approved')
                                        موافق
                                    @elseif($revision->status == 'rejected')
                                        مرفوض
                                    @else
                                        معلق
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    <!-- Services List with Progress -->
    <div class="services-table-card fade-in" data-loading-target>
        <div class="services-table-header">
            <h6 class="services-table-title">تفاصيل الخدمات</h6>
            <div class="table-actions">
                <button class="btn-refresh" onclick="serviceAnalytics.loadServiceData()" data-action="refresh">
                    <i class="fas fa-refresh me-1"></i>
                    تحديث البيانات
                </button>
                <button class="btn-alerts" onclick="serviceAnalytics.toggleServiceAlerts()">
                    <i class="fas fa-bell me-1"></i>
                    عرض التنبيهات
                </button>
            </div>
        </div>

        <!-- Service Alerts -->
        <div id="service-alerts" class="alert-container" style="display: none;">
            <div class="alert-header">
                <h6 class="alert-title">تنبيهات الخدمات</h6>
            </div>
            <div class="alert-content" id="alerts-content">
                <!-- Alert items will be loaded via JavaScript -->
            </div>
        </div>

        <!-- Services Table -->
        <div class="services-table-wrapper">
            <table class="services-table" id="services-table">
                <thead>
                    <tr>
                        <th>اسم الخدمة</th>
                        <th>الحالة</th>
                        <th>نسبة التقدم</th>
                        <th>المشاركون</th>
                        <th>إكمال المهام</th>
                        <th>النقاط</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Service Progress Modal -->
    <div class="modal fade" id="serviceProgressModal" tabindex="-1" aria-labelledby="serviceProgressModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceProgressModalLabel">
                        <i class="fas fa-chart-line"></i>
                        تحديث تقدم الخدمة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="serviceProgressForm">
                        <input type="hidden" id="serviceId" name="service_id">

                        <div class="form-group">
                            <label for="serviceStatus" class="form-label">
                                <i class="fas fa-flag"></i>
                                حالة الخدمة
                            </label>
                            <select class="form-control" id="serviceStatus" name="status" required>
                                <option value="">اختر حالة الخدمة</option>
                                <option value="لم تبدأ">🔘 لم تبدأ</option>
                                <option value="قيد التنفيذ">🔄 قيد التنفيذ</option>
                                <option value="مكتملة">✅ مكتملة</option>
                                <option value="معلقة">⏸️ معلقة</option>
                                <option value="ملغية">❌ ملغية</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="progressPercentage" class="form-label">
                                <i class="fas fa-percentage"></i>
                                نسبة التقدم (%)
                            </label>
                            <input type="range" class="form-range" id="progressPercentage" name="progress_percentage"
                                   min="0" max="100" step="5" value="50">
                            <div class="range-labels">
                                <span>0%</span>
                                <span class="range-value" id="progressValue">50%</span>
                                <span>100%</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="progressNotes" class="form-label">
                                <i class="fas fa-sticky-note"></i>
                                ملاحظات التقدم
                            </label>
                            <textarea class="form-control" id="progressNotes" name="progress_notes"
                                      rows="4" placeholder="أضف ملاحظات مفصلة حول تقدم الخدمة والخطوات المكتملة..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-info-circle"></i>
                                معاينة الحالة
                            </label>
                            <div id="statusPreview" class="status-preview">
                                <div class="progress-indicator">
                                    <div class="progress-fill" style="width: 50%"></div>
                                </div>
                                <div class="status-info mt-2">
                                    <span class="status-badge status-قيد-التنفيذ">قيد التنفيذ</span>
                                    <span class="progress-text">50% مكتمل</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        إلغاء
                    </button>
                    <button type="button" class="btn btn-primary" onclick="serviceAnalytics.updateServiceProgress()">
                        <i class="fas fa-save"></i>
                        حفظ التحديث
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Service History Modal -->
    <div class="modal fade" id="serviceHistoryModal" tabindex="-1" aria-labelledby="serviceHistoryModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceHistoryModalLabel">
                        <i class="fas fa-history"></i>
                        تاريخ تقدم الخدمة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Loading State -->
                    <div class="modal-loading" id="historyLoading" style="display: none;">
                        <div class="spinner"></div>
                        <div class="modal-loading-text">جاري تحميل سجل التقدم...</div>
                    </div>

                    <!-- History Content -->
                    <div id="service-history-content">
                        <!-- Sample History Items for Demo -->
                        <div class="history-item">
                            <div class="history-date">
                                <i class="fas fa-clock"></i>
                                2024-01-15 - 10:30 ص
                            </div>
                            <div class="history-action">تم تحديث حالة الخدمة إلى "قيد التنفيذ"</div>
                            <div class="history-details">
                                تم البدء في تنفيذ المهام الأساسية للخدمة. فريق التطوير بدأ العمل على الواجهات الأولية.
                            </div>
                        </div>

                        <div class="history-item">
                            <div class="history-date">
                                <i class="fas fa-clock"></i>
                                2024-01-12 - 02:15 م
                            </div>
                            <div class="history-action">تم إنشاء الخدمة</div>
                            <div class="history-details">
                                تم إنشاء الخدمة الجديدة وتعيين الفريق المسؤول عن التنفيذ.
                            </div>
                        </div>

                        <div class="history-item">
                            <div class="history-date">
                                <i class="fas fa-clock"></i>
                                2024-01-10 - 11:45 ص
                            </div>
                            <div class="history-action">تم الموافقة على المتطلبات</div>
                            <div class="history-details">
                                تمت مراجعة وموافقة المتطلبات الفنية للخدمة من قبل فريق الإدارة.
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div id="history-empty-state" style="display: none;" class="text-center p-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">لا يوجد سجل تقدم متاح</h6>
                        <p class="text-muted">لم يتم تسجيل أي تحديثات لهذه الخدمة بعد.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        إغلاق
                    </button>
                    <button type="button" class="btn btn-primary" onclick="serviceAnalytics.exportHistory()">
                        <i class="fas fa-download"></i>
                        تصدير السجل
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Timeline Chart Modal -->
    <div class="modal fade" id="serviceTimelineModal" tabindex="-1" aria-labelledby="serviceTimelineModalLabel">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceTimelineModalLabel">
                        <i class="fas fa-chart-gantt"></i>
                        الجدول الزمني للخدمات
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Chart Controls -->
                    <div class="chart-controls mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fas fa-calendar"></i>
                                    نطاق التاريخ
                                </label>
                                <select class="form-control" id="timelineRange">
                                    <option value="week">الأسبوع الحالي</option>
                                    <option value="month" selected>الشهر الحالي</option>
                                    <option value="quarter">الربع الحالي</option>
                                    <option value="year">السنة الحالية</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fas fa-filter"></i>
                                    فلتر الحالة
                                </label>
                                <select class="form-control" id="statusFilter">
                                    <option value="all">جميع الحالات</option>
                                    <option value="لم تبدأ">لم تبدأ</option>
                                    <option value="قيد التنفيذ">قيد التنفيذ</option>
                                    <option value="مكتملة">مكتملة</option>
                                    <option value="معلقة">معلقة</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fas fa-eye"></i>
                                    نوع العرض
                                </label>
                                <select class="form-control" id="chartType">
                                    <option value="gantt">مخطط جانت</option>
                                    <option value="timeline">الخط الزمني</option>
                                    <option value="progress">مؤشر التقدم</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div class="modal-loading" id="chartLoading" style="display: none;">
                        <div class="spinner"></div>
                        <div class="modal-loading-text">جاري تحميل البيانات...</div>
                    </div>

                    <!-- Chart Container -->
                    <div class="chart-container" style="height: 500px;">
                        <canvas id="service-timeline-chart"></canvas>
                    </div>

                    <!-- Chart Legend -->
                    <div class="chart-legend mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="legend-title">
                                    <i class="fas fa-info-circle"></i>
                                    شرح الرموز
                                </h6>
                                <div class="legend-items">
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: var(--arkan-success);"></span>
                                        <span>خدمات مكتملة</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: var(--arkan-primary);"></span>
                                        <span>خدمات قيد التنفيذ</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: var(--arkan-warning);"></span>
                                        <span>خدمات معلقة</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-color" style="background: var(--gray-400);"></span>
                                        <span>خدمات لم تبدأ</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="legend-title">
                                    <i class="fas fa-chart-bar"></i>
                                    إحصائيات سريعة
                                </h6>
                                <div class="quick-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">إجمالي الخدمات:</span>
                                        <span class="stat-value" id="totalServicesCount">-</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">معدل الإكمال:</span>
                                        <span class="stat-value" id="completionRateValue">-</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">المدة المتوقعة:</span>
                                        <span class="stat-value" id="estimatedDuration">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        إغلاق
                    </button>
                    <button type="button" class="btn btn-info" onclick="serviceAnalytics.refreshChart()">
                        <i class="fas fa-sync-alt"></i>
                        تحديث البيانات
                    </button>
                    <button type="button" class="btn btn-primary" onclick="serviceAnalytics.exportChart()">
                        <i class="fas fa-image"></i>
                        تصدير كصورة
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-analytics.css') }}">
<style>
/* Revisions Section Styles */
.revisions-section {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid #e3e6ef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.revisions-overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.revision-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.revision-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}

.revision-card.total::before { background: linear-gradient(90deg, #4BAAD4, #5bc0de); }
.revision-card.pending::before { background: linear-gradient(90deg, #ffad46, #ffc107); }
.revision-card.approved::before { background: linear-gradient(90deg, #23c277, #28a745); }
.revision-card.rejected::before { background: linear-gradient(90deg, #e74c3c, #dc3545); }

.revision-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.revision-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 20px;
    color: white;
}

.revision-card.total .revision-icon { background: linear-gradient(135deg, #4BAAD4, #5bc0de); }
.revision-card.pending .revision-icon { background: linear-gradient(135deg, #ffad46, #ffc107); }
.revision-card.approved .revision-icon { background: linear-gradient(135deg, #23c277, #28a745); }
.revision-card.rejected .revision-icon { background: linear-gradient(135deg, #e74c3c, #dc3545); }

.revision-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.revision-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
}

.revisions-alerts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.alert-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.alert-card.urgent {
    border-left: 4px solid #e74c3c;
}

.alert-card.latest {
    border-left: 4px solid #4BAAD4;
}

.alert-header {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1px solid #e9ecef;
}

.alert-header i {
    font-size: 1.1rem;
    color: #495057;
}

.alert-header h6 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3e50;
    flex: 1;
}

.alert-count {
    background: #495057;
    color: white;
    border-radius: 16px;
    padding: 0.25rem 0.6rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.alert-content {
    padding: 1rem;
}

.alert-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.alert-item:last-child {
    border-bottom: none;
}

.alert-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
    font-size: 0.85rem;
}

.alert-meta {
    font-size: 0.75rem;
    color: #6c757d;
}

.status-badge {
    padding: 0.15rem 0.4rem;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 500;
}

.status-badge.status-approved { background: #d4edda; color: #155724; }
.status-badge.status-rejected { background: #f8d7da; color: #721c24; }
.status-badge.status-pending { background: #fff3cd; color: #856404; }

@media (max-width: 768px) {
    .revisions-overview-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .revisions-alerts {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .revisions-overview-cards {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/projects/service-analytics.js') }}"></script>
@endpush
@endsection
