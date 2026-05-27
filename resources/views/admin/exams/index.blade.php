@extends('layouts.admin')
@section('title', 'Quản lý kỳ thi')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Kỳ thi</h1>
        <p class="text-gray-500 text-sm mt-1">Quản lý tất cả kỳ thi trong hệ thống</p>
    </div>
    <a href="{{ route('admin.exams.create') }}"
       class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 text-sm">
        + Tạo kỳ thi
    </a>
</div>

<!-- Filter -->
<form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-4 flex gap-3">
    <input type="text" name="search" value="{{ request('search') }}"
        placeholder="Tìm tên kỳ thi..."
        class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <select name="status" class="border border-gray-300 rounded px-3 py-2 text-sm">
        <option value="">Tất cả trạng thái</option>
        <option value="upcoming" {{ request('status') === 'upcoming' ? 'selected' : '' }}>Sắp diễn ra</option>
        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Đang mở</option>
        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Đã đóng</option>
    </select>
    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-sm">Lọc</button>
    @if(request()->hasAny(['search', 'status']))
        <a href="{{ route('admin.exams.index') }}" class="border border-gray-300 text-gray-600 px-4 py-2 rounded text-sm">Xóa</a>
    @endif
</form>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tên kỳ thi</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Lớp</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Bắt đầu</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Kết thúc</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">T/G làm bài</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Trạng thái</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Submissions</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($exams as $exam)
            @php
                $now = now();
                $isOpen = $now->between($exam->starts_at, $exam->ends_at);
                $isPast = $now->gt($exam->ends_at);
            @endphp
            <tr class="hover:bg-gray-50 transition {{ !$exam->is_active ? 'opacity-60' : '' }}">
                <td class="px-4 py-3 font-medium text-gray-800">
                    {{ $exam->title }}
                    @if(!$exam->is_active)
                        <span class="ml-1 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Ẩn</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $exam->class->name }}</td>
                <td class="px-4 py-3 text-gray-600 text-xs">{{ $exam->starts_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-gray-600 text-xs">{{ $exam->ends_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $exam->time_limit_minutes ? $exam->time_limit_minutes . ' phút' : '—' }}</td>
                <td class="px-4 py-3">
                    @if(!$exam->is_active)
                        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded">Ẩn</span>
                    @elseif($isOpen)
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded">Đang mở</span>
                    @elseif($isPast)
                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded">Đã đóng</span>
                    @else
                        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded">Sắp mở</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $exam->submissions_count ?? 0 }}</td>
                <td class="px-4 py-3">
                    <div class="flex gap-2 justify-end items-center">
                        <a href="{{ route('admin.exams.show', $exam) }}" class="text-xs text-gray-600 hover:underline">Xem</a>
                        <a href="{{ route('admin.exams.edit', $exam) }}" class="text-xs text-blue-600 hover:underline">Sửa</a>
                        <form method="POST" action="{{ route('admin.exams.toggle', $exam) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" title="{{ $exam->is_active ? 'Ẩn kỳ thi' : 'Kích hoạt kỳ thi' }}"
                                class="{{ $exam->is_active ? 'text-green-600 hover:text-red-600' : 'text-gray-400 hover:text-green-600' }}">
                                @if($exam->is_active)
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                @else
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                @endif
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}"
                            onsubmit="return confirm('Xóa kỳ thi {{ addslashes($exam->title) }}?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">Không có kỳ thi nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $exams->withQueryString()->links() }}</div>
@endsection
