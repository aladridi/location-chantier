<?php

namespace Tests\Unit\Service\PricingStrategy;

use App\Service\PricingStrategy\MonthlyPricing;
use PHPUnit\Framework\TestCase;

class MonthlyPricingTest extends TestCase
{
    public function test_monthly_pricing_contains_two_promotions(): void
    {
        $strategy = new MonthlyPricing();

        $promotions = $strategy->getPromotions();

        $this->assertCount(2, $promotions);

        $this->assertSame(
            'Offre 1 jour gratuit',
            $promotions[0]->getLabel()
        );

        $this->assertSame(
            'Remise fidélité',
            $promotions[1]->getLabel()
        );
    }
}