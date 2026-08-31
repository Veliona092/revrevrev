<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\ClassModel;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $classQuery = $this->accessibleClassesQuery($user);
        $classes = $classQuery->orderBy('classes.name')->get(['classes.id', 'classes.name']);
        $classIds = $classes->pluck('id');

        $selectedClassId = $request->integer('class_id') ?: null;
        if ($selectedClassId !== null && ! $classIds->contains($selectedClassId)) {
            abort(403, 'You do not have access to this class.');
        }

        $search = trim((string) $request->query('search', ''));

        $announcementsQuery = Announcement::query()
            ->with(['class:id,name,created_by', 'user:id,name'])
            ->whereIn('class_id', $classIds)
            ->when($selectedClassId, function ($query) use ($selectedClassId) {
                $query->where('class_id', $selectedClassId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('message', 'like', '%'.$search.'%');
            })
            ->orderByDesc('is_pinned')
            ->latest();

        $announcements = $announcementsQuery->paginate(20)->withQueryString();

        $this->markCollectionAsRead($user->id, $announcements->getCollection()->pluck('id'));

        $classAnnouncementCounts = $this->buildClassAnnouncementCounts($classIds);
        $totalAnnouncements = (int) $classAnnouncementCounts->sum();

        $layoutMap = [
            'psych' => 'layouts.appPsych',
            'educ' => 'layouts.appEduc',
            'accountancy' => 'layouts.appAcc',
            'teacher' => 'layouts.appTeach',
            'admin' => 'layouts.appAdmin',
            'superadmin' => 'layouts.appAdmin',
        ];

        $track = $user->role === 'student' ? ($user->program ?? 'accountancy') : $user->role;
        $layout = $layoutMap[$track] ?? 'layouts.appAcc';

        return view('pages.announcements.index', compact(
            'announcements',
            'classes',
            'selectedClassId',
            'search',
            'classAnnouncementCounts',
            'totalAnnouncements',
            'layout'
        ));
    }

    public function store(ClassModel $class, Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        if (! $this->canPostToClass($user->id, (string) $user->role, $class)) {
            abort(403);
        }

        $isPinned = (bool) $request->boolean('is_pinned');

        DB::transaction(function () use ($class, $user, $request, $isPinned) {
            if ($isPinned) {
                Announcement::query()
                    ->where('class_id', $class->id)
                    ->where('is_pinned', true)
                    ->update(['is_pinned' => false]);
            }

            $class->announcements()->create([
                'user_id' => $user->id,
                'message' => trim((string) $request->input('message')),
                'is_pinned' => $isPinned,
            ]);
        });

        return back()->with('success', 'Announcement posted.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        if (! $this->canManageAnnouncement($user->id, (string) $user->role, $announcement)) {
            abort(403);
        }

        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    public function update(Announcement $announcement, Request $request)
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        if (! $this->canManageAnnouncement($user->id, (string) $user->role, $announcement)) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $announcement->update([
            'message' => trim((string) $validated['message']),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Announcement updated.',
                'announcement' => [
                    'id' => $announcement->id,
                    'message' => $announcement->message,
                ],
            ]);
        }

        return back()->with('success', 'Announcement updated.');
    }

    public function markRead(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $classIds = $this->accessibleClassesQuery($user)->pluck('classes.id');
        $selectedClassId = $request->integer('class_id') ?: null;

        if ($selectedClassId !== null && ! $classIds->contains($selectedClassId)) {
            abort(403, 'You do not have access to this class.');
        }

        $announcementIds = Announcement::query()
            ->whereIn('class_id', $classIds)
            ->when($selectedClassId, function ($query) use ($selectedClassId) {
                $query->where('class_id', $selectedClassId);
            })
            ->pluck('id');

        $this->markCollectionAsRead($user->id, $announcementIds);

        return back()->with('success', 'Announcements marked as read.');
    }

    public function classFeed(ClassModel $class)
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $classIds = $this->accessibleClassesQuery($user)->pluck('id');
        abort_unless($classIds->contains($class->id), 403);

        $items = Announcement::query()
            ->with(['user:id,name', 'class:id,created_by'])
            ->where('class_id', $class->id)
            ->orderByDesc('is_pinned')
            ->latest()
            ->limit(15)
            ->get()
            ->map(function (Announcement $announcement) use ($user) {
                $canManage = $this->canManageAnnouncement($user->id, (string) $user->role, $announcement);

                return [
                    'id' => $announcement->id,
                    'message' => $announcement->message,
                    'is_pinned' => (bool) $announcement->is_pinned,
                    'author' => $announcement->user?->name ?? 'Unknown',
                    'created_at' => optional($announcement->created_at)?->toIso8601String(),
                    'created_human' => optional($announcement->created_at)?->diffForHumans(),
                    'can_delete' => $canManage,
                    'can_edit' => $canManage,
                ];
            });

        return response()->json(['announcements' => $items]);
    }

    /**
     * List comments (nested replies) for an announcement.
     * Access: enrolled students of the class, the class's owning teacher, or admin.
     */
    public function comments(Announcement $announcement)
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);
        abort_unless($this->canAccessAnnouncementThread($user, $announcement), 403);

        $comments = AnnouncementComment::query()
            ->where('announcement_id', $announcement->id)
            ->whereNull('parent_id')
            ->with(['user:id,name', 'replies'])
            ->oldest()
            ->get();

        return response()->json([
            'comments' => $this->formatCommentTree($comments),
        ]);
    }

    /**
     * Post a comment or nested reply on an announcement.
     */
    public function storeComment(Request $request, Announcement $announcement)
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);
        abort_unless($this->canAccessAnnouncementThread($user, $announcement), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:announcement_comments,id'],
        ]);

        // Siguraduhing ang parent comment ay kabilang sa parehong announcement
        if (! empty($validated['parent_id'])) {
            $parentBelongs = AnnouncementComment::where('id', $validated['parent_id'])
                ->where('announcement_id', $announcement->id)
                ->exists();

            if (! $parentBelongs) {
                abort(422, 'Invalid parent comment.');
            }
        }

        $comment = AnnouncementComment::create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => trim($validated['body']),
        ])->load('user:id,name');

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'parent_id' => $comment->parent_id,
                'body' => $comment->body,
                'author' => $comment->user?->name ?? 'Unknown',
                'author_id' => $comment->user_id,
                'created_human' => $comment->created_at->diffForHumans(),
                'replies' => [],
            ],
        ]);
    }

    private function canAccessAnnouncementThread($user, Announcement $announcement): bool
    {
        $isAdmin = in_array($user->role, ['admin', 'superadmin'], true);
        $isOwningTeacher = (int) ($announcement->class?->created_by ?? 0) === (int) $user->id;
        $isEnrolled = $announcement->class
            ? $announcement->class->users()->where('users.id', $user->id)->exists()
            : false;

        return $isAdmin || $isOwningTeacher || $isEnrolled;
    }

    private function formatCommentTree($comments): array
    {
        return $comments->map(function (AnnouncementComment $comment) {
            return [
                'id' => $comment->id,
                'parent_id' => $comment->parent_id,
                'body' => $comment->body,
                'author' => $comment->user?->name ?? 'Unknown',
                'author_id' => $comment->user_id,
                'created_human' => $comment->created_at->diffForHumans(),
                'replies' => $this->formatCommentTree($comment->replies),
            ];
        })->values()->all();
    }

    private function accessibleClassesQuery($user)
    {
        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return ClassModel::query();
        }

        if ($user->role === 'teacher') {
            return ClassModel::query()->where('created_by', $user->id);
        }

        return $user->classes();
    }

    private function canPostToClass(int $userId, string $role, ClassModel $class): bool
    {
        if (in_array($role, ['admin', 'superadmin'], true)) {
            return true;
        }

        if ($role === 'teacher') {
            return (int) $class->created_by === $userId;
        }

        return false;
    }

    private function canManageAnnouncement(int $userId, string $role, Announcement $announcement): bool
    {
        $isAdmin = in_array($role, ['admin', 'superadmin'], true);
        $isPoster = (int) $announcement->user_id === $userId;
        $isClassTeacher = (int) ($announcement->class?->created_by ?? 0) === $userId;

        return $isAdmin || $isPoster || $isClassTeacher;
    }

    private function markCollectionAsRead(int $userId, Collection $announcementIds): void
    {
        if ($announcementIds->isEmpty()) {
            return;
        }

        $now = Carbon::now();

        $rows = $announcementIds->map(function ($announcementId) use ($userId, $now) {
            return [
                'announcement_id' => (int) $announcementId,
                'user_id' => $userId,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::table('announcement_reads')->upsert(
            $rows,
            ['announcement_id', 'user_id'],
            ['read_at', 'updated_at']
        );
    }

    private function buildClassAnnouncementCounts(Collection $classIds): Collection
    {
        if ($classIds->isEmpty()) {
            return collect();
        }

        return Announcement::query()
            ->whereIn('class_id', $classIds)
            ->selectRaw('class_id, COUNT(*) as announcement_count')
            ->groupBy('class_id')
            ->pluck('announcement_count', 'class_id');
    }
}
