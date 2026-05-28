@extends('layouts.app')
@section('title', $assignment->title)
@push('styles')
@include('partials._multifile_css')
@endpush
@section('content')
<?php
// Compute initial files for editor from last submission
$lastSub = $submissions->first();
$initialEditorFiles = null;
if ($lastSub) {
    if (!empty($lastSub->files) && count($lastSub->files) > 0) {
        $initialEditorFiles = array_map(
            fn($name, $content) => ['name' => $name, 'content' => $content],
            array_keys($lastSub->files), array_values($lastSub->files)
        );
        $initialEditorFiles = array_values($initialEditorFiles);
    } else {
        $initialEditorFiles = [['name' => 'index.php', 'content' => $lastSub->code ?? "\x3C?php\n\n// Viết code PHP của bạn ở đây\necho 'Hello, World!';\n"]];
    }
}
?>
<!-- Breadcrumb -->
<nav class="text-sm text-gray-500 mb-4">
    <a href="{{ route('student.classes.index') }}" class="hover:text-blue-600">Lớp học</a>
    <span class="mx-2">›</span>
    <a href="{{ route('student.classes.show', $class) }}" class="hover:text-blue-600">{{ $class->name }}</a>
    <span class="mx-2">›</span>
    <span class="text-gray-800">{{ $assignment->title }}</span>
</nav>

<div x-data="sandboxApp('assignment', {{ $assignment->id }})" x-init="init()">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ $assignment->title }}</h1>
            <p class="text-sm text-gray-500">{{ $class->name }}</p>
            @if($assignment->due_at)
                <p class="text-xs text-orange-600 mt-1">Hạn nộp: {{ $assignment->due_at->format('d/m/Y H:i') }}</p>
            @endif
        </div>
        <button @click="runCode()" :disabled="running"
            class="flex items-center gap-1.5 bg-green-600 text-white px-6 py-2 rounded font-medium hover:bg-green-700 disabled:opacity-50 transition">
            <span x-show="!running" class="flex items-center gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                Chạy Code
            </span>
            <span x-show="running" x-cloak class="flex items-center gap-1.5">
                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Đang chạy...
            </span>
        </button>
    </div>

    @if($assignment->instructions)
    <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4 text-sm text-gray-700">
        <p class="flex items-center gap-1 font-medium text-blue-800 mb-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
            Hướng dẫn:
        </p>
        {!! nl2br(e($assignment->instructions)) !!}
    </div>
    @endif

    <div class="grid grid-cols-5 gap-4" style="height: calc(100vh - 260px); min-height: 460px;">
        <!-- Editor Panel -->
        <div class="col-span-3 sandbox-panel">
            @include('partials._filetabs_html')
            <div class="editor-wrap">
                <div id="editor"></div>
            </div>

            <!-- Status -->
            <div class="mt-2 flex items-center gap-2 flex-wrap flex-shrink-0">
                <span x-show="status === 'queued' || status === 'running'" x-cloak
                    class="flex items-center gap-1 text-sm text-yellow-700 bg-yellow-100 px-2 py-1 rounded">
                    <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    Đang thực thi...</span>
                <span x-show="status === 'success'" x-cloak
                    class="flex items-center gap-1 text-sm text-green-700 bg-green-100 px-2 py-1 rounded">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Thành công</span>
                <span x-show="status === 'error'" x-cloak
                    class="flex items-center gap-1 text-sm text-red-700 bg-red-100 px-2 py-1 rounded">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Lỗi</span>
                <span x-show="status === 'timeout'" x-cloak
                    class="flex items-center gap-1 text-sm text-orange-700 bg-orange-100 px-2 py-1 rounded">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Timeout</span>
                <span x-text="executionTime" class="text-xs text-gray-400"></span>
            </div>

            <!-- PHP Errors -->
            <div x-show="errors" x-cloak class="mt-2 bg-red-50 border border-red-200 rounded p-3 flex-shrink-0">
                <p class="text-sm font-medium text-red-700 mb-1">Lỗi PHP:</p>
                <pre x-text="errors" class="text-xs text-red-600 overflow-auto max-h-32 font-mono"></pre>
            </div>
        </div>

        <!-- Preview Panel -->
        <div class="col-span-2 sandbox-panel">
            <div class="bg-gray-100 text-gray-500 text-xs px-3 py-1.5 rounded-t flex items-center gap-1 flex-shrink-0 border border-b-0 border-gray-200">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                <span>Output Preview</span>
            </div>
            <iframe id="preview-frame" sandbox="allow-forms allow-scripts allow-same-origin"
                x-bind:src="previewUrl || 'about:blank'"></iframe>
        </div>
    </div>

    <!-- Submission History -->
    @if($submissions->count())
    <div class="mt-6">
        <h3 class="flex items-center gap-1.5 font-semibold text-gray-700 mb-2">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
            Lịch sử submissions
        </h3>
        <div class="space-y-2">
            @foreach($submissions as $sub)
            <div class="bg-white border border-gray-200 rounded p-3 flex justify-between items-center text-sm cursor-pointer hover:bg-gray-50 transition"
                onclick="restoreSubmission({{ json_encode($sub->files ?? null) }}, {{ json_encode($sub->code ?? '') }})">
                <span class="text-gray-600">{{ $sub->submitted_at->format('d/m H:i') }}</span>
                <span class="font-mono text-xs text-gray-400 flex-1 mx-4 truncate">
                    @if(!empty($sub->files))
                        <svg class="w-3.5 h-3.5 inline-block mr-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>{{ count($sub->files) }} file(s)
                    @else
                        {{ Str::limit($sub->code, 50) }}
                    @endif
                </span>
                <span class="{{ $sub->execution_status === 'success' ? 'text-green-600 bg-green-50' : ($sub->execution_status === 'error' ? 'text-red-600 bg-red-50' : 'text-yellow-600 bg-yellow-50') }} px-2 py-0.5 rounded text-xs">
                    {{ $sub->execution_status }}
                </span>
                <span class="text-gray-400 ml-3 text-xs">{{ $sub->execution_time_ms }}ms</span>
            </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-1">Nhấn vào submission để khôi phục code.</p>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js"></script>
