# 🧪 Structure des Tests Automatiques CRM Nextcloud

## 📋 Vue d'Ensemble

J'ai créé une suite complète de tests automatiques pour votre application CRM Nextcloud, couvrant tous les aspects du développement moderne.

## 🏗️ Architecture des Tests

### 1. Tests Unitaires PHP (PHPUnit)
```
tests/
├── phpunit.xml              # Configuration PHPUnit
├── bootstrap.php            # Initialisation tests
├── TestCase.php             # Classe de base
└── Unit/Controller/         # Tests contrôleurs
    ├── ApiControllerTest.php
    ├── FileControllerTest.php
    ├── PageControllerTest.php
    └── SettingsControllerTest.php
```

**Couverture :**
- ✅ Endpoints API REST
- ✅ Gestion des fichiers Markdown
- ✅ Configuration de l'application
- ✅ Gestion des erreurs
- ✅ Validation des données

### 2. Tests Frontend (Jest + TypeScript)
```
tests/Frontend/
├── setup.ts                 # Configuration Jest
├── __mocks__/              # Mocks statiques
├── App.test.ts             # App principale
└── AdminSettings.test.ts   # Interface admin
```

**Couverture :**
- ✅ Intégration Markdown-CRM
- ✅ Gestion des paramètres
- ✅ Mocks Nextcloud/Vue
- ✅ Tests TypeScript

### 3. Tests d'Intégration
```
tests/Integration/
└── CRMWorkflowTest.php     # Tests bout en bout
```

### 4. Tests E2E (Playwright)
```
tests/E2E/
├── crm.spec.ts             # Tests utilisateur
└── playwright.config.ts   # Configuration
```

## 🚀 Scripts de Test Disponibles

### NPM Scripts
```bash
# Tests frontend
npm run test                 # Jest standard
npm run test:watch          # Mode watch
npm run test:coverage       # Avec couverture

# Tests PHP
npm run test:php            # PHPUnit
npm run test:php:coverage   # Couverture PHP

# Tests E2E
npm run test:e2e            # Playwright
npm run test:e2e:headed     # Mode visuel
npm run test:e2e:debug      # Mode debug

# Tous les tests
npm run test:all            # Frontend + PHP + E2E
npm run test:install        # Install toutes dépendances
```

### Scripts Shell
```bash
# Unix/Linux/Mac
./run-tests.sh [frontend|php|all|coverage|install|clean]

# Windows PowerShell
.\run-tests.ps1 [frontend|php|all|coverage|install|clean]
```

## 🔧 Configuration Automatique

### Jest (jest.config.js)
- ✅ Support TypeScript/Vue
- ✅ Mocks Nextcloud globaux
- ✅ Couverture de code
- ✅ JSDOM environment

### PHPUnit (tests/phpunit.xml)
- ✅ Bootstrap personnalisé
- ✅ Couverture HTML/XML
- ✅ Tests parallèles
- ✅ Mocks Nextcloud

### Playwright (playwright.config.ts)
- ✅ Multi-navigateurs
- ✅ Screenshots automatiques
- ✅ Traces de debug
- ✅ Serveur de dev intégré

## 🎯 Tests Créés par Fonctionnalité

### Contrôleur API (ApiController)
- ✅ Endpoint `/api` de base
- ✅ Réponse JSON correcte
- ✅ Accès sans admin
- ✅ Gestion d'erreurs

### Contrôleur de Fichiers (FileController)
- ✅ Liste des fichiers Markdown
- ✅ Lecture de fichiers
- ✅ Sauvegarde de contenu
- ✅ Configuration YAML
- ✅ Cache et performance
- ✅ Gestion d'erreurs

### Contrôleur de Paramètres (SettingsController)
- ✅ Sauvegarde paramètres généraux
- ✅ Récupération configuration
- ✅ Paramètres de synchronisation
- ✅ Validation des données
- ✅ Messages d'erreur

### Application Frontend (App.ts)
- ✅ Interface IApp Markdown-CRM
- ✅ Lecture fichiers config YAML
- ✅ Cache des métadonnées
- ✅ Gestion d'erreurs réseau
- ✅ Configuration par défaut

### Interface Admin (AdminSettings.ts)
- ✅ Initialisation DOM
- ✅ Validation formulaires
- ✅ Gestion des événements
- ✅ Messages de statut

### Tests E2E
- ✅ Chargement de l'application
- ✅ Réponses API
- ✅ Interface d'administration
- ✅ Opérations sur fichiers
- ✅ Gestion d'erreurs

## 📊 Couverture de Code

### Objectifs Configurés
- **PHP Backend** : > 80%
- **TypeScript Frontend** : > 75%
- **Intégration** : > 70%

### Rapports Générés
- `tests/coverage/` : Couverture frontend (HTML)
- `tests/coverage-php/` : Couverture PHP (HTML)
- `coverage.xml` : Format XML pour CI/CD

## 🔄 CI/CD (GitHub Actions)

### Pipeline Configuré (.github/workflows/tests.yml)
- ✅ Tests frontend (Node.js 18/20)
- ✅ Tests PHP (8.1/8.2/8.3)
- ✅ Tests E2E multi-navigateurs
- ✅ Analyse de code qualité
- ✅ Build et validation
- ✅ Upload des artefacts

### Intégrations
- ✅ Codecov pour couverture
- ✅ Artifacts Playwright
- ✅ Cache des dépendances
- ✅ Notifications automatiques

## 📦 Dépendances Ajoutées

### Frontend
```json
{
  "@playwright/test": "^1.40.0",
  "@types/jest": "^29.5.0",
  "@vue/test-utils": "^2.4.0",
  "jest": "^29.5.0",
  "jest-environment-jsdom": "^29.5.0",
  "ts-jest": "^29.1.0"
}
```

### PHP
```json
{
  "phpunit/phpunit": "^10.0"
}
```

## 🚦 Démarrage Rapide

### 1. Installation
```bash
# Installer toutes les dépendances de test
npm run test:install
```

### 2. Premier test
```bash
# Tests rapides frontend
npm run test

# Tests backend
npm run test:php

# Tout en une fois
npm run test:all
```

### 3. Développement
```bash
# Mode watch pour développement
npm run test:watch

# Coverage pour validation
npm run test:coverage
```

## 📚 Documentation Complète

Référez-vous à [tests/README.md](tests/README.md) pour :
- Guide détaillé d'utilisation
- Écriture de nouveaux tests
- Troubleshooting
- Bonnes pratiques
- Métriques et reporting

## 🎉 Résultat

Vous avez maintenant :
- **✅ 13 fichiers de tests** couvrant toutes les fonctionnalités
- **✅ 3 frameworks** de test (PHPUnit, Jest, Playwright)
- **✅ Scripts automatisés** pour toutes les plateformes
- **✅ CI/CD complet** avec GitHub Actions
- **✅ Couverture de code** et reporting
- **✅ Documentation** exhaustive

Les tests sont **prêts à être exécutés** et **facilement extensibles** pour de nouvelles fonctionnalités !