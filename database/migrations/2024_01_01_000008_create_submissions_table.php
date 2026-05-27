<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->string('title')->nullable();
            $table->longText('code');
            $table->longText('output_html')->nullable();
            $table->text('output_errors')->nullable();
            $table->enum('execution_status', ['pending', 'running', 'success', 'error', 'timeout'])->default('pending');
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->unsignedInteger('memory_used_kb')->nullable();
            $table->timestamp('submitted_at');
            $table->index(['user_id', 'submitted_at']);
            $table->index('assignment_id');
            $table->index('exam_id');
        });
    }
    public function down(): void { Schema::dropIfExists('submissions'); }
};
