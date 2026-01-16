# Interface de configuration - Aperçu

## Section Synchronisation Contacts & Agenda

```
┌─────────────────────────────────────────────────────────────────────┐
│ Synchronisation Contacts & Agenda                                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│ Contacts (Classe Personne)                                         │
│ ☑ Activer la synchronisation automatique des contacts              │
│                                                                     │
│   Utilisateur cible pour les contacts                              │
│   ┌────────────────────────────────────────────────┐               │
│   │ -- Utiliser l'utilisateur connecté --        ▼│               │
│   │ admin                                          │               │
│   │ user1                                          │               │
│   │ user2                                          │               │
│   └────────────────────────────────────────────────┘               │
│   Choisir un utilisateur spécifique ou laisser vide pour           │
│   synchroniser avec l'utilisateur qui modifie le fichier           │
│                                                                     │
│   Carnet d'adresses cible                                          │
│   ┌────────────────────────────────────────────────┐               │
│   │ contacts                                       │               │
│   └────────────────────────────────────────────────┘               │
│   URI du carnet d'adresses (généralement "contacts" ou "default")  │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│ Agenda (Classe Action)                                             │
│ ☑ Activer la synchronisation automatique des actions dans l'agenda │
│                                                                     │
│   Utilisateur cible pour l'agenda                                  │
│   ┌────────────────────────────────────────────────┐               │
│   │ -- Utiliser l'utilisateur connecté --        ▼│               │
│   │ admin                                          │               │
│   │ user1                                          │               │
│   │ user2                                          │               │
│   └────────────────────────────────────────────────┘               │
│   Choisir un utilisateur spécifique ou laisser vide pour           │
│   synchroniser avec l'utilisateur qui modifie le fichier           │
│                                                                     │
│   Calendrier cible                                                 │
│   ┌────────────────────────────────────────────────┐               │
│   │ personal                                       │               │
│   └────────────────────────────────────────────────┘               │
│   URI du calendrier (généralement "personal", "work", etc.)        │
│                                                                     │
│   ┌──────────────────────────────────────────────┐                 │
│   │ 💾 Enregistrer les paramètres de synchro    │                 │
│   └──────────────────────────────────────────────┘                 │
│   ✅ Paramètres de synchronisation enregistrés avec succès         │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

## Comportement de l'interface

### Activation/Désactivation

Lorsque la case à cocher est **décochée** :
- Les champs en dessous deviennent **grisés** (opacité 50%)
- Les champs sont **désactivés** (non modifiables)
- Les valeurs sont conservées mais non utilisées

Lorsque la case à cocher est **cochée** :
- Les champs deviennent **actifs** (opacité 100%)
- Les champs sont **modifiables**
- Les valeurs sont utilisées lors de la synchronisation

### Enregistrement

Au clic sur "Enregistrer les paramètres de synchronisation" :
1. Bouton désactivé temporairement
2. Texte change en "⏳ Enregistrement..."
3. Requête POST vers `/apps/crm/settings/sync`
4. Message de succès affiché : "✅ Paramètres enregistrés avec succès"
5. Bouton redevient actif

### Messages d'erreur possibles

```
❌ Erreur lors de l'enregistrement
```

## Accès à l'interface

**Chemin de navigation :**
```
Avatar (haut droite) 
  → Paramètres d'administration 
    → Paramètres supplémentaires 
      → CRM 
        → Section "Synchronisation Contacts & Agenda"
```

## Valeurs par défaut

| Paramètre | Valeur par défaut |
|-----------|-------------------|
| Synchronisation contacts activée | ☐ (désactivée) |
| Utilisateur cible contacts | (vide - utilise l'utilisateur connecté) |
| Carnet d'adresses | "contacts" |
| Synchronisation agenda activée | ☐ (désactivée) |
| Utilisateur cible agenda | (vide - utilise l'utilisateur connecté) |
| Calendrier | "personal" |

## Technologies utilisées

- **Backend :** PHP 8.1+ avec OCP (Nextcloud API)
- **Frontend :** TypeScript compilé avec Webpack
- **Storage :** Configuration stockée dans `oc_appconfig`
- **API :** REST avec protection CSRF
