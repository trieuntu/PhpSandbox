<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\DatabaseProvisionService;

class ImportStudents extends Command {
    protected $signature = 'students:import {csv_path : Path to CSV file}';
    protected $description = 'Import students from a CSV file';
    
    public function handle(DatabaseProvisionService $dbService): int {
        $path = $this->argument('csv_path');
        
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return Command::FAILURE;
        }
        
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        
        $imported = 0;
        $skipped = 0;
        
        while ($row = fgetcsv($handle)) {
            $data = array_combine($headers, $row);
            
            try {
                $nameFields = isset($data['first_name']) || isset($data['last_name'])
                    ? ['last_name' => trim($data['last_name'] ?? ''), 'first_name' => trim($data['first_name'] ?? '')]
                    : ['name' => $data['name']];
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    $nameFields + [
                        'student_id' => $data['student_id'] ?? null,
                        'password' => Hash::make($data['password'] ?? 'password123'),
                        'role' => 'student',
                        'is_active' => true,
                    ]
                );
                
                if ($user->wasRecentlyCreated) {
                    $dbService->provision($user);
                    $imported++;
                    $this->line("Imported: {$user->email}");
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->warn("Skipped row (error: {$e->getMessage()})");
                $skipped++;
            }
        }
        
        fclose($handle);
        $this->info("Import complete: {$imported} imported, {$skipped} skipped.");
        return Command::SUCCESS;
    }
}
