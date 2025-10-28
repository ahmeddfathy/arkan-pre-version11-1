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
                <h1>إضافة عميل جديد</h1>
                <p>إضافة عميل جديد إلى قاعدة البيانات</p>
            </div>
        </div>
        <a href="{{ route('clients.index') }}" class="btn-back">
            <i class="fas fa-arrow-right"></i>
            العودة للقائمة
        </a>
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
        <form action="{{ route('clients.store') }}" method="POST" class="client-form">
            @csrf

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
                            value="{{ old('name') }}"
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
                            value="{{ old('company_name') }}"
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
                            <option value="مكالمة هاتفية" {{ old('source') == 'مكالمة هاتفية' ? 'selected' : '' }}>مكالمة هاتفية</option>
                            <option value="بريد إلكتروني" {{ old('source') == 'بريد إلكتروني' ? 'selected' : '' }}>بريد إلكتروني</option>
                            <option value="موقع الشركة" {{ old('source') == 'موقع الشركة' ? 'selected' : '' }}>موقع الشركة</option>
                            <option value="وسائل التواصل الاجتماعي" {{ old('source') == 'وسائل التواصل الاجتماعي' ? 'selected' : '' }}>وسائل التواصل الاجتماعي</option>
                            <option value="إحالة من عميل آخر" {{ old('source') == 'إحالة من عميل آخر' ? 'selected' : '' }}>إحالة من عميل آخر</option>
                            <option value="معارض ومؤتمرات" {{ old('source') == 'معارض ومؤتمرات' ? 'selected' : '' }}>معارض ومؤتمرات</option>
                            <option value="آخر" {{ old('source') == 'آخر' ? 'selected' : '' }}>آخر</option>
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
                            <div class="input-group">
                                <input
                                    type="email"
                                    name="emails[]"
                                    class="form-input email-input"
                                    placeholder="أدخل البريد الإلكتروني"
                                    value="{{ old('emails.0') }}"
                                    required
                                >
                                <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
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
                            <div class="input-group">
                                <input
                                    type="tel"
                                    name="phones[]"
                                    class="form-input phone-input"
                                    placeholder="أدخل رقم الهاتف"
                                    value="{{ old('phones.0') }}"
                                    required
                                >
                                <button type="button" class="btn-remove-input" onclick="removeInput(this)" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
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
                    حفظ العميل
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
        // Initialize form with existing data if any
        initializeFormWithOldData();

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

    // Initialize form with old data (for validation errors)
    function initializeFormWithOldData() {
        // Get old email values from hidden inputs if they exist
        const oldEmailInputs = document.querySelectorAll('input[name="old_emails[]"]');
        if (oldEmailInputs.length > 0) {
            oldEmailInputs.forEach((hiddenInput, index) => {
                if (index > 0) { // Skip first one as it's already in form
                    addEmailInput(hiddenInput.value);
                }
            });
        }

        // Get old phone values from hidden inputs if they exist
        const oldPhoneInputs = document.querySelectorAll('input[name="old_phones[]"]');
        if (oldPhoneInputs.length > 0) {
            oldPhoneInputs.forEach((hiddenInput, index) => {
                if (index > 0) { // Skip first one as it's already in form
                    addPhoneInput(hiddenInput.value);
                }
            });
        }
    }

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

{{-- Hidden inputs for old data --}}
@if(old('emails'))
    @foreach(old('emails') as $index => $email)
        @if($index > 0)
            <input type="hidden" name="old_emails[]" value="{{ $email }}">
        @endif
    @endforeach
@endif

@if(old('phones'))
    @foreach(old('phones') as $index => $phone)
        @if($index > 0)
            <input type="hidden" name="old_phones[]" value="{{ $phone }}">
        @endif
    @endforeach
@endif
@endpush
