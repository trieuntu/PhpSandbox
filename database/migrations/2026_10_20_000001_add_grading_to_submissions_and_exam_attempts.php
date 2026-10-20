<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('submissions', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->after('memory_used_kb');
            $table->text('teacher_feedback')->nullable()->after('score');
            $table->timestamp('graded_at')->nullable()->after('teacher_feedback');
            $table->foreignId('graded_by')->nullable()->after('graded_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->after('expires_at');
            $table->text('teacher_feedback')->nullable()->after('score');
            $table->timestamp('graded_at')->nullable()->after('teacher_feedback');
            $table->foreignId('graded_by')->nullable()->after('graded_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('submissions', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['graded_by']);
            }
            $table->dropColumn(['score', 'teacher_feedback', 'graded_at', 'graded_by']);
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['graded_by']);
            }
            $table->dropColumn(['score', 'teacher_feedback', 'graded_at', 'graded_by']);
        });
    }
};
