<?php

namespace Tests\Unit\Service\PricingStrategy\Promotion;

use App\Entity\Category;
use App\Entity\Equipment;
use App\Service\PricingStrategy\Promotion\FixedDiscountPromotion;
use PHPUnit\Framework\TestCase;

class FixedDiscountPromotionTest extends TestCase
{
    private function createEquipment(): Equipment
    {
        $category = new Category(
            'Excavatrice',
            'excavator'
        );

        return new Equipment(
            'Excavatrice CAT',
            $category,
            100.0
        );
    }

    public function test_it_calculates_fixed_discount(): void
    {
        $promotion = new FixedDiscountPromotion(
            'Remise fidélité',
            '50€ de réduction',
            50
        );

        $equipment = $this->createEquipment();

        $discount = $promotion->calculateDiscount(
            $equipment,
            10,
            1000.0
        );

        $this->assertSame(50.0, $discount);
    }

    public function test_it_does_not_exceed_current_price(): void
    {
        $promotion = new FixedDiscountPromotion(
            'Remise fidélité',
            '50€ de réduction',
            50
        );

        $equipment = $this->createEquipment();

        $discount = $promotion->calculateDiscount(
            $equipment,
            10,
            30.0
        );

        $this->assertSame(30.0, $discount);
    }

    public function test_it_requires_minimum_number_of_days(): void
    {
        $promotion = new FixedDiscountPromotion(
            'Remise longue durée',
            '50€ à partir de 20 jours',
            50,
            minDays: 20
        );

        $equipment = $this->createEquipment();

        $this->assertFalse(
            $promotion->isApplicable($equipment, 19)
        );

        $this->assertTrue(
            $promotion->isApplicable($equipment, 20)
        );

        $this->assertTrue(
            $promotion->isApplicable($equipment, 30)
        );
    }

    public function test_it_requires_minimum_price(): void
    {
        $promotion = new FixedDiscountPromotion(
            'Remise fidélité',
            '50€ à partir de 500€',
            50,
            minPrice: 500
        );

        $equipment = $this->createEquipment();

        $this->assertSame(
            0.0,
            $promotion->calculateDiscount(
                $equipment,
                10,
                499.99
            )
        );

        $this->assertSame(
            50.0,
            $promotion->calculateDiscount(
                $equipment,
                10,
                500.0
            )
        );
    }



    public function test_it_returns_label_and_description(): void
    {
        $promotion = new FixedDiscountPromotion(
            'Remise fidélité',
            '50€ de réduction pour les clients fidèles',
            50
        );

        $this->assertSame(
            'Remise fidélité',
            $promotion->getLabel()
        );

        $this->assertSame(
            '50€ de réduction pour les clients fidèles',
            $promotion->getDescription()
        );
    }

public function test_it_can_require_both_minimum_days_and_minimum_price(): void
{
    $promotion = new FixedDiscountPromotion(
        'Remise fidélité',
        '50€ pour les locations longues et coûteuses',
        50,
        minDays: 20,
        minPrice: 500
    );

    $equipment = $this->createEquipment();

    // Moins de 20 jours => promotion non applicable
    $this->assertFalse(
        $promotion->isApplicable($equipment, 19)
    );

    // 20 jours atteints => la promotion est applicable
    // (minPrice sera vérifié au moment du calcul)
    $this->assertTrue(
        $promotion->isApplicable($equipment, 20)
    );

    // Prix insuffisant => aucune remise
    $this->assertSame(
        0.0,
        $promotion->calculateDiscount(
            $equipment,
            20,
            499.0
        )
    );

    // Conditions remplies => remise de 50€
    $this->assertSame(
        50.0,
        $promotion->calculateDiscount(
            $equipment,
            20,
            1000.0
        )
    );
}


}
