<style>
    /* General Notification Styling */
    .notification-item {
        background-color: #fff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease-in-out;
        overflow: hidden;
        font-family: 'Arial', sans-serif;
    }

    .notification-item.unread {
        background-color: #f0f8ff;
        border-left: 4px solid #007bff;
    }

    .notification-item:hover {
        transform: scale(1.02);
    }

    /* Notification Link */
    .notification-link {
        text-decoration: none;
        color: #333;
    }

    /* Notification Header */
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    /* Notification Title */
    .notification-title {
        font-weight: bold;
        font-size: 16px;
        color: #333;
    }

    /* Notification Time */
    .notification-time {
        font-size: 12px;
    }

    /* Notification Content */
    .notification-content {
        font-size: 14px;
        color: #555;
        line-height: 1.5;
    }

    /* Empty State */
    .no-notifications {
        color: #888;
        font-size: 14px;
    }

    /* Hover Effects */
    .notification-item:hover .notification-content {
        color: #007bff;
    }
</style>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('الإشعارات') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('جميع الإشعارات') }}</h3>

                    <div class="space-y-4">
                        @forelse($notifications as $notification)
                            <div
                                class="notification-item p-4 border rounded-lg {{ is_null($notification->read_at) ? 'bg-blue-50 border-blue-200' : 'bg-white border-gray-200' }}"
                                data-id="{{ $notification->id }}"
                                data-read-at="{{ $notification->read_at }}"
                                data-created-at="{{ $notification->created_at }}"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="notification-message text-gray-800 text-lg font-medium">
                                        @if(isset($notification->data['message']))
                                            {{ $notification->data['message'] }}
                                        @elseif(isset($notification->data['title']))
                                            {{ $notification->data['title'] }}
                                        @else
                                            إشعار جديد
                                        @endif
                                    </div>

                                    <div class="text-gray-500 text-sm">
                                        {{ $notification->created_at->format('Y-m-d H:i') }}
                                    </div>
                                </div>

                                @if(is_null($notification->read_at))
                                    <div class="mt-2">
                                        <button
                                            class="text-sm text-blue-600 hover:text-blue-800 mark-as-read-btn"
                                            data-notification-id="{{ $notification->id }}"
                                        >
                                            تحديد كمقروء
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-6">
                                {{ __('لا توجد إشعارات') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const markAsReadBtns = document.querySelectorAll('.mark-as-read-btn');

            markAsReadBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const notificationId = this.getAttribute('data-notification-id');

                    fetch(`/notifications/${notificationId}/mark-as-read`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update the UI
                                const notificationItem = this.closest('.notification-item');
                                notificationItem.classList.remove('bg-blue-50', 'border-blue-200');
                                notificationItem.classList.add('bg-white', 'border-gray-200');
                                this.parentElement.remove();
                            }
                        })
                        .catch(error => {
                            console.error('Error marking notification as read:', error);
                        });
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
