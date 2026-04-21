<?php

namespace App\Services;

use App\Models\BlacklistEntry;

class BlacklistService
{
    public function normalize(string $entryType, string $entryValue): string
    {
        $value = trim($entryValue);
        return match ($entryType) {
            'email' => mb_strtolower($value),
            'ip' => $value,
            default => $value,
        };
    }

    public function findActiveMatch(string $entryType, string $entryValue): ?BlacklistEntry
    {
        $normalized = $this->normalize($entryType, $entryValue);

        return BlacklistEntry::query()
            ->where('entry_type', $entryType)
            ->where('entry_value', $normalized)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();
    }
}
