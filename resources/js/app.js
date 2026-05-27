// Alpine.js is loaded via CDN in layouts/app.blade.php
// This file is the entry point for Vite — add any app-wide JS here

document.addEventListener('DOMContentLoaded', () => {
    // Global CSRF token setup for fetch requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
        window._csrfToken = csrfToken;
    }
});
