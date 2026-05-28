@extends('layouts.admin')
@section('title', $sharedDatabase->display_name)

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.shared-databases.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $sharedDatabase->display_name }}</h1>
                <p class="text-sm text-gray-500 font-mono">sandbox_shared_{{ $sharedDatabase->slug }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.shared-databases.edit', $sharedDatabase) }}"
               class="inline-flex items-center gap-2 border border-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                Chỉnh sửa
            </a>
            <form method="POST" action="{{ route('admin.shared-databases.destroy', $sharedDatabase) }}"
                  onsubmit="return confirm('Xóa database này? Không thể hoàn tác!')">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 border border-red-200 text-red-600 px-3 py-2 rounded-lg text-sm hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    Xóa
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        {{-- Info panel --}}
        <div class="col-span-1 space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Thông tin</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Quyền sinh viên</dt>
                        <dd class="mt-0.5">
                            @php $color = $sharedDatabase->permission_color @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $color === 'blue' ? 'bg-blue-100 text-blue-700' : ($color === 'green' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ $sharedDatabase->permission_label }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">MySQL user (RO)</dt>
                        <dd class="mt-0.5 font-mono text-xs text-gray-700">sbx_shared_ro</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">MySQL user (RW)</dt>
                        <dd class="mt-0.5 font-mono text-xs text-gray-700">sbx_shared_rw</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tạo bởi</dt>
                        <dd class="mt-0.5 text-gray-700">{{ $sharedDatabase->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Ngày tạo</dt>
                        <dd class="mt-0.5 text-gray-700">{{ $sharedDatabase->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @if($sharedDatabase->description)
                    <div>
                        <dt class="text-gray-500">Mô tả</dt>
                        <dd class="mt-0.5 text-gray-700">{{ $sharedDatabase->description }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- Re-import form --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Import lại dữ liệu</h3>
                <p class="text-xs text-gray-500 mb-3">Database sẽ bị xóa và tạo lại từ file SQL mới.</p>
                <form method="POST" action="{{ route('admin.shared-databases.reimport', $sharedDatabase) }}"
                      enctype="multipart/form-data"
                      onsubmit="return confirm('Import lại sẽ xóa toàn bộ dữ liệu hiện tại. Tiếp tục?')">
                    @csrf
                    <input type="file" name="sql_file" accept=".sql,.txt" required
                           class="block w-full text-xs text-gray-500 mb-3 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                    <button type="submit"
                            class="w-full bg-amber-500 text-white text-sm py-2 rounded-lg hover:bg-amber-600 transition-colors">
                        Import lại
                    </button>
                </form>
            </div>

            {{-- Refresh stats --}}
            <form method="POST" action="{{ route('admin.shared-databases.refresh', $sharedDatabase) }}">
                @csrf
                <button type="submit" class="w-full border border-gray-300 text-gray-600 text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    Làm mới thống kê
                </button>
            </form>
        </div>

        {{-- Tables panel --}}
        <div class="col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">
                        Cấu trúc database
                        <span class="text-gray-400 font-normal">({{ count($sharedDatabase->tables_info ?? []) }} bảng)</span>
                    </h3>
                </div>

                @if(empty($sharedDatabase->tables_info))
                    <div class="p-8 text-center text-gray-400 text-sm">
                        Chưa có thông tin bảng — nhấn "Làm mới thống kê"
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên bảng</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số hàng</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kích thước</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($sharedDatabase->tables_info as $table)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <code class="text-sm text-gray-800">{{ $table['name'] }}</code>
                                </td>
                                <td class="px-5 py-3 text-right text-sm text-gray-600">
                                    {{ number_format($table['rows'] ?? 0) }}
                                </td>
                                <td class="px-5 py-3 text-right text-sm text-gray-600">
                                    {{ $table['size_kb'] ?? 0 }} KB
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Usage example --}}
            <div class="mt-4 bg-gray-900 rounded-xl p-5 text-sm">
                <p class="text-gray-400 text-xs mb-3 font-medium">Ví dụ sử dụng trong sandbox (PHP)</p>
                <pre class="text-green-400 text-xs leading-relaxed overflow-x-auto"><code>// Kết nối PDO đã được inject tự động
$pdo = $sharedDbs['{{ $sharedDatabase->slug }}'];

// Truy vấn
$rows = $pdo->query("SELECT * FROM ...")->fetchAll();

// Với prepared statement
$stmt = $pdo->prepare("SELECT * FROM ... WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection
