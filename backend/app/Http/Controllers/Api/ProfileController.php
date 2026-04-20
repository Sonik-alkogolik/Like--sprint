<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeviceLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct(private readonly DeviceLogService $deviceLogs)
    {
    }

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('profile');

        return response()->json([
            'profile' => [
                'username' => $user->profile?->username,
                'display_name' => $user->profile?->display_name,
                'about' => $user->profile?->about,
                'payout_wallet' => $user->profile?->payout_wallet,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('profiles', 'username')->ignore($user->profile?->id),
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:2000'],
            'payout_wallet' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profile = $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $user->name]
        );

        $profile->fill([
            'username' => $request->input('username'),
            'display_name' => $request->input('display_name'),
            'about' => $request->input('about'),
            'payout_wallet' => $request->input('payout_wallet'),
        ])->save();

        $this->deviceLogs->log($request, 'profile_update', $user, $request->attributes->get('user_session'));

        return response()->json(['message' => 'Profile updated']);
    }
}
