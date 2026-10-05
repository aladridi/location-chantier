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

            $limit = max(1, min(100, (int) $request->get('limit', 20)));
            $offset = max(0, (int) $request->get('offset', 0));

            // Pagination
            $pagination = Criteria::create()
                ->orderBy('last_name', 'ASC')
                ->orderBy('first_name', 'ASC')
                ->orderBy('id', 'ASC')
                ->limit($limit)
                ->offset($offset);

            $clients = $this->repository->search($filters, $pagination);
            $total = $this->repository->count($filters);

            return (new Response())->json([
                'success' => true,
                'data' => array_map(fn($c) => $c->toArray(), $clients),
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
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
                    'rentals' => $rentals,
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
        try {
            $data = $this->normalizeClientData($request->toArray());
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

            $data = $this->normalizeClientData($request->toArray());

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
                $client->setFirstName($data['first_name']);
            }
            if (isset($data['last_name'])) {
                $client->setLastName($data['last_name']);
            }
            if (isset($data['email'])) {
                $client->setEmail($data['email']);
            }
            if (array_key_exists('phone', $data)) {
                $client->setPhone($data['phone']);
            }
            if (array_key_exists('company', $data)) {
                $client->setCompany($data['company']);
            }
            if (array_key_exists('address', $data)) {
                $client->setAddress($data['address']);
            }
            if (array_key_exists('city', $data)) {
                $client->setCity($data['city']);
            }
            if (array_key_exists('postal_code', $data)) {
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
            $topClients = $this->repository->getTopClientStatistics(5);

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
    private function normalizeClientData(array $data): array
    {
        foreach (['first_name', 'last_name', 'email', 'phone', 'company', 'address', 'city', 'postal_code'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
                if ($field === 'email') {
                    $data[$field] = strtolower($data[$field]);
                }
            }
        }
        return $data;
    }

    private function validateClientData(array $data, bool $isUpdate = false): array
    {
        $errors = [];
        foreach (['first_name' => 'Le prénom', 'last_name' => 'Le nom', 'email' => "L'email"] as $field => $label) {
            if ($isUpdate && !array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field] ?? null;
            if (!is_string($value) || $value === '') {
                $errors[$field] = $label . ' est obligatoire';
            } elseif ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'Email invalide';
            } elseif ($field !== 'email' && (function_exists('mb_strlen') ? mb_strlen($value) : preg_match_all('/./us', $value)) < 2) {
                $errors[$field] = $label . ' doit faire au moins 2 caractères';
            }
        }
        foreach (['phone', 'company', 'address', 'city', 'postal_code'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && !is_string($data[$field])) {
                $errors[$field] = 'Ce champ doit être une chaîne de caractères ou null';
            }
        }
        if (isset($data['phone']) && is_string($data['phone']) && $data['phone'] !== '') {
            if (strlen(preg_replace('/[^0-9]/', '', $data['phone'])) < 10) {
                $errors['phone'] = 'Le numéro de téléphone doit faire au moins 10 chiffres';
            }
        }
        return $errors;
    }
}
