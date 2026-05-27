@extends('layouts.admin')
@section('title', $user->name)
@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h1>
    <span class="px-2 py-1 text-xs rounded-full font-medium {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ $user->role }}</span>
    @if(!$user->is_active)<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-600">Inactive</span>@endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Profile Card -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="font-semibold text-gray-700 mb-4">Thông tin</h2>
        <div class="space-y-3 text-sm">
            <div><span class="text-gray-500">Email:</span> <span class="font-medium">{{ $user->email }}</span></div>
            @if($user->student_id)
            <div><span class="text-gray-500">Mã SV:</span> <span class="font-medium">{{ $user->student_id }}</span></div>
            @endif
            <div><span class="text-gray-500">Ngày tạo:</span> <span>{{ $user->created_at->format('d/m/Y') }}</span></div>
            <div><span class="text-gray-500">Sandbox DB:</span>
                @if($user->sandbox_db_name)
                    <code class="bg-gray-100 px-1 rounded text-xs">{{ $user->sandbox_db_name }}</code>
                @else
                    <span class="text-gray-400">Chưa tạo</span>
                @endif
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}"
               class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Chỉnh sửa</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="font-semibold text-gray-700 mb-4">Thống kê</h2>
        <div class="grid grid-cols-2 gap-4 text-center">
            <div class="bg-blue-50 rounded p-3">
                <p class="text-2xl font-bold text-blue-600">{{ $submissions->total() }}</p>
                <p class="text-xs text-gray-500 mt-1">Submissions</p>
            </div>
            <div class="bg-green-50 rounded p-3">
                <p class="text-2xl font-bold text-green-600">{{ $enrolledClasses->count() }}</p>
                <p class="text-xs text-gray-500 mt-1">Lớp học</p>
            </div>
            <div class="bg-purple-50 rounded p-3">
                <p class="text-2xl font-bold text-purple-600">
                    {{ $submissions->where('execution_status', 'success')->count() }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Thành công</p>
            </div>
            <div class="bg-orange-50 rounded p-3">
                <p class="text-2xl font-bold text-orange-600">
                    {{ $submissions->where('exam_id', '!=', null)->count() }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Thi</p>
            </div>
        </div>
    </div>

    <!-- Enrolled Classes -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="font-semibold text-gray-700 mb-4">Lớp đang học ({{ $enrolledClasses->count() }})</h2>
        @if($enrolledClasses->count())
            <div class="space-y-2">
                @foreach($enrolledClasses as $class)
                <a href="{{ route('admin.classes.show', $class) }}"
                   class="flex items-center justify-between p-2 rounded hover:bg-gray-50 group">
                    <span class="text-sm text-gray-700 group-hover:text-blue-600">{{ $class->name }}</span>
                    <span class="text-xs text-gray-400">{{ $class->assignments->count() }} bài</span>
                </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">Chưa tham gia lớp nào.</p>
        @endif
    </div>
</div>

<!-- Submission History -->
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-700">Lịch sử Submissions ({{ $submissions->total() }})</h2>
    </div>
    @if($submissions->count())
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Loại</th>
                    <th class="px-6 py-3 text-left">Tiêu đề</th>
                    <th class="px-6 py-3 text-left">Trạng thái</th>
                    <th class="px-6 py-3 text-left">Thời gian</th>
                    <th class="px-6 py-3 text-left">Ngày nộp</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($submissions as $sub)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        @if($sub->exam_id)
                            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">Thi</span>
                        @elseif($sub->assignment_id)
                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">Bài tập</span>
                        @else
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">Sandbox</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-gray-700">
                        {{ $sub->assignment?->title ?? $sub->exam?->title ?? $sub->title ?? 'Free sandbox' }}
                    </td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 rounded text-xs font-medium
                            {{ $sub->execution_status === 'success' ? 'bg-green-100 text-green-700' :
                               ($sub->execution_status === 'error' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $sub->execution_status }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-gray-400 text-xs">{{ $sub->execution_time_ms }}ms</td>
                    <td class="px-6 py-3 text-gray-400 text-xs">{{ $sub->submitted_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.submissions.show', $sub) }}" class="text-blue-500 hover:underline text-xs">Xem</a>
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
