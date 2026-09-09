<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ERR-01: di production nanti display_errors sebaiknya dimatikan (APP_ENV != local),
// supaya stack trace tidak pernah tampil ke user.
ini_set('display_errors', getenv('APP_ENV') === 'local' ? '1' : '0');
error_reporting(E_ALL);
