<style>
[x-cloak] { display: none; }
.file-tab { cursor:pointer; padding:4px 12px; font-size:12px; border-radius:4px 4px 0 0; user-select:none; display:flex;align-items:center;gap:4px; }
.file-tab.active { background:#fff; border:1px solid #e5e7eb; border-bottom:1px solid #fff; color:#1f2937; font-weight:600; }
.file-tab:not(.active) { background:#f3f4f6; border:1px solid transparent; color:#6b7280; }
.file-tab:not(.active):hover { background:#e9eaf0; }
.file-tab .del-btn { opacity:0; font-size:10px; line-height:1; color:#9ca3af; cursor:pointer; }
.file-tab:hover .del-btn { opacity:1; }
.editor-wrap { border:1px solid #e5e7eb; border-radius:0 0 4px 4px; flex:1; display:flex; flex-direction:column; min-height:0; }
#editor { flex:1; border:0; border-radius:0 0 4px 4px; min-height:0; }
#preview-frame { flex:1; border:1px solid #e5e7eb; border-radius:0 0 4px 4px; background:#fff; min-height:0; }
.sandbox-panel { display:flex; flex-direction:column; min-height:0; }

/* Editor + preview workspace: stacked on small screens, resizable split from 1024px */
.workspace-grid { display:grid; grid-template-columns:minmax(0,1fr); gap:.75rem; }
.workspace-grid > .panel-editor { min-height:420px; min-height:max(420px, 65vh); }
.workspace-grid > .panel-preview { min-height:360px; }
.split-gutter { display:none; }
.workspace-page { display:flex; flex-direction:column; }
@media (min-width:1024px) {
    .workspace-page { height:calc(100vh - 7.5rem); height:calc(100dvh - 7.5rem); min-height:520px; }
    .workspace-page > .workspace-grid { flex:1; min-height:0; }
    .workspace-fixed { height:calc(100vh - 15rem); height:calc(100dvh - 15rem); min-height:480px; }
    .workspace-grid { grid-template-columns:minmax(0,62fr) 10px minmax(0,38fr); gap:0; }
    .workspace-grid > .panel-editor, .workspace-grid > .panel-preview { min-height:0; }
    .split-gutter { display:block; position:relative; cursor:col-resize; touch-action:none; outline:none; }
    .split-gutter::after { content:''; position:absolute; top:0; bottom:0; left:4px; width:2px; border-radius:2px; background:#e5e7eb; transition:background .15s, left .15s, width .15s; }
    .split-gutter:hover::after, .split-gutter:focus-visible::after, .split-gutter.dragging::after { background:#3b82f6; left:3px; width:4px; }
    .workspace-grid.dragging { user-select:none; }
    .workspace-grid.dragging iframe { pointer-events:none; }
    .workspace-grid > .panel-editor { padding-right:.5rem; }
    .workspace-grid > .panel-preview { padding-left:.5rem; }
}
</style>
