<?php

namespace Tests\Unit\Service\PricingStrategy;

use App\Service\PricingStrategy\SeasonalPricing;
use PHPUnit\Framework\TestCase;

class SeasonalPricingTest extends TestCase
{
    public function test_seasonal_pricing_contains_low_season_promotion(): void
    {
        $strategy = new SeasonalPricing();

        $promotions = $strategy->getPromotions();

        $this->assertCount(1, $promotions);

        $this->assertSame(
            'Promotion basse saison',
            $promotions[0]->getLabel()
        );

        $this->assertSame(
            'Réduction supplémentaire de 10% en basse saison',
            $promotions[0]->getDescription()
        );
    }
}