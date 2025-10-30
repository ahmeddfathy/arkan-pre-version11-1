@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/additional-tasks.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="py-12">
    <div style="width: 100%; padding: 0 2rem;">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <!-- Header -->
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">إدارة طلبات المهام الإضافية</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            <i class="fas fa-file-contract text-orange-500"></i> مراجعة وإدارة طلبات المستخدمين للمشاركة في المهام
                        </p>
                    </div>
                    <div class="flex space-x-2 space-x-reverse">
                        <a href="{{ route('additional-tasks.index') }}"
                           class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-arrow-left"></i> العودة للمهام
                        </a>
                        @if(isset($selectedTask))
                            <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                                <i class="fas fa-info-circle"></i>
                                عرض طلبات المهمة: <strong>{{ $selectedTask->title }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        {{ session('error') }}
                    </div>
                @endif

                @if($applications->count() > 0)
                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-clock text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="mr-3">
                                    <div class="text-sm font-medium text-yellow-800">طلبات في الانتظار</div>
                                    <div class="text-2xl font-bold text-yellow-900">{{ $applications->total() }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-tasks text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="mr-3">
                                    <div class="text-sm font-medium text-blue-800">مهام مختلفة</div>
                                    <div class="text-2xl font-bold text-blue-900">{{ $applications->groupBy('additional_task_id')->count() }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-purple-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-users text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="mr-3">
                                    <div class="text-sm font-medium text-purple-800">مستخدمين مختلفين</div>
                                    <div class="text-2xl font-bold text-purple-900">{{ $applications->groupBy('user_id')->count() }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-chart-line text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="mr-3">
                                    <div class="text-sm font-medium text-green-800">اليوم</div>
                                    <div class="text-2xl font-bold text-green-900">{{ $applications->where('applied_at', '>=', today())->count() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Applications List -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-semibold text-gray-900">
                                <i class="fas fa-list text-indigo-500"></i> قائمة الطلبات
                            </h3>
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <span class="text-sm font-medium text-gray-700">طريقة العرض:</span>
                                <button onclick="toggleApplicationsView('cards')" id="applications-cards-view-btn"
                                        class="px-4 py-2 text-sm font-medium rounded-l-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <i class="fas fa-th-large mr-1"></i> كاردات
                                </button>
                                <button onclick="toggleApplicationsView('table')" id="applications-table-view-btn"
                                        class="px-4 py-2 text-sm font-medium rounded-r-lg border-t border-r border-b border-gray-300 bg-gray-100 text-gray-700 hover:bg-gray-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <i class="fas fa-table mr-1"></i> جدول
                                </button>
                            </div>
                        </div>

                        <!-- Cards View -->
                        <div id="applications-cards-view" class="space-y-4">
                            @foreach($applications as $application)
                                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between">
                                        <!-- Task and User Info -->
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-4 space-x-reverse mb-4">
                                                <!-- Task Icon -->
                                                @if($application->additionalTask->icon)
                                                    <div class="h-12 w-12 rounded-full flex items-center justify-center text-white"
                                                         style="background-color: {{ $application->additionalTask->color_code }};">
                                                        <i class="{{ $application->additionalTask->icon }}"></i>
                                                    </div>
                                                @else
                                                    <div class="h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <i class="fas fa-tasks text-gray-600"></i>
                                                    </div>
                                                @endif

                                                <!-- Task Details -->
                                                <div class="flex-1">
                                                    <h4 class="text-xl font-semibold text-gray-900">{{ $application->additionalTask->title }}</h4>
                                                    <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-600 mt-1">
                                                        <div>
                                                            <i class="fas fa-star text-yellow-500"></i>
                                                            {{ number_format($application->additionalTask->points) }} نقطة
                                                        </div>
                                                        <div>
                                                            <i class="fas fa-clock text-blue-500"></i>
                                                            @if($application->additionalTask->isExpired())
                                                                <span class="text-red-600">انتهت</span>
                                                            @else
                                                                @php $hoursRemaining = $application->additionalTask->timeRemainingInHours(); @endphp
                                                                @if($hoursRemaining > 24)
                                                                    {{ round($hoursRemaining / 24, 1) }} يوم متبقي
                                                                @else
                                                                    {{ $hoursRemaining }} ساعة متبقية
                                                                @endif
                                                            @endif
                                                        </div>
                                                        @if($application->additionalTask->max_participants)
                                                            <div>
                                                                <i class="fas fa-users text-purple-500"></i>
                                                                {{ $application->additionalTask->getApprovedParticipantsCount() }}/{{ $application->additionalTask->max_participants }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                    @if($application->additionalTask->description)
                                                        <p class="text-gray-600 mt-2 line-clamp-2">{{ $application->additionalTask->description }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- User Info -->
                                            <div class="flex items-center space-x-3 space-x-reverse bg-gray-50 rounded-lg p-4">
                                                @if($application->user->profile_photo_path)
                                                    <img src="{{ asset('storage/' . $application->user->profile_photo_path) }}"
                                                         alt="{{ $application->user->name }}"
                                                         class="h-10 w-10 rounded-full">
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-gray-700">{{ substr($application->user->name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                <div class="flex-1">
                                                    <div class="text-lg font-semibold text-gray-900">{{ $application->user->name }}</div>
                                                    <div class="text-sm text-gray-600">
                                                        <i class="fas fa-building mr-1"></i>{{ $application->user->department }}
                                                        <span class="mx-2">|</span>
                                                        <i class="fas fa-envelope mr-1"></i>{{ $application->user->email }}
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm text-gray-500">تقدم في:</div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $application->applied_at->format('Y-m-d H:i') }}</div>
                                                    <div class="text-xs text-gray-500">{{ $application->applied_at->diffForHumans() }}</div>
                                                </div>
                                            </div>

                                            <!-- User Notes -->
                                            @if($application->user_notes)
                                                <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                                    <h5 class="font-medium text-blue-900 mb-2">
                                                        <i class="fas fa-comment text-blue-500"></i> ملاحظات المستخدم:
                                                    </h5>
                                                    <p class="text-blue-800">{{ $application->user_notes }}</p>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-col space-y-2 mr-6">
                                            @if($application->additionalTask->canAcceptMoreParticipants())
                                                <!-- Approve Button -->
                                                <button onclick="showApproveModal({{ $application->id }}, '{{ $application->user->name }}', '{{ $application->additionalTask->title }}')"
                                                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                                    <i class="fas fa-check"></i> قبول
                                                </button>
                                            @else
                                                <div class="bg-gray-100 text-gray-500 font-bold py-2 px-4 rounded text-center">
                                                    <i class="fas fa-users"></i> مكتمل
                                                </div>
                                            @endif

                                            <!-- Reject Button -->
                                            <button onclick="showRejectModal({{ $application->id }}, '{{ $application->user->name }}', '{{ $application->additionalTask->title }}')"
                                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                <i class="fas fa-times"></i> رفض
                                            </button>

                                            <!-- View Task -->
                                            <a href="{{ route('additional-tasks.show', $application->additionalTask) }}"
                                               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center">
                                                <i class="fas fa-eye"></i> المهمة
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Table View -->
                        <div id="applications-table-view" class="hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المهمة</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستخدم</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النقاط</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ التقديم</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($applications as $application)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        @if($application->additionalTask->icon)
                                                            <div class="h-10 w-10 rounded-full flex items-center justify-center text-white ml-3"
                                                                 style="background-color: {{ $application->additionalTask->color_code }};">
                                                                <i class="{{ $application->additionalTask->icon }}"></i>
                                                            </div>
                                                        @else
                                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center ml-3">
                                                                <i class="fas fa-tasks text-gray-600"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900">{{ $application->additionalTask->title }}</div>
                                                            @if($application->additionalTask->description)
                                                                <div class="text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($application->additionalTask->description, 50) }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        @if($application->user->profile_photo_path)
                                                            <img src="{{ asset('storage/' . $application->user->profile_photo_path) }}"
                                                                 alt="{{ $application->user->name }}"
                                                                 class="h-8 w-8 rounded-full ml-3">
                                                        @else
                                                            <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center ml-3">
                                                                <span class="text-xs font-medium text-gray-700">{{ substr($application->user->name, 0, 1) }}</span>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900">{{ $application->user->name }}</div>
                                                            <div class="text-sm text-gray-500">{{ $application->user->department }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-bold text-green-600">{{ number_format($application->additionalTask->points) }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">{{ $application->applied_at->format('Y-m-d H:i') }}</div>
                                                    <div class="text-xs text-gray-500">{{ $application->applied_at->diffForHumans() }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex space-x-2 space-x-reverse">
                                                        @if($application->additionalTask->canAcceptMoreParticipants())
                                                            <button onclick="showApproveModal({{ $application->id }}, '{{ $application->user->name }}', '{{ $application->additionalTask->title }}')"
                                                                    class="text-green-600 hover:text-green-900">
                                                                <i class="fas fa-check"></i> قبول
                                                            </button>
                                                        @else
                                                            <span class="text-gray-400">مكتمل</span>
                                                        @endif
                                                        <button onclick="showRejectModal({{ $application->id }}, '{{ $application->user->name }}', '{{ $application->additionalTask->title }}')"
                                                                class="text-red-600 hover:text-red-900">
                                                            <i class="fas fa-times"></i> رفض
                                                        </button>
                                                        <a href="{{ route('additional-tasks.show', $application->additionalTask) }}" class="text-gray-600 hover:text-gray-900">
                                                            <i class="fas fa-eye"></i> عرض
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-8">
                            {{ $applications->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">لا توجد طلبات في الانتظار</h3>
                        <p class="text-gray-500 mb-6">لا توجد طلبات من المستخدمين تحتاج لمراجعة في الوقت الحالي</p>
                        <a href="{{ route('additional-tasks.index') }}"
                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-arrow-left"></i> العودة للمهام
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-check text-green-500"></i> الموافقة على الطلب
        </h3>
        <form action="#" method="POST" id="approveForm">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">المستخدم</label>
                <div id="approve_user_name" class="text-lg font-medium text-gray-900"></div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">المهمة</label>
                <div id="approve_task_title" class="text-lg font-medium text-gray-900"></div>
            </div>

            <div class="mb-4">
                <label for="approve_admin_notes" class="block text-sm font-medium text-gray-700 mb-2">ملاحظات الموافقة (اختياري)</label>
                <textarea id="approve_admin_notes" name="admin_notes" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="أي ملاحظات أو تعليمات للمستخدم"></textarea>
            </div>

            <div class="bg-green-50 border border-green-200 rounded p-3 mb-4">
                <div class="text-sm text-green-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    سيتم قبول الطلب وتخصيص المهمة للمستخدم فوراً
                </div>
            </div>

            <div class="flex justify-end space-x-2 space-x-reverse">
                <button type="button" onclick="hideApproveModal()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    إلغاء
                </button>
                <button type="submit"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-check"></i> الموافقة
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-times text-red-500"></i> رفض الطلب
        </h3>
        <form action="#" method="POST" id="rejectForm">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">المستخدم</label>
                <div id="reject_user_name" class="text-lg font-medium text-gray-900"></div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">المهمة</label>
                <div id="reject_task_title" class="text-lg font-medium text-gray-900"></div>
            </div>

            <div class="mb-4">
                <label for="reject_admin_notes" class="block text-sm font-medium text-gray-700 mb-2">سبب الرفض</label>
                <textarea id="reject_admin_notes" name="admin_notes" rows="3" required
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="اشرح سبب رفض الطلب"></textarea>
            </div>

            <div class="bg-red-50 border border-red-200 rounded p-3 mb-4">
                <div class="text-sm text-red-700">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    سيتم رفض الطلب وإشعار المستخدم بالسبب
                </div>
            </div>

            <div class="flex justify-end space-x-2 space-x-reverse">
                <button type="button" onclick="hideRejectModal()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    إلغاء
                </button>
                <button type="submit"
                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                        onclick="return confirm('هل أنت متأكد من رفض هذا الطلب؟')">
                    <i class="fas fa-times"></i> رفض
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Approve modal
function showApproveModal(applicationId, userName, taskTitle) {
    document.getElementById('approve_user_name').textContent = userName;
    document.getElementById('approve_task_title').textContent = taskTitle;
    document.getElementById('approveForm').action = `/additional-task-users/${applicationId}/approve`;
    document.getElementById('approveModal').classList.remove('hidden');
}

function hideApproveModal() {
    document.getElementById('approveModal').classList.add('hidden');
    document.getElementById('approve_admin_notes').value = '';
}

// Reject modal
function showRejectModal(applicationId, userName, taskTitle) {
    document.getElementById('reject_user_name').textContent = userName;
    document.getElementById('reject_task_title').textContent = taskTitle;
    document.getElementById('rejectForm').action = `/additional-task-users/${applicationId}/reject`;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('reject_admin_notes').value = '';
}

// View toggle functionality for applications
function toggleApplicationsView(viewType) {
    const cardsView = document.getElementById('applications-cards-view');
    const tableView = document.getElementById('applications-table-view');
    const cardsBtn = document.getElementById('applications-cards-view-btn');
    const tableBtn = document.getElementById('applications-table-view-btn');

    if (viewType === 'cards') {
        if (cardsView) cardsView.classList.remove('hidden');
        if (tableView) tableView.classList.add('hidden');
        if (cardsBtn) {
            cardsBtn.classList.remove('bg-gray-100');
            cardsBtn.classList.add('bg-white');
        }
        if (tableBtn) {
            tableBtn.classList.remove('bg-white');
            tableBtn.classList.add('bg-gray-100');
        }
        localStorage.setItem('applicationsView', 'cards');
    } else {
        if (cardsView) cardsView.classList.add('hidden');
        if (tableView) tableView.classList.remove('hidden');
        if (cardsBtn) {
            cardsBtn.classList.remove('bg-white');
            cardsBtn.classList.add('bg-gray-100');
        }
        if (tableBtn) {
            tableBtn.classList.remove('bg-gray-100');
            tableBtn.classList.add('bg-white');
        }
        localStorage.setItem('applicationsView', 'table');
    }
}

// Initialize view from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('applicationsView') || 'cards';
    toggleApplicationsView(savedView);
});

// Close modals when clicking outside
window.onclick = function(event) {
    const approveModal = document.getElementById('approveModal');
    const rejectModal = document.getElementById('rejectModal');

    if (event.target == approveModal) {
        hideApproveModal();
    }
    if (event.target == rejectModal) {
        hideRejectModal();
    }
}
</script>
@endsection
