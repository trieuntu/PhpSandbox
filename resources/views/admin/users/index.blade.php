@extends('layouts.admin')
@section('title', 'Quản lý người dùng')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Người dùng</h1>
        <p class="text-gray-500 text-sm mt-1">Quản lý tài khoản sinh viên và admin</p>
    </div>
    <div class="flex gap-3">
        <button onclick="document.getElementById('import-modal').classList.remove('hidden')"
            class="border border-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-50 text-sm">
            📥 Import CSV
        </button>
        <a href="{{ route('admin.users.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
            + Thêm người dùng
        </a>
    </div>
</div>

<!-- Search & Filter -->
<form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-4 flex gap-3">
    <input type="text" name="search" value="{{ request('search') }}"
        placeholder="Tìm theo tên, email, mã SV..."
        class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <select name="role" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
        <option value="">Tất cả vai trò</option>
        <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Sinh viên</option>
        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
    </select>
    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-sm">Tìm kiếm</button>
    @if(request()->hasAny(['search', 'role']))
        <a href="{{ route('admin.users.index') }}" class="border border-gray-300 text-gray-600 px-4 py-2 rounded text-sm hover:bg-gray-50">Xóa bộ lọc</a>
    @endif
</form>

<!-- Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tên</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Email</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Mã SV</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Vai trò</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Submissions</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Tạo lúc</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $user->name }}</p>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $user->student_id ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-xs {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ $user->role }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $user->submissions_count ?? 0 }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $user->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3">
                    <div class="flex gap-2 justify-end">
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="text-xs text-blue-600 hover:underline">Sửa</a>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                            onsubmit="return confirm('Xóa người dùng {{ addslashes($user->name) }}? Tất cả submissions của họ cũng sẽ bị xóa.')"
                            class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">Không tìm thấy người dùng nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $users->withQueryString()->links() }}
</div>

<!-- CSV Import Modal -->
<div id="import-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-gray-800">Import người dùng từ CSV</h3>
            <button onclick="document.getElementById('import-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">File CSV cần có các cột: <code class="bg-gray-100 px-1 rounded">name, email, student_id, password</code></p>
                <input type="file" name="csv_file" accept=".csv" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                    Import
                </button>
                <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                    class="flex-1 border border-gray-300 text-gray-600 px-4 py-2 rounded text-sm hover:bg-gray-50">
                    Hủy
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
