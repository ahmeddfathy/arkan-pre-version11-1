@extends('admin.notifications.layouts.notification-layout')

@section('page-title', 'إنشاء إشعار جديد')

@section('notification-content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">إنشاء إشعار جديد</div>

            <div class="card-body">
                <form method="POST" action="{{ route('admin.notifications.store') }}">
                    @csrf

                    <div class="form-group mb-3">
                        <label for="title" class="form-label fw-bold">عنوان الإشعار</label>
                        <input type="text"
                            class="form-control @error('title') is-invalid @enderror"
                            id="title"
                            name="title"
                            value="{{ old('title') }}"
                            required>
                        @error('title')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="message" class="form-label fw-bold">نص الإشعار</label>
                        <textarea class="form-control @error('message') is-invalid @enderror"
                            id="message"
                            name="message"
                            rows="4"
                            required>{{ old('message') }}</textarea>
                        @error('message')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="is_administrative" class="form-label fw-bold">نوع الإشعار</label>
                        <select class="form-select @error('is_administrative') is-invalid @enderror"
                            id="is_administrative"
                            name="is_administrative"
                            onchange="handleAdministrativeChange(this.value)"
                            required>
                            <option value="0">إشعار عادي</option>
                            <option value="1">قرار إداري</option>
                        </select>
                        @error('is_administrative')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="requires_acknowledgment" class="form-label fw-bold">يتطلب تأكيد القراءة</label>
                        <select class="form-select @error('requires_acknowledgment') is-invalid @enderror"
                            id="requires_acknowledgment"
                            name="requires_acknowledgment"
                            required>
                            <option value="0">لا</option>
                            <option value="1">نعم</option>
                        </select>
                        <input type="hidden" id="requires_acknowledgment_hidden" name="requires_acknowledgment">
                        @error('requires_acknowledgment')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group d-flex justify-content-end mt-4">
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-filter secondary me-2">
                            إلغاء
                        </a>
                        <button type="submit" class="btn btn-filter primary">
                            إرسال الإشعار
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('page-scripts')
<script>
    function handleAdministrativeChange(value) {
        const requiresAckSelect = document.getElementById('requires_acknowledgment');
        const requiresAckHidden = document.getElementById('requires_acknowledgment_hidden');

        if (value === '1') { // إذا كان قرار إداري
            requiresAckSelect.value = '1'; // اختيار "نعم"
            requiresAckHidden.value = '1'; // تعيين قيمة الحقل المخفي
            requiresAckSelect.disabled = true; // تعطيل التغيير
        } else {
            requiresAckSelect.disabled = false; // تفعيل التغيير
            requiresAckHidden.value = requiresAckSelect.value; // نسخ القيمة للحقل المخفي
        }
    }

    // تحديث الحقل المخفي عند تغيير القيمة في القائمة المنسدلة
    document.getElementById('requires_acknowledgment').addEventListener('change', function() {
        document.getElementById('requires_acknowledgment_hidden').value = this.value;
    });

    // تنفيذ الدالة عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        handleAdministrativeChange(document.getElementById('is_administrative').value);
    });
</script>
@endpush