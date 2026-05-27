<?php
namespace App\Services;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger {
    public static function log(string $action, string $description = '', array $metadata = [], ?int $userId = null): void {
        try {
            ActivityLog::create([
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'description' => $description,
                'metadata' => !empty($metadata) ? $metadata : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("ActivityLogger failed: " . $e->getMessage());
        }
    }
}
