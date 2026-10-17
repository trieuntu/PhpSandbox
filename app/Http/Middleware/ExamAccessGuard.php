<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Exam;
use App\Models\ExamAttempt;

class ExamAccessGuard {
    public function handle(Request $request, Closure $next, ...$guards) {
        $examId = $request->route('exam');
        $exam = $examId instanceof Exam ? $examId : Exam::findOrFail($examId);
        $userId = Auth::id();
        
        // Check exam is active and within time window
        if (!$exam->isOpen()) {
            return redirect()->route('student.exams.lobby', $exam)->with('error', 'Bài thi hiện chưa mở hoặc đã kết thúc.');
        }
        
        // Check student has a valid attempt
        $attempt = ExamAttempt::where('exam_id', $exam->id)->where('user_id', $userId)->first();
        if (!$attempt) {
            return redirect()->route('student.exams.lobby', $exam)->with('error', 'Bạn chưa bắt đầu bài thi.');
        }
        
        // Check attempt not expired or submitted
        if ($attempt->isSubmitted()) {
            return redirect()->route('student.exams.submitted', $exam)->with('info', 'Bạn đã nộp bài thi này rồi.');
        }
        
        if ($attempt->isExpired()) {
            return redirect()->route('student.exams.submitted', $exam)->with('warning', 'Thời gian làm bài của bạn đã hết.');
        }
        
        return $next($request);
    }
}
