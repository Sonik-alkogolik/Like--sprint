<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertiser_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 140);
            $table->text('short_description')->nullable();
            $table->longText('instruction');
            $table->string('start_url')->nullable();
            $table->decimal('price_per_action', 18, 4);
            $table->decimal('commission_per_action', 18, 4)->default(0.0100);
            $table->unsignedInteger('max_approvals');
            $table->string('repeat_mode', 40)->default('one_time');
            $table->unsignedInteger('repeat_interval_hours')->nullable();
            $table->unsignedInteger('assignment_ttl_minutes')->default(60);
            $table->unsignedTinyInteger('check_deadline_days')->default(3);
            $table->string('verification_mode', 40)->default('manual');
            $table->string('status', 40)->default('draft');
            $table->string('moderation_status', 40)->default('draft');
            $table->text('moderation_comment')->nullable();
            $table->decimal('reserved_fund', 18, 4)->default(0);
            $table->decimal('reserved_commission', 18, 4)->default(0);
            $table->decimal('reserved_total', 18, 4)->default(0);
            $table->unsignedInteger('approved_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);
            $table->unsignedInteger('in_progress_count')->default(0);
            $table->timestamps();
            $table->index(['status', 'moderation_status']);
        });

        Schema::create('task_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('kind', 40);
            $table->string('label', 255)->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('task_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('url', 1000);
            $table->string('label', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_links');
        Schema::dropIfExists('task_requirements');
        Schema::dropIfExists('tasks');
    }
};