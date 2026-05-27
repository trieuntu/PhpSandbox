@extends('layouts.admin')
@section('title', isset($announcement) ? 'Sửa thông báo' : 'Tạo thông báo')
@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-500 hover:text-blue-600">← Danh sách thông báo</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">
            {{ isset($announcement) ? 'Sửa thông báo' : 'Tạo thông báo mới' }}
        </h1>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form method="POST"
            action="{{ isset($announcement) ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}">
            @csrf
            @if(isset($announcement)) @method('PUT') @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $announcement->title ?? '') }}" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-400 @enderror">
                    @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung <span class="text-red-500">*</span></label>
                    <textarea name="content" rows="8" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('content') border-red-400 @enderror">{{ old('content', $announcement->content ?? '') }}</textarea>
                    @error('content')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gửi đến lớp</label>
                    <select name="class_id"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Toàn bộ sinh viên</option>
                        @foreach($classes as $class)
                        <option value="{{ $class->id }}"
                            {{ old('class_id', $announcement->class_id ?? null) == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Chọn lớp cụ thể hoặc để trống để gửi cho tất cả</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_pinned" id="is_pinned" value="1"
                        {{ old('is_pinned', $announcement->is_pinned ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="is_pinned" class="flex items-center gap-1 text-sm text-gray-700 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-orange-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H7.5m9 0a2.25 2.25 0 012.25 2.25v13.5a2.25 2.25 0 01-2.25 2.25H7.5a2.25 2.25 0 01-2.25-2.25V6a2.25 2.25 0 012.25-2.25m9 0h-9" /></svg>
                        Ghim thông báo này lên đầu
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">
                    {{ isset($announcement) ? 'Lưu thay đổi' : 'Đăng thông báo' }}
                </button>
                <a href="{{ route('admin.announcements.index') }}" class="border border-gray-300 text-gray-600 px-6 py-2 rounded hover:bg-gray-50 text-sm">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
