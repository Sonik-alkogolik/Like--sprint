<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 120);
            $table->string('title', 255);
            $table->text('message');
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_event_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('user_notifications')->cascadeOnDelete();
            $table->string('channel', 40)->default('internal');
            $table->string('status', 40)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_events');
        Schema::dropIfExists('user_notifications');
    }
};
