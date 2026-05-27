<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Submission;
use App\Models\Exam;
use App\Models\ActivityLog;

class DashboardController extends Controller {
    public function index() {
        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'active_today' => User::where('last_active_at', '>=', today())->count(),
            'submissions_today' => Submission::where('submitted_at', '>=', today())->count(),
            'submissions_week' => Submission::where('submitted_at', '>=', now()->startOfWeek())->count(),
            'submissions_total' => Submission::count(),
            'open_exams' => Exam::where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now())->count(),
        ];
        
        $recentSubmissions = Submission::with('user', 'assignment', 'exam')->orderByDesc('submitted_at')->take(10)->get();
        $recentLogs = ActivityLog::with('user')->orderByDesc('created_at')->take(20)->get();
        
        return view('admin.dashboard', compact('stats', 'recentSubmissions', 'recentLogs'));
    }
}
