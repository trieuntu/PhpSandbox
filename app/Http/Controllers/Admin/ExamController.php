<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Exam;
use App\Models\Classes;

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
        Exam::create($data);
        return redirect()->route('admin.exams.index')->with('success', 'Exam created.');
    }
    
    public function show(Exam $exam) {
        $exam->load('class', 'attempts.user');
        $submissions = \App\Models\Submission::with('user')
            ->where('exam_id', $exam->id)
            ->orderByDesc('submitted_at')
            ->paginate(20);
        return view('admin.exams.show', compact('exam', 'submissions'));
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
        $exam->update($data);
        return redirect()->route('admin.exams.index')->with('success', 'Exam updated.');
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
