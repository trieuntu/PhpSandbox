<script>
/**
 * Shared multi-file mixin for all PHP sandbox editors.
 * Usage: Object.assign(createMultiFileMixin(initialFiles), { ...appSpecificState })
 *
 * @param {Array|null} initialFiles  [{name, content}, ...] or null for default
 */
function createMultiFileMixin(initialFiles) {
    return {
        files: (initialFiles && initialFiles.length)
            ? initialFiles
            : [{ name: 'index.php', content: '\x3C?php\n\n// Viết code PHP của bạn ở đây\necho "Hello, World!";\n' }],
        activeIdx: 0,
        renamingIdx: -1,

        syncActiveFile() {
            if (monacoEditor && this.files[this.activeIdx]) {
                this.files[this.activeIdx].content = monacoEditor.getValue();
            }
        },

        switchFile(idx) {
            this.syncActiveFile();
            this.activeIdx = idx;
            if (monacoEditor) {
                monacoEditor.setValue(this.files[idx].content);
                monacoEditor.focus();
            }
        },

        addFile() {
            this.syncActiveFile();
            const name = `file${this.files.length + 1}.php`;
            this.files.push({ name, content: '\x3C?php\n\n' });
            this.activeIdx = this.files.length - 1;
            if (monacoEditor) {
                monacoEditor.setValue(this.files[this.activeIdx].content);
            }
            // Auto-open rename for the new file
            this.renamingIdx = this.activeIdx;
            this.$nextTick(() => {
                const inp = document.querySelector('.file-tab input');
                if (inp) { inp.focus(); inp.select(); }
            });
        },

        deleteFile(idx) {
            if (this.files.length <= 1) return;
            this.files.splice(idx, 1);
            this.activeIdx = Math.min(this.activeIdx, this.files.length - 1);
            if (monacoEditor) {
                monacoEditor.setValue(this.files[this.activeIdx].content);
            }
        },

        startRename(idx) {
            this.renamingIdx = idx;
            this.$nextTick(() => {
                const inp = document.querySelector('.file-tab input');
                if (inp) { inp.focus(); inp.select(); }
            });
        },

        finishRename(event, idx) {
            const val = event.target.value.trim();
            if (val && !val.includes('/') && !val.includes('\\')) {
                this.files[idx].name = val.endsWith('.php') ? val : val + '.php';
            }
            this.renamingIdx = -1;
        },

        /** Returns { 'index.php': '...', 'abc.php': '...' } after syncing active file */
        buildFilesMap() {
            this.syncActiveFile();
            const map = {};
            this.files.forEach(f => { map[f.name] = f.content; });
            return map;
        }
    };
}
</script>
