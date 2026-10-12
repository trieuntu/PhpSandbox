<?php
namespace App\Http\Middleware;

use App\Models\Exam;
use Closure;
use Illuminate\Http\Request;

class EnsureEnrolledInExamClass {
    public function handle(Request $request, Closure $next) {
        $routeExam = $request->route('exam');
        $exam = $routeExam instanceof Exam ? $routeExam : Exam::findOrFail($routeExam);

        if (!$request->user()->isEnrolledIn($exam->class_id)) {
            abort(403, 'Bạn không thuộc lớp học của kỳ thi này.');
        }

        return $next($request);
    }
}
