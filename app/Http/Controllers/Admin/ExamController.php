<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Exam;
use App\Models\Classes;
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
}
