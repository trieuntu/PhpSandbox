@extends('layouts.app')
@section('title', $exam->title)
@push('styles')
@include('partials._multifile_css')
<style>
.timer-warning { color: #dc2626; animation: pulse 1s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>
@endpush
@section('content')
<?php
$initialExamFiles = null;
if ($lastSubmission) {
    if (!empty($lastSubmission->files) && count($lastSubmission->files) > 0) {
        $initialExamFiles = array_values(array_map(
            fn($name, $content) => ['name' => $name, 'content' => $content],
            array_keys($lastSubmission->files), array_values($lastSubmission->files)
        ));
    } else {
        $initialExamFiles = [['name' => 'index.php', 'content' => $lastSubmission->code ?? "<?php\n\n// Viết code PHP của bạn ở đây\n"]];
    }
}
?>
<div x-data="examApp({{ $exam->id }}, {{ $remainingSeconds ?? 'null' }})" x-init="init()"
     style="height: calc(100vh - 112px); display: flex; flex-direction: column;">
    <!-- Header with Timer -->
    <div class="flex justify-between items-center mb-3 flex-shrink-0">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ $exam->title }}</h1>
            <p class="text-sm text-gray-500">{{ $exam->class->name }}</p>
        </div>
        <div class="flex items-center gap-4">
            <span x-show="autoSaved" x-cloak class="flex items-center gap-1 text-xs text-green-600 bg-green-50 px-2 py-1 rounded">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                Đã lưu
            </span>
            <span x-show="saving" x-cloak class="text-xs text-yellow-600">Đang lưu...</span>

            @if($exam->time_limit_minutes)
            <div class="bg-gray-800 text-white px-4 py-2 rounded font-mono text-lg"
                 :class="{ 'bg-red-600 timer-warning': remainingSeconds !== null && remainingSeconds < 300 }">
                <span x-text="formatTime(remainingSeconds)">{{ gmdate('H:i:s', $remainingSeconds ?? 0) }}</span>
            </div>
            @endif

            <button @click="submitExam()" :disabled="submitting"
                class="flex items-center gap-1.5 bg-red-600 text-white px-6 py-2 rounded font-medium hover:bg-red-700 disabled:opacity-50 transition">
                <span x-show="!submitting" class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                    Nộp bài
                </span>
                <span x-show="submitting" x-cloak>Đang nộp...</span>
            </button>
        </div>
    </div>

    <!-- Run Button Row -->
    <div class="flex items-center gap-3 mb-2 flex-shrink-0">
        <button @click="runCode()" :disabled="running"
            class="flex items-center gap-1.5 bg-green-600 text-white px-5 py-2 rounded font-medium hover:bg-green-700 disabled:opacity-50 transition">
            <span x-show="!running" class="flex items-center gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                Chạy Code
            </span>
            <span x-show="running" x-cloak class="flex items-center gap-1.5">
                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Đang chạy...
            </span>
        </button>
        <span x-show="status === 'success'" x-cloak class="flex items-center gap-1 text-sm text-green-700 bg-green-100 px-2 py-1 rounded"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Thành công</span>
        <span x-show="status === 'error'" x-cloak class="flex items-center gap-1 text-sm text-red-700 bg-red-100 px-2 py-1 rounded"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Lỗi</span>
        <span x-show="status === 'timeout'" x-cloak class="flex items-center gap-1 text-sm text-orange-700 bg-orange-100 px-2 py-1 rounded"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Timeout</span>
        <span x-show="status === 'queued' || status === 'running'" x-cloak class="flex items-center gap-1 text-sm text-yellow-700 bg-yellow-100 px-2 py-1 rounded"><svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>Đang thực thi...</span>
        <span x-text="executionTime" class="text-xs text-gray-400"></span>
    </div>

    @if($exam->instructions)
    <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-2 text-sm text-gray-700 flex-shrink-0 max-h-24 overflow-auto">
        <p class="flex items-center gap-1 font-medium text-blue-800 mb-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
            Đề thi / Hướng dẫn:
        </p>
        {!! nl2br(e($exam->instructions)) !!}
    </div>
    @endif

    <div class="grid grid-cols-5 gap-4 flex-1 min-h-0">
        <!-- Editor -->
        <div class="col-span-3 sandbox-panel">
            @include('partials._filetabs_html')
            <div class="editor-wrap">
                <div id="editor"></div>
            </div>
            <!-- PHP Errors -->
            <div x-show="errors" x-cloak class="mt-1 bg-red-50 border border-red-200 rounded p-2 flex-shrink-0">
                <p class="text-xs font-semibold text-red-700 mb-1">Lỗi PHP:</p>
                <pre x-text="errors" class="text-xs text-red-600 overflow-auto max-h-24 font-mono"></pre>
            </div>
        </div>

        <!-- Preview -->
        <div class="col-span-2 sandbox-panel">
            <div class="bg-gray-100 text-gray-500 text-xs px-3 py-1.5 rounded-t flex items-center gap-1 flex-shrink-0 border border-b-0 border-gray-200">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                Output Preview
            </div>
            <iframe id="preview-frame" sandbox="allow-forms allow-scripts allow-same-origin"
                x-bind:src="previewUrl || 'about:blank'"></iframe>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js"></script>
