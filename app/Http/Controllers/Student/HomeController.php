<?php
namespace App\Http\Controllers\Student;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Announcement;
use App\Models\ClassEnrollment;
use App\Models\Exam;

class HomeController extends Controller {
    public function index() {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        $classIds = $isAdmin
            ? \App\Models\Classes::where('is_active', true)->pluck('id')
            : ClassEnrollment::where('user_id', $user->id)->pluck('class_id');
        
        $announcements = Announcement::where(function($q) use ($classIds) {
            $q->whereNull('class_id')->orWhereIn('class_id', $classIds);
        })->orderByDesc('is_pinned')->orderByDesc('created_at')->take(10)->with('creator')->get();
        
        $classes = \App\Models\Classes::whereIn('id', $classIds)->where('is_active', true)->with('assignments', 'exams')->get();
        
        $openExams = Exam::whereIn('class_id', $classIds)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with('class')
            ->get();
        
        return view('student.home', compact('announcements', 'classes', 'openExams'));
    }
}
