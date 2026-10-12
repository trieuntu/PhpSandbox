<script>
// Draggable divider between the code editor and the preview (desktop only); the ratio is remembered.
(function () {
    const KEY = 'sandbox.split';
    const MIN = 25, MAX = 80, DEFAULT = 62;
    const desktop = window.matchMedia('(min-width: 1024px)');
    const clamp = (v) => Math.min(MAX, Math.max(MIN, v));

    function init() {
        const grid = document.querySelector('.workspace-grid');
        const gutter = grid && grid.querySelector('.split-gutter');
        if (!gutter) return;

        let pct = clamp(parseFloat(localStorage.getItem(KEY)) || DEFAULT);
        const apply = () => {
            grid.style.gridTemplateColumns = desktop.matches ? 'minmax(0,' + pct + 'fr) 10px minmax(0,' + (100 - pct) + 'fr)' : '';
        };
        const save = () => localStorage.setItem(KEY, pct.toFixed(1));
        apply();
        desktop.addEventListener('change', apply);

        gutter.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            gutter.setPointerCapture(e.pointerId);
            grid.classList.add('dragging');
            gutter.classList.add('dragging');
            const rect = grid.getBoundingClientRect();
            const move = (ev) => { pct = clamp(((ev.clientX - rect.left) / rect.width) * 100); apply(); };
            const up = () => {
                gutter.removeEventListener('pointermove', move);
                gutter.removeEventListener('pointerup', up);
                gutter.removeEventListener('pointercancel', up);
                grid.classList.remove('dragging');
                gutter.classList.remove('dragging');
                save();
            };
            gutter.addEventListener('pointermove', move);
            gutter.addEventListener('pointerup', up);
            gutter.addEventListener('pointercancel', up);
        });

        gutter.addEventListener('dblclick', () => { pct = DEFAULT; apply(); save(); });
        gutter.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
            e.preventDefault();
            pct = clamp(pct + (e.key === 'ArrowRight' ? 2 : -2));
            apply();
            save();
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
