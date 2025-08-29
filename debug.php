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

    // Attempt to create the log file if it does not exist and we have permission
    if (!file_exists($logFile)) {
        $dir = dirname($logFile);
        if (is_writable($dir)) {
            if (@touch($logFile) === false) {
                error_log("debug_log: Failed to create log file at $logFile");
                return;
            }
        } else {
            error_log("debug_log: Directory not writable for log file at $dir");
            return;
        }
    }

    // Write log entry and check for failure
    $bytesWritten = @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    if ($bytesWritten === false) {
        error_log("debug_log: Failed to write to log file at $logFile");
    }
}
