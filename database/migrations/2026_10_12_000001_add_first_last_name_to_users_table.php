<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('student_id');
            $table->string('first_name')->nullable()->after('last_name');
        });

        // Vietnamese convention: the last word is the given name, the rest is surname + middle name.
        DB::table('users')->orderBy('id')->each(function ($user) {
            $full = trim(preg_replace('/\s+/u', ' ', (string) $user->name));
            $pos = mb_strrpos($full, ' ');
            DB::table('users')->where('id', $user->id)->update([
                'last_name'  => $pos === false ? '' : mb_substr($full, 0, $pos),
                'first_name' => $pos === false ? $full : mb_substr($full, $pos + 1),
            ]);
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'first_name']);
        });
    }
};
