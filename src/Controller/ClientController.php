<?php
namespace App\Controller;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repository\ClientRepository;
use App\Entity\Client;
use App\Core\Repository\Criteria\Criteria;

class ClientController
{
    public function __construct(
        private ClientRepository $repository
    ) {}

    /**
     * Liste des clients avec filtres et pagination
     */
    public function list(Request $request): Response
    {
        try {
            // ✅ Récupérer les paramètres de filtrage
            $search = $request->get('search');
            $email = $request->get('email');
            $company = $request->get('company');
            $city = $request->get('city');
            $hasCompany = $request->get('has_company');

            // ✅ Construire les filtres correctement
            $filters = [];

            if ($search) {
                $filters['search'] = $search;
            }
            if ($email) {
                $filters['email'] = $email;
            }
            if ($company) {
                $filters['company'] = $company;
            }
            if ($city) {
                $filters['city'] = $city;
            }
            if ($hasCompany !== null) {
                $filters['has_company'] = $hasCompany === 'true';
            }

            // Pagination
            $pagination = Criteria::create()
                ->orderBy('last_name', 'ASC')
                ->orderBy('first_name', 'ASC')
                ->limit((int) $request->get('limit', 20))
                ->offset((int) $request->get('offset', 0));

            // ✅ Si un search est présent, utiliser searchByName
            if (!empty($search)) {
                $clients = $this->repository->searchByName($search, (int) $request->get('limit', 20));
                $total = count($clients);
            } else {
                // Sinon, utiliser la recherche avancée avec les filtres
                $clients = $this->repository->search($filters, $pagination);
                $total = $this->repository->count($filters);
            }

            return (new Response())->json([
                'success' => true,
                'data' => array_map(fn($c) => $c->toArray(), $clients),
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

    /**
     * Affiche un client spécifique
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $client = $this->repository->find($id);

            if (!$client) {
                return (new Response())->json([
                    'error' => 'Client non trouvé'
                ], 404);
            }

            // Récupérer les locations du client
            $rentals = $this->repository->getClientRentals($id);

            return (new Response())->json([
                'success' => true,
                'data' => [
                    'client' => $client->toArray(),
                    'rentals' => array_map(fn($r) => $r->toArray(), $rentals),
                    'rentals_count' => count($rentals)
                ]
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée un nouveau client
     */
    public function create(Request $request): Response
    {
        $data = $request->toArray();

        try {
            // Validation
            $errors = $this->validateClientData($data);
            if (!empty($errors)) {
                return (new Response())->json([
                    'error' => 'Données invalides',
                    'errors' => $errors
                ], 400);
            }

            // Vérifier si l'email existe déjà
            if ($this->repository->emailExists($data['email'])) {
                return (new Response())->json([
                    'error' => 'Cet email est déjà utilisé'
                ], 400);
            }

            // Créer le client
            $client = new Client(
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['company'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['postal_code'] ?? null
            );

            $this->repository->save($client);

            return (new Response())->json([
                'success' => true,
                'data' => $client->toArray(),
                'message' => 'Client créé avec succès'
            ], 201);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Met à jour un client existant
     */
    public function update(Request $request, int $id): Response
    {
        try {
            $client = $this->repository->find($id);

            if (!$client) {
                return (new Response())->json([
                    'error' => 'Client non trouvé'
                ], 404);
            }

            $data = $request->toArray();

            // Validation
            $errors = $this->validateClientData($data, true);
            if (!empty($errors)) {
                return (new Response())->json([
                    'error' => 'Données invalides',
                    'errors' => $errors
                ], 400);
            }

            // Vérifier si l'email existe déjà (pour un autre client)
            if (isset($data['email']) && $this->repository->emailExists($data['email'], $id)) {
                return (new Response())->json([
                    'error' => 'Cet email est déjà utilisé par un autre client'
                ], 400);
            }

            // Mettre à jour les champs
            if (isset($data['first_name'])) {
                $client->firstName = $data['first_name'];
            }
            if (isset($data['last_name'])) {
                $client->lastName = $data['last_name'];
            }
            if (isset($data['email'])) {
                $client->email = $data['email'];
            }
            if (isset($data['phone'])) {
                $client->phone = $data['phone'];
            }
            if (isset($data['company'])) {
                $client->setCompany($data['company']);
            }
            if (isset($data['address'])) {
                $client->setAddress($data['address']);
            }
            if (isset($data['city'])) {
                $client->setCity($data['city']);
            }
            if (isset($data['postal_code'])) {
                $client->setPostalCode($data['postal_code']);
            }

            $this->repository->save($client);

            return (new Response())->json([
                'success' => true,
                'data' => $client->toArray(),
                'message' => 'Client mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Supprime un client
     */
    public function delete(Request $request, int $id): Response
    {
        try {
            if (!$this->repository->exists($id)) {
                return (new Response())->json([
                    'error' => 'Client non trouvé'
                ], 404);
            }

            // Vérifier si le client a des locations actives
            $activeRentals = $this->repository->getActiveRentalsCount($id);
            if ($activeRentals > 0) {
                return (new Response())->json([
                    'error' => 'Impossible de supprimer ce client car il a des locations en cours'
                ], 400);
            }

            $this->repository->delete($id);

            return (new Response())->json([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Recherche des clients
     */
    public function search(Request $request): Response
    {
        try {
            $query = $request->get('q', '');
            $limit = (int) $request->get('limit', 10);

            if (empty($query)) {
                return (new Response())->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $clients = $this->repository->searchByName($query, $limit);

            return (new Response())->json([
                'success' => true,
                'data' => array_map(fn($c) => [
                    'id' => $c->getId(),
                    'full_name' => $c->getDisplayName(),
                    'first_name' => $c->getFirstName(),
                    'last_name' => $c->getLastName(),
                    'email' => $c->getEmail(),
                    'phone' => $c->getPhone(),
                    'company' => $c->getCompany(),
                ], $clients)
            ]);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des clients
     */
    public function stats(Request $request): Response
    {
        try {
            $stats = $this->repository->getStatistics();
            $topClients = $this->repository->findTopClients(5);

            return (new Response())->json([
                'success' => true,
                'data' => [
                    'overview' => $stats,
                    'top_clients' => array_map(fn($c) => [
                        'id' => $c['id'] ?? null,
                        'name' => ($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''),
                        'rentals_count' => $c['rental_count'] ?? 0,
                        'total_spent' => $c['total_spent'] ?? 0,
                    ], $topClients)
                ]
            ]);

        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validation des données client
     */
    private function validateClientData(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        if (!$isUpdate || isset($data['first_name'])) {
            if (empty($data['first_name'] ?? '')) {
                $errors['first_name'] = 'Le prénom est obligatoire';
            } elseif (strlen($data['first_name']) < 2) {
                $errors['first_name'] = 'Le prénom doit faire au moins 2 caractères';
            }
        }

        if (!$isUpdate || isset($data['last_name'])) {
            if (empty($data['last_name'] ?? '')) {
                $errors['last_name'] = 'Le nom est obligatoire';
            } elseif (strlen($data['last_name']) < 2) {
                $errors['last_name'] = 'Le nom doit faire au moins 2 caractères';
            }
        }

        if (!$isUpdate || isset($data['email'])) {
            if (empty($data['email'] ?? '')) {
                $errors['email'] = 'L\'email est obligatoire';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email invalide';
            }
        }

        if (!$isUpdate || isset($data['phone'])) {
            if (!empty($data['phone'] ?? '')) {
                $cleaned = preg_replace('/[^0-9]/', '', $data['phone']);
                if (strlen($cleaned) < 10) {
                    $errors['phone'] = 'Le numéro de téléphone doit faire au moins 10 chiffres';
                }
            }
        }

        return $errors;
    }
}