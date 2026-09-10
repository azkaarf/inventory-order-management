<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

use App\Controller\AuthController;
use App\Controller\UserController;
use App\Repository\MySqlUserRepository;
use App\Service\AuthService;
use App\Service\UserService;

$userRepository = new MySqlUserRepository();
$authController = new AuthController(new AuthService($userRepository));
$userController = new UserController(new UserService($userRepository));

$routes = [
    'GET /'              => [$authController, 'showLoginForm'],
    'GET /login'         => [$authController, 'showLoginForm'],
    'POST /login'        => [$authController, 'login'],
    'POST /logout'       => [$authController, 'logout'],
    'GET /dashboard'     => [$authController, 'dashboard'],

    'GET /users'         => [$userController, 'index'],
    'GET /users/create'  => [$userController, 'showCreateForm'],
    'POST /users/create' => [$userController, 'create'],
    'GET /users/edit'    => [$userController, 'showEditForm'],
    'POST /users/edit'   => [$userController, 'update'],
    'POST /users/toggle' => [$userController, 'toggleActive'],
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