<?php

namespace App\Http\Controllers;

use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\ModuleSubpart;
use App\Models\SubpartLesson;
use App\Models\SubpartProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubpartLessonController extends Controller
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
    }

    /**
     * Teacher: list lessons for a sub-part (management view).
     */
    public function index(ModuleSubpart $subpart)
    {
        $this->assertCanManage($subpart->module);

        return response()->json([
            'success' => true,
            'lessons' => $subpart->lessons()->get(),
        ]);
    }

    /**
     * Teacher: create a lesson under a sub-part.
     */
    public function store(Request $request, ModuleSubpart $subpart)
    {
        $this->assertCanManage($subpart->module);

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
            $filePath = $file->storeAs("modules/{$subpart->module_id}/subparts/{$subpart->id}/lessons", $uniqueName, 'public');
            $fileType = match ($extension) {
                'pdf' => 'pdf',
                'ppt' => 'ppt',
                'pptx' => 'pptx',
                'doc', 'docx' => 'docx',
                'mov' => 'mov',
                default => null,
            };
        }

        $order = $validated['order'] ?? ((int) $subpart->lessons()->max('order') + 1);

        $lesson = SubpartLesson::create([
            'subpart_id' => $subpart->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'body' => $validated['body'] ?? null,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'order' => $order,
        ]);

        return response()->json(['success' => true, 'lesson' => $lesson]);
    }

    /**
     * Teacher: update a lesson (metadata and/or replace file).
     */
    public function update(Request $request, SubpartLesson $lesson)
    {
        $this->assertCanManage($lesson->subpart->module);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'body' => 'nullable|string',
            'file' => 'nullable|file|max:102400|mimes:pdf,doc,docx,ppt,pptx,mov',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('file')) {
            if ($lesson->file_path) {
                Storage::disk('public')->delete($lesson->file_path);
            }

            $file = $request->file('file');
            $extension = strtolower($file->extension());
            $uniqueName = time().'_'.Str::random(8).'.'.$extension;
            $validated['file_path'] = $file->storeAs(
                "modules/{$lesson->subpart->module_id}/subparts/{$lesson->subpart_id}/lessons",
                $uniqueName,
                'public'
            );
            $validated['file_type'] = match ($extension) {
                'pdf' => 'pdf',
                'ppt' => 'ppt',
                'pptx' => 'pptx',
                'doc', 'docx' => 'docx',
                'mov' => 'mov',
                default => null,
            };
        }

        $lesson->update($validated);

        return response()->json(['success' => true, 'lesson' => $lesson->fresh()]);
    }

    /**
     * Teacher: reorder lessons within a sub-part in one shot. Expects
     * { "order": [lesson_id, lesson_id, ...] } in the new sequence.
     */
    public function reorder(Request $request, ModuleSubpart $subpart)
    {
        $this->assertCanManage($subpart->module);

        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:subpart_lessons,id',
        ]);

        $lessonIds = $subpart->lessons()->pluck('id');
        $requestedIds = collect($validated['order']);

        abort_unless(
            $requestedIds->diff($lessonIds)->isEmpty() && $lessonIds->diff($requestedIds)->isEmpty(),
            422,
            'Order list must exactly match this sub-part\'s lessons.'
        );

        foreach ($validated['order'] as $index => $lessonId) {
            SubpartLesson::where('id', $lessonId)->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Teacher: delete a lesson.
     */
    public function destroy(SubpartLesson $lesson)
    {
        $this->assertCanManage($lesson->subpart->module);

        if ($lesson->file_path) {
            Storage::disk('public')->delete($lesson->file_path);
        }

        $lesson->delete();

        return response()->json(['success' => true]);
    }

    public function viewFile(SubpartLesson $lesson)
    {
        $this->assertCanView($lesson->subpart->module);
        $path = storage_path('app/public/'.$lesson->file_path);

        abort_unless($lesson->file_path && is_file($path), 404);

        $contentType = match ($lesson->file_type) {
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

    public function docxViewer(SubpartLesson $lesson)
    {
        $this->assertCanView($lesson->subpart->module);

        abort_unless($lesson->file_path && is_file(storage_path('app/public/'.$lesson->file_path)), 404);
        abort_unless($lesson->file_type === 'docx', 404);

        $docxUrl = route('subpart.lessons.view', $lesson);
        $module = $lesson;

        return view('pages.student.docx-viewer', compact('docxUrl', 'module'));
    }

    /**
     * Student: list lessons for a sub-part with this student's progress.
     */
    public function studentIndex(ModuleSubpart $subpart)
    {
        $this->assertCanView($subpart->module);

        $user = Auth::user();
        $lessons = $subpart->lessons()->get();

        $progressByLesson = LessonProgress::whereIn('lesson_id', $lessons->pluck('id'))
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('lesson_id');

        return response()->json([
            'success' => true,
            'lessons' => $lessons->map(function (SubpartLesson $lesson) use ($progressByLesson) {
                $progress = $progressByLesson->get($lesson->id);

                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'body' => $lesson->body,
                    'file_path' => $lesson->file_path,
                    'file_type' => $lesson->file_type,
                    'order' => $lesson->order,
                    'progress' => $progress->progress ?? 0,
                    'completed' => (bool) ($progress->completed ?? false),
                ];
            }),
        ]);
    }

    /**
     * Student: update progress on a single lesson. Progress only ever moves
     * forward, never backward — same rule as ModuleSubpartController's
     * updateProgress(). Triggers two levels of roll-up: this sub-part's
     * effective progress, then the module's overall ModuleProgress.
     */
    public function updateProgress(Request $request, SubpartLesson $lesson)
    {
        $user = Auth::user();
        $subpart = $lesson->subpart;
        $this->assertCanView($subpart->module);

        $validated = $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
            'completed' => 'sometimes|boolean',
            'scroll_position' => 'nullable|integer|min:0',
        ]);

        $existing = LessonProgress::firstOrNew([
            'lesson_id' => $lesson->id,
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

        $this->syncSubpartProgress($subpart, $user->id);

        return response()->json(['success' => true, 'progress' => $existing->progress, 'completed' => $existing->completed]);
    }

    /**
     * Recompute this sub-part's effective progress for the student as the
     * average of their lesson_progress rows, then write it into the existing
     * SubpartProgress table (same row shape ModuleSubpartController and the
     * rest of the app already read for leaf sub-parts) so ModuleProgress
     * roll-up keeps working unchanged — no parallel aggregation path needed.
     */
    private function syncSubpartProgress(ModuleSubpart $subpart, int $userId): void
    {
        $lessonIds = $subpart->lessons()->pluck('id');

        if ($lessonIds->isEmpty()) {
            return;
        }

        $progressRows = LessonProgress::whereIn('lesson_id', $lessonIds)
            ->where('user_id', $userId)
            ->get();

        $average = $lessonIds->count() > 0
            ? round($progressRows->sum('progress') / $lessonIds->count(), 2)
            : 0;

        $allCompleted = $progressRows->count() === $lessonIds->count()
            && $progressRows->every(fn ($row) => $row->completed);

        SubpartProgress::updateOrCreate(
            ['subpart_id' => $subpart->id, 'user_id' => $userId],
            [
                'progress' => $average,
                'completed' => $allCompleted,
                'completed_at' => $allCompleted ? now() : null,
            ]
        );

        $this->syncModuleProgress($subpart->module, $userId);
    }

    /**
     * Identical to ModuleSubpartController::syncModuleProgress() — kept here
     * too so a lesson-progress save fully rolls up to the module badge
     * without requiring a call back into the other controller.
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
