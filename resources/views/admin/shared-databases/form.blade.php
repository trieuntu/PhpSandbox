@extends('layouts.admin')
@section('title', $sharedDb ? 'Chỉnh sửa Database' : 'Tạo Shared Database')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.shared-databases.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">
            {{ $sharedDb ? 'Chỉnh sửa: ' . $sharedDb->display_name : 'Tạo Shared Database mới' }}
        </h1>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST"
              action="{{ $sharedDb ? route('admin.shared-databases.update', $sharedDb) : route('admin.shared-databases.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if($sharedDb)
                @method('PUT')
            @endif

            {{-- Slug (immutable after creation) --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Slug <span class="text-red-500">*</span>
                    <span class="text-gray-400 font-normal">(chỉ a–z, 0–9, _; không thay đổi được sau khi tạo)</span>
                </label>
                @if($sharedDb)
                    <div class="flex items-center gap-2">
                        <code class="bg-gray-100 px-3 py-2 rounded-lg text-sm text-gray-700 border border-gray-200 flex-1">
                            sandbox_shared_{{ $sharedDb->slug }}
                        </code>
                        <input type="hidden" name="slug" value="{{ $sharedDb->slug }}">
                    </div>
                @else
                    <div class="flex items-center gap-1">
                        <span class="text-gray-400 text-sm whitespace-nowrap bg-gray-50 border border-r-0 border-gray-300 px-3 py-2 rounded-l-lg">sandbox_shared_</span>
                        <input type="text" name="slug" value="{{ old('slug') }}"
                               class="flex-1 border border-gray-300 rounded-r-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('slug') border-red-400 @enderror"
                               placeholder="shop" pattern="[a-z0-9_]{2,32}" required>
                    </div>
                    @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                @endif
            </div>

            {{-- Display name --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tên hiển thị <span class="text-red-500">*</span>
                </label>
                <input type="text" name="display_name" value="{{ old('display_name', $sharedDb?->display_name) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('display_name') border-red-400 @enderror"
                       placeholder="Cửa hàng trực tuyến (Shop DB)" required>
                @error('display_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Mô tả ngắn về database này…">{{ old('description', $sharedDb?->description) }}</textarea>
            </div>

            {{-- Permission --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Quyền truy cập sinh viên <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['none' => ['label'=>'Tắt','desc'=>'Không inject vào sandbox','color'=>'gray'],
                               'readonly' => ['label'=>'Chỉ đọc','desc'=>'SELECT; không thể ghi','color'=>'blue'],
                               'readwrite' => ['label'=>'Đọc & Ghi','desc'=>'SELECT, INSERT, UPDATE, DELETE','color'=>'green']] as $val => $opt)
                    <label class="relative flex cursor-pointer rounded-lg border p-3 shadow-sm focus:outline-none
                        {{ old('permission', $sharedDb?->permission ?? 'readonly') === $val
                            ? ($opt['color'] === 'blue' ? 'border-blue-500 bg-blue-50' : ($opt['color'] === 'green' ? 'border-green-500 bg-green-50' : 'border-gray-400 bg-gray-50'))
                            : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" name="permission" value="{{ $val }}" class="sr-only"
                               {{ old('permission', $sharedDb?->permission ?? 'readonly') === $val ? 'checked' : '' }}>
                        <div>
                            <span class="block text-sm font-medium text-gray-900">{{ $opt['label'] }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">{{ $opt['desc'] }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('permission')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- SQL File --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    File SQL
                    @if(!$sharedDb) <span class="text-red-500">*</span> @else <span class="text-gray-400 font-normal">(bỏ trống để giữ dữ liệu cũ)</span> @endif
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg px-6 py-6 text-center hover:border-blue-400 transition-colors">
                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <p class="text-sm text-gray-500 mb-2">Kéo thả hoặc chọn file .sql / .txt</p>
                    <p class="text-xs text-gray-400 mb-3">Tối đa 20 MB</p>
                    <input type="file" name="sql_file" accept=".sql,.txt" {{ !$sharedDb ? 'required' : '' }}
                           class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                @error('sql_file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.shared-databases.index') }}"
                   class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Hủy
                </a>
                <button type="submit"
                        class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    {{ $sharedDb ? 'Lưu thay đổi' : 'Tạo database' }}
                </button>
            </div>
        </form>
    </div>

    {{-- Hint box --}}
    <div class="mt-4 bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-700">
        <p class="font-medium mb-1">Lưu ý bảo mật SQL</p>
        <p>File SQL chỉ được phép chứa DDL (CREATE TABLE, INSERT, …). Các lệnh như <code>DROP DATABASE</code>, <code>GRANT</code>, <code>CREATE USER</code> sẽ bị từ chối.</p>
    </div>
</div>

<script>
// Highlight permission radio on click
document.querySelectorAll('[name="permission"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('[name="permission"]').forEach(r => {
            r.closest('label').className = r.closest('label').className
                .replace(/border-(blue|green|gray)-500/g, 'border-gray-200')
                .replace(/bg-(blue|green|gray)-50/g, '');
        });
        const colors = {none:'gray',readonly:'blue',readwrite:'green'};
        const c = colors[radio.value];
        const sel = radio.closest('label');
        sel.classList.remove('border-gray-200');
        sel.classList.add(`border-${c}-500`, `bg-${c}-50`);
    });
});
</script>
@endsection
