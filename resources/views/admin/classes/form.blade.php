@extends('layouts.admin')
@section('title', isset($class) ? 'Sửa lớp học' : 'Tạo lớp học')
@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.classes.index') }}" class="text-sm text-gray-500 hover:text-blue-600">← Danh sách lớp học</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">
            {{ isset($class) ? 'Sửa: ' . $class->name : 'Tạo lớp học mới' }}
        </h1>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form method="POST"
            action="{{ isset($class) ? route('admin.classes.update', $class) : route('admin.classes.store') }}">
            @csrf
            @if(isset($class)) @method('PUT') @endif
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên lớp <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $class->name ?? '') }}" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                    <textarea name="description" rows="3"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $class->description ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sinh viên trong lớp</label>
                    <div class="border border-gray-300 rounded p-3 max-h-48 overflow-y-auto space-y-1">
                        @foreach($allStudents as $student)
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 px-1 py-0.5 rounded">
                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                {{ in_array($student->id, old('student_ids', isset($class) ? $class->students->pluck('id')->toArray() : [])) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">{{ $student->name }}</span>
                            <span class="text-xs text-gray-400 font-mono">{{ $student->student_id }}</span>
                        </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ $allStudents->count() }} sinh viên</p>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm">
                    {{ isset($class) ? 'Lưu thay đổi' : 'Tạo lớp học' }}
                </button>
                <a href="{{ route('admin.classes.index') }}" class="border border-gray-300 text-gray-600 px-6 py-2 rounded hover:bg-gray-50 text-sm">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
