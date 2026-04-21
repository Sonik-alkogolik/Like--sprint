<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\SystemWallet;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    public function ensureUserWallet(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['available_balance' => 0, 'hold_balance' => 0],
        );
    }

    public function getOrCreateSystemWallet(string $code): SystemWallet
    {
        return SystemWallet::query()->firstOrCreate(['code' => $code], ['balance' => 0]);
    }

    public function credit(User $user, float $amount, string $entryType, array $meta = []): Wallet
    {
        return DB::transaction(function () use ($user, $amount, $entryType, $meta) {
            $wallet = $this->ensureUserWallet($user);
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $wallet->available_balance = (float) $wallet->available_balance + $amount;
            $wallet->save();

            LedgerEntry::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'entry_type' => $entryType,
                'amount' => $amount,
                'balance_after' => $wallet->available_balance,
                'meta' => $meta,
            ]);

            return $wallet;
        });
    }

    public function debit(User $user, float $amount, string $entryType, array $meta = []): Wallet
    {
        return DB::transaction(function () use ($user, $amount, $entryType, $meta) {
            $wallet = $this->ensureUserWallet($user);
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ((float) $wallet->available_balance < $amount) {
                throw new RuntimeException('Insufficient available balance');
            }

            $wallet->available_balance = (float) $wallet->available_balance - $amount;
            $wallet->save();

            LedgerEntry::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'entry_type' => $entryType,
                'amount' => -$amount,
                'balance_after' => $wallet->available_balance,
                'meta' => $meta,
            ]);

            return $wallet;
        });
    }

    public function hold(User $user, float $amount, string $entryType, array $meta = []): Wallet
    {
        return DB::transaction(function () use ($user, $amount, $entryType, $meta) {
            $wallet = $this->ensureUserWallet($user);
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ((float) $wallet->available_balance < $amount) {
                throw new RuntimeException('Insufficient available balance');
            }

            $wallet->available_balance = (float) $wallet->available_balance - $amount;
            $wallet->hold_balance = (float) $wallet->hold_balance + $amount;
            $wallet->save();

            LedgerEntry::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'entry_type' => $entryType,
                'amount' => -$amount,
                'balance_after' => $wallet->available_balance,
                'meta' => $meta,
            ]);

            return $wallet;
        });
    }

    public function releaseHold(User $user, float $amount, string $entryType, array $meta = []): Wallet
    {
        return DB::transaction(function () use ($user, $amount, $entryType, $meta) {
            $wallet = $this->ensureUserWallet($user);
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ((float) $wallet->hold_balance < $amount) {
                throw new RuntimeException('Insufficient hold balance');
            }

            $wallet->hold_balance = (float) $wallet->hold_balance - $amount;
            $wallet->available_balance = (float) $wallet->available_balance + $amount;
            $wallet->save();

            LedgerEntry::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'entry_type' => $entryType,
                'amount' => $amount,
                'balance_after' => $wallet->available_balance,
                'meta' => $meta,
            ]);

            return $wallet;
        });
    }

    public function spendFromHold(User $user, float $amount, string $entryType, array $meta = []): Wallet
    {
        return DB::transaction(function () use ($user, $amount, $entryType, $meta) {
            $wallet = $this->ensureUserWallet($user);
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ((float) $wallet->hold_balance < $amount) {
                throw new RuntimeException('Insufficient hold balance');
            }

            $wallet->hold_balance = (float) $wallet->hold_balance - $amount;
            $wallet->save();

            LedgerEntry::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'entry_type' => $entryType,
                'amount' => 0,
                'balance_after' => $wallet->available_balance,
                'meta' => $meta,
            ]);

            return $wallet;
        });
    }
}
