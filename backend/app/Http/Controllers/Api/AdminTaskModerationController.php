<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminTaskModerationController extends Controller
{
    public function __construct(private readonly TaskService $tasks)
    {
    }

    public function queue(): JsonResponse
    {
        $tasks = Task::query()
            ->where('moderation_status', 'pending')
            ->orderBy('id')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    public function moderate(Request $request, Task $task): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $task = $this->tasks->moderate(
                $task,
                (string) $request->input('action'),
                $request->input('comment') ? (string) $request->input('comment') : null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['task' => $task]);
    }
}