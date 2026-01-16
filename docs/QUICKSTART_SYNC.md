# Guide de démarrage rapide - Synchronisation CRM

## Configuration initiale (5 minutes)

### Étape 1 : Accéder aux paramètres
1. Connectez-vous à Nextcloud en tant qu'**administrateur**
2. Cliquez sur votre **avatar** en haut à droite
3. Sélectionnez **Paramètres d'administration**
4. Dans le menu de gauche, allez dans **Paramètres supplémentaires**
5. Trouvez la section **CRM**

### Étape 2 : Configurer la synchronisation des contacts

#### Option A : Synchronisation personnelle (recommandé pour débuter)
```
☑ Activer la synchronisation automatique des contacts
Utilisateur cible : [laisser vide]
Carnet d'adresses cible : contacts
```

#### Option B : Synchronisation centralisée
```
☑ Activer la synchronisation automatique des contacts
Utilisateur cible : admin
Carnet d'adresses cible : contacts
```

### Étape 3 : Configurer la synchronisation de l'agenda

#### Option A : Synchronisation personnelle (recommandé pour débuter)
```
☑ Activer la synchronisation automatique des actions dans l'agenda
Utilisateur cible : [laisser vide]
Calendrier cible : personal
```

#### Option B : Synchronisation centralisée
```
☑ Activer la synchronisation automatique des actions dans l'agenda
Utilisateur cible : admin
Calendrier cible : personal
```

### Étape 4 : Enregistrer
Cliquez sur **💾 Enregistrer les paramètres de synchronisation**

## Test de la synchronisation

### Test 1 : Créer un contact

1. Dans Nextcloud Files, allez dans votre dossier `vault/Contacts/`
2. Créez un nouveau fichier `Test-Sync.md` avec ce contenu :

```markdown
---
Classe: Personne
Id: test-sync
Email: test@example.com
Téléphone: +33 1 23 45 67 89
---

# Test de Synchronisation

Ce contact est créé pour tester la synchronisation.
```

3. **Sauvegardez** le fichier
4. Allez dans l'application **Contacts** de Nextcloud
5. Vérifiez qu'un nouveau contact "Test-Sync" apparaît ✅

### Test 2 : Créer un événement

1. Dans votre dossier `vault/`, créez un fichier `Test-Event.md` :

```markdown
---
Classe: Action
Date: 2025-12-25
---

# Réunion de test

Événement créé pour tester la synchronisation.
```

2. **Sauvegardez** le fichier
3. Allez dans l'application **Agenda** de Nextcloud
4. Vérifiez qu'un nouvel événement "Test-Event" apparaît le 25 décembre ✅

## Vérification des logs (si problème)

### Méthode 1 : Via l'interface web
1. Paramètres → Administration → Journalisation
2. Recherchez "MarkdownListener"

### Méthode 2 : Via SSH (serveur)
```bash
tail -f /var/www/nextcloud/data/nextcloud.log | grep MarkdownListener
```

### Messages attendus
```
✅ MarkdownListener déclenché.
✅ Fichier écrit : /files/user/vault/Contacts/Test-Sync.md
✅ Metadata extraits: {"Classe":"Personne","Id":"test-sync",...}
✅ Contact ajouté directement au carnet de admin
```

## Problèmes courants

### Le contact/événement n'apparaît pas

**Vérifications :**
1. ✓ La synchronisation est bien **activée** dans les paramètres
2. ✓ Le fichier contient bien `Classe: Personne` ou `Classe: Action`
3. ✓ Le format YAML est correct (pas d'espace avant `---`)
4. ✓ Consultez les logs pour voir les erreurs

### Erreur "Aucun carnet trouvé"

**Solution :**
1. Allez dans l'application **Contacts**
2. Créez un nouveau carnet nommé "Contacts" si nécessaire
3. Retournez dans les paramètres CRM et configurez le bon nom de carnet

### Erreur "Aucun calendrier trouvé"

**Solution :**
1. Allez dans l'application **Agenda**
2. Créez un nouveau calendrier nommé "Personal" si nécessaire
3. Retournez dans les paramètres CRM et configurez le bon nom de calendrier

## Structure de dossiers recommandée

```
vault/
  ├── Contacts/
  │   ├── Pierre-Martin.md
  │   ├── Sophie-Dubois.md
  │   └── Emilie-Rousseau.md
  ├── Actions/
  │   ├── Reunion-Client-A.md
  │   └── Appel-Fournisseur.md
  ├── Institutions/
  └── Lieux/
```

## Prochaines étapes

Une fois la synchronisation fonctionnelle :

1. 📚 Lisez [SYNC_SETTINGS.md](SYNC_SETTINGS.md) pour les configurations avancées
2. 🔄 Configurez les utilisateurs cibles selon vos besoins
3. 📊 Explorez les possibilités de workflow avec les métadonnées
4. 🎨 Personnalisez vos fichiers YAML de configuration des classes

## Support

- 📖 Documentation complète : [SYNC_SETTINGS.md](SYNC_SETTINGS.md)
- 📝 Changelog : [CHANGELOG_SYNC.md](CHANGELOG_SYNC.md)
- 🐛 Problème ? Consultez les logs et créez une issue

---

**Astuce :** Commencez par activer uniquement la synchronisation des contacts, testez, puis activez l'agenda une fois que tout fonctionne bien ! 🚀
