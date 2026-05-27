<style>
[x-cloak] { display: none; }
.file-tab { cursor:pointer; padding:4px 12px; font-size:12px; border-radius:4px 4px 0 0; user-select:none; display:flex;align-items:center;gap:4px; }
.file-tab.active { background:#fff; border:1px solid #e5e7eb; border-bottom:1px solid #fff; color:#1f2937; font-weight:600; }
.file-tab:not(.active) { background:#f3f4f6; border:1px solid transparent; color:#6b7280; }
.file-tab:not(.active):hover { background:#e9eaf0; }
.file-tab .del-btn { opacity:0; font-size:10px; line-height:1; color:#9ca3af; cursor:pointer; }
.file-tab:hover .del-btn { opacity:1; }
.editor-wrap { border:1px solid #e5e7eb; border-radius:0 0 4px 4px; }
#editor { height: {{ $editorHeight ?? '480px' }}; border:0; border-radius:0 0 4px 4px; }
#preview-frame { width: 100%; height: {{ $previewHeight ?? '520px' }}; border: 1px solid #e5e7eb; border-radius: 4px; background: #fff; }
</style>
