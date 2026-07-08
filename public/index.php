<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;

// Chargement des variables d'environnement
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialisation du container
$container = new Container();

// Chargement des services
$servicesConfig = require __DIR__ . '/../config/services.php';
$servicesConfig($container);

// Chargement des routes
$router = $container->get(\App\Core\Router\Router::class);
$routesConfig = require __DIR__ . '/../config/routes.php';
$routesConfig($router);

// Création de la requête
$request = new Request();

// ✅ Récupérer l'URI sans les paramètres GET
$uri = strtok($request->getUri(), '?');

// ✅ Si c'est une route API, on la traite normalement
if (str_starts_with($uri, '/api/')) {
    $route = $router->match($uri, $request->getMethod());

    if (!$route) {
        $response = new Response(json_encode(['error' => 'API endpoint not found']), 404);
        $response->setHeader('Content-Type', 'application/json');
        $response->send();
        exit;
    }

    // Instanciation du contrôleur
    $controllerClass = $route->getController();
    $controller = $container->get($controllerClass);

    // Appel de la méthode
    $method = $route->getMethod();
    $parameters = $route->getParameters();

    try {
        $response = $controller->$method($request, ...$parameters);

        if (!$response instanceof Response) {
            $response = new Response($response);
        }

        $response->send();
        exit;
    } catch (\Exception $e) {
        if ($_ENV['APP_DEBUG'] ?? false) {
            $response = new Response(json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]), 500);
        } else {
            $response = new Response('Internal Server Error', 500);
        }
        $response->setHeader('Content-Type', 'application/json');
        $response->send();
        exit;
    }
}

// ✅ Pour toutes les autres routes, servir l'application Vue (SPA)
$pageTitle = 'Location Chantier - Gestion de matériel';
include __DIR__ . '/../templates/vue/app.php';