<?php
namespace App\Controller;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repository\EquipmentRepository;
use App\Repository\CategoryRepository;
use App\Repository\EquipmentImageRepository;
use App\Entity\Equipment;
use App\Entity\EquipmentImage;
use App\Service\ImageService;
use App\Core\Repository\Criteria\Criteria;



class EquipmentController
{
    public function __construct(
        private EquipmentRepository $repository,
        private CategoryRepository $categoryRepository,
        private ImageService $imageService,
        private EquipmentImageRepository $imageRepository
    ) {}

    /**
     * Liste des équipements avec leurs images
     */
    public function list(Request $request): Response
    {
        try {
            $filters = [
                'category' => $request->get('category'),
                'available' => $request->get('available') === 'true' ? true :
                    ($request->get('available') === 'false' ? false : null),
                'min_rate' => $request->get('min_rate'),
                'max_rate' => $request->get('max_rate'),
                'search' => $request->get('search'),
            ];

            $pagination = Criteria::create()
                ->orderBy('name', 'ASC')
                ->limit((int) $request->get('limit', 20))
                ->offset((int) $request->get('offset', 0));

            $equipments = $this->repository->search($filters, $pagination);
            $stats = $this->repository->getStatistics();

            $equipmentsData = [];
            foreach ($equipments as $equipment) {
                $data = $equipment->toArray();

                $mainImage = $this->imageRepository->findMainImage($equipment->getId());
                if ($mainImage) {
                    $data['image_url'] = $mainImage->getUrl('medium');
                    $data['thumbnail_url'] = $mainImage->getThumbnailUrl(); // ✅ Maintenant cette méthode existe
                } else {
                    $data['image_url'] = null;
                    $data['thumbnail_url'] = null;
                }

                $equipmentsData[] = $data;
            }

            return (new Response())->json([
                'success' => true,
                'data' => $equipmentsData,
                'pagination' => [
                    'limit' => (int) $request->get('limit', 20),
                    'offset' => (int) $request->get('offset', 0),
                ],
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
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
            // ✅ Vérifier que l'équipement existe
            $equipment = $this->repository->find($id);

            if (!$equipment) {
                return (new Response())->json([
                    'error' => 'Équipement non trouvé'
                ], 404);
            }

            // ✅ Vérifier si la méthode a des fichiers
            if (!$request->hasFile('image')) {
                return (new Response())->json([
                    'error' => 'Aucune image fournie'
                ], 400);
            }

            // ✅ Récupérer le fichier
            $file = $request->getFile('image');

            // ✅ Vérifier l'erreur d'upload
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors = [
                    UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur',
                    UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée par le formulaire',
                    UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
                    UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
                    UPLOAD_ERR_NO_TMP_DIR => 'Le répertoire temporaire est manquant',
                    UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier sur le disque',
                    UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement',
                ];
                return (new Response())->json([
                    'error' => $errors[$file['error']] ?? 'Erreur inconnue lors du téléchargement'
                ], 400);
            }

            // ✅ Upload de l'image
            $image = $this->imageService->uploadForEquipment($equipment, $file, false);

            // ✅ Retourner la réponse avec l'image correctement chargée
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
     * Upload multiple d'images
     */
    public function uploadMultipleImages(Request $request, int $id): Response
    {
        try {
            $equipment = $this->repository->find($id);

            if (!$equipment) {
                return (new Response())->json([
                    'error' => 'Équipement non trouvé'
                ], 404);
            }

            $files = $request->getFiles();

            if (empty($files['images'])) {
                return (new Response())->json([
                    'error' => 'Aucune image fournie'
                ], 400);
            }

            // ✅ S'assurer que c'est un tableau
            $uploadedFiles = $files['images'];
            if (!isset($uploadedFiles['name'][0])) {
                // Si un seul fichier est uploadé
                $uploadedFiles = [$uploadedFiles];
            } else {
                // Transformer en tableau de fichiers
                $temp = [];
                foreach ($uploadedFiles['name'] as $key => $name) {
                    $temp[] = [
                        'name' => $name,
                        'type' => $uploadedFiles['type'][$key],
                        'tmp_name' => $uploadedFiles['tmp_name'][$key],
                        'error' => $uploadedFiles['error'][$key],
                        'size' => $uploadedFiles['size'][$key],
                    ];
                }
                $uploadedFiles = $temp;
            }

            $results = [];
            $errors = [];

            foreach ($uploadedFiles as $index => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    try {
                        $isMain = ($index === 0 && $this->imageRepository->getCountByEquipment($id) === 0);
                        $image = $this->imageService->uploadForEquipment($equipment, $file, $isMain);
                        $results[] = $image->toArray();
                    } catch (\Exception $e) {
                        $errors[] = [
                            'index' => $index,
                            'error' => $e->getMessage()
                        ];
                    }
                } else {
                    $errors[] = [
                        'index' => $index,
                        'error' => 'Erreur d\'upload'
                    ];
                }
            }

            return (new Response())->json([
                'success' => true,
                'data' => [
                    'uploaded' => $results,
                    'errors' => $errors,
                    'total' => count($results)
                ],
                'message' => count($results) . ' image(s) uploadée(s) avec succès'
            ], 201);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }


    /**
     * ✅ Définit l'image principale
     */
    public function setMainImage(Request $request, int $id): Response
    {
        try {
            // ✅ Vérifier que l'équipement existe
            $equipment = $this->repository->find($id);
            if (!$equipment) {
                return (new Response())->json([
                    'error' => "Équipement #{$id} non trouvé"
                ], 404);
            }

            // ✅ Récupérer l'ID de l'image
            $data = $request->toArray();
            $imageId = $data['image_id'] ?? null;

            if (!$imageId) {
                return (new Response())->json([
                    'error' => 'ID d\'image non fourni'
                ], 400);
            }

            // ✅ Vérifier que l'image existe
            $image = $this->imageRepository->find($imageId);
            if (!$image) {
                return (new Response())->json([
                    'error' => "Image #{$imageId} non trouvée"
                ], 404);
            }


            // ✅ Vérifier que l'image appartient à l'équipement
            $imageEquipmentId = $image->getEquipmentId();

            if ($imageEquipmentId !== $id) {
                return (new Response())->json([
                    'error' => "L'image #{$imageId} n'appartient pas à l'équipement #{$id}"
                ], 400);
            }

            // ✅ Définir l'image comme principale
            $this->imageService->setMainImage($imageId, $id);

            // ✅ Récupérer les images mises à jour
            $mainImage = $this->imageRepository->findMainImage($id);
            $allImages = $this->imageRepository->findByEquipment($id);

            return (new Response())->json([
                'success' => true,
                'data' => [
                    'main_image' => $mainImage ? $mainImage->toArray() : null,
                    'images' => array_map(fn($img) => $img->toArray(), $allImages)
                ],
                'message' => 'Image principale définie avec succès'
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