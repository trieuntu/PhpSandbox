@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Chào mừng, {{ auth()->user()->name }}</h1>

    <!-- Announcements -->
    @if($announcements->count())
    <div>
        <h2 class="text-lg font-semibold mb-3 text-gray-700 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
            Thông báo
        </h2>
        <div class="space-y-3">
            @foreach($announcements as $announcement)
            <div class="bg-white rounded-lg border p-4 {{ $announcement->is_pinned ? 'border-blue-300 bg-blue-50' : 'border-gray-200' }}">
                <h3 class="font-semibold text-gray-800 flex items-center gap-1">
                    @if($announcement->is_pinned)
                    <svg class="w-4 h-4 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
                    @endif
                    {{ $announcement->title }}
                </h3>
                <p class="text-gray-600 text-sm mt-1 whitespace-pre-line">{{ $announcement->content }}</p>
                <p class="text-gray-400 text-xs mt-2">{{ $announcement->created_at?->diffForHumans() }} — {{ $announcement->creator->name }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Open Exams -->
    @if($openExams->count())
    <div>
        <h2 class="text-lg font-semibold mb-3 text-gray-700 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
            Bài thi đang mở
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($openExams as $exam)
            <div class="bg-white rounded-lg border border-orange-200 p-4">
                <h3 class="font-semibold text-gray-800">{{ $exam->title }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $exam->class->name }}</p>
                @if($exam->time_limit_minutes)
                    <p class="flex items-center gap-1 text-sm text-gray-500 mt-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Thời gian: {{ $exam->time_limit_minutes }} phút
                    </p>
                @endif
                <p class="flex items-center gap-1 text-orange-600 text-sm mt-2">
                    <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Đóng: {{ $exam->ends_at->format('d/m/Y H:i') }}
                </p>
                <a href="{{ route('student.exams.lobby', $exam) }}"
                   class="mt-3 inline-block bg-orange-500 text-white px-4 py-2 rounded text-sm hover:bg-orange-600 transition">
                    Vào thi
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Classes -->
    <div>
        <h2 class="text-lg font-semibold mb-3 text-gray-700 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
            Lớp học của bạn
        </h2>
        @if($classes->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($classes as $class)
            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:border-blue-300 transition">
                <h3 class="font-semibold text-gray-800">{{ $class->name }}</h3>
                @if($class->description)
                    <p class="text-gray-500 text-sm mt-1">{{ Str::limit($class->description, 80) }}</p>
                @endif
                <div class="mt-3 flex gap-4 text-sm text-gray-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        {{ $class->assignments->count() }} bài tập
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                        {{ $class->exams->count() }} bài thi
                    </span>
                </div>
                <a href="{{ route('student.classes.show', $class) }}"
                   class="mt-3 inline-block bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition">
                    Vào lớp
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
            <p class="text-gray-500">Bạn chưa được enroll vào lớp học nào.</p>
            <p class="text-gray-400 text-sm mt-2">Liên hệ giảng viên để được thêm vào lớp.</p>
        </div>
        @endif
    </div>

    <!-- Quick Sandbox Link -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">PHP Sandbox tự do</h3>
                    <p class="text-gray-500 text-sm mt-0.5">Thực hành PHP không giới hạn</p>
                </div>
            </div>
            <a href="{{ route('student.sandbox.editor') }}"
               class="bg-gray-800 text-white px-4 py-2 rounded text-sm hover:bg-gray-900 transition">
                Mở Sandbox
            </a>
        </div>
    </div>
</div>
@endsection
