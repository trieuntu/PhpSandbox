<?php
namespace App\Http\Controllers\Student;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Classes;
use App\Models\Assignment;
use App\Models\ClassEnrollment;
use App\Models\Submission;

class ClassController extends Controller {
    public function index() {
        $classIds = ClassEnrollment::where('user_id', Auth::id())->pluck('class_id');
        $classes = Classes::whereIn('id', $classIds)->where('is_active', true)->with('assignments', 'exams')->get();
        return view('student.class.index', compact('classes'));
    }
    
    public function show(Classes $class) {
        $this->authorizeEnrollment($class);
        if (!$class->is_active) abort(403, 'Lớp học này hiện không khả dụng.');
        $assignments = $class->assignments()->where('is_active', true)->orderByDesc('created_at')->get();
        $exams = $class->exams()->where('is_active', true)->orderByDesc('starts_at')->get();
        return view('student.class.show', compact('class', 'assignments', 'exams'));
    }
    
    public function assignment(Classes $class, Assignment $assignment) {
        $this->authorizeEnrollment($class);
        if (!$class->is_active) abort(403, 'Lớp học này hiện không khả dụng.');
        if (!$assignment->is_active) {
            return redirect()->route('student.classes.show', $class)
                ->with('error', 'Bài tập "' . $assignment->title . '" hiện không được kích hoạt.');
        }
        $user = Auth::user();
        $submissions = Submission::where('user_id', $user->id)
            ->where('assignment_id', $assignment->id)
            ->orderByDesc('submitted_at')
            ->take(10)
            ->get();
        return view('student.class.assignment', compact('class', 'assignment', 'submissions'));
    }
    
    private function authorizeEnrollment(Classes $class): void {
        $enrolled = ClassEnrollment::where('class_id', $class->id)->where('user_id', Auth::id())->exists();
        if (!$enrolled) abort(403, 'You are not enrolled in this class.');
    }
}
