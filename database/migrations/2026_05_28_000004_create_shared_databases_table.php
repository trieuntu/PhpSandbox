<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shared_databases', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();         // sandbox_shared_{slug}
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->enum('permission', ['none', 'readonly', 'readwrite'])->default('readonly');
            $table->json('tables_info')->nullable();      // [{name, rows, size_kb}]
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_databases');
    }
};
