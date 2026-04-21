<?php

namespace App\Services;

use App\Models\FraudEvent;

class FraudEventService
{
    public function log(
        string $eventType,
        ?int $userId = null,
        ?int $taskId = null,
        ?int $submissionId = null,
        string $severity = 'medium',
        ?string $message = null,
        array $payload = [],
    ): FraudEvent {
        return FraudEvent::query()->create([
            'user_id' => $userId,
            'task_id' => $taskId,
            'submission_id' => $submissionId,
            'event_type' => $eventType,
            'severity' => $severity,
            'message' => $message,
            'payload' => $payload,
        ]);
    }
}
