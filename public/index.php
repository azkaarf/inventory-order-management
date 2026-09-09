<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

use App\Controller\AuthController;
use App\Repository\MySqlUserRepository;
use App\Service\AuthService;

$authService = new AuthService(new MySqlUserRepository());
$authController = new AuthController($authService);

$routes = [
    'GET /'         => [$authController, 'showLoginForm'],
    'GET /login'    => [$authController, 'showLoginForm'],
    'POST /login'   => [$authController, 'login'],
    'POST /logout'  => [$authController, 'logout'],
    'GET /dashboard'=> [$authController, 'dashboard'],
];

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$key = "$method $path";

if (!isset($routes[$key])) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

[$controller, $action] = $routes[$key];
$controller->$action();
