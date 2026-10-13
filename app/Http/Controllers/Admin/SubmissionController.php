<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\User;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\SandboxService;

class SubmissionController extends Controller {
    public function index(Request $request) {
        $defaultTab = ($request->hasAny(['type', 'status', 'from', 'to']) && !$request->has('tab')) ? 'all' : 'submitted';
        $activeTab = $request->input('tab', $defaultTab);
        if (!in_array($activeTab, ['submitted', 'all'])) {
            $activeTab = 'submitted';
        }

        $exams = Exam::with('class')->orderByDesc('starts_at')->get();
        $submittedAttemptsTotal = ExamAttempt::whereNotNull('submitted_at')->count();
        $allSubmissionsTotal = Submission::count();

        $submittedAttempts = null;
        $submissions = null;

        if ($activeTab === 'submitted') {
            $attemptsQuery = ExamAttempt::with(['user', 'exam.class'])
                ->whereNotNull('submitted_at')
                ->orderByDesc('submitted_at');

            if ($search = trim((string) $request->input('search'))) {
                $attemptsQuery->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })->orWhereHas('exam', function ($eq) use ($search) {
                        $eq->where('title', 'like', "%{$search}%")
                            ->orWhereHas('class', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%");
                            });
                    });
                });
            }

            if ($examId = $request->input('exam_id')) {
                $attemptsQuery->where('exam_id', $examId);
            }

            $submittedAttempts = $attemptsQuery->paginate(20)->withQueryString();

            // Map latest submission for each attempt on the current page
            $userIds = $submittedAttempts->pluck('user_id');
            $examIds = $submittedAttempts->pluck('exam_id');
            $latest = Submission::whereIn('user_id', $userIds)
                ->whereIn('exam_id', $examIds)
                ->orderByDesc('submitted_at')
                ->get()
                ->groupBy(fn($s) => $s->exam_id . '_' . $s->user_id)
                ->map(fn($g) => $g->first());

            foreach ($submittedAttempts as $attempt) {
                $attempt->latest_submission = $latest->get($attempt->exam_id . '_' . $attempt->user_id);
            }
        } else {
            $submissionsQuery = Submission::with('user', 'assignment.class', 'exam.class')
                ->orderByDesc('submitted_at');

            if ($search = trim((string) $request->input('search'))) {
                $submissionsQuery->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })->orWhereHas('exam', function ($eq) use ($search) {
                        $eq->where('title', 'like', "%{$search}%");
                    })->orWhereHas('assignment', function ($aq) use ($search) {
                        $aq->where('title', 'like', "%{$search}%");
                    });
                });
            }

            if ($userId = $request->input('user_id')) $submissionsQuery->where('user_id', $userId);
            if ($examId = $request->input('exam_id')) $submissionsQuery->where('exam_id', $examId);
            if ($status = $request->input('status')) $submissionsQuery->where('execution_status', $status);
            if ($type = $request->input('type')) {
                if ($type === 'exam') $submissionsQuery->whereNotNull('exam_id');
                elseif ($type === 'assignment') $submissionsQuery->whereNotNull('assignment_id');
                elseif ($type === 'free') $submissionsQuery->whereNull('exam_id')->whereNull('assignment_id');
            }
            if ($from = $request->input('from')) $submissionsQuery->where('submitted_at', '>=', $from);
            if ($to = $request->input('to')) $submissionsQuery->where('submitted_at', '<=', $to . ' 23:59:59');

            $submissions = $submissionsQuery->paginate(20)->withQueryString();
        }

        $users = User::where('role', 'student')->orderBy('first_name')->orderBy('last_name')->get();

        return view('admin.submissions.index', compact(
            'activeTab',
            'submittedAttempts',
            'submissions',
            'submittedAttemptsTotal',
            'allSubmissionsTotal',
            'exams',
            'users'
        ));
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
