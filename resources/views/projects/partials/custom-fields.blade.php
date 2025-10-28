{{-- Component لعرض الحقول الديناميكية في صفحة إنشاء/تعديل المشاريع --}}

@php
    $customFields = \App\Models\ProjectField::active()->ordered()->get();
    $projectCustomFields = isset($project) ? ($project->custom_fields ?? []) : [];
@endphp

@if($customFields->isNotEmpty())
    <div class="card custom-fields-form-card">
        <div class="card-header custom-fields-form-header">
            <h4 class="mb-0 text-white d-flex align-items-center">
                <i class="fas fa-clipboard-list me-2"></i>
                معلومات إضافية
            </h4>
            <small class="text-white-50 d-block mt-1">
                <i class="fas fa-info-circle me-1"></i>
                أضف المعلومات الإضافية للمشروع
            </small>
        </div>

        <div class="card-body custom-fields-form-body">
            <div class="row g-4">
                @foreach($customFields as $field)
                    <div class="col-md-6">
                        <div class="custom-field-form-group">
                            <label for="custom_field_{{ $field->field_key }}" class="custom-field-form-label">
                                @switch($field->field_type)
                                    @case('text')
                                        <i class="fas fa-text-width me-2 text-primary"></i>
                                        @break
                                    @case('number')
                                        <i class="fas fa-hashtag me-2 text-success"></i>
                                        @break
                                    @case('date')
                                        <i class="fas fa-calendar-alt me-2 text-info"></i>
                                        @break
                                    @case('textarea')
                                        <i class="fas fa-align-left me-2 text-warning"></i>
                                        @break
                                    @case('select')
                                        <i class="fas fa-list-ul me-2 text-danger"></i>
                                        @break
                                @endswitch
                                {{ $field->name }}
                                @if($field->is_required)
                                    <span class="required-indicator">*</span>
                                @endif
                            </label>

                            @if($field->description)
                                <p class="custom-field-form-description">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    {{ $field->description }}
                                </p>
                            @endif

                            @switch($field->field_type)
                                @case('text')
                                    <div class="input-with-icon">
                                        <i class="fas fa-pen input-icon"></i>
                                        <input type="text"
                                               name="custom_fields[{{ $field->field_key }}]"
                                               id="custom_field_{{ $field->field_key }}"
                                               value="{{ old('custom_fields.' . $field->field_key, $projectCustomFields[$field->field_key] ?? '') }}"
                                               placeholder="أدخل {{ strtolower($field->name) }}"
                                               {{ $field->is_required ? 'required' : '' }}
                                               class="custom-field-form-input">
                                    </div>
                                    @break

                                @case('number')
                                    <div class="input-with-icon">
                                        <i class="fas fa-calculator input-icon"></i>
                                        <input type="number"
                                               name="custom_fields[{{ $field->field_key }}]"
                                               id="custom_field_{{ $field->field_key }}"
                                               value="{{ old('custom_fields.' . $field->field_key, $projectCustomFields[$field->field_key] ?? '') }}"
                                               placeholder="أدخل رقم"
                                               {{ $field->is_required ? 'required' : '' }}
                                               class="custom-field-form-input">
                                    </div>
                                    @break

                                @case('date')
                                    <div class="input-with-icon">
                                        <i class="fas fa-calendar input-icon"></i>
                                        <input type="date"
                                               name="custom_fields[{{ $field->field_key }}]"
                                               id="custom_field_{{ $field->field_key }}"
                                               value="{{ old('custom_fields.' . $field->field_key, $projectCustomFields[$field->field_key] ?? '') }}"
                                               {{ $field->is_required ? 'required' : '' }}
                                               class="custom-field-form-input">
                                    </div>
                                    @break

                                @case('textarea')
                                    <div class="textarea-with-icon">
                                        <i class="fas fa-edit textarea-icon"></i>
                                        <textarea name="custom_fields[{{ $field->field_key }}]"
                                                  id="custom_field_{{ $field->field_key }}"
                                                  rows="4"
                                                  placeholder="أدخل {{ strtolower($field->name) }}"
                                                  {{ $field->is_required ? 'required' : '' }}
                                                  class="custom-field-form-textarea">{{ old('custom_fields.' . $field->field_key, $projectCustomFields[$field->field_key] ?? '') }}</textarea>
                                    </div>
                                    @break

                                @case('select')
                                    <div class="select-with-icon">
                                        <i class="fas fa-caret-down select-icon"></i>
                                        <select name="custom_fields[{{ $field->field_key }}]"
                                                id="custom_field_{{ $field->field_key }}"
                                                {{ $field->is_required ? 'required' : '' }}
                                                class="custom-field-form-select">
                                            <option value="">اختر {{ strtolower($field->name) }}...</option>
                                            @if($field->field_options)
                                                @foreach($field->field_options as $option)
                                                    <option value="{{ $option }}"
                                                            {{ old('custom_fields.' . $field->field_key, $projectCustomFields[$field->field_key] ?? '') == $option ? 'selected' : '' }}>
                                                        {{ $option }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    @break
                            @endswitch

                            @error('custom_fields.' . $field->field_key)
                                <div class="custom-field-form-error">
                                    <i class="fas fa-exclamation-circle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .custom-fields-form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .custom-fields-form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            padding: 1.5rem 2rem;
        }

        .custom-fields-form-body {
            padding: 2rem;
        }

        .custom-field-form-group {
            margin-bottom: 0;
        }

        .custom-field-form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }

        .required-indicator {
            color: #dc3545;
            margin-right: 0.25rem;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .custom-field-form-description {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.75rem;
            font-style: italic;
        }

        .input-with-icon,
        .textarea-with-icon,
        .select-with-icon {
            position: relative;
        }

        .input-icon,
        .textarea-icon,
        .select-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 14px;
            z-index: 1;
            pointer-events: none;
        }

        .textarea-icon {
            top: 20px;
            transform: none;
        }

        .custom-field-form-input,
        .custom-field-form-textarea,
        .custom-field-form-select {
            width: 100%;
            padding: 0.75rem 2.75rem 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .custom-field-form-textarea {
            padding-right: 3rem;
            resize: vertical;
            min-height: 100px;
        }

        .custom-field-form-input:focus,
        .custom-field-form-textarea:focus,
        .custom-field-form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            outline: none;
        }

        .custom-field-form-input:hover,
        .custom-field-form-textarea:hover,
        .custom-field-form-select:hover {
            border-color: #667eea;
        }

        .custom-field-form-select {
            cursor: pointer;
            appearance: none;
            background-image: none;
        }

        .custom-field-form-error {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #dc3545;
            background: #fff5f5;
            border: 1px solid #ffe0e0;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .custom-fields-form-body {
                padding: 1rem;
            }

            .col-md-6 {
                width: 100%;
            }
        }
    </style>
@endif

