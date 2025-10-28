@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/call-logs.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="call-logs-container">
    <div class="container">
        <!-- Page Header -->
        <div class="arkan-phone-header">
            <div class="header-content">
                <div class="header-text">
                    <h1><i class="fas fa-edit phone-icon-animated"></i> تعديل سجل المكالمة</h1>
                    <p>تحديث وتعديل معلومات المكالمة مع العميل</p>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="call-log-card">
            <div style="padding: 2rem;">
                <form method="POST" action="{{ route('call-logs.update', $callLog) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <!-- Client Selection -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="client_id">العميل *</label>
                                <select name="client_id" id="client_id" class="form-control @error('client_id') is-invalid @enderror" required>
                                    <option value="">اختر العميل</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}"
                                            {{ (old('client_id', $callLog->client_id) == $client->id) ? 'selected' : '' }}>
                                            {{ $client->name }}
                                            @if($client->company_name)
                                                - {{ $client->company_name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Call Date -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="call_date">تاريخ ووقت المكالمة *</label>
                                <input type="datetime-local" name="call_date" id="call_date"
                                       class="form-control @error('call_date') is-invalid @enderror"
                                       value="{{ old('call_date', $callLog->call_date->format('Y-m-d\TH:i')) }}" required>
                                @error('call_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Contact Type -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_type">نوع التواصل *</label>
                                <select name="contact_type" id="contact_type" class="form-control @error('contact_type') is-invalid @enderror" required>
                                    <option value="">اختر نوع التواصل</option>
                                    <option value="call" {{ old('contact_type', $callLog->contact_type) == 'call' ? 'selected' : '' }}>مكالمة هاتفية</option>
                                    <option value="email" {{ old('contact_type', $callLog->contact_type) == 'email' ? 'selected' : '' }}>بريد إلكتروني</option>
                                    <option value="whatsapp" {{ old('contact_type', $callLog->contact_type) == 'whatsapp' ? 'selected' : '' }}>واتساب</option>
                                    <option value="meeting" {{ old('contact_type', $callLog->contact_type) == 'meeting' ? 'selected' : '' }}>اجتماع</option>
                                    <option value="other" {{ old('contact_type', $callLog->contact_type) == 'other' ? 'selected' : '' }}>آخر</option>
                                </select>
                                @error('contact_type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="duration_minutes">مدة المكالمة (بالدقائق)</label>
                                <input type="number" name="duration_minutes" id="duration_minutes"
                                       class="form-control @error('duration_minutes') is-invalid @enderror"
                                       value="{{ old('duration_minutes', $callLog->duration_minutes) }}" min="1" max="600" placeholder="مثال: 15">
                                @error('duration_minutes')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">اختياري - يمكن تركه فارغاً</small>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">حالة المكالمة *</label>
                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                    <option value="">اختر حالة المكالمة</option>
                                    <option value="successful" {{ old('status', $callLog->status) == 'successful' ? 'selected' : '' }}>تمت بنجاح</option>
                                    <option value="failed" {{ old('status', $callLog->status) == 'failed' ? 'selected' : '' }}>فشلت</option>
                                    <option value="needs_followup" {{ old('status', $callLog->status) == 'needs_followup' ? 'selected' : '' }}>تحتاج متابعة</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Outcome -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="outcome">نتيجة المكالمة</label>
                                <textarea name="outcome" id="outcome" rows="3"
                                          class="form-control @error('outcome') is-invalid @enderror"
                                          placeholder="اكتب نتيجة المكالمة... مثال: تم الاتفاق على موعد، يحتاج وقت للتفكير، غير مهتم، إلخ">{{ old('outcome', $callLog->outcome) }}</textarea>
                                @error('outcome')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">اختياري - اكتب النتيجة بحرية</small>
                            </div>
                        </div>

                        <!-- Call Summary -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="call_summary">ملخص المكالمة *</label>
                                <textarea name="call_summary" id="call_summary" rows="4"
                                          class="form-control @error('call_summary') is-invalid @enderror"
                                          placeholder="اكتب ملخصاً مفصلاً عن المكالمة، الموضوع المناقش، واستجابة العميل..." required>{{ old('call_summary', $callLog->call_summary) }}</textarea>
                                @error('call_summary')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="notes">ملاحظات إضافية</label>
                                <textarea name="notes" id="notes" rows="3"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="أي ملاحظات أخرى أو معلومات مهمة...">{{ old('notes', $callLog->notes) }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div style="padding: 2rem; background: var(--gray-50); border-radius: var(--radius-lg); margin-top: 1rem; display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> حفظ التغييرات
                        </button>
                        <a href="{{ route('call-logs.show', $callLog) }}" class="btn-view">
                            <i class="fas fa-eye"></i> عرض المكالمة
                        </a>
                        <a href="{{ route('call-logs.index') }}" class="btn-back">
                            <i class="fas fa-list"></i> العودة للقائمة
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Show/hide duration field based on contact type
    $('#contact_type').change(function() {
        const contactType = $(this).val();
        const durationField = $('#duration_minutes').closest('.form-group');

        if (contactType === 'call') {
            durationField.show();
            $('#duration_minutes').attr('placeholder', 'مدة المكالمة بالدقائق');
        } else if (contactType === 'meeting') {
            durationField.show();
            $('#duration_minutes').attr('placeholder', 'مدة الاجتماع بالدقائق');
        } else {
            durationField.hide();
        }
    });

    // Trigger change event on page load
    $('#contact_type').trigger('change');
});
</script>
@endpush
