<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\SandboxState;

class PurgeExpiredSessions extends Command {
    protected $signature = 'sandbox:purge-sessions {--days=7 : Purge sessions older than this many days}';
    protected $description = 'Purge old sandbox session state records';
    
    public function handle(): int {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        
        $count = SandboxState::where('last_used_at', '<', $cutoff)->delete();
        $this->info("Purged {$count} expired sandbox session records older than {$days} days.");
        return Command::SUCCESS;
    }
}
