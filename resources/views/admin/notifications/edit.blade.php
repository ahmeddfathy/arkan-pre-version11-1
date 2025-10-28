@extends('admin.notifications.layouts.notification-layout')

@section('page-title', 'تعديل الإشعار')

@section('notification-content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">تعديل الإشعار</div>

            <div class="card-body">
                <form method="POST" action="{{ route('admin.notifications.update', $notification) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group mb-3">
                        <label for="title">عنوان الإشعار</label>
                        <input type="text"
                            class="form-control @error('title') is-invalid @enderror"
                            id="title"
                            name="title"
                            value="{{ old('title', $notification->data['title']) }}"
                            required>
                        @error('title')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="message">نص الإشعار</label>
                        <textarea class="form-control @error('message') is-invalid @enderror"
                            id="message"
                            name="message"
                            rows="4"
                            required>{{ old('message', $notification->data['message']) }}</textarea>
                        @error('message')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="is_administrative">نوع الإشعار</label>
                        <select class="form-select @error('is_administrative') is-invalid @enderror"
                            id="is_administrative"
                            name="is_administrative"
                            required>
                            <option value="0" {{ $notification->type !== 'administrative_decision' ? 'selected' : '' }}>إشعار عادي</option>
                            <option value="1" {{ $notification->type === 'administrative_decision' ? 'selected' : '' }}>قرار إداري</option>
                        </select>
                        @error('is_administrative')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="requires_acknowledgment">يتطلب تأكيد القراءة</label>
                        <select class="form-select @error('requires_acknowledgment') is-invalid @enderror"
                            id="requires_acknowledgment"
                            name="requires_acknowledgment"
                            required>
                            <option value="0" {{ !($notification->data['requires_acknowledgment'] ?? false) ? 'selected' : '' }}>لا</option>
                            <option value="1" {{ ($notification->data['requires_acknowledgment'] ?? false) ? 'selected' : '' }}>نعم</option>
                        </select>
                        @error('requires_acknowledgment')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            تحديث الإشعار
                        </button>
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
