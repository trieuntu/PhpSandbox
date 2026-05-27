@extends('layouts.admin')
@section('title', 'Activity Logs')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Activity Logs</h1>
        <p class="text-gray-500 text-sm mt-1">Theo dõi hoạt động của người dùng trong hệ thống</p>
    </div>
</div>

<!-- Filter -->
<form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-4 flex gap-3 flex-wrap">
    @php($availableActionTypes = $actionTypes ?? $actions ?? collect())
    <input type="text" name="search" value="{{ request('search') }}"
        placeholder="Tìm theo tên, action..."
        class="flex-1 min-w-48 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <select name="action" class="border border-gray-300 rounded px-3 py-2 text-sm">
        <option value="">Tất cả hành động</option>
        @foreach($availableActionTypes as $type)
        <option value="{{ $type }}" {{ request('action') === $type ? 'selected' : '' }}>{{ $type }}</option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ request('date_from') }}"
        class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
    <input type="date" name="date_to" value="{{ request('date_to') }}"
        class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-sm">Lọc</button>
    @if(request()->hasAny(['search', 'action', 'date_from', 'date_to']))
        <a href="{{ route('admin.logs.index') }}" class="border border-gray-300 text-gray-600 px-4 py-2 rounded text-sm hover:bg-gray-50">Xóa</a>
    @endif
</form>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Người dùng</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Hành động</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Mô tả</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">IP</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Thời gian</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    @if($log->user)
                        <p class="font-medium text-gray-800">{{ $log->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $log->user->email }}</p>
                    @else
                        <p class="text-gray-400 text-xs">System</p>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded font-mono">{{ $log->action }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600 text-sm max-w-xs truncate">{{ $log->description ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs font-mono">{{ $log->ip_address ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-10 text-center text-gray-400">Không có log nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->withQueryString()->links() }}</div>
@endsection
