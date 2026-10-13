@extends('layouts.admin')
@section('title', 'Import người dùng')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Import người dùng qua CSV</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="font-semibold text-gray-700 mb-3">Định dạng file CSV</h2>
        <p class="text-sm text-gray-500 mb-2">Hệ thống hỗ trợ cả <strong>file có dòng tiêu đề</strong> hoặc <strong>không có dòng tiêu đề</strong>:</p>
        <div class="bg-gray-50 rounded p-3 font-mono text-sm text-gray-700 mb-2">
            Họ đệm, Tên, Email, MSSV, Mật khẩu
        </div>
        <div class="bg-gray-50 rounded p-3 font-mono text-xs text-gray-500">
            Ví dụ: Huỳnh Triều,An,an.ht.65cntt@ntu.edu.vn,65130011,65130011
        </div>
        <p class="text-xs text-gray-500 mt-3">Hỗ trợ nhận diện tự động: Header tiếng Việt/Anh (<code>last_name,first_name,email,student_id,password</code> hoặc <code>name,email,student_id,password</code>), danh sách xuất từ cổng đào tạo NTU, UTF-8 BOM, phân cách bằng dấu phẩy (<code>,</code>) hoặc chấm phẩy (<code>;</code>).</p>
        <p class="text-xs text-gray-500 mt-1">Nếu bỏ trống <code>password</code>, mật khẩu mặc định sẽ là MSSV hoặc <code>password123</code>.</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Chọn file CSV</label>
                <input type="file" name="csv" accept=".csv,.txt" required
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    Import
                </button>
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
