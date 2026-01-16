# 🎉 Nouvelle fonctionnalité : Gestion de la synchronisation CRM

## Qu'est-ce qui a été ajouté ?

Un système complet de **configuration de la synchronisation** des contacts et événements d'agenda depuis les fichiers Markdown vers Nextcloud.

### Avant cette mise à jour
- ❌ Synchronisation toujours active (pas de contrôle)
- ❌ Utilisateur en dur (l'utilisateur connecté uniquement)
- ❌ Carnet/calendrier automatique (premier trouvé)
- ❌ Pas de possibilité de centraliser les données

### Maintenant disponible
- ✅ **Activation/désactivation** par type (Contacts, Agenda)
- ✅ **Choix de l'utilisateur** cible ou utilisation de l'utilisateur connecté
- ✅ **Choix du carnet** d'adresses et du calendrier cible
- ✅ **Synchronisation centralisée** possible (tous les contacts/événements vers un seul compte)
- ✅ **Interface admin** intuitive avec configuration visuelle
- ✅ **Fallback intelligent** si les ressources configurées n'existent pas

## Comment l'utiliser ?

### En 3 étapes simples :

1. **Accéder aux paramètres**
   - Paramètres → Administration → Paramètres supplémentaires → CRM

2. **Configurer la synchronisation**
   - Activer les types de synchronisation souhaités
   - Choisir les utilisateurs et ressources cibles
   - Enregistrer

3. **Tester**
   - Créer un fichier Markdown avec `Classe: Personne` ou `Classe: Action`
   - Vérifier dans Contacts ou Agenda

📖 **Guide complet :** [QUICKSTART_SYNC.md](QUICKSTART_SYNC.md)

## Cas d'usage principaux

### 🔹 Cas 1 : Synchronisation personnelle
Chaque utilisateur a ses propres contacts et événements dans son compte.

**Configuration :**
- Utilisateur cible : *(laisser vide)*

### 🔹 Cas 2 : Base centralisée
Tous les contacts et événements sont centralisés dans le compte d'un administrateur.

**Configuration :**
- Utilisateur cible : `admin`

### 🔹 Cas 3 : Hybride
Contacts centralisés, événements personnels.

**Configuration :**
- Contacts → Utilisateur : `admin`
- Agenda → Utilisateur : *(laisser vide)*

## Fichiers modifiés

### Code source
- ✅ [lib/Settings/AdminSettings.php](lib/Settings/AdminSettings.php)
- ✅ [lib/Controller/SettingsController.php](lib/Controller/SettingsController.php)
- ✅ [lib/Listener/MarkdownListener.php](lib/Listener/MarkdownListener.php)
- ✅ [templates/admin-settings.php](templates/admin-settings.php)
- ✅ [src/settings/AdminSettings.ts](src/settings/AdminSettings.ts)
- ✅ [appinfo/routes.php](appinfo/routes.php)

### Documentation
- 📄 [SYNC_SETTINGS.md](SYNC_SETTINGS.md) - Documentation complète
- 📄 [QUICKSTART_SYNC.md](QUICKSTART_SYNC.md) - Guide de démarrage rapide
- 📄 [CHANGELOG_SYNC.md](CHANGELOG_SYNC.md) - Détails techniques des modifications
- 📄 [INTERFACE_SCREENSHOT.md](INTERFACE_SCREENSHOT.md) - Aperçu de l'interface

## Paramètres de configuration

| Paramètre | Description | Défaut |
|-----------|-------------|--------|
| `sync_contacts_enabled` | Active la synchro des contacts | Non |
| `sync_contacts_user` | Utilisateur cible pour contacts | (vide) |
| `sync_contacts_addressbook` | Carnet d'adresses cible | `contacts` |
| `sync_calendar_enabled` | Active la synchro de l'agenda | Non |
| `sync_calendar_user` | Utilisateur cible pour agenda | (vide) |
| `sync_calendar_name` | Calendrier cible | `personal` |

## Migration

Aucune action requise ! Les fichiers Markdown existants continueront de fonctionner.

**Par défaut, la synchronisation est désactivée** jusqu'à ce que vous l'activiez dans les paramètres.

## Sécurité

- ✅ Protection CSRF sur tous les endpoints
- ✅ Restriction aux administrateurs (`@AdminRequired`)
- ✅ Validation des données côté serveur
- ✅ Logs détaillés pour audit

## Prochaines améliorations possibles

Selon vos besoins, nous pourrions ajouter :
- 🔄 Synchronisation bidirectionnelle (Nextcloud → Markdown)
- 📋 Support de classes supplémentaires (Institution, Lieu, Événement)
- 🗂️ Gestion de plusieurs carnets/calendriers par type
- 🔀 Mapping personnalisé des champs
- ⏰ Synchronisation programmée (cron)

## Support

- 📖 Documentation détaillée dans [SYNC_SETTINGS.md](SYNC_SETTINGS.md)
- 🚀 Guide rapide dans [QUICKSTART_SYNC.md](QUICKSTART_SYNC.md)
- 🔍 Logs disponibles dans Nextcloud (chercher "MarkdownListener")
- 💬 Questions ? Consultez les fichiers de documentation ou créez une issue

---

**Prêt à démarrer ?** → Consultez [QUICKSTART_SYNC.md](QUICKSTART_SYNC.md) 🚀
