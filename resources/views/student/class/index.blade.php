@extends('layouts.app')
@section('title', 'Lớp học')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        <svg class="w-6 h-6 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        Lớp học của bạn
    </h1>

    @if($classes->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($classes as $class)
        <div class="bg-white rounded-lg border border-gray-200 p-5 hover:border-blue-300 transition">
            <h3 class="font-bold text-gray-800 text-lg">{{ $class->name }}</h3>
            @if($class->description)
                <p class="text-gray-500 text-sm mt-2">{{ $class->description }}</p>
            @endif
            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <div class="bg-gray-50 rounded p-2 text-center">
                    <p class="font-semibold text-gray-700">{{ $class->assignments->count() }}</p>
                    <p class="text-gray-500 text-xs">Bài tập</p>
                </div>
                <div class="bg-gray-50 rounded p-2 text-center">
                    <p class="font-semibold text-gray-700">{{ $class->exams->count() }}</p>
                    <p class="text-gray-500 text-xs">Bài thi</p>
                </div>
            </div>
            <a href="{{ route('student.classes.show', $class) }}"
               class="mt-4 w-full inline-block text-center bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition">
                Vào lớp học
            </a>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <p class="text-gray-500 text-lg">Bạn chưa được enroll vào lớp học nào.</p>
        <p class="text-gray-400 text-sm mt-2">Liên hệ giảng viên để được thêm vào lớp.</p>
    </div>
    @endif
</div>
@endsection
