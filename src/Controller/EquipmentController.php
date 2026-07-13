<?php
namespace App\Controller;

use App\Repository\EquipmentRepository;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Entity\Equipment;
use App\Factory\EquipmentFactory;
use App\Core\Repository\Criteria\Criteria;
use App\Repository\CategoryRepository;
use App\Entity\Category;
use App\Repository\EquipmentImageRepository;
use App\Service\ImageService;



class EquipmentController
{
    public function __construct(
        private EquipmentRepository $repository,
        private CategoryRepository $categoryRepository,
        private ImageService $imageService,
        private EquipmentImageRepository $imageRepository
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
            // ✅ Récupérer la catégorie
            $category = $this->categoryRepository->findBySlug($data['category']);
            if (!$category) {
                return (new Response())->json([
                    'error' => 'Catégorie invalide'
                ], 400);
            }

            $equipment = new Equipment(
                $data['name'],
                $category,
                (float) $data['daily_rate'],
                $data['available'] ?? true,
                null,
                $data['serial_number'] ?? null
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

    // ✅ AJOUT DE LA MÉTHODE stats()
    public function stats(Request $request): Response
    {
        try {
            // Statistiques générales
            $stats = $this->repository->getStatistics();

            // Statistiques par catégorie
            $byCategory = $this->repository->countByCategory();

            // Équipements nécessitant maintenance
            $needsMaintenance = $this->repository->findNeedingMaintenance(90);

            // Équipements disponibles
            $available = $this->repository->findAvailable();

            return (new Response())->json([
                'success' => true,
                'data' => [
                    'total' => (int) ($stats['total'] ?? 0),
                    'available' => (int) ($stats['available'] ?? 0),
                    'rented' => (int) ($stats['rented'] ?? 0),
                    'needs_maintenance' => count($needsMaintenance),
                    'categories' => (int) ($stats['categories'] ?? 0),
                    'avg_daily_rate' => (float) ($stats['avg_daily_rate'] ?? 0),
                    'by_category' => $byCategory,
                ]
            ]);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
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

    /**
     * Récupère les images d'un équipement
     */
    public function getImages(Request $request, int $id): Response
    {
        try {
            $equipment = $this->repository->find($id);

            if (!$equipment) {
                return (new Response())->json([
                    'error' => 'Équipement non trouvé'
                ], 404);
            }

            $images = $this->imageService->getEquipmentImages($id);
            $mainImage = $this->imageService->getEquipmentMainImage($id);

            return (new Response())->json([
                'success' => true,
                'data' => [
                    'images' => array_map(fn($img) => $img->toArray(), $images),
                    'main_image' => $mainImage ? $mainImage->toArray() : null,
                    'total' => count($images)
                ]
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload une image pour un équipement
     */
    public function uploadImage(Request $request, int $id): Response
    {
        try {
            $equipment = $this->repository->find($id);

            if (!$equipment) {
                return (new Response())->json([
                    'error' => 'Équipement non trouvé'
                ], 404);
            }

            $files = $request->getFiles();
            if (empty($files['image'])) {
                return (new Response())->json([
                    'error' => 'Aucune image fournie'
                ], 400);
            }

            $isMain = $request->get('is_main') === 'true' || $this->imageRepository->getCountByEquipment($id) === 0;

            $image = $this->imageService->uploadForEquipment($equipment, $files['image'], $isMain);

            return (new Response())->json([
                'success' => true,
                'data' => $image->toArray(),
                'message' => 'Image uploadée avec succès'
            ], 201);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Définit l'image principale
     */
    public function setMainImage(Request $request, int $id): Response
    {
        try {
            $data = $request->toArray();
            $imageId = $data['image_id'] ?? null;

            if (!$imageId) {
                return (new Response())->json([
                    'error' => 'ID d\'image non fourni'
                ], 400);
            }

            $success = $this->imageService->setMainImage($imageId, $id);

            if (!$success) {
                return (new Response())->json([
                    'error' => 'Impossible de définir l\'image principale'
                ], 400);
            }

            return (new Response())->json([
                'success' => true,
                'message' => 'Image principale mise à jour'
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Réorganise les images
     */
    public function reorderImages(Request $request, int $id): Response
    {
        try {
            $data = $request->toArray();
            $order = $data['order'] ?? [];

            if (empty($order)) {
                return (new Response())->json([
                    'error' => 'Aucun ordre spécifié'
                ], 400);
            }

            $this->imageService->reorderImages($id, $order);

            return (new Response())->json([
                'success' => true,
                'message' => 'Images réorganisées avec succès'
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Supprime une image
     */
    public function deleteImage(Request $request, int $imageId): Response
    {
        try {
            $image = $this->imageRepository->find($imageId);

            if (!$image) {
                return (new Response())->json([
                    'error' => 'Image non trouvée'
                ], 404);
            }

            $this->imageService->deleteImage($image);

            return (new Response())->json([
                'success' => true,
                'message' => 'Image supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}