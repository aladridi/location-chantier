<?php
namespace App\Controller;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repository\CategoryRepository;
use App\Entity\Category;
use App\Core\Repository\Criteria\Criteria;

class CategoryController
{
    public function __construct(
        private CategoryRepository $repository
    ) {}

    public function list(Request $request): Response
    {
        try {
            $filters = [];
            if ($request->get('search')) {
                $filters['search'] = $request->get('search');
            }
            if ($request->get('is_active') !== null) {
                $filters['is_active'] = $request->get('is_active') === 'true';
            }

            $pagination = Criteria::create()
                ->orderBy('display_order', 'ASC')
                ->orderBy('name', 'ASC')
                ->limit((int) $request->get('limit', 20))
                ->offset((int) $request->get('offset', 0));

            $categories = $this->repository->search($filters, $pagination);
            $total = $this->repository->count($filters);

            // Ajouter le comptage des équipements
            $categoriesWithCount = [];
            foreach ($categories as $category) {
                $data = $category->toArray();
                $data['equipment_count'] = $this->getEquipmentCount($category->getSlug());
                $categoriesWithCount[] = $data;
            }

            return (new Response())->json([
                'success' => true,
                'data' => $categoriesWithCount,
                'pagination' => [
                    'total' => $total,
                    'limit' => (int) $request->get('limit', 20),
                    'offset' => (int) $request->get('offset', 0),
                ]
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function active(Request $request): Response
    {
        try {
            $categories = $this->repository->findActive();

            return (new Response())->json([
                'success' => true,
                'data' => array_map(fn($c) => $c->toArray(), $categories)
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
            $category = $this->repository->find($id);

            if (!$category) {
                return (new Response())->json([
                    'error' => 'Catégorie non trouvée'
                ], 404);
            }

            $data = $category->toArray();
            $data['equipment_count'] = $this->getEquipmentCount($category->getSlug());

            return (new Response())->json([
                'success' => true,
                'data' => $data
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
            $errors = $this->validate($data);
            if (!empty($errors)) {
                return (new Response())->json([
                    'error' => 'Données invalides',
                    'errors' => $errors
                ], 400);
            }

            // Vérifier si le slug existe déjà
            if ($this->repository->findBySlug($data['slug'])) {
                return (new Response())->json([
                    'error' => 'Ce slug est déjà utilisé'
                ], 400);
            }

            $category = new Category(
                $data['name'],
                $data['slug'],
                $data['description'] ?? null,
                $data['icon'] ?? null,
                $data['color'] ?? null,
                (float) ($data['daily_rate_multiplier'] ?? 1.0),
                (bool) ($data['requires_maintenance'] ?? false),
                (bool) ($data['is_active'] ?? true),
                (int) ($data['display_order'] ?? 0)
            );

            $this->repository->save($category);

            return (new Response())->json([
                'success' => true,
                'data' => $category->toArray(),
                'message' => 'Catégorie créée avec succès'
            ], 201);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, int $id): Response
    {
        try {
            $category = $this->repository->find($id);

            if (!$category) {
                return (new Response())->json([
                    'error' => 'Catégorie non trouvée'
                ], 404);
            }

            $data = $request->toArray();

            $errors = $this->validate($data, true);
            if (!empty($errors)) {
                return (new Response())->json([
                    'error' => 'Données invalides',
                    'errors' => $errors
                ], 400);
            }

            // Vérifier si le slug existe déjà (pour un autre)
            if (isset($data['slug'])) {
                $existing = $this->repository->findBySlug($data['slug']);
                if ($existing && $existing->getId() !== $id) {
                    return (new Response())->json([
                        'error' => 'Ce slug est déjà utilisé'
                    ], 400);
                }
                $category->slug = $data['slug'];
            }

            if (isset($data['name'])) {
                $category->name = $data['name'];
            }
            if (isset($data['description'])) {
                $category->description = $data['description'];
            }
            if (isset($data['icon'])) {
                $category->icon = $data['icon'];
            }
            if (isset($data['color'])) {
                $category->color = $data['color'];
            }
            if (isset($data['daily_rate_multiplier'])) {
                $category->dailyRateMultiplier = (float) $data['daily_rate_multiplier'];
            }
            if (isset($data['requires_maintenance'])) {
                $category->requiresMaintenance = (bool) $data['requires_maintenance'];
            }
            if (isset($data['is_active'])) {
                $category->isActive = (bool) $data['is_active'];
            }
            if (isset($data['display_order'])) {
                $category->displayOrder = (int) $data['display_order'];
            }

            $this->repository->save($category);

            return (new Response())->json([
                'success' => true,
                'data' => $category->toArray(),
                'message' => 'Catégorie mise à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function delete(Request $request, int $id): Response
    {
        try {
            $category = $this->repository->find($id);

            if (!$category) {
                return (new Response())->json([
                    'error' => 'Catégorie non trouvée'
                ], 404);
            }

            // Vérifier si des équipements utilisent cette catégorie
            $count = $this->getEquipmentCount($category->getSlug());
            if ($count > 0) {
                return (new Response())->json([
                    'error' => 'Impossible de supprimer cette catégorie car elle est utilisée par ' . $count . ' équipement(s)'
                ], 400);
            }

            $this->repository->delete($id);

            return (new Response())->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function reorder(Request $request): Response
    {
        try {
            $data = $request->toArray();
            $orders = $data['orders'] ?? [];

            if (empty($orders)) {
                return (new Response())->json([
                    'error' => 'Aucun ordre spécifié'
                ], 400);
            }

            foreach ($orders as $item) {
                if (!isset($item['id']) || !isset($item['order'])) {
                    continue;
                }
                $category = $this->repository->find($item['id']);
                if ($category) {
                    $category->displayOrder = (int) $item['order'];
                    $this->repository->save($category);
                }
            }

            return (new Response())->json([
                'success' => true,
                'message' => 'Ordre mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    private function validate(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        if (!$isUpdate || isset($data['name'])) {
            if (empty($data['name'] ?? '')) {
                $errors['name'] = 'Le nom est obligatoire';
            } elseif (strlen($data['name']) < 2) {
                $errors['name'] = 'Le nom doit faire au moins 2 caractères';
            }
        }

        if (!$isUpdate || isset($data['slug'])) {
            if (empty($data['slug'] ?? '')) {
                $errors['slug'] = 'Le slug est obligatoire';
            } elseif (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
                $errors['slug'] = 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets';
            }
        }

        if (!$isUpdate || isset($data['daily_rate_multiplier'])) {
            if (isset($data['daily_rate_multiplier']) && $data['daily_rate_multiplier'] < 0) {
                $errors['daily_rate_multiplier'] = 'Le multiplicateur ne peut pas être négatif';
            }
        }

        return $errors;
    }

    private function getEquipmentCount(string $categorySlug): int
    {
        $sql = "SELECT COUNT(*) as count FROM equipment WHERE category = :slug";
        $result = $this->repository->db->query($sql, ['slug' => $categorySlug]);
        return (int) ($result[0]['count'] ?? 0);
    }
}