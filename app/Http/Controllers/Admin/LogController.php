<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\ActivityLog;
use App\Models\User;

class LogController extends Controller {
    public function index(Request $request) {
        $query = ActivityLog::with('user')->orderByDesc('created_at');
        
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        
        $logs = $query->paginate(50);
        $users = User::orderBy('name')->get();
        $actions = Cache::remember('log_action_types', 600, fn () =>
            ActivityLog::distinct()->pluck('action')->sort()->values()
        );
        
        return view('admin.logs.index', compact('logs', 'users', 'actions') + ['actionTypes' => $actions]);
    }
}
