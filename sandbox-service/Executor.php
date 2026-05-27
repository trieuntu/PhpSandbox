<?php
require_once __DIR__ . '/validator.php';

class Executor
{
    private array $input;
    private int   $maxExecutionTime = 5;

    public function __construct(array $input)
    {
        $this->input            = $input;
        $this->maxExecutionTime = (int) ($input['max_execution_time'] ?? 5);
    }

    public function run(): array
    {
        // Static analysis before execution
        $validation = validateCode($this->input['code'] ?? '');
        if ($validation['blocked']) {
            return [
                'status'           => 'error',
                'output'           => '',
                'errors'           => $validation['message'],
                'execution_time_ms' => 0,
                'memory_kb'        => 0,
                'cookies'          => [],
                'session'          => [],
            ];
        }

        $userId      = preg_replace('/[^a-zA-Z0-9_]/', '', $this->input['user_id']      ?? 'unknown');
        $contextType = preg_replace('/[^a-zA-Z0-9_]/', '', $this->input['context_type'] ?? 'free');
        $contextId   = preg_replace('/[^a-zA-Z0-9_]/', '', $this->input['context_id']   ?? '0');

        $execDir = "/tmp/sandbox_exec/{$userId}/{$contextType}_{$contextId}";
        if (!is_dir($execDir)) {
            mkdir($execDir, 0700, true);
        }

        // Build and write the full PHP execution file
        $fullCode = $this->buildCode($execDir);
        $runFile  = $execDir . '/run.php';
        file_put_contents($runFile, $fullCode);
        chmod($runFile, 0600);

        // Execute with a hard timeout watchdog
        $startTime       = microtime(true);
        $result          = $this->executeWithTimeout($runFile, $execDir);
        $executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        // The bootstrap wrapper emits structured JSON on stdout
        $parsed = @json_decode($result['stdout'], true);
        if ($parsed && isset($parsed['__sandbox_output'])) {
            return [
                'status'           => $result['timeout'] ? 'timeout' : ($result['exit_code'] === 0 ? 'success' : 'error'),
                'output'           => $parsed['__sandbox_output']  ?? '',
                'errors'           => $parsed['__sandbox_errors']  ?? $result['stderr'],
                'execution_time_ms' => $executionTimeMs,
                'memory_kb'        => $parsed['__sandbox_memory_kb'] ?? 0,
                'cookies'          => $parsed['__sandbox_cookies']  ?? [],
                'session'          => $parsed['__sandbox_session']  ?? [],
            ];
        }

        return [
            'status'           => $result['timeout'] ? 'timeout' : ($result['exit_code'] === 0 ? 'success' : 'error'),
            'output'           => $result['stdout'],
            'errors'           => $result['stderr'],
            'execution_time_ms' => $executionTimeMs,
            'memory_kb'        => 0,
            'cookies'          => [],
            'session'          => [],
        ];
    }

    private function buildCode(string $execDir): string
    {
        $bootstrap  = file_get_contents(__DIR__ . '/bootstrap.php.template');
        $cookieJson = json_encode($this->input['cookie_data'] ?? []);

        $bootstrap = str_replace(
            [
                '__USER_ID__',
                '__CONTEXT__',
                '__COOKIE_JSON__',
                '__STUDENT_DB__',
                '__STUDENT_DB_USER__',
                '__STUDENT_DB_PASS__',
                '__SESSION_DIR__',
            ],
            [
                $this->input['user_id']      ?? 'unknown',
                ($this->input['context_type'] ?? 'free') . '_' . ($this->input['context_id'] ?? '0'),
                addslashes($cookieJson),
                $this->input['db_name']  ?? '',
                $this->input['db_user']  ?? '',
                $this->input['db_pass']  ?? '',
                $execDir,
            ],
            $bootstrap
        );

        return $bootstrap . "\n" . ($this->input['code'] ?? '') . "\n" . $this->buildFooter();
    }

    private function buildFooter(): string
    {
        return <<<'PHP'

// === SANDBOX FOOTER ===
$__sandbox_output = ob_get_clean();
$__sandbox_cookies = [];
foreach (headers_list() as $header) {
    if (stripos($header, 'Set-Cookie:') === 0) {
        $__sandbox_cookies[] = substr($header, 12);
    }
}
$__sandbox_session = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    $__sandbox_session = $_SESSION ?? [];
    session_write_close();
}
$__sandbox_memory_kb = (int)(memory_get_peak_usage(true) / 1024);
echo json_encode([
    '__sandbox_output'    => $__sandbox_output,
    '__sandbox_errors'    => '',
    '__sandbox_cookies'   => $__sandbox_cookies,
    '__sandbox_session'   => $__sandbox_session,
    '__sandbox_memory_kb' => $__sandbox_memory_kb,
]);
PHP;
    }

    private function executeWithTimeout(string $runFile, string $execDir): array
    {
        // The executor process itself is NOT restricted by open_basedir so it can use proc_open.
        // The child PHP process runs under the restrictive php-sandbox.ini installed in the container.
        $sandboxIni = '/usr/local/etc/php/conf.d/php-sandbox.ini';
        $phpIniArg  = file_exists($sandboxIni) ? "-c {$sandboxIni}" : '';

        $cmd = "php {$phpIniArg} " . escapeshellarg($runFile);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $execDir);

        if (!is_resource($process)) {
            return ['stdout' => '', 'stderr' => 'Failed to start process', 'exit_code' => 1, 'timeout' => false];
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout    = '';
        $stderr    = '';
        $timedOut  = false;
        $deadline  = microtime(true) + $this->maxExecutionTime + 1;

        while (true) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                $stdout .= stream_get_contents($pipes[1]);
                $stderr .= stream_get_contents($pipes[2]);
                break;
            }
            if (microtime(true) > $deadline) {
                proc_terminate($process, 9);
                $timedOut = true;
                break;
            }
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            usleep(50000); // poll every 50 ms
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'stdout'    => $stdout,
            'stderr'    => $stderr,
            'exit_code' => $exitCode,
            'timeout'   => $timedOut,
        ];
    }
}
