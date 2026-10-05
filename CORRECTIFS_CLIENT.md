# Correctifs de la gestion des clients — 5 octobre 2026

## Causes identifiées

- `Client` accédait à `$this->fullName`, propriété inexistante : nom complet absent, avertissements PHP et erreur de type dans l’affichage du client.
- `getFirstName()`, `getLastName()`, `getEmail()` et `getPhone()` étaient appelés par la recherche mais absents de l’entité.
- La modification écrivait dans les propriétés privées au lieu d’utiliser les setters.
- La fiche client appelait `toArray()` sur des lignes SQL qui sont déjà des tableaux.
- Les statistiques traitaient les entités comme des tableaux et perdaient les agrégats de locations.
- Le total de pagination ne correspondait pas aux filtres ; la recherche ignorait l’offset et les filtres complémentaires. Certains LIMIT utilisaient des paramètres susceptibles d’être liés comme des chaînes par PDO/MySQL.
- L’interface ne chargeait que la première page : un client plus loin dans la liste ne pouvait pas être chargé dans le formulaire. Les erreurs générales n’étaient pas visibles et la modale de suppression dépendait d’une variable Bootstrap globale absente.

## Changements

Nom complet calculé via `getFullName()` ; getters et setter du nom ajoutés ; constructeur et mises à jour utilisent les setters. Les noms conservent leur casse et leurs accents ; l’email est normalisé ; les dates de modification sont actualisées. Les dates SQL nulles sont sérialisées sans erreur.

Validation des champs avant modification : espaces retirés, types vérifiés, noms et email obligatoires. Une valeur `null` permet de vider téléphone, entreprise, adresse, ville et code postal.

Recherche et comptage partagent le même filtrage. Pagination bornée et tri stable par identifiant en dernier critère. Paramètres de recherche distincts et LIMIT entiers. Les agrégats des meilleurs clients restent disponibles via une méthode dédiée.

L’interface charge toutes les pages de la liste et charge un client à modifier directement par son identifiant. Les erreurs de chargement et d’enregistrement sont affichées. Une suppression refusée reste visible dans la modale et peut être retentée.

## Fichiers à remplacer dans le projet existant

- `src/Entity/Client.php`
- `src/Controller/ClientController.php`
- `src/Repository/ClientRepository.php`
- `src/Repository/ClientRepositoryInterface.php`
- `public/assets/js/stores/client.js`
- `public/assets/js/components/ClientForm.vue`
- `public/assets/js/components/ClientList.vue`
- **Tout le dossier `public/dist/`**, y compris `.vite/manifest.json` (dossier caché).

Les ressources de production sont déjà compilées. Remplacer le dossier `public/dist/` complet pour ne pas mélanger les anciens et nouveaux bundles. Il n’est pas nécessaire de reconstruire les ressources pour utiliser cette archive.

Aucune modification de schéma SQL n’est nécessaire pour ces correctifs sur la base existante. Les autres fichiers du projet et les images fournies sont conservés. Garder la configuration `.env`, l’autoload Composer et les dépendances de l’installation existante : ils n’étaient pas inclus dans le ZIP reçu. La migration SQL fournie à l’origine ne constitue pas une installation complète de la base.

Pour reconstruire les ressources ultérieurement, des fichiers `package.json`, `package-lock.json` et `vite.config.js` ont été ajoutés :

```sh
npm ci
npm run build
```

Si votre dépôt contient déjà sa propre configuration npm/Vite, intégrer les changements de dépendances et conserver sa configuration si nécessaire. Bootstrap est importé directement pour la modale client.

## Vérifications

- 72 vérifications PHP passent sur une base SQLite en mémoire avec PDO : création, validation, doublon email, hydratation, modification, effacement des champs, dates, recherche, pagination au-delà de 100 clients, fiche avec locations, statistiques et suppression.
- Syntaxe vérifiée sur les 101 fichiers PHP : aucune erreur.
- Build Vite de production réussi.
- 12 contrôles DOM avec JSDOM sur le bundle de production et une API simulée : liste de 125 clients, édition hors première page, création, doublon, suppression, refus de suppression pour locations actives, client introuvable et erreur de chargement. Aucune erreur JavaScript détectée. Ces contrôles ne constituent pas une validation visuelle dans un navigateur réel.

Les tests PHP sont inclus et peuvent être relancés avec PHP 8.1 ou supérieur et PDO SQLite :

```sh
php tests/Integration/client-regression.php
```

Ces vérifications ne remplacent pas un essai sur votre serveur MySQL et ses données réelles : aucun accès au serveur ou à sa base n’était fourni.
