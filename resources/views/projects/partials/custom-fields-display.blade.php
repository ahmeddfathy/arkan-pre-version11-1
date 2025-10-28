{{-- Component لعرض الحقول الديناميكية في صفحة عرض المشروع --}}

@php
    $customFieldsData = $project->getCustomFieldsWithInfo();
@endphp

@if($customFieldsData->isNotEmpty())
    <div class="card custom-fields-display-card">
        <div class="card-header custom-fields-display-header">
            <h4 class="mb-0 text-white d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                معلومات إضافية
            </h4>
        </div>

        <div class="card-body custom-fields-display-body">
            @php
                $nonEmptyFields = $customFieldsData->filter(fn($item) => $item['value'] !== null && $item['value'] !== '');
            @endphp

            @if($nonEmptyFields->isNotEmpty())
                <div class="row g-4">
                    @foreach($nonEmptyFields as $item)
                        @php
                            $field = $item['field'];
                            $value = $item['value'];
                        @endphp

                        <div class="col-md-6 col-lg-4">
                            <div class="custom-field-display-item">
                                <div class="custom-field-display-label">
                                    <i class="fas fa-tag me-2 text-primary"></i>
                                    {{ $field->name }}
                                </div>
                                <div class="custom-field-display-value">
                                    @switch($field->field_type)
                                        @case('date')
                                            <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                            {{ \Carbon\Carbon::parse($value)->format('Y-m-d') }}
                                            @break

                                        @case('number')
                                            <i class="fas fa-hashtag me-2 text-muted"></i>
                                            {{ number_format($value) }}
                                            @break

                                        @case('textarea')
                                            <i class="fas fa-align-left me-2 text-muted"></i>
                                            <div class="mt-2">{{ $value }}</div>
                                            @break

                                        @default
                                            <i class="fas fa-file-alt me-2 text-muted"></i>
                                            {{ $value }}
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="custom-fields-empty-state">
                    <div class="custom-fields-empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h5 class="custom-fields-empty-title">لا توجد معلومات إضافية</h5>
                    <p class="custom-fields-empty-text">لم يتم إضافة أي معلومات إضافية لهذا المشروع بعد</p>
                </div>
            @endif
        </div>
    </div>

    <style>
        .custom-fields-display-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .custom-fields-display-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            padding: 1.5rem 2rem;
        }

        .custom-fields-display-body {
            padding: 2rem;
        }

        .custom-field-display-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .custom-field-display-item::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .custom-field-display-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            border-color: #667eea;
        }

        .custom-field-display-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }

        .custom-field-display-value {
            font-size: 1rem;
            color: #212529;
            font-weight: 500;
            word-wrap: break-word;
            line-height: 1.6;
        }

        .custom-fields-empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .custom-fields-empty-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
            opacity: 0.6;
        }

        .custom-fields-empty-title {
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .custom-fields-empty-text {
            color: #adb5bd;
            font-size: 0.9rem;
            margin: 0;
        }

        @media (max-width: 768px) {
            .custom-fields-display-body {
                padding: 1rem;
            }

            .custom-field-display-item {
                padding: 1rem;
            }
        }
    </style>
@endif

