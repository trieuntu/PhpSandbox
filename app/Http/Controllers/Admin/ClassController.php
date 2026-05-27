<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Classes;
use App\Models\User;

class ClassController extends Controller {
    public function index() {
        $classes = Classes::with('creator')->withCount('students', 'assignments', 'exams')->orderByDesc('created_at')->paginate(20);
        return view('admin.classes.index', compact('classes'));
    }

    public function create() {
        $allStudents = User::where('role', 'student')->where('is_active', true)->orderBy('name')->get();
        return view('admin.classes.form', compact('allStudents'));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
        $data['created_by'] = Auth::id();
        $data['is_active']  = $request->boolean('is_active', true);
        $class = Classes::create($data);

        // Sync student enrollments (set enrolled_at for new students)
        $studentIds = array_map('intval', $request->input('student_ids', []));
        $syncData = collect($studentIds)->mapWithKeys(fn($id) => [$id => ['enrolled_at' => now()]])->toArray();
        $class->students()->sync($syncData);

        return redirect()->route('admin.classes.index')->with('success', 'Đã tạo lớp học thành công.');
    }

    public function show(Classes $class) {
        $class->load('students', 'assignments', 'exams');
        return view('admin.classes.show', compact('class'));
    }

    public function edit(Classes $class) {
        $allStudents = User::where('role', 'student')->where('is_active', true)->orderBy('name')->get();
        return view('admin.classes.form', compact('class', 'allStudents'));
    }

    public function update(Request $request, Classes $class) {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $class->update($data);

        // Sync student enrollments (preserve existing enrolled_at, set now() for new)
        $existing = $class->students()->pluck('users.id')->toArray();
        $studentIds = array_map('intval', $request->input('student_ids', []));
        $syncData = collect($studentIds)->mapWithKeys(function($id) use ($existing) {
            return [$id => in_array($id, $existing) ? [] : ['enrolled_at' => now()]];
        })->toArray();
        $class->students()->sync($syncData);

        return redirect()->route('admin.classes.index')->with('success', 'Đã cập nhật lớp học.');
    }

    public function destroy(Classes $class) {
        $class->delete();
        return redirect()->route('admin.classes.index')->with('success', 'Đã xoá lớp học.');
    }

    public function toggle(Classes $class) {
        $class->update(['is_active' => !$class->is_active]);
        $state = $class->is_active ? 'kích hoạt' : 'ẩn';
        return back()->with('success', "Lớp \"{$class->name}\" đã được {$state}.");
    }
}
