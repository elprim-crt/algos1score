<?php
/**
 * Simple file-based debug logger.
 *
 * Writes messages to debug.log in the project root with a timestamp.
 */
function debug_log($message): void {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}
