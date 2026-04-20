<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use App\Services\DeviceLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(private readonly DeviceLogService $deviceLogs)
    {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $current = $request->attributes->get('user_session');

        $sessions = $user->sessions()
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (UserSession $session) => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_seen_at' => optional($session->last_seen_at)->toISOString(),
                'revoked_at' => optional($session->revoked_at)->toISOString(),
                'is_current' => $current && $current->id === $session->id,
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    public function revoke(Request $request, UserSession $session): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($session->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $session->revoked_at) {
            $session->forceFill(['revoked_at' => now()])->save();
            $this->deviceLogs->log($request, 'session_revoke', $user, $request->attributes->get('user_session'));
        }

        return response()->json(['message' => 'Session revoked']);
    }
}

