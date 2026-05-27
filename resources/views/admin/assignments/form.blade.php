@extends('layouts.admin')
@section('title', isset($assignment) ? 'Sửa bài tập' : 'Tạo bài tập')
@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.classes.show', $class) }}" class="text-sm text-gray-500 hover:text-blue-600">← {{ $class->name }}</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">
            {{ isset($assignment) ? 'Sửa: ' . $assignment->title : 'Tạo bài tập mới' }}
        </h1>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form method="POST"
            action="{{ isset($assignment)
                ? route('admin.assignments.update', [$class, $assignment])
                : route('admin.assignments.store', $class) }}">
            @csrf
            @if(isset($assignment)) @method('PUT') @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lớp học</label>
                    <p class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded px-3 py-2">{{ $class->name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $assignment->title ?? '') }}" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-400 @enderror">
                    @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                    <textarea name="description" rows="2"
                        placeholder="Mô tả ngắn về bài tập..."
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $assignment->description ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hướng dẫn / Đề bài</label>
                    <textarea name="instructions" rows="8"
                        placeholder="Nhập mô tả bài tập, yêu cầu, ví dụ..."
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">{{ old('instructions', $assignment->instructions ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hạn nộp</label>
                    <input type="datetime-local" name="due_at"
                        value="{{ old('due_at', isset($assignment) && $assignment->due_at ? $assignment->due_at->format('Y-m-d\TH:i') : '') }}"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Để trống nếu không có hạn nộp</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', $assignment->is_active ?? true) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-blue-600">
                    <label for="is_active" class="text-sm text-gray-700">Kích hoạt bài tập</label>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">
                    {{ isset($assignment) ? 'Lưu thay đổi' : 'Tạo bài tập' }}
                </button>
                <a href="{{ route('admin.classes.show', $class) }}" class="border border-gray-300 text-gray-600 px-6 py-2 rounded hover:bg-gray-50 text-sm">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
