@extends('layouts.admin')
@section('title', 'Thông báo')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Thông báo</h1>
        <p class="text-gray-500 text-sm mt-1">Quản lý thông báo cho sinh viên</p>
    </div>
    <a href="{{ route('admin.announcements.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
        + Tạo thông báo
    </a>
</div>

<div class="space-y-4">
    @forelse($announcements as $ann)
    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-semibold text-gray-800">{{ $ann->title }}</h3>
                    @if($ann->is_pinned)
                        <span class="flex items-center gap-1 text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded">
                            <svg class="w-3 h-3 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H7.5m9 0a2.25 2.25 0 012.25 2.25v13.5a2.25 2.25 0 01-2.25 2.25H7.5a2.25 2.25 0 01-2.25-2.25V6a2.25 2.25 0 012.25-2.25m9 0h-9" /></svg>
                            Ghim
                        </span>
                    @endif
                    @if($ann->class_id)
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ $ann->class->name }}</span>
                    @else
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">Toàn trường</span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 line-clamp-2">{{ Str::limit($ann->body, 200) }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $ann->created_at->format('d/m/Y H:i') }} · {{ $ann->author->name ?? 'Admin' }}</p>
            </div>
            <div class="flex gap-3 ml-4">
                <a href="{{ route('admin.announcements.edit', $ann) }}" class="text-xs text-blue-600 hover:underline">Sửa</a>
                <form method="POST" action="{{ route('admin.announcements.destroy', $ann) }}"
                    onsubmit="return confirm('Xóa thông báo này?')" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-600 hover:underline">Xóa</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">
        Chưa có thông báo nào
    </div>
    @endforelse
</div>

<div class="mt-4">{{ $announcements->links() }}</div>
@endsection
