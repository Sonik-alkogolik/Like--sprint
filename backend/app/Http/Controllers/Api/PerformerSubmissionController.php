<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class PerformerSubmissionController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments)
    {
    }

    public function takeTask(Request $request, Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $assignment = $this->assignments->takeTask($user, $task);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'assignment' => $assignment,
            'start_url' => $task->start_url,
        ], 201);
    }

    public function show(Request $request, Assignment $assignment): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ((int) $assignment->performer_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $assignment->load(['task', 'submission.attachments']);

        return response()->json(['assignment' => $assignment]);
    }

    public function submit(Request $request, Assignment $assignment): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'report_text' => ['required', 'string', 'max:10000'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*.attachment_type' => ['sometimes', 'string', 'max:40'],
            'attachments.*.file_url' => ['required_with:attachments', 'string', 'max:1200'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $submission = $this->assignments->submitReport(
                $user,
                $assignment,
                (string) $request->input('report_text'),
                (array) $request->input('attachments', []),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['submission' => $submission]);
    }

    public function cancel(Request $request, Assignment $assignment): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $assignment = $this->assignments->cancel($user, $assignment);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['assignment' => $assignment]);
    }

    public function pending(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $sort = (string) $request->query('sort', 'date');

        $result = $this->assignments->pendingForPerformer($user, $sort);

        return response()->json([
            'items' => $result['items'],
            'total_sum' => $result['total_sum'],
        ]);
    }

    public function dispute(Request $request, Submission $submission): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $dispute = $this->assignments->createDispute($user, $submission, (string) $request->input('reason'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['dispute' => $dispute], 201);
    }
}