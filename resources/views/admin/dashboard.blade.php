@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-gray-500 text-sm mt-1">Tổng quan hệ thống PHP Sandbox</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Sinh viên</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($stats['total_students']) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Hoạt động hôm nay</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['active_today']) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Submissions hôm nay</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['submissions_today']) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Tuần này</p>
        <p class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($stats['submissions_week']) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Tổng submissions</p>
        <p class="text-2xl font-bold text-gray-700 mt-1">{{ number_format($stats['submissions_total']) }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Kỳ thi đang mở</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($stats['open_exams']) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Submissions -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-700">Submissions gần đây</h2>
            <a href="{{ route('admin.submissions.index') }}" class="text-xs text-blue-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentSubmissions as $sub)
            <div class="px-4 py-3 hover:bg-gray-50 transition">
                <div class="flex justify-between items-start">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $sub->user->name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 truncate">
                            {{ $sub->assignment?->title ?? $sub->exam?->title ?? 'Free sandbox' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 ml-3 flex-shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded {{ $sub->execution_status === 'success' ? 'bg-green-100 text-green-700' : ($sub->execution_status === 'error' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $sub->execution_status }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $sub->submitted_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-gray-400 text-sm">Chưa có submission nào</div>
            @endforelse
        </div>
    </div>

    <!-- Recent Activity Logs -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-700">Hoạt động gần đây</h2>
            <a href="{{ route('admin.logs.index') }}" class="text-xs text-blue-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentLogs as $log)
            <div class="px-4 py-3 hover:bg-gray-50 transition">
                <div class="flex justify-between items-start">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800">
                            <span class="font-medium">{{ $log->user?->name ?? 'System' }}</span>
                            <span class="text-gray-600 ml-1">{{ $log->action }}</span>
                        </p>
                        @if($log->description)
                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ $log->description }}</p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 ml-3 flex-shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-gray-400 text-sm">Không có log nào</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
