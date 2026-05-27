@extends('layouts.app')
@section('title', 'Đã nộp bài')
@section('content')
<div class="max-w-2xl mx-auto text-center py-12">
    <div class="bg-white rounded-lg border border-green-200 p-10">
        <div class="w-20 h-20 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Bài thi đã được nộp!</h1>
        <p class="text-gray-600 mb-2">{{ $exam->title }}</p>
        <p class="text-gray-500 text-sm mb-6">{{ $exam->class->name }}</p>

        @if($submission)
        <div class="bg-gray-50 rounded p-4 mb-6 text-left">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-gray-500">Thời gian nộp</p>
                    <p class="font-medium text-gray-800">{{ $submission->submitted_at->format('d/m/Y H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Trạng thái</p>
                    <p class="font-medium {{ $submission->execution_status === 'success' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $submission->execution_status }}
                    </p>
                </div>
                @if($submission->execution_time_ms)
                <div>
                    <p class="text-gray-500">Thời gian thực thi</p>
                    <p class="font-medium text-gray-800">{{ $submission->execution_time_ms }}ms</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-6 text-sm text-blue-700">
            <p>Bài làm của bạn đã được ghi nhận. Giảng viên sẽ xem xét và phản hồi sớm nhất có thể.</p>
        </div>

        <div class="flex gap-3 justify-center">
            <a href="{{ route('student.home') }}"
               class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                Về Dashboard
            </a>
            <a href="{{ route('student.classes.show', $exam->class) }}"
               class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300 transition">
                Về lớp học
            </a>
        </div>
    </div>
</div>
@endsection
