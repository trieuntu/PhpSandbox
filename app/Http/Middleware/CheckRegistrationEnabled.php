<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;

class CheckRegistrationEnabled {
    public function handle(Request $request, Closure $next) {
        if (!Setting::get('registration_enabled', '1')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Registration is currently disabled.'], 403);
            }
            return redirect()->route('login')->with('error', 'Registration is currently disabled.');
        }
        return $next($request);
    }
}
