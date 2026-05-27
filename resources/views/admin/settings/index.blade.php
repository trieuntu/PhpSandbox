@extends('layouts.admin')
@section('title', 'Cài đặt hệ thống')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Cài đặt hệ thống</h1>
    <p class="text-gray-500 text-sm mt-1">Cấu hình các thông số cho PHP Sandbox Learning System</p>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf @method('PUT')
    <div class="space-y-6">

        <!-- General Settings -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="flex items-center gap-2 font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Chung
            </h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Cho phép đăng ký tài khoản mới</label>
                        <p class="text-xs text-gray-500 mt-0.5">Sinh viên có thể tự đăng ký khi tính năng này được bật</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="registration_enabled" value="1"
                            {{ ($settings['registration_enabled'] ?? false) ? 'checked' : '' }}
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Inactivity Settings -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="flex items-center gap-2 font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Hết phiên do không hoạt động
            </h2>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Thời gian hết phiên (phút)
                        </label>
                        <input type="number" name="session_timeout_minutes" min="5" max="480"
                            value="{{ old('session_timeout_minutes', $settings['session_timeout_minutes'] ?? 30) }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Sau bao nhiêu phút không hoạt động sẽ đăng xuất tự động</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Thời gian cảnh báo trước (giây)
                        </label>
                        <input type="number" name="inactivity_warning_seconds" min="30" max="300"
                            value="{{ old('inactivity_warning_seconds', $settings['inactivity_warning_seconds'] ?? 120) }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Hiển thị cảnh báo trước khi hết phiên bao nhiêu giây</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sandbox Settings -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">🐳 Cấu hình Sandbox</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Timeout thực thi code (giây)</label>
                        <input type="number" name="sandbox_timeout_seconds" min="1" max="60"
                            value="{{ old('sandbox_timeout_seconds', $settings['sandbox_timeout_seconds'] ?? 10) }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Giới hạn bộ nhớ (MB)</label>
                        <input type="number" name="sandbox_memory_mb" min="8" max="512"
                            value="{{ old('sandbox_memory_mb', $settings['sandbox_memory_mb'] ?? 64) }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sandbox Service URL</label>
                    <input type="url" name="sandbox_service_url"
                        value="{{ old('sandbox_service_url', $settings['sandbox_service_url'] ?? config('services.sandbox.url', '')) }}"
                        placeholder="http://sandbox-service:8080"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">URL của PHP Sandbox execution service</p>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex gap-3">
            <button type="submit" class="flex items-center gap-2 bg-blue-600 text-white px-8 py-2 rounded hover:bg-blue-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                Lưu cài đặt
            </button>
        </div>
    </div>
</form>
@endsection
