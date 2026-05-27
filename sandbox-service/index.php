<?php
require_once __DIR__ . '/Executor.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Validate Content-Type
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') === false) {
    http_response_code(415);
    echo json_encode(['error' => 'Content-Type must be application/json']);
    exit;
}

// Trusted origins — only the app service can reach this container (sandbox_net is internal),
// but we add a shared secret header as defence-in-depth.
$sandboxSecret = getenv('SANDBOX_SECRET') ?: '';
if ($sandboxSecret !== '') {
    $providedSecret = $_SERVER['HTTP_X_SANDBOX_SECRET'] ?? '';
    if (!hash_equals($sandboxSecret, $providedSecret)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$executor = new Executor($input);
$result   = $executor->run();

echo json_encode($result);
