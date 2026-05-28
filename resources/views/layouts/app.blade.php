<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PHP Sandbox') — PHP Sandbox</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col" x-data="heartbeatApp()" x-init="init()">

<!-- Inactivity Warning Banner -->
<div x-show="showWarning" x-cloak class="fixed top-0 left-0 right-0 z-50 bg-yellow-500 text-white px-4 py-2 text-center">
    <span class="inline-flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        Bạn sẽ bị đăng xuất sau <span x-text="remainingSeconds"></span> giây do không hoạt động.
    </span>
</div>

<!-- Navigation -->
<nav class="bg-white border-b border-gray-200 shadow-sm" x-data="{ mobileOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo + desktop links -->
            <div class="flex items-center">
                <a href="{{ route('student.home') }}" class="flex items-center gap-2 text-blue-600 font-bold text-lg sm:text-xl">
                    <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                    </svg>
                    <span class="hidden sm:inline">PHP Sandbox</span>
                </a>
                <div class="hidden md:flex ml-6 space-x-1">
                    <a href="{{ route('student.home') }}" class="px-3 py-2 text-sm font-medium rounded-md transition {{ request()->routeIs('student.home') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">Dashboard</a>
                    <a href="{{ route('student.classes.index') }}" class="px-3 py-2 text-sm font-medium rounded-md transition {{ request()->routeIs('student.classes.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">Lớp học</a>
                    <a href="{{ route('student.sandbox.editor') }}" class="px-3 py-2 text-sm font-medium rounded-md transition {{ request()->routeIs('student.sandbox.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">Sandbox</a>
                </div>
            </div>

            <!-- Desktop right -->
            <div class="hidden md:flex items-center gap-3">
                <span class="text-sm text-gray-600 max-w-[160px] truncate">{{ auth()->user()->name }}</span>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Admin Panel</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">Đăng xuất</button>
                </form>
            </div>

            <!-- Mobile hamburger -->
            <div class="flex items-center md:hidden">
                <button @click="mobileOpen = !mobileOpen" class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition" aria-label="Menu">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="mobileOpen" x-cloak class="md:hidden border-t border-gray-100 bg-white">
        <div class="px-4 pt-2 pb-3 space-y-1">
            <a href="{{ route('student.home') }}" class="block px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('student.home') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">Dashboard</a>
            <a href="{{ route('student.classes.index') }}" class="block px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('student.classes.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">Lớp học</a>
            <a href="{{ route('student.sandbox.editor') }}" class="block px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('student.sandbox.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">Sandbox</a>
        </div>
        <div class="px-4 pb-3 border-t border-gray-100 pt-3 flex flex-col gap-2">
            <span class="text-sm text-gray-600 font-medium truncate">{{ auth()->user()->name }}</span>
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Admin Panel</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">Đăng xuất</button>
            </form>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif
    @if(session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">{{ session('warning') }}</div>
    @endif
</div>

<!-- Main Content -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1">
    @yield('content')
</main>

<!-- Footer -->
<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                </svg>
                <span class="font-semibold text-gray-700">PHP Sandbox</span>
                <span>&middot;</span>
                <span>Phiên bản 1.0.0</span>
            </div>
            <div class="text-center sm:text-right">
                Phát triển bởi <span class="font-semibold text-gray-700">Nguyễn Hải Triều</span>
                &nbsp;&middot;&nbsp;Khoa Công nghệ Thông tin &nbsp;&middot;&nbsp;Trường Đại học Nha Trang
                <span class="ml-1">&copy; 2026</span>
            </div>
        </div>
    </div>
</footer>

<script>
function heartbeatApp() {
    return {
        showWarning: false,
        remainingSeconds: 0,
        heartbeatTimeout: null,

        init() {
            window.addEventListener('mousemove', () => this.resetHeartbeat());
            window.addEventListener('keypress', () => this.resetHeartbeat());

            // Heartbeat every 60 seconds
            setInterval(() => { this.sendHeartbeat(); }, 60000);
        },

        resetHeartbeat() {
            clearTimeout(this.heartbeatTimeout);
            this.heartbeatTimeout = setTimeout(() => { this.sendHeartbeat(); }, 5000);
        },

        sendHeartbeat() {
            fetch('/api/heartbeat', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.remaining_seconds < 120) {
                    this.showWarning = true;
                    this.remainingSeconds = data.remaining_seconds;
                } else {
                    this.showWarning = false;
                }
            })
            .catch(() => {});
        }
    }
}
</script>
@stack('scripts')
</body>
</html>
