<?php
namespace App\Services;
use App\Models\Submission;
use App\Models\User;
use App\Jobs\ExecuteSandboxJob;

class SubmissionService {
    public function createAndDispatch(User $user, array $data): Submission {
        $files = $data['files'] ?? null; // ['index.php' => '<?php...', 'abc.php' => '...']

        // Derive code from entry file if multi-file mode
        if ($files) {
            $entryKeys = array_keys($files);
            $entry = in_array('index.php', $entryKeys) ? 'index.php' : $entryKeys[0];
            $code = $files[$entry];
        } else {
            $code = $data['code'] ?? '';
        }

        $submission = Submission::create([
            'user_id'          => $user->id,
            'assignment_id'    => $data['assignment_id'] ?? null,
            'exam_id'          => $data['exam_id'] ?? null,
            'title'            => $data['title'] ?? null,
            'code'             => $code,
            'files'            => $files,
            'execution_status' => 'pending',
            'submitted_at'     => now(),
        ]);
        
        ExecuteSandboxJob::dispatch($submission);
        
        return $submission;
    }
}
