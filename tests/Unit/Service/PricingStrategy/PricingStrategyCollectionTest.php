<?php

namespace Tests\Unit\Service\PricingStrategy;

use App\Core\Container\Container;
use App\Service\PricingStrategy\Collection\PricingStrategyCollection;
use App\Service\PricingStrategy\PricingStrategyInterface;
use App\Service\PricingStrategy\WeeklyPricing;
use PHPUnit\Framework\TestCase;

class PricingStrategyCollectionTest extends TestCase
{
    public function test_it_builds_collection_from_discovered_strategies(): void
    {
        $container = new Container();

        $classes = $container->getImplementations(
            PricingStrategyInterface::class,
            __DIR__ . '/../../../../src/Service/PricingStrategy',
            'App\Service\PricingStrategy'
        );

        $collection = new PricingStrategyCollection(
            $container,
            $classes
        );

        $this->assertCount(6, $collection->getAll());

        $this->assertInstanceOf(
            WeeklyPricing::class,
            $collection->find('weekly')
        );
    }
}