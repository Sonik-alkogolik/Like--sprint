<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'advertiser_id',
        'title',
        'short_description',
        'instruction',
        'start_url',
        'price_per_action',
        'commission_per_action',
        'max_approvals',
        'repeat_mode',
        'repeat_interval_hours',
        'assignment_ttl_minutes',
        'check_deadline_days',
        'verification_mode',
        'status',
        'moderation_status',
        'moderation_comment',
        'reserved_fund',
        'reserved_commission',
        'reserved_total',
        'approved_count',
        'rejected_count',
        'in_progress_count',
    ];

    protected function casts(): array
    {
        return [
            'price_per_action' => 'decimal:4',
            'commission_per_action' => 'decimal:4',
            'reserved_fund' => 'decimal:4',
            'reserved_commission' => 'decimal:4',
            'reserved_total' => 'decimal:4',
        ];
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(TaskRequirement::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(TaskLink::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
