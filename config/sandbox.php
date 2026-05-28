<?php
return [
    'host'               => env('SANDBOX_HOST', 'http://sandbox:8080'),
    'local_fallback'     => env('SANDBOX_LOCAL_FALLBACK', false),
    'max_execution_time' => env('SANDBOX_MAX_EXECUTION_TIME', 5),
    'memory_limit_mb'    => env('SANDBOX_MEMORY_LIMIT_MB', 64),
    'db_admin_user'      => env('SANDBOX_DB_ADMIN_USER', 'sandbox_admin'),
    'db_admin_pass'      => env('SANDBOX_DB_ADMIN_PASS', 'sandbox_admin_pass'),
    'db_host'            => env('SANDBOX_DB_HOST', 'mysql'),
    'shared_ro_user'     => 'sbx_shared_ro',
    'shared_ro_pass'     => env('SANDBOX_SHARED_RO_PASS', 'shared_ro_pass_change_me'),
    'shared_rw_user'     => 'sbx_shared_rw',
    'shared_rw_pass'     => env('SANDBOX_SHARED_RW_PASS', 'shared_rw_pass_change_me'),
    'disabled_functions' => [
        'exec', 'system', 'passthru', 'shell_exec', 'popen', 'proc_open',
        'file_get_contents', 'file_put_contents', 'fopen', 'curl_init', 'mail',
    ],
];

