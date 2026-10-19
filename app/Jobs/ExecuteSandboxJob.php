<?php
namespace App\Jobs;
use App\Models\Submission;
use App\Services\SandboxService;
use App\Services\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteSandboxJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $timeout = 60;
    public int $tries = 1;
    
    public function __construct(public Submission $submission) {}
    
    public function handle(SandboxService $sandboxService): void {
        $submission = $this->submission;
        $user = $submission->user;
        
        // Determine context
        $contextType = 'free';
        $contextId = null;
        if ($submission->assignment_id) {
            $contextType = 'assignment';
            $contextId = $submission->assignment_id;
        } elseif ($submission->exam_id) {
            $contextType = 'exam';
            $contextId = $submission->exam_id;
        }
        
        // Update status to running
        $submission->update(['execution_status' => 'running']);
        
        try {
            $files = $submission->files ?? [];
            $result = $files
                ? $sandboxService->executeWithPost($user, $submission->code, [], $contextType, $contextId, $files)
                : $sandboxService->execute($user, $submission->code, $contextType, $contextId);
            
            $submission->update([
                'output_html' => $result['output'] ?? '',
                'output_errors' => $result['errors'] ?? '',
                'execution_status' => $result['status'] ?? 'error',
                'execution_time_ms' => $result['execution_time_ms'] ?? 0,
                'memory_used_kb' => $result['memory_kb'] ?? 0,
            ]);
            
            ActivityLogger::log('code_submitted', "Submission #{$submission->id} executed", [
                'submission_id' => $submission->id,
                'status' => $result['status'],
                'execution_time_ms' => $result['execution_time_ms'],
            ], $user->id);
        } catch (\Exception $e) {
            Log::error("ExecuteSandboxJob failed for submission #{$submission->id}: " . $e->getMessage());
            $submission->update([
                'execution_status' => 'error',
                'output_errors' => 'Internal error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure when queue worker timeout or unexpected termination occurs.
     */
    public function failed(?\Throwable $exception): void {
        Log::error("ExecuteSandboxJob permanently failed for submission #{$this->submission->id}: " . ($exception?->getMessage() ?? 'Unknown queue failure'));
        try {
            $this->submission->update([
                'execution_status' => 'timeout',
                'output_errors' => 'Tác vụ đã bị ngắt do quá thời gian chờ trong hàng đợi (' . ($exception?->getMessage() ?? 'Timeout') . ').',
            ]);
        } catch (\Exception $e) {}
    }
}
