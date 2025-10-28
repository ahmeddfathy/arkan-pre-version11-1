@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/my-tasks.css') }}">
@endpush
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">قوالب المهام</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                        <i class="fas fa-plus"></i> إضافة قالب جديد
                    </button>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="serviceFilter">تصفية حسب الخدمة</label>
                                <select class="form-control" id="serviceFilter">
                                    <option value="">جميع الخدمات</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="searchInput">بحث</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="ابحث عن اسم القالب...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-items-center mb-0" id="templatesTable">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الاسم</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الخدمة</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الوقت المقدر</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الترتيب</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الحالة</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $template)
                                <tr data-service-id="{{ $template->service_id }}">
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $template->name }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ Str::limit($template->description, 50) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $template->service->name ?? 'غير محدد' }}</td>
                                    <td>{{ $template->estimated_hours }}:{{ str_pad($template->estimated_minutes, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $template->order }}</td>
                                    <td>
                                        <span class="badge badge-sm {{ $template->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $template->is_active ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('task-templates.show', $template) }}" class="btn btn-sm btn-primary" title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('task-templates.toggle-status', $template) }}" class="btn btn-sm {{ $template->is_active ? 'btn-warning' : 'btn-success' }}" title="{{ $template->is_active ? 'تعطيل' : 'تفعيل' }}">
                                            <i class="fas {{ $template->is_active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                        </a>
                                        <button class="btn btn-sm btn-info edit-template"
                                                data-id="{{ $template->id }}"
                                                data-name="{{ $template->name }}"
                                                data-description="{{ $template->description }}"
                                                data-service="{{ $template->service_id }}"
                                                data-hours="{{ $template->estimated_hours }}"
                                                data-minutes="{{ $template->estimated_minutes }}"
                                                data-order="{{ $template->order }}"
                                                data-active="{{ $template->is_active ? '1' : '0' }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-template" data-id="{{ $template->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">لا توجد قوالب مهام</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $templates->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Template Modal -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-labelledby="createTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTemplateModalLabel">إضافة قالب مهمة جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('task-templates.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">اسم القالب</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="service_id">الخدمة</label>
                                <select class="form-control" id="service_id" name="service_id" required>
                                    <option value="">اختر الخدمة</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label for="description">وصف المهمة</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="estimated_hours">الوقت المقدر (ساعات)</label>
                                <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="estimated_minutes">الوقت المقدر (دقائق)</label>
                                <input type="number" class="form-control" id="estimated_minutes" name="estimated_minutes" min="0" max="59" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="order">الترتيب</label>
                                <input type="number" class="form-control" id="order" name="order" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">نشط</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTemplateModalLabel">تعديل قالب المهمة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTemplateForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_name">اسم القالب</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_service_id">الخدمة</label>
                                <select class="form-control" id="edit_service_id" name="service_id" required>
                                    <option value="">اختر الخدمة</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label for="edit_description">وصف المهمة</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_estimated_hours">الوقت المقدر (ساعات)</label>
                                <input type="number" class="form-control" id="edit_estimated_hours" name="estimated_hours" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_estimated_minutes">الوقت المقدر (دقائق)</label>
                                <input type="number" class="form-control" id="edit_estimated_minutes" name="estimated_minutes" min="0" max="59">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_order">الترتيب</label>
                                <input type="number" class="form-control" id="edit_order" name="order" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label" for="edit_is_active">نشط</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-labelledby="deleteTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTemplateModalLabel">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من رغبتك في حذف هذا القالب؟
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form id="deleteTemplateForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">حذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Filter by service
        $('#serviceFilter').change(function() {
            const serviceId = $(this).val();
            if (serviceId) {
                $('#templatesTable tbody tr').hide();
                $('#templatesTable tbody tr[data-service-id="' + serviceId + '"]').show();
            } else {
                $('#templatesTable tbody tr').show();
            }
        });

        // Search functionality
        $('#searchInput').keyup(function() {
            const searchText = $(this).val().toLowerCase();
            $('#templatesTable tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(searchText) > -1);
            });
        });

        // Edit template
        $('.edit-template').click(function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const description = $(this).data('description');
            const serviceId = $(this).data('service');
            const hours = $(this).data('hours');
            const minutes = $(this).data('minutes');
            const order = $(this).data('order');
            const active = $(this).data('active');

            console.log('Active value:', active, typeof active);

            $('#edit_name').val(name);
            $('#edit_description').val(description);
            $('#edit_service_id').val(serviceId);
            $('#edit_estimated_hours').val(hours);
            $('#edit_estimated_minutes').val(minutes);
            $('#edit_order').val(order);

            // تعيين حالة is_active بطريقة مباشرة وضمان تحويلها لقيمة boolean
            const isActive = (active === true || active === 1 || active === '1' || active === 'true');
            console.log('Setting checkbox to:', isActive);
            $('#edit_is_active').prop('checked', isActive);

            $('#editTemplateForm').attr('action', '/task-templates/' + id);
            $('#editTemplateModal').modal('show');
        });

        // Delete template
        $('.delete-template').click(function() {
            const id = $(this).data('id');
            $('#deleteTemplateForm').attr('action', '/task-templates/' + id);
            $('#deleteTemplateModal').modal('show');
        });
    });
</script>
@endpush
@endsection
