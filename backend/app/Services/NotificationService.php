<?php

namespace App\Services;

use App\Models\NotificationEvent;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function enqueue(
        User $user,
        string $type,
        string $title,
        string $message,
        array $payload = [],
        bool $includeEmail = false,
    ): UserNotification {
        return DB::transaction(function () use ($user, $type, $title, $message, $payload, $includeEmail) {
            $notification = UserNotification::query()->create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'payload' => $payload,
                'created_event_at' => now(),
            ]);

            NotificationEvent::query()->create([
                'notification_id' => $notification->id,
                'channel' => 'internal',
                'status' => 'pending',
            ]);

            if ($includeEmail) {
                NotificationEvent::query()->create([
                    'notification_id' => $notification->id,
                    'channel' => 'email',
                    'status' => 'pending',
                ]);
            }

            return $notification;
        });
    }

    public function dispatchPending(int $limit = 100): array
    {
        $events = NotificationEvent::query()
            ->where('status', 'pending')
            ->with('notification.user')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($events as $event) {
            try {
                $this->deliver($event);
                $event->status = 'sent';
                $event->sent_at = now();
                $event->error_message = null;
                $event->save();
                $sent++;
            } catch (\Throwable $e) {
                $event->status = 'failed';
                $event->error_message = mb_substr($e->getMessage(), 0, 2000);
                $event->save();
                $failed++;
            }
        }

        return [
            'processed' => $events->count(),
            'sent' => $sent,
            'failed' => $failed,
            'pending_after' => NotificationEvent::query()->where('status', 'pending')->count(),
        ];
    }

    public function stats(): array
    {
        return [
            'pending' => NotificationEvent::query()->where('status', 'pending')->count(),
            'sent' => NotificationEvent::query()->where('status', 'sent')->count(),
            'failed' => NotificationEvent::query()->where('status', 'failed')->count(),
        ];
    }

    private function deliver(NotificationEvent $event): void
    {
        $notification = $event->notification;
        if (!$notification) {
            return;
        }

        if ($event->channel === 'internal') {
            return;
        }

        if ($event->channel === 'email') {
            Log::info('notification.email.dispatched', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'email' => $notification->user?->email,
                'title' => $notification->title,
            ]);
            return;
        }

        throw new \RuntimeException('Unsupported notification channel');
    }
}
