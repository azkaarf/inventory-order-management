<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ERR-01: display_errors should be disabled in production (APP_ENV != local),
// so stack traces are never shown to the user.
ini_set('display_errors', getenv('APP_ENV') === 'local' ? '1' : '0');
error_reporting(E_ALL);

// ERR-01 safety net: any exception that isn't already handled somewhere more
// specific ends up here instead of leaking a raw stack trace or DB details.
// The real error still goes to the log for us to debug.
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
