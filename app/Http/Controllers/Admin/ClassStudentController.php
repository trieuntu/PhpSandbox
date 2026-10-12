<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ClassStudentController extends Controller {
    public function search(Request $request, Classes $class) {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 1) return response()->json([]);

        $like = '%' . addcslashes($q, '%_\\') . '%';
        $users = User::where('role', 'student')
            ->where('is_active', true)
            ->whereNotIn('id', $class->enrollments()->select('user_id'))
            ->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhere('first_name', 'like', $like)
                  ->orWhere('last_name', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('student_id', 'like', $like);
            })
            ->orderBy('first_name')->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'student_id']);

        return response()->json($users);
    }

    public function store(Request $request, Classes $class) {
        $request->validate([
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'integer',
            'identifiers'   => 'nullable|string|max:50000',
        ]);

        $ids = collect($request->input('student_ids', []))->map(fn($id) => (int) $id);
        $notFound = [];

        $tokens = preg_split('/[\s,;]+/u', (string) $request->input('identifiers', ''), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tokens as $token) {
            $user = User::where('role', 'student')
                ->where(fn($w) => $w->where('student_id', $token)->orWhere('email', strtolower($token)))
                ->first();
            $user ? $ids->push($user->id) : $notFound[] = $token;
        }

        $valid = User::where('role', 'student')->whereIn('id', $ids->unique())->pluck('id');
        $existing = $class->enrollments()->whereIn('user_id', $valid)->pluck('user_id');
        $toAdd = $valid->diff($existing);

        $class->students()->attach($toAdd->mapWithKeys(fn($id) => [$id => ['enrolled_at' => now()]])->all());

        if ($toAdd->isNotEmpty()) {
            ActivityLogger::log('class_students_added', "Added {$toAdd->count()} students to class {$class->name}", ['class_id' => $class->id]);
        }

        $message = "Đã thêm {$toAdd->count()} sinh viên";
        if ($existing->isNotEmpty()) $message .= ", {$existing->count()} đã có trong lớp";
        $response = back()->with($toAdd->isEmpty() && $notFound ? 'error' : 'success', $message . '.');
        if ($notFound) {
            $response->with('warning', 'Không tìm thấy: ' . implode(', ', array_slice($notFound, 0, 20)));
        }
        return $response;
    }

    public function destroy(Classes $class, User $user) {
        $class->students()->detach($user->id);
        ActivityLogger::log('class_student_removed', "Removed {$user->email} from class {$class->name}", ['class_id' => $class->id]);
        return back()->with('success', 'Đã xóa sinh viên khỏi lớp.');
    }
}
