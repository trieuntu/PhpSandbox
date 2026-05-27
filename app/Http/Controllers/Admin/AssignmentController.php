<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Assignment;
use App\Models\Classes;

class AssignmentController extends Controller {
    public function index(Classes $class) {
        $assignments = $class->assignments()->orderByDesc('created_at')->paginate(20);
        return view('admin.assignments.index', compact('class', 'assignments'));
    }
    
    public function create(Classes $class) { return view('admin.assignments.form', compact('class')); }
    
    public function store(Request $request, Classes $class) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'due_at' => 'nullable|date',
        ]);
        $data['class_id'] = $class->id;
        $data['created_by'] = Auth::id();
        $data['is_active'] = $request->boolean('is_active', true);
        Assignment::create($data);
        return redirect()->route('admin.classes.show', $class)->with('success', 'Assignment created.');
    }
    
    public function edit(Classes $class, Assignment $assignment) { return view('admin.assignments.form', compact('class', 'assignment')); }

    public function show(Classes $class, Assignment $assignment) {
        $submissions = $assignment->submissions()->with('user')->orderByDesc('submitted_at')->paginate(20);
        return view('admin.assignments.show', compact('class', 'assignment', 'submissions'));
    }

    public function update(Request $request, Classes $class, Assignment $assignment) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'due_at' => 'nullable|date',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $assignment->update($data);
        return redirect()->route('admin.classes.show', $class)->with('success', 'Assignment updated.');
    }
    
    public function destroy(Classes $class, Assignment $assignment) {
        $assignment->delete();
        return redirect()->route('admin.classes.show', $class)->with('success', 'Assignment deleted.');
    }

    public function toggle(Classes $class, Assignment $assignment) {
        $assignment->update(['is_active' => !$assignment->is_active]);
        $state = $assignment->is_active ? 'kích hoạt' : 'ẩn';
        return back()->with('success', "Bài tập \"{$assignment->title}\" đã được {$state}.");
    }
}
