<?php

namespace App\Controller;

abstract class BaseController
{
    protected const ROLE_ADMIN = ['Admin'];

    private const VIEWS_PATH = __DIR__ . '/../../views/';

    protected function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require self::VIEWS_PATH . $view . '.php';
    }

    protected function abort(int $statusCode, string $message): never
    {
        http_response_code($statusCode);
        echo $message;
        exit;
    }
}
