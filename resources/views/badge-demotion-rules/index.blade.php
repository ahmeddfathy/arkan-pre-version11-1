@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <!-- Header -->
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">قواعد هبوط الشارات</h2>
                        <p class="mt-1 text-sm text-gray-600">إدارة قواعد الهبوط بين الشارات</p>
                    </div>
                    <a href="{{ route('badge-demotion-rules.create') }}"
                       class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        إضافة قاعدة جديدة
                    </a>
                </div>
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

                @if($demotionRules->count() > 0)
                    <div class="space-y-6">
                        @foreach($demotionRules as $rule)
                            <div class="border border-gray-200 rounded-lg p-6 {{ $rule->is_active ? 'bg-white' : 'bg-gray-50' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-6 space-x-reverse">
                                        <!-- From Badge -->
                                        <div class="flex items-center space-x-3 space-x-reverse">
                                            @if($rule->fromBadge->icon)
                                                <img src="{{ asset('storage/' . $rule->fromBadge->icon) }}"
                                                     alt="{{ $rule->fromBadge->name }}"
                                                     class="h-12 w-12 rounded-full">
                                            @else
                                                <div class="h-12 w-12 rounded-full flex items-center justify-center text-white"
                                                     style="background-color: {{ $rule->fromBadge->color_code ?? '#6B7280' }};">
                                                    <i class="fas fa-medal"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-900">{{ $rule->fromBadge->name }}</h3>
                                                <p class="text-sm text-gray-500">مستوى {{ $rule->fromBadge->level }} - {{ number_format($rule->fromBadge->required_points) }} نقطة</p>
                                            </div>
                                        </div>

                                        <!-- Arrow -->
                                        <div class="text-gray-400 text-2xl">
                                            <i class="fas fa-arrow-left"></i>
                                        </div>

                                        <!-- To Badge -->
                                        <div class="flex items-center space-x-3 space-x-reverse">
                                            @if($rule->toBadge->icon)
                                                <img src="{{ asset('storage/' . $rule->toBadge->icon) }}"
                                                     alt="{{ $rule->toBadge->name }}"
                                                     class="h-12 w-12 rounded-full">
                                            @else
                                                <div class="h-12 w-12 rounded-full flex items-center justify-center text-white"
                                                     style="background-color: {{ $rule->toBadge->color_code ?? '#6B7280' }};">
                                                    <i class="fas fa-medal"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-900">{{ $rule->toBadge->name }}</h3>
                                                <p class="text-sm text-gray-500">مستوى {{ $rule->toBadge->level }} - {{ number_format($rule->toBadge->required_points) }} نقطة</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status and Actions -->
                                    <div class="flex items-center space-x-4 space-x-reverse">
                                        @if($rule->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                نشطة
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                غير نشطة
                                            </span>
                                        @endif

                                        <div class="flex space-x-2 space-x-reverse">
                                            <a href="{{ route('badge-demotion-rules.show', $rule) }}"
                                               class="text-indigo-600 hover:text-indigo-900">عرض</a>
                                            <a href="{{ route('badge-demotion-rules.edit', $rule) }}"
                                               class="text-green-600 hover:text-green-900">تعديل</a>
                                            <form method="POST" action="{{ route('badge-demotion-rules.toggle-active', $rule) }}"
                                                  class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="text-blue-600 hover:text-blue-900">
                                                    {{ $rule->is_active ? 'إلغاء التفعيل' : 'تفعيل' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('badge-demotion-rules.destroy', $rule) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('هل أنت متأكد من حذف هذه القاعدة؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-red-600 hover:text-red-900">
                                                    حذف
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rule Details -->
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-blue-50 rounded-lg p-4">
                                        <div class="text-sm font-medium text-gray-500">مستويات الهبوط</div>
                                        <div class="text-2xl font-bold text-blue-600">{{ $rule->demotion_levels }}</div>
                                    </div>

                                    <div class="bg-green-50 rounded-lg p-4">
                                        <div class="text-sm font-medium text-gray-500">النقاط المحتفظ بها</div>
                                        <div class="text-2xl font-bold text-green-600">{{ $rule->points_percentage_retained }}%</div>
                                    </div>

                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="text-sm font-medium text-gray-500">تاريخ الإنشاء</div>
                                        <div class="text-sm text-gray-900">{{ $rule->created_at->format('Y-m-d H:i') }}</div>
                                    </div>
                                </div>

                                @if($rule->description)
                                    <div class="mt-4">
                                        <div class="text-sm font-medium text-gray-500 mb-1">الوصف</div>
                                        <p class="text-sm text-gray-700">{{ $rule->description }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $demotionRules->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">
                            <i class="fas fa-level-down-alt"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">لا توجد قواعد هبوط</h3>
                        <p class="text-gray-500 mb-6">ابدأ بإضافة أول قاعدة هبوط للشارات</p>
                        <a href="{{ route('badge-demotion-rules.create') }}"
                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            إضافة قاعدة جديدة
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
