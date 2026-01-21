# Guide de Signature des Packages Nextcloud

## 🔐 Pourquoi signer les packages ?

La signature est **obligatoire** pour permettre les mises à jour automatiques dans Nextcloud. Elle garantit que le package n'a pas été modifié et provient bien du développeur légitime.

## 📋 Prérequis

- **OpenSSL** installé (natif sur Linux/macOS, disponible sur Windows via Git Bash ou WSL)
- Accès à un terminal Bash (ou PowerShell avec adaptations)

## 🔑 Étape 1 : Générer les clés (une seule fois)

### Sur Linux/macOS/Git Bash/WSL

```bash
# Créer le répertoire pour les certificats
mkdir -p ~/.nextcloud/certificates

# Générer la clé privée (4096 bits RSA)
openssl genrsa -out ~/.nextcloud/certificates/crm.key 4096

# Extraire la clé publique
openssl rsa -in ~/.nextcloud/certificates/crm.key -pubout -out ~/.nextcloud/certificates/crm.crt

# Vérifier que les clés ont été créées
ls -lh ~/.nextcloud/certificates/crm.*
```

### Sur Windows PowerShell

```powershell
# Créer le répertoire
New-Item -ItemType Directory -Force -Path "$env:USERPROFILE\.nextcloud\certificates"

# Générer la clé privée
openssl genrsa -out "$env:USERPROFILE\.nextcloud\certificates\crm.key" 4096

# Extraire la clé publique
openssl rsa -in "$env:USERPROFILE\.nextcloud\certificates\crm.key" -pubout -out "$env:USERPROFILE\.nextcloud\certificates\crm.crt"

# Vérifier
Get-ChildItem "$env:USERPROFILE\.nextcloud\certificates\crm.*"
```

### ⚠️ IMPORTANT : Sécuriser la clé privée

```bash
# Protéger la clé privée (lecture seule pour vous uniquement)
chmod 600 ~/.nextcloud/certificates/crm.key

# Ne JAMAIS commiter la clé privée dans Git
# Ne JAMAIS partager la clé privée
# Sauvegarder la clé privée dans un endroit sûr (gestionnaire de mots de passe, coffre-fort)
```

## 📄 Étape 2 : Ajouter la clé publique à info.xml

```bash
# Afficher la clé publique
cat ~/.nextcloud/certificates/crm.crt
```

Copier le contenu complet (y compris les lignes BEGIN/END) et l'ajouter dans `appinfo/info.xml` juste avant `</info>` :

```xml
<info>
  <!-- ... autres éléments ... -->
  
  <signature>
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAyQz5vGHxVqTp9v7L7nVh
... (votre clé complète ici) ...
-----END PUBLIC KEY-----
  </signature>
</info>
```

**Puis commiter ce fichier :**

```bash
git add appinfo/info.xml
git commit -m "Add public key for package signing"
git push
```

## 🏗️ Étape 3 : Signer un package

### Méthode automatique (recommandé) : avec le Makefile

```bash
# Le Makefile détecte automatiquement la clé privée et signe le package
make appstore

# Vérifie dans build/appstore/ :
# - crm-0.1.0.tar.gz
# - crm-0.1.0.tar.gz.sig (signature)
```

### Méthode manuelle

```bash
# 1. Créer le package non signé
make build
cd build/source
tar -czf ../appstore/crm-0.1.0.tar.gz crm

# 2. Signer le package
openssl dgst -sha512 -sign ~/.nextcloud/certificates/crm.key \
  ../appstore/crm-0.1.0.tar.gz | openssl base64 > \
  ../appstore/crm-0.1.0.tar.gz.sig

# 3. Vérifier la signature
cat ../appstore/crm-0.1.0.tar.gz.sig
```

## ✅ Étape 4 : Vérifier la signature

Pour tester que la signature est valide :

```bash
# Extraire la signature en format binaire
base64 -d build/appstore/crm-0.1.0.tar.gz.sig > /tmp/signature.bin

# Vérifier avec la clé publique
openssl dgst -sha512 -verify ~/.nextcloud/certificates/crm.crt \
  -signature /tmp/signature.bin \
  build/appstore/crm-0.1.0.tar.gz

# Si valide, vous verrez : "Verified OK"
```

## 📤 Étape 5 : Publier avec la signature

### Option A : GitHub Releases

Lors de la création d'une release GitHub, **uploader les deux fichiers** :
- `crm-0.1.0.tar.gz` (package)
- `crm-0.1.0.tar.gz.sig` (signature)

### Option B : Nextcloud App Store

1. Connectez-vous sur https://apps.nextcloud.com
2. Créez une nouvelle release
3. Uploadez le `.tar.gz` et le `.sig`
4. L'App Store vérifiera automatiquement la signature avec la clé publique de `info.xml`

### Option C : Custom App Store (appstore.json)

