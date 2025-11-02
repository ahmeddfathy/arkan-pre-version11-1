@extends('layouts.app')

@section('title', 'إدارة أنواع المهام الجرافيكية')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/graphic-task-types.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div>
                    <h1>🎨 إدارة أنواع المهام الجرافيكية</h1>
                    <p>إدارة وتنظيم أنواع المهام الجرافيكية المختلفة</p>
                </div>
                <button type="button"
                    class="services-btn"
                    style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; height: fit-content;"
                    data-bs-toggle="modal"
                    data-bs-target="#createModal">
                    <i class="fas fa-plus"></i>
                    إضافة نوع جديد
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; margin-bottom: 20px;">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2> قائمة أنواع المهام الجرافيكية</h2>
            </div>

            <table class="projects-table">
                <thead>
                    <tr>
                        <th>اسم النوع</th>
                        <th>الوصف</th>
                        <th>النقاط</th>
                        <th>نطاق الوقت</th>
                        <th>الوقت المتوسط</th>
                        <th>القسم</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($graphicTaskTypes as $type)
                    <tr class="project-row">
                        <td>
                            <div class="project-info">
                                <div class="project-avatar">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <div class="project-details">
                                    <h4>{{ $type->name }}</h4>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="client-info">
                                {{ Str::limit($type->description, 50) ?? 'لا يوجد وصف' }}
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center;">
                                <span style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 6px 12px; border-radius: 15px; font-weight: 700; font-size: 0.85rem; display: inline-block;">
                                    {{ $type->points }} نقطة
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center; color: #6b7280; font-size: 0.9rem;">
                                {{ $type->min_minutes }} - {{ $type->max_minutes }} دقيقة
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center; font-weight: 600; color: #374151;">
                                {{ $type->average_time_formatted }}
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center;">
                                <span style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white; padding: 6px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85rem; display: inline-block;">
                                    {{ $type->department }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center;">
                                <button class="toggle-status-btn"
                                    data-id="{{ $type->id }}"
                                    style="border: none; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; {{ $type->is_active ? 'background: linear-gradient(135deg, #10b981, #059669); color: white;' : 'background: linear-gradient(135deg, #ef4444, #dc2626); color: white;' }}"
                                    title="{{ $type->is_active ? 'نشط' : 'غير نشط' }}">
                                    <i class="fas {{ $type->is_active ? 'fa-check' : 'fa-times' }}"></i>
                                    {{ $type->is_active ? 'نشط' : 'غير نشط' }}
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <button class="services-btn show-btn"
                                    data-id="{{ $type->id }}"
                                    style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;"
                                    title="عرض">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </button>
                                <button class="services-btn edit-btn"
                                    data-id="{{ $type->id }}"
                                    style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;"
                                    title="تعديل">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </button>
                                <button class="services-btn delete-btn"
                                    data-id="{{ $type->id }}"
                                    data-name="{{ $type->name }}"
                                    style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;"
                                    title="حذف">
                                    <i class="fas fa-trash"></i>
                                    حذف
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>لا توجد أنواع مهام جرافيكية</h4>
                            <p>لم يتم إضافة أي أنواع مهام جرافيكية بعد</p>
                            <button type="button"
                                class="services-btn"
                                style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; padding: 0.5rem 1rem; font-size: 0.85rem;"
                                data-bs-toggle="modal"
                                data-bs-target="#createModal">
                                <i class="fas fa-plus"></i>
                                إضافة النوع الأول
                            </button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($graphicTaskTypes->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $graphicTaskTypes->links() }}
            </div>
            @endif
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle delete button clicks
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;

                Swal.fire({
                    title: 'هل أنت متأكد؟',
                    html: `هل تريد حذف نوع المهمة: <strong>${name}</strong>؟<br><span style="color: #f59e0b;"><i class="fas fa-exclamation-triangle me-1"></i>لا يمكن التراجع عن هذا الإجراء</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'نعم، احذف',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'جاري الحذف...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/graphic-task-types/${id}`;

                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;

                        const methodField = document.createElement('input');
                        methodField.type = 'hidden';
                        methodField.name = '_method';
                        methodField.value = 'DELETE';

                        form.appendChild(csrfToken);
                        form.appendChild(methodField);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });

        // Handle edit button clicks
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;

                Swal.fire({
                    title: 'جاري التحميل...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/graphic-task-types/${id}`)
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
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
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء تحميل البيانات'
                        });
                    });
            });
        });

        // Handle show button clicks
        document.querySelectorAll('.show-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;

                Swal.fire({
                    title: 'جاري التحميل...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/graphic-task-types/${id}`)
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
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
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء تحميل البيانات'
                        });
                    });
            });
        });

        // Handle status toggle
        document.querySelectorAll('.toggle-status-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const currentBtn = this;
                const isActive = currentBtn.textContent.includes('نشط');

                Swal.fire({
                    title: 'جاري التحديث...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

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
                                currentBtn.style.cssText = 'border: none; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #10b981, #059669); color: white;';
                                currentBtn.innerHTML = '<i class="fas fa-check"></i> نشط';
                                currentBtn.title = 'نشط';
                            } else {
                                currentBtn.style.cssText = 'border: none; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #ef4444, #dc2626); color: white;';
                                currentBtn.innerHTML = '<i class="fas fa-times"></i> غير نشط';
                                currentBtn.title = 'غير نشط';
                            }

                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء تحديث الحالة'
                        });
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
    });
</script>
@endpush
@endsection
