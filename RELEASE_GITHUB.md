# Guide Release GitHub - Étapes Pratiques

## 🎯 Checklist complète pour une release v0.1.0

### ✅ Étape 1 : Vérifier que tout est prêt

```powershell
# 1. Vérifier que tous les tests passent
npm run test:all

# 2. Vérifier qu'il n'y a pas de changements non commités
git status

# 3. Vérifier la branche actuelle
git branch
# Devrait afficher : * master (ou main)
```

### ✅ Étape 2 : Commit et Tag

```powershell
# 1. Vérifier que info.xml, package.json et CHANGELOG.md sont à jour
# - appinfo/info.xml : <version>0.1.0</version> ✅
# - package.json : "version": "0.1.0" ✅
# - CHANGELOG.md : [0.1.0] - 2026-01-21 ✅

# 2. Créer le commit de release (si besoin)
git add appinfo/info.xml package.json CHANGELOG.md
git commit -m "Release v0.1.0"

# 3. Créer le tag Git
git tag -a v0.1.0 -m "Release version 0.1.0 - First stable release with sync features"

# 4. Vérifier le tag
git tag -l
git show v0.1.0

# 5. Pousser vers GitHub
git push origin master
git push origin v0.1.0
```

### ✅ Étape 3 : Créer le package signé

**Sur Windows, vous avez 2 options :**

#### Option A : Via WSL ou Git Bash (recommandé)

```bash
# Depuis Git Bash
cd "/c/Users/leodu/Documents/1 - Pro/Plugin Nextcloud/custom_apps/crm"

# Créer le package signé
make appstore

# Vérifier les fichiers créés
ls -lh build/appstore/
```

#### Option B : Via PowerShell + Docker

```powershell
# Créer le build source
npm install
npm run build

# Utiliser Docker pour créer l'archive et signer
# (nécessite que Docker Desktop soit lancé)
docker run --rm -v ${PWD}:/app -w /app alpine:latest sh -c "
  apk add --no-cache tar gzip openssl bash make rsync
  make appstore
"
```

#### Option C : Manuellement (si make ne fonctionne pas)

```powershell
# 1. Installer les dépendances
npm install
composer install --no-dev

# 2. Build le frontend
npm run build

# 3. Créer la structure
New-Item -ItemType Directory -Force -Path build\appstore, build\source\crm

# 4. Copier les fichiers nécessaires
$exclude = @(
    '.git*', '.vscode', 'node_modules', 'vendor-bin', 'test', 'tests', 
    'docs', 'src', '*.md', 'webpack.config.js', 'tsconfig.json', 
    'jest.config.js', 'playwright.config.ts', '*.ps1', '*.sh', 
    'Makefile', 'composer.json', 'package.json', 'build'
)

robocopy . build\source\crm /E /XD $exclude /XF $exclude /NFL /NDL /NJH /NJS

# 5. Créer l'archive (nécessite 7zip ou tar depuis Git Bash)
cd build\source
tar -czf ..\appstore\crm-0.1.0.tar.gz crm\

# 6. Signer (depuis Git Bash)
cd ../..
bash -c "openssl dgst -sha512 -sign ~/.nextcloud/certificates/crm.key build/appstore/crm-0.1.0.tar.gz | openssl base64 > build/appstore/crm-0.1.0.tar.gz.sig"
```

### ✅ Étape 4 : Vérifier le package

```powershell
# Vérifier que les fichiers existent
Get-ChildItem build\appstore\

# Devrait afficher :
# crm-0.1.0.tar.gz      (le package)
# crm-0.1.0.tar.gz.sig  (la signature)

# Vérifier la taille (devrait être ~500KB-2MB)
(Get-Item build\appstore\crm-0.1.0.tar.gz).Length / 1MB

# Tester l'extraction
New-Item -ItemType Directory -Force -Path build\test-extract
cd build\test-extract
tar -xzf ..\appstore\crm-0.1.0.tar.gz

# Vérifier le contenu
Get-ChildItem crm\ -Recurse | Select-Object FullName

# Devrait contenir UNIQUEMENT :
# - appinfo/
# - css/ (compilé)
# - js/ (compilé)
# - lib/
# - templates/
# - img/
# - vendor/ (production)
# - LICENSE

# Retourner à la racine
cd ..\..
```

### ✅ Étape 5 : Créer la Release GitHub

#### Via l'interface Web GitHub

1. **Aller sur GitHub**
   ```
   https://github.com/lasagne20/nextcloud-CRM/releases/new
   ```

2. **Remplir le formulaire :**
   - **Choose a tag** : Sélectionner `v0.1.0`
   - **Release title** : `Version 0.1.0 - First Stable Release`
   - **Description** : Copier depuis CHANGELOG.md

   ```markdown
   ## 🎉 First Stable Release
   
   ### ✨ Added
   
   - **Array Properties**: Automatic creation of multiple events from a single Markdown file
     - Configuration interface with filter management, title and description formats
     - Support for dynamic variables: `{fieldName}`, `{index}`, `{filename}`
     - Access to root metadata via `_root.FieldName` and content via `_content`
     - Smart duplicate management with unique IDs
   
   - **Enhanced admin interface**: Dark interface with better readability for array properties configuration
   
   - **Contacts & Calendar Synchronization**: Automatic synchronization from Markdown files to Nextcloud
     - Support for multiple sync configurations per type
     - Metadata mapping and filtering
     - Array properties for bulk event creation
   
   - **Workflow filter by metadata**: Create workflow rules based on YAML metadata
   
   - **Comprehensive test suite**: 48 tests covering Unit, Integration, and E2E scenarios
     - PHPUnit tests for backend logic
     - Jest tests for frontend components
     - Playwright tests for end-to-end workflows
   
   ### 🐛 Fixed
   
   - Fixed array properties configuration save (added @CSRFCheck, using FormData)
   - Improved configuration persistence after Nextcloud restart
   - PHP 8.3 compatibility improvements
   
   ### 📦 Installation
   
   Download `crm-0.1.0.tar.gz` and extract into your Nextcloud `custom_apps/` folder.
   
   ### 🔐 Signature
   
   This package is signed with our public key (included in info.xml).
   Signature file: `crm-0.1.0.tar.gz.sig`
   ```

