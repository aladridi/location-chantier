<?php

namespace Tests\Unit\Core\Container;

use App\Core\Container\Container;
use App\Service\PricingStrategy\WeeklyPricing;
use PHPUnit\Framework\TestCase;
use App\Service\PricingStrategy\PricingStrategyInterface;
use App\Core\Container\ContainerInterface;


class ContainerTest extends TestCase
{
    public function test_it_autowires_weekly_pricing(): void
    {
        $container = new Container();

        $strategy = $container->get(WeeklyPricing::class);

        $this->assertInstanceOf(
            WeeklyPricing::class,
            $strategy
        );

        $this->assertSame(
            'weekly',
            $strategy->getType()
        );

        $this->assertSame(
            'Tarif hebdomadaire',
            $strategy->getLabel()
        );
    }

    public function test_it_discovers_all_pricing_strategies(): void
    {
        $container = new Container();

        $strategies = $container->getImplementations(
            PricingStrategyInterface::class,
            __DIR__ . '/../../../../src/Service/PricingStrategy',
            'App\Service\PricingStrategy'
        );

        $this->assertCount(6, $strategies);

        $this->assertContains(
            'App\Service\PricingStrategy\DailyPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\WeeklyPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\MonthlyPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\SeasonalPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\VolumeDiscountPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\PremiumPricing',
            $strategies
        );
    }


public function test_it_uses_default_value_for_optional_parameter(): void
{
    $container = new Container();

    $service = $container->get(
        \Tests\Unit\Core\Container\Fixtures\ServiceWithOptionalParameter::class
    );

    $this->assertInstanceOf(
        \Tests\Unit\Core\Container\Fixtures\ServiceWithOptionalParameter::class,
        $service
    );

    $this->assertSame(
        'default',
        $service->getValue()
    );
}

public function test_it_resolves_required_dependency_and_uses_optional_default(): void
{
    $container = new Container();

    $service = $container->get(
        \Tests\Unit\Core\Container\Fixtures\ServiceWithOptionalDependency::class
    );

    $this->assertInstanceOf(
        \Tests\Unit\Core\Container\Fixtures\ServiceWithOptionalDependency::class,
        $service
    );

    $this->assertInstanceOf(
        \Tests\Unit\Core\Container\Fixtures\RequiredDependency::class,
        $service->getDependency()
    );

    $this->assertSame(
        'default',
        $service->getValue()
    );
}


public function test_it_uses_null_for_unresolvable_nullable_dependency(): void
{
    $container = new Container();

    $service = $container->get(
        \Tests\Unit\Core\Container\Fixtures\ServiceWithNullableDependency::class
    );

    $this->assertInstanceOf(
        \Tests\Unit\Core\Container\Fixtures\ServiceWithNullableDependency::class,
        $service
    );

    $this->assertNull(
        $service->getDependency()
    );
}





    public function test_it_autowires_interface_with_single_implementation(): void
    {
        $container = new Container();

        $service = $container->get(
            \Tests\Unit\Core\Container\Fixtures\ServiceWithContainerInterfaceDependency::class
        );

        $this->assertInstanceOf(
            \Tests\Unit\Core\Container\Fixtures\ServiceWithContainerInterfaceDependency::class,
            $service
        );

        $this->assertInstanceOf(
            Container::class,
            $service->getContainer()
        );

        $this->assertInstanceOf(
            ContainerInterface::class,
            $service->getContainer()
        );
    }

    public function test_it_rejects_interface_with_multiple_implementations(): void
    {
        $container = new Container();

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(
            'Multiple implementations found for ' .
            PricingStrategyInterface::class
        );

        $container->get(PricingStrategyInterface::class);
    }







}