<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLogger;

class LoginController extends Controller {
    public function showLoginForm(Request $request) {
        return view('auth.login', [
            'registrationEnabled' => \App\Models\Setting::get('registration_enabled', '1'),
            'inactivityLogout' => $request->query('reason') === 'inactivity',
        ]);
    }
    
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account has been deactivated.']);
            }
            
            $request->session()->regenerate();
            ActivityLogger::log('login', "User {$user->email} logged in", ['ip' => $request->ip()]);
            
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('student.home');
        }
        
        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }
    
    public function logout(Request $request) {
        $userId = Auth::id();
        \Illuminate\Support\Facades\Cache::forget("last_active_{$userId}");
        
        Auth::logout();
        $request->session()->flush();
        $request->session()->regenerate();
        
        return redirect()->route('login');
    }
}
