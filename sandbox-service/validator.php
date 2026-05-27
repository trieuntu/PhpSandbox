<?php

/**
 * Static code analysis using PHP's own tokenizer.
 *
 * Returns ['blocked' => bool, 'message' => string].
 * Blocked  → execution must not proceed.
 * Warning  → logged but execution is allowed (e.g. eval()).
 */
function validateCode(string $code): array
{
    $tokens = @token_get_all('<?php ' . $code);

    // 1. Reject backtick shell execution operator
    foreach ($tokens as $token) {
        if (is_string($token) && $token === '`') {
            return ['blocked' => true, 'message' => 'Backtick operator (shell execution) is not allowed.'];
        }
    }

    $dangerousFunctions = [
        'exec', 'system', 'passthru', 'shell_exec',
        'popen', 'proc_open', 'proc_close',
    ];

    $stringValues   = [];
    $tokenCount     = count($tokens);

    for ($i = 0; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if (!is_array($token)) {
            continue;
        }

        // 2. Collect string literal values for dynamic-call detection
        if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $val = trim($token[1], '"\'');
            $stringValues[] = strtolower($val);
        }

        // 3. Detect variable-variable calls: $$fn() pattern
        if ($token[0] === T_VARIABLE) {
            // Look ahead for another $ (variable variable)
            $next = $tokens[$i + 1] ?? null;
            if (is_array($next) && $next[0] === T_VARIABLE) {
                return ['blocked' => true, 'message' => 'Variable variables used as function calls ($$fn()) are not allowed.'];
            }
        }
    }

    // 4. Detect string literals that match dangerous function names
    //    (catches: $f = 'exec'; $f() pattern)
    foreach ($stringValues as $val) {
        if (in_array($val, $dangerousFunctions, true)) {
            return ['blocked' => true, 'message' => "Dynamic call to '{$val}' is not allowed."];
        }
    }

    // 5. Detect direct calls to proc_open / proc_close in function-call tokens
    for ($i = 0; $i < $tokenCount; $i++) {
        $token = $tokens[$i];
        if (is_array($token) && $token[0] === T_STRING) {
            $name = strtolower($token[1]);
            if ($name === 'proc_open' || $name === 'proc_close') {
                // Check the next non-whitespace token is '(' — i.e. a call, not a string
                for ($j = $i + 1; $j < $tokenCount; $j++) {
                    $ahead = $tokens[$j];
                    if (is_array($ahead) && $ahead[0] === T_WHITESPACE) {
                        continue;
                    }
                    if ($ahead === '(') {
                        return ['blocked' => true, 'message' => "Direct call to '{$token[1]}' is not allowed."];
                    }
                    break;
                }
            }

            // 6. Log warning for eval() but do NOT block
            if ($name === 'eval') {
                error_log("[sandbox-validator] WARNING: eval() detected in submitted code.");
            }
        }
    }

    return ['blocked' => false, 'message' => ''];
}
