@extends('layouts.admin')
@section('title', isset($exam) ? 'Sửa kỳ thi' : 'Tạo kỳ thi')
@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.exams.index') }}" class="text-sm text-gray-500 hover:text-blue-600">← Danh sách kỳ thi</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">
            {{ isset($exam) ? 'Sửa: ' . $exam->title : 'Tạo kỳ thi mới' }}
        </h1>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form method="POST"
            action="{{ isset($exam) ? route('admin.exams.update', $exam) : route('admin.exams.store') }}">
            @csrf
            @if(isset($exam)) @method('PUT') @endif
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề kỳ thi <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $exam->title ?? '') }}" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-400 @enderror">
                    @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lớp học <span class="text-red-500">*</span></label>
                    <select name="class_id" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('class_id') border-red-400 @enderror">
                        <option value="">-- Chọn lớp --</option>
                        @foreach($classes as $class)
                        <option value="{{ $class->id }}"
                            {{ old('class_id', isset($exam) ? $exam->class_id : request('class_id')) == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('class_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bắt đầu <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="starts_at"
                            value="{{ old('starts_at', isset($exam) ? $exam->starts_at->format('Y-m-d\TH:i') : '') }}" required
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('starts_at') border-red-400 @enderror">
                        @error('starts_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kết thúc <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="ends_at"
                            value="{{ old('ends_at', isset($exam) ? $exam->ends_at->format('Y-m-d\TH:i') : '') }}" required
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('ends_at') border-red-400 @enderror">
                        @error('ends_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian làm bài (phút)</label>
                    <input type="number" name="time_limit_minutes" min="1"
                        value="{{ old('time_limit_minutes', $exam->time_limit_minutes ?? '') }}"
                        placeholder="Để trống nếu không giới hạn"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Tính từ khi sinh viên bắt đầu làm bài</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Đề thi / Hướng dẫn</label>
                    <textarea name="instructions" rows="6"
                        placeholder="Nhập đề bài, yêu cầu, hướng dẫn cho sinh viên..."
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('instructions', $exam->instructions ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-orange-500 text-white px-6 py-2 rounded hover:bg-orange-600 text-sm">
                    {{ isset($exam) ? 'Lưu thay đổi' : 'Tạo kỳ thi' }}
                </button>
                <a href="{{ route('admin.exams.index') }}" class="border border-gray-300 text-gray-600 px-6 py-2 rounded hover:bg-gray-50 text-sm">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
