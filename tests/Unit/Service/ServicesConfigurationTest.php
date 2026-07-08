<?php
namespace Tests\Unit\Service;

use App\Core\Container\Container;
use App\Core\Database\Database;
use App\Core\EventDispatcher\EventDispatcher;
use App\Core\Router\Router;
use App\Repository\EquipmentRepository;
use App\Repository\ClientRepository;
use App\Repository\RentalRepository;
use App\Service\RentalService;
use App\Service\PricingStrategy\Calculator\PriceCalculator;
use App\Service\PricingStrategy\Collection\PricingStrategyCollection;
use App\Service\PricingStrategy\DailyPricing;
use App\Service\PricingStrategy\WeeklyPricing;
use App\Service\PricingStrategy\MonthlyPricing;
use App\Service\PricingStrategy\SeasonalPricing;
use App\Service\PricingStrategy\VolumeDiscountPricing;
use App\Service\PricingStrategy\PremiumPricing;
use PHPUnit\Framework\TestCase;

class ServicesConfigurationTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();

        $configFile = __DIR__ . '/../../../config/services.php';

        if (!file_exists($configFile)) {
            $this->markTestSkipped('Le fichier config/services.php n\'existe pas');
        }

        $config = require $configFile;
        $config($this->container);
    }

    public function testContainerHasAllServices(): void
    {
        $services = [
            Database::class,
            Router::class,
            EventDispatcher::class,
            EquipmentRepository::class,
            ClientRepository::class,
            RentalRepository::class,
            PricingStrategyCollection::class,
            PriceCalculator::class,
            RentalService::class,
        ];

        foreach ($services as $service) {
            $this->assertTrue(
                $this->container->has($service),
                "Service {$service} not found in container"
            );
        }
    }

    public function testPricingStrategyCollectionIsCorrectlyConfigured(): void
    {
        $collection = $this->container->get(PricingStrategyCollection::class);
        $this->assertInstanceOf(PricingStrategyCollection::class, $collection);

        $strategies = $collection->getAll();
        $this->assertNotEmpty($strategies);
        $this->assertGreaterThanOrEqual(6, count($strategies));
    }

    public function testPriceCalculatorIsCorrectlyConfigured(): void
    {
        $calculator = $this->container->get(PriceCalculator::class);
        $this->assertInstanceOf(PriceCalculator::class, $calculator);

        $reflection = new \ReflectionClass($calculator);
        $property = $reflection->getProperty('strategies');
        $property->setAccessible(true);

        $strategies = $property->getValue($calculator);

        $this->assertNotEmpty($strategies);
        $this->assertGreaterThanOrEqual(6, count($strategies));
    }

    public function testRentalServiceHasPriceCalculator(): void
    {
        $rentalService = $this->container->get(RentalService::class);
        $this->assertInstanceOf(RentalService::class, $rentalService);

        $reflection = new \ReflectionClass($rentalService);
        $property = $reflection->getProperty('priceCalculator');
        $property->setAccessible(true);

        $calculator = $property->getValue($rentalService);

        $this->assertInstanceOf(PriceCalculator::class, $calculator);
    }

    public function testContainerHasDatabaseService(): void
    {
        $this->assertTrue(
            $this->container->has(Database::class),
            'Database service not found in container'
        );
    }

    public function testContainerHasRouterService(): void
    {
        $this->assertTrue(
            $this->container->has(Router::class),
            'Router service not found in container'
        );
    }

    public function testContainerHasEventDispatcherService(): void
    {
        $this->assertTrue(
            $this->container->has(EventDispatcher::class),
            'EventDispatcher service not found in container'
        );
    }

    public function testContainerHasRepositories(): void
    {
        $repositories = [
            EquipmentRepository::class,
            ClientRepository::class,
            RentalRepository::class,
        ];

        foreach ($repositories as $repository) {
            $this->assertTrue(
                $this->container->has($repository),
                "Repository {$repository} not found in container"
            );
        }
    }

    public function testContainerHasPricingStrategies(): void
    {
        $strategies = [
            DailyPricing::class,
            WeeklyPricing::class,
            MonthlyPricing::class,
            SeasonalPricing::class,
            VolumeDiscountPricing::class,
            PremiumPricing::class,
        ];

        foreach ($strategies as $strategy) {
            $this->assertTrue(
                $this->container->has($strategy),
                "Strategy {$strategy} not found in container"
            );
        }
    }

    public function testContainerHasPricingStrategyCollection(): void
    {
        $this->assertTrue(
            $this->container->has(PricingStrategyCollection::class),
            'PricingStrategyCollection not found in container'
        );

        $collection = $this->container->get(PricingStrategyCollection::class);
        $this->assertInstanceOf(PricingStrategyCollection::class, $collection);
        $this->assertGreaterThanOrEqual(6, $collection->count());
    }

    public function testContainerHasPriceCalculator(): void
    {
        $this->assertTrue(
            $this->container->has(PriceCalculator::class),
            'PriceCalculator not found in container'
        );

        $calculator = $this->container->get(PriceCalculator::class);
        $this->assertInstanceOf(PriceCalculator::class, $calculator);
    }

    public function testContainerHasRentalService(): void
    {
        $this->assertTrue(
            $this->container->has(RentalService::class),
            'RentalService not found in container'
        );

        $rentalService = $this->container->get(RentalService::class);
        $this->assertInstanceOf(RentalService::class, $rentalService);
    }

    public function testContainerHasParameters(): void
    {
        try {
            $databaseConfig = $this->container->getParameter('database.config');
            $this->assertIsArray($databaseConfig);
            $this->assertArrayHasKey('dsn', $databaseConfig);
            $this->assertArrayHasKey('username', $databaseConfig);
            $this->assertArrayHasKey('password', $databaseConfig);
        } catch (\RuntimeException $e) {
            $this->fail('Parameter database.config not found: ' . $e->getMessage());
        }
    }

    public function testAllServicesAreInstantiable(): void
    {
        $services = [
            Database::class,
            Router::class,
            EventDispatcher::class,
            PricingStrategyCollection::class,
        ];

        foreach ($services as $service) {
            try {
                $instance = $this->container->get($service);
                $this->assertNotNull($instance);
            } catch (\Exception $e) {
                $this->fail(
                    "Failed to instantiate {$service}: " . $e->getMessage()
                );
            }
        }
    }
}