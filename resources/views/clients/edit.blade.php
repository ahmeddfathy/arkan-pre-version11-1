@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/clients.css') }}">
@endpush

@section('content')
<div class="clients-container">
    <div class="client-page-header">
        <div class="header-content">
            <img src="{{ asset('assets/images/arkan.png') }}" alt="Arkan Logo" class="arkan-logo">
            <div class="header-text">
                <h1>تعديل بيانات العميل</h1>
                <p>تعديل بيانات العميل: {{ $client->name }}</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('clients.show', $client) }}" class="btn-view">
                <i class="fas fa-eye"></i>
                عرض العميل
            </a>
            <a href="{{ route('clients.index') }}" class="btn-back">
                <i class="fas fa-arrow-right"></i>
                العودة للقائمة
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="error-content">
                <h4>يرجى تصحيح الأخطاء التالية:</h4>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="form-container">
        <form action="{{ route('clients.update', $client) }}" method="POST" class="client-form">
            @csrf
            @method('PUT')

            <div class="form-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-user"></i>
                        المعلومات الأساسية
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <label for="name" class="form-label required">
                            <i class="fas fa-user"></i>
                            اسم العميل
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-input {{ $errors->has('name') ? 'error' : '' }}"
                            value="{{ old('name', $client->name) }}"
                            placeholder="أدخل اسم العميل"
                            required
                        >
                        @error('name')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="company_name" class="form-label">
                            <i class="fas fa-building"></i>
                            اسم الشركة
                        </label>
                        <input
                            type="text"
                            id="company_name"
                            name="company_name"
                            class="form-input {{ $errors->has('company_name') ? 'error' : '' }}"
                            value="{{ old('company_name', $client->company_name) }}"
                            placeholder="أدخل اسم الشركة (اختياري)"
                        >
                        @error('company_name')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="source" class="form-label">
                            <i class="fas fa-map-marker-alt"></i>
                            مصدر العميل
                        </label>
                        <select
                            id="source"
                            name="source"
                            class="form-input {{ $errors->has('source') ? 'error' : '' }}"
                        >
                            <option value="">اختر مصدر العميل</option>
                            <option value="مكالمة هاتفية" {{ old('source', $client->source) == 'مكالمة هاتفية' ? 'selected' : '' }}>مكالمة هاتفية</option>
                            <option value="بريد إلكتروني" {{ old('source', $client->source) == 'بريد إلكتروني' ? 'selected' : '' }}>بريد إلكتروني</option>
                            <option value="موقع الشركة" {{ old('source', $client->source) == 'موقع الشركة' ? 'selected' : '' }}>موقع الشركة</option>
                            <option value="وسائل التواصل الاجتماعي" {{ old('source', $client->source) == 'وسائل التواصل الاجتماعي' ? 'selected' : '' }}>وسائل التواصل الاجتماعي</option>
                            <option value="إحالة من عميل آخر" {{ old('source', $client->source) == 'إحالة من عميل آخر' ? 'selected' : '' }}>إحالة من عميل آخر</option>
                            <option value="معارض ومؤتمرات" {{ old('source', $client->source) == 'معارض ومؤتمرات' ? 'selected' : '' }}>معارض ومؤتمرات</option>
                            <option value="آخر" {{ old('source', $client->source) == 'آخر' ? 'selected' : '' }}>آخر</option>
                        </select>
                        @error('source')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-heart"></i>
                        اهتمامات العميل
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <label for="interests" class="form-label">
                            <i class="fas fa-tags"></i>
                            اهتمامات العميل
                        </label>
                        <div class="dynamic-inputs" id="interests-container">
                            @if(old('interests'))
                                @foreach(old('interests') as $index => $interest)
                                    @if($interest)
                                        <div class="input-group">
                                            <input
                                                type="text"
                                                name="interests[]"
                                                class="form-input interest-input"
                                                placeholder="أدخل اهتمام العميل"
                                                value="{{ $interest }}"
                                            >
                                            <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="{{ $index === 0 ? 'display: none;' : '' }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            @elseif(is_array($client->interests) && count($client->interests) > 0)
                                @foreach($client->interests as $index => $interest)
                                    @if($interest)
                                        <div class="input-group">
                                            <input
                                                type="text"
                                                name="interests[]"
                                                class="form-input interest-input"
                                                placeholder="أدخل اهتمام العميل"
                                                value="{{ $interest }}"
                                            >
                                            <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="{{ $index === 0 ? 'display: none;' : '' }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <div class="input-group">
                                    <input
                                        type="text"
                                        name="interests[]"
                                        class="form-input interest-input"
                                        placeholder="أدخل اهتمام العميل (مثال: تطوير المواقع)"
                                    >
                                    <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <button type="button" class="btn-add-input" onclick="addInterestInput()">
                            <i class="fas fa-plus"></i>
                            إضافة اهتمام آخر
                        </button>
                        @error('interests')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                        @error('interests.*')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-address-book"></i>
                        بيانات الاتصال
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <label for="emails" class="form-label required">
                            <i class="fas fa-envelope"></i>
                            البريد الإلكتروني
                        </label>
                        <div class="dynamic-inputs" id="emails-container">
                            @if(old('emails'))
                                @foreach(old('emails') as $index => $email)
                                    <div class="input-group">
                                        <input
                                            type="email"
                                            name="emails[]"
                                            class="form-input email-input"
                                            placeholder="أدخل البريد الإلكتروني"
                                            value="{{ $email }}"
                                            required
                                        >
                                        <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="{{ $index === 0 ? 'display: none;' : '' }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @elseif(is_array($client->emails) && count($client->emails) > 0)
                                @foreach($client->emails as $index => $email)
                                    <div class="input-group">
                                        <input
                                            type="email"
                                            name="emails[]"
                                            class="form-input email-input"
                                            placeholder="أدخل البريد الإلكتروني"
                                            value="{{ $email }}"
                                            required
                                        >
                                        <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="{{ $index === 0 ? 'display: none;' : '' }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @else
                                <div class="input-group">
                                    <input
                                        type="email"
                                        name="emails[]"
                                        class="form-input email-input"
                                        placeholder="أدخل البريد الإلكتروني"
                                        required
                                    >
                                    <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <button type="button" class="btn-add-input" onclick="addEmailInput()">
                            <i class="fas fa-plus"></i>
                            إضافة إيميل آخر
                        </button>
                        @error('emails')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                        @error('emails.*')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phones" class="form-label required">
                            <i class="fas fa-phone"></i>
                            رقم الهاتف
                        </label>
                        <div class="dynamic-inputs" id="phones-container">
                            @if(old('phones'))
                                @foreach(old('phones') as $index => $phone)
                                    <div class="input-group">
                                        <input
                                            type="tel"
                                            name="phones[]"
                                            class="form-input phone-input"
                                            placeholder="أدخل رقم الهاتف"
                                            value="{{ $phone }}"
                                            required
                                        >
                                        <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="{{ $index === 0 ? 'display: none;' : '' }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @elseif(is_array($client->phones) && count($client->phones) > 0)
                                @foreach($client->phones as $index => $phone)
                                    <div class="input-group">
                                        <input
                                            type="tel"
                                            name="phones[]"
                                            class="form-input phone-input"
                                            placeholder="أدخل رقم الهاتف"
                                            value="{{ $phone }}"
                                            required
                                        >
                                        <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="{{ $index === 0 ? 'display: none;' : '' }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @else
                                <div class="input-group">
                                    <input
                                        type="tel"
                                        name="phones[]"
                                        class="form-input phone-input"
                                        placeholder="أدخل رقم الهاتف"
                                        required
                                    >
                                    <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <button type="button" class="btn-add-input" onclick="addPhoneInput()">
                            <i class="fas fa-plus"></i>
                            إضافة رقم آخر
                        </button>
                        @error('phones')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                        @error('phones.*')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i>
                    حفظ التغييرات
                </button>
                <a href="{{ route('clients.index') }}" class="btn-cancel">
                    <i class="fas fa-times"></i>
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update remove buttons visibility on page load
        updateRemoveButtons();

        // Form validation enhancements
        const form = document.querySelector('.client-form');

        // Form submission handler
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                // Scroll to first error
                const firstError = form.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });

        // Auto-hide alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        });

        // Initialize validation for existing inputs
        const existingInputs = document.querySelectorAll('.form-input');
        existingInputs.forEach(input => {
            addInputValidation(input);
        });
    });

    // Add new email input field
    function addEmailInput(value = '') {
        const container = document.getElementById('emails-container');
        const inputGroup = document.createElement('div');
        inputGroup.className = 'input-group';

        inputGroup.innerHTML = `
            <input
                type="email"
                name="emails[]"
                class="form-input email-input"
                placeholder="أدخل البريد الإلكتروني"
                value="${value}"
                required
            >
            <button type="button" class="btn-remove-input" onclick="removeInput(this)">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(inputGroup);
        updateRemoveButtons();

        // Add real-time validation
        const newInput = inputGroup.querySelector('input');
        addInputValidation(newInput);

        // Focus on new input if no value provided
        if (!value) {
            newInput.focus();
        }
    }

    // Add new phone input field
    function addPhoneInput(value = '') {
        const container = document.getElementById('phones-container');
        const inputGroup = document.createElement('div');
        inputGroup.className = 'input-group';

        inputGroup.innerHTML = `
            <input
                type="tel"
                name="phones[]"
                class="form-input phone-input"
                placeholder="أدخل رقم الهاتف"
                value="${value}"
                required
            >
            <button type="button" class="btn-remove-input" onclick="removeInput(this)">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(inputGroup);
        updateRemoveButtons();

        // Add real-time validation
        const newInput = inputGroup.querySelector('input');
        addInputValidation(newInput);

        // Focus on new input if no value provided
        if (!value) {
            newInput.focus();
        }
    }

    // Add new interest input field
    function addInterestInput(value = '') {
        const container = document.getElementById('interests-container');
        const inputGroup = document.createElement('div');
        inputGroup.className = 'input-group';

        inputGroup.innerHTML = `
            <input
                type="text"
                name="interests[]"
                class="form-input interest-input"
                placeholder="أدخل اهتمام العميل"
                value="${value}"
            >
            <button type="button" class="btn-remove-input" onclick="removeInput(this)">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(inputGroup);
        updateRemoveButtons();

        // Add real-time validation
        const newInput = inputGroup.querySelector('input');
        addInputValidation(newInput);

        // Focus on new input if no value provided
        if (!value) {
            newInput.focus();
        }
    }

    // Remove input field
    function removeInput(button) {
        const inputGroup = button.parentNode;
        const container = inputGroup.parentNode;

        if (container.children.length > 1) {
            inputGroup.remove();
            updateRemoveButtons();
        }
    }

    // Update visibility of remove buttons
    function updateRemoveButtons() {
        const emailContainer = document.getElementById('emails-container');
        const phoneContainer = document.getElementById('phones-container');
        const interestsContainer = document.getElementById('interests-container');

        // Update email remove buttons
        const emailGroups = emailContainer.querySelectorAll('.input-group');
        emailGroups.forEach((group, index) => {
            const removeBtn = group.querySelector('.btn-remove-input');
            removeBtn.style.display = emailGroups.length > 1 ? 'flex' : 'none';
        });

        // Update phone remove buttons
        const phoneGroups = phoneContainer.querySelectorAll('.input-group');
        phoneGroups.forEach((group, index) => {
            const removeBtn = group.querySelector('.btn-remove-input');
            removeBtn.style.display = phoneGroups.length > 1 ? 'flex' : 'none';
        });

        // Update interests remove buttons
        if (interestsContainer) {
            const interestGroups = interestsContainer.querySelectorAll('.input-group');
            interestGroups.forEach((group, index) => {
                const removeBtn = group.querySelector('.btn-remove-input');
                removeBtn.style.display = interestGroups.length > 1 ? 'flex' : 'none';
            });
        }
    }

    // Add validation to input field
    function addInputValidation(input) {
        input.addEventListener('blur', function() {
            validateField(this);
        });

        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    }

    // Validate individual field
    function validateField(field) {
        const value = field.value.trim();
        const fieldType = field.type;

        // Remove existing error state
        field.classList.remove('error');
        const existingError = field.parentNode.querySelector('.error-message');
        if (existingError && !existingError.parentElement.classList.contains('form-group')) {
            existingError.remove();
        }

        // Validate based on field type
        let isValid = true;
        let errorMessage = '';

        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'هذا الحقل مطلوب';
        } else if (fieldType === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'يرجى إدخال عنوان بريد إلكتروني صحيح';
            }
        } else if (fieldType === 'tel' && value) {
            const phoneRegex = /^[0-9+\-\s()]+$/;
            if (!phoneRegex.test(value) || value.length < 10) {
                isValid = false;
                errorMessage = 'يرجى إدخال رقم هاتف صحيح';
            }
        }

        if (!isValid) {
            field.classList.add('error');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.textContent = errorMessage;
            field.parentNode.appendChild(errorSpan);
        }

        return isValid;
    }

    // Validate entire form
    function validateForm() {
        const form = document.querySelector('.client-form');
        const inputs = form.querySelectorAll('.form-input');
        let isFormValid = true;

        inputs.forEach(input => {
            if (!validateField(input)) {
                isFormValid = false;
            }
        });

        return isFormValid;
    }
</script>
@endpush
