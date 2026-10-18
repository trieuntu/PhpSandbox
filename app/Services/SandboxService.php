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
            if (!empty($result['errors'])) {
                $result['errors'] = $this->sanitizeOutputErrors($result['errors']);
            }
            if (!empty($result['output'])) {
                $result['output'] = $this->sanitizeOutputErrors($result['output']);
            }
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
     * Public method for re-executing code with POST/GET data (form submit simulation).
     * Supports multi-file: pass $files as ['index.php'=>'...','abc.php'=>'...']
     * and $targetFile as the file to execute (e.g., 'abc.php').
     */
    public function executeWithPost(User $user, string $code, array $postData, string $contextType = 'free', ?int $contextId = null, array $files = [], string $targetFile = '', string $httpMethod = 'POST'): array {
        return $this->executeLocally($user, $code, $contextType, $contextId, $postData, $files, $targetFile, $httpMethod);
    }

    private function buildInitLine(array $formData, string $httpMethod = 'POST'): string {
        $method  = in_array(strtoupper($httpMethod), ['GET', 'POST', 'PUT', 'DELETE']) ? strtoupper($httpMethod) : 'POST';
        $encoded = base64_encode(serialize($formData));

        if ($method === 'GET') {
            return '<?php'
                . ' $_SERVER["PHP_SELF"]="/";'
                . '$_SERVER["SCRIPT_NAME"]="/";'
                . '$_SERVER["REQUEST_METHOD"]="GET";'
                . '$_SERVER["HTTP_HOST"]="localhost";'
                . '$_GET=unserialize(base64_decode("' . $encoded . '"));'
                . '$_POST=[];'
                . '$_REQUEST=$_GET;'
                . ' ?>' . "\n";
        }

        return '<?php'
            . ' $_SERVER["PHP_SELF"]="/";'
            . '$_SERVER["SCRIPT_NAME"]="/";'
            . '$_SERVER["REQUEST_METHOD"]="POST";'
            . '$_SERVER["HTTP_HOST"]="localhost";'
            . '$_POST=unserialize(base64_decode("' . $encoded . '"));'
            . '$_GET=[];'
            . '$_REQUEST=$_POST;'
            . ' ?>' . "\n";
    }

    /**
     * Generate PHP code that initializes the student's personal sandbox database connection.
     */
    private function buildStudentDbBootstrap(User $user): string
    {
        if (!$user->sandbox_db_name || !$user->sandbox_db_user) {
            return '';
        }
        $dbHost = config('sandbox.db_host', env('DB_HOST', '127.0.0.1'));
        $dbName = $user->sandbox_db_name;
        $dbUser = $user->sandbox_db_user;
        $dbPass = $user->sandbox_db_pass ? decrypt($user->sandbox_db_pass) : '';

        $exportedHost = var_export($dbHost, true);
        $exportedName = var_export($dbName, true);
        $exportedUser = var_export($dbUser, true);
        $exportedPass = var_export($dbPass, true);

        return '<?php' . "\n"
            . 'try {' . "\n"
            . '    $db = new PDO("mysql:host=" . ' . $exportedHost . ' . ";port=3306;dbname=" . ' . $exportedName . ' . ";charset=utf8mb4", ' . $exportedUser . ', ' . $exportedPass . ');' . "\n"
            . '    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);' . "\n"
            . '    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);' . "\n"
            . '} catch (PDOException $e) {}' . "\n"
            . 'if (function_exists(\'mysqli_connect\')) {' . "\n"
            . '    $conn = @mysqli_connect(' . $exportedHost . ', ' . $exportedUser . ', ' . $exportedPass . ', ' . $exportedName . ', 3306);' . "\n"
            . '    if ($conn) { mysqli_set_charset($conn, \'utf8mb4\'); }' . "\n"
            . '}' . "\n"
            . '?>' . "\n";
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

    private function executeLocally(User $user, string $code, string $contextType, ?int $contextId, array $postData = [], array $files = [], string $targetFile = '', string $httpMethod = 'POST'): array {
        $memLimit    = config('sandbox.memory_limit_mb', 64) . 'M';
        $maxTime     = (int) config('sandbox.max_execution_time', 5);
        // PHP_BINARY may point to php-fpm in production containers; use php CLI instead
        $php         = is_executable('/usr/local/bin/php') ? '/usr/local/bin/php' : PHP_BINARY;
        $disabledFns = implode(',', config('sandbox.disabled_functions', []));
        $initLine    = $this->buildInitLine($postData, $httpMethod);
        $sharedDbsBootstrap = $this->buildSharedDbsBootstrap();
        $studentDbBootstrap = $this->buildStudentDbBootstrap($user);
        $preamble    = $initLine . $sharedDbsBootstrap . $studentDbBootstrap;

        $tmpDir = sys_get_temp_dir() . '/phpsandbox_' . uniqid() . '/';
        mkdir($tmpDir, 0700, true);

        if (!empty($files)) {
            foreach ($files as $filename => $content) {
                // Sanitize filename — no path traversal and prevent overwriting internal boot script
                $safeName = basename($filename);
                if ($safeName !== '' && !str_starts_with($safeName, '__sb_')) {
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
        } else {
            $entryName = 'index.php';
            file_put_contents($tmpDir . $entryName, $code);
        }

        // Write bootstrap file loaded via auto_prepend_file so line numbers in student code match original source
        $bootFile = $tmpDir . '__sb_boot__.php';
        file_put_contents($bootFile, $preamble);

        $cmd = sprintf(
            'cd %s && %s -d memory_limit=%s -d max_execution_time=%d -d disable_functions=%s -d display_errors=stderr -d auto_prepend_file=%s %s',
            escapeshellarg($tmpDir),
            escapeshellarg($php),
            escapeshellarg($memLimit),
            $maxTime,
            escapeshellarg($disabledFns),
            escapeshellarg($bootFile),
            escapeshellarg($entryName)
        );

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

        // Clean error messages: strip temporary directory paths and internal bootstrap references
        $errors = $this->sanitizeOutputErrors($errors, $tmpDir);
        $output = $this->sanitizeOutputErrors($output, $tmpDir);

        // Cleanup temp files
        if ($tmpDir) {
            array_map('unlink', glob($tmpDir . '*'));
            @rmdir($tmpDir);
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

    /**
     * Sanitize output/errors to remove internal temporary directory paths and internal bootstrap wrappers.
     */
    private function sanitizeOutputErrors(string $text, string $tmpDir = ''): string
    {
        if (trim($text) === '') {
            return $text;
        }

        // If specific tmpDir provided, strip it and its realpath
        if ($tmpDir !== '') {
            $realTmpDir = realpath($tmpDir) ?: $tmpDir;
            $tmpDirNorm = rtrim($tmpDir, '/') . '/';
            $realTmpDirNorm = rtrim($realTmpDir, '/') . '/';
            $text = str_replace([$realTmpDirNorm, $tmpDirNorm], '', $text);
        }

        // Strip any remaining /tmp/phpsandbox_xxx/ or /private/var/folders/.../phpsandbox_xxx/ paths
        $text = preg_replace('#/(?:private/var/folders/[^\s/]+/[^\s/]+/[^\s/]+/|tmp/)?phpsandbox_[a-zA-Z0-9_]+/#', '', $text);
        $text = preg_replace('#/tmp/sandbox_exec/[^/]+/[^/]+/#', '', $text);

        // Strip internal bootstrap/runner filenames if referenced in traces or warnings
        $lines = explode("\n", $text);
        $filtered = [];
        foreach ($lines as $line) {
            if (str_contains($line, '__sb_boot__.php') || str_contains($line, '__sb_run__.php') || str_contains($line, '__sb_foot__.php')) {
                continue;
            }
            $filtered[] = $line;
        }

        return trim(implode("\n", $filtered));
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
