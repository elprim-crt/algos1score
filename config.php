<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'algos1score');
define('DB_USER', getenv('DB_USER') ?: 'algos1score_user');
define('DB_PASS', getenv('DB_PASS') ?: 'algos1score_password');
define('DB_DSN', getenv('DB_DSN') ?: null);

// Debug logging configuration
define('DEBUG_LOG_ENABLED',
    filter_var(getenv('DEBUG_LOG_ENABLED'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true
);
