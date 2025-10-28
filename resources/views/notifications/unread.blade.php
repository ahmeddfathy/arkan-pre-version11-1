@extends('layouts.app')

@section('content')
<div class="container">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">قرارات إدارية تحتاج للقراءة والتأكيد</h4>
        </div>
        <div class="card-body">
            @if($unreadDecisions->isEmpty())
            <div class="alert alert-info mb-0">
                لا توجد قرارات إدارية تحتاج للقراءة حالياً
            </div>
            @else
            @foreach($unreadDecisions as $decision)
            <div class="alert alert-warning mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-1">{{ $decision->notification->data['title'] }}</h5>
                    <small>{{ $decision->notification->created_at->format('Y-m-d H:i') }}</small>
                </div>
                <p class="mb-3">{{ $decision->notification->data['message'] }}</p>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        تم الإرسال بواسطة: {{ $decision->notification->data['sender_name'] }}
                    </small>
                    <form action="{{ route('notifications.acknowledge', $decision) }}"
                        method="POST"
                        class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            تأكيد القراءة والموافقة
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>
@endsection