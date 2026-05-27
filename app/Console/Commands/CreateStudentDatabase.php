<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\User;
use App\Services\DatabaseProvisionService;

class CreateStudentDatabase extends Command {
    protected $signature = 'sandbox:provision {user_id : The user ID to provision}';
    protected $description = 'Create sandbox database and MySQL user for a student';
    
    public function handle(DatabaseProvisionService $service): int {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User #{$userId} not found.");
            return Command::FAILURE;
        }
        
        if ($user->sandbox_db_name) {
            $this->warn("User #{$userId} already has sandbox DB: {$user->sandbox_db_name}");
            return Command::SUCCESS;
        }
        
        try {
            $service->provision($user);
            $this->info("Provisioned sandbox DB for user #{$userId}: {$user->fresh()->sandbox_db_name}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
