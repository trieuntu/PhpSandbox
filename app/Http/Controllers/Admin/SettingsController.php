<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;

class SettingsController extends Controller {
    public function index() {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }
    
    public function update(Request $request) {
        $data = $request->validate([
            'registration_enabled' => 'nullable',
            'inactivity_timeout_minutes' => 'required|integer|in:5,10,15,30,60',
            'max_execution_time_seconds' => 'required|integer|min:1|max:30',
            'sandbox_memory_limit_mb' => 'required|integer|min:16|max:256',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer',
            'smtp_user' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_from_address' => 'nullable|email',
            'smtp_from_name' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|string',
        ]);
        
        $userId = auth()->id();
        $data['registration_enabled'] = $request->boolean('registration_enabled') ? '1' : '0';
        
        foreach ($data as $key => $value) {
            Setting::set($key, $value ?? '', $userId);
        }
        
        return redirect()->route('admin.settings.index')->with('success', 'Settings saved.');
    }
    
    public function testEmail(Request $request) {
        $request->validate(['email' => 'required|email']);
        
        try {
            Mail::raw('This is a test email from PHP Sandbox system.', function($msg) use ($request) {
                $msg->to($request->input('email'))->subject('PHP Sandbox Test Email');
            });
            return response()->json(['success' => true, 'message' => 'Test email sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
