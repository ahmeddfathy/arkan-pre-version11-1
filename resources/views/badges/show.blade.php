@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <!-- Header -->
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        @if($badge->icon)
                            <img src="{{ asset('storage/' . $badge->icon) }}"
                                 alt="{{ $badge->name }}"
                                 class="h-16 w-16 rounded-full">
                        @else
                            <div class="h-16 w-16 rounded-full flex items-center justify-center text-white text-2xl"
                                 style="background-color: {{ $badge->color_code ?? '#6B7280' }};">
                                <i class="fas fa-medal"></i>
                            </div>
                        @endif
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900">{{ $badge->name }}</h2>
                            <p class="mt-1 text-sm text-gray-600">تفاصيل الشارة</p>
                        </div>
                    </div>
                    <div class="flex space-x-2 space-x-reverse">
                        <a href="{{ route('badges.edit', $badge) }}"
                           class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            تعديل
                        </a>
                        <a href="{{ route('badges.index') }}"
                           class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            العودة للقائمة
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- معلومات الشارة -->
                    <div class="lg:col-span-2">
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">معلومات الشارة</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">الاسم</label>
                                    <p class="mt-1 text-lg text-gray-900">{{ $badge->name }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">المستوى</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            مستوى {{ $badge->level }}
                                        </span>
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">النقاط المطلوبة</label>
                                    <p class="mt-1 text-lg text-gray-900">{{ number_format($badge->required_points) }} نقطة</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">لون الشارة</label>
                                    <div class="mt-1 flex items-center space-x-2 space-x-reverse">
                                        <div class="h-6 w-6 rounded-full border"
                                             style="background-color: {{ $badge->color_code ?? '#6B7280' }};"></div>
                                        <span class="text-sm text-gray-900">{{ $badge->color_code ?? 'غير محدد' }}</span>
                                    </div>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-500">الوصف</label>
                                    <p class="mt-1 text-gray-900">{{ $badge->description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- قواعد الهبوط -->
                        @if($badge->demotionRules->count() > 0)
                            <div class="mt-8 bg-yellow-50 rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">قواعد الهبوط من هذه الشارة</h3>

                                <div class="space-y-4">
                                    @foreach($badge->demotionRules as $rule)
                                        <div class="border border-yellow-200 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3 space-x-reverse">
                                                    @if($rule->toBadge->icon)
                                                        <img src="{{ asset('storage/' . $rule->toBadge->icon) }}"
                                                             alt="{{ $rule->toBadge->name }}"
                                                             class="h-8 w-8 rounded-full">
                                                    @else
                                                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-white text-sm"
                                                             style="background-color: {{ $rule->toBadge->color_code ?? '#6B7280' }};">
                                                            <i class="fas fa-medal"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <p class="font-medium text-gray-900">{{ $rule->toBadge->name }}</p>
                                                        <p class="text-sm text-gray-500">مستوى {{ $rule->toBadge->level }}</p>
                                                    </div>
                                                </div>
                                                <div class="text-left">
                                                    <p class="text-sm text-gray-600">يتم الاحتفاظ بـ {{ $rule->points_percentage_retained }}% من النقاط</p>
                                                    <p class="text-xs text-gray-500">بعد {{ $rule->demotion_levels }} مستوى من الهبوط</p>
                                                </div>
                                            </div>
                                            @if($rule->description)
                                                <p class="mt-2 text-sm text-gray-600">{{ $rule->description }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- إحصائيات الشارة -->
                    <div class="lg:col-span-1">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">إحصائيات الشارة</h3>

                            <div class="space-y-4">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-blue-600">{{ $badge->users->count() }}</div>
                                    <div class="text-sm text-gray-600">إجمالي المستخدمين</div>
                                </div>

                                <div class="text-center">
                                    <div class="text-3xl font-bold text-green-600">{{ $badge->users()->wherePivot('is_active', true)->count() }}</div>
                                    <div class="text-sm text-gray-600">مستخدمين نشطين</div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <a href="{{ route('badges.users', $badge) }}"
                                   class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center block">
                                    عرض المستخدمين
                                </a>
                            </div>
                        </div>

                        <!-- المستخدمين الحديثين -->
                        @if($badge->users->count() > 0)
                            <div class="mt-6 bg-gray-50 rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">أحدث المستخدمين</h3>

                                <div class="space-y-3">
                                    @foreach($badge->users()->latest('user_badges.earned_at')->limit(5)->get() as $user)
                                        <div class="flex items-center space-x-3 space-x-reverse">
                                            @if($user->profile_photo_path)
                                                <img src="{{ asset('storage/' . $user->profile_photo_path) }}"
                                                     alt="{{ $user->name }}"
                                                     class="h-8 w-8 rounded-full">
                                            @else
                                                <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-700">{{ substr($user->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                                <p class="text-xs text-gray-500">{{ $user->pivot->earned_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
