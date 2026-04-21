<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\FraudEvent;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\DisputeService;
use App\Services\FraudEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminOpsController extends Controller
{
    public function __construct(
        private readonly FraudEventService $fraudEvents,
        private readonly AuditLogService $auditLogs,
        private readonly DisputeService $disputes,
    ) {}

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
        /** @var User $admin */
        $admin = $request->user();
        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $old = ['is_blocked' => (bool) $user->is_blocked];
        $user->is_blocked = true;
        $user->save();

        $this->fraudEvents->log(
            eventType: 'user_blocked',
            userId: $user->id,
            severity: 'high',
            message: (string) ($request->input('reason') ?: 'Blocked by admin'),
            payload: ['email' => $user->email],
        );
        $this->auditLogs->log(
            actor: $admin,
            action: 'admin_user_blocked',
            entityType: 'user',
            entityId: $user->id,
            oldValues: $old,
            newValues: ['is_blocked' => true],
            meta: ['reason' => (string) ($request->input('reason') ?: '')],
        );

        return response()->json(['user' => $user]);
    }

    public function unblockUser(Request $request, User $user): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $old = ['is_blocked' => (bool) $user->is_blocked];
        $user->is_blocked = false;
        $user->save();

        $this->fraudEvents->log(
            eventType: 'user_unblocked',
            userId: $user->id,
            severity: 'low',
            message: 'Unblocked by admin',
            payload: ['email' => $user->email],
        );
        $this->auditLogs->log(
            actor: $admin,
            action: 'admin_user_unblocked',
            entityType: 'user',
            entityId: $user->id,
            oldValues: $old,
            newValues: ['is_blocked' => false],
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
            'apply_compensation' => ['sometimes', 'boolean'],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $updated = $this->disputes->setStatus(
                admin: $admin,
                dispute: $dispute,
                newStatus: (string) $request->input('status'),
                adminComment: $request->input('admin_comment') ? (string) $request->input('admin_comment') : null,
                applyCompensation: $request->boolean('apply_compensation', true),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['dispute' => $updated]);
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

    public function auditLogs(Request $request): JsonResponse
    {
        $action = (string) $request->query('action', '');
        $query = AuditLog::query()
            ->with('actor')
            ->orderByDesc('id')
            ->limit(200);
        if ($action !== '') {
            $query->where('action', $action);
        }

        return response()->json(['items' => $query->get()]);
    }
}
