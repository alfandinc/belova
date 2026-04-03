<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        $authId = (int) Auth::id();
        $search = trim((string) $request->input('q', ''));
        $limit = max(1, min((int) $request->input('limit', 200), 200));

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->with([
                'roles:id,name',
                'employee:id,user_id,photo,position_id',
                'employee.position:id,name',
                'dokter:id,user_id,photo',
            ])
            ->whereKeyNot($authId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $participantIds = $users->pluck('id')->values();
        $unreadBySender = $this->getUnreadCounts($authId, $participantIds);
        $latestByUser = $this->getLatestMessages($authId, $participantIds);

        $data = $users->map(function (User $user) use ($unreadBySender, $latestByUser) {
            $latestMessage = $latestByUser->get($user->id);
            $avatar = $this->resolveAvatar($user);
            $positionLabel = $user->employee?->position?->name;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position_label' => $positionLabel,
                'unread_count' => (int) ($unreadBySender[$user->id] ?? 0),
                'last_message' => $latestMessage?->body,
                'last_message_at' => $latestMessage?->created_at?->toIso8601String(),
                'last_message_sender_id' => $latestMessage?->sender_id,
                'avatar_url' => $avatar['url'],
                'avatar_initials' => $avatar['initials'],
            ];
        })->sort(function (array $left, array $right) {
            $leftUnread = (int) ($left['unread_count'] ?? 0);
            $rightUnread = (int) ($right['unread_count'] ?? 0);

            if ($leftUnread !== $rightUnread) {
                return $rightUnread <=> $leftUnread;
            }

            $leftTime = $left['last_message_at'] ?? '';
            $rightTime = $right['last_message_at'] ?? '';

            if ($leftTime !== $rightTime) {
                return strcmp($rightTime, $leftTime);
            }

            return strcasecmp($left['name'], $right['name']);
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total_unread' => array_sum(array_map('intval', $unreadBySender->all())),
            ],
        ]);
    }

    public function conversation(User $user, Request $request): JsonResponse
    {
        $authId = (int) Auth::id();
        $user->loadMissing([
            'roles:id,name',
            'employee:id,user_id,photo,position_id',
            'employee.position:id,name',
            'dokter:id,user_id,photo',
        ]);
        $this->ensureValidParticipant($user, $authId);
        $avatar = $this->resolveAvatar($user);

        UserChatMessage::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $authId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $limit = max(1, min((int) $request->input('limit', 100), 200));

        $messages = UserChatMessage::query()
            ->where(function ($query) use ($authId, $user) {
                $query
                    ->where('sender_id', $authId)
                    ->where('recipient_id', $user->id);
            })
            ->orWhere(function ($query) use ($authId, $user) {
                $query
                    ->where('sender_id', $user->id)
                    ->where('recipient_id', $authId);
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(function (UserChatMessage $message) use ($authId) {
                return [
                    'id' => $message->id,
                    'body' => $message->body,
                    'sender_id' => $message->sender_id,
                    'recipient_id' => $message->recipient_id,
                    'is_mine' => (int) $message->sender_id === $authId,
                    'read_at' => $message->read_at?->toIso8601String(),
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position_label' => $user->employee?->position?->name,
                'avatar_url' => $avatar['url'],
                'avatar_initials' => $avatar['initials'],
            ],
            'messages' => $messages,
        ]);
    }

    public function store(User $user, Request $request): JsonResponse
    {
        $authId = (int) Auth::id();
        $this->ensureValidParticipant($user, $authId);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $body = trim($validated['body']);
        abort_if($body === '', 422, 'Pesan tidak boleh kosong.');

        $message = UserChatMessage::create([
            'sender_id' => $authId,
            'recipient_id' => $user->id,
            'body' => $body,
        ]);

        return response()->json([
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'recipient_id' => $message->recipient_id,
                'is_mine' => true,
                'read_at' => null,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    protected function ensureValidParticipant(User $user, int $authId): void
    {
        abort_if($user->id === $authId, 422, 'Tidak bisa chat dengan diri sendiri.');
    }

    protected function getUnreadCounts(int $authId, Collection $participantIds): Collection
    {
        if ($participantIds->isEmpty()) {
            return collect();
        }

        return UserChatMessage::query()
            ->selectRaw('sender_id, COUNT(*) as unread_count')
            ->where('recipient_id', $authId)
            ->whereNull('read_at')
            ->whereIn('sender_id', $participantIds)
            ->groupBy('sender_id')
            ->pluck('unread_count', 'sender_id');
    }

    protected function getLatestMessages(int $authId, Collection $participantIds): Collection
    {
        if ($participantIds->isEmpty()) {
            return collect();
        }

        return UserChatMessage::query()
            ->where(function ($query) use ($authId, $participantIds) {
                $query
                    ->where('sender_id', $authId)
                    ->whereIn('recipient_id', $participantIds);
            })
            ->orWhere(function ($query) use ($authId, $participantIds) {
                $query
                    ->where('recipient_id', $authId)
                    ->whereIn('sender_id', $participantIds);
            })
            ->orderByDesc('id')
            ->get()
            ->unique(function (UserChatMessage $message) use ($authId) {
                return (int) ($message->sender_id === $authId
                    ? $message->recipient_id
                    : $message->sender_id);
            })
            ->mapWithKeys(function (UserChatMessage $message) use ($authId) {
                $otherUserId = (int) ($message->sender_id === $authId
                    ? $message->recipient_id
                    : $message->sender_id);

                return [$otherUserId => $message];
            });
    }

    protected function resolveAvatar(User $user): array
    {
        $photoPath = $user->employee->photo ?? $user->dokter->photo ?? null;

        return [
            'url' => $photoPath ? asset('storage/' . ltrim($photoPath, '/')) : null,
            'initials' => collect(preg_split('/\s+/', trim($user->name)))
                ->filter()
                ->take(2)
                ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
                ->implode('') ?: 'U',
        ];
    }
}