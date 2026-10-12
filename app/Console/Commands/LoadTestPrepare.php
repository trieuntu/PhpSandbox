<?php
namespace App\Console\Commands;

use App\Models\Classes;
use App\Models\ClassEnrollment;
use App\Models\Exam;
use App\Models\User;
use App\Services\DatabaseProvisionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class LoadTestPrepare extends Command {
    protected $signature = 'loadtest:prepare {--count=80 : Number of student accounts} {--provision : Also create each student\'s sandbox database} {--password=LoadTest#2026}';
    protected $description = 'Create throw-away students, a class and an open exam for load testing';

    public function handle(DatabaseProvisionService $dbService): int {
        $count = max(1, (int) $this->option('count'));
        $admin = User::where('role', 'admin')->orderBy('id')->first();
        if (!$admin) {
            $this->error('No admin user found.');
            return self::FAILURE;
        }

        $class = Classes::firstOrCreate(
            ['name' => 'LOADTEST'],
            ['description' => 'Temporary load test class', 'created_by' => $admin->id, 'is_active' => true]
        );

        $passwordHash = Hash::make($this->option('password'));
        for ($i = 1; $i <= $count; $i++) {
            $user = User::firstOrCreate(
                ['email' => sprintf('loadtest%03d@loadtest.local', $i)],
                [
                    'student_id' => sprintf('LT%04d', $i),
                    'last_name' => 'Load Test',
                    'first_name' => sprintf('SV%03d', $i),
                    'password' => $passwordHash,
                    'role' => 'student',
                    'is_active' => true,
                ]
            );
            ClassEnrollment::firstOrCreate(['class_id' => $class->id, 'user_id' => $user->id], ['enrolled_at' => now()]);
            if ($this->option('provision')) {
                try { $dbService->provision($user); } catch (\Throwable $e) { $this->warn("Provision failed for {$user->email}: {$e->getMessage()}"); }
            }
        }

        $exam = Exam::updateOrCreate(
            ['class_id' => $class->id, 'title' => 'LOADTEST Exam'],
            [
                'instructions' => '<p>Load test exam</p>',
                'starts_at' => now()->subMinute(),
                'ends_at' => now()->addHours(3),
                'time_limit_minutes' => null,
                'is_active' => true,
                'created_by' => $admin->id,
            ]
        );

        $this->info("Prepared {$count} students (loadtest001..." . sprintf('%03d', $count) . '@loadtest.local), class #' . $class->id);
        $this->line("EXAM_ID={$exam->id}  COUNT={$count}  PASSWORD={$this->option('password')}");
        return self::SUCCESS;
    }
}
