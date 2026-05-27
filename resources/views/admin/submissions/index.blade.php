@extends('layouts.admin')
@section('title', 'Submissions')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Submissions</h1>
        <p class="text-gray-500 text-sm mt-1">Tất cả bài nộp của sinh viên</p>
    </div>
</div>

<!-- Filter -->
<form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-4 flex gap-3 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
        placeholder="Tìm tên sinh viên, mã SV..."
        class="flex-1 min-w-48 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <select name="status" class="border border-gray-300 rounded px-3 py-2 text-sm">
        <option value="">Tất cả trạng thái</option>
        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Thành công</option>
        <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Lỗi</option>
        <option value="timeout" {{ request('status') === 'timeout' ? 'selected' : '' }}>Timeout</option>
    </select>
    <select name="type" class="border border-gray-300 rounded px-3 py-2 text-sm">
        <option value="">Tất cả loại</option>
        <option value="exam" {{ request('type') === 'exam' ? 'selected' : '' }}>Kỳ thi</option>
        <option value="assignment" {{ request('type') === 'assignment' ? 'selected' : '' }}>Bài tập</option>
        <option value="free" {{ request('type') === 'free' ? 'selected' : '' }}>Sandbox tự do</option>
    </select>
    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-sm">Lọc</button>
    @if(request()->hasAny(['search', 'status', 'type', 'exam_id']))
        <a href="{{ route('admin.submissions.index') }}" class="border border-gray-300 text-gray-600 px-4 py-2 rounded text-sm hover:bg-gray-50">Xóa bộ lọc</a>
    @endif
</form>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Sinh viên</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Bài tập / Kỳ thi</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Lớp</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Trạng thái</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">T/G thực thi</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Nộp lúc</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($submissions as $sub)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $sub->user->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $sub->user->student_id ?? '' }}</p>
                </td>
                <td class="px-4 py-3">
                    <p class="text-gray-800">{{ $sub->assignment?->title ?? $sub->exam?->title ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $sub->assignment ? 'Bài tập' : ($sub->exam ? 'Kỳ thi' : 'Sandbox') }}</p>
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs">
                    {{ $sub->assignment?->class?->name ?? $sub->exam?->class?->name ?? '—' }}
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded
                        {{ $sub->execution_status === 'success' ? 'bg-green-100 text-green-700' :
                           ($sub->execution_status === 'error' ? 'bg-red-100 text-red-700' :
                           ($sub->execution_status === 'timeout' ? 'bg-orange-100 text-orange-700' :
                           'bg-yellow-100 text-yellow-700')) }}">
                        {{ $sub->execution_status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs">
                    {{ $sub->execution_time_ms ? $sub->execution_time_ms . 'ms' : '—' }}
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $sub->submitted_at->format('d/m H:i') }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.submissions.show', $sub) }}" class="text-xs text-blue-600 hover:underline">Xem</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">Không có submission nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $submissions->withQueryString()->links() }}</div>
@endsection
