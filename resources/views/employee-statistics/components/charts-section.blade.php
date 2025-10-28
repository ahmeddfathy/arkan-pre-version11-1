<!-- Charts Section -->
<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0 text-center">
                    <i class="fas fa-chart-pie me-2"></i> نسب الحضور والغياب
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0 text-center">
                    <i class="fas fa-chart-bar me-2"></i> الإجازات والأذونات
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="leavesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0 text-center">
                    <i class="fas fa-clock me-2"></i> إحصائيات التأخير والوقت الإضافي
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="timeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
