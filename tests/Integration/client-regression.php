<?php
/** Tests autonomes : php tests/Integration/client-regression.php (PDO SQLite requis). */
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/../../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require_once $file;
    }
});
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

use App\Core\Database\Database;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Repository\Criteria\Criteria;
use App\Controller\ClientController;
use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Repository\ClientRepositoryInterface;

$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    ++$checks;
}
function payload(Response $response, int $expectedStatus = 200): array {
    $reflection = new ReflectionClass($response);
    check($reflection->getProperty('statusCode')->getValue($response) === $expectedStatus, 'Statut HTTP incorrect');
    return json_decode($reflection->getProperty('content')->getValue($response), true, 512, JSON_THROW_ON_ERROR);
}
class TestRequest extends Request {
    public function __construct(private array $body = [], private array $params = []) {}
    public function toArray(): array { return $this->body; }
    public function get(string $key, mixed $default = null): mixed { return $this->params[$key] ?? $default; }
}

$db = new Database('sqlite::memory:', '', '');
$pdo = (new ReflectionClass($db))->getProperty('pdo')->getValue($db);
$pdo->sqliteCreateFunction('CONCAT', static fn(...$parts) => implode('', $parts));
$db->execute('CREATE TABLE clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT, first_name TEXT NOT NULL, last_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE COLLATE NOCASE, phone TEXT, company TEXT, address TEXT,
    city TEXT, postal_code TEXT, created_at TEXT, updated_at TEXT
)');
$db->execute('CREATE TABLE rentals (
    id INTEGER PRIMARY KEY AUTOINCREMENT, client_id INTEGER, status TEXT,
    total_price REAL, created_at TEXT
)');
$repository = new ClientRepository($db);
$controller = new ClientController($repository);
check($repository instanceof ClientRepositoryInterface, 'Interface du repository');
check($repository->getStatistics()['companies'] == 0, 'Statistiques vides');

$client = new Client(' Élodie ', ' de La Tour ', ' ELODIE@example.com ', '0612345678', 'Sixtrone');
check($client->getFullName() === 'Élodie de La Tour', 'Nom complet calculé');
check($client->getDisplayName() === 'Sixtrone (Élodie de La Tour)', 'Affichage entreprise');
check((string) $client === $client->getDisplayName(), 'Conversion en chaîne');
check($client->getFirstName() === 'Élodie' && $client->getLastName() === 'de La Tour', 'Getters et casse');
check($client->getEmail() === 'elodie@example.com', 'Normalisation email');
check($client->getPhone() === '06 12 34 56 78', 'Format téléphone');
check($client->toArray()['full_name'] === $client->getFullName(), 'JSON nom complet');
foreach ([['', 'Dupont', 'a@example.com'], ['Jean', ' ', 'a@example.com'], ['Jean', 'Dupont', 'invalide']] as $args) {
    try { new Client(...$args); check(false, 'Constructeur devrait refuser les données'); }
    catch (InvalidArgumentException) { check(true, 'Validation constructeur'); }
}
$repository->save($client);
check($client->getId() === 1, 'ID après insertion');
$loaded = $repository->find(1);
check($loaded->toArray() === $client->toArray(), 'Hydratation avec attributs SQL et dates');
check($repository->findByEmail(' ELODIE@example.com ')->getId() === 1, 'Recherche email');
check($repository->emailExists('ELODIE@example.com'), 'Email existant');
check(!$repository->emailExists('elodie@example.com', 1), 'Exclusion du client courant');
check($repository->find(999) === null, 'ID absent');

$result = payload($controller->create(new TestRequest([
    'first_name' => ' Jean ', 'last_name' => ' Dupont ', 'email' => ' JEAN@example.com ',
    'company' => '', 'city' => 'Paris', 'phone' => '0611223344'
])), 201);
$id = $result['data']['id'];
check($result['data']['full_name'] === 'Jean Dupont', 'Création API');
check($result['data']['email'] === 'jean@example.com', 'Email API normalisé');
payload($controller->create(new TestRequest(['first_name'=>'Jean','last_name'=>'Dupont','email'=>'JEAN@example.com'])), 400);
foreach ([['first_name'=>null], ['first_name'=>'  '], ['last_name'=>[]], ['email'=>null], ['phone'=>[]], ['company'=>123]] as $invalid) {
    $response = payload($controller->update(new TestRequest($invalid), $id), 400);
    check(isset($response['errors'][array_key_first($invalid)]), 'Validation des types et champs nulls');
}
$before = $repository->find($id)->getCreatedAt();
$result = payload($controller->update(new TestRequest([
    'first_name'=>' Jeannine ', 'last_name'=>' Martin ', 'email'=>' MARTIN@example.com ',
    'phone'=>null, 'company'=>null, 'address'=>null, 'city'=>null, 'postal_code'=>null
]), $id));
check($result['data']['full_name'] === 'Jeannine Martin', 'Modification par setters');
check($result['data']['phone'] === null && $result['data']['company'] === null && $result['data']['city'] === null, 'Effacement explicite des champs');
check($repository->find($id)->getCreatedAt() == $before, 'Date de création conservée');
$old = new DateTimeImmutable('2000-01-01');
$loaded = $repository->find($id);
$property = (new ReflectionClass($loaded))->getProperty('updatedAt');
foreach (['setFirstName'=>'Jeannine','setLastName'=>'Martin','setEmail'=>'martin@example.com','setPhone'=>null] as $method=>$value) {
    $property->setValue($loaded, $old); $loaded->$method($value);
    check($loaded->getUpdatedAt() > $old, 'Date actualisée : ' . $method);
}
payload($controller->update(new TestRequest(['email'=>'elodie@example.com']), $id), 400);
payload($controller->update(new TestRequest([]), 999), 404);
payload($controller->show(new TestRequest(), 999), 404);

