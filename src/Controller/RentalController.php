<?php
namespace App\Controller;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Entity\Client;
use App\Service\RentalService;
use App\Repository\EquipmentRepository;
use App\Service\PricingStrategy\Calculator\PriceCalculator;

class RentalController
{
    public function __construct(
        private RentalService $rentalService,
        private EquipmentRepository $equipmentRepo,
        private PriceCalculator $priceCalculator
    ) {}

    public function list(Request $request): Response
    {
        try {
            $rentals = $this->rentalService->getAllRentals();

            return (new Response())->json([
                'success' => true,
                'data' => array_map(fn($r) => $r->toArray(), $rentals)
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, int $id): Response
    {
        try {
            $rental = $this->rentalService->getRental($id);

            if (!$rental) {
                return (new Response())->json([
                    'error' => 'Location non trouvée'
                ], 404);
            }

            return (new Response())->json([
                'success' => true,
                'data' => $rental->toArray()
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
                $data['strategy'] ?? null
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

    // ✅ AJOUT DE LA MÉTHODE stats()
    public function stats(Request $request): Response
    {
        try {
            $stats = $this->rentalService->getStatistics();

            return (new Response())->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ AJOUT DE LA MÉTHODE recent()
    public function recent(Request $request): Response
    {
        try {
            $limit = (int) $request->get('limit', 5);
            $rentals = $this->rentalService->getRecentRentals($limit);

            return (new Response())->json([
                'success' => true,
                'data' => array_map(fn($r) => [
                    'id' => $r->getId(),
                    'client_name' => $r->getClient()->getDisplayName(),
                    'equipment_name' => $r->getEquipment()->getName(),
                    'start_date' => $r->getStartDate()->format('Y-m-d H:i:s'),
                    'end_date' => $r->getEndDate()->format('Y-m-d H:i:s'),
                    'date_range' => $r->getFormattedDateRange(),
                    'status' => $r->getStatus()->value,
                    'status_label' => $r->getStatus()->getLabel(),
                    'total_price' => $r->getTotalPrice(),
                ], $rentals)
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ AJOUT DE LA MÉTHODE monthlyRevenue()
    public function monthlyRevenue(Request $request): Response
    {
        try {
            $months = (int) $request->get('months', 12);
            $revenue = $this->rentalService->getMonthlyRevenue($months);

            return (new Response())->json([
                'success' => true,
                'data' => $revenue
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ AJOUT DE LA MÉTHODE return()
    public function return(Request $request, int $id): Response
    {
        try {
            $this->rentalService->returnEquipment($id);

            return (new Response())->json([
                'success' => true,
                'message' => 'Équipement retourné avec succès'
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}