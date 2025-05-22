<?php

use Controllers\UserController;
use Controllers\DefaultController;
use Middleware\AuthMiddleware;

$router->get('/', [DefaultController::class, 'index']);
$router->get('/api/v1', [DefaultController::class, 'index']);

// User routes
$router->get('/api/v1/users', [UserController::class, 'index']);
$router->get('/api/v1/users/{id}', [UserController::class, 'show'], [AuthMiddleware::class]);
$router->put('/api/v1/users/{id}', [UserController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/api/v1/users/{id}', [UserController::class, 'destroy'], [AuthMiddleware::class]);

// Authentication routes
$router->post('/api/v1/auth/login', [UserController::class, 'login']);
$router->post('/api/v1/auth/register', [UserController::class, 'register']);
$router->get('/api/v1/auth/me', [UserController::class, 'show'], [AuthMiddleware::class]);


