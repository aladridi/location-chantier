<?php

use App\Core\Container\Container;
use App\Core\Database\Database;
use App\Core\EventDispatcher\EventDispatcher;
use App\Core\Router\Router;

// Repositories
use App\Repository\EquipmentRepository;
use App\Repository\EquipmentRepositoryInterface;
use App\Repository\ClientRepository;
use App\Repository\RentalRepository;
use App\Repository\RentalRepositoryInterface;

// Services
use App\Service\RentalService;

// Pricing Strategies
use App\Service\PricingStrategy\DailyPricing;
use App\Service\PricingStrategy\WeeklyPricing;
use App\Service\PricingStrategy\MonthlyPricing;
use App\Service\PricingStrategy\SeasonalPricing;
use App\Service\PricingStrategy\VolumeDiscountPricing;
use App\Service\PricingStrategy\PremiumPricing;
use App\Service\PricingStrategy\Factory\PricingStrategyFactory;
use App\Service\PricingStrategy\Calculator\PriceCalculator;
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
        return new Database(
            $config['dsn'],
            $config['username'],
            $config['password']
        );
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
    // Enregistrer les classes concrètes
    $container->set(EquipmentRepository::class, function ($c) {
        return new EquipmentRepository($c->get(Database::class));
    });

    $container->set(ClientRepository::class, function ($c) {
        return new ClientRepository($c->get(Database::class));
    });

    $container->set(RentalRepository::class, function ($c) {
        return new RentalRepository($c->get(Database::class));
    });

    // ✅ AJOUTÉ : Alias pour les interfaces
    $container->set(EquipmentRepositoryInterface::class, function ($c) {
        return $c->get(EquipmentRepository::class);
    });

    $container->set(RentalRepositoryInterface::class, function ($c) {
        return $c->get(RentalRepository::class);
    });

    // ============================================
    // 4. STRATÉGIES DE PRIX INDIVIDUELLES
    // ============================================
    $container->set(DailyPricing::class, function () {
        return new DailyPricing();
    });

    $container->set(WeeklyPricing::class, function () {
        return new WeeklyPricing();
    });

    $container->set(MonthlyPricing::class, function () {
        return new MonthlyPricing();
    });

    $container->set(SeasonalPricing::class, function () {
        return new SeasonalPricing();
    });

    $container->set(VolumeDiscountPricing::class, function () {
        return new VolumeDiscountPricing();
    });

    $container->set(PremiumPricing::class, function () {
        return new PremiumPricing();
    });

    // ============================================
    // 5. COLLECTION DES STRATÉGIES
    // ============================================
    $container->set(PricingStrategyCollection::class, function ($c) {
        $strategies = [
            $c->get(DailyPricing::class),
            $c->get(WeeklyPricing::class),
            $c->get(MonthlyPricing::class),
            $c->get(SeasonalPricing::class),
            $c->get(VolumeDiscountPricing::class),
            $c->get(PremiumPricing::class),
        ];

        $strategiesWithPromotions = [];
        foreach ($strategies as $strategy) {
            $type = str_replace('pricing', '', strtolower($strategy->getType()));
            $strategiesWithPromotions[] = PricingStrategyFactory::createWithPromotions($type);
        }

        return new PricingStrategyCollection($strategiesWithPromotions);
    });

    // ============================================
    // 6. CALCULATEUR DE PRIX
    // ============================================
    $container->set(PriceCalculator::class, function ($c) {
        $collection = $c->get(PricingStrategyCollection::class);
        return new PriceCalculator($collection->getAll());
    });

    // ============================================
    // 7. SERVICE PRINCIPAL
    // ============================================
    $container->set(RentalService::class, function ($c) {
        return new RentalService(
            $c->get(EquipmentRepositoryInterface::class),  // ✅ Utiliser l'interface
            $c->get(RentalRepositoryInterface::class),      // ✅ Utiliser l'interface
            $c->get(PriceCalculator::class),
            $c->get(EventDispatcher::class)
        );
    });

    // ============================================
    // 8. OBSERVATEURS
    // ============================================
    $container->set(MaintenanceAlert::class, function () {
        return new MaintenanceAlert();
    });

    $container->set(RentalNotification::class, function () {
        return new RentalNotification();
    });

    // ============================================
    // 9. ENREGISTREMENT DES OBSERVATEURS
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
    // 10. DÉBOGAGE
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