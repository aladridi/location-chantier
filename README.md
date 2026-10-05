# Mini Framework PHP — Location Chantier

Mini-framework PHP développé en **PHP 8.4+**, conçu comme une base légère et extensible pour construire une application web de gestion de location de matériel de chantier.

Le projet privilégie une architecture simple, modulaire et compréhensible, sans dépendre d'un framework PHP complet comme Laravel ou Symfony.

## 🚀 Technologies

* **PHP 8.4+**
* **Composer**
* **PHPUnit 11**
* **vlucas/phpdotenv** — gestion des variables d'environnement
* **Symfony VarDumper** — outils de debug
* Serveur PHP natif pour le développement
* Architecture **PSR-4**

## 📋 Prérequis

Avant de commencer, assurez-vous d'avoir installé :

* PHP >= 8.4
* Composer
* Git
* Une base de données compatible avec le projet si nécessaire

Vérifier les versions :

```bash
php -v
composer -V
git --version
```

## 📦 Installation

Cloner le projet :

```bash
git clone <URL_DU_REPOSITORY>
cd mini-framework
```

Installer les dépendances :

```bash
composer install
```

Créer le fichier d'environnement :

```bash
cp .env.example .env
```

Puis adapter les variables selon votre environnement.

## ⚙️ Configuration

Le projet utilise `phpdotenv` pour charger les variables d'environnement.

Exemple :

```env
APP_ENV=local
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=location_chantier
DB_USERNAME=root
DB_PASSWORD=
```

> Ne jamais versionner le fichier `.env` s'il contient des informations sensibles.

## ▶️ Lancer l'application

Le projet utilise le serveur PHP intégré.

```bash
composer start
```

L'application sera accessible à l'adresse :

```text
http://localhost:8000
```

Vous pouvez également lancer directement :

```bash
php -S localhost:8000 -t public
```

Le dossier `public/` constitue le point d'entrée public de l'application.

## 🗄️ Base de données

Les migrations peuvent être exécutées avec :

```bash
composer migrate
```

Ce script exécute :

```bash
php scripts/migrate.php
```

Pour initialiser la base avec des données de démonstration :

```bash
composer seed
```

Ce script exécute :

```bash
php scripts/seed.php
```

### Workflow recommandé

Après une installation propre :

```bash
composer install
composer migrate
composer seed
composer start
```

## 🧪 Tests

Les tests sont réalisés avec **PHPUnit 11**.

Lancer l'ensemble des tests :

```bash
composer test
```

ou :

```bash
./vendor/bin/phpunit
```

Pour obtenir une sortie plus détaillée :

```bash
./vendor/bin/phpunit --testdox
```

## 🏗️ Architecture

Le projet suit une architecture volontairement légère afin de garder le contrôle sur les composants fondamentaux d'une application PHP.

Structure générale :

```text
.
├── public/
│   └── index.php
│
├── src/
│   ├── Core/
│   │   └── ...
│   ├── Controllers/
│   │   └── ...
│   ├── Models/
│   │   └── ...
│   ├── Services/
│   │   └── ...
│   └── ...
│
├── scripts/
│   ├── migrate.php
│   └── seed.php
│
├── tests/
│   └── ...
│
├── .env.example
├── composer.json
├── composer.lock
└── README.md
```

### `public/`

Contient le point d'entrée de l'application.

Toutes les requêtes HTTP passent par :

```text
public/index.php
```

Cela permet de garder le reste du code de l'application en dehors de la racine publiquement accessible.

### `src/`

Contient le code source de l'application.

Le namespace principal est :

```php
App\
```

avec le mapping PSR-4 :

```json
"App\\": "src/"
```

Le namespace du cœur du framework est :

```php
App\Core\
```

### `src/Core/`

Contient les composants fondamentaux du mini-framework.

Cette partie est indépendante des fonctionnalités métier et peut notamment gérer :

* le routing ;
* les requêtes HTTP ;
* les réponses HTTP ;
* l'injection de dépendances ;
* la configuration ;
* la gestion de la base de données ;
* les middlewares ;
* les exceptions ;
* les helpers du framework.

