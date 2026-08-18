<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lecture;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LectureController extends Controller
{
    /**
     * Show list of lectures (role-based)
     * - Admin: shows all lectures (future override if needed)
     * - Teacher: shows only their own lectures
     */

   
public function index()
{
    $user = Auth::user();
    $role = $user?->role ?? 'accountancy';

    if ($role === 'teacher') {
        $lectures = Lecture::where('teacher_id', $user->id)
                           ->latest()
                           ->paginate(10);

        return view('pages.teacher.lectures', compact('lectures'));
    }

    if ($role === 'admin') {
        $lectures = Lecture::latest()->paginate(10);
        return view('pages.admin.lectures', compact('lectures'));
    }

    // For now: students just see all lectures
    if (in_array($role, ['psych', 'educ', 'accountancy'])) {
        $lectures = Lecture::latest()->paginate(10);
        return view("pages.$role.lectures", compact('lectures'));
    }

    abort(403, 'Unauthorized');
}


public function create()
{
    $this->authorizeTeacher();

    // Changed from 'pages.teacher.lectures-create' → 'pages.teachers.create'
    return view('pages.teacher.create');
}

    /**
     * Store a newly uploaded lecture (teacher only)
     */
    public function store(Request $request)
    {
        $this->authorizeTeacher();

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'lecture_file'=> 'required|file|mimes:pdf,doc,docx,ppt,pptx,jpg,png,gif|max:25600', // 25MB
        ]);

        $file = $request->file('lecture_file');
        $teacherId = Auth::id();
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = "lectures/teacher_{$teacherId}/{$fileName}";

        // Store file in private storage
        Storage::put($filePath, file_get_contents($file));

        Lecture::create([
            'teacher_id'  => $teacherId,
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path'   => $filePath,
            'file_name'   => $file->getClientOriginalName(),
            'file_type'   => $file->extension(),
            'file_size'   => $file->getSize(),
            'mime_type'   => $file->getMimeType(),
        ]);

        return redirect()->route('lectures')
                         ->with('success', 'Lecture uploaded successfully!');
    }

    /**
     * Show form to edit an existing lecture (teacher only, own lecture)
     */
    public function edit($id)
    {
        $lecture = $this->getOwnLectureOrFail($id);

        return view('pages.teacher.lectures-edit', compact('lecture'));
    }

    /**
     * Update lecture (teacher only, own lecture)
     */
    public function update(Request $request, $id)
    {
        $lecture = $this->getOwnLectureOrFail($id);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'lecture_file'=> 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,jpg,png,gif|max:25600',
        ]);

        $data = [
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? $lecture->description,
        ];

        // If new file uploaded, replace old one
        if ($request->hasFile('lecture_file')) {
            $file = $request->file('lecture_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = "lectures/teacher_{$lecture->teacher_id}/{$fileName}";

            // Delete old file
            if (Storage::exists($lecture->file_path)) {
                Storage::delete($lecture->file_path);
            }

            Storage::put($filePath, file_get_contents($file));

            $data['file_path']   = $filePath;
            $data['file_name']   = $file->getClientOriginalName();
            $data['file_type']   = $file->extension();
            $data['file_size']   = $file->getSize();
            $data['mime_type']   = $file->getMimeType();
        }

        $lecture->update($data);

        return redirect()->route('lectures')
                         ->with('success', 'Lecture updated successfully!');
    }

    /**
     * Delete lecture (teacher only, own lecture) – hard delete
     */
    public function destroy($id)
    {
        $lecture = $this->getOwnLectureOrFail($id);

        // Delete physical file
        if (Storage::exists($lecture->file_path)) {
            Storage::delete($lecture->file_path);
        }

        $lecture->delete();

        return redirect()->route('lectures')
                         ->with('success', 'Lecture deleted successfully.');
    }

    // Helper: Ensure user is teacher and owns the lecture
 private function getLectureOrFail($id)
{
    // Just fetch the lecture by ID, no role restriction
    return Lecture::findOrFail($id);
}

    // Helper: Quick role check
    private function authorizeTeacher()
    {
        if (!Auth::check() || Auth::user()->role !== 'teacher') {
            abort(403, 'Only teachers can access this page.');
        }
    }
public function download($id)
{
    $lecture = $this->getLectureOrFail($id);

    if (!Storage::exists($lecture->file_path)) {
        abort(404, 'File not found.');
    }

    return Storage::download($lecture->file_path, $lecture->file_name);
}
/**
 * Fetch a lecture and ensure it belongs to the currently authenticated teacher.
 * Throws 404 if not found, 403 if not owned by this teacher.
 */
private function getOwnLectureOrFail($id)
{
    $lecture = Lecture::findOrFail($id);

    if ($lecture->teacher_id !== Auth::id()) {
        abort(403, 'You do not own this lecture.');
    }

    return $lecture;
}

}