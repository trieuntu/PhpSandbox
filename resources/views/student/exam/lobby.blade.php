@extends('layouts.app')
@section('title', $exam->title . ' — Lobby')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-orange-50 border-b border-orange-200 p-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $exam->title }}</h1>
            <p class="text-gray-600 mt-1">{{ $exam->class->name }}</p>
        </div>

        <!-- Exam Info -->
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Bắt đầu</p>
                    <p class="font-semibold text-gray-800 mt-1">{{ $exam->starts_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Kết thúc</p>
                    <p class="font-semibold text-gray-800 mt-1">{{ $exam->ends_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($exam->time_limit_minutes)
                <div class="bg-orange-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Thời gian làm bài</p>
                    <p class="font-semibold text-orange-700 mt-1">{{ $exam->time_limit_minutes }} phút</p>
                </div>
                @endif
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Trạng thái</p>
                    @php
                        $now = now();
                        $isOpen = $now->between($exam->starts_at, $exam->ends_at);
                        $isPast = $now->gt($exam->ends_at);
                    @endphp
                    @if($isOpen)
                        <p class="font-semibold text-green-600 mt-1 flex items-center gap-1">
                            <svg class="w-4 h-4 text-green-500" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6" /></svg>
                            Đang mở
                        </p>
                    @elseif($isPast)
                        <p class="font-semibold text-gray-500 mt-1">Đã kết thúc</p>
                    @else
                        <p class="flex items-center gap-1 font-semibold text-yellow-600 mt-1">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Chưa mở
                        </p>
                    @endif
                </div>
            </div>

            @if($exam->instructions)
            <div class="bg-blue-50 border border-blue-200 rounded p-4">
                <p class="flex items-center gap-1 font-medium text-blue-800 mb-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                    Hướng dẫn thi:
                </p>
                <div class="text-sm text-gray-700 whitespace-pre-line">{{ $exam->instructions }}</div>
            </div>
            @endif

            <!-- Previous Attempt -->
            @if($attempt)
            <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
                <p class="flex items-center gap-1 font-medium text-yellow-800">
                    <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    Bạn đã bắt đầu bài thi này
                </p>
                <p class="text-sm text-yellow-700 mt-1">Bắt đầu lúc: {{ $attempt->started_at->format('d/m/Y H:i') }}</p>
                @if($attempt->submitted_at)
                    <p class="flex items-center gap-1 text-sm text-green-700 mt-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Đã nộp lúc: {{ $attempt->submitted_at->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="pt-2">
                @if($isPast)
                    <p class="text-gray-500 text-center">Bài thi đã kết thúc.</p>
                @elseif(!$isOpen)
                    <p class="text-yellow-600 text-center">Bài thi chưa bắt đầu.</p>
                @elseif($attempt && $attempt->submitted_at)
                    <a href="{{ route('student.exams.submitted', $exam) }}"
                       class="w-full inline-block text-center bg-gray-600 text-white px-6 py-3 rounded font-medium hover:bg-gray-700 transition">
                        Xem kết quả
                    </a>
                @elseif($attempt && !$attempt->submitted_at)
                    <a href="{{ route('student.exams.editor', $exam) }}"
                       class="w-full inline-block text-center bg-orange-500 text-white px-6 py-3 rounded font-medium hover:bg-orange-600 transition">
                        Tiếp tục làm bài →
                    </a>
                @else
                    <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                        @csrf
                        <button type="submit"
                            class="flex items-center justify-center gap-2 w-full bg-orange-500 text-white px-6 py-3 rounded font-medium hover:bg-orange-600 transition text-lg"
                            onclick="return confirm('Bạn chắc chắn muốn bắt đầu bài thi? Thời gian sẽ được tính từ lúc này.')">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                            Bắt đầu thi
                        </button>
                    </form>
                    @if($exam->time_limit_minutes)
                        <p class="text-xs text-gray-400 text-center mt-2">
                            Sau khi bắt đầu, bạn có {{ $exam->time_limit_minutes }} phút để hoàn thành.
                        </p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
