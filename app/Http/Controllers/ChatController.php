<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Render chat page with role-specific view.
     */
    public function index()
    {
        $this->ensureAdminMessagingDisabled();

        $user = Auth::user();
        $track = $user->role === 'student' ? ($user->program ?? 'accountancy') : $user->role;

        $layout = match ($track) {
            'admin', 'superadmin' => 'layouts.appAdmin',
            'teacher' => 'layouts.appTeach',
            'accountancy' => 'layouts.appAcc',
            'educ' => 'layouts.appEduc',
            'psych' => 'layouts.appPsych',
            default => 'layouts.app',
        };

        $chatTheme = match ($track) {
            'teacher' => 'teacher',
            default => 'student',
        };

        $classes = match ($user->role) {
            'admin' => ClassModel::query()
                ->orderBy('classes.created_at', 'desc')
                ->get(['id', 'name']),

            'teacher' => ClassModel::query()
                ->where('created_by', $user->id)
                ->orderBy('classes.created_at', 'desc')
                ->get(['id', 'name']),

            default => $user->classes()
                ->select('classes.id', 'classes.name')
                ->orderBy('class_user.created_at', 'desc')
                ->get(),
        };

        // Return role-specific chat view
        $view = match ($track) {
            'teacher' => 'pages.chat.teacher',
            'accountancy' => 'pages.chat.accountancy',
            'educ' => 'pages.chat.educ',
            'psych' => 'pages.chat.psych',
            'admin' => 'pages.chat.admin',
            'superadmin' => 'pages.chat.superadmin',
            default => 'pages.chat.index',
        };

        if (! view()->exists($view)) {
            $view = 'pages.chat.index';
        }

        return view($view, compact('classes', 'layout', 'chatTheme'));
    }

    /**
     * List direct conversations for the authenticated user.
     */
    public function conversations()
    {
        $this->ensureAdminMessagingDisabled();

        $user = Auth::user();

        $chats = Chat::query()
            ->where('kind', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with([
                'participants:id,name,idnumber,role,last_seen_at',
                'lastMessage' => fn ($q) => $q->with('sender:id,name,idnumber,role')
                    ->select('id', 'chat_id', 'sender_id', 'body', 'created_at'),
            ])
            ->get();

        $chats = $chats->sortByDesc(fn (Chat $chat) => optional($chat->lastMessage)->created_at?->timestamp ?? 0);

        $payload = $chats->map(function (Chat $chat) use ($user) {
            $other = $chat->participants->firstWhere('id', '!=', $user->id);

            return [
                'chat_id' => $chat->id,
                'other_user' => $other ? [
                    'id' => $other->id,
                    'name' => $other->name,
                    'idnumber' => $other->idnumber,
                    'role' => $other->role,
                    'is_online' => $other->isOnline(),
                    'last_seen_label' => $other->lastSeenLabel(),
                ] : null,
                'last_message' => $chat->lastMessage ? [
                    'id' => $chat->lastMessage->id,
                    'sender_id' => $chat->lastMessage->sender_id,
                    'body' => Str::limit($chat->lastMessage->body, 120),
                    'created_at' => $chat->lastMessage->created_at?->toIso8601String(),
                ] : null,
            ];
        })->values();

        return response()->json(['chats' => $payload]);
    }

    /**
     * Fetch messages for a chat (requires membership).
     */
    public function messages(Chat $chat, Request $request)
    {
        $this->ensureAdminMessagingDisabled();

        $user = Auth::user();

        if (! $chat->participants()->where('users.id', $user->id)->exists()) {
            abort(403, 'Unauthorized chat access.');
        }

        $afterId = $request->integer('after_id');

        $messages = ChatMessage::query()
            ->with('sender:id,name,idnumber,role')
            ->where('chat_id', $chat->id)
            ->when($afterId, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('chat_messages.created_at', 'asc')
            ->limit(200)
            ->get()
            ->map(function (ChatMessage $m) {
                return [
                    'id' => $m->id,
                    'sender_id' => $m->sender_id,
                    'sender' => [
                        'name' => $m->sender?->name,
                        'idnumber' => $m->sender?->idnumber,
                        'role' => $m->sender?->role,
                    ],
                    'body' => $m->body,
                    'created_at' => $m->created_at?->toIso8601String(),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    /**
     * Start (or reuse) a direct chat with another user.
     */
    /**
     * Start (or reuse) a direct chat with another user.
     */
    public function start(Request $request)
    {
        $this->ensureAdminMessagingDisabled();

        $user = Auth::user();

        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $target = User::findOrFail($validated['target_user_id']);

        if ($target->id === $user->id) {
            abort(422, 'You cannot start a chat with yourself.');
        }

        // Anyone can now message anyone (no class restriction)

        // Find existing direct chat between these exact two users
        $existingChat = Chat::query()
            ->where('kind', 'direct')
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->whereHas('participants', function ($q) use ($target) {
                $q->where('users.id', $target->id);
            })
            ->whereDoesntHave('participants', function ($q) use ($user, $target) {
                $q->whereNotIn('users.id', [$user->id, $target->id]);
            })
            ->first();

        if ($existingChat) {
            return response()->json([
                'success' => true,
                'chat_id' => $existingChat->id,
            ]);
        }

        // No existing chat → create a new one
        $chat = Chat::create([
            'kind' => 'direct',
            'created_by' => $user->id,
            'class_id' => null,        // completely removed
        ]);

        $chat->participants()->attach([$user->id, $target->id]);

        return response()->json([
            'success' => true,
            'chat_id' => $chat->id,
        ]);
    }

    /**
     * Send a message inside a chat.
     */
    public function sendMessage(Chat $chat, Request $request)
    {
        $this->ensureAdminMessagingDisabled();

        $user = Auth::user();

        if (! $chat->participants()->where('users.id', $user->id)->exists()) {
            abort(403, 'Unauthorized chat access.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'sender_id' => $user->id,
            'body' => trim($validated['body']),
        ])->load('sender:id,name,idnumber,role');

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender' => [
                    'name' => $message->sender?->name,
                    'idnumber' => $message->sender?->idnumber,
                    'role' => $message->sender?->role,
                ],
                'body' => $message->body,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Remove the current user from a DM conversation.
     */
    public function remove(Chat $chat)
    {
        $this->ensureAdminMessagingDisabled();

        $user = Auth::user();

        $allowedRoles = ['teacher', 'admin', 'psych', 'educ', 'accountancy'];
        if (! in_array($user->role, $allowedRoles, true)) {
            abort(403, 'Not allowed to remove DMs.');
        }

        if (! $chat->participants()->where('users.id', $user->id)->exists()) {
            abort(403, 'Unauthorized chat access.');
        }

        $chat->participants()->detach($user->id);

        if ($chat->participants()->count() === 0) {
            $chat->delete();
        }

        return response()->json(['success' => true]);
    }

    private function ensureAdminMessagingDisabled(): void
    {
        $user = Auth::user();

        if ($user && in_array($user->role, ['admin', 'superadmin'], true)) {
            abort(403, 'Messaging is not available for admin accounts.');
        }
    }

    private function canDirectMessage(User $from, User $to): bool
    {
        if ($from->role === 'admin' || $to->role === 'admin') {
            return true;
        }

        return $this->usersShareClass($from, $to);
    }

    private function usersShareClass(User $a, User $b): bool
    {
        return ClassModel::query()->where(function ($q) use ($a, $b) {
            $q->where(function ($q2) use ($a, $b) {
                $q2->where('created_by', $a->id)
                    ->whereHas('users', fn ($u) => $u->where('users.id', $b->id));
            })->orWhere(function ($q2) use ($a, $b) {
                $q2->where('created_by', $b->id)
                    ->whereHas('users', fn ($u) => $u->where('users.id', $a->id));
            })->orWhere(function ($q2) use ($a, $b) {
                $q2->whereHas('users', fn ($u) => $u->where('users.id', $a->id))
                    ->whereHas('users', fn ($u) => $u->where('users.id', $b->id));
            });
        })->exists();
    }

    private function sharedClassId(User $a, User $b): ?int
    {
        $classId = ClassModel::query()
            ->where(function ($q) use ($a, $b) {
                $q->where(function ($q2) use ($a, $b) {
                    $q2->where('created_by', $a->id)
                        ->whereHas('users', fn ($u) => $u->where('users.id', $b->id));
                })->orWhere(function ($q2) use ($a, $b) {
                    $q2->where('created_by', $b->id)
                        ->whereHas('users', fn ($u) => $u->where('users.id', $a->id));
                })->orWhere(function ($q2) use ($a, $b) {
                    $q2->whereHas('users', fn ($u) => $u->where('users.id', $a->id))
                        ->whereHas('users', fn ($u) => $u->where('users.id', $b->id));
                });
            })
            ->value('id');

        return $classId ? (int) $classId : null;
    }
}
