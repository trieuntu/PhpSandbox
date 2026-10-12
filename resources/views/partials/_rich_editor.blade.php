{{-- Include once per page; turns every textarea.rich-editor into a TinyMCE editor. --}}
@push('scripts')
<script src="{{ asset('build/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const uploadUrl = @json(route('admin.uploads.image', [], false));

    tinymce.init({
        selector: 'textarea.rich-editor',
        license_key: 'gpl',
        base_url: @json(asset('build/tinymce')),
        suffix: '.min',
        height: 480,
        promotion: false,
        branding: false,
        menubar: 'edit insert format table',
        plugins: 'lists link image table code codesample autoresize charmap searchreplace wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image table | codesample code | removeformat',
        codesample_languages: [
            { text: 'PHP', value: 'php' },
            { text: 'HTML', value: 'markup' },
            { text: 'SQL', value: 'sql' },
            { text: 'JavaScript', value: 'javascript' },
            { text: 'CSS', value: 'css' },
            { text: 'Bash', value: 'bash' },
            { text: 'JSON', value: 'json' },
        ],
        relative_urls: false,
        convert_urls: false,
        paste_data_images: true,
        automatic_uploads: true,
        images_file_types: 'jpg,jpeg,png,gif,webp',
        images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
            const body = new FormData();
            body.append('file', blobInfo.blob(), blobInfo.filename());
            fetch(uploadUrl, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.location) {
                        const msg = data.errors?.file?.[0] || data.message || 'Tải ảnh thất bại (tối đa 5MB, png/jpg/gif/webp).';
                        return reject({ message: msg, remove: true });
                    }
                    resolve(data.location);
                })
                .catch(() => reject({ message: 'Không thể kết nối máy chủ để tải ảnh.', remove: true }));
        }),
        content_style: 'body{font-family:Inter,system-ui,sans-serif;font-size:14px} img{max-width:100%;height:auto} pre{background:#f3f4f6;padding:8px;border-radius:4px}',
    });
})();
</script>
@endpush
