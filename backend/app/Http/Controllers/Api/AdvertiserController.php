<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertiserController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'role' => 'advertiser',
            'message' => 'Advertiser dashboard access granted',
            'user_id' => $user->id,
        ]);
    }
}

