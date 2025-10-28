@extends('admin.notifications.layouts.notification-layout')

@section('page-title', 'تفاصيل الإشعار')

@section('notification-content')
<div class="card shadow-sm">
  <div class="card-header bg-white py-3">
    <h4 class="mb-0 fw-bold">تفاصيل الإشعار</h4>
  </div>
  <div class="card-body p-4">
    <div class="notification-details mb-4 p-4 border-bottom">
      <h5 class="notification-title mb-3 d-flex align-items-center">
        <i class="fas {{ $notification->type === 'administrative_decision' ? 'fa-gavel' : 'fa-bell' }} me-2"></i>
        {{ $notification->data['title'] }}
      </h5>
      <p class="notification-message mb-4">{{ $notification->data['message'] }}</p>
      <div class="d-flex align-items-center text-muted">
        <i class="far fa-clock me-2"></i>
        <span>تاريخ الإنشاء: {{ $notification->created_at->format('Y-m-d H:i') }}</span>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="stats-card success">
          <h3>{{ $readCount }}</h3>
          <p>قرأوا الإشعار</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stats-card warning">
          <h3>{{ $unreadCount }}</h3>
          <p>لم يقرأوا</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stats-card">
          <h3>{{ $totalRecipients }}</h3>
          <p>إجمالي المستلمين</p>
        </div>
      </div>
    </div>

    <div class="progress-container mb-4">
      <div class="progress-bar-custom">
        @php
        $percentage = 0;
        if ($totalRecipients > 0) {
            $percentage = ($readCount / $totalRecipients) * 100;
        }
        @endphp
        <div class="progress-fill" style="width: {{ $percentage }}%;"></div>
      </div>
      <div class="progress-stats mt-3 text-center">
        <strong class="text-primary">{{ $readCount }}</strong>
        <span class="text-muted">من</span>
        <strong class="text-primary">{{ $totalRecipients }}</strong>
        <span class="text-muted">قرأوا الإشعار</span>
      </div>
    </div>

    <div class="card shadow-sm border">
      <div class="card-header bg-white py-3">
        <h5 class="mb-0">قائمة المستلمين</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th class="py-3">اسم الموظف</th>
                <th class="py-3">حالة القراءة</th>
                <th class="py-3">تاريخ القراءة</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recipients as $recipient)
              <tr>
                <td class="py-3">{{ $recipient->name }}</td>
                <td class="py-3">
                  @if($recipient->read_at)
                  <span class="notification-badge badge-normal px-3 py-2">
                    <i class="fas fa-check-circle me-1"></i>
                    تمت القراءة
                  </span>
                  @else
                  <span class="notification-badge badge-warning px-3 py-2">
                    <i class="fas fa-clock me-1"></i>
                    لم يقرأ
                  </span>
                  @endif
                </td>
                <td class="py-3">
                  @if($recipient->read_at)
                  <span>{{ $recipient->read_at->format('Y-m-d H:i') }}</span>
                  @else
                  <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-4">
      <a href="{{ route('admin.notifications.index') }}" class="btn btn-filter secondary">
        <i class="fas fa-arrow-right"></i>
        العودة للقائمة
      </a>
    </div>
  </div>
</div>
@endsection

@push('page-styles')
<style>
.notification-details {
  background-color: #f8f9fa;
  border-radius: 15px;
}

.notification-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: #333;
}

.notification-title i {
  color: #8E54E9;
  background: rgba(142, 84, 233, 0.1);
  padding: 10px;
  border-radius: 50%;
}

.notification-message {
  font-size: 1.1rem;
  line-height: 1.8;
  color: #555;
}

.text-primary {
  color: #8E54E9 !important;
}

.table-hover tbody tr:hover {
  background-color: rgba(142, 84, 233, 0.05);
}
</style>
@endpush
