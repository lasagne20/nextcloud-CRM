# Filtrage par métadonnées Markdown dans les flux Nextcloud

## Vue d'ensemble

Cette fonctionnalité permet d'utiliser les métadonnées des fichiers Markdown (frontmatter YAML) comme critères de filtrage dans les flux (workflows) de Nextcloud.

## Configuration

### 1. Activer les flux Nextcloud

Assurez-vous que l'application "Workflow" est installée et activée dans votre instance Nextcloud.

### 2. Créer un flux basé sur les métadonnées Markdown

1. Allez dans **Paramètres** → **Flux**
2. Cliquez sur **Ajouter un nouveau flux**
3. Dans le menu déroulant "Flux disponibles", sélectionnez **Markdown Metadata**
4. Choisissez un filtre **Markdown Metadata**

## Format des filtres

Les filtres de métadonnées Markdown utilisent le format suivant :

```
Clé:Valeur
```

### Exemples de filtres

- `Classe:Personne` - Filtrer les fichiers dont la métadonnée "Classe" vaut "Personne"
- `Classe:Action` - Filtrer les fichiers de type "Action"
- `Type:Réunion` - Filtrer les actions de type "Réunion"
- `Statut:En cours` - Filtrer les éléments dont le statut est "En cours"

### Opérateurs disponibles

- **is** (est) - La métadonnée doit correspondre exactement à la valeur
- **is not** (n'est pas) - La métadonnée ne doit pas correspondre à la valeur
- **matches** (correspond à) - La métadonnée correspond à une expression régulière
- **does not match** (ne correspond pas à) - La métadonnée ne correspond pas à une expression régulière

## Exemples de fichiers Markdown

### Personne

```markdown
---
Classe: Personne
Nom: Martin
Prénom: Pierre
Email: pierre.martin@example.com
---

# Pierre Martin

Notes sur le contact...
```

### Action

```markdown
---
Classe: Action
Type: Réunion
Date: 2024-01-15
Statut: En cours
Participants: [Pierre Martin, Sophie Dubois]
---

# Réunion du 15 janvier 2024

Ordre du jour...
```

## Cas d'usage

### 1. Notification lors de la création d'une action

- **Flux** : Markdown Metadata
- **Filtre** : `Classe:Action`
- **Action** : Envoyer une notification
- **Résultat** : Vous recevez une notification chaque fois qu'un fichier Markdown avec la classe "Action" est créé ou modifié

### 2. Conversion automatique en PDF pour les rapports

- **Flux** : Markdown Metadata
- **Filtre** : `Type:Rapport`
- **Action** : Convertir en PDF
- **Résultat** : Tous les fichiers Markdown marqués comme "Rapport" sont automatiquement convertis en PDF

### 3. Partage automatique avec une équipe

- **Flux** : Markdown Metadata
- **Filtre** : `Statut:Terminé`
- **Action** : Partager avec un groupe
- **Résultat** : Les actions terminées sont automatiquement partagées avec l'équipe

## Structure des métadonnées

Les métadonnées sont extraites du frontmatter YAML en début de fichier :

```yaml
---
Clé1: Valeur1
Clé2: Valeur2
Clé3: [Valeur A, Valeur B, Valeur C]
---
```

### Métadonnées prédéfinies suggérées

- **Classe** : Type d'entité (Personne, Action, Institution, Lieu, etc.)
- **Type** : Sous-type (Réunion, Appel, Email, etc.)
- **Statut** : État actuel (En cours, Terminé, Annulé, etc.)
- **Date** : Date de création ou d'échéance
- **Priorité** : Niveau de priorité (Haute, Moyenne, Basse)
- **Tags** : Étiquettes (tableau de valeurs)

## Notes techniques

- Les métadonnées doivent être au format YAML valide
- Le frontmatter doit commencer et finir par `---`
- Les tableaux sont supportés avec la syntaxe `[val1, val2, val3]`
- La comparaison est insensible à la casse pour l'opérateur "is"
- Les expressions régulières sont supportées avec l'opérateur "matches"
