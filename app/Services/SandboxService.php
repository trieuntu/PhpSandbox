<?php
namespace App\Services;
use App\Models\SharedDatabase;
use App\Models\User;
use App\Models\SandboxState;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SandboxService {
    private string $sandboxHost;
    private bool $localFallback;

    public function __construct() {
        $this->sandboxHost = config('sandbox.host', 'http://sandbox:8080');
        $this->localFallback = (bool) config('sandbox.local_fallback', false);
    }

    public function execute(User $user, string $code, string $contextType, ?int $contextId): array {
        // Get existing sandbox state (session/cookies)
        $state = SandboxState::where('user_id', $user->id)
            ->where('context_type', $contextType)
            ->where('context_id', $contextId)
            ->first();

        $dbPass = $user->sandbox_db_pass ? decrypt($user->sandbox_db_pass) : '';

        $payload = [
            'code' => $code,
            'user_id' => $user->id,
            'context_type' => $contextType,
            'context_id' => $contextId ?? 0,
            'db_name' => $user->sandbox_db_name ?? '',
            'db_user' => $user->sandbox_db_user ?? '',
            'db_pass' => $dbPass,
            'session_data' => $state?->session_data ?? [],
            'cookie_data' => $state?->cookie_data ?? [],
            'max_execution_time' => config('sandbox.max_execution_time', 5),
            'shared_dbs' => $this->getSharedDbCredentials(),
        ];

        try {
            $response = Http::timeout(config('sandbox.max_execution_time', 5) + 5)
                ->post($this->sandboxHost, $payload);

            if (!$response->successful()) {
                if ($this->localFallback) {
                    return $this->executeLocally($user, $code, $contextType, $contextId);
                }
                return [
                    'status' => 'error',
                    'output' => '',
                    'errors' => 'Sandbox service error: ' . $response->status(),
                    'execution_time_ms' => 0,
                    'memory_kb' => 0,
                    'cookies' => [],
                    'session' => [],
                ];
            }

            $result = $response->json();
            $this->updateState($user, $contextType, $contextId, $result);
            return $result;

        } catch (\Exception $e) {
            Log::warning("SandboxService remote failed: " . $e->getMessage());
            if ($this->localFallback) {
                return $this->executeLocally($user, $code, $contextType, $contextId);
            }
            Log::error("SandboxService error: " . $e->getMessage());
            return [
                'status' => 'error',
                'output' => '',
                'errors' => 'Failed to reach sandbox service: ' . $e->getMessage(),
                'execution_time_ms' => 0,
                'memory_kb' => 0,
                'cookies' => [],
                'session' => [],
            ];
        }
    }

    /**
     * Local fallback: run PHP code via proc_open in a restricted env (dev only).
     */
    /**
     * Public method for re-executing code with POST data (form submit simulation).
     * Supports multi-file: pass $files as ['index.php'=>'...','abc.php'=>'...']
     * and $targetFile as the file to execute (e.g., 'abc.php').
     */
    public function executeWithPost(User $user, string $code, array $postData, string $contextType = 'free', ?int $contextId = null, array $files = [], string $targetFile = ''): array {
        return $this->executeLocally($user, $code, $contextType, $contextId, $postData, $files, $targetFile);
    }

    private function buildInitLine(array $postData): string {
        $method  = empty($postData) ? 'GET' : 'POST';
        $encoded = base64_encode(serialize($postData));
        return '<?php'
            . ' $_SERVER["PHP_SELF"]="/";'
            . '$_SERVER["SCRIPT_NAME"]="/";'
            . '$_SERVER["REQUEST_METHOD"]="' . $method . '";'
            . '$_SERVER["HTTP_HOST"]="localhost";'
            . '$_POST=unserialize(base64_decode("' . $encoded . '"));'
            . '$_GET=[];'
            . '$_REQUEST=$_POST;'
            . ' ?>' . "\n";
    }

    /**
     * Generate PHP code that populates $sharedDbs (PDO) and $sharedConns (mysqli)
     * for local-fallback execution.
     */
    private function buildSharedDbsBootstrap(): string
    {
        $dbs     = $this->getSharedDbCredentials();
        $dbHost  = config('sandbox.db_host', env('DB_HOST', '127.0.0.1'));
        $dbsExported = var_export($dbs, true);

        return '<?php' . "\n"
            . '$sharedDbs   = [];' . "\n"
            . '$sharedConns = [];' . "\n"
            . '$__sdb_host  = ' . var_export($dbHost, true) . ';' . "\n"
            . 'foreach (' . $dbsExported . ' as $__sdb) {' . "\n"
            . '    $__sdb_name = "sandbox_shared_{$__sdb[\'slug\']}";' . "\n"
            . '    try {' . "\n"
            . '        $__sdb_pdo = new PDO("mysql:host={$__sdb_host};port=3306;dbname={$__sdb_name};charset=utf8mb4", $__sdb[\'user\'], $__sdb[\'pass\']);' . "\n"
            . '        $__sdb_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);' . "\n"
            . '        $__sdb_pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);' . "\n"
            . '        $sharedDbs[$__sdb[\'slug\']] = $__sdb_pdo;' . "\n"
            . '    } catch (PDOException $e) {}' . "\n"
            . '    if (function_exists(\'mysqli_connect\')) {' . "\n"
            . '        $__sdb_conn = @mysqli_connect($__sdb_host, $__sdb[\'user\'], $__sdb[\'pass\'], $__sdb_name, 3306);' . "\n"
            . '        if ($__sdb_conn) { mysqli_set_charset($__sdb_conn, \'utf8mb4\'); $sharedConns[$__sdb[\'slug\']] = $__sdb_conn; }' . "\n"
            . '    }' . "\n"
            . '}' . "\n"
            . 'unset($__sdb, $__sdb_pdo, $__sdb_conn, $__sdb_host, $__sdb_name);' . "\n"
            . '?>' . "\n";
    }

    private function executeLocally(User $user, string $code, string $contextType, ?int $contextId, array $postData = [], array $files = [], string $targetFile = ''): array {
        $memLimit    = config('sandbox.memory_limit_mb', 64) . 'M';
        $maxTime     = (int) config('sandbox.max_execution_time', 5);
        // PHP_BINARY may point to php-fpm in production containers; use php CLI instead
        $php         = is_executable('/usr/local/bin/php') ? '/usr/local/bin/php' : PHP_BINARY;
        $disabledFns = implode(',', config('sandbox.disabled_functions', []));
        $initLine    = $this->buildInitLine($postData);
        $sharedDbsBootstrap = $this->buildSharedDbsBootstrap();
        $preamble    = $initLine . $sharedDbsBootstrap;
        $tmpDir      = null;

        if (!empty($files)) {
            // Multi-file mode: write all files to a temp directory
            $tmpDir = sys_get_temp_dir() . '/phpsandbox_' . uniqid() . '/';
            mkdir($tmpDir, 0700, true);

            foreach ($files as $filename => $content) {
                // Sanitize filename — no path traversal
                $safeName = basename($filename);
                if ($safeName !== '') {
                    file_put_contents($tmpDir . $safeName, $content);
                }
            }

            // Determine which file to execute
            if ($targetFile && isset($files[basename($targetFile)])) {
                $entryName = basename($targetFile);
            } elseif (isset($files['index.php'])) {
                $entryName = 'index.php';
            } else {
                $entryName = basename((string) array_key_first($files));
            }

            // Write a runner that prepends init then includes the entry file
            $runnerFile = $tmpDir . '__sb_run__.php';
            file_put_contents($runnerFile, $preamble . file_get_contents($tmpDir . $entryName));

            $cmd = sprintf(
                'cd %s && %s -d memory_limit=%s -d max_execution_time=%d -d disable_functions=%s %s',
                escapeshellarg($tmpDir),
                escapeshellarg($php),
                escapeshellarg($memLimit),
                $maxTime,
                escapeshellarg($disabledFns),
                escapeshellarg($runnerFile)
            );
        } else {
            // Single-file mode (backward compat)
            $tmpFile = tempnam(sys_get_temp_dir(), 'phpsandbox_') . '.php';
            file_put_contents($tmpFile, $preamble . $code);

            $cmd = sprintf(
                '%s -d memory_limit=%s -d max_execution_time=%d -d disable_functions=%s %s',
                escapeshellarg($php),
                escapeshellarg($memLimit),
                $maxTime,
                escapeshellarg($disabledFns),
                escapeshellarg($tmpFile)
            );
        }

        $startTime = microtime(true);
        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        $output = '';
        $errors = '';
        $status = 'success';

        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            if (proc_close($process) !== 0) $status = 'error';
        } else {
            $status = 'error';
            $errors = 'Failed to start PHP process.';
        }

        // Cleanup temp files
        if ($tmpDir) {
            array_map('unlink', glob($tmpDir . '*'));
            @rmdir($tmpDir);
        } else {
            @unlink($tmpFile ?? '');
        }

        $executionTimeMs = (int) round((microtime(true) - $startTime) * 1000);

        $result = [
            'status'            => $status,
            'output'            => $output,
            'errors'            => $errors,
            'execution_time_ms' => $executionTimeMs,
            'memory_kb'         => 0,
            'cookies'           => [],
            'session'           => [],
        ];

        $this->updateState($user, $contextType, $contextId, $result);
        return $result;
    }

    private function updateState(User $user, string $contextType, ?int $contextId, array $result): void {
        SandboxState::updateOrCreate(
            ['user_id' => $user->id, 'context_type' => $contextType, 'context_id' => $contextId],
            [
                'session_data' => $result['session'] ?? [],
                'cookie_data' => $result['cookies'] ?? [],
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Build the shared_dbs credentials array for the execution payload.
     * Only includes DBs that are not set to 'none' permission.
     */
    private function getSharedDbCredentials(): array
    {
        try {
            return SharedDatabase::where('permission', '!=', 'none')
                ->get()
                ->map(fn($sdb) => [
                    'slug' => $sdb->slug,
                    'user' => $sdb->permission === 'readwrite'
                        ? config('sandbox.shared_rw_user', 'sbx_shared_rw')
                        : config('sandbox.shared_ro_user', 'sbx_shared_ro'),
                    'pass' => $sdb->permission === 'readwrite'
                        ? config('sandbox.shared_rw_pass')
                        : config('sandbox.shared_ro_pass'),
                ])
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
