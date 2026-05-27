@extends('layouts.admin')
@section('title', $exam->title)
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.exams.index') }}" class="text-sm text-gray-500 hover:text-blue-600">← Danh sách kỳ thi</a>
    <div class="flex justify-between items-start mt-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $exam->title }}</h1>
            <p class="text-gray-500 text-sm">{{ $exam->class->name }}</p>
        </div>
        <a href="{{ route('admin.exams.edit', $exam) }}"
           class="border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50">
            ✏️ Sửa kỳ thi
        </a>
    </div>
</div>

<!-- Exam Info Grid -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-500 uppercase">Bắt đầu</p>
        <p class="font-medium text-gray-800 mt-1 text-sm">{{ $exam->starts_at->format('d/m/Y H:i') }}</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-500 uppercase">Kết thúc</p>
        <p class="font-medium text-gray-800 mt-1 text-sm">{{ $exam->ends_at->format('d/m/Y H:i') }}</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-500 uppercase">T/G làm bài</p>
        <p class="font-medium text-orange-600 mt-1 text-sm">{{ $exam->time_limit_minutes ? $exam->time_limit_minutes . ' phút' : 'Không giới hạn' }}</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-500 uppercase">Submissions</p>
        <p class="font-bold text-2xl text-blue-600 mt-1">{{ $submissions->total() }}</p>
    </div>
</div>

@if($exam->instructions)
<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
    <p class="font-medium text-gray-700 mb-2">Đề thi / Hướng dẫn:</p>
    <div class="text-sm text-gray-600 whitespace-pre-line">{{ $exam->instructions }}</div>
</div>
@endif

<!-- Submissions Table -->
<div class="bg-white rounded-lg border border-gray-200">
    <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-semibold text-gray-700">Submissions ({{ $submissions->total() }})</h2>
        <a href="{{ route('admin.submissions.index', ['exam_id' => $exam->id]) }}"
           class="text-xs text-blue-600 hover:underline">Xem tất cả</a>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-4 py-2 font-medium text-gray-600">Sinh viên</th>
                <th class="text-left px-4 py-2 font-medium text-gray-600">Nộp lúc</th>
                <th class="text-left px-4 py-2 font-medium text-gray-600">Trạng thái</th>
                <th class="text-left px-4 py-2 font-medium text-gray-600">T/G thực thi</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($submissions as $sub)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2">
                    <p class="font-medium text-gray-800">{{ $sub->user->name }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $sub->user->student_id }}</p>
                </td>
                <td class="px-4 py-2 text-gray-600 text-xs">{{ $sub->submitted_at->format('d/m/Y H:i:s') }}</td>
                <td class="px-4 py-2">
                    <span class="text-xs px-2 py-0.5 rounded {{ $sub->execution_status === 'success' ? 'bg-green-100 text-green-700' : ($sub->execution_status === 'error' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ $sub->execution_status }}
                    </span>
                </td>
                <td class="px-4 py-2 text-gray-600 text-xs">{{ $sub->execution_time_ms ? $sub->execution_time_ms . 'ms' : '—' }}</td>
                <td class="px-4 py-2">
                    <a href="{{ route('admin.submissions.show', $sub) }}" class="text-xs text-blue-600 hover:underline">Xem</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400">Chưa có submission nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $submissions->links() }}</div>
@endsection
