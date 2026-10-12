<?php
namespace App\Console\Commands;

use App\Models\Classes;
use App\Models\User;
use App\Services\DatabaseProvisionService;
use Illuminate\Console\Command;

class LoadTestCleanup extends Command {
    protected $signature = 'loadtest:cleanup';
    protected $description = 'Remove the accounts, class and exam created by loadtest:prepare';

    public function handle(DatabaseProvisionService $dbService): int {
        $users = User::where('email', 'like', 'loadtest%@loadtest.local')->get();
        foreach ($users as $user) {
            try { $dbService->deprovision($user); } catch (\Throwable $e) { $this->warn("Deprovision failed for {$user->email}: {$e->getMessage()}"); }
            $user->delete();
        }
        $classes = Classes::where('name', 'LOADTEST')->delete();

        $this->info("Removed {$users->count()} users and {$classes} class(es).");
        return self::SUCCESS;
    }
}
