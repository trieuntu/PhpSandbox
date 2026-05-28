<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('is_active');
            $table->index('last_active_at');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('assignments', function (Blueprint $table) {
            // class_id FK already exists but may not have an index in all engines
            $table->index(['class_id', 'is_active']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->index(['class_id', 'is_active']);
            // Speed up "open exams" query: is_active + date range
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->index(['class_id', 'is_pinned', 'created_at']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->index('execution_status');
            // Speed up per-user assignment submissions lookup
            $table->index(['user_id', 'assignment_id']);
            $table->index(['user_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['last_active_at']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['class_id', 'is_active']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex(['class_id', 'is_active']);
            $table->dropIndex(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['class_id', 'is_pinned', 'created_at']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['execution_status']);
            $table->dropIndex(['user_id', 'assignment_id']);
            $table->dropIndex(['user_id', 'exam_id']);
        });
    }
};
