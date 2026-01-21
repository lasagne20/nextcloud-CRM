# Guide de Release - CRM Nextcloud App

Ce guide explique comment créer une release propre et permettre les mises à jour automatiques via l'interface Nextcloud.

## 📋 Prérequis

### Sur votre machine de développement

1. **Make** installé (Linux/macOS natif, Windows via WSL ou Git Bash)
2. **Node.js** et **npm** pour le build frontend
3. **Composer** pour les dépendances PHP
4. **Git** pour la gestion de versions

### Pour la signature des packages (optionnel mais recommandé)

Si vous voulez que votre app soit mise à jour automatiquement, vous devez signer vos releases :

```bash
# Créer un répertoire pour les certificats
mkdir -p ~/.nextcloud/certificates

# Générer une paire de clés
openssl genrsa -out ~/.nextcloud/certificates/crm.key 4096
openssl rsa -in ~/.nextcloud/certificates/crm.key -pubout -out ~/.nextcloud/certificates/crm.crt

# Afficher la clé publique (à ajouter dans info.xml)
cat ~/.nextcloud/certificates/crm.crt
```

## 🚀 Processus de Release

### 1. Préparer la version

```bash
# Vérifier que tous les tests passent
npm run test:all

# Vérifier qu'il n'y a pas de changements non commités
git status

# Mettre à jour le numéro de version
make set-version VERSION=0.2.0
```

Cela met à jour automatiquement `appinfo/info.xml`. Ensuite, mettez à jour manuellement :
- `package.json` → `"version": "0.2.0"`
- `CHANGELOG.md` → Déplacer les changements de `[Unreleased]` vers `[0.2.0] - 2026-XX-XX`

### 2. Commit et tag

```bash
# Commit des changements de version
git add appinfo/info.xml package.json CHANGELOG.md
git commit -m "Release v0.2.0"

# Créer un tag Git
git tag -a v0.2.0 -m "Release version 0.2.0"

# Pousser vers le dépôt
git push origin main
git push origin v0.2.0
```

### 3. Créer le package

#### Sans signature (développement/test)

```bash
make appstore-unsigned
```

Cela crée : `build/appstore/crm-0.2.0.tar.gz`

#### Avec signature (production)

```bash
make appstore
```

Cela crée :
- `build/appstore/crm-0.2.0.tar.gz`
- `build/appstore/crm-0.2.0.tar.gz.sig`

### 4. Tester le package localement

```bash
# Extraire dans un répertoire temporaire
mkdir -p /tmp/test-crm
cd /tmp/test-crm
tar -xzf /path/to/crm-0.2.0.tar.gz

# Vérifier le contenu
ls -la crm/
# Doit contenir : appinfo/, css/, js/, lib/, templates/, img/
# Ne doit PAS contenir : node_modules/, src/, tests/, docs/, *.md
```

### 5. Publier la release

#### Option A : GitHub Releases (recommandé)

1. Aller sur : `https://github.com/lasagne20/nextcloud-CRM/releases/new`
2. Sélectionner le tag : `v0.2.0`
3. Titre : `Version 0.2.0`
4. Description : Copier depuis `CHANGELOG.md`
5. Attacher les fichiers :
   - `crm-0.2.0.tar.gz`
   - `crm-0.2.0.tar.gz.sig` (si signé)
6. Publier

#### Option B : Nextcloud App Store (pour distribution publique)

1. Créer un compte sur : https://apps.nextcloud.com
2. Enregistrer votre app
3. Uploader la signature publique (`crm.crt`) dans les paramètres de l'app
4. Créer une nouvelle release :
   - Upload `crm-0.2.0.tar.gz`
   - Upload `crm-0.2.0.tar.gz.sig`
   - Remplir les métadonnées

## 🔄 Mise à jour automatique dans Nextcloud

Pour que votre app soit mise à jour automatiquement via l'interface Nextcloud, vous avez **deux options** :

### Option 1 : Via le Nextcloud App Store (recommandé)

Une fois votre app publiée sur https://apps.nextcloud.com, Nextcloud vérifie automatiquement les mises à jour.

**Avantages :**
- Mises à jour automatiques pour tous les utilisateurs
- Visibilité publique de votre app
- Processus standardisé

**Inconvénients :**
- Processus de validation/modération
- Nécessite une signature de package

### Option 2 : Custom App Store (déploiement privé)

Créez un fichier JSON accessible publiquement (ex: sur GitHub Pages) :

```json
{
  "crm": {
    "0.1.0": {
      "download": "https://github.com/lasagne20/nextcloud-CRM/releases/download/v0.1.0/crm-0.1.0.tar.gz",
      "signature": "base64_signature_here"
    },
    "0.2.0": {
      "download": "https://github.com/lasagne20/nextcloud-CRM/releases/download/v0.2.0/crm-0.2.0.tar.gz",
      "signature": "base64_signature_here"
    }
  }
}
```

