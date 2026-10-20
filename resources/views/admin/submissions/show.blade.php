@extends('layouts.admin')
@section('title', 'Chi tiết submission #' . $submission->id)
@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.submissions.index') }}" class="text-sm text-gray-500 hover:text-blue-600 inline-flex items-center gap-1 transition">
                ← Danh sách submissions
            </a>
            @if($submission->exam_id)
            <span class="text-gray-300">&bull;</span>
            <a href="{{ route('admin.exams.show', $submission->exam_id) }}" class="text-sm text-blue-600 hover:underline inline-flex items-center gap-1 transition font-medium">
                Về kỳ thi: {{ Str::limit($submission->exam->title ?? '', 30) }} &rarr;
            </a>
            @endif
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mt-1">Chi tiết Submission #{{ $submission->id }}</h1>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <form method="POST" action="{{ route('admin.submissions.rerun', $submission->id) }}" onsubmit="this.querySelector('button').disabled = true;">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ▶ Chạy lại Code
            </button>
        </form>
        <a href="{{ route('admin.submissions.download', $submission->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition shadow-sm">
            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Tải file .php
        </a>
        <a href="{{ route('admin.submissions.download-output', $submission->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition shadow-sm">
            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Tải Output
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Info Panel -->
    <div class="space-y-4">
        <!-- Grading Box (Chấm điểm bài làm) -->
        <div class="bg-white border {{ $submission->score !== null ? 'border-green-300 ring-1 ring-green-100' : 'border-blue-200' }} rounded-lg p-5 shadow-sm">
            <div class="flex items-center justify-between border-b pb-2.5 mb-3">
                <h3 class="font-semibold text-gray-800 text-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Chấm điểm bài làm
                </h3>
                @if($submission->score !== null)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                        Đã chấm: {{ number_format($submission->score, 2) }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                        Chưa chấm điểm
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.submissions.grade', $submission->id) }}" class="space-y-3">
                @csrf
                <div>
                    <label for="score-input" class="block text-xs font-medium text-gray-700 mb-1 flex items-center justify-between">
                        <span>Điểm số:</span>
                        <span class="text-[11px] text-gray-400 font-normal">Thang điểm 10 (VD: 8.5)</span>
                    </label>
                    <div class="relative rounded-md shadow-sm">
                        <input type="number" step="0.01" min="0" max="100" id="score-input" name="score"
                            value="{{ old('score', $submission->score !== null ? $submission->score : '') }}"
                            placeholder="Nhập điểm..."
                            class="w-full text-sm font-semibold rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2 px-3 pr-10 text-gray-900 border">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-gray-400 font-bold">
                            đ
                        </div>
                    </div>
                    @error('score')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="feedback-input" class="block text-xs font-medium text-gray-700 mb-1">
                        Nhận xét của giáo viên:
                    </label>
                    <textarea id="feedback-input" name="teacher_feedback" rows="3"
                        placeholder="Nhập nhận xét, ưu điểm hoặc lỗi cần khắc phục cho sinh viên..."
                        class="w-full text-xs rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 p-2.5 text-gray-800 border leading-relaxed">{{ old('teacher_feedback', $submission->teacher_feedback) }}</textarea>
                    @error('teacher_feedback')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-1">
                    <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-400 transition shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $submission->score !== null ? 'Cập nhật điểm & nhận xét' : 'Lưu điểm bài làm' }}
                    </button>
                </div>

                @if($submission->graded_at)
                <div class="pt-2 border-t border-gray-100 text-[11px] text-gray-500 flex items-center justify-between">
                    <span>Chấm bởi: <strong class="text-gray-700">{{ $submission->grader->name ?? 'Giáo viên' }}</strong></span>
                    <span>{{ $submission->graded_at->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </form>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm flex items-center gap-1.5 border-b pb-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Thông tin sinh viên & bài nộp
            </h3>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-400 text-xs">Sinh viên</p>
                    <p class="font-semibold text-gray-900">{{ $submission->user->name }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs text-blue-600 font-mono font-medium">{{ $submission->user->student_id }}</span>
                        <span class="text-xs text-gray-400">&bull;</span>
                        <span class="text-xs text-gray-500">{{ $submission->user->email }}</span>
                    </div>
                </div>

                <div>
                    <p class="text-gray-400 text-xs">Bài tập / Kỳ thi</p>
                    <p class="font-medium text-gray-800">
                        {{ $submission->assignment?->title ?? $submission->exam?->title ?? 'Sandbox tự do' }}
                    </p>
                </div>

                <div>
                    <p class="text-gray-400 text-xs">Lớp học</p>
                    <p class="text-gray-700">
                        {{ $submission->assignment?->class?->name ?? $submission->exam?->class?->name ?? $submission->user->class_name ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-gray-400 text-xs">Nộp lúc</p>
                    <p class="text-gray-700 font-medium">{{ $submission->submitted_at ? $submission->submitted_at->format('d/m/Y H:i:s') : '—' }}</p>
                </div>

                <div>
                    <p class="text-gray-400 text-xs">Trạng thái thực thi</p>
                    <span class="inline-block text-xs px-2.5 py-0.5 rounded-full font-medium mt-0.5
                        {{ $submission->execution_status === 'success' ? 'bg-green-100 text-green-700 border border-green-200' :
                           ($submission->execution_status === 'error' ? 'bg-red-100 text-red-700 border border-red-200' :
                           ($submission->execution_status === 'timeout' ? 'bg-orange-100 text-orange-700 border border-orange-200' :
                           'bg-yellow-100 text-yellow-700 border border-yellow-200')) }}">
                        ● {{ ucfirst($submission->execution_status) }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-100">
                    <div>
                        <p class="text-gray-400 text-xs">Thời gian chạy</p>
                        <p class="text-gray-700 font-mono text-xs font-semibold">{{ $submission->execution_time_ms ?? 0 }} ms</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">Bộ nhớ</p>
                        <p class="text-gray-700 font-mono text-xs font-semibold">{{ $submission->memory_used_kb ?? 0 }} KB</p>
                    </div>
                </div>
            </div>
        </div>

        @if($submission->user->sandbox_db_name)
        <div class="bg-blue-50/60 border border-blue-200 rounded-lg p-4 text-xs">
            <h4 class="font-semibold text-blue-800 mb-2 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                Database Sandbox của sinh viên
            </h4>
            <div class="space-y-1 font-mono text-blue-900">
                <p><span class="text-gray-500">DB:</span> <span class="font-semibold">{{ $submission->user->sandbox_db_name }}</span></p>
                <p><span class="text-gray-500">User:</span> <span>{{ $submission->user->sandbox_db_user }}</span></p>
            </div>
            <p class="text-gray-500 text-[11px] mt-2 leading-relaxed">
                Khi form sinh viên thao tác cơ sở dữ liệu, câu lệnh sẽ tự động truy vấn vào database riêng này.
            </p>
        </div>
        @endif

        @if($submission->output_errors || $submission->errors)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h3 class="font-semibold text-red-700 mb-2 text-xs flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Lỗi PHP
            </h3>
            <pre class="text-xs text-red-600 overflow-auto max-h-48 font-mono whitespace-pre-wrap leading-relaxed">{{ $submission->output_errors ?: $submission->errors }}</pre>
        </div>
        @endif
    </div>

    <!-- Code & Output -->
    <div class="lg:col-span-2 space-y-5">
        <!-- Code Viewer -->
        @php
            $filesMap = $submission->files ?? [];
            $hasMultipleFiles = is_array($filesMap) && count($filesMap) > 1;
            $firstFile = $hasMultipleFiles ? array_key_first($filesMap) : 'submission.php';
        @endphp

        @if($hasMultipleFiles)
        <div x-data="{ activeFile: '{{ $firstFile }}', copied: false }" class="bg-gray-900 border border-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="bg-gray-800/90 border-b border-gray-700 px-3 py-1.5 flex items-center justify-between">
                <div class="flex items-center gap-1 overflow-x-auto">
                    @foreach($filesMap as $fileName => $fileContent)
                    <button type="button"
                        @click="activeFile = '{{ $fileName }}'"
                        :class="activeFile === '{{ $fileName }}' ? 'bg-gray-900 text-blue-400 font-semibold border-b-2 border-blue-400' : 'text-gray-400 hover:text-gray-200'"
                        class="px-3 py-1 text-xs rounded-t transition flex items-center gap-1.5 whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ $fileName }}
                    </button>
                    @endforeach
                </div>
                <button type="button"
                    @click="navigator.clipboard.writeText(document.getElementById('code-' + activeFile).innerText); copied = true; setTimeout(() => copied = false, 2000)"
                    class="text-xs text-gray-400 hover:text-gray-200 px-2 py-1 rounded bg-gray-700 hover:bg-gray-600 transition flex items-center gap-1">
                    <span x-show="!copied">📋 Sao chép</span>
                    <span x-show="copied" class="text-green-400 font-medium">✓ Đã sao chép</span>
                </button>
            </div>
            @foreach($filesMap as $fileName => $fileContent)
            <div x-show="activeFile === '{{ $fileName }}'">
                <pre id="code-{{ $fileName }}" class="p-4 text-xs text-gray-100 font-mono overflow-auto max-h-96 whitespace-pre-wrap leading-relaxed">{{ $fileContent }}</pre>
            </div>
            @endforeach
        </div>
        @else
        <div x-data="{ copied: false }" class="bg-gray-900 border border-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="bg-gray-800/90 text-gray-300 px-4 py-2 text-xs flex items-center justify-between border-b border-gray-700">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                    <span class="font-mono text-gray-400 ml-2">{{ !empty($filesMap) ? array_key_first($filesMap) : 'submission.php' }}</span>
                </div>
                <button type="button"
                    @click="navigator.clipboard.writeText(document.getElementById('code-single').innerText); copied = true; setTimeout(() => copied = false, 2000)"
                    class="text-xs text-gray-400 hover:text-gray-200 px-2 py-1 rounded bg-gray-700 hover:bg-gray-600 transition flex items-center gap-1">
                    <span x-show="!copied">📋 Sao chép</span>
                    <span x-show="copied" class="text-green-400 font-medium">✓ Đã sao chép</span>
                </button>
            </div>
            <pre id="code-single" class="p-4 text-xs text-gray-100 font-mono overflow-auto max-h-96 whitespace-pre-wrap leading-relaxed">{{ $submission->code }}</pre>
        </div>
        @endif

        <!-- Output Preview -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-4 py-2.5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-xs text-gray-800 uppercase tracking-wider">Output Preview</span>
                    <span class="text-[11px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-medium">Hỗ trợ Form POST / GET</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                        onclick="const f = document.getElementById('preview-frame'); if(f) f.src = '{{ route('sandbox.preview', $submission->id) }}?t=' + Date.now();"
                        class="text-xs text-gray-600 hover:text-blue-600 px-2 py-1 rounded border border-gray-300 hover:border-blue-400 bg-white transition flex items-center gap-1 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Làm mới Preview
                    </button>
                    <a href="{{ route('sandbox.preview', $submission->id) }}" target="_blank"
                        class="text-xs text-gray-500 hover:text-blue-600 px-2 py-1 rounded border border-gray-300 hover:border-blue-400 bg-white transition flex items-center gap-1 shadow-sm">
                        Mở tab mới ↗
                    </a>
                </div>
            </div>

            <div class="bg-amber-50/60 border-b border-amber-100 px-4 py-1.5 text-[11px] text-amber-800 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Thầy/Cô có thể nhập dữ liệu vào form bên dưới và nhấn submit để chấm/kiểm tra trực tiếp kết quả chạy code của sinh viên.</span>
            </div>

            <iframe id="preview-frame"
                src="{{ route('sandbox.preview', $submission->id) }}"
                sandbox="allow-forms allow-scripts allow-same-origin"
                class="w-full bg-white"
                style="min-height: 480px; height: 520px; border: none;"></iframe>
        </div>
    </div>
</div>
@endsection
