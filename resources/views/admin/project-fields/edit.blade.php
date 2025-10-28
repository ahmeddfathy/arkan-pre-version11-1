<x-app-layout>
    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/project-fields.css') }}">
    @endpush

    <x-slot name="header">
        <h2 class="h4 mb-0">تعديل الحقل: {{ $projectField->name }}</h2>
    </x-slot>

    <div class="container-fluid py-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('project-fields.update', $projectField->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- اسم الحقل -->
                    <div class="mb-3">
                        <label for="name" class="form-label">اسم الحقل *</label>
                        <input type="text" name="name" id="name" required class="form-control" value="{{ old('name', $projectField->name) }}">
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- مفتاح الحقل -->
                    <div class="mb-3">
                        <label for="field_key" class="form-label">مفتاح الحقل *</label>
                        <input type="text" name="field_key" id="field_key" required class="form-control" value="{{ old('field_key', $projectField->field_key) }}">
                        <div class="form-text">يستخدم في الكود. تأكد من عدم تغييره إذا كان مستخدماً في المشاريع الحالية</div>
                        @error('field_key')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- نوع الحقل -->
                    <div class="mb-3">
                        <label for="field_type" class="form-label">نوع الحقل *</label>
                        <select name="field_type" id="field_type" required class="form-select" onchange="toggleOptionsField()">
                            <option value="text" {{ old('field_type', $projectField->field_type) == 'text' ? 'selected' : '' }}>نص</option>
                            <option value="select" {{ old('field_type', $projectField->field_type) == 'select' ? 'selected' : '' }}>قائمة منسدلة</option>
                            <option value="textarea" {{ old('field_type', $projectField->field_type) == 'textarea' ? 'selected' : '' }}>نص طويل</option>
                            <option value="number" {{ old('field_type', $projectField->field_type) == 'number' ? 'selected' : '' }}>رقم</option>
                            <option value="date" {{ old('field_type', $projectField->field_type) == 'date' ? 'selected' : '' }}>تاريخ</option>
                        </select>
                        @error('field_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- خيارات الحقل (للقوائم المنسدلة) -->
                    <div id="options-container" class="mb-3" style="display: {{ old('field_type', $projectField->field_type) == 'select' ? 'block' : 'none' }}">
                        <label class="form-label">خيارات القائمة</label>
                        <div id="options-list">
                            @php
                                $options = old('field_options', $projectField->field_options ?? []);
                            @endphp
                            @if(!empty($options))
                                @foreach($options as $index => $option)
                                    <div class="input-group mb-2">
                                        <input type="text" name="field_options[]" value="{{ $option }}" class="form-control" placeholder="خيار {{ $index + 1 }}">
                                        <button type="button" onclick="removeOption(this)" class="btn btn-danger">حذف</button>
                                    </div>
                                @endforeach
                            @else
                                <div class="input-group mb-2">
                                    <input type="text" name="field_options[]" class="form-control" placeholder="خيار 1">
                                    <button type="button" onclick="removeOption(this)" class="btn btn-danger">حذف</button>
                                </div>
                            @endif
                        </div>
                        <button type="button" onclick="addOption()" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> إضافة خيار
                        </button>
                    </div>

                    <!-- الوصف -->
                    <div class="mb-3">
                        <label for="description" class="form-label">الوصف (اختياري)</label>
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $projectField->description) }}</textarea>
                        @error('description')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- الترتيب -->
                    <div class="mb-3">
                        <label for="order" class="form-label">الترتيب</label>
                        <input type="number" name="order" id="order" class="form-control" value="{{ old('order', $projectField->order) }}">
                        @error('order')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- الخيارات -->
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="is_required" id="is_required" value="1" class="form-check-input" {{ old('is_required', $projectField->is_required) ? 'checked' : '' }}>
                            <label for="is_required" class="form-check-label">حقل إلزامي</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" {{ old('is_active', $projectField->is_active) ? 'checked' : '' }}>
                            <label for="is_active" class="form-check-label">نشط</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                        <a href="{{ route('project-fields.index') }}" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleOptionsField() {
            const fieldType = document.getElementById('field_type').value;
            const optionsContainer = document.getElementById('options-container');
            optionsContainer.style.display = fieldType === 'select' ? 'block' : 'none';
        }

        function addOption() {
            const optionsList = document.getElementById('options-list');
            const optionCount = optionsList.children.length + 1;
            const newOption = `
                <div class="input-group mb-2">
                    <input type="text" name="field_options[]" class="form-control" placeholder="خيار ${optionCount}">
                    <button type="button" onclick="removeOption(this)" class="btn btn-danger">حذف</button>
                </div>
            `;
            optionsList.insertAdjacentHTML('beforeend', newOption);
        }

        function removeOption(button) {
            const optionRow = button.closest('.input-group');
            const optionsList = document.getElementById('options-list');
            if (optionsList.children.length > 1) {
                optionRow.remove();
            } else {
                alert('يجب أن يكون هناك خيار واحد على الأقل');
            }
        }
    </script>
    @endpush
</x-app-layout>

