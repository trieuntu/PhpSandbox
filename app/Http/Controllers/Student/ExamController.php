<?php
namespace App\Http\Controllers\Student;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Submission;

class ExamController extends Controller {
    public function lobby(Exam $exam) {
        $attempt = ExamAttempt::where('exam_id', $exam->id)->where('user_id', Auth::id())->first();
        return view('student.exam.lobby', compact('exam', 'attempt'));
    }
    
    public function start(Exam $exam) {
        $user = Auth::user();
        
        if (!$exam->isOpen()) {
            return redirect()->route('student.exams.lobby', $exam)->with('error', 'Exam is not currently active.');
        }
        
        $existing = ExamAttempt::where('exam_id', $exam->id)->where('user_id', $user->id)->first();
        if ($existing) {
            return redirect()->route('student.exams.editor', $exam);
        }
        
        $expiresAt = null;
        if ($exam->time_limit_minutes) {
            $expiresAt = now()->addMinutes($exam->time_limit_minutes);
            // Also cap at exam end time
            if ($expiresAt->isAfter($exam->ends_at)) {
                $expiresAt = $exam->ends_at;
            }
        }
        
        ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'expires_at' => $expiresAt,
        ]);
        
        \App\Services\ActivityLogger::log('exam_started', "Started exam: {$exam->title}", ['exam_id' => $exam->id]);
        
        return redirect()->route('student.exams.editor', $exam);
    }
    
    public function editor(Exam $exam) {
        $attempt = ExamAttempt::where('exam_id', $exam->id)->where('user_id', Auth::id())->firstOrFail();
        $lastSubmission = Submission::where('user_id', Auth::id())->where('exam_id', $exam->id)->orderByDesc('submitted_at')->first();

        $remainingSeconds = null;
        if ($exam->time_limit_minutes && $attempt->expires_at) {
            $remainingSeconds = max(0, now()->diffInSeconds($attempt->expires_at, false));
        } elseif ($exam->ends_at) {
            $remainingSeconds = max(0, now()->diffInSeconds($exam->ends_at, false));
        }

        return view('student.exam.editor', compact('exam', 'attempt', 'lastSubmission', 'remainingSeconds'));
    }

    public function submitted(Exam $exam) {
        $attempt = ExamAttempt::where('exam_id', $exam->id)->where('user_id', Auth::id())->first();
        $submission = Submission::where('user_id', Auth::id())->where('exam_id', $exam->id)->orderByDesc('submitted_at')->first();
        return view('student.exam.submitted', compact('exam', 'attempt', 'submission'));
    }

    public function submit(Exam $exam) {
        $user = Auth::user();
        $attempt = ExamAttempt::where('exam_id', $exam->id)->where('user_id', $user->id)->first();

        if ($attempt && !$attempt->submitted_at) {
            $attempt->update(['submitted_at' => now()]);
        }

        \App\Services\ActivityLogger::log('exam_submitted', "Submitted exam: {$exam->title}", ['exam_id' => $exam->id]);

        return redirect()->route('student.exams.submitted', $exam);
    }
}
