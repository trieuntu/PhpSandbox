<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sandbox_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('context_type', ['assignment', 'exam', 'free']);
            $table->unsignedBigInteger('context_id')->nullable();
            $table->json('session_data')->nullable();
            $table->json('cookie_data')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unique(['user_id', 'context_type', 'context_id'], 'uq_user_context');
        });
    }
    public function down(): void { Schema::dropIfExists('sandbox_state'); }
};
