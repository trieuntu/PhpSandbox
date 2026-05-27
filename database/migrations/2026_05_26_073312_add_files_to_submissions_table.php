<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('submissions', function (Blueprint $table) {
            // Multi-file projects: {"index.php": "<?php...", "abc.php": "<?php..."}
            // Null = single-file mode (backward compat, uses `code` column)
            $table->json('files')->nullable()->after('code');
        });
    }

    public function down(): void {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('files');
        });
    }
};
