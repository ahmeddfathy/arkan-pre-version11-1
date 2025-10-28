<x-app-layout>
    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/project-fields.css') }}">
    @endpush

    <x-slot name="header">
        <h2 class="h4 mb-0">إدارة حقول المشاريع الإضافية</h2>
    </x-slot>

    <div class="container-fluid py-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">قائمة الحقول</h5>
                    <a href="{{ route('project-fields.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        إضافة حقل جديد
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($fields->isEmpty())
                    <div class="text-center py-5">
                        <svg class="mb-3" width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="currentColor" opacity="0.3">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-muted">لا توجد حقول مضافة حتى الآن</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>الترتيب</th>
                                    <th>اسم الحقل</th>
                                    <th>المفتاح</th>
                                    <th>النوع</th>
                                    <th>إلزامي؟</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="fields-tbody">
                                @foreach($fields as $field)
                                    <tr data-field-id="{{ $field->id }}">
                                        <td>{{ $field->order }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $field->name }}</div>
                                            @if($field->description)
                                                <small class="text-muted">{{ $field->description }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $field->field_key }}</code>
                                        </td>
                                        <td>
                                            @php
                                                $types = [
                                                    'text' => 'نص',
                                                    'select' => 'قائمة منسدلة',
                                                    'textarea' => 'نص طويل',
                                                    'number' => 'رقم',
                                                    'date' => 'تاريخ'
                                                ];
                                            @endphp
                                            {{ $types[$field->field_type] ?? $field->field_type }}
                                        </td>
                                        <td>
                                            @if($field->is_required)
                                                <span class="badge bg-danger">إلزامي</span>
                                            @else
                                                <span class="badge bg-secondary">اختياري</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button onclick="toggleActive({{ $field->id }})" class="btn btn-sm p-0 border-0">
                                                @if($field->is_active)
                                                    <span class="badge bg-success">نشط</span>
                                                @else
                                                    <span class="badge bg-secondary">معطل</span>
                                                @endif
                                            </button>
                                        </td>
                                        <td>
                                            <a href="{{ route('project-fields.edit', $field->id) }}" class="btn btn-sm btn-outline-primary me-1">
                                                تعديل
                                            </a>
                                            <form action="{{ route('project-fields.destroy', $field->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الحقل؟')">
                                                    حذف
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleActive(fieldId) {
            fetch(`/project-fields/${fieldId}/toggle-active`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
    @endpush
</x-app-layout>