3. **Attacher les fichiers :**
   - Glisser-déposer `build/appstore/crm-0.1.0.tar.gz`
   - Glisser-déposer `build/appstore/crm-0.1.0.tar.gz.sig`

4. **Options :**
   - ☐ Set as a pre-release (laisser décoché pour release stable)
   - ☐ Set as the latest release (cocher ✅)

5. **Publier :**
   - Cliquer sur **"Publish release"**

#### Via GitHub CLI (alternative)

```powershell
# Installer GitHub CLI si nécessaire
# https://cli.github.com/

# Se connecter
gh auth login

# Créer la release
gh release create v0.1.0 `
  --title "Version 0.1.0 - First Stable Release" `
  --notes-file CHANGELOG.md `
  build/appstore/crm-0.1.0.tar.gz `
  build/appstore/crm-0.1.0.tar.gz.sig

# Vérifier
gh release view v0.1.0
```

### ✅ Étape 6 : Vérifier la Release

1. **Vérifier sur GitHub**
   ```
   https://github.com/lasagne20/nextcloud-CRM/releases/tag/v0.1.0
   ```

2. **Vérifier que les fichiers sont téléchargeables**
   ```powershell
   # Tester le téléchargement
   Invoke-WebRequest -Uri "https://github.com/lasagne20/nextcloud-CRM/releases/download/v0.1.0/crm-0.1.0.tar.gz" -OutFile test-download.tar.gz
   
   # Vérifier la taille
   (Get-Item test-download.tar.gz).Length
   
   # Nettoyer
   Remove-Item test-download.tar.gz
   ```

### ✅ Étape 7 : Mise à jour pour Custom App Store (optionnel)

Si vous utilisez un custom app store :

```powershell
# Mettre à jour appstore.json avec la signature
$signature = Get-Content build\appstore\crm-0.1.0.tar.gz.sig -Raw

# Éditer appstore.json
@"
{
  "crm": {
    "0.1.0": {
      "download": "https://github.com/lasagne20/nextcloud-CRM/releases/download/v0.1.0/crm-0.1.0.tar.gz",
      "signature": "$($signature.Trim())",
      "changelog": "https://github.com/lasagne20/nextcloud-CRM/blob/v0.1.0/CHANGELOG.md"
    }
  }
}
"@ | Out-File -FilePath appstore.json -Encoding UTF8

# Commit et push
git add appstore.json
git commit -m "Update appstore.json for v0.1.0"
git push
```

### ✅ Étape 8 : Communication

```powershell
# Créer une annonce (optionnel)
Write-Host @"

🎉 Version 0.1.0 is now available!

Download: https://github.com/lasagne20/nextcloud-CRM/releases/tag/v0.1.0

What's new:
- Contacts & Calendar sync from Markdown files
- Array properties for bulk event creation
- 48 automated tests
- Full PHP 8.3 compatibility

"@
```

## 🔄 Pour les releases futures

```powershell
# 1. Mettre à jour la version
# Éditer manuellement :
# - appinfo/info.xml → <version>0.2.0</version>
# - package.json → "version": "0.2.0"
# - CHANGELOG.md → Ajouter section [0.2.0]

# 2. Tag et push
git add appinfo/info.xml package.json CHANGELOG.md
git commit -m "Release v0.2.0"
git tag -a v0.2.0 -m "Release version 0.2.0"
git push origin master v0.2.0

# 3. Build et release
make appstore
gh release create v0.2.0 --generate-notes build/appstore/crm-0.2.0.tar.gz build/appstore/crm-0.2.0.tar.gz.sig
```

## ⚠️ Dépannage

### "make: command not found"

**Solution :** Utiliser Git Bash ou l'option manuelle PowerShell

### "tar: command not found"

**Solution :** Installer Git for Windows ou utiliser 7-Zip
```powershell
# Avec 7-Zip
& "C:\Program Files\7-Zip\7z.exe" a -ttar build\appstore\crm-0.1.0.tar build\source\crm\
& "C:\Program Files\7-Zip\7z.exe" a -tgzip build\appstore\crm-0.1.0.tar.gz build\appstore\crm-0.1.0.tar
```

### "openssl: command not found"

**Solution :** OpenSSL est dans Git for Windows
```powershell
& "C:\Program Files\Git\usr\bin\openssl.exe" dgst -sha512 -sign ...
```

### Le package est trop gros (>10 MB)

**Problème :** node_modules ou tests inclus

**Solution :**
```powershell
# Vérifier le contenu
tar -tzf build\appstore\crm-0.1.0.tar.gz | more

# Si node_modules présent, refaire le build en excluant correctement
```

## 📚 Ressources

- [RELEASE_QUICK.md](RELEASE_QUICK.md) - Guide rapide
- [RELEASE.md](RELEASE.md) - Guide complet avec App Store
- [SIGNATURE.md](SIGNATURE.md) - Guide de signature détaillé
- [GitHub Releases Documentation](https://docs.github.com/en/repositories/releasing-projects-on-github)