Ensuite, dans `config/config.php` de votre Nextcloud :

```php
'appstoreurl' => 'https://your-github-username.github.io/nextcloud-appstore.json',
```

**Avantages :**
- Contrôle total sur les déploiements
- Pas de validation externe
- Déploiements privés possibles

**Inconvénients :**
- Infrastructure à maintenir
- Pas de visibilité publique

## 🔐 Signature des packages

La signature est **obligatoire** pour les mises à jour automatiques.

### Générer la signature (déjà fait par `make appstore`)

```bash
openssl dgst -sha512 -sign ~/.nextcloud/certificates/crm.key \
  build/appstore/crm-0.2.0.tar.gz | openssl base64 > \
  build/appstore/crm-0.2.0.tar.gz.sig
```

### Ajouter la clé publique à info.xml

Dans `appinfo/info.xml`, ajoutez (si pas déjà fait) :

```xml
<info>
  <!-- ... autres éléments ... -->
  <screenshot>https://raw.githubusercontent.com/lasagne20/nextcloud-CRM/main/docs/screenshots/interface.png</screenshot>
  <!-- Ajoutez cette section juste avant </info> -->
  <signature>
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA...
(votre clé publique ici, obtenue avec : cat ~/.nextcloud/certificates/crm.crt)
...
-----END PUBLIC KEY-----
  </signature>
</info>
```

## 📦 Contenu du package

Le Makefile exclut automatiquement les fichiers de développement. Le package final contient uniquement :

**Inclus :**
- `appinfo/` - Métadonnées et configuration
- `css/` - Styles compilés
- `js/` - JavaScript compilé
- `lib/` - Code PHP backend
- `templates/` - Templates HTML
- `img/` - Images et icônes
- `vendor/` - Dépendances PHP production
- `LICENSE`
- `README.md` (optionnel)

**Exclus :**
- `src/` - Sources TypeScript non compilées
- `tests/` et `test/` - Suites de tests
- `docs/` - Documentation de développement
- `node_modules/` - Dépendances npm
- `vendor-bin/` - Dépendances de développement PHP
- Fichiers de configuration : `.gitignore`, `webpack.config.js`, `tsconfig.json`, etc.
- Scripts de build : `*.ps1`, `*.sh`, `Makefile`

## ✅ Checklist de Release

Avant de publier une release :

- [ ] Tous les tests passent (`npm run test:all`)
- [ ] Le code est commité et poussé sur `main`
- [ ] La version est mise à jour dans `appinfo/info.xml`, `package.json` et `CHANGELOG.md`
- [ ] Le CHANGELOG décrit clairement les changements
- [ ] Le package est créé (`make appstore`)
- [ ] Le package a été testé localement
- [ ] Le tag Git est créé et poussé
- [ ] La release GitHub est créée avec les assets
- [ ] (Optionnel) L'app est publiée sur le Nextcloud App Store

## 🐛 Dépannage

### Erreur "Package signature invalid"

**Cause :** La clé publique dans `info.xml` ne correspond pas à la clé privée utilisée pour signer.

**Solution :**
```bash
# Vérifier que la clé publique dans info.xml correspond
cat ~/.nextcloud/certificates/crm.crt

# Régénérer la signature
make appstore
```

### Erreur "No such file or directory: vendor/autoload.php"

**Cause :** Les dépendances PHP ne sont pas installées.

**Solution :**
```bash
composer install --no-dev
```

### Le package contient node_modules/

**Cause :** Les exclusions du Makefile ne fonctionnent pas.

**Solution :**
```bash
# Nettoyer et reconstruire
make clean
make appstore
```

### La mise à jour n'apparaît pas dans Nextcloud

**Causes possibles :**
1. Nextcloud met en cache la liste des apps (attendre 24h ou vider le cache)
2. Le numéro de version dans `info.xml` n'est pas supérieur à l'actuel
3. La signature est invalide ou manquante
4. L'URL de téléchargement n'est pas accessible

**Solutions :**
```bash
# Vider le cache Nextcloud
docker exec nc_app php occ app:list --output=json | jq

# Forcer la vérification des mises à jour
docker exec nc_app php occ app:update --all --showonly

# Vérifier les logs Nextcloud
docker exec nc_app tail -f /var/www/html/data/nextcloud.log
```

## 📚 Ressources

- [Nextcloud App Development](https://docs.nextcloud.com/server/latest/developer_manual/)
- [App Store Documentation](https://nextcloudappstore.readthedocs.io/)
- [Semantic Versioning](https://semver.org/)
- [Keep a Changelog](https://keepachangelog.com/)
