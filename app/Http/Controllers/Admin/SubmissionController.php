<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\User;
use App\Models\Exam;

class SubmissionController extends Controller {
    public function index(Request $request) {
        $query = Submission::with('user', 'assignment', 'exam')->orderByDesc('submitted_at');
        
        if ($userId = $request->input('user_id')) $query->where('user_id', $userId);
        if ($examId = $request->input('exam_id')) $query->where('exam_id', $examId);
        if ($status = $request->input('status')) $query->where('execution_status', $status);
        if ($from = $request->input('from')) $query->where('submitted_at', '>=', $from);
        if ($to = $request->input('to')) $query->where('submitted_at', '<=', $to . ' 23:59:59');
        
        $submissions = $query->paginate(20);
        $users = User::where('role', 'student')->orderBy('name')->get();
        $exams = Exam::orderByDesc('starts_at')->get();
        
        return view('admin.submissions.index', compact('submissions', 'users', 'exams'));
    }
    
    public function show(Submission $submission) {
        $submission->load('user', 'assignment', 'exam');
        return view('admin.submissions.show', compact('submission'));
    }
    
    public function download(Submission $submission) {
        $filename = "submission_{$submission->id}_{$submission->user->student_id}.php";
        return response($submission->code)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
    
    public function downloadOutput(Submission $submission) {
        $filename = "output_{$submission->id}_{$submission->user->student_id}.html";
        return response($submission->output_html ?? '')
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
