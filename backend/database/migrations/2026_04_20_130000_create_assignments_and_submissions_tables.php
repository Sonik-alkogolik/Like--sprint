<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('performer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 40)->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('deadline_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['task_id', 'performer_id', 'status']);
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('performer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 40)->default('pending');
            $table->longText('report_text')->nullable();
            $table->text('rework_comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_deadline_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['task_id', 'status']);
            $table->index(['performer_id', 'status']);
        });

        Schema::create('submission_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->string('attachment_type', 40)->default('file');
            $table->string('file_url', 1200);
            $table->timestamps();
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('performer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('advertiser_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 40)->default('open');
            $table->text('reason');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('submission_attachments');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('assignments');
    }
};