<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskLink;
use App\Models\TaskRequirement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TaskService
{
    public function __construct(private readonly WalletService $wallets)
    {
    }

    public function create(User $advertiser, array $data): Task
    {
        return DB::transaction(function () use ($advertiser, $data) {
            $task = Task::query()->create([
                'advertiser_id' => $advertiser->id,
                'title' => $data['title'],
                'short_description' => $data['short_description'] ?? null,
                'instruction' => $data['instruction'],
                'start_url' => $data['start_url'] ?? null,
                'price_per_action' => $data['price_per_action'],
                'commission_per_action' => $data['commission_per_action'] ?? 0.0100,
                'max_approvals' => $data['max_approvals'],
                'repeat_mode' => $data['repeat_mode'],
                'repeat_interval_hours' => $data['repeat_interval_hours'] ?? null,
                'assignment_ttl_minutes' => $data['assignment_ttl_minutes'] ?? 60,
                'check_deadline_days' => $data['check_deadline_days'] ?? 3,
                'verification_mode' => $data['verification_mode'] ?? 'manual',
                'status' => 'draft',
                'moderation_status' => 'draft',
            ]);

            foreach ($data['requirements'] ?? [] as $index => $requirement) {
                TaskRequirement::query()->create([
                    'task_id' => $task->id,
                    'kind' => $requirement['kind'] ?? 'text',
                    'label' => $requirement['label'] ?? null,
                    'is_required' => (bool) ($requirement['is_required'] ?? true),
                    'sort_order' => $index,
                ]);
            }

            foreach ($data['links'] ?? [] as $index => $link) {
                if (!isset($link['url']) || trim((string) $link['url']) === '') {
                    continue;
                }
                TaskLink::query()->create([
                    'task_id' => $task->id,
                    'url' => $link['url'],
                    'label' => $link['label'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            return $task->load(['requirements', 'links']);
        });
    }

    public function update(Task $task, array $data): Task
    {
        if ($task->status === 'active') {
            throw new RuntimeException('Active task cannot be edited');
        }

        return DB::transaction(function () use ($task, $data) {
            $task->fill([
                'title' => $data['title'] ?? $task->title,
                'short_description' => $data['short_description'] ?? $task->short_description,
                'instruction' => $data['instruction'] ?? $task->instruction,
                'start_url' => $data['start_url'] ?? $task->start_url,
                'price_per_action' => $data['price_per_action'] ?? $task->price_per_action,
                'commission_per_action' => $data['commission_per_action'] ?? $task->commission_per_action,
                'max_approvals' => $data['max_approvals'] ?? $task->max_approvals,
                'repeat_mode' => $data['repeat_mode'] ?? $task->repeat_mode,
                'repeat_interval_hours' => $data['repeat_interval_hours'] ?? $task->repeat_interval_hours,
                'assignment_ttl_minutes' => $data['assignment_ttl_minutes'] ?? $task->assignment_ttl_minutes,
                'check_deadline_days' => $data['check_deadline_days'] ?? $task->check_deadline_days,
                'verification_mode' => $data['verification_mode'] ?? $task->verification_mode,
                'moderation_status' => 'draft',
                'status' => 'draft',
            ]);
            $task->save();

            if (array_key_exists('requirements', $data)) {
                $task->requirements()->delete();
                foreach ($data['requirements'] ?? [] as $index => $requirement) {
                    TaskRequirement::query()->create([
                        'task_id' => $task->id,
                        'kind' => $requirement['kind'] ?? 'text',
                        'label' => $requirement['label'] ?? null,
                        'is_required' => (bool) ($requirement['is_required'] ?? true),
                        'sort_order' => $index,
                    ]);
                }
            }

            if (array_key_exists('links', $data)) {
                $task->links()->delete();
                foreach ($data['links'] ?? [] as $index => $link) {
                    if (!isset($link['url']) || trim((string) $link['url']) === '') {
                        continue;
                    }
                    TaskLink::query()->create([
                        'task_id' => $task->id,
                        'url' => $link['url'],
                        'label' => $link['label'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $task->load(['requirements', 'links']);
        });
    }

    public function submitToModeration(Task $task): Task
    {
        if (!in_array($task->status, ['draft', 'paused'], true)) {
            throw new RuntimeException('Task cannot be submitted to moderation in current status');
        }

        $task->forceFill([
            'status' => 'pending_moderation',
            'moderation_status' => 'pending',
            'moderation_comment' => null,
        ])->save();

        return $task;
    }

    public function moderate(Task $task, string $action, ?string $comment = null): Task
    {
        if ($task->moderation_status !== 'pending') {
            throw new RuntimeException('Task is not in moderation queue');
        }

        if ($action === 'approve') {
            $task->forceFill([
                'status' => 'paused',
                'moderation_status' => 'approved',
                'moderation_comment' => $comment,
            ])->save();
        } else {
            $task->forceFill([
                'status' => 'draft',
                'moderation_status' => 'rejected',
                'moderation_comment' => $comment,
            ])->save();
        }

        return $task;
    }

    public function launch(User $advertiser, Task $task): Task
    {
        if ($task->moderation_status !== 'approved') {
            throw new RuntimeException('Task must be approved by moderation first');
        }

        if ($task->status === 'active') {
            throw new RuntimeException('Task already active');
        }

        return DB::transaction(function () use ($advertiser, $task) {
            if ((float) $task->reserved_total > 0) {
                $task->status = 'active';
                $task->save();

                return $task;
            }

            $fund = (float) $task->price_per_action * (int) $task->max_approvals;
            $commission = (float) $task->commission_per_action * (int) $task->max_approvals;
            $total = $fund + $commission;

            $this->wallets->hold($advertiser, $total, 'task_reserve_hold', [
                'task_id' => $task->id,
                'fund' => $fund,
                'commission' => $commission,
            ]);

            $task->forceFill([
                'status' => 'active',
                'reserved_fund' => $fund,
                'reserved_commission' => $commission,
                'reserved_total' => $total,
            ])->save();

            return $task;
        });
    }

    public function pause(Task $task): Task
    {
        if ($task->status !== 'active') {
            throw new RuntimeException('Task is not active');
        }

        $task->forceFill(['status' => 'paused'])->save();

        return $task;
    }
}
