<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminTaskModerationController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly AuditLogService $auditLogs,
    ) {}

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
        /** @var User $admin */
        $admin = $request->user();
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

        $this->auditLogs->log(
            actor: $admin,
            action: 'admin_task_moderated',
            entityType: 'task',
            entityId: $task->id,
            oldValues: null,
            newValues: [
                'moderation_status' => $task->moderation_status,
                'status' => $task->status,
            ],
            meta: [
                'action' => (string) $request->input('action'),
                'comment' => $request->input('comment') ? (string) $request->input('comment') : null,
            ],
        );

        return response()->json(['task' => $task]);
    }
}
