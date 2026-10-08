<?php

namespace Tests\Unit\Service\PricingStrategy;

use App\Service\PricingStrategy\WeeklyPricing;
use PHPUnit\Framework\TestCase;

class WeeklyPricingTest extends TestCase
{
    public function test_weekly_pricing_contains_weekend_promotion(): void
    {
        $strategy = new WeeklyPricing();

        $promotions = $strategy->getPromotions();

        $this->assertCount(1, $promotions);

        $this->assertSame(
            'Promotion week-end',
            $promotions[0]->getLabel()
        );

        $this->assertSame(
            'Réduction de 10% pour les locations incluant le week-end',
            $promotions[0]->getDescription()
        );
    }
}