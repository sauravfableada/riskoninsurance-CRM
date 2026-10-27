<?php

namespace App\Http\Controllers;

use App\Models\TutorialVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TutorialVideoController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()?->isAdmin()) {
                abort(403, 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video_file' => 'required|file|mimetypes:video/mp4,video/x-m4v,video/*|max:102400', // max 100MB
        ]);

        $path = $request->file('video_file')->store('tutorials', 'public');

        TutorialVideo::create([
            'title' => $request->title,
            'description' => $request->description,
            'video_path' => $path,
        ]);

        return back()->with('success', 'Tutorial video added successfully!');
    }

    public function update(Request $request, $id)
    {
        $tutorial = TutorialVideo::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/x-m4v,video/*|max:102400',
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
        ];

        if ($request->hasFile('video_file')) {
            if (Storage::disk('public')->exists($tutorial->video_path)) {
                Storage::disk('public')->delete($tutorial->video_path);
            }
            $data['video_path'] = $request->file('video_file')->store('tutorials', 'public');
        }

        $tutorial->update($data);

        return back()->with('success', 'Tutorial video updated successfully!');
    }

    public function destroy($id)
    {
        $tutorial = TutorialVideo::findOrFail($id);
        
        if (Storage::disk('public')->exists($tutorial->video_path)) {
            Storage::disk('public')->delete($tutorial->video_path);
        }
        
        $tutorial->delete();

        return back()->with('success', 'Tutorial video deleted successfully!');
    }
}
