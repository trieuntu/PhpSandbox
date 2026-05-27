<!-- File Tabs -->
<div class="flex items-end gap-1 flex-wrap mb-0 pt-1"
    style="background:#f9fafb;border:1px solid #e5e7eb;border-bottom:none;border-radius:4px 4px 0 0;padding:6px 8px 0;">
    <template x-for="(file, idx) in files" :key="idx">
        <div class="file-tab" :class="{ active: activeIdx === idx }" @click="switchFile(idx)">
            <span x-show="renamingIdx !== idx" x-text="file.name" @dblclick.stop="startRename(idx)"></span>
            <input x-show="renamingIdx === idx" x-cloak
                :value="file.name"
                @blur="finishRename($event, idx)"
                @keydown.enter="$event.target.blur()"
                @keydown.escape="renamingIdx = -1"
                @click.stop
                style="width:90px;font-size:12px;border:1px solid #6366f1;border-radius:2px;padding:0 2px;">
            <span class="del-btn" x-show="files.length > 1" @click.stop="deleteFile(idx)">✕</span>
        </div>
    </template>
    <button @click="addFile()"
        class="text-xs text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-50"
        title="Thêm file mới (double-click tab để đổi tên)">+ File</button>
</div>
