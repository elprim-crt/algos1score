<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'algos1score');
define('DB_USER', getenv('DB_USER') ?: 'algos1score_user');
define('DB_PASS', getenv('DB_PASS') ?: 'algos1score_password');
define('DB_DSN', getenv('DB_DSN') ?: null);
