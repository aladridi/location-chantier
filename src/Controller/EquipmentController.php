<?php
namespace App\Controller;

use App\Repository\EquipmentRepository;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Entity\Equipment;
use App\Entity\Enum\EquipmentCategory;
use App\Factory\EquipmentFactory;
use App\Core\Repository\Criteria\Criteria;

class EquipmentController
{
    public function __construct(
        private EquipmentRepository $repository
    ) {}

    public function list(Request $request): Response
    {
        // Récupérer les paramètres de filtrage
        $filters = [
            'category' => $request->get('category'),
            'available' => $request->get('available') === 'true' ? true : ($request->get('available') === 'false' ? false : null),
            'min_rate' => $request->get('min_rate'),
            'max_rate' => $request->get('max_rate'),
        ];

        // Pagination
        $pagination = Criteria::create()
            ->orderBy('name', 'ASC')
            ->limit($request->get('limit', 20))
            ->offset($request->get('offset', 0));

        $equipments = $this->repository->search($filters, $pagination);
        $stats = $this->repository->getStatistics();

        return (new Response())->json([
            'success' => true,
            'data' => array_map(fn($e) => $e->toArray(), $equipments),
            'pagination' => [
                'limit' => $pagination->getLimit(),
                'offset' => $pagination->getOffset(),
            ],
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $equipment = $this->repository->find($id);

        if (!$equipment) {
            return (new Response())->json([
                'error' => 'Equipment not found'
            ], 404);
        }

        return (new Response())->json([
            'success' => true,
            'data' => $equipment->toArray()
        ]);
    }

    public function create(Request $request): Response
    {
        $data = $request->toArray();

        try {
            $equipment = EquipmentFactory::create(
                $data['name'],
                $data['category'],
                (float) $data['daily_rate'],
                $data['available'] ?? true
            );

            $this->repository->save($equipment);

            return (new Response())->json([
                'success' => true,
                'data' => $equipment->toArray(),
                'message' => 'Equipment created successfully'
            ], 201);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, int $id): Response
    {
        $equipment = $this->repository->find($id);

        if (!$equipment) {
            return (new Response())->json([
                'error' => 'Equipment not found'
            ], 404);
        }

        $data = $request->toArray();

        try {
            // Mise à jour via les hooks
            if (isset($data['name'])) {
                $equipment->name = $data['name'];
            }
            if (isset($data['daily_rate'])) {
                $equipment->dailyRate = (float) $data['daily_rate'];
            }
            if (isset($data['available'])) {
                $equipment->setAvailable((bool) $data['available']);
            }

            $this->repository->save($equipment);

            return (new Response())->json([
                'success' => true,
                'data' => $equipment->toArray(),
                'message' => 'Equipment updated successfully'
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function delete(Request $request, int $id): Response
    {
        if (!$this->repository->exists($id)) {
            return (new Response())->json([
                'error' => 'Equipment not found'
            ], 404);
        }

        try {
            $this->repository->delete($id);

            return (new Response())->json([
                'success' => true,
                'message' => 'Equipment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function statistics(Request $request): Response
    {
        $stats = $this->repository->getStatistics();
        $byCategory = $this->repository->countByCategory();

        return (new Response())->json([
            'success' => true,
            'data' => [
                'overview' => $stats,
                'by_category' => $byCategory,
            ]
        ]);
    }
}