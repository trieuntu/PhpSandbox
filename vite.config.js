import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/sandbox.js'],
            refresh: true,
        }),
        // TinyMCE loads skins/plugins at runtime, so it is served as static files under /build/tinymce.
        viteStaticCopy({
            targets: [
                { src: 'node_modules/tinymce/tinymce.min.js', dest: 'tinymce' },
                { src: 'node_modules/tinymce/icons', dest: 'tinymce' },
                { src: 'node_modules/tinymce/models', dest: 'tinymce' },
                { src: 'node_modules/tinymce/plugins', dest: 'tinymce' },
                { src: 'node_modules/tinymce/skins', dest: 'tinymce' },
                { src: 'node_modules/tinymce/themes', dest: 'tinymce' },
            ],
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
