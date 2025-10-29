@extends('layouts.app')

@section('title', 'تفاصيل الاجتماع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/meetings/meetings-modern.css') }}">
<style>
.mention {
    background-color: #e3f2fd;
    color: #1976d2;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 500;
}

.mention-everyone {
    background-color: #fff3e0 !important;
    color: #f57c00 !important;
    border: 1px solid #ffa726;
    font-weight: 600;
}
</style>
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📋 {{ $meeting->title }}</h1>
            <p>عرض تفاصيل الاجتماع الكاملة</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        <!-- Meeting Details Container -->
        <div class="detail-container">
            <div class="detail-header">
                <h2>{{ $meeting->title }}</h2>
                <p>معلومات وتفاصيل الاجتماع</p>
            </div>

            <div class="detail-body">
                <!-- Meeting Information Section -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        تفاصيل الاجتماع
                    </h3>

                    <div class="detail-item">
                        <span class="detail-label">النوع:</span>
                        <div class="detail-value">
                            @if($meeting->type === 'internal')
                                <span class="status-badge status-scheduled">
                                    <i class="fas fa-building"></i>
                                    اجتماع داخلي
                                </span>
                            @else
                                <span class="status-badge status-approved">
                                    <i class="fas fa-handshake"></i>
                                    اجتماع مع عميل
                                    @if(Auth::user()->hasRole('sales_employee') && $meeting->client)
                                        : {{ $meeting->client->name }} ({{ $meeting->client->client_code }})
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">التاريخ:</span>
                        <span class="detail-value">
                            <i class="fas fa-calendar"></i>
                            {{ $meeting->start_time->format('Y-m-d') }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">الوقت:</span>
                        <span class="detail-value">
                            <i class="fas fa-clock"></i>
                            {{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">المدة:</span>
                        <span class="detail-value">
                            <i class="fas fa-hourglass-half"></i>
                            {{ $meeting->start_time->diffInMinutes($meeting->end_time) }} دقيقة
                        </span>
                    </div>

                    @if($meeting->isClientMeeting())
                    <div class="detail-item">
                        <span class="detail-label">حالة الموافقة:</span>
                        <div class="detail-value">
                            @if($meeting->approval_status === 'pending')
                                <span class="status-badge status-pending">
                                    <i class="fas fa-clock"></i> في انتظار الموافقة
                                </span>
                            @elseif($meeting->approval_status === 'approved')
                                <span class="status-badge status-approved">
                                    <i class="fas fa-check"></i> موافق عليه
                                </span>
                            @elseif($meeting->approval_status === 'rejected')
                                <span class="status-badge status-rejected">
                                    <i class="fas fa-times"></i> مرفوض
                                </span>
                            @else
                                <span class="status-badge status-approved">
                                    <i class="fas fa-check-circle"></i> معتمد تلقائياً
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($meeting->approved_by)
                    <div class="detail-item">
                        <span class="detail-label">تمت الموافقة بواسطة:</span>
                        <span class="detail-value">{{ $meeting->approver->name ?? 'غير محدد' }}</span>
                    </div>
                    @endif

                    @if($meeting->approval_notes)
                    <div class="detail-item">
                        <span class="detail-label">ملاحظات الموافقة:</span>
                        <span class="detail-value">{{ $meeting->approval_notes }}</span>
                    </div>
                    @endif
                    @endif

                    @if($meeting->project)
                    <div class="detail-item">
                        <span class="detail-label">المشروع:</span>
                        <span class="detail-value">
                            {{ $meeting->project->name }} ({{ $meeting->project->code }})
                            @if($canViewClientData && $meeting->project->client)
                                - {{ $meeting->project->client->name }} ({{ $meeting->project->client->client_code }})
                            @endif
                        </span>
                    </div>
                    @endif

                    @php
                        $clientToShow = $meeting->client ?? ($meeting->project ? $meeting->project->client : null);
                    @endphp

                    @if($canViewClientData && $clientToShow)
                    <div class="detail-item">
                        <span class="detail-label">العميل:</span>
                        <div class="detail-value">
                            <strong>{{ $clientToShow->name }} ({{ $clientToShow->client_code }})</strong>
                            <div class="mt-2">
                                <a href="{{ route('clients.show', $clientToShow) }}" class="meetings-btn btn-view" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                                    <i class="fas fa-user-tie"></i> عرض ملف العميل
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="detail-item">
                        <span class="detail-label">المكان:</span>
                        <span class="detail-value">
                            <i class="fas fa-map-marker-alt"></i>
                            {{ $meeting->location ?? 'غير محدد' }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">منشئ الاجتماع:</span>
                        <span class="detail-value">
                            <i class="fas fa-user"></i>
                            {{ $meeting->creator->name }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">الحالة:</span>
                        <div class="detail-value">
                            @if($meeting->status === 'cancelled')
                                <span class="status-badge status-cancelled">
                                    <i class="fas fa-times-circle"></i>
                                    ملغي
                                </span>
                            @elseif($meeting->status === 'completed')
                                <span class="status-badge status-completed">
                                    <i class="fas fa-check-circle"></i>
                                    مكتمل
                                </span>
                            @else
                                <span class="status-badge status-scheduled">
                                    <i class="fas fa-calendar-check"></i>
                                    مجدول
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Description Section -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-align-left"></i>
                        الوصف
                    </h3>
                    <div class="detail-item">
                        <p class="detail-value" style="width: 100%;">{{ $meeting->description ?: 'لا يوجد وصف' }}</p>
                    </div>
                </div>

                <!-- Approval Actions (Technical Support) -->
                @php
                    $isTechnicalSupport = Auth::user()->hasRole('technical_support');
                @endphp

                @if($meeting->type === 'client' && $meeting->approval_status === 'pending' && $isTechnicalSupport)
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-clipboard-check"></i>
                        إجراءات الموافقة
                    </h3>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div style="padding: 1.5rem; background: #f9fafb; border-radius: 10px;">
                                <h6 style="margin: 0 0 1rem 0; font-weight: 600;">موافقة على الاجتماع</h6>
                                <form action="{{ route('meetings.approve', $meeting) }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="approval_notes_approve" class="form-label">ملاحظات (اختياري)</label>
                                        <textarea name="approval_notes" id="approval_notes_approve" rows="2" class="form-control" placeholder="أي ملاحظات إضافية..."></textarea>
                                    </div>
                                    <button type="submit" class="meetings-btn" style="margin-top: 1rem;">
                                        <i class="fas fa-check"></i> موافقة
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div style="padding: 1.5rem; background: #f9fafb; border-radius: 10px;">
                                <h6 style="margin: 0 0 1rem 0; font-weight: 600;">رفض الاجتماع</h6>
                                <form action="{{ route('meetings.reject', $meeting) }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="approval_notes_reject" class="form-label">سبب الرفض <span style="color: #dc2626;">*</span></label>
                                        <textarea name="approval_notes" id="approval_notes_reject" rows="2" class="form-control" required placeholder="اذكر سبب رفض الاجتماع..."></textarea>
                                    </div>
                                    <button type="submit" class="meetings-btn btn-delete" style="margin-top: 1rem;">
                                        <i class="fas fa-times"></i> رفض
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div style="padding: 1.5rem; background: #f9fafb; border-radius: 10px;">
                                <h6 style="margin: 0 0 1rem 0; font-weight: 600;">تعديل الوقت والموافقة</h6>
                                <form action="{{ route('meetings.update-time', $meeting) }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="start_time" class="form-label">وقت البدء الجديد</label>
                                                <input type="datetime-local" name="start_time" id="start_time" class="form-control"
                                                       value="{{ $meeting->start_time->format('Y-m-d\TH:i') }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="end_time" class="form-label">وقت الانتهاء الجديد</label>
                                                <input type="datetime-local" name="end_time" id="end_time" class="form-control"
                                                       value="{{ $meeting->end_time->format('Y-m-d\TH:i') }}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="approval_notes_time" class="form-label">ملاحظات التعديل (اختياري)</label>
                                        <textarea name="approval_notes" id="approval_notes_time" rows="2" class="form-control" placeholder="اذكر سبب تغيير الوقت..."></textarea>
                                    </div>
                                    <button type="submit" class="meetings-btn btn-edit" style="margin-top: 1rem;">
                                        <i class="fas fa-clock"></i> تعديل الوقت والموافقة
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Undo Approval -->
                @if($meeting->type === 'client' && in_array($meeting->approval_status, ['approved', 'auto_approved']) && $isTechnicalSupport)
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-undo"></i>
                        إجراءات إضافية
                    </h3>
                    <div style="padding: 1.5rem; background: #f9fafb; border-radius: 10px;">
                        <p style="margin: 0 0 1rem 0; color: #6b7280;">إذا وافقت على الاجتماع بالغلط، يمكنك إلغاء الموافقة والعودة للحالة المعلقة</p>
                        <form action="{{ route('meetings.undo-approval', $meeting) }}" method="POST">
                            @csrf
                            <button type="submit" class="meetings-btn btn-edit">
                                <i class="fas fa-undo"></i> إلغاء الموافقة
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Meeting Actions -->
                @if($meeting->created_by === Auth::id())
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-cog"></i>
                        إجراءات الاجتماع
                    </h3>
                    <div class="d-flex gap-2 flex-wrap">
                        @if($meeting->status !== 'cancelled' && $meeting->status !== 'completed')
                            <form action="{{ route('meetings.cancel', $meeting) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="meetings-btn btn-edit" onclick="return confirm('هل أنت متأكد من إلغاء هذا الاجتماع؟')">
                                    <i class="fas fa-times"></i> إلغاء الاجتماع
                                </button>
                            </form>
                        @endif
                        @if($meeting->status !== 'completed')
                            <form action="{{ route('meetings.mark-completed', $meeting) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="meetings-btn" onclick="return confirm('هل تريد تحديد هذا الاجتماع كمكتمل؟')">
                                    <i class="fas fa-check"></i> إتمام الاجتماع
                                </button>
                            </form>
                        @endif
                        @if($meeting->status === 'completed')
                            <form action="{{ route('meetings.reset-status', $meeting) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="meetings-btn btn-edit" onclick="return confirm('هل تريد إعادة تعيين حالة الاجتماع؟')">
                                    <i class="fas fa-undo"></i> إعادة تعيين
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('meetings.edit', $meeting) }}" class="meetings-btn btn-view">
                            <i class="fas fa-edit"></i> تعديل الاجتماع
                        </a>
                        <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="meetings-btn btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا الاجتماع؟')">
                                <i class="fas fa-trash"></i> حذف الاجتماع
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Participants Section -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-users"></i>
                        المشاركون
                    </h3>
                    <table class="participants-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الحضور</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($meeting->participants as $participant)
                                <tr>
                                    <td>{{ $participant->name }}</td>
                                    <td>{{ $participant->email }}</td>
                                    <td>
                                        @if($meeting->created_by === Auth::id())
                                            <form action="{{ route('meetings.mark-attendance', $meeting) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $participant->id }}">
                                                <input type="hidden" name="attended" value="{{ $participant->pivot->attended ? 0 : 1 }}">
                                                <button type="submit" class="meetings-btn {{ $participant->pivot->attended ? '' : 'btn-delete' }}" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                                                    @if($participant->pivot->attended)
                                                        <i class="fas fa-check"></i> حاضر
                                                    @else
                                                        <i class="fas fa-times"></i> غائب
                                                    @endif
                                                </button>
                                            </form>
                                        @else
                                            @if($participant->pivot->attended)
                                                <span class="status-badge status-approved">
                                                    <i class="fas fa-check"></i> حاضر
                                                </span>
                                            @else
                                                <span class="status-badge status-rejected">
                                                    <i class="fas fa-times"></i> غائب
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Notes Section -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-sticky-note"></i>
                        ملاحظات الاجتماع
                    </h3>

                    @if(is_array($meeting->notes) && count($meeting->notes) > 0)
                        <div class="notes-list">
                            @foreach($meeting->notes as $note)
                                <div class="note-item">
                                    <div class="note-header">
                                        <span class="note-user">
                                            <i class="fas fa-user-circle"></i>
                                            {{ App\Models\User::find($note['user_id'])->name ?? 'مستخدم غير معروف' }}
                                        </span>
                                        <span class="note-date">
                                            <i class="fas fa-clock"></i>
                                            {{ \Carbon\Carbon::parse($note['created_at'])->format('Y-m-d H:i') }}
                                        </span>
                                    </div>
                                    <p class="note-content">{{ $note['content'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p style="color: #9ca3af; text-align: center; padding: 2rem;">لا توجد ملاحظات حتى الآن</p>
                    @endif

                    @if(!$meeting->is_completed)
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e5e7eb;">
                            <h4 style="margin: 0 0 1rem 0; font-weight: 600; color: #374151;">إضافة ملاحظة جديدة</h4>
                            <form action="{{ route('meetings.add-note', $meeting) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="content" class="form-label">
                                        <i class="fas fa-pen"></i>
                                        الملاحظة
                                        <small style="color: #6b7280; font-weight: normal;">(يمكنك استخدام @ لذكر المشاركين)</small>
                                    </label>
                                    <textarea id="content" name="content" rows="3" class="form-control"
                                              placeholder="اكتب ملاحظتك هنا... استخدم @ لذكر المشاركين في الاجتماع" required></textarea>
                                    <small style="color: #6b7280; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                                        <i class="fas fa-info-circle"></i>
                                        اكتب @ متبوعاً باسم المشارك لذكره في الملاحظة وإرسال إشعار له<br>
                                        <i class="fas fa-users" style="color: #f59e0b;"></i>
                                        استخدم <code>@everyone</code> أو <code>@الجميع</code> لإشعار جميع المشاركين
                                    </small>
                                    @error('content')
                                        <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div style="text-align: left; margin-top: 1rem;">
                                    <button type="submit" class="meetings-btn">
                                        <i class="fas fa-paper-plane"></i>
                                        إضافة ملاحظة
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- Back Link -->
                <div style="text-align: center; padding-top: 2rem; border-top: 2px solid #e5e7eb;">
                    <a href="{{ route('meetings.index') }}" class="meetings-btn btn-view">
                        <i class="fas fa-arrow-right"></i>
                        العودة إلى قائمة الاجتماعات
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/meetings/notes-mentions.js') }}"></script>
@endpush
@endsection
