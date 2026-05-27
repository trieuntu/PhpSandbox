<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Announcement;
use App\Models\Classes;

class AnnouncementController extends Controller {
    public function index() {
        $announcements = Announcement::with('creator', 'class')->orderByDesc('is_pinned')->orderByDesc('created_at')->paginate(20);
        return view('admin.announcements.index', compact('announcements'));
    }
    
    public function create() {
        $classes = Classes::where('is_active', true)->get();
        return view('admin.announcements.form', compact('classes'));
    }
    
    public function store(Request $request) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'class_id' => 'nullable|exists:classes,id',
            'is_pinned' => 'boolean',
        ]);
        $data['created_by'] = Auth::id();
        $data['is_pinned'] = $request->boolean('is_pinned');
        Announcement::create($data);
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement created.');
    }
    
    public function destroy(Announcement $announcement) {
        $announcement->delete();
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted.');
    }

    public function edit(Announcement $announcement) {
        $classes = Classes::where('is_active', true)->get();
        return view('admin.announcements.form', compact('classes', 'announcement'));
    }

    public function update(Request $request, Announcement $announcement) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'class_id' => 'nullable|exists:classes,id',
            'is_pinned' => 'boolean',
        ]);
        $data['is_pinned'] = $request->boolean('is_pinned');
        $announcement->update($data);
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }
}
