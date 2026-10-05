<?php

namespace App\Controller;

abstract class BaseController
{
    protected const ROLE_ADMIN = ['Admin'];

    private const VIEWS_PATH = __DIR__ . '/../../views/';

    /**
     * Redirect ke path tertentu lalu hentikan eksekusi.
     */
    protected function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    /**
     * Render view dari folder views/.
     * Contoh: $this->render('categories/index', ['categories' => $list]);
     * Key di $data akan tersedia sebagai variabel di file view.
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require self::VIEWS_PATH . $view . '.php';
    }

    /**
     * Kirim HTTP error response lalu hentikan eksekusi.
     */
    protected function abort(int $statusCode, string $message): never
    {
        http_response_code($statusCode);
        echo $message;
        exit;
    }
}
