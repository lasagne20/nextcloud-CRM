# AIDE-MÉMOIRE RAPIDE - Tests VS Code

## ✅ Configuration Terminée

- Extension Jest : **orta.vscode-jest** ✓
- 21 tests fonctionnels ✓  
- Configuration VS Code ✓
- Workspace créé ✓

---

## 🎯 ÉTAPES DANS VS CODE

### 1️⃣ Ouvrir le Panneau Testing

**Méthode A** : Cliquer sur l'icône **"Testing"** (fiole) dans la barre latérale GAUCHE

**Méthode B** : `Ctrl+Shift+P` → taper "**Testing**" → "**Testing: Focus on Test Explorer View**"

### 2️⃣ Démarrer Jest

Dans le panneau Testing qui s'ouvre :
- Chercher un bouton "**Start Jest**" ou "**Run**"
- Cliquer dessus
- Attendre l'initialisation (barre de statut en bas)

### 3️⃣ Voir les Tests

Vous devriez voir cette arborescence :

```
📁 CRM Tests
  └── 📁 tests/Frontend
      ├── ✅ App.test.ts (9 tests)
      └── ✅ AdminSettings.test.ts (12 tests)
```

### 4️⃣ Exécuter un Test

**Option 1** : Cliquer sur **▶** à côté d'un test

**Option 2** : Clic droit sur un test → "**Run Test**"

**Option 3** : Dans un fichier de test, cliquer sur l'icône **▶** dans la marge gauche

---

## 🔄 SI RIEN N'APPARAÎT

### Solution 1 : Recharger VS Code
`Ctrl+Shift+P` → "**Developer: Reload Window**"

### Solution 2 : Redémarrer Jest
`Ctrl+Shift+P` → "**Jest: Restart Jest Runner**"

### Solution 3 : Vérifier les Logs
`Ctrl+Shift+U` (Output) → Sélectionner "**Jest**" dans la liste déroulante

### Solution 4 : Lancer Manuellement
Dans le terminal intégré :
```bash
npm run test
```

---

## 🎨 INTERFACE VISUELLE

### Où se trouve quoi ?

```
┌─────────────────────────────────────────┐
│  VS Code                                │
├─────────────────────────────────────────┤
│                                         │
│  [🧪] ← CLIQUER ICI (Panneau Testing)  │
│  [📁]                                   │
│  [🔍]                                   │
│  [▶]                                    │
│  [⚙]                                    │
│                                         │
└─────────────────────────────────────────┘
```

### Barre de Statut (en bas)

```
Jest ✓ | 21 tests passed
```

---

## ⌨ RACCOURCIS UTILES

| Raccourci | Action |
|-----------|--------|
| `Ctrl+; A` | Exécuter tous les tests |
| `Ctrl+; L` | Réexécuter dernier test |
| `Ctrl+; T` | Exécuter test sous curseur |
| `Ctrl+Shift+P` | Palette de commandes |

---

## 📁 FICHIERS IMPORTANTS

- `.vscode/settings.json` - Configuration tests
- `jest.config.js` - Config Jest
- `tests/Frontend/*.test.ts` - Vos tests

---

## 🆘 BESOIN D'AIDE ?

1. **Guide détaillé** : [DEMARRAGE_TESTS_VSCODE.md](DEMARRAGE_TESTS_VSCODE.md)
2. **Vérifier que ça marche** : 
   ```bash
   npm run test
   ```
   Devrait afficher : `Tests: 21 passed, 21 total`

---

## ✨ C'EST TOUT !

Une fois le panneau Testing ouvert et Jest démarré, vous pouvez :
- ✅ Exécuter n'importe quel test d'un clic
- 🐛 Debugger avec breakpoints
- 📊 Voir la couverture de code
- 🔄 Mode watch pour auto-refresh

**Bon développement ! 🚀**