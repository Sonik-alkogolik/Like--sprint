<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\FraudEvent;
use App\Models\User;
use App\Services\FraudEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminOpsController extends Controller
{
    public function __construct(private readonly FraudEventService $fraudEvents)
    {
    }

    public function users(): JsonResponse
    {
        $items = User::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'name', 'email', 'role', 'is_blocked', 'created_at']);

        return response()->json(['items' => $items]);
    }

    public function blockUser(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->is_blocked = true;
        $user->save();

        $this->fraudEvents->log(
            eventType: 'user_blocked',
            userId: $user->id,
            severity: 'high',
            message: (string) ($request->input('reason') ?: 'Blocked by admin'),
            payload: ['email' => $user->email],
        );

        return response()->json(['user' => $user]);
    }

    public function unblockUser(Request $request, User $user): JsonResponse
    {
        $user->is_blocked = false;
        $user->save();

        $this->fraudEvents->log(
            eventType: 'user_unblocked',
            userId: $user->id,
            severity: 'low',
            message: 'Unblocked by admin',
            payload: ['email' => $user->email],
        );

        return response()->json(['user' => $user]);
    }

    public function disputes(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'open');
        $allowed = ['open', 'in_review', 'resolved_for_performer', 'resolved_for_advertiser'];
        if (!in_array($status, $allowed, true)) {
            $status = 'open';
        }

        $items = Dispute::query()
            ->where('status', $status)
            ->with(['task', 'submission', 'performer', 'advertiser'])
            ->orderBy('id')
            ->limit(200)
            ->get();

        return response()->json(['items' => $items]);
    }

    public function setDisputeStatus(Request $request, Dispute $dispute): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['in_review', 'resolved_for_performer', 'resolved_for_advertiser'])],
            'admin_comment' => ['nullable', 'string', 'max:5000'],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newStatus = (string) $request->input('status');
        $dispute->status = $newStatus;
        $dispute->admin_comment = $request->input('admin_comment')
            ? (string) $request->input('admin_comment')
            : null;
        $dispute->resolved_by_id = str_starts_with($newStatus, 'resolved_') ? $admin->id : null;
        $dispute->resolved_at = str_starts_with($newStatus, 'resolved_') ? now() : null;
        $dispute->save();

        $this->fraudEvents->log(
            eventType: 'dispute_status_changed',
            userId: $dispute->performer_id,
            taskId: $dispute->task_id,
            submissionId: $dispute->submission_id,
            severity: $newStatus === 'resolved_for_performer' ? 'medium' : 'low',
            message: "Dispute #{$dispute->id} set to {$newStatus}",
            payload: [
                'dispute_id' => $dispute->id,
                'resolved_by_id' => $admin->id,
                'admin_comment' => $dispute->admin_comment,
            ],
        );

        return response()->json(['dispute' => $dispute->load(['task', 'submission', 'performer', 'advertiser'])]);
    }

    public function fraudEvents(Request $request): JsonResponse
    {
        $severity = (string) $request->query('severity', '');
        $query = FraudEvent::query()
            ->with(['user', 'task', 'submission'])
            ->orderByDesc('id')
            ->limit(200);
        if (in_array($severity, ['low', 'medium', 'high'], true)) {
            $query->where('severity', $severity);
        }

        return response()->json(['items' => $query->get()]);
    }
}
