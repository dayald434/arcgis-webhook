<?php
// Fixed webhook for InfinityFree - handles common issues
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $logFile = __DIR__ . '/webhook_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    
    // Build log entry
    $logEntry = "\n" . str_repeat("=", 70) . "\n";
    $logEntry .= "WEBHOOK RECEIVED: " . $timestamp . "\n";
    $logEntry .= str_repeat("=", 70) . "\n\n";
    
    // 1. Request Method
    $logEntry .= "METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n\n";
    
    // 2. Headers (compatible with all PHP versions)
    $logEntry .= "=== HEADERS ===\n";
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            $logEntry .= "$name: $value\n";
        }
    } else {
        // Fallback for servers without getallheaders
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $logEntry .= "$header: $value\n";
            }
        }
    }
    $logEntry .= "\n";
    
    // 3. Raw input
    $rawPayload = @file_get_contents('php://input');
    $logEntry .= "=== RAW INPUT (length: " . strlen($rawPayload) . ") ===\n";
    $logEntry .= $rawPayload ?: "(empty)";
    $logEntry .= "\n\n";
    
    // 4. POST data
    $logEntry .= "=== POST DATA ===\n";
    if (!empty($_POST)) {
        $logEntry .= print_r($_POST, true);
    } else {
        $logEntry .= "(empty)\n";
    }
    $logEntry .= "\n";
    
    // 5. GET data
    $logEntry .= "=== GET DATA ===\n";
    if (!empty($_GET)) {
        $logEntry .= print_r($_GET, true);
    } else {
        $logEntry .= "(empty)\n";
    }
    $logEntry .= "\n";
    
    // 6. Server info
    $logEntry .= "=== SERVER INFO ===\n";
    $logEntry .= "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";
    $logEntry .= "CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'not set') . "\n";
    $logEntry .= "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
    $logEntry .= "USER_AGENT: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'not set') . "\n";
    $logEntry .= "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'not set') . "\n";
    $logEntry .= "\n";
    
    // 7. Try JSON parsing
    $parsedJson = null;
    if (!empty($rawPayload)) {
        $logEntry .= "=== JSON PARSE ===\n";
        $parsedJson = json_decode($rawPayload, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $logEntry .= "SUCCESS:\n";
            $logEntry .= print_r($parsedJson, true);
        } else {
            $logEntry .= "FAILED: " . json_last_error_msg() . "\n";
        }
        $logEntry .= "\n";
    }
    
    $logEntry .= str_repeat("=", 70) . "\n";
    
    // Try to write log
    $writeSuccess = @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Build response
    $response = [
        'status' => 'success',
        'message' => 'Webhook received',
        'timestamp' => $timestamp,
        'log_written' => $writeSuccess !== false,
        'log_file' => $logFile,
        'log_size' => $writeSuccess !== false ? filesize($logFile) : 0,
        'data_received' => [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'has_raw' => !empty($rawPayload),
            'has_post' => !empty($_POST),
            'has_get' => !empty($_GET),
            'valid_json' => $parsedJson !== null
        ]
    ];
    
    // Check file permissions
    if ($writeSuccess === false) {
        $response['error'] = 'Could not write to log file';
        $response['log_dir_writable'] = is_writable(__DIR__);
        $response['log_exists'] = file_exists($logFile);
        if (file_exists($logFile)) {
            $response['log_writable'] = is_writable($logFile);
        }
    }
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>