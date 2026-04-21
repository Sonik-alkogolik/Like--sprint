<?php

namespace App\Services;

use App\Models\Dispute;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DisputeService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly AuditLogService $auditLogs,
        private readonly FraudEventService $fraudEvents,
    ) {}

    public function setStatus(
        User $admin,
        Dispute $dispute,
        string $newStatus,
        ?string $adminComment = null,
        bool $applyCompensation = true,
    ): Dispute {
        return DB::transaction(function () use ($admin, $dispute, $newStatus, $adminComment, $applyCompensation) {
            /** @var Dispute $locked */
            $locked = Dispute::query()->whereKey($dispute->id)->lockForUpdate()->firstOrFail();
            /** @var Submission $submission */
            $submission = Submission::query()->whereKey($locked->submission_id)->lockForUpdate()->firstOrFail();

            $old = [
                'status' => $locked->status,
                'admin_comment' => $locked->admin_comment,
                'compensation_applied' => (bool) $locked->compensation_applied,
                'compensation_amount' => (float) $locked->compensation_amount,
            ];

            $locked->status = $newStatus;
            $locked->admin_comment = $adminComment;
            $locked->resolved_by_id = str_starts_with($newStatus, 'resolved_') ? $admin->id : null;
            $locked->resolved_at = str_starts_with($newStatus, 'resolved_') ? now() : null;

            if (
                $newStatus === 'resolved_for_performer'
                && $applyCompensation
                && !$locked->compensation_applied
                && $submission->status === 'rejected'
            ) {
                $task = $locked->task()->lockForUpdate()->firstOrFail();
                $performer = $locked->performer()->lockForUpdate()->firstOrFail();
                $advertiser = $locked->advertiser()->lockForUpdate()->firstOrFail();
                $amount = (float) $task->price_per_action;

                if ($amount <= 0) {
                    throw new RuntimeException('Invalid compensation amount');
                }

                $this->wallets->debit($advertiser, $amount, 'dispute_compensation_debit', [
                    'dispute_id' => $locked->id,
                    'submission_id' => $locked->submission_id,
                    'task_id' => $locked->task_id,
                ]);
                $this->wallets->credit($performer, $amount, 'dispute_compensation_credit', [
                    'dispute_id' => $locked->id,
                    'submission_id' => $locked->submission_id,
                    'task_id' => $locked->task_id,
                ]);

                $locked->compensation_applied = true;
                $locked->compensation_amount = $amount;
                $locked->compensation_applied_at = now();
            }

            $locked->save();

            $this->fraudEvents->log(
                eventType: 'dispute_status_changed',
                userId: $locked->performer_id,
                taskId: $locked->task_id,
                submissionId: $locked->submission_id,
                severity: $newStatus === 'resolved_for_performer' ? 'medium' : 'low',
                message: "Dispute #{$locked->id} set to {$newStatus}",
                payload: [
                    'dispute_id' => $locked->id,
                    'resolved_by_id' => $admin->id,
                    'admin_comment' => $locked->admin_comment,
                    'compensation_applied' => (bool) $locked->compensation_applied,
                    'compensation_amount' => (float) $locked->compensation_amount,
                ],
            );

            $this->auditLogs->log(
                actor: $admin,
                action: 'admin_dispute_status_changed',
                entityType: 'dispute',
                entityId: $locked->id,
                oldValues: $old,
                newValues: [
                    'status' => $locked->status,
                    'admin_comment' => $locked->admin_comment,
                    'compensation_applied' => (bool) $locked->compensation_applied,
                    'compensation_amount' => (float) $locked->compensation_amount,
                ],
                meta: [
                    'apply_compensation' => $applyCompensation,
                    'submission_status' => $submission->status,
                ],
            );

            return $locked->load(['task', 'submission', 'performer', 'advertiser']);
        });
    }
}
