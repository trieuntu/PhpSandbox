@extends('layouts.app')
@section('title', 'PHP Sandbox')
@push('styles')
@include('partials._multifile_css', ['editorHeight' => '510px', 'previewHeight' => '570px'])
@endpush
@section('content')
<div x-data="sandboxApp('free', null)" x-init="init()">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                </svg>
                PHP Sandbox
            </h1>
            <p class="text-sm text-gray-500">Thực hành PHP tự do — hỗ trợ nhiều file PHP</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="clearCode()" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 border border-gray-300 px-3 py-2 rounded">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Xóa
            </button>
            <button @click="runCode()" :disabled="running"
                class="flex items-center gap-1.5 bg-green-600 text-white px-6 py-2 rounded font-medium hover:bg-green-700 disabled:opacity-50 transition">
                <span x-show="!running" class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                    </svg>
                    Chạy Code
                </span>
                <span x-show="running" x-cloak class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Đang chạy...
                </span>
            </button>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="flex items-center gap-2 mb-3 flex-wrap">
        <span x-show="status === 'queued' || status === 'running'" x-cloak
            class="flex items-center gap-1 text-sm text-yellow-700 bg-yellow-100 px-2 py-1 rounded">
            <svg class="w-3.5 h-3.5 animate-spin flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            Đang thực thi...</span>
        <span x-show="status === 'success'" x-cloak
            class="flex items-center gap-1 text-sm text-green-700 bg-green-100 px-2 py-1 rounded">
            <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Thành công</span>
        <span x-show="status === 'error'" x-cloak
            class="flex items-center gap-1 text-sm text-red-700 bg-red-100 px-2 py-1 rounded">
            <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Lỗi</span>
        <span x-show="status === 'timeout'" x-cloak
            class="flex items-center gap-1 text-sm text-orange-700 bg-orange-100 px-2 py-1 rounded">
            <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Timeout</span>
        <span x-text="executionTime" class="text-xs text-gray-400"></span>
    </div>

    <div class="grid grid-cols-5 gap-4">
        <!-- Editor Panel -->
        <div class="col-span-3">
            @include('partials._filetabs_html')
            <!-- Monaco Editor -->
            <div class="editor-wrap" style="border-radius:0 0 4px 4px">
                <div id="editor"></div>
            </div>

            <!-- Errors -->
            <div x-show="errors" x-cloak class="mt-2 bg-red-50 border border-red-200 rounded p-3">
                <p class="text-sm font-medium text-red-700 mb-1">Lỗi PHP:</p>
                <pre x-text="errors" class="text-xs text-red-600 overflow-auto max-h-40 font-mono"></pre>
            </div>
        </div>

        <!-- Preview Panel -->
        <div class="col-span-2">
            <div class="bg-gray-100 text-gray-500 text-xs px-3 py-1 rounded-t flex items-center justify-between">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                    Output Preview
                </span>
                <button @click="previewUrl = ''" x-show="previewUrl" x-cloak class="text-gray-400 hover:text-gray-600 leading-none">&times;</button>
            </div>
            <iframe id="preview-frame" sandbox="allow-forms allow-scripts allow-same-origin"
                x-bind:src="previewUrl || 'about:blank'"></iframe>
        </div>
    </div>

    <!-- Quick Examples -->
    <div class="mt-4">
        <p class="text-xs text-gray-500 mb-2">Ví dụ nhanh:</p>
        <div class="flex gap-2 flex-wrap">
            <button @click="loadExample('hello')" class="text-xs border border-gray-300 px-2 py-1 rounded hover:bg-gray-50">Hello World</button>
            <button @click="loadExample('array')" class="text-xs border border-gray-300 px-2 py-1 rounded hover:bg-gray-50">Arrays</button>
            <button @click="loadExample('function')" class="text-xs border border-gray-300 px-2 py-1 rounded hover:bg-gray-50">Functions</button>
            <button @click="loadExample('html')" class="text-xs border border-gray-300 px-2 py-1 rounded hover:bg-gray-50">HTML Output</button>
            <button @click="loadExample('multifile')" class="flex items-center gap-1 text-xs border border-blue-300 px-2 py-1 rounded hover:bg-blue-50 text-blue-600">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                Multi-file
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js"></script>
@include('partials._multifile_js')
<?php
$phpExamples = [
    'hello' => "<?php\necho \"Hello, World!\";\necho \"\\nChào mừng đến với PHP Sandbox!\";\n",
    'array' => "<?php\n\$fruits = ['Táo', 'Chuối', 'Cam', 'Xoài'];\n\nforeach (\$fruits as \$index => \$fruit) {\n    echo (\$index + 1) . \". \$fruit\\n\";\n}\n\necho \"\\nTổng: \" . count(\$fruits) . \" loại trái cây\";\n",
    'function' => "<?php\nfunction tinhTong(\$a, \$b) {\n    return \$a + \$b;\n}\n\nfunction chaoHoi(\$ten) {\n    return \"Xin chào, \$ten!\";\n}\n\necho tinhTong(10, 20) . \"\\n\";\necho chaoHoi(\"PHP Sandbox\");\n",
    'html' => "<?php\n\$items = ['PHP', 'Laravel', 'MySQL', 'Redis'];\n?>\n<!DOCTYPE html>\n<html>\n<head><style>li { color: #3b82f6; }</style></head>\n<body>\n<h2>Tech Stack</h2>\n<ul>\n<?php foreach (\$items as \$item): ?>\n    <li><?= \$item ?></li>\n<?php endforeach; ?>\n</ul>\n</body>\n</html>\n",
    'loop' => "<?php\nfor (\$i = 1; \$i <= 5; \$i++) {\n    echo \"\$i\\n\";\n}\n",
];
$multifileExample = [
    'index.php' => "<!DOCTYPE html>\n<html lang=\"vi\">\n<head><meta charset=\"UTF-8\"><title>Multi-file Demo</title></head>\n<body>\n<h2>Nhập 2 số để tính</h2>\n<form method=\"POST\" action=\"calculate.php\">\n    Số A: <input type=\"number\" name=\"a\" value=\"0\"><br><br>\n    Số B: <input type=\"number\" name=\"b\" value=\"0\"><br><br>\n    <input type=\"submit\" name=\"submit\" value=\"Tính\">\n</form>\n</body>\n</html>\n",
    'calculate.php' => "<?php\n\$a = \$_POST['a'] ?? 0;\n\$b = \$_POST['b'] ?? 0;\n?>\n<!DOCTYPE html>\n<html lang=\"vi\">\n<head><meta charset=\"UTF-8\"><title>Kết quả</title></head>\n<body>\n<h2>Kết quả tính toán</h2>\n<p>A = <?= \$a ?>, B = <?= \$b ?></p>\n<p>Tổng: <strong><?= \$a + \$b ?></strong></p>\n<p>Tích: <strong><?= \$a * \$b ?></strong></p>\n<a href=\"javascript:history.back()\">← Quay lại</a>\n</body>\n</html>\n",
];
?>
<script>
let monacoEditor;
const examples = @json($phpExamples);
const multifileExample = @json($multifileExample);

