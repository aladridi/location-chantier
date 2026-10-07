<?php

use App\Core\Container\Container;
use App\Core\Database\Database;
use App\Core\Database\DatabaseInterface;
use App\Core\EventDispatcher\EventDispatcher;
use App\Core\Router\Router;
use App\Core\Upload\ImageUpload;
use App\Core\Upload\ImageOptimizer;

// Repositories
use App\Repository\EquipmentRepository;
use App\Repository\ClientRepository;
use App\Repository\RentalRepository;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\EquipmentImageRepository;

// Services
use App\Service\RentalService;
use App\Service\ImageService;
use App\Service\Auth\AuthService;
use App\Service\Auth\PasswordHasher;
use App\Service\Auth\SessionManager;

// Pricing Strategies
use App\Service\PricingStrategy\Calculator\PriceCalculator;
use App\Service\PricingStrategy\PricingStrategyInterface;
use App\Service\PricingStrategy\Collection\PricingStrategyCollection;

// Observers
use App\Observer\MaintenanceAlert;
use App\Observer\RentalNotification;

return function (Container $container) {
    // ============================================
    // 1. PARAMÈTRES
    // ============================================
    $databaseConfigFile = __DIR__ . '/database.php';
    if (!file_exists($databaseConfigFile)) {
        throw new \RuntimeException('Database configuration file not found: ' . $databaseConfigFile);
    }

    $container->setParameter('database.config', require $databaseConfigFile);
    $container->setParameter('app.env', $_ENV['APP_ENV'] ?? 'dev');
    $container->setParameter('app.debug', $_ENV['APP_DEBUG'] ?? true);

    // ============================================
    // 2. SERVICES DE BASE (CORE)
    // ============================================

    $container->set(Database::class, function ($c) {
        $config = $c->getParameter('database.config');

        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? 'location_chantier';

        if (isset($config['socket']) && !empty($config['socket']) && file_exists($config['socket'])) {
            $dsn = "$driver:unix_socket={$config['socket']};dbname=$database;charset=utf8mb4";
        } else {
            $dsn = "$driver:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        }

        return new Database(
            $dsn,
            $config['username'] ?? 'root',
            $config['password'] ?? ''
        );
    });

    // Alias pour l'interface DatabaseInterface
    $container->set(DatabaseInterface::class, function ($c) {
        return $c->get(Database::class);
    });

    $container->set(Router::class, function () {
        return new Router();
    });

    $container->set(EventDispatcher::class, function () {
        return new EventDispatcher();
    });

    // ============================================
    // 3. REPOSITORIES
    // ============================================
    $container->set(EquipmentRepository::class, function ($c) {
        return new EquipmentRepository($c->get(Database::class));
    });

    $container->set(ClientRepository::class, function ($c) {
        return new ClientRepository($c->get(Database::class));
    });

    $container->set(RentalRepository::class, function ($c) {
        return new RentalRepository($c->get(Database::class));
    });

    $container->set(UserRepository::class, function ($c) {
        return new UserRepository($c->get(Database::class));
    });

    $container->set(CategoryRepository::class, function ($c) {
        return new CategoryRepository($c->get(Database::class));
    });

    $container->set(EquipmentImageRepository::class, function ($c) {
        return new EquipmentImageRepository($c->get(Database::class));
    });

    // ============================================
    // 4. SERVICES D'AUTHENTIFICATION
    // ============================================
    $container->set(SessionManager::class, function () {
        return new SessionManager();
    });

    $container->set(PasswordHasher::class, function () {
        return new PasswordHasher();
    });

    $container->set(AuthService::class, function ($c) {
        return new AuthService(
            $c->get(UserRepository::class),
            $c->get(PasswordHasher::class),
            $c->get(SessionManager::class)
        );
    });


    // ============================================
    // 5. COLLECTION DES STRATÉGIES
    // ============================================
    $container->set(PricingStrategyCollection::class, function ($c) {

        $strategyClasses = $c->getImplementations(
            PricingStrategyInterface::class,
            __DIR__ . '/../src/Service/PricingStrategy',
            'App\Service\PricingStrategy'
        );

        return new PricingStrategyCollection(
            $c,
            $strategyClasses
        );
    });

    // ============================================
    // 6. CALCULATEUR DE PRIX
    // ============================================
    $container->set(PriceCalculator::class, function ($c) {
        return new PriceCalculator(
            $c->get(PricingStrategyCollection::class)->getAll()
        );
    });

    // ============================================
    // 7. SERVICE PRINCIPAL
    // ============================================
    $container->set(RentalService::class, function ($c) {
        return new RentalService(
            $c->get(EquipmentRepository::class),
            $c->get(RentalRepository::class),
            $c->get(PriceCalculator::class),
            $c->get(EventDispatcher::class)
        );
    });

    // ============================================
    // 8. SERVICE D'IMAGES
    // ============================================

    // ✅ Configuration de l'upload
    $uploadDir = __DIR__ . '/../public/uploads/';

    // ✅ Créer l'ImageUpload
    $container->set(ImageUpload::class, function () use ($uploadDir) {
        return new ImageUpload($uploadDir);
    });

    // ✅ Créer l'ImageOptimizer
    $container->set(ImageOptimizer::class, function () {
        return new ImageOptimizer();
    });

    // ✅ Créer l'ImageService avec les bons paramètres
    $container->set(ImageService::class, function ($c) {
        return new ImageService(
            $c->get(ImageUpload::class),         // ✅ ImageUpload
            $c->get(ImageOptimizer::class),      // ✅ ImageOptimizer
            $c->get(EquipmentImageRepository::class) // ✅ Repository
        );
    });

    // ============================================
    // 9. OBSERVATEURS
    // ============================================
    $container->set(MaintenanceAlert::class, function () {
        return new MaintenanceAlert();
    });

    $container->set(RentalNotification::class, function () {
        return new RentalNotification();
    });

    // ============================================
    // 10. ENREGISTREMENT DES OBSERVATEURS
    // ============================================
    $eventDispatcher = $container->get(EventDispatcher::class);

    $eventDispatcher->addListener(
        'rental.created',
        [$container->get(MaintenanceAlert::class), 'onRentalCreated']
    );

    $eventDispatcher->addListener(
        'rental.created',
        [$container->get(RentalNotification::class), 'onRentalCreated']
    );

    $eventDispatcher->addListener(
        'rental.overdue',
        [$container->get(MaintenanceAlert::class), 'onRentalOverdue']
    );

    $eventDispatcher->addListener(
        'rental.returned',
        [$container->get(RentalNotification::class), 'onRentalReturned']
    );

    // ============================================
    // 11. DÉBOGAGE
    // ============================================
    if ($container->getParameter('app.debug')) {
        $eventDispatcher->addListener(
            'rental.created',
            function ($data) {
                error_log('[DEBUG] Rental created: ' . json_encode($data));
            }
        );
    }

    return $container;
};