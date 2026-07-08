<?php
namespace App\Controller;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Entity\Client;
use App\Service\RentalService;

class RentalController
{
    public function __construct(
        private RentalService $rentalService
    ) {}

    public function create(Request $request): Response
    {
        $data = $request->toArray();

        try {
            $client = new Client(
                $data['client_first_name'] ?? 'Jean',
                $data['client_last_name'] ?? 'Dupont',
                $data['client_email'] ?? 'jean@email.com'
            );

            $rental = $this->rentalService->rent(
                $client,
                (int) $data['equipment_id'],
                (int) $data['days'],
                $data['strategy'] ?? null // Stratégie optionnelle
            );

            return (new Response())->json([
                'success' => true,
                'data' => [
                    'rental' => $rental->toArray(),
                    'pricing_breakdown' => $rental->getPricingBreakdown(),
                ]
            ], 201);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function estimate(Request $request): Response
    {
        $data = $request->toArray();

        try {
            $estimation = $this->rentalService->estimatePrice(
                (int) $data['equipment_id'],
                (int) $data['days'],
                $data['strategy'] ?? null
            );

            return (new Response())->json([
                'success' => true,
                'data' => $estimation
            ]);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function compare(Request $request): Response
    {
        $data = $request->toArray();

        try {
            // Utiliser le service directement
            $equipment = $this->equipmentRepo->find((int) $data['equipment_id']);
            if (!$equipment) {
                throw new \Exception('Équipement non trouvé');
            }

            $comparison = $this->priceCalculator->compareStrategies(
                $equipment,
                (int) $data['days']
            );

            return (new Response())->json([
                'success' => true,
                'data' => $comparison
            ]);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}