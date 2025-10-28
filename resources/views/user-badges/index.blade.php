@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <!-- Header -->
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">شارات المستخدمين</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            <i class="fas fa-magic text-blue-500"></i> الشارات تُمنح تلقائياً عند إكمال المهام وحسب النقاط المكتسبة
                        </p>
                    </div>
                    <div class="text-sm text-gray-600 flex items-center">
                        <i class="fas fa-robot text-green-500 mr-1"></i>
                        نظام تلقائي 100%
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <form method="GET" action="{{ route('user-badges.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">المستخدم</label>
                        <select name="user_id" id="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">كل المستخدمين</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="badge_id" class="block text-sm font-medium text-gray-700 mb-1">الشارة</label>
                        <select name="badge_id" id="badge_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">كل الشارات</option>
                            @foreach($badges as $badge)
                                <option value="{{ $badge->id }}" {{ request('badge_id') == $badge->id ? 'selected' : '' }}>
                                    {{ $badge->name }} (مستوى {{ $badge->level }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="season_id" class="block text-sm font-medium text-gray-700 mb-1">الموسم</label>
                        <select name="season_id" id="season_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">كل المواسم</option>
                            @foreach($seasons as $season)
                                <option value="{{ $season->id }}" {{ request('season_id') == $season->id ? 'selected' : '' }}>
                                    {{ $season->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
                        <select name="is_active" id="is_active" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">كل الحالات</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشطة</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>غير نشطة</option>
                        </select>
                    </div>

                    <div class="md:col-span-4 flex justify-end space-x-2 space-x-reverse">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            تطبيق الفلتر
                        </button>
                        <a href="{{ route('user-badges.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            إزالة الفلتر
                        </a>
                    </div>
                </form>
            </div>

            <!-- Content -->
            <div class="p-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                @if($userBadges->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستخدم</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الشارة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الموسم</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النقاط المكتسبة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الحصول</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($userBadges as $userBadge)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-3 space-x-reverse">
                                                @if($userBadge->user->profile_photo_path)
                                                    <img src="{{ asset('storage/' . $userBadge->user->profile_photo_path) }}"
                                                         alt="{{ $userBadge->user->name }}"
                                                         class="h-8 w-8 rounded-full">
                                                @else
                                                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-gray-700">{{ substr($userBadge->user->name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $userBadge->user->name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-3 space-x-reverse">
                                                @if($userBadge->badge->icon)
                                                    <img src="{{ asset('storage/' . $userBadge->badge->icon) }}"
                                                         alt="{{ $userBadge->badge->name }}"
                                                         class="h-8 w-8 rounded-full">
                                                @else
                                                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-white text-sm"
                                                         style="background-color: {{ $userBadge->badge->color_code ?? '#6B7280' }};">
                                                        <i class="fas fa-medal"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $userBadge->badge->name }}</div>
                                                    <div class="text-sm text-gray-500">مستوى {{ $userBadge->badge->level }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $userBadge->season->name ?? 'غير محدد' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($userBadge->points_earned) }} نقطة
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $userBadge->earned_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($userBadge->is_active)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    نشطة
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    غير نشطة
                                                </span>
                                            @endif
                                        </td>
                                                                                 <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                             <div class="flex space-x-2 space-x-reverse">
                                                 <a href="{{ route('user-badges.show', $userBadge) }}"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-eye"></i> عرض
                                                 </a>
                                                 <span class="text-green-600 text-xs">
                                                     <i class="fas fa-robot"></i> تلقائي
                                                 </span>
                                             </div>
                                         </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $userBadges->appends(request()->query())->links() }}
                    </div>
                @else
                                        <div class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">لا توجد شارات بعد</h3>
                        <p class="text-gray-500 mb-6">
                            الشارات ستُمنح تلقائياً للمستخدمين عند إكمالهم للمهام وحسب النقاط المكتسبة
                        </p>
                        <div class="text-blue-600">
                            <i class="fas fa-magic"></i> النظام يعمل تلقائياً - لا حاجة لتدخل يدوي
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
