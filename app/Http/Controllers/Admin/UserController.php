<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\DatabaseProvisionService;
use App\Services\ActivityLogger;

class UserController extends Controller {
    public function index(Request $request) {
        $query = User::query();
        
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,student',
        ]);
        
        $user = User::create([
            'student_id' => $data['student_id'] ?? null,
            'name' => $data['name'],
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
            'name' => 'required|string|max:255',
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

    public function import(Request $request, DatabaseProvisionService $dbService) {
        $request->validate(['csv' => 'required|file|mimes:csv,txt']);
        
        $file = $request->file('csv');
        $handle = fopen($file->getPathname(), 'r');
        $headers = fgetcsv($handle);
        
        $imported = 0;
        while ($row = fgetcsv($handle)) {
            $data = array_combine($headers, $row);
            try {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'student_id' => $data['student_id'] ?? null,
                        'name' => $data['name'],
                        'password' => Hash::make($data['password'] ?? 'password123'),
                        'role' => 'student',
                        'is_active' => true,
                    ]
                );
                if ($user->wasRecentlyCreated) {
                    try { $dbService->provision($user); } catch (\Exception $e) {}
                    $imported++;
                }
            } catch (\Exception $e) {}
        }
        fclose($handle);
        
        return redirect()->route('admin.users.index')->with('success', "Imported {$imported} students.");
    }
    
    public function toggleRegistration(Request $request) {
        $current = \App\Models\Setting::get('registration_enabled', '1');
        \App\Models\Setting::set('registration_enabled', $current ? '0' : '1', auth()->id());
        return redirect()->route('admin.users.index')->with('success', 'Registration setting updated.');
    }
}
