# 🧪 Guide VS Code - Tests CRM Nextcloud

## 🚀 Installation et Configuration

### 1. Extensions Requises

Installez ces extensions VS Code (VS Code vous proposera automatiquement) :

#### Tests Essentiels
- **Jest** (`ms-vscode.vscode-jest`) - Tests frontend
- **Playwright Test** (`ms-playwright.playwright`) - Tests E2E  
- **PHPUnit** (`emallin.phpunit`) - Tests PHP

#### Support Développement
- **PHP Intelephense** (`bmewburn.vscode-intelephense-client`)
- **Volar** (`Vue.volar`) - Support Vue.js
- **TypeScript** (`ms-vscode.vscode-typescript-next`)

### 2. Installation Dépendances

```bash
# Installer toutes les dépendances de test
npm run test:install
```

## 🎯 Utilisation de l'Interface Tests VS Code

### Panneau Tests (Testing Panel)

1. **Ouvrir le panneau Tests** : 
   - `Ctrl+Shift+P` → "Test: Focus on Test Explorer View"
   - Ou cliquer sur l'icône Tests dans la barre latérale

2. **Structure visible** :
```
📁 CRM Tests
├── 🧪 Jest Tests (Frontend)
│   ├── App.test.ts
│   └── AdminSettings.test.ts
├── 🔧 PHPUnit Tests (Backend)
│   ├── ApiControllerTest.php
│   ├── FileControllerTest.php
│   ├── PageControllerTest.php
│   └── SettingsControllerTest.php
└── 🌐 Playwright Tests (E2E)
    └── crm.spec.ts
```

### Exécution des Tests

#### Méthode 1: Interface Graphique
- **Clic gauche** sur test → Exécuter
- **Clic droit** → Options (Debug, Coverage, etc.)
- **Icônes play** dans l'éditeur (gutter icons)

#### Méthode 2: Raccourcis Clavier
- `Ctrl+; A` - Exécuter tous les tests
- `Ctrl+; L` - Exécuter dernier test
- `Ctrl+; T` - Exécuter test sous curseur
- `Ctrl+; Ctrl+D` - Debug test sous curseur

#### Méthode 3: Command Palette
- `Ctrl+Shift+P` → "Test: Run All Tests"
- `Ctrl+Shift+P` → "Test: Debug Test at Cursor"

## 🔍 Debug des Tests

### Jest (Frontend)
1. Placer breakpoints dans le code
2. Clic droit sur test → "Debug Test"
3. Le debugger s'arrête automatiquement

### PHPUnit (Backend)  
1. Configurer Xdebug dans votre environnement PHP
2. Placer breakpoints
3. `F5` → "Run PHP Tests (PHPUnit)"

### Playwright (E2E)
1. Clic droit sur test E2E
2. "Debug Test" pour mode interactif
3. Ou `F5` → "Debug Playwright Test"

## 📊 Couverture de Code

### Activation Automatique
- Jest affiche la couverture en temps réel
- Lignes colorées : vert (couvert), rouge (non couvert)

### Rapports Complets
```bash
# Via Command Palette
Ctrl+Shift+P → "Tasks: Run Task" → "Run Tests with Coverage"

# Ou directement
npm run test:coverage
```

### Visualisation
- **Frontend**: `tests/coverage/index.html`
- **PHP**: `tests/coverage-php/index.html`

## ⚡ Modes d'Exécution

### Mode Watch (Recommandé pour développement)
```bash
# Via VS Code Tasks
Ctrl+Shift+P → "Tasks: Run Task" → "Run Frontend Tests (Watch)"
```
- Auto-rechargement quand vous modifiez les fichiers
- Feedback instantané

### Mode Coverage
- Activé automatiquement avec Jest
- Lignes de code colorées dans l'éditeur

### Mode Debug
- Breakpoints actifs
- Variables inspectables
- Call stack visible

## 🛠️ Configuration Personnalisée

### Tests Filtres
Dans le panneau Tests, utilisez les filtres :
- 🔍 **Recherche** : Filtrer par nom
- ✅ **Status** : Seulement passed/failed
- 📁 **Dossier** : Par type de test

### Paramètres Workspace
Modifiez `.vscode/settings.json` pour :
- Changer comportement auto-run
- Configurer chemins spécifiques
- Ajuster performance

## 🎨 Indicateurs Visuels

### Dans l'Éditeur
- ✅ **Vert** : Test passé
- ❌ **Rouge** : Test échoué  
- 🟡 **Jaune** : Test en cours
- ⚪ **Gris** : Non exécuté

### Dans l'Explorateur
- **Icônes** colorées par statut
- **Badges** avec nombre de tests
- **Progress bars** pendant exécution

## 🔧 Résolution de Problèmes

### Tests non détectés
1. Vérifier extensions installées
2. Recharger VS Code (`Ctrl+Shift+P` → "Reload Window")  
3. Vérifier chemins dans `.vscode/settings.json`

### Performances lentes
1. Exclure dossiers inutiles (node_modules, vendor)
2. Utiliser filtres dans panneau Tests
3. Désactiver auto-run si nécessaire

### Debug non fonctionnel
1. **Jest** : Vérifier Node.js version
2. **PHPUnit** : Configurer Xdebug
3. **Playwright** : Vérifier navigateurs installés

## 🎯 Workflow Recommandé

### Développement TDD
1. **Écrire test** → Échec (rouge)
2. **Implémenter** → Mode watch actif
3. **Test passe** → Vert
4. **Refactorer** → Tests restent verts

### Avant Commit
```bash
# Via Command Palette
Ctrl+Shift+P → "Tasks: Run Task" → "Run All Tests"
```

### Intégration Continue
- Les tests s'exécutent automatiquement sur GitHub
- Notifications dans VS Code si échecs

## 💡 Astuces Pro

### Raccourcis Utiles
- `Ctrl+Shift+5` : Basculer panneau Tests
- `F12` : Aller à définition depuis test
- `Alt+F12` : Peek définition

### Multi-curseurs dans Tests  
- Sélectionner plusieurs tests
- Exécution groupée
- Debug simultané possible

### Test Snippets
Tapez dans un fichier de test :
- `test` → Template test Jest
- `describe` → Template suite de tests  
- `it` → Template test unitaire

Cette configuration vous donne une interface de test professionnelle intégrée directement dans VS Code ! 🚀