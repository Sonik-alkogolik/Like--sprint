<?php

namespace App\Services;

use App\Models\DeviceLog;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;

class DeviceLogService
{
    public function log(
        Request $request,
        string $event,
        ?User $user = null,
        ?UserSession $session = null
    ): void {
        DeviceLog::query()->create([
            'user_id' => $user?->id,
            'user_session_id' => $session?->id,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'fingerprint' => (string) $request->header('X-Device-Fingerprint', ''),
            'created_at' => now(),
        ]);
    }
}

