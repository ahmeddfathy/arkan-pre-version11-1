@extends('layouts.app')

@section('title', 'لوحة متابعة الموظفين')

@push('styles')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- العنوان الرئيسي -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-800">لوحة متابعة نشاط الموظفين</h1>
            <p class="text-gray-600">تتبع نشاط الموظفين ومراقبة إنتاجيتهم</p>
        </div>

    <!-- فورم الفلترة -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" action="{{ route('tracking-employee.dashboard') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اختر الموظف:</label>
                    <select name="employee_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- اختر موظف --</option>
                        @if(isset($employees))
                            @foreach($employees as $employee)
                                <option value="{{ $employee->employee_id }}"
                                    {{ request('employee_id') == $employee->employee_id ? 'selected' : '' }}>
                                    {{ $employee->name }} - {{ $employee->employee_id }} ({{ $employee->department ?? 'غير محدد' }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">التاريخ:</label>
                    <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full md:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        بحث
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>

                <!-- زر تحميل التقرير -->
                <div class="flex items-end">
                    <a href="{{ route('tracking-employee.export.activity.report', ['employee_id' => request('employee_id'), 'date' => request('date', date('Y-m-d'))]) }}" class="w-full md:w-auto px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        تحميل التقرير
                    </a>
                </div>
    </form>
        </div>

        <!-- حالة الموظف -->
        @if(isset($data['employee_status']) && !empty($data['employee_status']['status_details']))
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">حالة الموظف في هذا اليوم</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($data['employee_status']['status_details'] as $status)
                <div class="flex items-center p-4 rounded-lg {{ $status['color'] }}">
                    <div class="flex-shrink-0">
                        <i class="{{ $status['icon'] }} text-lg"></i>
                    </div>
                    <div class="mr-3">
                        <h3 class="text-sm font-medium">{{ $status['title'] }}</h3>
                        <p class="text-xs text-gray-600">{{ $status['description'] }}</p>
                        @if(isset($status['details']))
                            <p class="text-xs font-semibold text-gray-800 mt-1">{{ $status['details'] }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- كروت الإحصائيات -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <!-- إجمالي نقرات الماوس -->
            <div class="bg-white rounded-lg shadow-md p-6 border-r-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">إجمالي نقرات الماوس</p>
                        <p class="text-3xl font-bold text-gray-800">
                            @php
                                $totalMouseClicks = 0;
                                if(isset($data['entries']) && is_array($data['entries'])) {
                                    foreach($data['entries'] as $entry) {
                                        $totalMouseClicks += $entry['mouse_clicks'] ?? 0;
                                    }
                                }
                            @endphp
                            {{ $totalMouseClicks }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- إجمالي ضغطات الكيبورد -->
            <div class="bg-white rounded-lg shadow-md p-6 border-r-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">إجمالي ضغطات الكيبورد</p>
                        <p class="text-3xl font-bold text-gray-800">
                            @php
                                $totalKeyboardClicks = 0;
                                if(isset($data['entries']) && is_array($data['entries'])) {
                                    foreach($data['entries'] as $entry) {
                                        $totalKeyboardClicks += $entry['keyboard_clicks'] ?? 0;
                                    }
                                }
                            @endphp
                            {{ $totalKeyboardClicks }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- إجمالي الوقت النشط -->
            <div class="bg-white rounded-lg shadow-md p-6 border-r-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">إجمالي الوقت النشط</p>
                        <p class="text-3xl font-bold text-gray-800">
                            @php
                                $totalActiveSeconds = 0;
                                if(isset($data['entries']) && is_array($data['entries'])) {
                                    foreach($data['entries'] as $entry) {
                                        if(isset($entry['total_active_seconds'])) {
                                            $totalActiveSeconds += $entry['total_active_seconds'];
                                        } elseif(isset($entry['active_seconds'])) {
                                            $totalActiveSeconds += $entry['active_seconds'];
                                        } elseif(isset($entry['active_seconds_in_minute'])) {
                                            $totalActiveSeconds += $entry['active_seconds_in_minute'];
                                        } elseif(isset($entry['status']) && $entry['status'] === 'ACTIVE') {
                                            // افتراض أن كل فترة زمنية مدتها 60 ثانية (دقيقة واحدة)
                                            $totalActiveSeconds += 60;
                                        }
                                    }
                                }

                                // تحويل الثواني إلى صيغة ساعات:دقائق:ثواني
                                $hours = floor($totalActiveSeconds / 3600);
                                $minutes = floor(($totalActiveSeconds % 3600) / 60);
                                $seconds = $totalActiveSeconds % 60;
                                $formattedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                            @endphp
                            {{ $formattedTime }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- نسبة النشاط -->
            <div class="bg-white rounded-lg shadow-md p-6 border-r-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">نسبة النشاط</p>
                        <p class="text-3xl font-bold text-gray-800">
                            @php
                                $activeEntries = 0;
                                $totalEntries = 0;

                                if(isset($data['entries']) && is_array($data['entries'])) {
                                    foreach($data['entries'] as $entry) {
                                        if(isset($entry['status'])) {
                                            $totalEntries++;
                                            if($entry['status'] === 'ACTIVE') {
                                                $activeEntries++;
                                            }
                                        }
                                    }
                                }

                                $activityPercentage = $totalEntries > 0 ? round(($activeEntries / $totalEntries) * 100, 1) : 0;
                            @endphp
                            {{ $activityPercentage }}%
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- حالة الموظف -->
            <div class="bg-white rounded-lg shadow-md p-6 border-r-4 border-indigo-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">حالة الموظف</p>
                        @if(isset($data['employee_status']))
                            @if($data['employee_status']['has_absence'])
                                <p class="text-lg font-bold text-red-600">في إجازة</p>
                                @if(isset($data['employee_status']['status_details']))
                                    @foreach($data['employee_status']['status_details'] as $status)
                                        @if($status['type'] === 'absence' && isset($status['details']))
                                            <p class="text-xs text-gray-500 mt-1">{{ $status['details'] }}</p>
                                        @endif
                                    @endforeach
                                @endif
                            @elseif($data['employee_status']['has_permission'])
                                <p class="text-lg font-bold text-yellow-600">لديه إذن</p>
                                @if(isset($data['employee_status']['status_details']))
                                    @foreach($data['employee_status']['status_details'] as $status)
                                        @if($status['type'] === 'permission' && isset($status['details']))
                                            <p class="text-xs text-gray-500 mt-1">{{ $status['details'] }}</p>
                                        @endif
                                    @endforeach
                                @endif
                            @elseif($data['employee_status']['has_meeting'])
                                <p class="text-lg font-bold text-blue-600">اجتماع معتمد</p>
                                @if(isset($data['employee_status']['status_details']))
                                    @foreach($data['employee_status']['status_details'] as $status)
                                        @if($status['type'] === 'meeting' && isset($status['details']))
                                            <p class="text-xs text-gray-500 mt-1">{{ $status['details'] }}</p>
                                        @endif
                                    @endforeach
                                @endif
                            @else
                                <p class="text-lg font-bold text-green-600">متاح للعمل</p>
                            @endif
                        @else
                            <p class="text-lg font-bold text-gray-600">غير محدد</p>
                        @endif
                    </div>
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                        @if(isset($data['employee_status']))
                            @if($data['employee_status']['has_absence'])
                                <i class="fas fa-calendar-times text-2xl"></i>
                            @elseif($data['employee_status']['has_permission'])
                                <i class="fas fa-door-open text-2xl"></i>
                            @elseif($data['employee_status']['has_meeting'])
                                <i class="fas fa-users text-2xl"></i>
                            @else
                                <i class="fas fa-check-circle text-2xl"></i>
                            @endif
                        @else
                            <i class="fas fa-question-circle text-2xl"></i>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- الرسوم البيانية -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- رسم بياني خطي - نقرات الماوس -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">نقرات الماوس عبر الوقت</h2>
                <div style="height: 300px;">
                    <canvas id="mouseClicksChart"></canvas>
                </div>
            </div>

            <!-- رسم بياني خطي - ضغطات الكيبورد -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">ضغطات الكيبورد عبر الوقت</h2>
                <div style="height: 300px;">
                    <canvas id="keyboardClicksChart"></canvas>
                </div>
            </div>

            <!-- رسم بياني دائري - نشط vs خامل -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">توزيع حالة النشاط</h2>
                <div style="height: 300px;">
                    <canvas id="activityStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- رسم بياني شريطي - الوقت النشط -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">الوقت النشط لكل فترة (بالثواني)</h2>
            <div style="height: 300px;">
                <canvas id="activeSecondsChart"></canvas>
            </div>
        </div>

        <!-- جدول النشاط التفصيلي -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">تفاصيل النشاط الدقيقة</h2>

                <!-- أزرار التصدير في الجدول -->
                @if(isset($data['entries']) && is_array($data['entries']) && count($data['entries']) > 0)
                <div class="flex space-x-2 space-x-reverse">
                    <a href="{{ route('tracking-employee.export.activity.report', ['employee_id' => request('employee_id'), 'date' => request('date', date('Y-m-d'))]) }}" class="text-sm px-3 py-1 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md transition-colors flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        تنزيل التقرير
                    </a>
                </div>
                @endif
            </div>
            <div class="overflow-x-auto">
                @if(isset($data['entries']) && is_array($data['entries']) && count($data['entries']) > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الوقت</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نقرات الماوس</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ضغطات الكيبورد</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الثواني النشطة</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عنوان النافذة</th>
        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($data['entries'] as $index => $entry)
                                <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $entry['time_block'] ?? '--' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if(isset($entry['status']))
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                            {{ $entry['status'] === 'ACTIVE' ? 'bg-green-100 text-green-800' :
                                              ($entry['status'] === 'BREAK' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ $entry['status'] === 'ACTIVE' ? 'نشط' :
                                                  ($entry['status'] === 'BREAK' ? 'استراحة' : 'خامل') }}
                                            </span>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $entry['mouse_clicks'] ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $entry['keyboard_clicks'] ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @php
                                            // حساب الثواني النشطة من خلال مختلف الحقول المتاحة
                                            $activeSeconds = 0;
                                            if(isset($entry['total_active_seconds'])) {
                                                $activeSeconds = $entry['total_active_seconds'];
                                            } elseif(isset($entry['active_seconds'])) {
                                                $activeSeconds = $entry['active_seconds'];
                                            } elseif(isset($entry['active_seconds_in_minute'])) {
                                                $activeSeconds = $entry['active_seconds_in_minute'];
                                            } elseif(isset($entry['status']) && $entry['status'] === 'ACTIVE') {
                                                $activeSeconds = 60;
                                            }

                                            // تنسيق عرض الثواني إلى دقائق:ثواني إذا تجاوزت 60 ثانية
                                            if ($activeSeconds > 60) {
                                                $minutes = floor($activeSeconds / 60);
                                                $seconds = $activeSeconds % 60;
                                                $formattedActiveSeconds = "{$minutes}:{$seconds}";
                                            } else {
                                                $formattedActiveSeconds = $activeSeconds;
                                            }
                                        @endphp
                                        <span title="{{ $activeSeconds }} ثانية">{{ $formattedActiveSeconds }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">
                                        {{ $entry['last_window_title'] ?? '--' }}
                                    </td>
                </tr>
            @endforeach
                        </tbody>
                    </table>
        @else
                    <div class="text-center py-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">لا توجد بيانات</h3>
                        <p class="mt-1 text-sm text-gray-500">لم يتم العثور على بيانات لهذا الموظف في هذا التاريخ.</p>
                    </div>
        @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // تفعيل Select2 للبحث في قائمة الموظفين
            $('select[name="employee_id"]').select2({
                placeholder: '-- ابحث عن موظف --',
                allowClear: true,
                dir: 'rtl',
                language: {
                    noResults: function() {
                        return "لا توجد نتائج";
                    },
                    searching: function() {
                        return "جاري البحث...";
                    }
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // تجهيز البيانات للرسوم البيانية
            const timeLabels = [
                @if(isset($data['entries']) && is_array($data['entries']))
                @foreach($data['entries'] as $entry)
                        "{{ $entry['time_block'] ?? '' }}",
                @endforeach
            @endif
        ];

            const mouseClicksData = [
                @if(isset($data['entries']) && is_array($data['entries']))
                        @foreach($data['entries'] as $entry)
                            {{ $entry['mouse_clicks'] ?? 0 }},
                        @endforeach
                    @endif
            ];

            const keyboardClicksData = [
                @if(isset($data['entries']) && is_array($data['entries']))
                    @foreach($data['entries'] as $entry)
                        {{ $entry['keyboard_clicks'] ?? 0 }},
                    @endforeach
                @endif
            ];

            const activeSecondsData = [
                @if(isset($data['entries']) && is_array($data['entries']))
                    @foreach($data['entries'] as $entry)
                        @php
                            // حساب الثواني النشطة من خلال مختلف الحقول المتاحة - بدون أي قيود
                            $activeSeconds = 0;
                            if(isset($entry['total_active_seconds'])) {
                                $activeSeconds = $entry['total_active_seconds'];
                            } elseif(isset($entry['active_seconds'])) {
                                $activeSeconds = $entry['active_seconds'];
                            } elseif(isset($entry['active_seconds_in_minute'])) {
                                $activeSeconds = $entry['active_seconds_in_minute'];
                            } elseif(isset($entry['status']) && $entry['status'] === 'ACTIVE') {
                                $activeSeconds = 60;
                    }
                @endphp
                        {{ $activeSeconds }},
                    @endforeach
            @endif
        ];

            // حساب بيانات حالة النشاط للرسم البياني الدائري
            @php
                $activeCount = 0;
                $idleCount = 0;
                $breakCount = 0;
                if(isset($data['entries']) && is_array($data['entries'])) {
                    foreach($data['entries'] as $entry) {
                        if(isset($entry['status'])) {
                            if($entry['status'] === 'ACTIVE') {
                                $activeCount++;
                            } elseif($entry['status'] === 'BREAK') {
                                $breakCount++;
                            } else {
                                $idleCount++;
                            }
                        }
                    }
                }
            @endphp

            // ======= رسم بياني خطي - نقرات الماوس =======
            const mouseClicksCtx = document.getElementById('mouseClicksChart').getContext('2d');
            new Chart(mouseClicksCtx, {
                type: 'line',
                data: {
                    labels: timeLabels,
                    datasets: [{
                        label: 'نقرات الماوس',
                        data: mouseClicksData,
                        borderColor: '#3b82f6', // أزرق
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            rtl: true,
                            labels: {
                                font: {
                                    family: 'Tajawal'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            padding: 10,
                            titleFont: {
                                family: 'Tajawal'
                            },
                            bodyFont: {
                                family: 'Tajawal'
                            },
                            rtl: true
                        }
                    }
                }
            });

            // ======= رسم بياني خطي - ضغطات الكيبورد =======
            const keyboardClicksCtx = document.getElementById('keyboardClicksChart').getContext('2d');
            new Chart(keyboardClicksCtx, {
                type: 'line',
                data: {
                    labels: timeLabels,
                    datasets: [{
                        label: 'ضغطات الكيبورد',
                        data: keyboardClicksData,
                        borderColor: '#10b981', // أخضر
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            rtl: true,
                            labels: {
                                font: {
                                    family: 'Tajawal'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            padding: 10,
                            titleFont: {
                                family: 'Tajawal'
                            },
                            bodyFont: {
                                family: 'Tajawal'
                            },
                            rtl: true
                        }
                    }
                }
            });

            // ======= رسم بياني دائري - حالة النشاط =======
            const activityCtx = document.getElementById('activityStatusChart').getContext('2d');
            new Chart(activityCtx, {
            type: 'pie',
            data: {
                    labels: ['نشط', 'خامل', 'استراحة'],
                    datasets: [{
                        data: [{{ $activeCount }}, {{ $idleCount }}, {{ $breakCount }}],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.7)', // أخضر للنشط
                            'rgba(239, 68, 68, 0.7)',  // أحمر للخامل
                            'rgba(245, 158, 11, 0.7)'  // برتقالي للاستراحة
                        ],
                        borderColor: [
                            'rgb(16, 185, 129)',
                            'rgb(239, 68, 68)',
                            'rgb(245, 158, 11)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: true,
                            labels: {
                                font: {
                                    family: 'Tajawal'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            padding: 10,
                            titleFont: {
                                family: 'Tajawal'
                            },
                            bodyFont: {
                                family: 'Tajawal'
                            },
                            rtl: true
                        }
                    }
                }
            });

            // ======= رسم بياني شريطي - الوقت النشط =======
            const activeSecondsCtx = document.getElementById('activeSecondsChart').getContext('2d');
            new Chart(activeSecondsCtx, {
                type: 'bar',
                data: {
                    labels: timeLabels,
                datasets: [{
                        label: 'الوقت النشط (بالثواني)',
                        data: activeSecondsData,
                        backgroundColor: 'rgba(139, 92, 246, 0.7)', // بنفسجي
                        borderColor: 'rgb(139, 92, 246)',
                        borderWidth: 1,
                        borderRadius: 5,
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            // تم حذف الحد الأقصى max لكي يظهر الرسم البياني القيم الحقيقية
                            ticks: {
                                precision: 0
                            },
                            // إضافة عنوان للمحور الرأسي
                            title: {
                                display: true,
                                text: 'الثواني النشطة',
                                font: {
                                    family: 'Tajawal',
                                    size: 14
                                }
                            }
                        },
                        x: {
                            // إضافة عنوان للمحور الأفقي
                            title: {
                                display: true,
                                text: 'الوقت',
                                font: {
                                    family: 'Tajawal',
                                    size: 14
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            rtl: true,
                            labels: {
                                font: {
                                    family: 'Tajawal'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            padding: 10,
                            titleFont: {
                                family: 'Tajawal'
                            },
                            bodyFont: {
                                family: 'Tajawal'
                            },
                            rtl: true,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        const hours = Math.floor(context.parsed.y / 3600);
                                        const minutes = Math.floor((context.parsed.y % 3600) / 60);
                                        const seconds = context.parsed.y % 60;

                                        if (hours > 0) {
                                            label += `${hours} ساعة و ${minutes} دقيقة و ${seconds} ثانية`;
                                        } else if (minutes > 0) {
                                            label += `${minutes} دقيقة و ${seconds} ثانية`;
                                        } else {
                                            label += `${seconds} ثانية`;
                                        }
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
