<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 bg-light">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    ملخص المشروع
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-white">
                            <h4 class="text-primary">{{ $project->services->count() }}</h4>
                            <p class="text-muted mb-0">عدد الخدمات</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-white">
                            <h4 class="text-success">{{ $project->total_points }}</h4>
                            <p class="text-muted mb-0">مجموع النقاط</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-white">
                            <h4 class="text-warning">
                                {{ $project->services->where('pivot.service_status', 'قيد التنفيذ')->count() }}
                            </h4>
                            <p class="text-muted mb-0">قيد التنفيذ</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-white">
                            <h4 class="text-success">
                                {{ $project->services->where('pivot.service_status', 'مكتملة')->count() }}
                            </h4>
                            <p class="text-muted mb-0">مكتملة</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
