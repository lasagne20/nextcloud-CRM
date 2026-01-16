# Modifications - Système de paramètres de synchronisation

## Date : 22 décembre 2025

## Résumé

Ajout d'un système complet de gestion de la synchronisation des contacts et agendas via les paramètres d'administration, permettant de configurer :
- L'activation/désactivation de la synchronisation par type (Contacts, Agenda)
- Le choix de l'utilisateur cible
- Le choix du carnet d'adresses ou calendrier cible

## Fichiers modifiés

### 1. Backend PHP

#### `lib/Settings/AdminSettings.php`
- Ajout des paramètres de synchronisation dans le template :
  - `sync_contacts_enabled`, `sync_contacts_user`, `sync_contacts_addressbook`
  - `sync_calendar_enabled`, `sync_calendar_user`, `sync_calendar_name`

#### `lib/Controller/SettingsController.php`
- Nouvelle méthode `saveSyncSettings()` pour enregistrer les paramètres de synchronisation
- Validation et sauvegarde des 6 paramètres de configuration

#### `lib/Listener/MarkdownListener.php`
- Modification de `handle()` pour vérifier les paramètres avant synchronisation
- Modification de `addContact()` pour utiliser l'utilisateur et le carnet configurés
- Modification de `addAction()` pour utiliser l'utilisateur et le calendrier configurés
- Ajout de logs d'avertissement si les ressources cibles n'existent pas
- Fallback vers l'utilisateur connecté si aucun utilisateur cible n'est configuré

#### `appinfo/routes.php`
- Nouvelle route POST `/apps/crm/settings/sync` pour la sauvegarde des paramètres

### 2. Frontend

#### `templates/admin-settings.php`
- Nouvelle section "Synchronisation Contacts & Agenda" avec :
  - Configuration des contacts (activation, utilisateur, carnet)
  - Configuration de l'agenda (activation, utilisateur, calendrier)
  - Bouton de sauvegarde dédié
  - Interface utilisateur avec désactivation visuelle des options

#### `src/settings/AdminSettings.ts`
- Nouvelle classe `SyncSettingsManager` pour gérer les paramètres de synchronisation
- Gestion de l'activation/désactivation dynamique des sections
- Envoi des données au serveur via l'endpoint `/settings/sync`
- Affichage des messages de succès/erreur

#### `js/admin-settings.js` (généré)
- Compilation du TypeScript avec webpack

### 3. Documentation

#### `SYNC_SETTINGS.md` (nouveau)
- Documentation complète de la fonctionnalité
- Exemples de configuration
- Cas d'usage détaillés
- Guide de dépannage

#### `README.md`
- Mise à jour avec la nouvelle fonctionnalité de synchronisation
- Lien vers la documentation détaillée

## Fonctionnalités ajoutées

### Configuration flexible
- ✅ Activation/désactivation par type de synchronisation
- ✅ Choix de l'utilisateur cible (ou utilisation de l'utilisateur connecté)
- ✅ Choix du carnet d'adresses/calendrier cible
- ✅ Fallback intelligent si les ressources n'existent pas

### Interface utilisateur
- ✅ Section dédiée dans les paramètres admin
- ✅ Interface intuitive avec activation/désactivation visuelle
- ✅ Sélection des utilisateurs via dropdown
- ✅ Messages de confirmation et d'erreur

### Sécurité
- ✅ Vérification CSRF sur les endpoints
- ✅ Restriction admin (@AdminRequired)
- ✅ Validation des données côté serveur

## Paramètres de configuration

Les paramètres sont stockés dans la table `oc_appconfig` avec le préfixe `crm` :

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `sync_contacts_enabled` | boolean | false | Active la synchro des contacts |
| `sync_contacts_user` | string | '' | Utilisateur cible pour contacts (vide = utilisateur connecté) |
| `sync_contacts_addressbook` | string | 'contacts' | URI du carnet d'adresses cible |
| `sync_calendar_enabled` | boolean | false | Active la synchro des événements |
| `sync_calendar_user` | string | '' | Utilisateur cible pour agenda (vide = utilisateur connecté) |
| `sync_calendar_name` | string | 'personal' | URI du calendrier cible |

## Tests recommandés

1. **Test de synchronisation des contacts**
   - Créer un fichier Markdown avec `Classe: Personne`
   - Vérifier que le contact apparaît dans le carnet configuré
   - Modifier le fichier et vérifier la mise à jour

2. **Test de synchronisation de l'agenda**
   - Créer un fichier Markdown avec `Classe: Action`
   - Vérifier que l'événement apparaît dans le calendrier configuré

3. **Test des utilisateurs cibles**
   - Configurer un utilisateur spécifique
   - Vérifier que les données sont créées dans le bon compte

4. **Test des fallbacks**
   - Configurer un carnet/calendrier inexistant
   - Vérifier que le système utilise le premier disponible
   - Vérifier les logs d'avertissement

## Migration

Aucune migration de données nécessaire. Les fichiers existants continueront de fonctionner avec les nouveaux paramètres.

**Comportement par défaut (si paramètres non configurés) :**
- Synchronisation désactivée pour contacts et agenda
- Nécessite une activation manuelle dans les paramètres admin

## Notes de déploiement

1. Mettre à jour le code source
2. Recompiler le TypeScript : `npm run build`
3. Configurer les paramètres dans Admin > Paramètres supplémentaires > CRM
4. Tester avec des fichiers Markdown de test

## Améliorations futures possibles

- [ ] Gestion de la synchronisation bidirectionnelle (Nextcloud → Markdown)
- [ ] Support de plusieurs carnets/calendriers par type
- [ ] Interface de mapping personnalisé des champs
- [ ] Synchronisation programmée (cron)
- [ ] Interface de résolution de conflits
- [ ] Support d'autres classes (Événement, Institution, Lieu)
