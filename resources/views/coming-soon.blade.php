<x-guest-layout>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden py-6 sm:py-12 bg-gray-50">
        <div class="max-w-xl px-5 text-center">
            <h2 class="mb-2 text-[42px] font-bold text-zinc-800">قريباً</h2>
            <div class="mb-2 h-1 w-16 bg-indigo-600 mx-auto"></div>
            <p class="mb-10 text-lg text-zinc-500">نحن نعمل على هذه الصفحة حالياً، وسيتم إطلاقها قريباً.</p>
            <a href="{{ route('dashboard') }}" class="mt-3 inline-block w-96 rounded bg-indigo-600 px-5 py-3 font-medium text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-700">
                العودة إلى لوحة التحكم
            </a>
        </div>
    </div>
</x-guest-layout>