$db->execute("INSERT INTO rentals (client_id, status, total_price, created_at) VALUES (?, ?, ?, ?)", [1,'active',125,'2026-10-01']);
$db->execute("INSERT INTO rentals (client_id, status, total_price, created_at) VALUES (?, ?, ?, ?)", [1,'returned',75,'2026-10-02']);
$show = payload($controller->show(new TestRequest(), 1));
check($show['data']['rentals_count'] === 2 && is_array($show['data']['rentals'][0]), 'Fiche client avec locations SQL');
$stats = payload($controller->stats(new TestRequest()));
check($stats['data']['top_clients'][0]['id'] == 1, 'Statistiques meilleur client');
check($stats['data']['top_clients'][0]['rentals_count'] == 2 && $stats['data']['top_clients'][0]['total_spent'] == 200, 'Agrégats conservés');
check($repository->findTopClients(1)[0] instanceof Client, 'Méthode historique retourne des entités');
check($repository->findActiveClients()[0]->getId() === 1, 'Clients actifs');
check($repository->searchByName('Élodie de La Tour', 5)[0]->getId() === 1, 'Recherche nom complet');
check($repository->count(['has_company'=>true]) === 1, 'Comptage entreprises');
check($repository->count(['has_company'=>false]) === 1, 'Comptage particuliers');

for ($i=0; $i<105; ++$i) {
    $repository->save(new Client('Client', 'Pagination', "client{$i}@example.com", null, null, null, 'Paris'));
}
$list = payload($controller->list(new TestRequest([], ['search'=>'Pagination','city'=>'Paris','limit'=>20,'offset'=>20])));
check(count($list['data']) === 20 && $list['pagination']['total'] === 105, 'Recherche paginée avec total réel');
$empty = payload($controller->list(new TestRequest([], ['search'=>'Pagination','city'=>'Lyon'])));
check($empty['data'] === [] && $empty['pagination']['total'] === 0, 'Filtres combinés');
$list = payload($controller->list(new TestRequest([], ['email'=>'client10','limit'=>100])));
check(count($list['data']) === 6 && $list['pagination']['total'] === 6, 'Filtrage email partiel');
$list = payload($controller->list(new TestRequest([], ['has_company'=>'true','limit'=>-1,'offset'=>-20])));
check($list['pagination']['limit'] === 1 && $list['pagination']['offset'] === 0 && $list['pagination']['total'] === 1, 'Bornes pagination');
$search = payload($controller->search(new TestRequest([], ['q'=>'Jeannine'])));
check($search['data'][0]['first_name'] === 'Jeannine' && $search['data'][0]['email'] === 'martin@example.com', 'Getters utilisés par la recherche API');
try { $repository->search([], Criteria::create()->orderBy('id; DROP TABLE clients', 'ASC')); check(false, 'Tri SQL invalide accepté'); }
catch (InvalidArgumentException) { check(true, 'Validation tri SQL'); }
$db->execute("INSERT INTO clients (first_name,last_name,email) VALUES ('Sans','Date','sansdate@example.com')");
$withoutDates = $repository->find((int)$db->lastInsertId());
check($withoutDates->toArray()['created_at'] === null, 'Dates SQL nulles');
payload($controller->delete(new TestRequest(), 1), 400);
check($repository->exists(1), 'Suppression bloquée pour locations actives');
payload($controller->delete(new TestRequest(), $id));
check(!$repository->exists($id), 'Suppression client sans locations');
payload($controller->delete(new TestRequest(), $id), 404);
echo "OK : {$checks} vérifications client (PHP, PDO SQLite, contrôleur et repository).\n";