### `scripts/`

Contient les scripts exécutables depuis la ligne de commande.

Exemples :

```text
scripts/migrate.php
scripts/seed.php
```

## 🔄 Autoloading

Composer est utilisé pour charger automatiquement les classes via **PSR-4**.

Configuration principale :

```json
"autoload": {
  "psr-4": {
    "App\\": "src/",
    "App\\Core\\": "src/Core/"
  }
}
```

Après avoir ajouté ou déplacé des classes, vous pouvez reconstruire l'autoloader :

```bash
composer dump-autoload
```

Les tests utilisent leur propre namespace :

```json
"autoload-dev": {
  "psr-4": {
    "Tests\\": "tests/"
  }
}
```

## 🐛 Debug

Le projet utilise **Symfony VarDumper**.

Exemple :

```php
dump($variable);
```

Ou pour arrêter immédiatement l'exécution :

```php
dd($variable);
```

Cela permet d'inspecter rapidement les données pendant le développement.

## 🔐 Sécurité

Quelques règles importantes :

* Les secrets doivent être stockés dans `.env`.
* Le fichier `.env` ne doit pas être commité.
* Les données provenant des utilisateurs doivent toujours être validées.
* Les requêtes SQL doivent utiliser des paramètres liés.
* Les mots de passe doivent être stockés avec `password_hash()`.
* Les mots de passe ne doivent jamais être enregistrés en clair.
* Les erreurs détaillées ne doivent pas être exposées en production.
* Le dossier `public/` doit être le seul dossier directement accessible depuis le serveur web.

## 🧩 Dépendances

### Production

| Package              | Utilisation                       |
| -------------------- | --------------------------------- |
| `php`                | PHP >= 8.4                        |
| `vlucas/phpdotenv`   | Variables d'environnement         |
| `symfony/var-dumper` | Debug et inspection des variables |

### Développement

| Package           | Utilisation       |
| ----------------- | ----------------- |
| `phpunit/phpunit` | Tests automatisés |

## 🛠️ Commandes disponibles

| Commande                 | Description                       |
| ------------------------ | --------------------------------- |
| `composer install`       | Installe les dépendances          |
| `composer start`         | Lance le serveur PHP local        |
| `composer test`          | Lance les tests PHPUnit           |
| `composer migrate`       | Exécute les migrations            |
| `composer seed`          | Insère les données initiales      |
| `composer dump-autoload` | Reconstruit l'autoloader Composer |

## 💻 Développement

Pour commencer à développer :

```bash
composer install
cp .env.example .env
composer migrate
composer seed
composer start
```

Puis ouvrir :

```text
http://localhost:8000
```

Pendant le développement, les tests peuvent être exécutés régulièrement avec :

```bash
composer test
```

## 🎯 Objectifs du projet

Ce projet a pour objectif de fournir une base PHP :

* légère ;
* facilement compréhensible ;
* modulaire ;
* testable ;
* extensible ;
* respectant les standards modernes PHP ;
* sans la complexité d'un framework complet.

Il peut servir de base pour une application de **location de matériel de chantier** ou comme projet pédagogique permettant de comprendre les mécanismes internes d'un framework PHP.

## 📌 Philosophie

L'objectif n'est pas de reproduire Laravel ou Symfony, mais de comprendre et maîtriser les briques fondamentales qui composent une application web moderne :

```text
HTTP Request
     ↓
Front Controller
     ↓
Router
     ↓
Middleware
     ↓
Controller
     ↓
Service
     ↓
Repository / Model
     ↓
Database
     ↓
Response
```

Cette approche permet de mieux comprendre ce qui se passe derrière les abstractions proposées par les frameworks PHP modernes.

## 📄 Licence

Ce projet est distribué sous licence à définir.

---

## 👨‍💻 Auteur

**Location Chantier — Mini Framework PHP**

Projet développé en PHP 8.4+ avec une architecture légère et modulaire.
