<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $onlyUnread = $request->boolean('unread');

        $query = UserNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id');

        if ($onlyUnread) {
            $query->whereNull('read_at');
        }

        $items = $query->limit(200)->get();

        return response()->json(['items' => $items]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $count = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request, UserNotification $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ((int) $notification->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($notification->read_at === null) {
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json(['notification' => $notification]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function dispatchQueue(Request $request): JsonResponse
    {
        $limit = max(1, min(500, (int) $request->input('limit', 100)));
        $result = $this->notifications->dispatchPending($limit);

        return response()->json(['result' => $result]);
    }

    public function queueStats(): JsonResponse
    {
        return response()->json(['stats' => $this->notifications->stats()]);
    }
}