@include('partials._multifile_js')
<script>
let monacoEditor;
let sandboxInstance;
const initialEditorFiles = @json($initialEditorFiles);

require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs' } });
require(['vs/editor/editor.main'], function() {
    const firstContent = initialEditorFiles ? initialEditorFiles[0].content
        : '\x3C?php\n\n// Viết code PHP của bạn ở đây\necho \'Hello, World!\';\n';
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
});

function restoreSubmission(filesJson, codeString) {
    if (!sandboxInstance) return;
    if (filesJson && Object.keys(filesJson).length > 0) {
        const arr = Object.entries(filesJson).map(([n,c]) => ({ name: n, content: c }));
        sandboxInstance.files = arr;
        sandboxInstance.activeIdx = 0;
        sandboxInstance.renamingIdx = -1;
        if (monacoEditor) monacoEditor.setValue(arr[0].content);
    } else {
        const code = codeString || '\x3C?php\n\n';
        sandboxInstance.files = [{ name: 'index.php', content: code }];
        sandboxInstance.activeIdx = 0;
        sandboxInstance.renamingIdx = -1;
        if (monacoEditor) monacoEditor.setValue(code);
    }
}

function sandboxApp(contextType, contextId) {
    return Object.assign(createMultiFileMixin(initialEditorFiles), {
        running: false,
        status: '',
        errors: '',
        previewUrl: '',
        executionTime: '',

        init() { sandboxInstance = this; },

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
                    body: JSON.stringify({
                        files: filesMap,
                        code: this.files[this.activeIdx].content,
                        context_type: contextType,
                        context_id: contextId
                    })
                });

                if (!res.ok) { const err = await res.json(); throw new Error(err.message || 'Server error'); }
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
            if (attempts >= 30) { this.status = 'timeout'; this.errors = 'Polling timeout.'; this.running = false; }
        }
    });
}
</script>
@endpush

