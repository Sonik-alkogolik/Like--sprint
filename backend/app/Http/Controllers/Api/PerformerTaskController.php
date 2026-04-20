<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class PerformerTaskController extends Controller
{
    public function available(): JsonResponse
    {
        $tasks = Task::query()
            ->where('status', 'active')
            ->where('moderation_status', 'approved')
            ->whereColumn('approved_count', '<', 'max_approvals')
            ->orderByDesc('id')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }
}