require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs' } });
require(['vs/editor/editor.main'], function() {
    monacoEditor = monaco.editor.create(document.getElementById('editor'), {
        value: '<?php\n\n// Viết code PHP của bạn ở đây\necho "Hello, World!";\n',
        language: 'php',
        theme: 'vs',
        fontSize: 14,
        minimap: { enabled: false },
        scrollBeyondLastLine: false,
        automaticLayout: true,
        wordWrap: 'on',
    });
});

function sandboxApp(contextType, contextId) {
    return Object.assign(createMultiFileMixin(null), {
        running: false,
        status: '',
        errors: '',
        previewUrl: '',
        executionTime: '',

        init() {},

        clearCode() {
            this.files = [{ name: 'index.php', content: '<?php\n\n' }];
            this.activeIdx = 0;
            this.renamingIdx = -1;
            if (monacoEditor) monacoEditor.setValue('<?php\n\n');
            this.status = '';
            this.errors = '';
            this.previewUrl = '';
            this.executionTime = '';
        },

        loadExample(name) {
            if (name === 'multifile') {
                this.files = Object.entries(multifileExample).map(([n,c]) => ({ name: n, content: c }));
                this.activeIdx = 0;
                this.renamingIdx = -1;
                if (monacoEditor) monacoEditor.setValue(this.files[0].content);
            } else if (monacoEditor && examples[name]) {
                this.syncActiveFile();
                this.files[this.activeIdx].content = examples[name];
                monacoEditor.setValue(examples[name]);
            }
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
                    body: JSON.stringify({
                        files: filesMap,
                        code: this.files[this.activeIdx].content,
                        context_type: 'free',
                        context_id: null
                    })
                });

                if (!res.ok) throw new Error((await res.json()).message || 'Server error');
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
            if (attempts >= 30) {
                this.status = 'timeout';
                this.running = false;
            }
        }
    });
}
</script>
@endpush
