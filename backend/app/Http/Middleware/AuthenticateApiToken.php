<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();
        if (! $bearer) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $tokenHash = hash('sha256', $bearer);
        $session = UserSession::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->with('user')
            ->first();

        if (! $session || ! $session->user || $session->user->is_blocked) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $session->forceFill(['last_seen_at' => now()])->save();

        $request->setUserResolver(fn () => $session->user);
        $request->attributes->set('user_session', $session);

        return $next($request);
    }
}

