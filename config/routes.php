<?php
use App\Core\Router\Router;
use App\Controller\RentalController;
use App\Controller\EquipmentController;
use App\Controller\VueController;
use App\Controller\DashboardController;


return function (Router $router) {

    // Routes Vue (UI)
    $router->addRoute('/', VueController::class, 'index', 'GET');
    $router->addRoute('/equipment', VueController::class, 'equipment', 'GET');
    $router->addRoute('/equipment/create', VueController::class, 'equipment', 'GET');
    $router->addRoute('/equipment/{id}/edit', VueController::class, 'equipment', 'GET');
    $router->addRoute('/clients', VueController::class, 'clients', 'GET');
    $router->addRoute('/clients/create', VueController::class, 'clients', 'GET');
    $router->addRoute('/clients/{id}/edit', VueController::class, 'clients', 'GET');
    $router->addRoute('/rentals', VueController::class, 'rentals', 'GET');
    $router->addRoute('/rentals/create', VueController::class, 'rentals', 'GET');
    $router->addRoute('/rentals/{id}', VueController::class, 'rentals', 'GET');

    // Routes API
    $router->addRoute('/api/rentals', RentalController::class, 'list', 'GET');
    $router->addRoute('/api/rentals/{id}', RentalController::class, 'show', 'GET');
    $router->addRoute('/api/rentals', RentalController::class, 'create', 'POST');
    $router->addRoute('/api/rentals/{id}/return', RentalController::class, 'return', 'POST');

    $router->addRoute('/api/equipment', EquipmentController::class, 'list', 'GET');
    $router->addRoute('/api/equipment', EquipmentController::class, 'create', 'POST');
    $router->addRoute('/api/equipment/{id}', EquipmentController::class, 'show', 'GET');
};