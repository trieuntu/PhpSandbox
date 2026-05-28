@extends('layouts.app')
@section('title', $class->name)
@section('content')
<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="text-sm text-gray-500">
        <a href="{{ route('student.classes.index') }}" class="hover:text-blue-600">Lớp học</a>
        <span class="mx-2">›</span>
        <span class="text-gray-800">{{ $class->name }}</span>
    </nav>

    <div>
        <h1 class="text-2xl font-bold text-gray-800">{{ $class->name }}</h1>
        @if($class->description)
            <p class="text-gray-600 mt-1">{{ $class->description }}</p>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Assignments -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    Bài tập
                </h2>
            </div>
            @if($class->assignments->count())
            <div class="divide-y divide-gray-100">
                @foreach($class->assignments as $assignment)
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-800">{{ $assignment->title }}</h3>
                            @if($assignment->instructions)
                                <p class="text-sm text-gray-500 mt-1">{{ Str::limit($assignment->instructions, 100) }}</p>
                            @endif
                            @if($assignment->due_at)
                                <p class="text-xs text-orange-600 mt-1">Hạn nộp: {{ $assignment->due_at->format('d/m/Y H:i') }}</p>
                            @endif
                        </div>
                        <a href="{{ route('student.classes.assignment', [$class, $assignment]) }}"
                           class="ml-3 flex-shrink-0 bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
                            Làm bài
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="p-8 text-center text-gray-400">
                <p>Chưa có bài tập nào.</p>
            </div>
            @endif
        </div>

        <!-- Exams -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                    Bài thi
                </h2>
            </div>
            @if($class->exams->count())
            <div class="divide-y divide-gray-100">
                @foreach($class->exams as $exam)
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-800">{{ $exam->title }}</h3>
                            <div class="flex gap-3 mt-1 text-xs text-gray-500">
                                @if($exam->time_limit_minutes)
                                <span>{{ $exam->time_limit_minutes }} phút</span>
                                @endif
                                <span>{{ $exam->starts_at->format('d/m H:i') }} – {{ $exam->ends_at->format('d/m H:i') }}</span>
                            </div>
                            @php
                                $now = now();
                                $isOpen = $now->between($exam->starts_at, $exam->ends_at);
                                $isPast = $now->gt($exam->ends_at);
                            @endphp
                            @if($isOpen)
                                <span class="inline-block mt-1 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">Đang mở</span>
                            @elseif($isPast)
                                <span class="inline-block mt-1 text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Đã kết thúc</span>
                            @else
                                <span class="inline-block mt-1 text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">Chưa mở</span>
                            @endif
                        </div>
                        @if($isOpen)
                        <a href="{{ route('student.exams.lobby', $exam) }}"
                           class="ml-3 flex-shrink-0 bg-orange-500 text-white px-3 py-1 rounded text-sm hover:bg-orange-600 transition">
                            Vào thi
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="p-8 text-center text-gray-400">
                <p>Chưa có bài thi nào.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
