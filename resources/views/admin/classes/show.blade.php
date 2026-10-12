@extends('layouts.admin')
@section('title', $class->name)
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.classes.index') }}" class="text-sm text-gray-500 hover:text-blue-600">← Danh sách lớp học</a>
    <div class="flex justify-between items-start mt-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $class->name }}</h1>
            @if($class->description)<p class="text-gray-600 text-sm mt-1">{{ $class->description }}</p>@endif
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.classes.edit', $class) }}" class="border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50">
                ✏️ Sửa lớp
            </a>
            <a href="{{ route('admin.assignments.create', $class) }}"
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                + Thêm bài tập
            </a>
            <a href="{{ route('admin.exams.create', ['class_id' => $class->id]) }}"
               class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 text-sm">
                + Thêm kỳ thi
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
        <p class="text-2xl font-bold text-gray-800">{{ $class->students->count() }}</p>
        <p class="text-xs text-gray-500 mt-1">Sinh viên</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ $class->assignments->count() }}</p>
        <p class="text-xs text-gray-500 mt-1">Bài tập</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
        <p class="text-2xl font-bold text-orange-600">{{ $class->exams->count() }}</p>
        <p class="text-xs text-gray-500 mt-1">Kỳ thi</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Students -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="font-semibold text-gray-700">Sinh viên ({{ $class->students->count() }})</h2>
        </div>
        <div class="px-4 py-3 border-b border-gray-100 space-y-3" x-data="studentPicker('{{ route('admin.classes.students.search', $class, false) }}')">
            <form method="POST" action="{{ route('admin.classes.students.store', $class) }}" @submit="if(!selected.length) $event.preventDefault()">
                @csrf
                <div class="relative">
                    <input type="text" x-model="query" @input.debounce.300ms="search()" autocomplete="off"
                        placeholder="Tìm theo tên, MSSV hoặc email..."
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div x-show="results.length" x-cloak class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded shadow max-h-48 overflow-y-auto">
                        <template x-for="u in results" :key="u.id">
                            <button type="button" @click="add(u)" class="w-full text-left px-3 py-1.5 text-sm hover:bg-gray-50">
                                <span x-text="u.name"></span>
                                <span class="text-xs text-gray-400 font-mono" x-text="u.student_id || u.email"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="status" x-cloak x-text="status" class="text-xs text-red-500 mt-1"></p>
                </div>
                <div class="flex flex-wrap gap-1 mt-2">
                    <template x-for="u in selected" :key="u.id">
                        <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded">
                            <span x-text="u.name"></span>
                            <input type="hidden" name="student_ids[]" :value="u.id">
                            <button type="button" @click="remove(u.id)" class="hover:text-red-600">&times;</button>
                        </span>
                    </template>
                </div>
                <button type="submit" x-show="selected.length" x-cloak class="mt-2 bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700">Thêm vào lớp</button>
            </form>

            <details>
                <summary class="text-xs text-blue-600 cursor-pointer">Thêm hàng loạt theo danh sách MSSV / email</summary>
                <form method="POST" action="{{ route('admin.classes.students.store', $class) }}" class="mt-2">
                    @csrf
                    <textarea name="identifiers" rows="4" placeholder="Mỗi dòng hoặc cách nhau bằng dấu phẩy: SV001, sv002@email.com..."
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    <button type="submit" class="mt-2 bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700">Thêm danh sách</button>
                </form>
            </details>
        </div>
        <div class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
            @forelse($class->students->sortBy(['first_name', 'last_name']) as $student)
            <div class="px-4 py-2 flex justify-between items-center hover:bg-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $student->last_name }} <span class="font-semibold">{{ $student->first_name }}</span></p>
                    <p class="text-xs text-gray-400">{{ $student->email }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono text-gray-400">{{ $student->student_id }}</span>
                    <form method="POST" action="{{ route('admin.classes.students.destroy', [$class, $student]) }}"
                        onsubmit="return confirm('Xóa sinh viên này khỏi lớp?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-600 hover:underline">Xóa</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-4 py-6 text-center text-gray-400 text-sm">Chưa có sinh viên</div>
            @endforelse
        </div>
    </div>

    <!-- Assignments & Exams -->
    <div class="space-y-4">
        <!-- Assignments -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="font-semibold text-gray-700">Bài tập</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($class->assignments as $assignment)
                <div class="px-4 py-2 flex justify-between items-center hover:bg-gray-50 {{ !$assignment->is_active ? 'opacity-60' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-gray-800">
                            {{ $assignment->title }}
                            @if(!$assignment->is_active)
                                <span class="ml-1 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Ẩn</span>
                            @endif
                        </p>
                        @if($assignment->due_at)
                            <p class="text-xs text-orange-500">Hạn: {{ $assignment->due_at->format('d/m H:i') }}</p>
                        @endif
                    </div>
                    <div class="flex gap-2 items-center">
                        <form method="POST" action="{{ route('admin.assignments.toggle', [$class, $assignment]) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" title="{{ $assignment->is_active ? 'Ẩn bài tập' : 'Kích hoạt bài tập' }}"
                                class="text-xs {{ $assignment->is_active ? 'text-green-600 hover:text-red-600' : 'text-gray-400 hover:text-green-600' }}">
                                @if($assignment->is_active)
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                @else
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                @endif
                            </button>
                        </form>
                        <a href="{{ route('admin.assignments.edit', [$class, $assignment]) }}" class="text-xs text-blue-600 hover:underline">Sửa</a>
                    </div>
                </div>
                @empty
                <div class="px-4 py-4 text-center text-gray-400 text-sm">Chưa có bài tập</div>
                @endforelse
            </div>
        </div>

        <!-- Exams -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="font-semibold text-gray-700">Kỳ thi</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($class->exams as $exam)
                <div class="px-4 py-2 flex justify-between items-center hover:bg-gray-50 {{ !$exam->is_active ? 'opacity-60' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-gray-800">
                            {{ $exam->title }}
                            @if(!$exam->is_active)
                                <span class="ml-1 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Ẩn</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500">{{ $exam->starts_at->format('d/m H:i') }} – {{ $exam->ends_at->format('d/m H:i') }}</p>
                    </div>
                    <div class="flex gap-2 items-center">
                        <form method="POST" action="{{ route('admin.exams.toggle', $exam) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" title="{{ $exam->is_active ? 'Ẩn kỳ thi' : 'Kích hoạt kỳ thi' }}"
                                class="text-xs {{ $exam->is_active ? 'text-green-600 hover:text-red-600' : 'text-gray-400 hover:text-green-600' }}">
                                @if($exam->is_active)
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                @else
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                @endif
                            </button>
                        </form>
                        <a href="{{ route('admin.exams.show', $exam) }}" class="text-xs text-gray-600 hover:underline">Xem</a>
                        <a href="{{ route('admin.exams.edit', $exam) }}" class="text-xs text-blue-600 hover:underline">Sửa</a>
                    </div>
                </div>
                @empty
                <div class="px-4 py-4 text-center text-gray-400 text-sm">Chưa có kỳ thi</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@push('scripts')
<style>[x-cloak]{display:none !important}</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('studentPicker', (searchUrl) => ({
        query: '', results: [], selected: [], status: '',
        async search() {
            const q = this.query.trim();
            this.status = '';
            if (!q) { this.results = []; return; }
            try {
                const res = await fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) { this.results = []; this.status = 'Lỗi tìm kiếm (HTTP ' + res.status + ').'; return; }
                this.results = (await res.json()).filter(u => !this.selected.some(s => s.id === u.id));
                if (!this.results.length) this.status = 'Không tìm thấy sinh viên (hoặc đã có trong lớp).';
            } catch (e) {
                this.results = [];
                this.status = 'Không kết nối được máy chủ.';
            }
        },
        add(u) { this.selected.push(u); this.results = []; this.query = ''; },
        remove(id) { this.selected = this.selected.filter(s => s.id !== id); },
    }));
});
</script>
@endpush
@endsection
