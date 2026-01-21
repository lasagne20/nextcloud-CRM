# Quick Release Guide

Pour créer une release rapidement :

## 0. Configuration initiale (une seule fois)

```bash
# Linux/macOS/Git Bash
./scripts/setup-signing.sh

# OU sur Windows PowerShell
.\scripts\setup-signing.ps1
```

Cela génère les clés de signature et met à jour `info.xml`.

## 1. Préparation (1 minute)

```bash
# Vérifier les tests
npm run test:all

# Mettre à jour la version
make set-version VERSION=0.2.0
```

Ensuite manuellement :
- `package.json` → `"version": "0.2.0"`
- `CHANGELOG.md` → Déplacer `[Unreleased]` vers `[0.2.0] - 2026-XX-XX`

## 2. Commit et Tag (30 secondes)

```bash
git add appinfo/info.xml package.json CHANGELOG.md
git commit -m "Release v0.2.0"
git tag -a v0.2.0 -m "Release version 0.2.0"
git push origin main
git push origin v0.2.0
```

## 3. Créer le package (1 minute)

```bash
# Avec signature (production)
make appstore

# OU sans signature (développement)
make appstore-unsigned
```

Package créé dans : `build/appstore/crm-0.2.0.tar.gz`

## 4. Publier sur GitHub (2 minutes)

1. Aller sur : https://github.com/lasagne20/nextcloud-CRM/releases/new
2. Sélectionner le tag `v0.2.0`
3. Copier les notes du CHANGELOG
4. Uploader `crm-0.2.0.tar.gz` (+ `.sig` si signé)
5. Publier

## 5. Installer/Mettre à jour dans Nextcloud

### Installation manuelle

```bash
# Copier dans Nextcloud
docker cp build/appstore/crm-0.2.0.tar.gz nc_app:/tmp/
docker exec nc_app bash -c "cd /var/www/html/custom_apps && tar -xzf /tmp/crm-0.2.0.tar.gz"

# Activer/Mettre à jour
docker exec nc_app php occ app:enable crm
```

### Mise à jour automatique (nécessite App Store ou custom updater)

Voir [RELEASE.md](RELEASE.md) pour configurer les mises à jour automatiques.

---

**Voir [RELEASE.md](RELEASE.md) pour le guide complet avec signature, App Store, dépannage, etc.**
