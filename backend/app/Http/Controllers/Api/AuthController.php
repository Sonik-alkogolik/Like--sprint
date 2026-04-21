<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Wallet;
use App\Services\BlacklistService;
use App\Services\DeviceLogService;
use App\Services\FraudEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(
        private readonly DeviceLogService $deviceLogs,
        private readonly BlacklistService $blacklist,
        private readonly FraudEventService $fraudEvents,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', Rule::in(['performer', 'advertiser', 'admin'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = (string) $request->input('email');
        $ip = (string) ($request->ip() ?? '');
        $blockedByEmail = $this->blacklist->findActiveMatch('email', $email);
        $blockedByIp = $ip !== '' ? $this->blacklist->findActiveMatch('ip', $ip) : null;
        if ($blockedByEmail || $blockedByIp) {
            $this->fraudEvents->log(
                eventType: 'blacklist_registration_blocked',
                severity: 'high',
                message: 'Registration blocked by blacklist',
                payload: [
                    'email' => $email,
                    'ip' => $ip,
                    'entry_id' => $blockedByEmail?->id ?? $blockedByIp?->id,
                ],
            );
            return response()->json(['message' => 'Registration is blocked by blacklist'], 403);
        }

        $user = User::query()->create([
            'name' => (string) $request->input('name'),
            'email' => $email,
            'password' => (string) $request->input('password'),
            'role' => (string) $request->input('role', 'performer'),
        ]);

        Profile::query()->create([
            'user_id' => $user->id,
            'display_name' => $user->name,
        ]);
        Wallet::query()->create([
            'user_id' => $user->id,
            'available_balance' => 0,
            'hold_balance' => 0,
        ]);

        [$plainToken, $session] = $this->issueSession($request, $user);
        $this->deviceLogs->log($request, 'register', $user, $session);

        return response()->json([
            'token' => $plainToken,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = (string) $request->input('email');
        $ip = (string) ($request->ip() ?? '');
        $blockedByEmail = $this->blacklist->findActiveMatch('email', $email);
        $blockedByIp = $ip !== '' ? $this->blacklist->findActiveMatch('ip', $ip) : null;
        if ($blockedByEmail || $blockedByIp) {
            $this->fraudEvents->log(
                eventType: 'blacklist_login_blocked',
                severity: 'high',
                message: 'Login blocked by blacklist',
                payload: [
                    'email' => $email,
                    'ip' => $ip,
                    'entry_id' => $blockedByEmail?->id ?? $blockedByIp?->id,
                ],
            );
            return response()->json(['message' => 'Access denied by blacklist'], 403);
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->is_blocked) {
            return response()->json(['message' => 'User is blocked'], 403);
        }

        [$plainToken, $session] = $this->issueSession($request, $user);
        $this->deviceLogs->log($request, 'login', $user, $session);

        return response()->json([
            'token' => $plainToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('profile');

        return response()->json(['user' => $this->userPayload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var UserSession|null $session */
        $session = $request->attributes->get('user_session');
        /** @var User|null $user */
        $user = $request->user();

        if ($session && ! $session->revoked_at) {
            $session->forceFill(['revoked_at' => now()])->save();
        }

        if ($user) {
            $this->deviceLogs->log($request, 'logout', $user, $session);
        }

        return response()->json(['message' => 'Logged out']);
    }

    private function issueSession(Request $request, User $user): array
    {
        $plainToken = Str::random(80);
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_seen_at' => now(),
        ]);

        return [$plainToken, $session];
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_blocked' => $user->is_blocked,
            'profile' => $user->profile ? [
                'username' => $user->profile->username,
                'display_name' => $user->profile->display_name,
                'about' => $user->profile->about,
                'payout_wallet' => $user->profile->payout_wallet,
            ] : null,
        ];
    }
}
