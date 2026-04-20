<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemWallet extends Model
{
    protected $fillable = [
        'code',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
        ];
    }
}