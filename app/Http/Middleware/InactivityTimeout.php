<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class InactivityTimeout {
    public function handle(Request $request, Closure $next) {
        if (!Auth::check()) {
            return $next($request);
        }
        
        // Skip for API heartbeat
        if ($request->is('api/heartbeat')) {
            return $next($request);
        }
        
        $userId = Auth::id();
        $cacheKey = "last_active_{$userId}";
        $timeoutMinutes = (int) Setting::get('inactivity_timeout_minutes', 15);
        
        $lastActive = Cache::get($cacheKey);
        
        if ($lastActive && (time() - (int)$lastActive) >= ($timeoutMinutes * 60)) {
            Auth::logout();
            $request->session()->flush();
            $request->session()->regenerate();
            Cache::forget($cacheKey);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Session expired due to inactivity.'], 401);
            }
            return redirect()->route('login')->with('inactivity', true);
        }
        
        // Update last active time (store as unix timestamp to avoid Carbon serialization issues)
        Cache::put($cacheKey, time(), $timeoutMinutes * 60 + 300);
        
        // Update user's last_active_at in DB (throttled - only every 60 seconds)
        $dbKey = "last_active_db_{$userId}";
        if (!Cache::has($dbKey)) {
            Auth::user()->update(['last_active_at' => now()]);
            Cache::put($dbKey, true, 60);
        }
        
        return $next($request);
    }
}
