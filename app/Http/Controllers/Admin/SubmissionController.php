<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\User;
use App\Models\Exam;
use App\Services\SandboxService;

class SubmissionController extends Controller {
    public function index(Request $request) {
        $query = Submission::with('user', 'assignment', 'exam')->orderByDesc('submitted_at');
        
        if ($userId = $request->input('user_id')) $query->where('user_id', $userId);
        if ($examId = $request->input('exam_id')) $query->where('exam_id', $examId);
        if ($status = $request->input('status')) $query->where('execution_status', $status);
        if ($from = $request->input('from')) $query->where('submitted_at', '>=', $from);
        if ($to = $request->input('to')) $query->where('submitted_at', '<=', $to . ' 23:59:59');
        
        $submissions = $query->paginate(20);
        $users = User::where('role', 'student')->orderBy('first_name')->orderBy('last_name')->get();
        $exams = Exam::orderByDesc('starts_at')->get();
        
        return view('admin.submissions.index', compact('submissions', 'users', 'exams'));
    }
    
    public function show(Submission $submission) {
        $submission->load('user', 'assignment', 'exam');
        return view('admin.submissions.show', compact('submission'));
    }

    public function rerun(Submission $submission, SandboxService $sandboxService) {
        $user = $submission->user;
        $contextType = $submission->assignment_id ? 'assignment' : ($submission->exam_id ? 'exam' : 'free');
        $contextId = $submission->assignment_id ?: ($submission->exam_id ?: null);

        $files = $submission->files ?? [];
        $result = $files
            ? $sandboxService->executeWithPost($user, $submission->code, [], $contextType, $contextId, $files, '', 'GET')
            : $sandboxService->execute($user, $submission->code, $contextType, $contextId);

        $submission->update([
            'output_html' => $result['output'] ?? '',
            'output_errors' => $result['errors'] ?? '',
            'execution_status' => $result['status'] ?? 'error',
            'execution_time_ms' => $result['execution_time_ms'] ?? 0,
            'memory_used_kb' => $result['memory_kb'] ?? 0,
        ]);

        return back()->with('success', 'Đã chạy lại code sinh viên thành công.');
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
