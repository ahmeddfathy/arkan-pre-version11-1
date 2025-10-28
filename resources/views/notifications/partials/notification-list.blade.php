@forelse($notifications as $notification)
    <div
        class="notification-item {{ is_null($notification->read_at) ? 'unread' : '' }}"
        data-id="{{ $notification->id }}"
        data-read-at="{{ $notification->read_at }}"
        data-created-at="{{ $notification->created_at }}"
    >
        <div class="notification-message">
            @if(isset($notification->data['message']))
                {{ $notification->data['message'] }}
            @elseif(isset($notification->data['title']))
                {{ $notification->data['title'] }}
            @else
                إشعار جديد
            @endif
        </div>

        <div class="notification-time text-gray-400 text-xs mt-1">
            {{ $notification->created_at->diffForHumans() }}
        </div>
    </div>
@empty
    <div class="text-center text-gray-500 p-4">
        لا توجد إشعارات
    </div>
@endforelse
