# Tests Automatiques - CRM Nextcloud

Ce document explique comment utiliser et maintenir les tests automatiques pour l'application CRM Nextcloud.

## 🎯 Types de Tests

### 1. Tests Unitaires PHP (Backend)
- **Localisation**: `tests/Unit/Controller/`
- **Framework**: PHPUnit
- **Couverture**: Contrôleurs, services et logique métier

### 2. Tests Frontend (TypeScript/JavaScript)
- **Localisation**: `tests/Frontend/`
- **Framework**: Jest + Vue Test Utils
- **Couverture**: Composants, utilitaires et logique client

### 3. Tests d'Intégration
- **Localisation**: `tests/Integration/`
- **Objectif**: Vérifier que les composants fonctionnent ensemble

## 🚀 Exécution des Tests

### Tests Frontend (Jest)
```bash
# Tous les tests frontend
npm run test

# Tests en mode watch (rechargement automatique)
npm run test:watch

# Tests avec couverture de code
npm run test:coverage
```

### Tests PHP (PHPUnit)
```bash
# Installation des dépendances PHP de test
composer install

# Tous les tests PHP
npm run test:php

# Tests PHP avec couverture
npm run test:php:coverage
```

### Tous les Tests
```bash
# Frontend + Backend
npm run test:all
```

## 📋 Structure des Tests

### Tests des Contrôleurs PHP

Chaque contrôleur a son fichier de test correspondant :

- `ApiControllerTest.php` - Tests de l'API REST
- `FileControllerTest.php` - Gestion des fichiers Markdown
- `PageControllerTest.php` - Rendu des pages
- `SettingsControllerTest.php` - Configuration de l'application

### Tests Frontend

- `App.test.ts` - Tests de l'application principale
- `AdminSettings.test.ts` - Interface d'administration
- `setup.ts` - Configuration globale des tests

## 🔧 Configuration

### PHPUnit (`tests/phpunit.xml`)
```xml
- Bootstrap: tests/bootstrap.php
- Source: ../lib
- Tests: tests/Unit
- Coverage: HTML et XML
```

### Jest (`jest.config.js`)
```javascript
- Environment: jsdom
- TypeScript: ts-jest
- Vue: @vue/vue3-jest
- Setup: tests/Frontend/setup.ts
```

## 📊 Couverture de Code

### Génération des Rapports

#### Frontend
```bash
npm run test:coverage
# Rapport généré dans: tests/coverage/
```

#### PHP
```bash
npm run test:php:coverage
# Rapport généré dans: tests/coverage-php/
```

### Objectifs de Couverture

- **Contrôleurs PHP**: > 80%
- **Services PHP**: > 85%
- **Frontend TypeScript**: > 75%
- **Composants Vue**: > 80%

## 🎨 Mocking et Fixtures

### Mocks Nextcloud (Frontend)
```typescript
// Globales mockées dans setup.ts
global.OC.generateUrl()
global.t() // Traductions
global.n() // Pluralisation
```

### Mocks PHP
```php
// Interfaces Nextcloud mockées dans TestCase.php
IRequest, IConfig, IRootFolder, IUserSession
```

## 📝 Écriture de Nouveaux Tests

### Test d'un Nouveau Contrôleur PHP

```php
<?php
namespace OCA\CRM\Tests\Unit\Controller;

use OCA\CRM\Controller\MonNouveauController;
use OCA\CRM\Tests\TestCase;

class MonNouveauControllerTest extends TestCase {
    private MonNouveauController $controller;
    
    protected function setUp(): void {
        parent::setUp();
        // Configuration spécifique
    }
    
    public function testMaMéthode(): void {
        $result = $this->controller->maMéthode();
        $this->assertEquals('expected', $result);
    }
}
```

### Test d'un Composant Frontend

```typescript
import { MonComposant } from '../../src/MonComposant';

describe('MonComposant', () => {
  test('should initialize correctly', () => {
    const composant = new MonComposant();
    expect(composant).toBeDefined();
  });
});
```

## 🐛 Debugging et Troubleshooting

### Problèmes Courants

#### 1. Tests PHP Échouent
```bash
# Vérifier l'autoload
composer dump-autoload

# Vérifier les dépendances
composer install --dev
```

#### 2. Tests Frontend Échouent
```bash
# Vérifier les dépendances
npm install

# Vérifier la configuration TypeScript
npm run type-check
```

#### 3. Mocks Nextcloud
Si des interfaces Nextcloud ne sont pas trouvées :
1. Ajouter les mocks dans `tests/TestCase.php` (PHP)
2. Ajouter les mocks dans `tests/Frontend/setup.ts` (JS)

### Debug Mode

#### Jest
```bash
# Debug avec Node Inspector
node --inspect-brk node_modules/.bin/jest --runInBand --no-cache
```

#### PHPUnit
```bash
# Debug verbose
./vendor/bin/phpunit --debug --verbose
```

## 🔄 CI/CD Integration

### GitHub Actions (exemple)

```yaml
name: Tests
on: [push, pull_request]
jobs:
  php-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: npm run test:php
        
  frontend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup Node
        uses: actions/setup-node@v2
        with:
          node-version: 18
      - name: Install dependencies
        run: npm install
      - name: Run tests
        run: npm run test:coverage
```

## 📚 Ressources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Vue Test Utils](https://vue-test-utils.vuejs.org/)
- [Nextcloud App Development](https://docs.nextcloud.com/server/latest/developer_manual/)

## 📈 Métriques et Reporting

### Dashboards Recommandés

1. **Couverture de Code**: Suivi dans le temps
2. **Temps d'Exécution**: Optimisation des tests
3. **Taux de Succès**: Stabilité des tests
4. **Qualité du Code**: Complexité cyclomatique

### Outils d'Analyse

- **SonarQube** : Analyse qualité globale
- **Codecov** : Couverture de code
- **PHPStan** : Analyse statique PHP
- **ESLint** : Qualité JavaScript/TypeScript