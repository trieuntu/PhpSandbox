<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;

class LogController extends Controller {
    public function index(Request $request) {
        $query = ActivityLog::with('user')->orderByDesc('created_at');
        
        if ($userId = $request->input('user_id')) $query->where('user_id', $userId);
        if ($action = $request->input('action')) $query->where('action', $action);
        if ($from = $request->input('from')) $query->where('created_at', '>=', $from);
        
        $logs = $query->paginate(50);
        $users = User::orderBy('name')->get();
        $actions = ActivityLog::distinct()->pluck('action')->sort()->values();
        
        return view('admin.logs.index', compact('logs', 'users', 'actions') + ['actionTypes' => $actions]);
    }
}
