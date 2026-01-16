# Configuration de la Synchronisation CRM

## Vue d'ensemble

Le plugin CRM pour Nextcloud permet de synchroniser automatiquement les contacts et événements d'agenda depuis des fichiers Markdown contenant des métadonnées YAML.

## Accès aux paramètres

1. Connectez-vous à Nextcloud en tant qu'administrateur
2. Allez dans **Paramètres** → **Administration** → **Paramètres supplémentaires**
3. Trouvez la section **CRM**

## Configuration de la synchronisation

### Synchronisation des Contacts (Classe Personne)

Lorsqu'un fichier Markdown avec `Classe: Personne` est créé ou modifié, le système peut automatiquement créer/mettre à jour un contact dans le carnet d'adresses Nextcloud.

**Options de configuration :**

- **Activer la synchronisation** : Cochez cette option pour activer la création automatique de contacts
- **Utilisateur cible** : 
  - Laisser vide → Le contact sera créé dans le carnet de l'utilisateur qui modifie le fichier
  - Choisir un utilisateur spécifique → Tous les contacts seront créés dans le carnet de cet utilisateur
- **Carnet d'adresses cible** : URI du carnet d'adresses (par défaut : `contacts`)

**Exemple de fichier Markdown pour un contact :**

```markdown
---
Classe: Personne
Id: pierre-martin
Email: pierre.martin@example.com
Téléphone: +33 1 23 45 67 89
Portable: +33 6 12 34 56 78
---

# Pierre Martin

Notes sur le contact...
```

### Synchronisation de l'Agenda (Classe Action)

Lorsqu'un fichier Markdown avec `Classe: Action` est créé ou modifié, le système peut automatiquement créer un événement dans le calendrier Nextcloud.

**Options de configuration :**

- **Activer la synchronisation** : Cochez cette option pour activer la création automatique d'événements
- **Utilisateur cible** : 
  - Laisser vide → L'événement sera créé dans l'agenda de l'utilisateur qui modifie le fichier
  - Choisir un utilisateur spécifique → Tous les événements seront créés dans l'agenda de cet utilisateur
- **Calendrier cible** : URI du calendrier (par défaut : `personal`)

**Exemple de fichier Markdown pour une action :**

```markdown
---
Classe: Action
Date: 2025-01-15
---

# Réunion avec client

Description de l'action...
```

## Cas d'usage

### Cas 1 : Synchronisation personnelle

Chaque utilisateur gère ses propres fichiers Markdown et souhaite que ses contacts/événements soient créés dans son propre carnet/agenda.

**Configuration :**
- Utilisateur cible : *laisser vide*
- Résultat : Les contacts/événements sont créés dans le compte de l'utilisateur qui modifie le fichier

### Cas 2 : Synchronisation centralisée

Un administrateur ou un utilisateur désigné centralise tous les contacts et événements dans son compte.

**Configuration :**
- Utilisateur cible : `admin` (ou tout autre utilisateur)
- Résultat : Tous les contacts/événements sont créés dans le compte de cet utilisateur, peu importe qui modifie les fichiers

### Cas 3 : Synchronisation hybride

Les contacts sont centralisés mais les événements restent personnels.

**Configuration :**
- Contacts → Utilisateur cible : `admin`
- Agenda → Utilisateur cible : *laisser vide*

## Logs et dépannage

Les logs de synchronisation sont disponibles dans le fichier de logs Nextcloud :

```bash
tail -f /var/www/nextcloud/data/nextcloud.log
```

Recherchez les entrées avec `MarkdownListener` pour voir les détails de la synchronisation.

**Messages courants :**

- `✓ Contact ajouté directement au carnet de {user}`
- `✓ Contact mis à jour dans le carnet de {user}`
- `✓ Nouvelle action ajoutée dans le calendrier {calendar} pour {user}`
- `⚠ Calendrier '{name}' non trouvé pour {user}, utilisation du premier calendrier disponible`
- `⚠ Carnet '{name}' non trouvé pour {user}, utilisation du premier carnet disponible`

## Notes importantes

1. **Identifiants uniques** : Le champ `Id` dans le YAML doit être unique pour éviter les doublons
2. **Format des dates** : Les dates doivent être au format `YYYY-MM-DD` ou `YYYY-MM-DD HH:MM:SS`
3. **Permissions** : L'utilisateur cible doit exister et avoir les permissions nécessaires
4. **Carnets/Calendriers** : Les URIs doivent correspondre exactement aux noms des carnets/calendriers existants

## Support

Pour toute question ou problème, consultez les logs ou contactez l'administrateur système.
