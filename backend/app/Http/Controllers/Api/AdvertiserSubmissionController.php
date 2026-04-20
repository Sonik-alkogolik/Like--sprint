<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AdvertiserSubmissionController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments)
    {
    }

    public function pendingByTask(Request $request, Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $items = $this->assignments->pendingForAdvertiser($user, $task);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'task' => $task->load(['requirements', 'links']),
            'items' => $items,
            'count' => $items->count(),
        ]);
    }

    public function approve(Request $request, Submission $submission): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $submission = $this->assignments->approve($user, $submission);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['submission' => $submission]);
    }

    public function reject(Request $request, Submission $submission): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $submission = $this->assignments->reject($user, $submission);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['submission' => $submission]);
    }

    public function rework(Request $request, Submission $submission): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $submission = $this->assignments->requestRework($user, $submission, (string) $request->input('comment'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['submission' => $submission]);
    }

    public function massApprove(Request $request, Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'all' => ['sometimes', 'boolean'],
            'submission_ids' => ['sometimes', 'array'],
            'submission_ids.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->assignments->massApprove(
                $user,
                $task,
                (array) $request->input('submission_ids', []),
                (bool) $request->boolean('all'),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['result' => $result]);
    }
}