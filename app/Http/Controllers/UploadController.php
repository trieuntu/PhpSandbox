<?php
namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller {
    // Admin-only: used by the rich text editor.
    public function store(Request $request) {
        $request->validate([
            'file' => 'required|file|mimes:png,jpg,jpeg,gif,webp|max:5120',
        ]);

        $file = $request->file('file');
        $uuid = (string) Str::uuid();
        $path = $file->storeAs('editor-images', $uuid . '.' . $file->extension(), 'local');

        Upload::create([
            'uuid'          => $uuid,
            'path'          => $path,
            'mime'          => $file->getMimeType(),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'size'          => $file->getSize(),
            'user_id'       => $request->user()->id,
        ]);

        return response()->json(['location' => route('media.show', $uuid, false)]);
    }

    public function show(string $uuid) {
        $upload = Upload::where('uuid', $uuid)->firstOrFail();
        abort_unless(Storage::disk('local')->exists($upload->path), 404);

        return Storage::disk('local')->response($upload->path, null, [
            'Content-Type'           => $upload->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=86400',
        ]);
    }
}
