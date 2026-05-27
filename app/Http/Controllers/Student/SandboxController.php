<?php
namespace App\Http\Controllers\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SubmitCodeRequest;
use App\Services\SubmissionService;
use App\Services\SandboxService;
use App\Models\Submission;
use App\Models\Assignment;
use App\Models\Exam;

class SandboxController extends Controller {
    public function editor() {
        return view('student.sandbox.editor');
    }
    
    public function execute(SubmitCodeRequest $request, SubmissionService $service) {
        $user = Auth::user();
        
        $contextType = $request->input('context_type', 'free');
        $contextId   = $request->input('context_id');
        $files       = $request->input('files'); // ['index.php' => '...', 'abc.php' => '...'] or null
        
        $data = [
            'code'  => $request->input('code', ''),
            'title' => $request->input('title'),
            'files' => $files,
        ];
        
        if ($contextType === 'assignment' && $contextId) {
            $assignment = Assignment::find($contextId);
            if (!$assignment || !$assignment->is_active) {
                return response()->json(['error' => 'Bài tập này hiện không được kích hoạt. Không thể nộp bài.'], 403);
            }
            $data['assignment_id'] = $contextId;
        } elseif ($contextType === 'exam' && $contextId) {
            $exam = Exam::find($contextId);
            if (!$exam || !$exam->is_active) {
                return response()->json(['error' => 'Kỳ thi này hiện không hoạt động.'], 403);
            }
            $data['exam_id'] = $contextId;
        }
        
        $submission = $service->createAndDispatch($user, $data);
        
        return response()->json([
            'job_id' => $submission->id,
            'status' => 'queued',
        ]);
    }
    
    public function pollJob(int $submissionId) {
        $user = Auth::user();
        $submission = Submission::where('id', $submissionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'status'           => $submission->execution_status,
            'submission_id'    => $submission->id,
            'execution_time_ms'=> $submission->execution_time_ms,
            'errors'           => $submission->output_errors ?: null,
        ]);
    }
    
    public function preview(int $submissionId) {
        $user = Auth::user();
        $submission = Submission::where('id', $submissionId)
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->role === 'admin') $q->orWhere('id', '>', 0);
            })
            ->firstOrFail();
        
        $html = $this->buildPreviewHtml($submission->output_html ?? '', $submissionId);
        
        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * Re-execute original code with form POST data injected (supports interactive forms).
     */
    public function executeForm(Request $request, int $submissionId, SandboxService $sandboxService) {
        $user = Auth::user();
        $submission = Submission::where('id', $submissionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $postData   = array_map(fn($v) => $v ?? '', $request->except(['_token', '_method', '__sb_target__']));
        $targetFile = (string) ($request->input('__sb_target__') ?? '');
        $files      = $submission->files ?? [];

        $result = $sandboxService->executeWithPost(
            $user,
            $submission->code,
            $postData,
            'free',
            null,
            $files,
            $targetFile
        );

        $html = $this->buildPreviewHtml($result['output'] ?? '', $submissionId);

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }
    
    public function heartbeat(Request $request) {
        $userId = Auth::id();
        $timeoutMinutes = (int)\App\Models\Setting::get('inactivity_timeout_minutes', 15);
        $cacheKey = "last_active_{$userId}";
        
        \Illuminate\Support\Facades\Cache::put($cacheKey, time(), $timeoutMinutes * 60 + 300);
        
        $lastActiveTs = (int) \Illuminate\Support\Facades\Cache::get($cacheKey, time());
        $elapsed = time() - $lastActiveTs;
        $remaining = ($timeoutMinutes * 60) - $elapsed;
        
        return response()->json(['remaining_seconds' => max(0, $remaining)]);
    }

    /**
     * Build a full preview HTML page, injecting form-intercept JS so forms
     * can submit back to the sandbox for re-execution.
     */
    private function buildPreviewHtml(string $output, int $submissionId): string {
        $sid   = (int) $submissionId;
        $csrf  = e(csrf_token());
        $interceptScript = <<<JS
<script>
(function(){
    var SID={$sid}, CSRF='{$csrf}';
    function intercept(){
        document.querySelectorAll('form:not([data-sb-hooked])').forEach(function(f){
            f.dataset.sbHooked='1';
            f.addEventListener('submit',function(e){
                e.preventDefault();
                var fd=new FormData(f), data={};
                fd.forEach(function(v,k){ data[k]=v; });
                // Include the clicked submit button (FormData omits it by default)
                if(e.submitter && e.submitter.name){ data[e.submitter.name]=e.submitter.value; }
                // Detect form action file (e.g., action="abc.php" → target=abc.php)
                var action=(f.getAttribute('action')||'').trim();
                var target='';
                if(action && action!=='/' && !action.startsWith('http')){
                    var last=action.split('/').pop().split('?')[0];
                    if(last.match(/\.php$/i)) target=last;
                }
                data['__sb_target__']=target;
                var xhr=new XMLHttpRequest();
                xhr.open('POST','/api/sandbox/execute-form/'+SID,true);
                xhr.setRequestHeader('Content-Type','application/json');
                xhr.setRequestHeader('X-CSRF-TOKEN',CSRF);
                xhr.withCredentials=true;
                xhr.onload=function(){
                    if(xhr.status===200){ document.open(); document.write(xhr.responseText); document.close(); }
                    else{ alert('Error '+xhr.status+': '+xhr.statusText); }
                };
                xhr.send(JSON.stringify(data));
            });
        });
    }
    if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded',intercept); }
    else{ intercept(); }
})();
</script>
JS;

        $hasHtml = preg_match('/<[a-z][\s\S]*>/i', $output);

        if (!$hasHtml) {
            $safe = htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return "<!DOCTYPE html><html><head><meta charset='utf-8'>"
                . "<style>body{font-family:monospace;font-size:13px;padding:12px;margin:0;white-space:pre-wrap;word-break:break-all;}</style>"
                . "</head><body>{$safe}{$interceptScript}</body></html>";
        }

        if (!preg_match('/^\s*<!DOCTYPE|^\s*<html/i', $output)) {
            return "<!DOCTYPE html><html><head><meta charset='utf-8'>"
                . "<style>body{font-family:sans-serif;font-size:14px;padding:12px;margin:0;}</style>"
                . "</head><body>{$output}{$interceptScript}</body></html>";
        }

        if (stripos($output, '</body>') !== false) {
            return str_ireplace('</body>', $interceptScript . '</body>', $output);
        }
        return $output . $interceptScript;
    }
}
