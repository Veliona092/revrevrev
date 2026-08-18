<?php

namespace App\Http\Controllers;

use App\Models\BoardExamMaterial;
use App\Models\BoardExamMaterialFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TeacherBoardExamModuleController extends Controller
{
    private function ensureTeacher(): void
    {
        if (Auth::user()->role !== 'teacher') {
            abort(403, 'Only teachers can manage their own Board Exam Materials.');
        }
    }

    /**
     * Kunin ang program ng naka-login na teacher. Kung wala (edge case),
     * i-block ang access dahil hindi natin malalaman kung saang program
     * dapat i-scope ang mga materials nila.
     */
    private function currentTeacherProgram(): string
    {
        $program = Auth::user()->program;

        if (empty($program)) {
            abort(403, 'Your account has no assigned program. Contact an admin.');
        }

        return $program;
    }

    /**
     * Teacher's own materials list — naka-lock sa sariling program,
     * individual ownership lang (created_by = sarili).
     */
    public function index()
    {
        $this->ensureTeacher();

        $program = $this->currentTeacherProgram();

        $materials = BoardExamMaterial::where('program', $program)
            ->ownedBy(Auth::id())
            ->with('files')
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.teacher.board-exam-modules.index', [
            'materials' => $materials,
            'program' => $program,
        ]);
    }

    /**
     * Gumawa ng bagong material. Palaging nagsisimula sa 'pending' status.
     */
    public function store(Request $request)
    {
        $this->ensureTeacher();

        $program = $this->currentTeacherProgram();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $maxOrder = BoardExamMaterial::where('program', $program)
            ->ownedBy(Auth::id())
            ->max('order') ?? 0;

        $material = BoardExamMaterial::create([
            'program' => $program,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'created_by' => Auth::id(),
            'status' => 'pending',
            'order' => $maxOrder + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Material created. Pending admin approval.', 'material' => $material]);
        }

        return redirect()->back()->with('success', 'Material created successfully. It is now pending admin approval.');
    }

    /**
     * I-update ang isang material. Kung may bagong edit matapos ma-reject,
     * bumabalik ito sa 'pending' status para ma-review ulit.
     */
    public function update(Request $request, BoardExamMaterial $material)
    {
        $this->ensureTeacher();

        $this->authorizeOwnership($material);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order' => 'sometimes|integer|min:0',
        ]);

        $material->update($validated);

        // Anumang edit ay nangangahulugan na kailangan ng bagong review —
        // hindi dapat manatiling 'approved' ang lumang content kung binago na ito.
        if ($material->wasChanged(['title', 'description'])) {
            $material->update([
                'status' => 'pending',
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        }

        return redirect()->back()->with('success', 'Material updated successfully. It is now pending admin approval.');
    }

    public function destroy(BoardExamMaterial $material)
    {
        $this->ensureTeacher();

        $this->authorizeOwnership($material);

        foreach ($material->files as $file) {
            \Storage::disk('public')->delete($file->file_path);
        }

        $material->delete(); // cascades to files/progress via FK

        return redirect()->back()->with('success', 'Material deleted successfully.');
    }

    /**
     * Mag-upload ng isa o higit pang files sa isang material.
     * Nagre-reset din sa 'pending' — dahil bagong content ito.
     */
    public function storeFiles(Request $request, BoardExamMaterial $material)
    {
        $this->ensureTeacher();

        $this->authorizeOwnership($material);

        $validated = $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:102400|mimes:pdf,ppt,pptx,docx,mov',
        ]);

        $maxOrder = $material->files()->max('order') ?? 0;
        $created = [];

        foreach ($request->file('files') as $index => $file) {
            $extension = strtolower($file->extension());
            $uniqueName = time().'_'.Str::random(8).'.'.$extension;
            $subPath = "board-exam-materials/{$material->id}";
            $filePath = $file->storeAs($subPath, $uniqueName, 'public');

            $fileType = match ($extension) {
                'pdf' => 'pdf',
                'ppt' => 'ppt',
                'pptx' => 'pptx',
                'docx' => 'docx',
                'mov' => 'mov',
                default => null,
            };

            $created[] = BoardExamMaterialFile::create([
                'board_exam_material_id' => $material->id,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'original_name' => $file->getClientOriginalName(),
                'order' => $maxOrder + $index + 1,
            ]);
        }

        // Bagong files = bagong content, kailangan ng bagong review
        $material->update([
            'status' => 'pending',
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => count($created).' file(s) uploaded. Pending admin approval.', 'files' => $created]);
        }

        return redirect()->back()->with('success', count($created).' file(s) uploaded successfully. Material is now pending admin approval.');
    }

    public function destroyFile(BoardExamMaterialFile $file)
    {
        $this->ensureTeacher();

        $this->authorizeOwnership($file->material);

        \Storage::disk('public')->delete($file->file_path);
        $file->delete();

        return redirect()->back()->with('success', 'File removed successfully.');
    }

    /**
     * Siguraduhin na ang naka-login na teacher ang gumawa ng material na ito.
     * Individual ownership — hindi dapat magkahalo kahit magkaparehong program.
     */
    private function authorizeOwnership(BoardExamMaterial $material): void
    {
        if ($material->created_by !== Auth::id()) {
            abort(403, 'You can only manage materials you created.');
        }
    }
}