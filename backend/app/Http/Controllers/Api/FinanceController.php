<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\LedgerEntry;
use App\Models\Withdrawal;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class FinanceController extends Controller
{
    public function __construct(private readonly WalletService $wallets)
    {
    }

    public function wallet(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $wallet = $this->wallets->ensureUserWallet($user);

        return response()->json([
            'wallet' => [
                'available_balance' => (float) $wallet->available_balance,
                'hold_balance' => (float) $wallet->hold_balance,
                'currency' => 'USD',
            ],
        ]);
    }

    public function ledger(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $wallet = $this->wallets->ensureUserWallet($user);

        $entries = LedgerEntry::query()
            ->where('wallet_id', $wallet->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (LedgerEntry $entry) => [
                'id' => $entry->id,
                'entry_type' => $entry->entry_type,
                'amount' => (float) $entry->amount,
                'balance_after' => (float) $entry->balance_after,
                'meta' => $entry->meta,
                'created_at' => $entry->created_at?->toISOString(),
            ]);

        return response()->json(['entries' => $entries]);
    }

    public function simulateDeposit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'provider' => ['nullable', 'string', 'max:60'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $amount = (float) $request->input('amount');

        $deposit = DB::transaction(function () use ($user, $amount, $request) {
            $deposit = Deposit::query()->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'status' => 'paid',
                'provider' => (string) $request->input('provider', 'yookassa'),
                'provider_ref' => 'sim_' . bin2hex(random_bytes(6)),
                'paid_at' => now(),
            ]);

            $this->wallets->credit($user, $amount, 'deposit_paid', [
                'deposit_id' => $deposit->id,
                'provider' => $deposit->provider,
            ]);

            return $deposit;
        });

        return response()->json([
            'deposit' => [
                'id' => $deposit->id,
                'status' => $deposit->status,
                'amount' => (float) $deposit->amount,
            ],
        ], 201);
    }

    public function createWithdrawal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payout_method' => ['nullable', 'string', 'max:40'],
            'requisites' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $amount = (float) $request->input('amount');

        try {
            $withdrawal = DB::transaction(function () use ($user, $request, $amount) {
                $withdrawal = Withdrawal::query()->create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'status' => 'pending',
                    'payout_method' => (string) $request->input('payout_method', 'card'),
                    'requisites' => (string) $request->input('requisites'),
                ]);

                $this->wallets->hold($user, $amount, 'withdrawal_hold', [
                    'withdrawal_id' => $withdrawal->id,
                ]);

                return $withdrawal;
            });
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'withdrawal' => [
                'id' => $withdrawal->id,
                'status' => $withdrawal->status,
                'amount' => (float) $withdrawal->amount,
            ],
        ], 201);
    }

    public function withdrawals(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $withdrawals = Withdrawal::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (Withdrawal $item) => [
                'id' => $item->id,
                'amount' => (float) $item->amount,
                'status' => $item->status,
                'payout_method' => $item->payout_method,
                'requisites' => $item->requisites,
                'created_at' => $item->created_at?->toISOString(),
            ]);

        return response()->json(['withdrawals' => $withdrawals]);
    }
}