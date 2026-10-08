<?php

namespace Tests\Unit\Service\PricingStrategy\Promotion;

use App\Entity\Category;
use App\Entity\Equipment;
use App\Service\PricingStrategy\Promotion\FreeDayPromotion;
use PHPUnit\Framework\TestCase;

class FreeDayPromotionTest extends TestCase
{
    private function createEquipment(string $slug = 'excavator'): Equipment
    {
        $category = new Category(
            'Excavatrice',
            $slug
        );

        return new Equipment(
            'Excavatrice CAT',
            $category,
            100.0
        );
    }

    public function test_it_calculates_free_days_discount(): void
    {
        $promotion = new FreeDayPromotion(
            '1 jour gratuit',
            '1 jour offert pour 5 jours de location',
            5,
            1
        );

        $equipment = $this->createEquipment();

        // 5 jours => 1 jour gratuit => 100€
        $this->assertSame(
            100.0,
            $promotion->calculateDiscount(
                $equipment,
                5,
                500.0
            )
        );

        // 10 jours => 2 jours gratuits => 200€
        $this->assertSame(
            200.0,
            $promotion->calculateDiscount(
                $equipment,
                10,
                1000.0
            )
        );
    }

    public function test_it_does_not_apply_before_required_days(): void
    {
        $promotion = new FreeDayPromotion(
            '1 jour gratuit',
            '1 jour offert pour 5 jours de location',
            5,
            1
        );

        $equipment = $this->createEquipment();

        $this->assertFalse(
            $promotion->isApplicable($equipment, 4)
        );

        $this->assertSame(
            0.0,
            $promotion->calculateDiscount(
                $equipment,
                4,
                400.0
            )
        );
    }

    public function test_it_is_applicable_when_required_days_are_reached(): void
    {
        $promotion = new FreeDayPromotion(
            '1 jour gratuit',
            '1 jour offert pour 5 jours de location',
            5,
            1
        );

        $equipment = $this->createEquipment();

        $this->assertTrue(
            $promotion->isApplicable($equipment, 5)
        );

        $this->assertTrue(
            $promotion->isApplicable($equipment, 10)
        );
    }

    public function test_it_can_be_restricted_to_categories(): void
    {
        $promotion = new FreeDayPromotion(
            '1 jour gratuit',
            '1 jour offert pour les excavatrices',
            5,
            1,
            ['excavator', 'loader']
        );

        $excavator = $this->createEquipment('excavator');
        $loader = $this->createEquipment('loader');
        $bulldozer = $this->createEquipment('bulldozer');

        $this->assertTrue(
            $promotion->isApplicable($excavator, 5)
        );

        $this->assertTrue(
            $promotion->isApplicable($loader, 5)
        );

        $this->assertFalse(
            $promotion->isApplicable($bulldozer, 5)
        );
    }

    public function test_it_returns_zero_for_non_applicable_category(): void
    {
        $promotion = new FreeDayPromotion(
            '1 jour gratuit',
            '1 jour offert pour les excavatrices',
            5,
            1,
            ['excavator']
        );

        $equipment = $this->createEquipment('bulldozer');

        $this->assertSame(
            0.0,
            $promotion->calculateDiscount(
                $equipment,
                10,
                1000.0
            )
        );
    }

    public function test_free_days_never_exceed_rental_days(): void
    {
        $promotion = new FreeDayPromotion(
            'Jours gratuits',
            'Promotion avec jours gratuits',
            1,
            2
        );

        $equipment = $this->createEquipment();

        // 1 jour loué => calcul théorique de 2 jours gratuits,
        // mais la remise est limitée à 1 jour.
        $this->assertSame(
            100.0,
            $promotion->calculateDiscount(
                $equipment,
                1,
                100.0
            )
        );
    }

    public function test_it_returns_label_and_description(): void
    {
        $promotion = new FreeDayPromotion(
            '1 jour gratuit',
            '1 jour offert pour 5 jours de location',
            5,
            1
        );

        $this->assertSame(
            '1 jour gratuit',
            $promotion->getLabel()
        );

        $this->assertSame(
            '1 jour offert pour 5 jours de location',
            $promotion->getDescription()
        );
    }
}
