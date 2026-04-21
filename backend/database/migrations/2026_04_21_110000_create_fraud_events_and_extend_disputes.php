<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->foreignId('resolved_by_id')->nullable()->after('advertiser_id')->constrained('users')->nullOnDelete();
            $table->text('admin_comment')->nullable()->after('reason');
        });

        Schema::create('fraud_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('submissions')->nullOnDelete();
            $table->string('event_type', 120);
            $table->string('severity', 40)->default('medium');
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['event_type', 'severity', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_events');

        Schema::table('disputes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by_id');
            $table->dropColumn('admin_comment');
        });
    }
};
