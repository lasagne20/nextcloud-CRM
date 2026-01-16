# Installation PHP et Composer sur Windows

## Prérequis pour les tests PHP

Les tests PHP nécessitent PHP 8.1+ et Composer. Voici comment les installer:

## Méthode 1: Chocolatey (Recommandé)

Si vous avez [Chocolatey](https://chocolatey.org/install) installé:

```powershell
# Installer PHP
choco install php -y

# Installer Composer
choco install composer -y

# Redémarrer PowerShell puis vérifier
php --version
composer --version
```

## Méthode 2: Installation manuelle

### 1. Installer PHP

1. Télécharger PHP 8.1+ Thread Safe depuis [windows.php.net](https://windows.php.net/download/)
2. Extraire dans `C:\php`
3. Ajouter `C:\php` au PATH:
   - Ouvrir "Modifier les variables d'environnement système"
   - Cliquer sur "Variables d'environnement"
   - Dans "Variables système", sélectionner "Path" et cliquer "Modifier"
   - Ajouter `C:\php`
   - Cliquer OK
4. Copier `C:\php\php.ini-development` vers `C:\php\php.ini`
5. Éditer `php.ini` et activer les extensions:
   ```ini
   extension=mbstring
   extension=openssl
   extension=pdo_sqlite
   extension=fileinfo
   ```

### 2. Installer Composer

1. Télécharger [Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe)
2. Lancer l'installateur (il détectera PHP automatiquement)
3. Suivre les instructions

### 3. Redémarrer PowerShell

Fermer et rouvrir PowerShell pour que le PATH soit mis à jour.

### 4. Vérifier l'installation

```powershell
php --version
composer --version
```

## Installation des dépendances PHP

Une fois PHP et Composer installés:

```powershell
# Installer toutes les dépendances PHP
composer install

# Installer spécifiquement PHPUnit
composer bin phpunit install
```

## Exécuter les tests PHP

```powershell
# Via npm (recommandé)
npm run test:php

# Ou directement avec composer
composer test:unit

# Avec coverage
npm run test:php:coverage
```

## Alternative: Utiliser Docker

Si vous ne voulez pas installer PHP localement, utilisez Docker:

```powershell
# Exécuter les tests dans un container PHP
docker run --rm -v ${PWD}:/app -w /app php:8.2-cli composer install
docker run --rm -v ${PWD}:/app -w /app php:8.2-cli composer bin phpunit install
docker run --rm -v ${PWD}:/app -w /app php:8.2-cli vendor-bin/phpunit/vendor/bin/phpunit tests -c tests/phpunit.xml --colors=always
```

## Dépannage

### "php n'est pas reconnu..."

Le PATH n'est pas configuré correctement. Redémarrez PowerShell ou ajoutez PHP au PATH manuellement.

### "composer n'est pas reconnu..."

Même problème que ci-dessus. Vérifiez que Composer est dans le PATH.

### Extensions PHP manquantes

Éditez `php.ini` et décommentez (retirez le `;` devant) les extensions nécessaires.

### Tests qui échouent

Assurez-vous d'avoir installé toutes les dépendances:
```powershell
composer install --dev
composer bin all install
```
