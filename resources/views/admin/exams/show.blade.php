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
        <p class="text-xs text-gray-500 uppercase">Đã nộp bài</p>
        <p class="font-bold text-2xl text-green-600 mt-1">{{ $submittedAttempts->count() }} <span class="text-xs text-gray-400 font-normal">sinh viên</span></p>
    </div>
</div>

@if($exam->instructions)
<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
    <p class="font-medium text-gray-700 mb-2">Đề thi / Hướng dẫn:</p>
    <div class="text-sm text-gray-600 rich-content">{!! \App\Support\HtmlSanitizer::render($exam->instructions) !!}</div>
</div>
@endif

<!-- Submitted Students Table (To Grade) -->
<div class="bg-white rounded-lg border border-gray-200 mb-6 shadow-sm overflow-hidden">
    <div class="px-4 py-3 bg-blue-50/50 border-b border-gray-200 flex justify-between items-center">
        <div>
            <h2 class="font-semibold text-gray-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Sinh viên đã nộp bài chính thức ({{ $submittedAttempts->count() }})
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Những sinh viên đã nhấn nút nộp bài để giáo viên chấm điểm</p>
        </div>
        <a href="{{ route('admin.submissions.index', ['tab' => 'submitted', 'exam_id' => $exam->id]) }}"
           class="text-xs text-blue-600 hover:underline font-medium">Xem trong Bài làm &rarr;</a>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-2.5 font-medium text-gray-600">Sinh viên</th>
                <th class="text-left px-4 py-2.5 font-medium text-gray-600">Thời gian làm bài</th>
                <th class="text-left px-4 py-2.5 font-medium text-gray-600">Nộp bài lúc</th>
                <th class="text-left px-4 py-2.5 font-medium text-gray-600">Trạng thái code</th>
                <th class="text-center px-4 py-2.5 font-medium text-gray-600">Hành động</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($submittedAttempts as $attempt)
            <tr class="hover:bg-blue-50/30 transition">
                <td class="px-4 py-3">
                    <p class="font-semibold text-gray-800">{{ $attempt->user->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $attempt->user->student_id ?? '' }} &bull; {{ $attempt->user->email ?? '' }}</p>
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-medium">
                        ⏱ {{ $attempt->durationFormatted() }}
                    </span>
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        Bắt đầu: {{ $attempt->started_at ? $attempt->started_at->format('d/m H:i') : '—' }}
                    </p>
                </td>
                <td class="px-4 py-3">
                    <p class="text-xs font-medium text-gray-700">{{ $attempt->submitted_at ? $attempt->submitted_at->format('d/m/Y H:i:s') : '—' }}</p>
                    <span class="inline-block mt-0.5 text-[10px] text-green-700 bg-green-50 border border-green-200 px-1.5 py-0.5 rounded font-medium">
                        ✓ Đã nộp thành công
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if($attempt->latest_submission)
                        <span class="text-xs px-2 py-0.5 rounded font-medium
                            {{ $attempt->latest_submission->execution_status === 'success' ? 'bg-green-100 text-green-700 border border-green-200' :
                               ($attempt->latest_submission->execution_status === 'error' ? 'bg-red-100 text-red-700 border border-red-200' :
                               'bg-yellow-100 text-yellow-700 border border-yellow-200') }}">
                            ● {{ ucfirst($attempt->latest_submission->execution_status) }}
                        </span>
                        <p class="text-[11px] text-gray-400 mt-0.5 font-mono">
                            {{ $attempt->latest_submission->execution_time_ms ? $attempt->latest_submission->execution_time_ms . 'ms' : '0ms' }}
                        </p>
                    @else
                        <span class="text-xs text-gray-400 italic">Chưa có bài lưu</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($attempt->latest_submission)
                        <a href="{{ route('admin.submissions.show', $attempt->latest_submission->id) }}"
                           class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded-lg font-medium shadow-sm transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Chấm bài
                        </a>
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                    Chưa có sinh viên nào nhấn nộp bài cho kỳ thi này
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- All Submissions Log Table -->
<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center bg-gray-50">
        <h2 class="font-semibold text-gray-700 text-sm">Nhật ký tất cả lượt chạy code ({{ $submissions->total() }})</h2>
        <a href="{{ route('admin.submissions.index', ['tab' => 'all', 'exam_id' => $exam->id]) }}"
           class="text-xs text-blue-600 hover:underline">Xem tất cả trong Logs</a>
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
                <td class="px-4 py-2 text-right">
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
