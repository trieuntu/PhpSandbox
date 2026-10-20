<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Exam;
use App\Models\Classes;
use App\Models\ExamAttempt;
use App\Models\Submission;
use Illuminate\Support\Str;
use App\Support\HtmlSanitizer;

class ExamController extends Controller {
    public function index() {
        $exams = Exam::with('class', 'creator')->withCount('attempts')->orderByDesc('created_at')->paginate(20);
        return view('admin.exams.index', compact('exams'));
    }
    
    public function create() {
        $classes = Classes::where('is_active', true)->get();
        return view('admin.exams.form', compact('classes'));
    }
    
    public function store(Request $request) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'class_id' => 'required|exists:classes,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'time_limit_minutes' => 'nullable|integer|min:1',
        ]);
        $data['created_by'] = Auth::id();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['instructions'] = HtmlSanitizer::clean($data['instructions'] ?? null);
        Exam::create($data);
        return redirect()->route('admin.exams.index')->with('success', 'Exam created.');
    }
    
    public function show(Exam $exam) {
        $exam->load('class');

        $submittedAttempts = \App\Models\ExamAttempt::with('user')
            ->where('exam_id', $exam->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->get();

        $userIds = $submittedAttempts->pluck('user_id');
        $latest = \App\Models\Submission::where('exam_id', $exam->id)
            ->whereIn('user_id', $userIds)
            ->orderByDesc('submitted_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn($g) => $g->first());

        foreach ($submittedAttempts as $attempt) {
            $attempt->latest_submission = $latest->get($attempt->user_id);
        }

        $submissions = \App\Models\Submission::with('user')
            ->where('exam_id', $exam->id)
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return view('admin.exams.show', compact('exam', 'submittedAttempts', 'submissions'));
    }
    
    public function edit(Exam $exam) {
        $classes = Classes::where('is_active', true)->get();
        return view('admin.exams.form', compact('exam', 'classes'));
    }
    
    public function update(Request $request, Exam $exam) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'class_id' => 'required|exists:classes,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'time_limit_minutes' => 'nullable|integer|min:1',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['instructions'] = HtmlSanitizer::clean($data['instructions'] ?? null);
        $exam->update($data);

        // Synchronize expires_at for all ongoing (unsubmitted) attempts
        if (!empty($exam->time_limit_minutes)) {
            $minutes = (int) $exam->time_limit_minutes;
            $exam->attempts()->whereNull('submitted_at')->get()->each(function ($attempt) use ($exam, $minutes) {
                $started = $attempt->started_at ? \Illuminate\Support\Carbon::parse($attempt->started_at) : now();
                $newExpires = $started->copy()->addMinutes($minutes);
                if ($exam->ends_at && $newExpires->isAfter($exam->ends_at)) {
                    $newExpires = \Illuminate\Support\Carbon::parse($exam->ends_at);
                }
                $attempt->update(['expires_at' => $newExpires]);
            });
        } elseif ($exam->ends_at) {
            $exam->attempts()->whereNull('submitted_at')->update(['expires_at' => $exam->ends_at]);
        }

        return redirect()->route('admin.exams.index')->with('success', 'Đã cập nhật bài thi và đồng bộ thời gian làm bài cho các sinh viên đang thi.');
    }
    
    public function destroy(Exam $exam) {
        $exam->delete();
        return redirect()->route('admin.exams.index')->with('success', 'Exam deleted.');
    }

    public function toggle(Exam $exam) {
        $exam->update(['is_active' => !$exam->is_active]);
        $state = $exam->is_active ? 'kích hoạt' : 'ẩn';
        return back()->with('success', "Kỳ thi \"{$exam->title}\" đã được {$state}.");
    }

    /**
     * Download grade report for the given exam as a UTF-8 BOM CSV file.
     */
    public function exportGrades(Exam $exam) {
        $exam->load('class.students');

        // All attempts for this exam
        $attempts = ExamAttempt::with('user', 'grader')
            ->where('exam_id', $exam->id)
            ->get()
            ->keyBy('user_id');

        // Latest submission for each student in this exam
        $latestSubmissions = Submission::with('grader')
            ->where('exam_id', $exam->id)
            ->orderByDesc('submitted_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn($g) => $g->first());

        // All enrolled students in class
        $enrolledStudents = $exam->class
            ? $exam->class->students()->where('role', 'student')->get()
            : collect();

        // Merge users: all enrolled + any student who took the exam
        $allUsers = $enrolledStudents->keyBy('id');
        foreach ($attempts as $userId => $attempt) {
            if (!$allUsers->has($userId) && $attempt->user) {
                $allUsers->put($userId, $attempt->user);
            }
        }

        // Sort by student_id or name
        $sortedUsers = $allUsers->sort(function ($a, $b) {
            if ($a->student_id && $b->student_id) {
                return strcmp($a->student_id, $b->student_id);
            }
            return strcmp($a->name ?? '', $b->name ?? '');
        });

        $fileName = 'bang_diem_' . Str::slug($exam->title, '_') . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($sortedUsers, $attempts, $latestSubmissions, $exam) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'STT',
                'Mã sinh viên',
                'Họ và tên đệm',
                'Tên',
                'Họ và tên đầy đủ',
                'Email',
                'Lớp',
                'Kỳ thi',
                'Bắt đầu thi',
                'Nộp bài lúc',
                'Thời lượng',
                'Trạng thái nộp',
                'Điểm số',
                'Trạng thái chấm',
                'Nhận xét của giáo viên',
                'Người chấm',
                'Thời gian chấm',
            ]);

            $stt = 1;
            foreach ($sortedUsers as $user) {
                $attempt = $attempts->get($user->id);
                $latestSub = $latestSubmissions->get($user->id);

                // Determine submission status
                if ($attempt && $attempt->submitted_at) {
                    $statusText = 'Đã nộp bài';
                } elseif ($attempt && $attempt->started_at) {
                    $statusText = 'Chưa nộp bài (đang làm / hết giờ)';
                } else {
                    $statusText = 'Vắng thi';
                }

                // Determine score
                $score = null;
                if ($latestSub && $latestSub->score !== null) {
                    $score = $latestSub->score;
                } elseif ($attempt && $attempt->score !== null) {
                    $score = $attempt->score;
                }

                $scoreDisplay = $score !== null ? (string)$score : '';
                $gradeStatus = $score !== null ? 'Đã chấm' : ($attempt && $attempt->submitted_at ? 'Chưa chấm' : '—');

                $feedback = $latestSub?->teacher_feedback ?? $attempt?->teacher_feedback ?? '';
                $gradedBy = $latestSub?->grader?->name ?? $attempt?->grader?->name ?? '';
                $gradedAt = $latestSub?->graded_at ? $latestSub->graded_at->format('d/m/Y H:i:s') : ($attempt?->graded_at ? $attempt->graded_at->format('d/m/Y H:i:s') : '');

                fputcsv($handle, [
                    $stt++,
                    $user->student_id ?? '',
                    $user->last_name ?? '',
                    $user->first_name ?? '',
                    $user->name ?? '',
                    $user->email ?? '',
                    $exam->class?->name ?? $user->class_name ?? '',
                    $exam->title,
                    $attempt?->started_at ? $attempt->started_at->format('d/m/Y H:i:s') : '',
                    $attempt?->submitted_at ? $attempt->submitted_at->format('d/m/Y H:i:s') : '',
                    $attempt ? $attempt->durationFormatted() : '',
                    $statusText,
                    $scoreDisplay,
                    $gradeStatus,
                    $feedback,
                    $gradedBy,
                    $gradedAt,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
