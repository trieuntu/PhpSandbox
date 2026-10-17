@extends('layouts.app')
@section('title', $exam->title . ' — Lobby')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <!-- Header -->
        <div class="bg-orange-50 border-b border-orange-200 p-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $exam->title }}</h1>
            <p class="text-gray-600 mt-1 font-medium">{{ $exam->class->name }}</p>
        </div>

        <!-- Exam Info -->
        <div class="p-6 space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-3.5 border border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Bắt đầu</p>
                    <p class="font-semibold text-gray-800 mt-1">{{ $exam->starts_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3.5 border border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Kết thúc</p>
                    <p class="font-semibold text-gray-800 mt-1">{{ $exam->ends_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($exam->time_limit_minutes)
                <div class="bg-orange-50/70 rounded-lg p-3.5 border border-orange-100">
                    <p class="text-xs text-orange-700 uppercase tracking-wide font-medium">Thời gian làm bài</p>
                    <p class="font-semibold text-orange-800 mt-1">{{ $exam->time_limit_minutes }} phút</p>
                </div>
                @endif
                <div class="bg-gray-50 rounded-lg p-3.5 border border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Trạng thái</p>
                    @php
                        $now = now();
                        $isOpen = $exam->isOpen();
                        $isPast = $now->gt($exam->ends_at);
                    @endphp
                    @if($isOpen)
                        <p class="font-semibold text-green-600 mt-1 flex items-center gap-1.5">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                            </span>
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
            <div class="bg-blue-50/80 border border-blue-200 rounded-lg p-4">
                <p class="flex items-center gap-1.5 font-medium text-blue-900 mb-2">
                    <svg class="w-4 h-4 text-blue-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                    Hướng dẫn thi:
                </p>
                <div class="text-sm text-gray-700 rich-content leading-relaxed">{!! \App\Support\HtmlSanitizer::render($exam->instructions) !!}</div>
            </div>
            @endif

            <!-- Previous Attempt -->
            @if($attempt)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="flex items-center gap-1.5 font-medium text-yellow-800">
                    <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    Bạn đã bắt đầu bài thi này
                </p>
                <p class="text-sm text-yellow-700 mt-1">Bắt đầu lúc: {{ $attempt->started_at->format('d/m/Y H:i') }}</p>
                @if($attempt->submitted_at)
                    <p class="flex items-center gap-1 text-sm text-green-700 mt-1 font-medium">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Đã nộp lúc: {{ $attempt->submitted_at->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="pt-2">
                @if($isPast)
                    <div class="text-gray-500 text-center font-medium py-3 px-4 bg-gray-50 rounded-lg border border-gray-200">
                        Bài thi đã kết thúc.
                    </div>
                @elseif(!$isOpen)
                    <div class="text-yellow-700 text-center font-medium py-3 px-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        Bài thi chưa bắt đầu.
                    </div>
                @elseif($attempt && $attempt->submitted_at)
                    <a href="{{ route('student.exams.submitted', $exam) }}"
                       class="w-full inline-flex items-center justify-center gap-2 bg-gray-700 text-white px-6 py-3.5 rounded-lg font-medium hover:bg-gray-800 transition shadow-sm">
                        <span>Xem kết quả</span>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </a>
                @elseif($attempt && !$attempt->submitted_at)
                    <a href="{{ route('student.exams.editor', $exam) }}"
                       class="w-full inline-flex items-center justify-center gap-2 bg-orange-500 text-white px-6 py-3.5 rounded-lg font-semibold hover:bg-orange-600 transition text-lg shadow-md">
                        <span>Tiếp tục làm bài →</span>
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </a>
                @else
                    <!-- Start Button triggers In-Page Modal (Fail-proof, bypasses browser confirm blockers) -->
                    <button type="button"
                        id="openStartModalBtn"
                        onclick="openStartModal()"
                        class="flex items-center justify-center gap-2 w-full bg-orange-500 text-white px-6 py-3.5 rounded-lg font-semibold hover:bg-orange-600 active:bg-orange-700 transition text-lg shadow-md cursor-pointer">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                        <span>Bắt đầu thi</span>
                    </button>
                    @if($exam->time_limit_minutes)
                        <p class="text-xs text-gray-500 text-center mt-2.5">
                            Sau khi bắt đầu, bạn có <strong>{{ $exam->time_limit_minutes }} phút</strong> để hoàn thành.
                        </p>
                    @endif

                    <!-- In-page confirmation modal (Zero dependency on native window.confirm) -->
                    <div id="startExamModal"
                         class="hidden fixed inset-0 z-50 overflow-y-auto"
                         role="dialog"
                         aria-modal="true"
                         aria-labelledby="modalTitle">
                        <!-- Backdrop -->
                        <div class="fixed inset-0 bg-black/50 transition-opacity backdrop-blur-xs"
                             onclick="closeStartModal()"></div>

                        <!-- Modal Dialog -->
                        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-200"
                                 onclick="event.stopPropagation()">
                                <div class="bg-white p-6 sm:p-7">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h3 id="modalTitle" class="text-xl font-bold text-gray-900">
                                                Xác nhận bắt đầu thi
                                            </h3>
                                            <div class="mt-3 text-sm text-gray-600 space-y-3">
                                                <p class="font-medium text-gray-800">
                                                    Bài thi: <span class="text-orange-600 font-semibold">{{ $exam->title }}</span>
                                                </p>
                                                @if($exam->time_limit_minutes)
                                                    <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-lg p-3 text-xs leading-relaxed">
                                                        <strong class="font-semibold block mb-1">⏱️ Lưu ý về thời gian làm bài:</strong>
                                                        <p>Thời gian làm bài là <strong>{{ $exam->time_limit_minutes }} phút</strong> và sẽ được tính ngay khi bạn bấm nút xác nhận.</p>
                                                    </div>
                                                @else
                                                    <p class="text-xs text-gray-500 bg-gray-50 p-2.5 rounded border border-gray-200">
                                                        Thời gian kết thúc chung: {{ $exam->ends_at->format('d/m/Y H:i') }}
                                                    </p>
                                                @endif
                                                <p class="text-gray-700">
                                                    Bạn đã chắc chắn muốn bắt đầu làm bài chưa?
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 border-t border-gray-100">
                                    <button type="button"
                                        id="cancelStartBtn"
                                        onclick="closeStartModal()"
                                        class="w-full sm:w-auto px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-white hover:bg-gray-100 font-medium text-sm transition cursor-pointer">
                                        Hủy
                                    </button>

                                    <form id="startExamForm" method="POST" action="{{ route('student.exams.start', $exam) }}" onsubmit="handleStartSubmit(event)" class="w-full sm:w-auto">
                                        @csrf
                                        <button type="submit"
                                            id="confirmStartBtn"
                                            class="w-full sm:w-auto px-6 py-2.5 rounded-lg bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white font-semibold text-sm transition shadow-sm flex items-center justify-center gap-2 cursor-pointer">
                                            <svg id="startSpinner" class="hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <span id="startBtnText">Bắt đầu làm bài</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                    function openStartModal() {
                        const modal = document.getElementById('startExamModal');
                        if (modal) {
                            modal.classList.remove('hidden');
                            document.body.classList.add('overflow-hidden');
                        }
                    }
                    function closeStartModal() {
                        const btn = document.getElementById('confirmStartBtn');
                        if (btn && btn.dataset.submitting === 'true') return;
                        const modal = document.getElementById('startExamModal');
                        if (modal) {
                            modal.classList.add('hidden');
                            document.body.classList.remove('overflow-hidden');
                        }
                    }
                    function handleStartSubmit(e) {
                        const btn = document.getElementById('confirmStartBtn');
                        const cancelBtn = document.getElementById('cancelStartBtn');
                        const spinner = document.getElementById('startSpinner');
                        const text = document.getElementById('startBtnText');
                        if (!btn) return;
                        if (btn.dataset.submitting === 'true') {
                            e.preventDefault();
                            return false;
                        }
                        btn.dataset.submitting = 'true';
                        btn.disabled = true;
                        if (cancelBtn) cancelBtn.disabled = true;
                        if (spinner) spinner.classList.remove('hidden');
                        if (text) text.innerText = 'Đang vào bài thi...';
                    }
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') closeStartModal();
                    });
                    </script>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
