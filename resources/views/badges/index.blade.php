@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <!-- Header -->
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">الشارات</h2>
                        <p class="mt-1 text-sm text-gray-600">إدارة شارات النظام</p>
                    </div>
                    <a href="{{ route('badges.create') }}"
                       class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        إضافة شارة جديدة
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

                @if($badges->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الأيقونة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الاسم</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الوصف</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النقاط المطلوبة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستوى</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($badges as $badge)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($badge->icon)
                                                <img src="{{ asset('storage/' . $badge->icon) }}"
                                                     alt="{{ $badge->name }}"
                                                     class="h-10 w-10 rounded-full">
                                            @else
                                                <div class="h-10 w-10 rounded-full flex items-center justify-center text-white"
                                                     style="background-color: {{ $badge->color_code ?? '#6B7280' }};">
                                                    <i class="fas fa-medal"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $badge->name }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">{{ Str::limit($badge->description, 50) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($badge->required_points) }} نقطة
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                مستوى {{ $badge->level }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2 space-x-reverse">
                                                <a href="{{ route('badges.show', $badge) }}"
                                                   class="text-indigo-600 hover:text-indigo-900">عرض</a>
                                                <a href="{{ route('badges.edit', $badge) }}"
                                                   class="text-green-600 hover:text-green-900">تعديل</a>
                                                <form method="POST" action="{{ route('badges.destroy', $badge) }}"
                                                      class="inline"
                                                      onsubmit="return confirm('هل أنت متأكد من حذف هذه الشارة؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-900">
                                                        حذف
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $badges->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">
                            <i class="fas fa-medal"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">لا توجد شارات</h3>
                        <p class="text-gray-500 mb-6">ابدأ بإضافة أول شارة في النظام</p>
                        <a href="{{ route('badges.create') }}"
                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            إضافة شارة جديدة
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
