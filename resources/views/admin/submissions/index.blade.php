@extends('layouts.admin')
@section('title', 'Quản lý Bài làm')
@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Quản lý Bài làm</h1>
        <p class="text-gray-500 text-sm mt-1">Theo dõi danh sách sinh viên nộp bài chính thức và nhật ký thực thi code</p>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="border-b border-gray-200 mb-6 bg-white rounded-t-lg px-4 pt-2 border-t border-x">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.submissions.index', array_merge(request()->except(['page']), ['tab' => 'submitted'])) }}"
           class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 whitespace-nowrap transition
                  {{ $activeTab === 'submitted' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Đã nhấn nộp bài thành công</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                         {{ $activeTab === 'submitted' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                {{ $submittedAttemptsTotal }}
            </span>
        </a>
        <a href="{{ route('admin.submissions.index', array_merge(request()->except(['page']), ['tab' => 'all'])) }}"
           class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 whitespace-nowrap transition
                  {{ $activeTab === 'all' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            <span>Tất cả lượt chạy code (Logs)</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                         {{ $activeTab === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                {{ $allSubmissionsTotal }}
            </span>
        </a>
    </nav>
</div>

@if($activeTab === 'submitted')
<!-- FILTER TAB: ĐÃ NỘP BÀI THÀNH CÔNG -->
<form method="GET" action="{{ route('admin.submissions.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-5 shadow-sm flex gap-3 flex-wrap items-center">
    <input type="hidden" name="tab" value="submitted">

    <div class="flex-1 min-w-64 relative">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Tìm theo tên sinh viên, MSSV, email, kỳ thi..."
            class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>

    <select name="exam_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 max-w-xs">
        <option value="">-- Tất cả kỳ thi --</option>
        @foreach($exams as $exam)
            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                {{ $exam->title }} ({{ $exam->class?->name ?? 'Lớp' }})
            </option>
        @endforeach
    </select>

    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        Tìm kiếm & Lọc
    </button>

    @if(request()->hasAny(['search', 'exam_id']))
        <a href="{{ route('admin.submissions.index', ['tab' => 'submitted']) }}" class="border border-gray-300 text-gray-600 px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
            Xóa bộ lọc
        </a>
    @endif
</form>

<!-- BẢNG DANH SÁCH SINH VIÊN ĐÃ NỘP BÀI -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Sinh viên</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Kỳ thi / Lớp</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Thời gian làm bài</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Nộp bài lúc</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Trạng thái code</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-700">Điểm số</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-700">Hành động</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($submittedAttempts as $attempt)
            <tr class="hover:bg-blue-50/40 transition">
                <td class="px-4 py-3">
                    <p class="font-semibold text-gray-900">{{ $attempt->user->name ?? 'N/A' }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs text-blue-600 font-mono font-medium">{{ $attempt->user->student_id ?? '—' }}</span>
                        <span class="text-xs text-gray-400">&bull;</span>
                        <span class="text-xs text-gray-500">{{ $attempt->user->email ?? '' }}</span>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $attempt->exam->title ?? '—' }}</p>
                    <p class="text-xs text-gray-500">{{ $attempt->exam?->class?->name ?? '—' }}</p>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-medium">
                        ⏱ {{ $attempt->durationFormatted() }}
                    </span>
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        Bắt đầu: {{ $attempt->started_at ? $attempt->started_at->format('d/m H:i') : '—' }}
                    </p>
                </td>
                <td class="px-4 py-3">
                    <p class="text-xs font-medium text-gray-800">
                        {{ $attempt->submitted_at ? $attempt->submitted_at->format('d/m/Y H:i:s') : '—' }}
                    </p>
                    <span class="inline-block mt-0.5 text-[10px] text-green-700 bg-green-50 border border-green-200 px-1.5 py-0.5 rounded font-medium">
                        ✓ Đã xác nhận nộp
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if($attempt->latest_submission)
                        <span class="text-xs px-2 py-0.5 rounded font-medium inline-block
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
                    @if($attempt->score !== null)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                            {{ number_format($attempt->score, 2) }} đ
                        </span>
                        @if($attempt->graded_at)
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $attempt->graded_at->format('d/m H:i') }}</p>
                        @endif
                    @elseif($attempt->latest_submission && $attempt->latest_submission->score !== null)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                            {{ number_format($attempt->latest_submission->score, 2) }} đ
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">
                            Chưa chấm
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($attempt->latest_submission)
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.submissions.show', $attempt->latest_submission->id) }}"
                           class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded-lg font-medium shadow-sm transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Chấm bài
                        </a>
                        <a href="{{ route('admin.submissions.download', $attempt->latest_submission->id) }}"
                           title="Tải mã nguồn sinh viên"
                           class="text-gray-500 hover:text-gray-700 p-1.5 rounded border border-gray-200 hover:bg-gray-50 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                    </div>
                    @else
                    <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="font-medium text-gray-600">Chưa có sinh viên nào nộp bài thi thành công</p>
                    <p class="text-xs text-gray-400 mt-1">Khi sinh viên hoàn thành bài thi và nhấn nút "Nộp bài", danh sách sẽ hiển thị tại đây để chấm điểm.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $submittedAttempts->links() }}</div>

@else
<!-- FILTER TAB: TẤT CẢ LƯỢT CHẠY CODE (LOGS) -->
<form method="GET" action="{{ route('admin.submissions.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-5 shadow-sm flex gap-3 flex-wrap items-center">
    <input type="hidden" name="tab" value="all">

    <div class="flex-1 min-w-56 relative">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Tìm theo tên sinh viên, mã SV, email, bài tập..."
            class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>

    <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Tất cả trạng thái</option>
        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Thành công</option>
        <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Lỗi</option>
        <option value="timeout" {{ request('status') === 'timeout' ? 'selected' : '' }}>Timeout</option>
    </select>

    <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Tất cả loại</option>
        <option value="exam" {{ request('type') === 'exam' ? 'selected' : '' }}>Kỳ thi</option>
        <option value="assignment" {{ request('type') === 'assignment' ? 'selected' : '' }}>Bài tập</option>
        <option value="free" {{ request('type') === 'free' ? 'selected' : '' }}>Sandbox tự do</option>
    </select>

    <select name="exam_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 max-w-xs">
        <option value="">-- Kỳ thi cụ thể --</option>
        @foreach($exams as $exam)
            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                {{ $exam->title }}
            </option>
        @endforeach
    </select>

    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        Lọc
    </button>

    @if(request()->hasAny(['search', 'status', 'type', 'exam_id']))
        <a href="{{ route('admin.submissions.index', ['tab' => 'all']) }}" class="border border-gray-300 text-gray-600 px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
            Xóa bộ lọc
        </a>
    @endif
</form>

<!-- BẢNG TẤT CẢ SUBMISSIONS -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Sinh viên</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Bài tập / Kỳ thi</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Lớp</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Trạng thái</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-700">Điểm số</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">T/G thực thi</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Thời gian chạy</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-700">Hành động</th>
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
                    <p class="text-gray-800 font-medium">{{ $sub->assignment?->title ?? $sub->exam?->title ?? 'Sandbox tự do' }}</p>
                    <span class="inline-block text-[11px] px-1.5 py-0.2 rounded mt-0.5
                        {{ $sub->exam ? 'bg-purple-100 text-purple-700' : ($sub->assignment ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ $sub->assignment ? 'Bài tập' : ($sub->exam ? 'Kỳ thi' : 'Sandbox') }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs">
                    {{ $sub->assignment?->class?->name ?? $sub->exam?->class?->name ?? '—' }}
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded font-medium
                        {{ $sub->execution_status === 'success' ? 'bg-green-100 text-green-700' :
                           ($sub->execution_status === 'error' ? 'bg-red-100 text-red-700' :
                           ($sub->execution_status === 'timeout' ? 'bg-orange-100 text-orange-700' :
                           'bg-yellow-100 text-yellow-700')) }}">
                        {{ $sub->execution_status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    @if($sub->score !== null)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                            {{ number_format($sub->score, 2) }} đ
                        </span>
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs font-mono">
                    {{ $sub->execution_time_ms ? $sub->execution_time_ms . 'ms' : '—' }}
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $sub->submitted_at ? $sub->submitted_at->format('d/m/Y H:i:s') : '—' }}</td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('admin.submissions.show', $sub) }}" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline">
                        Xem chi tiết &rarr;
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">Không có submission nào phù hợp</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $submissions->links() }}</div>
@endif
@endsection