@include('partials._multifile_js')
<script>
let monacoEditor;
const initialExamFiles = @json($initialExamFiles);

require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs' } });
require(['vs/editor/editor.main'], function() {
    const firstContent = initialExamFiles ? initialExamFiles[0].content : '<?php\n\n// Viết code PHP của bạn ở đây\n';
    monacoEditor = monaco.editor.create(document.getElementById('editor'), {
        value: firstContent,
        language: 'php',
        theme: 'vs',
        fontSize: 14,
        minimap: { enabled: false },
        scrollBeyondLastLine: false,
        automaticLayout: true,
        wordWrap: 'on',
    });

    // Auto-save on change (debounced 30s)
    let saveTimer;
    monacoEditor.onDidChangeModelContent(() => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            window.examAppInstance && window.examAppInstance.autoSaveCode();
        }, 30000);
    });
});

function examApp(examId, initialSeconds) {
    const app = Object.assign(createMultiFileMixin(initialExamFiles), {
        running: false,
        submitting: false,
        saving: false,
        autoSaved: false,
        status: '',
        errors: '',
        previewUrl: '',
        executionTime: '',
        remainingSeconds: initialSeconds,

        init() {
            if (this.remainingSeconds !== null) {
                setInterval(() => {
                    if (this.remainingSeconds > 0) {
                        this.remainingSeconds--;
                        if (this.remainingSeconds === 0) this.submitExam(true);
                    }
                }, 1000);
            }
            window.examAppInstance = this;
        },

        formatTime(seconds) {
            if (seconds === null) return '--:--:--';
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
        },

        async autoSaveCode() {
            if (!monacoEditor) return;
            this.saving = true;
            this.autoSaved = false;
            const filesMap = this.buildFilesMap();
            try {
                await fetch('/api/sandbox/execute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ files: filesMap, code: this.files[this.activeIdx].content, context_type: 'exam', context_id: examId, save_only: true })
                });
                this.autoSaved = true;
                setTimeout(() => { this.autoSaved = false; }, 3000);
            } catch(e) {}
            this.saving = false;
        },

        async runCode() {
            if (!monacoEditor) return;
            const filesMap = this.buildFilesMap();
            this.running = true;
            this.status = 'queued';
            this.errors = '';
            this.previewUrl = '';
            this.executionTime = '';

            try {
                const res = await fetch('/api/sandbox/execute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ files: filesMap, code: this.files[this.activeIdx].content, context_type: 'exam', context_id: examId })
                });

                const data = await res.json();
                await this.pollJob(data.job_id || data.submission_id);
            } catch(e) {
                this.status = 'error';
                this.errors = e.message;
                this.running = false;
            }
        },

        async pollJob(jobId) {
            let attempts = 0;
            while (attempts < 30) {
                await new Promise(r => setTimeout(r, 1000));
                attempts++;
                try {
                    const res = await fetch(`/api/sandbox/job/${jobId}`);
                    const data = await res.json();
                    this.status = data.status;
                    if (data.execution_time_ms) this.executionTime = `${data.execution_time_ms}ms`;
                    if (!['pending', 'running', 'queued'].includes(data.status)) {
                        this.running = false;
                        if (data.submission_id) this.previewUrl = `/api/sandbox/preview/${data.submission_id}`;
                        if (data.errors) this.errors = data.errors;
                        break;
                    }
                } catch(e) {}
            }
            if (attempts >= 30) { this.status = 'timeout'; this.running = false; }
        },

        async submitExam(autoSubmit = false) {
            if (!monacoEditor) return;
            const msg = autoSubmit
                ? 'Hết thời gian! Bài thi sẽ được nộp tự động.'
                : 'Bạn chắc chắn muốn nộp bài? Không thể chỉnh sửa sau khi nộp.';
            if (!autoSubmit && !confirm(msg)) return;

            this.submitting = true;
            const filesMap = this.buildFilesMap();
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            try {
                await fetch('/api/sandbox/execute', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ files: filesMap, code: this.files[this.activeIdx].content, context_type: 'exam', context_id: examId })
                });
            } catch(e) {}

            try {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/exams/${examId}/submit`;
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrf;
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            } catch(e) {
                this.submitting = false;
                alert('Lỗi khi nộp bài: ' + e.message);
            }
        }
    });
    return app;
}
</script>
@endpush