Mettre à jour `appstore.json` avec la signature encodée en base64 :

```bash
# Lire la signature
cat build/appstore/crm-0.1.0.tar.gz.sig
```

```json
{
  "crm": {
    "0.1.0": {
      "download": "https://github.com/lasagne20/nextcloud-CRM/releases/download/v0.1.0/crm-0.1.0.tar.gz",
      "signature": "base64_signature_content_here",
      "changelog": "https://github.com/lasagne20/nextcloud-CRM/blob/v0.1.0/CHANGELOG.md"
    }
  }
}
```

## 🔄 Workflow complet

```bash
# 1. Setup initial (une seule fois)
./scripts/setup-signing.sh

# 2. Pour chaque release
make set-version VERSION=0.2.0
# Éditer package.json et CHANGELOG.md manuellement
git add appinfo/info.xml package.json CHANGELOG.md
git commit -m "Release v0.2.0"
git tag -a v0.2.0 -m "Release version 0.2.0"
git push origin main v0.2.0

# 3. Build et signature automatique
make appstore

# 4. Upload sur GitHub
# Uploader build/appstore/crm-0.2.0.tar.gz et crm-0.2.0.tar.gz.sig
```

## 🛡️ Sécurité

### ✅ À FAIRE

- ✅ Générer une clé de 4096 bits minimum
- ✅ Protéger la clé privée (`chmod 600`)
- ✅ Sauvegarder la clé privée dans un endroit sûr
- ✅ Commiter la clé publique dans `info.xml`
- ✅ Vérifier chaque signature après création
- ✅ Utiliser HTTPS pour héberger les packages

### ❌ À NE JAMAIS FAIRE

- ❌ Commiter la clé privée (`.key`) dans Git
- ❌ Partager la clé privée avec quiconque
- ❌ Stocker la clé privée en clair sur un serveur
- ❌ Réutiliser une clé compromise
- ❌ Oublier de signer un package avant publication

## 🐛 Dépannage

### "No such file: ~/.nextcloud/certificates/crm.key"

**Cause :** La clé privée n'existe pas.

**Solution :**
```bash
# Générer une nouvelle paire de clés
openssl genrsa -out ~/.nextcloud/certificates/crm.key 4096
openssl rsa -in ~/.nextcloud/certificates/crm.key -pubout -out ~/.nextcloud/certificates/crm.crt

# Mettre à jour info.xml avec la nouvelle clé publique
```

### "Package signature is invalid"

**Cause :** La clé publique dans `info.xml` ne correspond pas à la clé privée utilisée pour signer.

**Solution :**
```bash
# Vérifier que la clé publique dans info.xml correspond
cat ~/.nextcloud/certificates/crm.crt

# Comparer avec le contenu de <signature> dans info.xml
# Si différent, mettre à jour info.xml et RE-publier
```

### "Warning: Package not signed"

**Cause :** La clé privée n'a pas été trouvée lors du build.

**Solution :**
```bash
# Vérifier le chemin
ls -lh ~/.nextcloud/certificates/crm.key

# Si le chemin est différent, éditer le Makefile
# Ou créer un symlink
ln -s /chemin/vers/ma/cle.key ~/.nextcloud/certificates/crm.key
```

### Signature différente à chaque build

**Cause :** Normal ! La signature contient un timestamp.

**Solution :** Ce n'est pas un problème. Chaque build génère une signature unique mais valide.

## 📚 Ressources

- [OpenSSL Documentation](https://www.openssl.org/docs/)
- [Nextcloud Code Signing](https://docs.nextcloud.com/server/latest/developer_manual/app_publishing_maintenance/code_signing.html)
- [RSA Cryptography](https://en.wikipedia.org/wiki/RSA_(cryptosystem))

## 🔐 Révocation d'une clé compromise

Si votre clé privée est compromise :

```bash
# 1. Générer une NOUVELLE paire de clés
openssl genrsa -out ~/.nextcloud/certificates/crm-new.key 4096
openssl rsa -in ~/.nextcloud/certificates/crm-new.key -pubout -out ~/.nextcloud/certificates/crm-new.crt

# 2. Remplacer l'ancienne clé
mv ~/.nextcloud/certificates/crm-new.key ~/.nextcloud/certificates/crm.key
mv ~/.nextcloud/certificates/crm-new.crt ~/.nextcloud/certificates/crm.crt

# 3. Mettre à jour info.xml avec la NOUVELLE clé publique
cat ~/.nextcloud/certificates/crm.crt

# 4. Publier une nouvelle version avec la nouvelle signature
make set-version VERSION=0.1.1
# ... commit, tag, build, publish ...

# 5. Informer les utilisateurs de mettre à jour IMMÉDIATEMENT
```

**⚠️ Les anciennes versions signées avec l'ancienne clé resteront valides, mais ne pourront plus être mises à jour.**
