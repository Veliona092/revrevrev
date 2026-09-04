<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\ModuleSubpart;
use App\Models\SubpartProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleSubpartController extends Controller
{
    private function assertCanManage(Module $module): void
    {
        $class = $module->class;
        $isOwnerOrAdmin = $class
            ? ($class->created_by === Auth::id() || Auth::user()->role === 'admin')
            : (Auth::user()->role === 'admin' || (int) $module->created_by === (int) Auth::id());

        abort_unless($isOwnerOrAdmin, 403);
    }

    private function assertCanView(Module $module): void
    {
        $user = Auth::user();
        $class = $module->class;

        $allowed = $user->role === 'admin'
            || ($class && $class->created_by === $user->id)
            || ($class && $class->users()->where('user_id', $user->id)->exists());

        abort_unless($allowed, 403);

        if ($user->role === 'student' && $class && $user->hasActiveFormalAssessment($class->id) && ! $module->is_formal_assessment) {
            abort(403, 'Access to lecture materials is locked while a Formal Assessment is in progress.');
        }
    }

    /**
     * Teacher: list sub-parts for a module (management view).
     */
    public function index(Module $module)
    {
        $this->assertCanManage($module);

        return response()->json([
            'success' => true,
            'subparts' => $module->subparts()->get(),
        ]);
    }

    /**
     * Teacher: create a sub-part.
     */
    public function store(Request $request, Module $module)
    {
        $this->assertCanManage($module);

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'body' => 'nullable|string',
            'file' => 'nullable|file|max:102400|mimes:pdf,doc,docx,ppt,pptx,mov',
            'order' => 'nullable|integer|min:0',
        ]);

        $filePath = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = strtolower($file->extension());
            $uniqueName = time().'_'.Str::random(8).'.'.$extension;
            $filePath = $file->storeAs("modules/{$module->id}/subparts", $uniqueName, 'public');
            $fileType = match ($extension) {
                'pdf' => 'pdf',
                'ppt' => 'ppt',
                'pptx' => 'pptx',
                'doc', 'docx' => 'docx',
                'mov' => 'mov',
                default => null,
            };
        }

        $order = $validated['order'] ?? ((int) $module->subparts()->max('order') + 1);

        $subpart = ModuleSubpart::create([
            'module_id' => $module->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'body' => $validated['body'] ?? null,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'order' => $order,
        ]);

        return response()->json(['success' => true, 'subpart' => $subpart]);
    }

    /**
     * Teacher: update a sub-part (metadata and/or replace file).
     */
    public function update(Request $request, ModuleSubpart $subpart)
    {
        $this->assertCanManage($subpart->module);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'body' => 'nullable|string',
            'file' => 'nullable|file|max:102400|mimes:pdf,doc,docx,ppt,pptx,mov',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('file')) {
            if ($subpart->file_path) {
                Storage::disk('public')->delete($subpart->file_path);
            }

            $file = $request->file('file');
            $extension = strtolower($file->extension());
            $uniqueName = time().'_'.Str::random(8).'.'.$extension;
            $subpart->file_path = $file->storeAs("modules/{$subpart->module_id}/subparts", $uniqueName, 'public');
            $subpart->file_type = match ($extension) {
                'pdf' => 'pdf',
                'ppt' => 'ppt',
                'pptx' => 'pptx',
                'doc', 'docx' => 'docx',
                'mov' => 'mov',
                default => null,
            };
        }

        $subpart->update($validated);

        return response()->json(['success' => true, 'subpart' => $subpart->fresh()]);
    }

    /**
     * Teacher: reorder sub-parts in one shot. Expects
     * { "order": [subpart_id, subpart_id, ...] } in the new sequence.
     */
    public function reorder(Request $request, Module $module)
    {
        $this->assertCanManage($module);

        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:module_subparts,id',
        ]);

        $subpartIds = $module->subparts()->pluck('id');
        $requestedIds = collect($validated['order']);

        abort_unless(
            $requestedIds->diff($subpartIds)->isEmpty() && $subpartIds->diff($requestedIds)->isEmpty(),
            422,
            'Order list must exactly match this module\'s sub-parts.'
        );

        foreach ($validated['order'] as $index => $subpartId) {
            ModuleSubpart::where('id', $subpartId)->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Teacher: delete a sub-part.
     */
    public function destroy(ModuleSubpart $subpart)
    {
        $this->assertCanManage($subpart->module);

        if ($subpart->file_path) {
            Storage::disk('public')->delete($subpart->file_path);
        }

        $subpart->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Student: list sub-parts for a module with this student's progress.
     */
    public function studentIndex(Module $module)
    {
        $this->assertCanView($module);

        $user = Auth::user();
        $subparts = $module->subparts()->get();

        $progressBySubpart = SubpartProgress::whereIn('subpart_id', $subparts->pluck('id'))
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('subpart_id');

        return response()->json([
            'success' => true,
            'subparts' => $subparts->map(function (ModuleSubpart $subpart) use ($progressBySubpart) {
                $progress = $progressBySubpart->get($subpart->id);

                return [
                    'id' => $subpart->id,
                    'title' => $subpart->title,
                    'description' => $subpart->description,
                    'body' => $subpart->body,
                    'file_path' => $subpart->file_path,
                    'file_type' => $subpart->file_type,
                    'order' => $subpart->order,
                    'progress' => $progress->progress ?? 0,
                    'completed' => (bool) ($progress->completed ?? false),
                ];
            }),
        ]);
    }

    public function viewFile(ModuleSubpart $subpart)
    {
        $this->assertCanView($subpart->module);
        $path = storage_path('app/public/'.$subpart->file_path);

        abort_unless($subpart->file_path && is_file($path), 404);

        $contentType = match ($subpart->file_type) {
            'mov' => 'video/quicktime',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/pdf',
        };

        return response()->file($path, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    public function pdfjsViewer(ModuleSubpart $subpart)
    {
        $this->assertCanView($subpart->module);

        abort_unless($subpart->file_path && is_file(storage_path('app/public/'.$subpart->file_path)), 404);
        abort_unless(in_array($subpart->file_type, ['pdf', 'ppt', 'pptx'], true), 404);

        $pdfUrl = route('module.subparts.view', $subpart);
        $module = $subpart;

        return view('pages.student.pdfjs-viewer', compact('pdfUrl', 'module'));
    }

    public function docxViewer(ModuleSubpart $subpart)
    {
        $this->assertCanView($subpart->module);

        abort_unless($subpart->file_path && is_file(storage_path('app/public/'.$subpart->file_path)), 404);
        abort_unless($subpart->file_type === 'docx', 404);

        $docxUrl = route('module.subparts.view', $subpart);
        $module = $subpart;

        return view('pages.student.docx-viewer', compact('docxUrl', 'module'));
    }

    /**
     * Student: update progress on a single sub-part. Mirrors
     * ClassManagerController::updateProgress() for whole modules — progress
     * only ever moves forward, never backward, per request.
     */
    public function updateProgress(Request $request, ModuleSubpart $subpart)
    {
        $user = Auth::user();
        $this->assertCanView($subpart->module);

        $validated = $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
            'completed' => 'sometimes|boolean',
            'scroll_position' => 'nullable|integer|min:0',
        ]);

        $existing = SubpartProgress::firstOrNew([
            'subpart_id' => $subpart->id,
            'user_id' => $user->id,
        ]);

        $newProgress = max((float) ($existing->progress ?? 0), (float) $validated['progress']);

        $existing->fill([
            'progress' => $newProgress,
            'scroll_position' => $validated['scroll_position'] ?? $existing->scroll_position,
            'completed' => $existing->completed || ($validated['completed'] ?? false),
        ]);

        if ($existing->completed && ! $existing->completed_at) {
            $existing->completed_at = now();
        }

        $existing->save();

        $this->syncModuleProgress($subpart->module, $user->id);

        return response()->json(['success' => true, 'progress' => $existing->progress, 'completed' => $existing->completed]);
    }

    /**
     * Recompute ModuleProgress.progress for this student as the average of
     * their subpart_progress rows, so the whole-module progress bar/badge
     * (module_progress table, still read elsewhere in the app) stays
     * accurate for lecture-style modules without duplicating tracking logic
     * in the frontend.
     */
    private function syncModuleProgress(Module $module, int $userId): void
    {
        $subpartIds = $module->subparts()->pluck('id');

        if ($subpartIds->isEmpty()) {
            return;
        }

        $progressRows = SubpartProgress::whereIn('subpart_id', $subpartIds)
            ->where('user_id', $userId)
            ->get();

        $average = $subpartIds->count() > 0
            ? round($progressRows->sum('progress') / $subpartIds->count(), 2)
            : 0;

        $allCompleted = $progressRows->count() === $subpartIds->count()
            && $progressRows->every(fn ($row) => $row->completed);

        ModuleProgress::updateOrCreate(
            ['module_id' => $module->id, 'user_id' => $userId],
            [
                'progress' => $average,
                'completed' => $allCompleted,
                'completed_at' => $allCompleted ? now() : null,
            ]
        );
    }
}
