<?php

namespace App\Http\Controllers;

use App\Models\AssessmentAttemptGrant;
use App\Models\ClassModel;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\AiSettingsResolver;
use App\Services\CloudflareAI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Smalot\PdfParser\Parser;

class ClassManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function canManageClassStudents(ClassModel $class): bool
    {
        $actor = Auth::user();

        return $actor !== null
            && ($class->created_by === $actor->id || in_array($actor->role, ['admin', 'superadmin'], true));
    }

    private function normalizeExtractedContext(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_substr($text, 0, 8000)));
    }

    private function extractPdfContext(UploadedFile $file): ?string
    {
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            return null;
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($realPath);

        return $this->normalizeExtractedContext($pdf->getText());
    }

    private function extractTxtContext(UploadedFile $file): ?string
    {
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            return null;
        }

        $text = File::get($realPath);

        return $this->normalizeExtractedContext($text);
    }

    private function extractDocxContext(UploadedFile $file): ?string
    {
        if (! class_exists('ZipArchive')) {
            return null;
        }

        $realPath = $file->getRealPath();
        if ($realPath === false) {
            return null;
        }

        $zip = new \ZipArchive;
        if ($zip->open($realPath) !== true) {
            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xml) || $xml === '') {
            return null;
        }

        $text = strip_tags(str_replace(['</w:p>', '</w:tr>', '</w:tab>'], ["\n", "\n", "\t"], $xml));

        return $this->normalizeExtractedContext(html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }

    private function extractContextFileText(UploadedFile $file): ?string
    {
        return match (strtolower((string) $file->getClientOriginalExtension())) {
            'pdf' => $this->extractPdfContext($file),
            'doc' => $this->extractPdfContext($file),
            'txt' => $this->extractTxtContext($file),
            'docx' => $this->extractDocxContext($file),
            default => null,
        };
    }

    private function normalizeQuizQuestionsPayload(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return [];
        }

        $questionCandidates = [];

        if (array_is_list($decoded)) {
            $questionCandidates = $decoded;
        } elseif (isset($decoded['questions']) && is_array($decoded['questions'])) {
            $questionCandidates = $decoded['questions'];
        } elseif (isset($decoded['items']) && is_array($decoded['items'])) {
            $questionCandidates = $decoded['items'];
        } else {
            $questionCandidates = [$decoded];
        }

        $normalized = [];
        foreach ($questionCandidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            $questionText = trim((string) ($candidate['question'] ?? $candidate['question_text'] ?? $candidate['text'] ?? ''));
            if ($questionText === '') {
                continue;
            }

            $optionsRaw = $candidate['options'] ?? null;
            if (! is_array($optionsRaw)) {
                continue;
            }

            if (array_is_list($optionsRaw)) {
                if (count($optionsRaw) < 4) {
                    continue;
                }

                $options = [
                    'A' => trim((string) ($optionsRaw[0] ?? '')),
                    'B' => trim((string) ($optionsRaw[1] ?? '')),
                    'C' => trim((string) ($optionsRaw[2] ?? '')),
                    'D' => trim((string) ($optionsRaw[3] ?? '')),
                ];
            } else {
                $options = [
                    'A' => trim((string) ($optionsRaw['A'] ?? $optionsRaw['a'] ?? '')),
                    'B' => trim((string) ($optionsRaw['B'] ?? $optionsRaw['b'] ?? '')),
                    'C' => trim((string) ($optionsRaw['C'] ?? $optionsRaw['c'] ?? '')),
                    'D' => trim((string) ($optionsRaw['D'] ?? $optionsRaw['d'] ?? '')),
                ];
            }

            if (in_array('', $options, true)) {
                continue;
            }

            $correctRaw = strtoupper(trim((string) ($candidate['correct'] ?? $candidate['answer'] ?? $candidate['correct_option'] ?? '')));

            // Handle pipe-separated correct answers (e.g., "A|B" -> take first valid one)
            $correctParts = explode('|', $correctRaw);
            $correctLetter = null;
            foreach ($correctParts as $part) {
                $part = trim($part);
                if (in_array($part, ['A', 'B', 'C', 'D'], true)) {
                    $correctLetter = $part;
                    break;
                }
            }

            if ($correctLetter === null) {
                continue;
            }

            $normalized[] = [
                'question' => $questionText,
                'options' => $options,
                'correct' => $correctLetter,
            ];
        }

        return $normalized;
    }

    private function extractLikelyJsonBlock(string $raw): ?string
    {
        $startArray = strpos($raw, '[');
        $startObject = strpos($raw, '{');

        if ($startArray === false && $startObject === false) {
            return null;
        }

        if ($startArray === false) {
            $start = $startObject;
            $opening = '{';
            $closing = '}';
        } elseif ($startObject === false || $startArray < $startObject) {
            $start = $startArray;
            $opening = '[';
            $closing = ']';
        } else {
            $start = $startObject;
            $opening = '{';
            $closing = '}';
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($raw);

        for ($i = $start; $i < $length; $i++) {
            $char = $raw[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;

                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;

                    continue;
                }

                if ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;

                continue;
            }

            if ($char === $opening) {
                $depth++;
            } elseif ($char === $closing) {
                $depth--;
                if ($depth === 0) {
                    return substr($raw, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private function parseAiQuizQuestions(string $raw): array
    {
        $cleaned = trim($raw);

        $attempts = [$cleaned];

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $cleaned, $blockMatch) === 1) {
            $attempts[] = trim((string) ($blockMatch[1] ?? ''));
        }

        $jsonBlock = $this->extractLikelyJsonBlock($cleaned);
        if ($jsonBlock !== null) {
            $attempts[] = $jsonBlock;
        }

        foreach ($attempts as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $decoded = json_decode($candidate, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            $normalized = $this->normalizeQuizQuestionsPayload($decoded);
            if (! empty($normalized)) {
                return $normalized;
            }
        }

        return [];
    }

    /**
     * Show manage classes / search page.
     */
    public function index()
    {
        $user = Auth::user();

        $classes = match ($user->role) {
            'admin' => ClassModel::query()
                ->withCount('students')
                ->orderBy('classes.created_at', 'desc')
                ->get(['id', 'name', 'code', 'school_year', 'description']),

            'teacher' => ClassModel::query()
                ->where('created_by', $user->id)
                ->withCount('students')
                ->orderBy('classes.created_at', 'desc')
                ->get(['id', 'name', 'code', 'school_year', 'description']),

            default => $user->classes()
                ->select('classes.id', 'classes.name', 'classes.code', 'classes.school_year', 'classes.description')
                ->withCount('students')
                ->orderBy('class_user.created_at', 'desc') // explicit pivot ordering
                ->get(),
        };

        return view('pages.teacher.manageclass', compact('classes'));
    }

    // Keep the rest of your controller methods unchanged.

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20|unique:classes,code',
            'school_year' => 'nullable|integer|digits:4|min:2000|max:2100',
            'year_level' => 'nullable|integer|min:1|max:4',
            'description' => 'nullable|string|max:500',
        ]);

        ClassModel::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'school_year' => $validated['school_year'] ?? null,
            'year_level' => $validated['year_level'] ?? null,
            'description' => $validated['description'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('manageclass')
            ->with('success', 'Class created successfully!');
    }

    public function destroy(ClassModel $class)
    {
        // Verify the teacher owns this class
        if ($class->created_by !== Auth::id() && ! Auth::user()->isAdmin()) {
            return redirect()->route('manageclass')
                ->with('error', 'You do not have permission to delete this class.');
        }

        $studentCount = $class->users()->where('role', 'student')->count();
        $className = $class->name;

        // Delete related records first (cascade)
        $class->modules()->delete();
        $class->users()->detach();
        $class->delete();

        $message = $studentCount > 0
            ? "Class '{$className}' and all its data (including {$studentCount} enrolled students) have been deleted."
            : "Class '{$className}' has been deleted.";

        return redirect()->route('manageclass')
            ->with('success', $message);
    }

    public function searchStudents(Request $request)
    {
        $term = trim($request->input('q', $request->input('term', '')));

        // Start searching from 1 character for instant feel
        if (strlen($term) < 1) {
            return response()->json(['results' => []]);
        }

        $users = User::query()
            ->where('role', 'student')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('idnumber', 'like', "%{$term}%");
            })
            ->select('id', 'name', 'idnumber')
            ->limit(15)
            ->get()
            ->map(function ($user) {
                // Display only name + ID (no email for teachers)
                $display = $user->name
                    ? "{$user->name} (ID: {$user->idnumber})"
                    : "ID: {$user->idnumber}";

                return [
                    'id' => $user->id,
                    'text' => $display,
                ];
            });

        return response()->json(['results' => $users]);

    }

    public function addStudents(Request $request, ClassModel $class)
    {
        $actor = Auth::user();

        if ($class->created_by !== $actor->id && $actor->role !== 'admin') {
            abort(403, 'You are not authorized to add students to this class.');
        }

        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'student')),
            ],
        ]);

        $class->users()->syncWithoutDetaching($validated['student_ids']);

        return redirect()->back()
            ->with('success', count($validated['student_ids']).' student(s) added to '.$class->name);
    }

    /**
     * Render the user search page (HTML).
     */
    public function searchPage()
    {
        $user = Auth::user();

        $classes = match ($user->role) {
            'admin' => ClassModel::query()->latest()->get(['id', 'name']),
            'teacher' => ClassModel::query()->where('created_by', $user->id)->latest()->get(['id', 'name']),
            default => $user->classes()->select('classes.id', 'classes.name')->latest('classes.created_at')->get(),
        };

        return view('pages.users.search', compact('classes')); // ← correct
    }

    /**
     * Generic user search used by the chat UI.
     * Visibility is role-based:
     * - Admin can search all users.
     * - Others can only search users that are connected through at least one shared class.
     *
     * Query params:
     * - q|term: free text (name or idnumber)
     * - role (optional): filter by exact role
     * - class_id (optional): narrow results to a specific class
     */
    public function searchUsers(Request $request)
    {
        $user = Auth::user();

        $term = trim($request->input('q', $request->input('term', '')));
        $role = $request->input('role');        // optional: filter by exact role
        $classId = $request->input('class_id'); // optional: still allow narrowing to a specific class

        if ($term !== '' && strlen($term) < 1) {
            return response()->json(['results' => []]);
        }

        $query = User::query()
            ->select(['id', 'idnumber', 'name', 'role', 'email', 'program']);

        // Role filter (if provided)
        if ($role) {
            $query->where('role', $role);
        }

        // Search term filter
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('idnumber', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        // ========================
        // CLASS FILTER (optional)
        // ========================
        if ($classId) {
            // Verify class exists
            $classExists = ClassModel::query()->where('id', $classId)->exists();
            if (! $classExists) {
                return response()->json(['results' => []]);
            }

            // Restrict results to users who are either:
            // - Enrolled in the class, OR
            // - Created the class
            $enrolledIds = DB::table('class_user')
                ->where('class_id', $classId)
                ->pluck('user_id');

            $creatorIds = ClassModel::query()
                ->where('id', $classId)
                ->pluck('created_by');

            $allowedIds = $enrolledIds->merge($creatorIds)->unique()->values();

            $query->whereIn('id', $allowedIds);

            $scopeClassIds = collect([$classId]);
        } else {
            $scopeClassIds = null;   // No class restriction → show all users
        }

        // ========================
        // Execute query
        // ========================
        $users = $query
            ->limit(15)
            ->get()
            ->map(function (User $u) use ($scopeClassIds) {
                $displayName = $u->name ?: $u->idnumber;

                // Determine which classes to show for this user in the result
                $classQuery = ClassModel::query()
                    ->where(function ($q) use ($u) {
                        $q->where('created_by', $u->id)
                            ->orWhereHas('users', fn ($uq) => $uq->where('users.id', $u->id));
                    });

                if ($scopeClassIds) {
                    $classQuery->whereIn('id', $scopeClassIds->all());
                }

                $classNames = $classQuery
                    ->orderBy('name')
                    ->pluck('name')
                    ->filter()
                    ->values();

                $classCount = $classNames->count();
                $classNamesShort = $classNames->take(5)->implode(', ');
                if ($classCount > 5) {
                    $classNamesShort .= ', ...';
                }

                return [
                    'id' => $u->id,
                    'text' => "{$displayName} ({$u->role}) - {$u->idnumber}".($u->program ? " [{$u->program}]" : ''),
                    'role' => $u->role,
                    'program' => $u->program,
                    'name' => $u->name,
                    'idnumber' => $u->idnumber,
                    'class_count' => $classCount,
                    'class_names' => $classNamesShort,
                ];
            });

        return response()->json(['results' => $users->values()]);
    }

    public function removeStudent(ClassModel $class, User $student)
    {
        abort_unless($this->canManageClassStudents($class), 403, 'You are not authorized to remove students from this class.');

        abort_unless($student->role === 'student', 422, 'Only student accounts can be removed from classes.');

        $class->users()->detach($student->id);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $student->idnumber.' removed from '.$class->name,
            ]);
        }

        return redirect()->back()
            ->with('success', $student->idnumber.' removed from '.$class->name);
    }

    public function generateInvite(ClassModel $class)
    {
        $url = URL::temporarySignedRoute(
            'class.join',
            now()->addDays(7),
            ['class' => $class->id]
        );

        return response()->json([
            'success' => true,
            'url' => $url,
            'message' => 'Invite link generated (valid for 7 days)',
        ]);
    }

    public function generate24HourJoinLink(ClassModel $class): JsonResponse
    {
        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $url = URL::temporarySignedRoute(
            'class.join.permanent',
            now()->addHours(24),
            ['class' => $class->id]
        );

        return response()->json([
            'success' => true,
            'url' => $url,
            'expires' => now()->addHours(24)->toIso8601String(),
        ]);
    }

    public function getClassStudents(ClassModel $class)
    {
        abort_unless($this->canManageClassStudents($class), 403, 'You are not authorized to view students in this class.');

        $students = $class->students()
            ->select('users.id', 'users.idnumber', 'users.name', 'users.email', 'users.program')
            ->orderBy('users.name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->name ? "{$user->name} (ID: {$user->idnumber})" : "ID: {$user->idnumber}",
                    'program' => $user->program ?? null,
                ];
            });

        return response()->json([
            'students' => $students,
            'count' => $students->count(),
        ]);
    }

    public function storeModule(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required_if:type,document,assignment|string|max:150',
            'description' => 'nullable|string',
            'file' => 'required_if:type,document|file|max:102400|mimes:pdf,ppt,pptx,docx,mov',
            'type' => 'required|in:document,quiz,assignment',
            'visibility' => 'nullable|in:all,selected,except',
            'visible_user_ids' => 'nullable|array',
            'visible_user_ids.*' => 'integer|exists:users,id',
        ]);

        $class = ClassModel::findOrFail($validated['class_id']);

        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403, 'You are not authorized to upload modules to this class.');
        }

        $visibility = $validated['visibility'] ?? 'all';
        $visibleUserIds = $validated['visible_user_ids'] ?? [];

        if (in_array($visibility, ['selected', 'except']) && ! empty($visibleUserIds)) {
            $enrolledIds = $class->users()->pluck('users.id')->toArray();
            $visibleUserIds = array_values(array_intersect($visibleUserIds, $enrolledIds));
        }

        $filePath = null;

        // Handle file upload for document type
        if ($validated['type'] === 'document' && $request->hasFile('file')) {
            $file = $request->file('file');
            $extension = strtolower($file->extension());

            // Create unique filename
            $uniqueName = time().'_'.Str::random(8).'.'.$extension;
            $subPath = "modules/{$class->id}";
            $filePath = $file->storeAs($subPath, $uniqueName, 'public');
            $fullPath = storage_path('app/public/'.$filePath);

            // Ensure directory exists
            $dir = storage_path('app/public/'.$subPath);
            if (! file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            \Log::info('File stored', [
                'path' => $fullPath,
                'size' => filesize($fullPath),
                'mime' => mime_content_type($fullPath),
                'extension' => $extension,
            ]);
        }

        // Create the module (quiz has no file)
        $fileType = null;
        if ($filePath) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $fileType = match ($ext) {
                'pdf' => 'pdf',
                'ppt' => 'ppt',
                'pptx' => 'pptx',
                'docx' => 'docx',
                'mov' => 'mov',
                default => null,
            };
        }

        $module = Module::create([
            'class_id' => $validated['class_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'is_quiz' => $validated['type'] === 'quiz',
            'is_assignment' => $validated['type'] === 'assignment',
            'visibility' => $visibility,
        ]);

        // Visibility is set at creation time only — editing is a future feature.
        if (in_array($visibility, ['selected', 'except']) && ! empty($visibleUserIds)) {
            $module->visibleTo()->sync($visibleUserIds);
        }

        // If it's a quiz, redirect to quiz creation page
        if ($validated['type'] === 'quiz') {
            return redirect()->route('quiz.create', $module)
                ->with('success', 'Quiz module created. Now add questions.');
        }

        return response()->json([
            'success' => 'Module added successfully!',
            'module' => [
                'id' => $module->id,
                'title' => $module->title,
                'file' => $filePath,
            ],
        ]);
    }

    // Student view: modules for a class with progress
    // List of classes the current student is enrolled in
    /**
     * Show the student's list of enrolled classes
     */
    /**
     * Show the current user's list of enrolled classes
     */
    /**
     * Show the current user's list of enrolled classes (role-based access)
     */
    /**
     * Show the current user's list of enrolled classes (role-based view)
     */
    public function myClasses()
    {
        $user = Auth::user();

        // Define allowed roles for this student-like dashboard
        $allowedRoles = ['student', 'psych', 'accountancy', 'educ'];

        // Block teachers and admins (they use /manageclass)
        if (! in_array($user->role, $allowedRoles)) {
            return redirect()->route('manageclass')
                ->with('info', 'Use the management dashboard for your role.');
        }

        // Get enrolled classes
        $classes = $user->classes()
            ->withCount('users as total_students')
            ->withCount('modules as total_modules')
            ->with('creator:id,idnumber,name')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalModules = (int) $classes->sum('total_modules');

        // Student accounts resolve by program track; legacy role-based student tracks still work.
        $track = $user->role === 'student' ? ($user->program ?? 'accountancy') : $user->role;
        $view = "pages.{$track}.my-classes";

        // Safe fallback to an existing student track view.
        if (! view()->exists($view)) {
            $view = 'pages.accountancy.my-classes';
        }

        return view($view, [
            'enrolledClasses' => $classes,
            'totalModules' => $totalModules,
        ]);
    }

    /**
     * Show modules + progress for a specific class (student view)
     */
    public function studentModules(ClassModel $class)
    {
        $user = Auth::user();

        // Security: must be enrolled
        if (! $class->users()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not enrolled in this class.');
        }

        $modules = $class->modules()
            ->where('is_formal_assessment', false)
            ->where(function ($query) use ($user) {
                $query->where('visibility', 'all')
                    ->orWhere(function ($sub) use ($user) {
                        $sub->where('visibility', 'selected')
                            ->whereHas('visibleTo', fn ($q) => $q->where('users.id', $user->id));
                    })
                    ->orWhere(function ($sub) use ($user) {
                        $sub->where('visibility', 'except')
                            ->whereDoesntHave('visibleTo', fn ($q) => $q->where('users.id', $user->id));
                    });
            })
            ->orderBy('order')
            ->get();

        $progress = [];
        $completed = [];
        $totalProgress = 0;
        $moduleCount = $modules->count();

        foreach ($modules as $module) {
            $prog = ModuleProgress::firstOrCreate(
                ['module_id' => $module->id, 'user_id' => $user->id],
                ['progress' => 0, 'completed' => false]
            );

            $progress[$module->id] = $prog->progress;
            $completed[$module->id] = (bool) $prog->completed;
            $totalProgress += $prog->progress;
        }

        // Sequential lock removed: modules are no longer gated by previous completion.
        $locked = [];

        // Load completed pre-assessment quiz attempts so the view can show locked results.
        $quizModuleIds = $modules->where('is_quiz', true)->pluck('id');
        $quizAttempts = [];

        if ($quizModuleIds->isNotEmpty()) {
            QuizAttempt::where('user_id', $user->id)
                ->whereIn('module_id', $quizModuleIds)
                ->where('total', '>', 0)
                ->get()
                ->each(function (QuizAttempt $attempt) use (&$quizAttempts) {
                    $quizAttempts[$attempt->module_id] = [
                        'score' => $attempt->score,
                        'total' => $attempt->total,
                        'percentage' => $attempt->percentage,
                        'passed' => (bool) $attempt->passed,
                        'attempt_count' => $attempt->attempt_count,
                        'ai_strong' => $attempt->ai_strong,
                        'ai_weak' => $attempt->ai_weak,
                        'ai_recommendation' => $attempt->ai_recommendation,
                    ];
                });
        }

        $overallCompletion = $moduleCount > 0 ? round($totalProgress / $moduleCount, 2) : 0;

        // Attempt-limit info bawat formal-assessment na module, para sa student-facing
        // "ilang attempts pa" na display bago pa man sagutan ng estudyante.
        $attemptLimits = [];
        $formalModules = $modules->where('is_formal_assessment', true);

        if ($formalModules->isNotEmpty()) {
            $formalModuleIds = $formalModules->pluck('id');

            $grants = AssessmentAttemptGrant::where('user_id', $user->id)
                ->whereIn('module_id', $formalModuleIds)
                ->get()
                ->keyBy('module_id');

            foreach ($formalModules as $formalModule) {
                $baseMax = $formalModule->max_attempts ?? 1;
                $extra = $grants->get($formalModule->id)?->extra_attempts ?? 0;
                $used = $quizAttempts[$formalModule->id]['attempt_count'] ?? 0;

                $attemptLimits[$formalModule->id] = [
                    'base_max_attempts' => $baseMax,
                    'extra_attempts_granted' => $extra,
                    'attempts_allowed' => $baseMax + $extra,
                    'attempts_used' => $used,
                ];
            }
        }

        return view('pages.student.modules', compact(
            'class',
            'modules',
            'progress',
            'completed',
            'locked',
            'quizAttempts',
            'overallCompletion',
            'attemptLimits'
        ));
    }

    // Teacher: post announcement
    public function postAnnouncement(Request $request, ClassModel $class)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $class->announcements()->create([
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        return redirect()->back()->with('success', 'Announcement posted.');
    }

    // Teacher: view announcements (optional)
    public function viewAnnouncements(ClassModel $class)
    {
        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $announcements = $class->announcements()->with('user')->latest()->get();

        return view('pages.teacher.announcements', compact('class', 'announcements'));
    }

    public function viewModuleFile(Module $module)
    {
        $user = Auth::user();
        $class = $module->class;

        if (! $class->users()->where('user_id', $user->id)->exists() &&
            $class->created_by !== $user->id &&
            $user->role !== 'admin') {
            abort(403);
        }

        // Check module visibility
        $visibility = $module->visibility ?? 'all';
        if ($visibility === 'selected') {
            // User must be in the visibleTo list
            $allowed = $module->visibleTo()->where('user_id', $user->id)->exists();
            if (! $allowed) {
                abort(403, 'You do not have access to this module.');
            }
        } elseif ($visibility === 'except') {
            // User must NOT be in the visibleTo list
            $blocked = $module->visibleTo()->where('user_id', $user->id)->exists();
            if ($blocked) {
                abort(403, 'You do not have access to this module.');
            }
        }

        // Sequential lock removed: students can open modules without previous-module completion checks.

        if (! $module->file_path) {
            abort(404);
        }

        $path = storage_path('app/public/'.$module->file_path);

        if (! file_exists($path)) {
            abort(404);
        }

        // Determine content type based on file_type
        $contentType = match ($module->file_type) {
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
            'Accept-Ranges' => 'bytes', // for video seeking
        ]);
    }

    public function pdfjsViewer(Module $module)
    {
        $user = Auth::user();
        $class = $module->class;

        // Access check
        if (! $class->users()->where('user_id', $user->id)->exists() &&
            $class->created_by !== $user->id &&
            $user->role !== 'admin') {
            abort(403);
        }

        // Check module visibility
        $visibility = $module->visibility ?? 'all';
        if ($visibility === 'selected') {
            // User must be in the visibleTo list
            $allowed = $module->visibleTo()->where('user_id', $user->id)->exists();
            if (! $allowed) {
                abort(403, 'You do not have access to this module.');
            }
        } elseif ($visibility === 'except') {
            // User must NOT be in the visibleTo list
            $blocked = $module->visibleTo()->where('user_id', $user->id)->exists();
            if ($blocked) {
                abort(403, 'You do not have access to this module.');
            }
        }

        if (! $module->file_path || ! in_array($module->file_type, ['pdf', 'ppt', 'pptx', 'docx'], true)) {
            abort(404);
        }

        if (in_array($module->file_type, ['pdf', 'ppt', 'pptx'], true)) {
            $pdfUrl = route('module.view.pdf', $module);

            return view('pages.student.pdfjs-viewer', compact('pdfUrl', 'module'));
        }

        if ($module->file_type === 'docx') {
            $docxUrl = route('module.view', $module);

            return view('pages.student.docx-viewer', compact('module', 'docxUrl'));
        }

        abort(404);
    }

    public function viewPdf(Module $module)
    {
        $user = Auth::user();
        $class = $module->class;

        if (! $class->users()->where('user_id', $user->id)->exists() &&
            $class->created_by !== $user->id &&
            $user->role !== 'admin') {
            abort(403);
        }

        $visibility = $module->visibility ?? 'all';
        if ($visibility === 'selected') {
            $allowed = $module->visibleTo()->where('user_id', $user->id)->exists();
            if (! $allowed) {
                abort(403, 'You do not have access to this module.');
            }
        } elseif ($visibility === 'except') {
            $blocked = $module->visibleTo()->where('user_id', $user->id)->exists();
            if ($blocked) {
                abort(403, 'You do not have access to this module.');
            }
        }

        if (! $module->file_path || ! in_array($module->file_type, ['pdf', 'ppt', 'pptx'], true)) {
            abort(404);
        }

        $pdfPath = $module->file_type === 'pdf'
            ? storage_path('app/public/'.$module->file_path)
            : $this->ensurePresentationPreviewPdf($module);

        if (! file_exists($pdfPath)) {
            abort(404);
        }

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ]);
    }

    public function publicModuleFile(Module $module)
    {
        if (! request()->hasValidSignature()) {
            abort(403);
        }

        if (! $module->file_path || ! in_array($module->file_type, ['ppt', 'pptx', 'docx'], true)) {
            abort(404);
        }

        $path = storage_path('app/public/'.$module->file_path);

        if (! file_exists($path)) {
            abort(404);
        }

        $contentType = match ($module->file_type) {
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };

        return response()->file($path, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
        ]);
    }

    private function ensurePresentationPreviewPdf(Module $module): string
    {
        $sourcePath = storage_path('app/public/'.$module->file_path);

        if (! file_exists($sourcePath)) {
            abort(404);
        }

        $previewDirectory = storage_path('app/public/module-previews');
        if (! File::exists($previewDirectory)) {
            File::makeDirectory($previewDirectory, 0755, true);
        }

        $sourceHash = md5($module->file_path.'|'.filemtime($sourcePath));
        $previewPath = $previewDirectory.DIRECTORY_SEPARATOR.'module-'.$module->id.'-'.$sourceHash.'.pdf';

        if (file_exists($previewPath)) {
            return $previewPath;
        }

        foreach (glob($previewDirectory.DIRECTORY_SEPARATOR.'module-'.$module->id.'-*.pdf') ?: [] as $oldPreviewPath) {
            @unlink($oldPreviewPath);
        }

        $escapedSourcePath = str_replace("'", "''", $sourcePath);
        $escapedPreviewPath = str_replace("'", "''", $previewPath);

        $script = <<<'POWERSHELL'
$sourcePath = '__SOURCE_PATH__'
$targetPath = '__TARGET_PATH__'
$powerPoint = $null
$presentation = $null

try {
    $previewDirectory = Split-Path -Parent $targetPath
    if (-not (Test-Path -LiteralPath $previewDirectory)) {
        New-Item -ItemType Directory -Path $previewDirectory -Force | Out-Null
    }

    $powerPoint = New-Object -ComObject PowerPoint.Application
    $powerPoint.Visible = -1
    $presentation = $powerPoint.Presentations.Open($sourcePath, $false, $false, $false)
    $presentation.SaveAs($targetPath, 32)
}
catch {
    Write-Error $_.Exception.Message
    exit 1
}
finally {
    if ($presentation -ne $null) {
        $presentation.Close()
    }

    if ($powerPoint -ne $null) {
        $powerPoint.Quit()
    }
}
POWERSHELL;

        $script = str_replace(['__SOURCE_PATH__', '__TARGET_PATH__'], [$escapedSourcePath, $escapedPreviewPath], $script);
        $encodedCommand = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));

        $command = 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -EncodedCommand '.$encodedCommand;

        exec($command.' 2>&1', $output, $exitCode);

        if ($exitCode !== 0 || ! file_exists($previewPath)) {
            Log::error('PowerPoint preview conversion failed.', [
                'module_id' => $module->id,
                'source_path' => $sourcePath,
                'preview_path' => $previewPath,
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            abort(500, 'The presentation preview could not be generated.');
        }

        return $previewPath;
    }

    /**
     * Return JSON list of modules for a class (for modal)
     */
    public function listModulesJson(ClassModel $class)
    {
        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $modules = $class->modules()
            ->orderBy('title')
            ->get()
            ->map(function ($module) {
                return [
                    'id' => $module->id,
                    'title' => $module->title,
                    'description' => $module->description ?? 'No description',
                    'type' => $module->is_quiz ? 'Quiz' : ($module->is_assignment ? 'Assignment' : 'Document'),
                    'is_formal_assessment' => (bool) $module->is_formal_assessment,
                    'edit_url' => $module->is_quiz ? route('quiz.create', $module) : null,
                    'file_path' => $module->file_path ? asset('storage/'.$module->file_path) : null,
                    'file_type' => $module->file_type,
                    'created_at' => $module->created_at->diffForHumans(),
                    'due_date' => $module->due_date?->format('M d, Y g:i A'),
                ];
            });

        return response()->json(['modules' => $modules]);
    }

    public function deleteModule(Module $module)
    {
        $class = $module->class;

        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($module->file_path) {
            $fullPath = storage_path('app/public/'.$module->file_path);
            @unlink($fullPath);
        }

        $module->delete();

        return response()->json(['success' => 'Module deleted']);
    }

    public function updateProgress(Request $request, Module $module)
    {
        $user = Auth::user();

        if (! $module->class->users()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
            'completed' => 'sometimes|boolean',
        ]);

        ModuleProgress::updateOrCreate(
            ['module_id' => $module->id, 'user_id' => $user->id],
            [
                'progress' => $validated['progress'],
                'completed' => $validated['completed'] ?? false,
                'completed_at' => $validated['completed'] ? now() : null,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function createQuiz(Module $module)
    {
        $class = $module->class;

        // Ligtas na pagsusuri: Kung may class, i-check ang created_by nito.
        // Kung wala (program-based/mock board), ang titingnan naman ay ang created_by ng mismong module.
        if ($class) {
            if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
                abort(403);
            }
        } else {
            if ($module->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
                abort(403);
            }
        }

        // Eksplisitong destinasyon para sa "Back" button — hindi na umaasa sa
        // url()->previous(), na nagiging self-referencing pagkatapos ng save/redirect loop.
        $isMockBoardModule = \DB::table('mock_board_phases')->where('module_id', $module->id)->exists() || ($module->is_mock_board ?? false);
        $backUrl = $isMockBoardModule
            ? route('mock-boards.index')
            : route('manageclass');

        if (! $module->is_quiz) {
            abort(404, 'This module is not a quiz.');
        }

        $existingQuestions = $module->quizQuestions()
            ->get()
            ->map(function (QuizQuestion $question) {
                return [
                    'text' => $question->question_text,
                    'options' => $question->options,
                    'correct' => $question->correct_option,
                    'points' => $question->points,
                    'difficulty' => $question->difficulty,
                ];
            });

        $settingsResolver = app(AiSettingsResolver::class);

        // Protektahan din ito: Kung null ang $class, iwasang pasahen ng null para hindi mag-error ang resolver
        $classQuizDefaults = $class ? $settingsResolver->getClassQuizDefaults($class) : [];

        // 1. Check if this module belongs to any mock board exam phases
        $isMockBoard = \DB::table('mock_board_phases')->where('module_id', $module->id)->exists() || ($module->is_mock_board ?? false);

        // 2. Resolve standard AI setting for regular quizzes (kung may class, tsaka lang isama ang class)
        $isAiQuizGenerationEnabled = $class ? $settingsResolver->isFeatureEnabled('quiz_generation', $class) : false;

        // 3. OVERRIDE: If this is a mock board, strip away all AI capability flags completely
        if ($isMockBoard) {
            $isAiQuizGenerationEnabled = false;
        }

        return view('pages.teacher.quiz-create', compact(
            'module',
            'class',
            'existingQuestions',
            'classQuizDefaults',
            'isAiQuizGenerationEnabled',
            'isMockBoard',
            'backUrl'
        ));
    }

    /**
     * Generate quiz questions using AI, with support for multiple uploaded documents
     */
    /**
     * Generate quiz questions using AI, with precise target counts per uploaded document
     */
public function generateQuizAi(Request $request, Module $module)
{
    $class = $module->class;

    if ($class) {
        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }
    } else {
        if (Auth::user()->role !== 'admin' && (int) $module->created_by !== (int) Auth::id()) {
            abort(403, 'You do not have permission to generate questions for this Mock Board.');
        }
    }

    if (! $module->is_quiz) {
        abort(404, 'This module is not a quiz.');
    }

    $validator = Validator::make($request->all(), [
        'context_files' => 'required|array|min:1',
        'context_files.*' => 'file|mimes:pdf,doc,docx,txt|max:20480',
        'file_question_counts' => 'required|array|min:1',
        'file_question_counts.*' => 'integer|min:0|max:20',
        'file_difficulties' => 'nullable|array',
        'file_difficulties.*' => 'nullable|in:Average,Normal,Hard',
        'extra_instructions' => 'nullable|string|max:500',
        'choice_count' => 'nullable|integer|min:2|max:10',
    ]);

    $validator->after(function ($v) use ($request) {
        $counts = $request->input('file_question_counts', []);
        if (is_array($counts)) {
            $sum = array_sum(array_map('intval', $counts));
            if ($sum > 50) {
                $v->errors()->add(
                    'file_question_counts',
                    "The total requested questions across all files ({$sum}) exceeds the maximum allowed limit of 50."
                );
            }
        }
    });

    $validated = $validator->validate();

    $counts = $validated['file_question_counts'] ?? [];
    $activeFileCount = count(array_filter($counts, fn ($c) => (int) $c > 0));
    // More AI calls (per type) — allow more time
    set_time_limit(max(90, $activeFileCount * 90));

    $allGeneratedQuestions = [];
    $ai = app(CloudflareAI::class);

    $choiceCount = (int) ($validated['choice_count'] ?? 4);
    $choiceLetters = array_slice(range('A', 'J'), 0, $choiceCount);
    $allowedDifficulties = ['Average', 'Normal', 'Hard'];

    $typePatterns = [
        // what, why, how  (base = 5)
        'Average' => [3, 1, 1],
        'Normal' => [1, 2, 2],
        'Hard' => [0, 2, 3],
    ];

    foreach ($request->file('context_files') as $index => $file) {
        $requestedCount = (int) ($validated['file_question_counts'][$index] ?? 0);

        if ($requestedCount === 0 || ! $file->isValid()) {
            continue;
        }

        $storedPath = $file->storeAs(
            'temp_context',
            uniqid('ctx_').'.'.$file->getClientOriginalExtension(),
            'local'
        );
        $fullPath = Storage::disk('local')->path($storedPath);

        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($fullPath);
            $text = trim(preg_replace('/\s+/', ' ', $pdf->getText()));
            $text = $this->truncateAtSentenceBoundary($text, 8000);
        } catch (\Exception $e) {
            Log::warning('PDF parse failed for file '.$file->getClientOriginalName().' - '.$e->getMessage());
            @unlink($fullPath);
            continue;
        }
        @unlink($fullPath);

        if ($text === '') {
            Log::warning('Empty text after parse: '.$file->getClientOriginalName());
            continue;
        }

        $extraInstructions = trim($validated['extra_instructions'] ?? '');
        $extraInstructionsBlock = $extraInstructions !== ''
            ? "Additional Teacher Instructions: {$extraInstructions}\n\n"
            : '';

        $optionsExample = '{'.implode(',', array_map(fn ($l) => "\"{$l}\":\"...\"", $choiceLetters)).'}';
        $letterList = implode('|', $choiceLetters);

        $fileDifficulties = $validated['file_difficulties'] ?? [];
        $targetDifficulty = $fileDifficulties[$index] ?? 'Normal';

        $pattern = $typePatterns[$targetDifficulty] ?? $typePatterns['Normal'];
        $base = array_sum($pattern);

        $targetWhat = (int) round($requestedCount * ($pattern[0] / $base));
        $targetWhy = (int) round($requestedCount * ($pattern[1] / $base));
        $targetHow = (int) round($requestedCount * ($pattern[2] / $base));

        $sum = $targetWhat + $targetWhy + $targetHow;
        if ($sum < $requestedCount) {
            if ($targetDifficulty === 'Average') {
                $targetWhat += ($requestedCount - $sum);
            } else {
                $targetHow += ($requestedCount - $sum);
            }
        } elseif ($sum > $requestedCount) {
            $overflow = $sum - $requestedCount;
            if ($targetWhat >= $overflow) {
                $targetWhat -= $overflow;
            } else {
                $overflow -= $targetWhat;
                $targetWhat = 0;
                if ($targetWhy >= $overflow) {
                    $targetWhy -= $overflow;
                } else {
                    $overflow -= $targetWhy;
                    $targetWhy = 0;
                    $targetHow = max(0, $targetHow - $overflow);
                }
            }
        }

        Log::info('AI quiz per-type plan', [
            'file' => $file->getClientOriginalName(),
            'targetDifficulty' => $targetDifficulty,
            'targetWhat' => $targetWhat,
            'targetWhy' => $targetWhy,
            'targetHow' => $targetHow,
        ]);

        $typeJobs = [
            'what' => $targetWhat,
            'why' => $targetWhy,
            'how' => $targetHow,
        ];

        $fileQuestions = [];

        foreach ($typeJobs as $questionType => $typeCount) {
            if ($typeCount <= 0) {
                continue;
            }

            $typeInstructions = match ($questionType) {
                'why' => "ALL {$typeCount} questions MUST be WHY questions.\n"
                    ."- Stem MUST start with \"Why...\" or \"What is the rationale...\" or \"What best explains...\".\n"
                    ."- Ask for reasoning behind a rule, principle, choice, or outcome.\n"
                    ."- Set question_type to \"why\" on every object.\n"
                    ."- Do NOT use \"Which of the following\".\n",
                'how' => "ALL {$typeCount} questions MUST be HOW questions.\n"
                    ."- Stem MUST start with \"How should...\" or \"How is ... computed/applied...\" or \"What is the correct procedure to...\".\n"
                    ."- Ask for procedure, process, or application steps.\n"
                    ."- Set question_type to \"how\" on every object.\n"
                    ."- Do NOT use \"Which of the following\".\n",
                default => "ALL {$typeCount} questions MUST be WHAT questions.\n"
                    ."- Identify something FROM a scenario, case, computation, or data — never bare definition.\n"
                    ."- Set question_type to \"what\" on every object.\n"
                    ."- Do NOT start every item with \"Given...\".\n",
            };

            $prompt = "Generate EXACTLY {$typeCount} multiple-choice questions based ONLY on the text below.\n"
                ."Each question must have EXACTLY {$choiceCount} answer choices ({$letterList}).\n"
                ."Batch difficulty target: {$targetDifficulty}.\n"
                ."Source file: {$file->getClientOriginalName()}\n\n"
                ."════════════════════════════════════════\n"
                .$typeInstructions
                ."════════════════════════════════════════\n\n"
                ."Difficulty guide (most items should feel {$targetDifficulty}):\n"
                ."- Average: one concept, simple scenario.\n"
                ."- Normal: two related concepts or richer scenario.\n"
                ."- Hard: multi-step reasoning or nuanced case.\n\n"
                .$extraInstructionsBlock
                ."Content:\n{$text}\n\n"
                ."Rules:\n"
                ."- Return ONLY a valid JSON array with EXACTLY {$typeCount} objects.\n"
                ."- Format: {\"question\":\"...\",\"options\":{$optionsExample},\"correct\":\"{$letterList}\",\"difficulty\":\"Average|Normal|Hard\",\"question_type\":\"{$questionType}\"}\n"
                ."- Every question_type MUST be \"{$questionType}\".\n"
                .'- No markdown, no backticks, no extra text.';

            try {
                $payload = [
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "You generate board-exam MCQs. Output ONLY a JSON array of exactly {$typeCount} objects. Every object must have question_type \"{$questionType}\". No markdown.",
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => min(320 * $typeCount, 4096),
                    'temperature' => 0.2,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'question' => ['type' => 'string'],
                                    'options' => [
                                        'type' => 'object',
                                        'properties' => array_fill_keys($choiceLetters, ['type' => 'string']),
                                        'required' => $choiceLetters,
                                    ],
                                    'correct' => [
                                        'type' => 'string',
                                        'enum' => $choiceLetters,
                                    ],
                                    'difficulty' => [
                                        'type' => 'string',
                                        'enum' => $allowedDifficulties,
                                    ],
                                    'question_type' => [
                                        'type' => 'string',
                                        'enum' => ['what', 'why', 'how'],
                                    ],
                                ],
                                'required' => ['question', 'options', 'correct', 'difficulty', 'question_type'],
                            ],
                        ],
                    ],
                ];

                $result = $ai->run('@cf/meta/llama-3.2-3b-instruct', $payload);
                $aiResponse = $result['response'] ?? '';

                if (is_array($aiResponse)) {
                    $batch = $aiResponse;
                } else {
                    $raw = trim((string) $aiResponse);
                    $raw = preg_replace('/^[\s\r\n]*```json\s*/i', '', $raw);
                    $raw = preg_replace('/\s*```[\s\r\n]*$/i', '', $raw);
                    $raw = preg_replace('/^[\s\r\n]*```[\s\r\n]*/i', '', $raw);
                    $raw = preg_replace('/,\s*([}\]])/u', '$1', $raw);
                    $raw = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw);
                    $raw = trim($raw);
                    preg_match('/\[.*\]/s', $raw, $matches);
                    $jsonBlock = $matches[0] ?? $raw;
                    if (! str_starts_with($jsonBlock, '[')) {
                        $jsonBlock = '['.$jsonBlock;
                    }
                    if (! str_ends_with($jsonBlock, ']')) {
                        $jsonBlock .= ']';
                    }
                    $batch = json_decode($jsonBlock, true);
                    if (! is_array($batch)) {
                        $batch = [];
                    }
                }

                $batch = array_values(array_filter($batch, function ($q) use ($choiceLetters) {
                    return isset($q['question'], $q['options'], $q['correct'])
                        && is_string($q['question'])
                        && is_string($q['correct'])
                        && is_array($q['options'])
                        && count($q['options']) === count($choiceLetters)
                        && in_array(strtoupper($q['correct']), $choiceLetters, true);
                }));

                $batch = array_slice($batch, 0, $typeCount);

                // Force correct question_type label for this job
                foreach ($batch as &$q) {
                    $q['question_type'] = $questionType;
                    if (empty($q['difficulty'])) {
                        $q['difficulty'] = $targetDifficulty;
                    }
                }
                unset($q);

                // If short, one focused retry for this type only
                if (count($batch) < $typeCount) {
                    Log::warning('AI per-type short — retry once', [
                        'file' => $file->getClientOriginalName(),
                        'type' => $questionType,
                        'got' => count($batch),
                        'need' => $typeCount,
                    ]);

                    $need = $typeCount - count($batch);
                    $retryPrompt = $prompt."\n\nRETRY: Return EXACTLY {$need} more \"{$questionType}\" questions only.";

                    try {
                        $retryPayload = $payload;
                        $retryPayload['messages'][1]['content'] = $retryPrompt;
                        $retryPayload['max_tokens'] = min(320 * $need, 4096);

                        $retryResult = $ai->run('@cf/meta/llama-3.2-3b-instruct', $retryPayload);
                        $retryResponse = $retryResult['response'] ?? '';
                        $retryBatch = is_array($retryResponse) ? $retryResponse : null;

                        if (! is_array($retryBatch)) {
                            $rawRetry = trim((string) $retryResponse);
                            preg_match('/\[.*\]/s', $rawRetry, $m);
                            $retryBatch = json_decode($m[0] ?? $rawRetry, true);
                        }

                        if (is_array($retryBatch)) {
                            $retryBatch = array_values(array_filter($retryBatch, function ($q) use ($choiceLetters) {
                                return isset($q['question'], $q['options'], $q['correct'])
                                    && is_string($q['question'])
                                    && is_string($q['correct'])
                                    && is_array($q['options'])
                                    && count($q['options']) === count($choiceLetters)
                                    && in_array(strtoupper($q['correct']), $choiceLetters, true);
                            }));
                            $retryBatch = array_slice($retryBatch, 0, $need);
                            foreach ($retryBatch as &$rq) {
                                $rq['question_type'] = $questionType;
                                if (empty($rq['difficulty'])) {
                                    $rq['difficulty'] = $targetDifficulty;
                                }
                            }
                            unset($rq);
                            $batch = array_merge($batch, $retryBatch);
                        }
                    } catch (\Exception $retryEx) {
                        Log::warning('AI per-type retry failed: '.$retryEx->getMessage());
                    }
                }

                foreach (array_slice($batch, 0, $typeCount) as $q) {
                    $fileQuestions[] = $q;
                }
            } catch (\Exception $typeEx) {
                Log::error("AI per-type generation failed [{$questionType}] {$file->getClientOriginalName()}: ".$typeEx->getMessage());
            }
        }

        // Optional light shuffle so why/how/what are not clumped by type order
        shuffle($fileQuestions);

        foreach ($fileQuestions as $q) {
            $allGeneratedQuestions[] = $q;
        }
    }

    if (empty($allGeneratedQuestions)) {
        return response()->json([
            'success' => false,
            'message' => 'No valid questions could be generated from any of the uploaded documents.',
        ], 422);
    }

    DB::transaction(function () use ($module, $allGeneratedQuestions, $allowedDifficulties) {
        QuizQuestion::query()->where('module_id', $module->id)->delete();

        foreach ($allGeneratedQuestions as $index => $q) {
            $questionDifficulty = ucfirst(strtolower((string) ($q['difficulty'] ?? '')));
            if (! in_array($questionDifficulty, $allowedDifficulties, true)) {
                $questionDifficulty = 'Normal';
            }

            QuizQuestion::create([
                'module_id' => $module->id,
                'question_text' => trim((string) $q['question']),
                'options' => $q['options'],
                'correct_option' => strtoupper((string) $q['correct']),
                'points' => 1,
                'order' => $index + 1,
                'difficulty' => $questionDifficulty,
            ]);
        }
    });

    return response()->json([
        'success' => true,
        'message' => count($allGeneratedQuestions).' customized questions generated and saved into the quiz module.',
        'questions' => $allGeneratedQuestions,
    ]);
}
    /**
     * Helper to truncate text cleanly at sentence boundaries
     */
    private function truncateAtSentenceBoundary(string $text, int $limit = 8000): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $substring = mb_substr($text, 0, $limit);

        // Search for last sentence end (. ! ?)
        if (preg_match('/.*[.!?](?=\s|$)/s', $substring, $matches)) {
            return trim($matches[0]);
        }

        // Fallback: Clip at last space boundary
        $lastSpace = mb_strrpos($substring, ' ');

        return $lastSpace !== false ? mb_substr($substring, 0, $lastSpace) : $substring;
    }

    /**
     * Save manually created quiz questions
     */
    public function storeQuizManual(Request $request, Module $module)
    {
        $class = $module->class;

        if ($class) {
            if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
                abort(403);
            }
        } else {
            if (Auth::user()->role !== 'admin' && (int) $module->created_by !== (int) Auth::id()) {
                abort(403, 'You do not have permission to modify this Mock Board.');
            }
        }

        if (! $module->is_quiz) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string',
            'questions.*.options' => 'required|array|min:2|max:10',
            'questions.*.options.*' => 'required|string',
            'questions.*.correct' => 'required|string',
            'questions.*.points' => 'required|integer|min:1',
            'questions.*.difficulty' => 'nullable|in:Average,Normal,Hard',
            'questions.*.domain' => 'nullable|string|max:150',
            'questions.*.explanation' => 'nullable|string',
            'shuffle_questions' => 'nullable|boolean',
        ]);

        // Tiyakin na ang 'correct' letter ay talagang isa sa mga key na binigay sa 'options'
        $validator->after(function ($v) use ($request) {
            $questions = $request->input('questions', []);

            foreach ($questions as $index => $q) {
                $options = $q['options'] ?? [];
                $correct = $q['correct'] ?? null;

                if ($correct === null || ! array_key_exists($correct, $options)) {
                    $v->errors()->add(
                        "questions.{$index}.correct",
                        'Ang tinukoy na tamang sagot ay wala sa listahan ng options.'
                    );
                }
            }
        });

        $validated = $validator->validate();

        $questions = $validated['questions'];

        if (! empty($validated['shuffle_questions'])) {
            shuffle($questions);
        }

        DB::transaction(function () use ($questions, $module) {
            QuizQuestion::query()->where('module_id', $module->id)->delete();

            foreach (array_values($questions) as $index => $q) {
                QuizQuestion::create([
                    'module_id' => $module->id,
                    'question_text' => $q['text'],
                    'options' => $q['options'], // dynamic na array, hindi na fixed A-D
                    'correct_option' => $q['correct'],
                    'points' => $q['points'],
                    'order' => $index + 1,
                    'difficulty' => $q['difficulty'] ?? 'Normal',
                    'domain' => $q['domain'] ?? null,
                    'explanation' => $q['explanation'] ?? null,
                ]);
            }
        });

        return redirect()->route('quiz.create', $module)
            ->with('success', count($validated['questions']).' quiz questions saved successfully!');
    }

    public function createQuizDraft(Request $request, ClassModel $class)
    {
        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }
        \Log::info('createQuizDraft - Received data:', $request->all());
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            // allow null, but ensure numeric when present
            'time_limit' => 'nullable',
            'due_date' => 'nullable|date',
        ]);

        // Normalize time_limit: accept numeric strings, null, or empty -> store null or int minutes
        $rawTime = $request->input('time_limit', null);

        // If user submitted an empty string, treat as null (no limit)
        if ($rawTime === '' || $rawTime === null) {
            $minutes = null;
        } else {
            // Remove non-digits, then cast to int. This accepts "5", "05", " 5 ", etc.
            // If you expect formats like "00:05", handle that separately below.
            if (is_numeric($rawTime)) {
                $minutes = (int) $rawTime;
            } elseif (preg_match('/^(\d+):(\d{1,2})$/', trim($rawTime), $m)) {
                // Accept "MM:SS" or "H:MM" style and convert to minutes (round up if seconds > 0)
                $mins = (int) $m[1];
                $secs = (int) $m[2];
                $minutes = $mins + ($secs > 0 ? 1 : 0);
            } else {
                // fallback: try to extract digits
                preg_match('/\d+/', $rawTime, $m2);
                $minutes = isset($m2[0]) ? (int) $m2[0] : null;
            }
        }

        $module = Module::create([
            'class_id' => $class->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'time_limit' => $minutes ?? 0,
            'due_date' => $validated['due_date'] ?? null,
            'file_path' => null,
            'file_type' => null,
            'is_quiz' => true,
            'is_assignment' => false,
        ]);

        return redirect()->route('quiz.create', $module);
    }

    public function showModules(ClassModel $class)
    {
        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $modules = $class->modules()->latest()->get();

        return view('pages.teacher.modules-list', compact('class', 'modules'));
    }

    /**
     * Delete a module (teacher/admin only)
     */
    public function destroyModule(Module $module)
    {
        $class = $module->class;

        // Security: only creator or admin
        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        // Optional: delete physical file
        if ($module->file_path) {
            $fullPath = storage_path('app/public/'.$module->file_path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        // Delete module (cascades to quiz_questions if foreign key set)
        $module->delete();

        return redirect()->back()
            ->with('success', 'Module deleted successfully.');
    }

    public function getQuizQuestions(Module $module)
    {
        // load questions and module
        $questions = QuizQuestion::where('module_id', $module->id)
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'questions' => $questions,
            // ensure time_limit is an integer (minutes) or null
            'time_limit' => is_null($module->time_limit) ? null : (int) $module->time_limit,
        ]);
    }

    public function studentPerformance(ClassModel $class)
    {
        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403, 'You do not have permission to view this class performance.');
        }

        $attempts = QuizAttempt::whereHas('module', function ($q) use ($class) {
            $q->where('class_id', $class->id);
        })->with(['user', 'module'])->get();

        $totalStudents = $class->students()->count();
        $averageScore = $attempts->avg('percentage') ?? 0;
        $passRate = $totalStudents > 0 ? round(($attempts->where('passed', true)->count() / max($attempts->count(), 1)) * 100, 1) : 0;

        $weakTopics = [
            ['topic' => 'Reference Locking', 'count' => 14],
            ['topic' => 'AI Engine Function', 'count' => 11],
            ['topic' => 'Database Storage', 'count' => 8],
        ];

        $ranking = $attempts->sortByDesc('percentage')
            ->take(10)
            ->map(fn ($a) => [
                'rank' => $attempts->sortByDesc('percentage')->search($a) + 1,
                'student' => $a->user->name ?? 'Student '.$a->user_id,
                'score' => round($a->percentage, 1).'%',
            ]);

        $aiSummary = 'The class performed moderately (average '.round($averageScore, 1).'%). Most students struggled with Reference Locking and AI Engine functions. Recommendation: Review Module 3 next week.';

        return view('teacher.student-performance', compact(
            'class', 'averageScore', 'passRate', 'weakTopics', 'ranking', 'aiSummary', 'totalStudents'
        ));
    }

    /**
     * Display quiz with timer based on teacher's input
     */
    public function quizTimer(Module $module)
    {
        // Teacher sets time_limit (in minutes) when creating the quiz
        $timeLimitMinutes = $module->time_limit ?? 0;

        return view('quiz.timer', [
            'module' => $module,
            'questions' => $module->questions, // assuming relation exists
            'timeLimitMinutes' => $timeLimitMinutes,
        ]);
    }

    /**
     * Search students enrolled in a class (for visibility picker).
     */
    public function searchClassStudents(Request $request, ClassModel $class): JsonResponse
    {
        if ($class->created_by !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $query = trim($request->input('q', ''));

        $students = $class->users()
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('users.name', 'like', '%'.$query.'%')
                        ->orWhere('users.email', 'like', '%'.$query.'%')
                        ->orWhere('users.idnumber', 'like', '%'.$query.'%');
                });
            })
            ->select('users.id', 'users.name', 'users.email', 'users.idnumber', 'users.program')
            ->limit(20)
            ->get();

        return response()->json($students);
    }
}
