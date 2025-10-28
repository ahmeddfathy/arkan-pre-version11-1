@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/meetings.css') }}" rel="stylesheet">
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
<div class="container">
    <h2 class="page-title">{{ __('تفاصيل الاجتماع') }}</h2>

    <div class="meetings-page">
        <div class="arkan-container">

            @if(session('success'))
                <div class="alert alert-success">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info">
                    <span class="block sm:inline">{{ session('info') }}</span>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="arkan-title text-xl mb-0">{{ $meeting->title }}</h3>
                <div class="d-flex gap-2">
                    @if($meeting->created_by === Auth::id())
                        <a href="{{ route('meetings.edit', $meeting) }}" class="arkan-btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                            </svg>
                            تعديل الاجتماع
                        </a>
                        @if(!$meeting->is_completed)
                            <form action="{{ route('meetings.mark-completed', $meeting) }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="arkan-btn-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    تحديد كمكتمل
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <div class="arkan-meeting-info">
                <div class="arkan-meeting-section">
                    <h4 class="arkan-subtitle">تفاصيل الاجتماع</h4>

                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">النوع:</span>
                        <div class="arkan-detail-value">
                            @if($meeting->type === 'internal')
                                <span class="arkan-badge arkan-badge-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                    اجتماع داخلي
                                </span>
                            @else
                                <span class="arkan-badge arkan-badge-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                    </svg>
                                    @if(Auth::user()->hasRole('sales_employee') && $meeting->client)
                                        اجتماع مع عميل: {{ $meeting->client->name }}
                                    @else
                                        اجتماع مع عميل
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">التاريخ:</span>
                        <span class="arkan-detail-value">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            {{ $meeting->start_time->format('Y-m-d') }}
                        </span>
                    </div>

                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">الوقت:</span>
                        <span class="arkan-detail-value">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            {{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}
                        </span>
                    </div>

                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">المدة:</span>
                        <span class="arkan-detail-value">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            {{ $meeting->start_time->diffInMinutes($meeting->end_time) }} دقيقة
                        </span>
                    </div>

                    @if($meeting->isClientMeeting())
                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">حالة الموافقة:</span>
                        <div class="arkan-detail-value">
                            @if($meeting->approval_status === 'pending')
                                <span class="arkan-badge arkan-badge-warning">
                                    <i class="fas fa-clock"></i> في انتظار الموافقة
                                </span>
                            @elseif($meeting->approval_status === 'approved')
                                <span class="arkan-badge arkan-badge-success">
                                    <i class="fas fa-check"></i> موافق عليه
                                </span>
                            @elseif($meeting->approval_status === 'rejected')
                                <span class="arkan-badge arkan-badge-danger">
                                    <i class="fas fa-times"></i> مرفوض
                                </span>
                            @else
                                <span class="arkan-badge arkan-badge-info">
                                    <i class="fas fa-check-circle"></i> معتمد تلقائياً
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($meeting->approved_by)
                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">تمت الموافقة بواسطة:</span>
                        <span class="arkan-detail-value">{{ $meeting->approver->name ?? 'غير محدد' }}</span>
                    </div>
                    @endif

                    @if($meeting->approved_at)
                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">تاريخ الموافقة:</span>
                        <span class="arkan-detail-value">{{ $meeting->approved_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif

                    @if($meeting->approval_notes)
                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">ملاحظات الموافقة:</span>
                        <span class="arkan-detail-value">{{ $meeting->approval_notes }}</span>
                    </div>
                    @endif
                    @endif

                    @if($meeting->project)
                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">المشروع:</span>
                        <span class="arkan-detail-value">
                            {{ $meeting->project->name }}
                            @if($canViewClientData && $meeting->project->client)
                                - {{ $meeting->project->client->name }}
                            @endif
                        </span>
                    </div>
                    @endif

                    <!-- Client Button for Sales Team -->
                    @php
                        $clientToShow = $meeting->client ?? ($meeting->project ? $meeting->project->client : null);
                    @endphp

                    @if($canViewClientData && $clientToShow)
                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">العميل:</span>
                        <div class="arkan-detail-value">
                            <strong>{{ $clientToShow->name }}</strong>
                            <div class="mt-2">
                                <a href="{{ route('clients.show', $clientToShow) }}" class="arkan-btn-primary arkan-btn-sm">
                                    <i class="fas fa-user-tie"></i> عرض ملف العميل
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">المكان:</span>
                        <span class="arkan-detail-value">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            {{ $meeting->location ?? 'غير محدد' }}
                        </span>
                    </div>

                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">منشئ الاجتماع:</span>
                        <span class="arkan-detail-value">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                            {{ $meeting->creator->name }}
                        </span>
                    </div>

                    <div class="arkan-meeting-detail">
                        <span class="arkan-detail-label">الحالة:</span>
                        <div class="arkan-detail-value">
                            @if($meeting->status === 'cancelled')
                                <span class="arkan-badge arkan-badge-danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    ملغي
                                </span>
                            @elseif($meeting->status === 'completed')
                                <span class="arkan-badge arkan-badge-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    مكتمل
                                </span>
                            @else
                                <span class="arkan-badge arkan-badge-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    </svg>
                                    مجدول
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="arkan-meeting-section">
                    <h4 class="arkan-subtitle">الوصف</h4>
                                                        <p class="arkan-detail-value">{{ $meeting->description ?: 'لا يوجد وصف' }}</p>
                </div>

                @php
                    $isTechnicalSupport = Auth::user()->hasRole('technical_support');
                @endphp

                @if($meeting->type === 'client' && $meeting->approval_status === 'pending' && $isTechnicalSupport)
                <div class="arkan-meeting-section">
                    <h4 class="arkan-subtitle">إجراءات الموافقة</h4>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">موافقة على الاجتماع</h6>
                                    <form action="{{ route('meetings.approve', $meeting) }}" method="POST" class="d-inline-block me-2">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="approval_notes_approve" class="form-label">ملاحظات (اختياري)</label>
                                            <textarea name="approval_notes" id="approval_notes_approve" rows="2" class="form-control" placeholder="أي ملاحظات إضافية..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> موافقة
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">رفض الاجتماع</h6>
                                    <form action="{{ route('meetings.reject', $meeting) }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="approval_notes_reject" class="form-label">سبب الرفض <span class="text-danger">*</span></label>
                                            <textarea name="approval_notes" id="approval_notes_reject" rows="2" class="form-control" required placeholder="اذكر سبب رفض الاجتماع..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-times"></i> رفض
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">تعديل الوقت والموافقة</h6>
                                    <form action="{{ route('meetings.update-time', $meeting) }}" method="POST">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="start_time" class="form-label">وقت البدء الجديد</label>
                                                <input type="datetime-local" name="start_time" id="start_time" class="form-control"
                                                       value="{{ $meeting->start_time->format('Y-m-d\TH:i') }}" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="end_time" class="form-label">وقت الانتهاء الجديد</label>
                                                <input type="datetime-local" name="end_time" id="end_time" class="form-control"
                                                       value="{{ $meeting->end_time->format('Y-m-d\TH:i') }}" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="approval_notes_time" class="form-label">ملاحظات التعديل (اختياري)</label>
                                            <textarea name="approval_notes" id="approval_notes_time" rows="2" class="form-control" placeholder="اذكر سبب تغيير الوقت..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-clock"></i> تعديل الوقت والموافقة
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- زر إلغاء الموافقة - يظهر للاجتماعات الموافق عليها --}}
                @if($meeting->type === 'client' && in_array($meeting->approval_status, ['approved', 'auto_approved']) && $isTechnicalSupport)
                <div class="arkan-meeting-section">
                    <h4 class="arkan-subtitle">إجراءات إضافية</h4>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">إلغاء الموافقة</h6>
                                    <p class="text-muted mb-3">إذا وافقت على الاجتماع بالغلط، يمكنك إلغاء الموافقة والعودة للحالة المعلقة</p>
                                    <form action="{{ route('meetings.undo-approval', $meeting) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-undo"></i> إلغاء الموافقة
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            @if($meeting->created_by === Auth::id())
                <div class="arkan-meeting-section mb-4">
                    <h4 class="arkan-subtitle">إجراءات الاجتماع</h4>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex gap-2 flex-wrap">
                                @if($meeting->status !== 'cancelled' && $meeting->status !== 'completed')
                                    <form action="{{ route('meetings.cancel', $meeting) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-warning" onclick="return confirm('هل أنت متأكد من إلغاء هذا الاجتماع؟ سيتم إشعار جميع المشاركين.')">
                                            <i class="fas fa-times"></i> إلغاء الاجتماع
                                        </button>
                                    </form>
                                @endif
                                @if($meeting->status !== 'completed')
                                    <form action="{{ route('meetings.mark-completed', $meeting) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success" onclick="return confirm('هل تريد تحديد هذا الاجتماع كمكتمل؟')">
                                            <i class="fas fa-check"></i> إتمام الاجتماع
                                        </button>
                                    </form>
                                @endif
                                @if($meeting->status === 'completed')
                                    <form action="{{ route('meetings.reset-status', $meeting) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-info" onclick="return confirm('هل تريد إعادة تعيين حالة الاجتماع إلى مجدول؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> تعديل الاجتماع
                                </a>
                                <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الاجتماع؟ هذا الإجراء لا يمكن التراجع عنه.')">
                                        <i class="fas fa-trash"></i> حذف الاجتماع
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-4 mb-4">
                <h4 class="arkan-subtitle">المشاركون</h4>
                <div class="arkan-meeting-section">
                    <div class="table-responsive">
                        <table class="arkan-table">
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
                                                <form action="{{ route('meetings.mark-attendance', $meeting) }}" method="POST" class="d-inline-flex align-items-center">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $participant->id }}">
                                                    <input type="hidden" name="attended" value="{{ $participant->pivot->attended ? 0 : 1 }}">
                                                    <button type="submit" class="btn {{ $participant->pivot->attended ? 'text-success' : 'text-danger' }} btn-link p-0">
                                                        @if($participant->pivot->attended)
                                                            <span>✓ حاضر</span>
                                                        @else
                                                            <span>✗ غائب</span>
                                                        @endif
                                                    </button>
                                                </form>
                                            @else
                                                @if($participant->pivot->attended)
                                                    <span class="text-success">✓ حاضر</span>
                                                @else
                                                    <span class="text-danger">✗ غائب</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h4 class="arkan-subtitle">ملاحظات الاجتماع</h4>
                <div class="arkan-meeting-section">
                    @if(is_array($meeting->notes) && count($meeting->notes) > 0)
                        <div class="notes-list">
                            @foreach($meeting->notes as $note)
                                <div class="arkan-note">
                                    <div class="arkan-note-header">
                                        <span class="arkan-note-user">
                                            {{ App\Models\User::find($note['user_id'])->name ?? 'مستخدم غير معروف' }}
                                        </span>
                                        <span class="arkan-note-date">
                                            {{ \Carbon\Carbon::parse($note['created_at'])->format('Y-m-d H:i') }}
                                        </span>
                                    </div>
                                    <p class="arkan-note-content">{{ $note['content'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">لا توجد ملاحظات حتى الآن</p>
                    @endif

                    @if(!$meeting->is_completed)
                        <div class="mt-4">
                            <form action="{{ route('meetings.add-note', $meeting) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="content" class="arkan-form-label">{{ __('إضافة ملاحظة جديدة') }} <small class="text-muted">(يمكنك استخدام @ لذكر المشاركين)</small></label>
                                    <textarea id="content" name="content" rows="3" class="arkan-form-control"
                                              placeholder="اكتب ملاحظتك هنا... استخدم @ لذكر المشاركين في الاجتماع" required></textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        اكتب @ متبوعاً باسم المشارك لذكره في الملاحظة وإرسال إشعار له<br>
                                        <i class="fas fa-users text-warning"></i>
                                        استخدم <code>@everyone</code> أو <code>@الجميع</code> لإشعار جميع المشاركين
                                    </small>
                                    @error('content')
                                        <p class="text-danger mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="arkan-btn-primary">
                                        {{ __('إضافة ملاحظة') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 border-top pt-3">
                <a href="{{ route('meetings.index') }}" class="text-primary">
                    &laquo; العودة إلى قائمة الاجتماعات
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/meetings/notes-mentions.js') }}"></script>
@endpush
