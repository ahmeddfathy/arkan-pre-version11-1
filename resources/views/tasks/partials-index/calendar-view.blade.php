<!-- Calendar View -->
<div id="calendarBoard" class="calendar-view" style="display: none;">
    <div class="calendar-container">
        <!-- Calendar Header -->
        <div class="calendar-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary" id="prevMonthIndex">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h5 id="currentMonthYearIndex" class="mb-0 fw-bold"></h5>
                    <button class="btn btn-outline-secondary" id="nextMonthIndex">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-primary" id="backToTableBtnIndex">
                        <i class="fas fa-table me-1"></i>
                        العودة للجدول
                    </button>
                    <button class="btn btn-sm btn-primary" id="todayBtnIndex">اليوم</button>
                </div>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid">
            <!-- Days of Week Header -->
            <div class="calendar-weekdays">
                <div class="calendar-weekday">الأحد</div>
                <div class="calendar-weekday">الاثنين</div>
                <div class="calendar-weekday">الثلاثاء</div>
                <div class="calendar-weekday">الأربعاء</div>
                <div class="calendar-weekday">الخميس</div>
                <div class="calendar-weekday">الجمعة</div>
                <div class="calendar-weekday">السبت</div>
            </div>

            <!-- Calendar Days -->
            <div class="calendar-days" id="calendarDaysIndex">
                <!-- Days will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>
