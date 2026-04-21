<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    protected $fillable = [
        'submission_id',
        'task_id',
        'performer_id',
        'advertiser_id',
        'resolved_by_id',
        'status',
        'reason',
        'admin_comment',
        'resolved_at',
        'compensation_applied',
        'compensation_amount',
        'compensation_applied_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'compensation_applied' => 'boolean',
            'compensation_amount' => 'decimal:4',
            'compensation_applied_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performer_id');
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
