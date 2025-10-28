@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <!-- Header -->
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">إضافة شارة جديدة</h2>
                        <p class="mt-1 text-sm text-gray-600">إنشاء شارة جديدة في النظام</p>
                    </div>
                    <a href="{{ route('badges.index') }}"
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        العودة للقائمة
                    </a>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('badges.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- اسم الشارة -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                اسم الشارة <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="أدخل اسم الشارة"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- المستوى -->
                        <div>
                            <label for="level" class="block text-sm font-medium text-gray-700 mb-2">
                                المستوى <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   name="level"
                                   id="level"
                                   value="{{ old('level') }}"
                                   min="1"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="1"
                                   required>
                            @error('level')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- النقاط المطلوبة -->
                        <div>
                            <label for="required_points" class="block text-sm font-medium text-gray-700 mb-2">
                                النقاط المطلوبة <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   name="required_points"
                                   id="required_points"
                                   value="{{ old('required_points') }}"
                                   min="0"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="100"
                                   required>
                            @error('required_points')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- لون الشارة -->
                        <div>
                            <label for="color_code" class="block text-sm font-medium text-gray-700 mb-2">
                                لون الشارة
                            </label>
                            <input type="color"
                                   name="color_code"
                                   id="color_code"
                                   value="{{ old('color_code', '#6B7280') }}"
                                   class="mt-1 block w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('color_code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- أيقونة الشارة -->
                        <div class="md:col-span-2">
                            <label for="icon" class="block text-sm font-medium text-gray-700 mb-2">
                                أيقونة الشارة
                            </label>
                            <input type="file"
                                   name="icon"
                                   id="icon"
                                   accept="image/*"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500">اختر صورة للشارة (PNG, JPG, GIF, SVG)</p>
                            @error('icon')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- وصف الشارة -->
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                وصف الشارة <span class="text-red-500">*</span>
                            </label>
                            <textarea name="description"
                                      id="description"
                                      rows="4"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="أدخل وصف الشارة وشروط الحصول عليها"
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- أزرار التحكم -->
                    <div class="mt-8 flex justify-end space-x-3 space-x-reverse">
                        <a href="{{ route('badges.index') }}"
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            إلغاء
                        </a>
                        <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            إنشاء الشارة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
