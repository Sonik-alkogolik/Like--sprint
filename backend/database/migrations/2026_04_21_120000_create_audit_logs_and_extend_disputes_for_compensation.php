<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->boolean('compensation_applied')->default(false)->after('resolved_at');
            $table->decimal('compensation_amount', 18, 4)->default(0)->after('compensation_applied');
            $table->timestamp('compensation_applied_at')->nullable()->after('compensation_amount');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120);
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        Schema::table('disputes', function (Blueprint $table) {
            $table->dropColumn([
                'compensation_applied',
                'compensation_amount',
                'compensation_applied_at',
            ]);
        });
    }
};
