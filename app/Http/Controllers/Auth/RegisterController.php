<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\DatabaseProvisionService;
use App\Services\ActivityLogger;

class RegisterController extends Controller {
    public function showRegistrationForm() {
        return view('auth.register');
    }
    
    public function register(Request $request, DatabaseProvisionService $dbService) {
        $data = $request->validate([
            'student_id' => 'nullable|string|max:50|unique:users,student_id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $user = User::create([
            'student_id' => $data['student_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'is_active' => true,
        ]);
        
        // Provision sandbox database
        try {
            $dbService->provision($user);
        } catch (\Exception $e) {
            // Log but don't fail registration
            \Illuminate\Support\Facades\Log::error("Failed to provision sandbox DB for user {$user->id}: " . $e->getMessage());
        }
        
        Auth::login($user);
        ActivityLogger::log('register', "New student registered: {$user->email}");
        
        return redirect()->route('student.home');
    }
}
