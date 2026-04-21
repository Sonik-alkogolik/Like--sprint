<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Dispute;
use App\Models\Submission;
use App\Models\SubmissionAttachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssignmentService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly NotificationService $notifications,
        private readonly FraudEventService $fraudEvents,
    ) {}

    public function takeTask(User $performer, Task $task): Assignment
    {
        if ($task->status !== 'active' || $task->moderation_status !== 'approved') {
            throw new RuntimeException('Task is not available now');
        }

        if ((int) $task->approved_count >= (int) $task->max_approvals) {
            throw new RuntimeException('Task approval limit reached');
        }

        return DB::transaction(function () use ($performer, $task) {
            /** @var Task $lockedTask */
            $lockedTask = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $this->assertRepeatRules($performer, $lockedTask);

            $assignment = Assignment::query()->create([
                'task_id' => $lockedTask->id,
                'performer_id' => $performer->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'deadline_at' => now()->addMinutes((int) $lockedTask->assignment_ttl_minutes),
            ]);

            $lockedTask->in_progress_count = (int) $lockedTask->in_progress_count + 1;
            $lockedTask->save();

            return $assignment->load('task');
        });
    }

    public function submitReport(User $performer, Assignment $assignment, string $reportText, array $attachments = []): Submission
    {
        return DB::transaction(function () use ($performer, $assignment, $reportText, $attachments) {
            /** @var Assignment $lockedAssignment */
            $lockedAssignment = Assignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            if ((int) $lockedAssignment->performer_id !== (int) $performer->id) {
                throw new RuntimeException('Forbidden');
            }

            if (in_array($lockedAssignment->status, ['approved', 'rejected', 'cancelled', 'expired'], true)) {
                throw new RuntimeException('Assignment already finalized');
            }

            if ($lockedAssignment->deadline_at && now()->greaterThan($lockedAssignment->deadline_at)) {
                $lockedAssignment->status = 'expired';
                $lockedAssignment->save();
                $this->decrementInProgress((int) $lockedAssignment->task_id);
                throw new RuntimeException('Assignment expired');
            }

            $task = Task::query()->whereKey($lockedAssignment->task_id)->lockForUpdate()->firstOrFail();
            $submission = Submission::query()->firstOrNew(['assignment_id' => $lockedAssignment->id]);
            $submission->fill([
                'task_id' => $task->id,
                'performer_id' => $performer->id,
                'status' => 'pending',
                'report_text' => $reportText,
                'rework_comment' => null,
                'submitted_at' => now(),
                'review_deadline_at' => now()->addDays((int) $task->check_deadline_days),
                'reviewed_at' => null,
                'reviewer_id' => null,
            ]);
            $submission->save();

            $submission->attachments()->delete();
            foreach ($attachments as $attachment) {
                if (!isset($attachment['file_url']) || trim((string) $attachment['file_url']) === '') {
                    continue;
                }
                SubmissionAttachment::query()->create([
                    'submission_id' => $submission->id,
                    'attachment_type' => (string) ($attachment['attachment_type'] ?? 'file'),
                    'file_url' => (string) $attachment['file_url'],
                ]);
            }

            $lockedAssignment->status = 'submitted';
            $lockedAssignment->submitted_at = now();
            $lockedAssignment->save();

            if ($task->verification_mode === 'auto_accept') {
                $submission = $this->approve($task->advertiser, $submission);
            } else {
                $this->notifications->enqueue(
                    $task->advertiser,
                    'submission_submitted',
                    'Новый отчёт на проверку',
                    "По заданию #{$task->id} \"{$task->title}\" отправлен новый отчёт.",
                    [
                        'task_id' => $task->id,
                        'submission_id' => $submission->id,
                    ],
                    true,
                );
            }

            return $submission->load(['attachments', 'task']);
        });
    }

    public function cancel(User $performer, Assignment $assignment): Assignment
    {
        return DB::transaction(function () use ($performer, $assignment) {
            /** @var Assignment $locked */
            $locked = Assignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            if ((int) $locked->performer_id !== (int) $performer->id) {
                throw new RuntimeException('Forbidden');
            }

            if (in_array($locked->status, ['approved', 'rejected', 'cancelled', 'expired'], true)) {
                throw new RuntimeException('Assignment already finalized');
            }

            $locked->status = 'cancelled';
            $locked->save();

            $submission = Submission::query()->where('assignment_id', $locked->id)->first();
            if ($submission && !in_array($submission->status, ['approved', 'rejected', 'cancelled'], true)) {
                $submission->status = 'cancelled';
                $submission->reviewed_at = now();
                $submission->save();
            }

            $this->decrementInProgress((int) $locked->task_id);

            return $locked;
        });
    }

    public function pendingForPerformer(User $performer, string $sort = 'date'): array
    {
        $query = Submission::query()
            ->where('performer_id', $performer->id)
            ->where('status', 'pending')
            ->with(['task', 'assignment']);

        if ($sort === 'price') {
            $query->join('tasks', 'tasks.id', '=', 'submissions.task_id')
                ->orderByDesc('tasks.price_per_action')
                ->select('submissions.*');
        } else {
            $query->orderByDesc('submitted_at');
        }

        /** @var Collection<int, Submission> $items */
        $items = $query->get();
        $total = $items->sum(fn (Submission $s) => (float) $s->task?->price_per_action);

        return [
            'items' => $items,
            'total_sum' => $total,
        ];
    }

    public function pendingForAdvertiser(User $advertiser, Task $task): Collection
    {
        if ((int) $task->advertiser_id !== (int) $advertiser->id) {
            throw new RuntimeException('Forbidden');
        }

        return Submission::query()
            ->where('task_id', $task->id)
            ->where('status', 'pending')
            ->with(['performer', 'assignment', 'attachments'])
            ->orderBy('submitted_at')
            ->get();
    }

    public function requestRework(User $advertiser, Submission $submission, string $comment): Submission
    {
        return DB::transaction(function () use ($advertiser, $submission, $comment) {
            /** @var Submission $locked */
            $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $task = Task::query()->whereKey($locked->task_id)->lockForUpdate()->firstOrFail();
            if ((int) $task->advertiser_id !== (int) $advertiser->id) {
                throw new RuntimeException('Forbidden');
            }
            if ($locked->status !== 'pending') {
                throw new RuntimeException('Submission is not pending');
            }

            $locked->status = 'rework_requested';
            $locked->rework_comment = $comment;
            $locked->reviewed_at = now();
            $locked->reviewer_id = $advertiser->id;
            $locked->save();

            $assignment = Assignment::query()->whereKey($locked->assignment_id)->lockForUpdate()->firstOrFail();
            $assignment->status = 'rework_requested';
            $assignment->save();

            $this->notifications->enqueue(
                $locked->performer,
                'submission_rework_requested',
                'Отчёт возвращён на доработку',
                "По заданию #{$task->id} \"{$task->title}\" отчёт возвращён на доработку.",
                [
                    'task_id' => $task->id,
                    'submission_id' => $locked->id,
                    'comment' => $comment,
                ],
                true,
            );

            return $locked->load(['assignment', 'task']);
        });
    }

    public function approve(User $advertiser, Submission $submission): Submission
    {
        return DB::transaction(function () use ($advertiser, $submission) {
            /** @var Submission $locked */
            $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            /** @var Task $task */
            $task = Task::query()->whereKey($locked->task_id)->lockForUpdate()->firstOrFail();
            if ((int) $task->advertiser_id !== (int) $advertiser->id) {
                throw new RuntimeException('Forbidden');
            }

            if ($locked->status !== 'pending') {
                throw new RuntimeException('Submission is not pending');
            }

            if ((int) $task->approved_count >= (int) $task->max_approvals) {
                throw new RuntimeException('Approval limit reached');
            }

            $assignment = Assignment::query()->whereKey($locked->assignment_id)->lockForUpdate()->firstOrFail();
            $performer = User::query()->whereKey($assignment->performer_id)->lockForUpdate()->firstOrFail();
            $advertiserLocked = User::query()->whereKey($task->advertiser_id)->lockForUpdate()->firstOrFail();

            $locked->status = 'approved';
            $locked->reviewed_at = now();
            $locked->reviewer_id = $advertiser->id;
            $locked->save();

            $assignment->status = 'approved';
            $assignment->save();

            $task->approved_count = (int) $task->approved_count + 1;
            $task->in_progress_count = max(0, (int) $task->in_progress_count - 1);
            if ((int) $task->approved_count >= (int) $task->max_approvals) {
                $task->status = 'paused';
            }
            $task->save();

            $total = (float) $task->price_per_action + (float) $task->commission_per_action;
            $this->wallets->spendFromHold($advertiserLocked, $total, 'task_submission_approved', [
                'task_id' => $task->id,
                'submission_id' => $locked->id,
            ]);
            $this->wallets->credit($performer, (float) $task->price_per_action, 'task_reward', [
                'task_id' => $task->id,
                'submission_id' => $locked->id,
            ]);

            $system = $this->wallets->getOrCreateSystemWallet('platform_commission');
            $system->balance = (float) $system->balance + (float) $task->commission_per_action;
            $system->save();

            $this->notifications->enqueue(
                $performer,
                'submission_approved',
                'Отчёт подтверждён',
                "Ваш отчёт по заданию #{$task->id} \"{$task->title}\" подтверждён.",
                [
                    'task_id' => $task->id,
                    'submission_id' => $locked->id,
                    'reward' => (float) $task->price_per_action,
                ],
                true,
            );

            return $locked->load(['assignment', 'task']);
        });
    }

    public function reject(User $advertiser, Submission $submission): Submission
    {
        return DB::transaction(function () use ($advertiser, $submission) {
            /** @var Submission $locked */
            $locked = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            /** @var Task $task */
            $task = Task::query()->whereKey($locked->task_id)->lockForUpdate()->firstOrFail();
            if ((int) $task->advertiser_id !== (int) $advertiser->id) {
                throw new RuntimeException('Forbidden');
            }
            if ($locked->status !== 'pending') {
                throw new RuntimeException('Submission is not pending');
            }

            $assignment = Assignment::query()->whereKey($locked->assignment_id)->lockForUpdate()->firstOrFail();
            $advertiserLocked = User::query()->whereKey($task->advertiser_id)->lockForUpdate()->firstOrFail();

            $locked->status = 'rejected';
            $locked->reviewed_at = now();
            $locked->reviewer_id = $advertiser->id;
            $locked->save();

            $assignment->status = 'rejected';
            $assignment->save();

            $task->rejected_count = (int) $task->rejected_count + 1;
            $task->in_progress_count = max(0, (int) $task->in_progress_count - 1);
            $task->save();

            $total = (float) $task->price_per_action + (float) $task->commission_per_action;
            $this->wallets->releaseHold($advertiserLocked, $total, 'task_submission_rejected', [
                'task_id' => $task->id,
                'submission_id' => $locked->id,
            ]);

            $this->notifications->enqueue(
                $assignment->performer,
                'submission_rejected',
                'Отчёт отклонён',
                "Ваш отчёт по заданию #{$task->id} \"{$task->title}\" был отклонён.",
                [
                    'task_id' => $task->id,
                    'submission_id' => $locked->id,
                ],
                true,
            );

            $this->fraudEvents->log(
                eventType: 'submission_rejected',
                userId: $assignment->performer_id,
                taskId: $task->id,
                submissionId: $locked->id,
                severity: 'medium',
                message: "Submission #{$locked->id} rejected by advertiser",
                payload: ['advertiser_id' => $advertiser->id],
            );

            return $locked->load(['assignment', 'task']);
        });
    }

    public function massApprove(User $advertiser, Task $task, array $submissionIds = [], bool $all = false): array
    {
        if ((int) $task->advertiser_id !== (int) $advertiser->id) {
            throw new RuntimeException('Forbidden');
        }

        $query = Submission::query()->where('task_id', $task->id)->where('status', 'pending');
        if (!$all) {
            $query->whereIn('id', $submissionIds);
        }

        $pending = $query->get();
        $approved = 0;
        $failed = 0;

        foreach ($pending as $submission) {
            try {
                $this->approve($advertiser, $submission);
                $approved++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return ['approved' => $approved, 'failed' => $failed];
    }

    public function createDispute(User $performer, Submission $submission, string $reason): Dispute
    {
        if ((int) $submission->performer_id !== (int) $performer->id) {
            throw new RuntimeException('Forbidden');
        }
        if ($submission->status !== 'rejected') {
            throw new RuntimeException('Dispute allowed only for rejected submissions');
        }

        $task = Task::query()->findOrFail($submission->task_id);

        $dispute = Dispute::query()->create([
            'submission_id' => $submission->id,
            'task_id' => $task->id,
            'performer_id' => $performer->id,
            'advertiser_id' => $task->advertiser_id,
            'status' => 'open',
            'reason' => $reason,
        ]);

        $this->notifications->enqueue(
            $task->advertiser,
            'submission_dispute_created',
            'Создан спор по отклонённому отчёту',
            "По заданию #{$task->id} \"{$task->title}\" создан спор по отчёту #{$submission->id}.",
            [
                'task_id' => $task->id,
                'submission_id' => $submission->id,
                'dispute_id' => $dispute->id,
            ],
            true,
        );

        $this->fraudEvents->log(
            eventType: 'dispute_created',
            userId: $performer->id,
            taskId: $task->id,
            submissionId: $submission->id,
            severity: 'high',
            message: "Dispute #{$dispute->id} created for rejected submission",
            payload: ['advertiser_id' => $task->advertiser_id],
        );

        return $dispute;
    }

    private function assertRepeatRules(User $performer, Task $task): void
    {
        $openStatuses = ['in_progress', 'submitted', 'rework_requested'];

        $openExists = Assignment::query()
            ->where('task_id', $task->id)
            ->where('performer_id', $performer->id)
            ->whereIn('status', $openStatuses)
            ->exists();

        if ($openExists) {
            throw new RuntimeException('You already have this task in progress');
        }

        $last = Assignment::query()
            ->where('task_id', $task->id)
            ->where('performer_id', $performer->id)
            ->orderByDesc('id')
            ->first();

        if (!$last) {
            return;
        }

        if ($task->repeat_mode === 'one_time') {
            throw new RuntimeException('Task is one-time only');
        }

        if ($task->repeat_mode === 'repeat_after_review') {
            if (!in_array($last->status, ['approved', 'rejected', 'cancelled', 'expired'], true)) {
                throw new RuntimeException('Repeat available only after previous review');
            }
            return;
        }

        if ($task->repeat_mode === 'repeat_interval') {
            $intervalHours = (int) ($task->repeat_interval_hours ?? 0);
            if ($intervalHours <= 0) {
                throw new RuntimeException('Invalid repeat interval');
            }

            if (!$last->submitted_at) {
                return;
            }

            if (now()->lt($last->submitted_at->copy()->addHours($intervalHours))) {
                throw new RuntimeException('Repeat interval is not reached yet');
            }
        }
    }

    private function decrementInProgress(int $taskId): void
    {
        $task = Task::query()->whereKey($taskId)->lockForUpdate()->first();
        if (!$task) {
            return;
        }
        $task->in_progress_count = max(0, (int) $task->in_progress_count - 1);
        $task->save();
    }
}
