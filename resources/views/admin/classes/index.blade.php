@extends('layouts.admin')
@section('title', 'Quản lý lớp học')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Lớp học</h1>
        <p class="text-gray-500 text-sm mt-1">Quản lý các lớp học trong hệ thống</p>
    </div>
    <a href="{{ route('admin.classes.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
        + Tạo lớp học
    </a>
</div>

<!-- Search -->
<form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-4 flex gap-3">
    <input type="text" name="search" value="{{ request('search') }}"
        placeholder="Tìm tên lớp, mã lớp..."
        class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-sm">Tìm kiếm</button>
    @if(request('search'))
        <a href="{{ route('admin.classes.index') }}" class="border border-gray-300 text-gray-600 px-4 py-2 rounded text-sm hover:bg-gray-50">Xóa</a>
    @endif
</form>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tên lớp</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Mã lớp</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Sinh viên</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Bài tập</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Kỳ thi</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tạo lúc</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($classes as $class)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-medium text-gray-800">
                    {{ $class->name }}
                    @if(!$class->is_active)
                        <span class="ml-1 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Ẩn</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $class->code ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $class->students_count ?? 0 }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $class->assignments_count ?? 0 }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $class->exams_count ?? 0 }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $class->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3">
                    <div class="flex gap-3 justify-end items-center">
                        <a href="{{ route('admin.classes.show', $class) }}" class="text-xs text-gray-600 hover:underline">Xem</a>
                        <a href="{{ route('admin.classes.edit', $class) }}" class="text-xs text-blue-600 hover:underline">Sửa</a>
                        <form method="POST" action="{{ route('admin.classes.toggle', $class) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" title="{{ $class->is_active ? 'Ẩn lớp' : 'Kích hoạt lớp' }}"
                                class="text-xs {{ $class->is_active ? 'text-green-600 hover:text-red-600' : 'text-gray-400 hover:text-green-600' }}">
                                @if($class->is_active)
                                    <svg class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                @else
                                    <svg class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                @endif
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.classes.destroy', $class) }}"
                            onsubmit="return confirm('Xóa lớp {{ addslashes($class->name) }}?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">Không có lớp học nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $classes->withQueryString()->links() }}</div>
@endsection
