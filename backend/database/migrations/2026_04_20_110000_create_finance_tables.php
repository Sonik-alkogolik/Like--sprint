<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('available_balance', 18, 4)->default(0);
            $table->decimal('hold_balance', 18, 4)->default(0);
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('system_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->decimal('balance', 18, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entry_type', 80);
            $table->decimal('amount', 18, 4);
            $table->decimal('balance_after', 18, 4);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['wallet_id', 'created_at']);
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 4);
            $table->string('status', 40)->default('pending');
            $table->string('provider', 60)->default('yookassa');
            $table->string('provider_ref', 120)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 4);
            $table->string('status', 40)->default('pending');
            $table->string('payout_method', 40)->default('card');
            $table->string('requisites', 255);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('system_wallets');
        Schema::dropIfExists('wallets');
    }
};