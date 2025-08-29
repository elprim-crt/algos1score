<?php

namespace App\Debug;

/**
 * Buffered debug logger with optional syslog forwarding.
 *
 * Messages are queued in memory and flushed to debug.log at script shutdown
 * to reduce I/O overhead. Each message is also sent to the system logger
 * (syslog) immediately when available.
 */
function debug_log($message): void {
    static $buffer = [];
    static $registered = false;
    static $syslogOpened = false;

    $timestamp = date('Y-m-d H:i:s');
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    $line = "[$timestamp] $message";

    // Forward to syslog for asynchronous handling if available
    if (function_exists('openlog') && function_exists('syslog')) {
        if (!$syslogOpened) {
            openlog('algos1score', LOG_PID, LOG_USER);
            $syslogOpened = true;
        }
        syslog(LOG_INFO, $line);
    }

    // Queue the line for buffered file writing
    $buffer[] = $line;

    if (!$registered) {
        $registered = true;
        register_shutdown_function(function () use (&$buffer) {
            if (!$buffer) {
                return;
            }
            $logFile = dirname(__DIR__, 2) . '/debug.log';
            $dir = dirname($logFile);

            if (!file_exists($logFile) && !is_writable($dir)) {
                error_log("debug_log: Directory not writable for log file at $dir");
                return;
            }

            if (!file_exists($logFile)) {
                if (@touch($logFile) === false) {
                    error_log("debug_log: Failed to create log file at $logFile");
                    return;
                }
            }

            $data = implode(PHP_EOL, $buffer) . PHP_EOL;
            $bytesWritten = @file_put_contents($logFile, $data, FILE_APPEND);
            if ($bytesWritten === false) {
                error_log("debug_log: Failed to write to log file at $logFile");
            }
        });
    }
}
