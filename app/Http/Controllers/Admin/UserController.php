<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\DatabaseProvisionService;
use App\Services\ActivityLogger;
use App\Services\UserCsvImportService;

class UserController extends Controller {
    public function index(Request $request) {
        $query = User::query();
        
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }
        if ($role = $request->input('role')) $query->where('role', $role);
        if ($request->has('active')) $query->where('is_active', $request->boolean('active'));
        
        $users = $query->orderByDesc('created_at')->paginate(20);
        return view('admin.users.index', compact('users'));
    }
    
    public function create() { return view('admin.users.create'); }
    
    public function store(Request $request, DatabaseProvisionService $dbService) {
        $data = $request->validate([
            'student_id' => 'nullable|string|max:50|unique:users',
            'last_name' => 'required|string|max:150',
            'first_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,student',
        ]);
        
        $user = User::create([
            'student_id' => $data['student_id'] ?? null,
            'last_name' => trim($data['last_name']),
            'first_name' => trim($data['first_name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => true,
        ]);
        
        if ($user->role === 'student') {
            try { $dbService->provision($user); } catch (\Exception $e) {}
        }
        
        ActivityLogger::log('user_created', "Admin created user: {$user->email}");
        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }
    
    public function edit(User $user) { return view('admin.users.edit', compact('user')); }

    public function show(User $user) {
        $submissions = $user->submissions()->with('assignment', 'exam')->orderByDesc('submitted_at')->paginate(15);
        $enrolledClasses = $user->classes()->with('assignments')->get();
        return view('admin.users.show', compact('user', 'submissions', 'enrolledClasses'));
    }

    public function update(Request $request, User $user) {
        $data = $request->validate([
            'student_id' => 'nullable|string|max:50|unique:users,student_id,' . $user->id,
            'last_name' => 'required|string|max:150',
            'first_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,student',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:8',
        ]);
        
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        
        // Prevent admin from deactivating their own account
        if (auth()->id() === $user->id) {
            $data['is_active'] = true;
        } else {
            $data['is_active'] = $request->boolean('is_active');
        }
        $user->update($data);
        ActivityLogger::log('user_updated', "Admin updated user: {$user->email}");
        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }
    
    public function destroy(User $user) {
        $user->update(['is_active' => false]);
        ActivityLogger::log('user_deactivated', "Admin deactivated user: {$user->email}");
        return redirect()->route('admin.users.index')->with('success', 'User deactivated.');
    }
    
    public function importForm() {
        return view('admin.users.import');
    }

    public function import(Request $request, UserCsvImportService $importService) {
        $request->validate([
            'csv' => 'nullable|file|mimes:csv,txt',
            'csv_file' => 'nullable|file|mimes:csv,txt',
        ]);
        
        $file = $request->file('csv') ?? $request->file('csv_file');
        if (!$file) {
            return back()->with('error', 'Vui lòng chọn file CSV để import.');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $result = $importService->import($file->getPathname());
        
        ActivityLogger::log('users_imported', "Admin imported {$result['imported']} students from CSV ({$result['skipped']} skipped).");

        $redirect = redirect()->route('admin.users.index');

        if ($result['imported'] === 0) {
            $errorMsg = 'Không import được sinh viên nào.';
            if (!empty($result['errors'])) {
                $errorMsg .= ' Chi tiết lỗi: ' . implode(' | ', array_slice($result['errors'], 0, 5));
            }
            return $redirect->with('error', $errorMsg);
        }

        $msg = "Đã import thành công {$result['imported']} sinh viên.";
        if ($result['skipped'] > 0) {
            $msg .= " ({$result['skipped']} dòng bị bỏ qua hoặc đã tồn tại).";
        }

        if (!empty($result['errors'])) {
            return $redirect->with('warning', $msg . ' Lỗi một số dòng: ' . implode(' | ', array_slice($result['errors'], 0, 3)));
        }

        return $redirect->with('success', $msg);
    }
    
    public function toggleRegistration(Request $request) {
        $current = \App\Models\Setting::get('registration_enabled', '1');
        \App\Models\Setting::set('registration_enabled', $current ? '0' : '1', auth()->id());
        return redirect()->route('admin.users.index')->with('success', 'Registration setting updated.');
    }
}
