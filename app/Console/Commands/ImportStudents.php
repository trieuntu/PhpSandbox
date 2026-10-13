<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\UserCsvImportService;

class ImportStudents extends Command {
    protected $signature = 'students:import {csv_path : Path to CSV file}';
    protected $description = 'Import students from a CSV file';
    
    public function handle(UserCsvImportService $importService): int {
        $path = $this->argument('csv_path');
        
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return Command::FAILURE;
        }

        $this->info("Importing students from: {$path}");
        
        $result = $importService->import($path, function(string $type, string $message) {
            if ($type === 'imported') {
                $this->line("Imported: {$message}");
            } elseif ($type === 'skipped') {
                $this->warn("Skipped: {$message}");
            } elseif ($type === 'error') {
                $this->error("Error: {$message}");
            }
        });
        
        $this->info("Import complete: {$result['imported']} imported, {$result['skipped']} skipped.");
        if (!empty($result['errors'])) {
            $this->warn("Total errors: " . count($result['errors']));
            foreach (array_slice($result['errors'], 0, 10) as $err) {
                $this->warn(" - {$err}");
            }
        }

        return Command::SUCCESS;
    }
}
