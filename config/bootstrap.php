<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', getenv('APP_ENV') === 'local' ? '1' : '0');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e): void {
    error_log(sprintf(
        'Unhandled exception: %s in %s:%d',
        \App\Support\LogSanitizer::clean($e->getMessage()),
        $e->getFile(),
        $e->getLine(),
    ));

    if (!headers_sent()) {
        http_response_code(500);
    }

    echo '500 Internal Server Error — something went wrong. Please try again later.';
});
