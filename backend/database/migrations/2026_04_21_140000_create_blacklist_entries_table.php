<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blacklist_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_type', 40); // email, ip
            $table->string('entry_value', 255);
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deactivated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
            $table->index(['entry_type', 'entry_value', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_entries');
    }
};
