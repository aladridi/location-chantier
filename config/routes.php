<?php
use App\Core\Router\Router;
use App\Controller\AuthApiController;
use App\Controller\EquipmentController;
use App\Controller\ClientController;
use App\Controller\RentalController;
use App\Controller\CategoryController;

return function (Router $router) {
    // ============================================
    // AUTHENTIFICATION API
    // ============================================
    $router->addRoute('/api/auth/login', AuthApiController::class, 'login', 'POST');
    $router->addRoute('/api/auth/register', AuthApiController::class, 'register', 'POST');
    $router->addRoute('/api/auth/me', AuthApiController::class, 'me', 'GET');
    $router->addRoute('/api/auth/logout', AuthApiController::class, 'logout', 'POST');

    // ============================================
    // API PROTÉGÉES
    // ============================================
    $router->addRoute('/api/equipment', EquipmentController::class, 'list', 'GET');
    $router->addRoute('/api/equipment/stats', EquipmentController::class, 'stats', 'GET');
    $router->addRoute('/api/equipment', EquipmentController::class, 'create', 'POST');
    $router->addRoute('/api/equipment/{id}', EquipmentController::class, 'update', 'PUT');
    $router->addRoute('/api/equipment/{id}', EquipmentController::class, 'delete', 'DELETE');

    $router->addRoute('/api/clients', ClientController::class, 'list', 'GET');
    $router->addRoute('/api/clients/search', ClientController::class, 'search', 'GET');
    $router->addRoute('/api/clients/stats', ClientController::class, 'stats', 'GET');
    $router->addRoute('/api/clients', ClientController::class, 'create', 'POST');
    $router->addRoute('/api/clients/{id}', ClientController::class, 'show', 'GET');
    $router->addRoute('/api/clients/{id}', ClientController::class, 'update', 'PUT');
    $router->addRoute('/api/clients/{id}', ClientController::class, 'delete', 'DELETE');

    $router->addRoute('/api/rentals', RentalController::class, 'list', 'GET');
    $router->addRoute('/api/rentals/stats', RentalController::class, 'stats', 'GET');
    $router->addRoute('/api/rentals/recent', RentalController::class, 'recent', 'GET');
    $router->addRoute('/api/rentals/monthly-revenue', RentalController::class, 'monthlyRevenue', 'GET');
    $router->addRoute('/api/rentals', RentalController::class, 'create', 'POST');
    $router->addRoute('/api/rentals/{id}/return', RentalController::class, 'return', 'POST');
    $router->addRoute('/api/rentals/estimate', RentalController::class, 'estimate', 'GET');
    // ============================================
    // API CATÉGORIES
    // ============================================
    $router->addRoute('/api/categories', CategoryController::class, 'list', 'GET');
    $router->addRoute('/api/categories/active', CategoryController::class, 'active', 'GET');
    $router->addRoute('/api/categories', CategoryController::class, 'create', 'POST');
    $router->addRoute('/api/categories/reorder', CategoryController::class, 'reorder', 'POST');
    $router->addRoute('/api/categories/{id}', CategoryController::class, 'show', 'GET');
    $router->addRoute('/api/categories/{id}', CategoryController::class, 'update', 'PUT');
    $router->addRoute('/api/categories/{id}', CategoryController::class, 'delete', 'DELETE');
};