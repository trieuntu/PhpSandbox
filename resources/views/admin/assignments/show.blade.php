@extends('layouts.admin')
@section('title', 'Chi tiết Assignment')
@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.classes.show', $class) }}" class="text-gray-500 hover:text-gray-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div>
        <p class="text-sm text-gray-500">{{ $class->name }}</p>
        <h1 class="text-2xl font-bold text-gray-800">{{ $assignment->title }}</h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow p-5 col-span-2">
        <h2 class="font-semibold text-gray-700 mb-3">Thông tin</h2>
        @if($assignment->description)
            <p class="text-gray-600 mb-3">{{ $assignment->description }}</p>
        @endif
        @if($assignment->instructions)
            <div class="bg-gray-50 rounded p-3 text-sm text-gray-600 whitespace-pre-wrap">{{ $assignment->instructions }}</div>
        @endif
        <div class="mt-4 flex gap-6 text-sm text-gray-500">
            <span>Hạn nộp: <strong class="text-gray-700">{{ $assignment->due_at ? $assignment->due_at->format('d/m/Y H:i') : 'Không giới hạn' }}</strong></span>
            <span>Trạng thái: <span class="{{ $assignment->is_active ? 'text-green-600' : 'text-gray-400' }}">{{ $assignment->is_active ? 'Đang mở' : 'Đã đóng' }}</span></span>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold text-gray-700 mb-4">Thao tác</h2>
        <div class="flex flex-col gap-2">
            <a href="{{ route('admin.assignments.edit', [$class, $assignment]) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-center text-sm font-medium">
                Chỉnh sửa
            </a>
            <form method="POST" action="{{ route('admin.assignments.destroy', [$class, $assignment]) }}"
                  onsubmit="return confirm('Xóa assignment này?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 text-sm font-medium">
                    Xóa
                </button>
            </form>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-semibold text-gray-700">Submissions ({{ $submissions->total() }})</h2>
    </div>
    @if($submissions->count())
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Sinh viên</th>
                    <th class="px-6 py-3 text-left">Trạng thái</th>
                    <th class="px-6 py-3 text-left">Thời gian</th>
                    <th class="px-6 py-3 text-left">Ngày nộp</th>
                    <th class="px-6 py-3 text-left"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($submissions as $sub)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">{{ $sub->user->name ?? 'N/A' }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            {{ $sub->execution_status === 'success' ? 'bg-green-100 text-green-700' : 
                               ($sub->execution_status === 'error' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $sub->execution_status }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $sub->execution_time_ms }}ms</td>
                    <td class="px-6 py-3 text-gray-500">{{ $sub->submitted_at ? $sub->submitted_at->format('d/m/Y H:i') : $sub->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.submissions.show', $sub) }}" class="text-blue-600 hover:underline text-xs">Xem</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">{{ $submissions->links() }}</div>
    @else
        <div class="px-6 py-10 text-center text-gray-400">Chưa có submission nào.</div>
    @endif
</div>
@endsection
