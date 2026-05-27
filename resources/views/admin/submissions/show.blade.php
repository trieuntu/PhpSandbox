@extends('layouts.admin')
@section('title', 'Chi tiết submission')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.submissions.index') }}" class="text-sm text-gray-500 hover:text-blue-600">← Danh sách submissions</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Chi tiết Submission</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Info Panel -->
    <div class="space-y-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="font-semibold text-gray-700 mb-3">Thông tin</h3>
            <div class="space-y-2 text-sm">
                <div>
                    <p class="text-gray-500 text-xs">Sinh viên</p>
                    <p class="font-medium text-gray-800">{{ $submission->user->name }}</p>
                    <p class="text-gray-400 text-xs font-mono">{{ $submission->user->student_id }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Bài tập / Kỳ thi</p>
                    <p class="font-medium text-gray-800">
                        {{ $submission->assignment?->title ?? $submission->exam?->title ?? 'Sandbox tự do' }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Lớp</p>
                    <p class="text-gray-700">
                        {{ $submission->assignment?->class?->name ?? $submission->exam?->class?->name ?? '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Nộp lúc</p>
                    <p class="text-gray-700">{{ $submission->submitted_at->format('d/m/Y H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Trạng thái thực thi</p>
                    <span class="text-xs px-2 py-0.5 rounded
                        {{ $submission->execution_status === 'success' ? 'bg-green-100 text-green-700' :
                           ($submission->execution_status === 'error' ? 'bg-red-100 text-red-700' :
                           'bg-yellow-100 text-yellow-700') }}">
                        {{ $submission->execution_status }}
                    </span>
                </div>
                @if($submission->execution_time_ms)
                <div>
                    <p class="text-gray-500 text-xs">Thời gian thực thi</p>
                    <p class="text-gray-700">{{ $submission->execution_time_ms }}ms</p>
                </div>
                @endif
            </div>
        </div>

        @if($submission->errors)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h3 class="font-semibold text-red-700 mb-2 text-sm">Lỗi PHP</h3>
            <pre class="text-xs text-red-600 overflow-auto max-h-40 font-mono whitespace-pre-wrap">{{ $submission->errors }}</pre>
        </div>
        @endif
    </div>

    <!-- Code & Output -->
    <div class="lg:col-span-2 space-y-4">
        <!-- Code -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-800 text-gray-300 px-4 py-2 text-xs flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                <span class="w-2 h-2 rounded-full bg-yellow-500 inline-block"></span>
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                <span class="ml-2">PHP Code</span>
            </div>
            <pre class="p-4 text-sm text-gray-800 font-mono overflow-auto max-h-96 whitespace-pre-wrap">{{ $submission->code }}</pre>
        </div>

        <!-- Preview Output -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-100 px-4 py-2 text-xs text-gray-500">Output Preview</div>
            @if($submission->output_html)
            <iframe srcdoc="{{ $submission->output_html }}" sandbox="allow-scripts allow-same-origin"
                class="w-full" style="height: 300px; border: none;"></iframe>
            @else
            <div class="p-4 text-gray-400 text-sm text-center">Không có output</div>
            @endif
        </div>
    </div>
</div>
@endsection
