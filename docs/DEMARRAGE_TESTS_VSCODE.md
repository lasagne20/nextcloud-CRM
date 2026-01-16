# 🔧 Guide de Démarrage Rapide - Tests VS Code

## ✅ Étapes pour Activer les Tests dans VS Code

### 1️⃣ Vérifier l'Extension Jest

**L'extension Jest est déjà installée** : `orta.vscode-jest` ✅

Si elle n'apparaît pas :
1. `Ctrl+Shift+X` (ouvrir Extensions)
2. Rechercher "**Jest**"
3. Installer "**Jest**" by **Orta**

### 2️⃣ Recharger VS Code

**Important** : Après l'installation, rechargez VS Code :
- `Ctrl+Shift+P` → "**Developer: Reload Window**"
- Ou fermer et rouvrir VS Code

### 3️⃣ Ouvrir le Workspace Correct

Ouvrez le fichier workspace fourni :
```
Fichier → Ouvrir Workspace depuis un fichier → crm-tests.code-workspace
```

Ou ouvrez simplement le dossier `custom_apps/crm`

### 4️⃣ Activer le Panneau Testing

1. **Cliquer sur l'icône Testing** (fiole 🧪) dans la barre latérale gauche
2. Ou `Ctrl+Shift+P` → "**Testing: Focus on Test Explorer View**"

### 5️⃣ Initialiser Jest

Dans le panneau Testing, vous devriez voir :
- Un bouton "**Start Jest**" ou "**Enable Jest**"
- Cliquez dessus pour activer

Si rien n'apparaît, dans le terminal :
```bash
npm run test
```
Puis rechargez VS Code.

---

## 🎯 Utilisation des Tests

### Option A : Panneau Testing (Interface Graphique)

Dans le panneau Testing, vous verrez :
```
📁 CRM Tests
  ├── 📁 tests/Frontend
  │   ├── ✅ AdminSettings.test.ts (12 tests)
  │   └── ✅ App.test.ts (9 tests)
```

**Actions** :
- **▶** à côté d'un fichier → Exécuter tous ses tests
- **▶** à côté d'un test → Exécuter ce test uniquement
- **Clic droit** → Debug Test, Run with Coverage...

### Option B : Dans l'Éditeur

Ouvrez un fichier de test (ex: `tests/Frontend/App.test.ts`) :
- Des icônes **▶** apparaissent à gauche de chaque test
- **Clic sur ▶** → Exécuter ce test
- **Résultat** : ✅ ou ❌ affiché en temps réel

### Option C : Barre de Statut

En bas de VS Code :
- **Icône Jest** avec statut
- Cliquez dessus pour options rapides

---

## 🐛 Debugging

### Debug un Test Spécifique

1. **Placer un breakpoint** (F9) dans le code de test
2. **Clic droit** sur le test → "**Debug Test**"
3. Le debugger s'arrête au breakpoint
4. Inspecter variables, call stack...

### Debug depuis le Code

1. Ouvrir le fichier de test
2. `F5` ou `Ctrl+Shift+D` (panneau Debug)
3. Sélectionner "**Debug Jest Test**"
4. Run

---

## 🔄 Si les Tests n'Apparaissent Pas

### Solution 1 : Redémarrer Jest
Dans la palette de commandes (`Ctrl+Shift+P`) :
```
Jest: Restart Jest Runner
```

### Solution 2 : Vérifier la Configuration
Ouvrir `.vscode/settings.json`, vérifier :
```json
{
  "jest.autoRun": "off",
  "jest.runMode": "on-demand"
}
```

### Solution 3 : Logs de Debug
Dans la palette :
```
Jest: Toggle Coverage
Jest: Show Output Channel
```
Vérifier les erreurs dans le canal de sortie.

### Solution 4 : Reinstaller les Dépendances
```bash
npm install
```

### Solution 5 : Réinitialiser Jest
```bash
# Nettoyer le cache
npm run clean
# Réinstaller
npm install
```

---

## ✅ Vérification Rapide

Pour tester que tout fonctionne :

### Dans le Terminal VS Code :
```bash
npm run test
```

Vous devriez voir :
```
Test Suites: 2 passed, 2 total
Tests:       21 passed, 21 total
```

### Dans l'Interface Testing :
1. Ouvrir panneau Testing
2. Cliquer sur **▶ Run All Tests**
3. Voir les résultats s'afficher en temps réel

---

## 📋 Checklist de Démarrage

- [ ] Extension Jest installée (`orta.vscode-jest`)
- [ ] VS Code rechargé
- [ ] Panneau Testing ouvert
- [ ] Tests visibles dans l'arborescence
- [ ] `npm run test` fonctionne dans le terminal
- [ ] Icônes ▶ visibles dans les fichiers de test

---

## 🎨 Personnalisation

### Activer le Mode Watch
Dans `.vscode/settings.json` :
```json
{
  "jest.autoRun": "watch"
}
```
Les tests se relancent automatiquement quand vous modifiez le code.

### Afficher la Couverture
```json
{
  "jest.showCoverageOnLoad": true
}
```
Les lignes de code sont colorées selon leur couverture.

---

## 🆘 Support

Si les tests ne s'affichent toujours pas :

1. **Vérifier la sortie Jest** :
   - `Ctrl+Shift+U` (Output)
   - Sélectionner "**Jest**" dans le dropdown

2. **Vérifier les erreurs** :
   - Panneau Problèmes (`Ctrl+Shift+M`)

3. **Redémarrer complètement** :
   - Fermer VS Code
   - Rouvrir le dossier
   - Attendre que Jest s'initialise (barre de statut)

4. **Lancer manuellement** :
   ```bash
   npm run test:watch
   ```
   Laissez tourner en arrière-plan

---

## 🎉 C'est Prêt !

Une fois configuré :
- Les tests s'affichent dans le panneau Testing
- Vous pouvez les exécuter d'un clic
- Debug intégré disponible
- Couverture de code en temps réel

**Profitez du développement TDD avec VS Code ! 🚀**