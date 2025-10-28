@extends('layouts.app')

@section('title', 'إدارة أنواع المهام الجرافيكية')

@push('styles')
<link href="{{ asset('css/graphic-task-types.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-palette me-2"></i>
                        إدارة أنواع المهام الجرافيكية
                    </h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="fas fa-plus me-1"></i>
                        إضافة نوع جديد
                    </button>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>اسم النوع</th>
                                    <th>الوصف</th>
                                    <th>النقاط</th>
                                    <th>نطاق الوقت (دقيقة)</th>
                                    <th>الوقت المتوسط</th>
                                    <th>القسم</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($graphicTaskTypes as $type)
                                    <tr>
                                        <td>
                                            <strong>{{ $type->name }}</strong>
                                        </td>
                                        <td>
                                            {{ Str::limit($type->description, 50) ?? 'لا يوجد وصف' }}
                                        </td>
                                        <td>
                                            <span class="badge bg-primary fs-6">
                                                {{ $type->points }} نقطة
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ $type->min_minutes }} - {{ $type->max_minutes }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $type->average_time_formatted }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark">{{ $type->department }}</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm toggle-status-btn {{ $type->is_active ? 'btn-success' : 'btn-danger' }}"
                                                    data-id="{{ $type->id }}"
                                                    title="{{ $type->is_active ? 'نشط' : 'غير نشط' }}">
                                                <i class="fas {{ $type->is_active ? 'fa-check' : 'fa-times' }}"></i>
                                                {{ $type->is_active ? 'نشط' : 'غير نشط' }}
                                            </button>
                                        </td>
                                        <td>
                                                                                        <div class="btn-group">
                                                <button class="btn btn-outline-info btn-sm show-btn"
                                                        data-id="{{ $type->id }}"
                                                        title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-warning btn-sm edit-btn"
                                                        data-id="{{ $type->id }}"
                                                        title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm delete-btn"
                                                        data-id="{{ $type->id }}"
                                                        data-name="{{ $type->name }}"
                                                        title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                                <p>لا توجد أنواع مهام جرافيكية مضافة بعد</p>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                                                    <i class="fas fa-plus me-1"></i>
                                                    إضافة النوع الأول
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($graphicTaskTypes->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $graphicTaskTypes->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف نوع المهمة: <strong id="deleteItemName"></strong>؟</p>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    لا يمكن التراجع عن هذا الإجراء
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        حذف
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="createForm" method="POST" action="{{ route('graphic-task-types.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        إضافة نوع مهمة جرافيكية جديد
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_name" class="form-label">اسم النوع *</label>
                                <input type="text" class="form-control" id="create_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_department" class="form-label">القسم *</label>
                                <input type="text" class="form-control" id="create_department" name="department" value="التصميم" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="create_description" class="form-label">الوصف</label>
                        <textarea class="form-control" id="create_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="create_points" class="form-label">النقاط *</label>
                                <input type="number" class="form-control" id="create_points" name="points" min="1" max="100" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="create_min_minutes" class="form-label">الحد الأدنى (دقيقة) *</label>
                                <input type="number" class="form-control" id="create_min_minutes" name="min_minutes" min="1" max="1440" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="create_max_minutes" class="form-label">الحد الأقصى (دقيقة) *</label>
                                <input type="number" class="form-control" id="create_max_minutes" name="max_minutes" min="1" max="1440" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_average_minutes" class="form-label">المتوسط (دقيقة) *</label>
                                <input type="number" class="form-control" id="create_average_minutes" name="average_minutes" min="1" max="1440" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 d-flex align-items-center">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="create_is_active">
                                        نشط
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        تعديل نوع المهمة الجرافيكية
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">اسم النوع *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_department" class="form-label">القسم *</label>
                                <input type="text" class="form-control" id="edit_department" name="department" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">الوصف</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_points" class="form-label">النقاط *</label>
                                <input type="number" class="form-control" id="edit_points" name="points" min="1" max="100" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_min_minutes" class="form-label">الحد الأدنى (دقيقة) *</label>
                                <input type="number" class="form-control" id="edit_min_minutes" name="min_minutes" min="1" max="1440" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_max_minutes" class="form-label">الحد الأقصى (دقيقة) *</label>
                                <input type="number" class="form-control" id="edit_max_minutes" name="max_minutes" min="1" max="1440" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_average_minutes" class="form-label">المتوسط (دقيقة) *</label>
                                <input type="number" class="form-control" id="edit_average_minutes" name="average_minutes" min="1" max="1440" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 d-flex align-items-center">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">
                                        نشط
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        تحديث
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Show Modal -->
<div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>
                    تفاصيل نوع المهمة الجرافيكية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>اسم النوع:</strong>
                            <p id="show_name" class="text-muted mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>القسم:</strong>
                            <p id="show_department" class="text-muted mb-0"></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <strong>الوصف:</strong>
                    <p id="show_description" class="text-muted mb-0"></p>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <strong>النقاط:</strong>
                            <span id="show_points" class="badge bg-primary fs-6"></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <strong>الحد الأدنى:</strong>
                            <span id="show_min_minutes" class="badge bg-info"></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <strong>الحد الأقصى:</strong>
                            <span id="show_max_minutes" class="badge bg-info"></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <strong>المتوسط:</strong>
                            <span id="show_average_minutes" class="badge bg-secondary"></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>الحالة:</strong>
                            <span id="show_status" class="badge"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>عدد المهام المرتبطة:</strong>
                            <span id="show_tasks_count" class="badge bg-dark"></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>تاريخ الإنشاء:</strong>
                            <p id="show_created_at" class="text-muted mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>آخر تحديث:</strong>
                            <p id="show_updated_at" class="text-muted mb-0"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete button clicks
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;

            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteForm').action = `/graphic-task-types/${id}`;

            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    // Handle edit button clicks
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;

            fetch(`/graphic-task-types/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;

                        // Fill form fields
                        document.getElementById('edit_name').value = item.name;
                        document.getElementById('edit_department').value = item.department;
                        document.getElementById('edit_description').value = item.description || '';
                        document.getElementById('edit_points').value = item.points;
                        document.getElementById('edit_min_minutes').value = item.min_minutes;
                        document.getElementById('edit_max_minutes').value = item.max_minutes;
                        document.getElementById('edit_average_minutes').value = item.average_minutes;
                        document.getElementById('edit_is_active').checked = item.is_active;

                        // Set form action
                        document.getElementById('editForm').action = `/graphic-task-types/${id}`;

                        // Show modal
                        new bootstrap.Modal(document.getElementById('editModal')).show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء تحميل البيانات');
                });
        });
    });

    // Handle show button clicks
    document.querySelectorAll('.show-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;

            fetch(`/graphic-task-types/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;

                        // Fill show fields
                        document.getElementById('show_name').textContent = item.name;
                        document.getElementById('show_department').textContent = item.department;
                        document.getElementById('show_description').textContent = item.description || 'لا يوجد وصف';
                        document.getElementById('show_points').textContent = item.points + ' نقطة';
                        document.getElementById('show_min_minutes').textContent = item.min_minutes + ' دقيقة';
                        document.getElementById('show_max_minutes').textContent = item.max_minutes + ' دقيقة';
                        document.getElementById('show_average_minutes').textContent = item.average_time_formatted;
                        document.getElementById('show_tasks_count').textContent = item.tasks_count + ' مهمة';
                        document.getElementById('show_created_at').textContent = new Date(item.created_at).toLocaleDateString('ar-EG');
                        document.getElementById('show_updated_at').textContent = new Date(item.updated_at).toLocaleDateString('ar-EG');

                        // Status badge
                        const statusBadge = document.getElementById('show_status');
                        if (item.is_active) {
                            statusBadge.className = 'badge bg-success';
                            statusBadge.textContent = 'نشط';
                        } else {
                            statusBadge.className = 'badge bg-danger';
                            statusBadge.textContent = 'غير نشط';
                        }

                        // Show modal
                        new bootstrap.Modal(document.getElementById('showModal')).show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء تحميل البيانات');
                });
        });
    });

    // Handle status toggle
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const currentBtn = this;

            fetch(`/graphic-task-types/${id}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button appearance
                    if (data.is_active) {
                        currentBtn.className = 'btn btn-sm toggle-status-btn btn-success';
                        currentBtn.innerHTML = '<i class="fas fa-check"></i> نشط';
                        currentBtn.title = 'نشط';
                    } else {
                        currentBtn.className = 'btn btn-sm toggle-status-btn btn-danger';
                        currentBtn.innerHTML = '<i class="fas fa-times"></i> غير نشط';
                        currentBtn.title = 'غير نشط';
                    }

                    // Show success message
                    showAlert('success', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تحديث الحالة');
            });
        });
    });

    // Auto-calculate average when min/max changes
    function updateAverage(prefix) {
        const minInput = document.getElementById(prefix + '_min_minutes');
        const maxInput = document.getElementById(prefix + '_max_minutes');
        const avgInput = document.getElementById(prefix + '_average_minutes');

        function calculate() {
            const min = parseInt(minInput.value) || 0;
            const max = parseInt(maxInput.value) || 0;
            if (min > 0 && max > 0 && min < max) {
                avgInput.value = Math.round((min + max) / 2);
            }
        }

        minInput.addEventListener('input', calculate);
        maxInput.addEventListener('input', calculate);
    }

    updateAverage('create');
    updateAverage('edit');

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('.table-responsive'));

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});
</script>
@endpush
@endsection
