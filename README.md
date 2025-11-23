# CRM Nextcloud avec Markdown-CRM

Application CRM pour Nextcloud qui intègre la bibliothèque Markdown-CRM pour gérer des données structurées dans des fichiers Markdown avec frontmatter YAML.

## ✨ Fonctionnalités

- **📝 CRM basé sur Markdown** : Stockez contacts, institutions, lieux et entités personnalisées sous forme de fichiers Markdown
- **🎨 Affichage enrichi des métadonnées** : Interface interactive avec onglets, listes déroulantes, boutons multi-sélection et notations par étoiles
- **💾 Sauvegarde automatique** : Persistance automatique lors des modifications (debounce de 300ms)
- **⚡ Optimisé pour la performance** : Mise en cache des métadonnées (TTL de 5 secondes) pour minimiser les lectures de fichiers
- **🎯 Classes personnalisables** : Définissez vos propres types d'entités via des fichiers de configuration YAML
- **📊 Vues dynamiques** : Affichage en ligne, onglets, pliage et tableau pour les propriétés
- **🔧 Paramètres administrateur** : Configuration des chemins config et vault via le panel admin Nextcloud
- **🌐 Support multi-utilisateurs** : Chaque utilisateur dispose de son propre vault avec données isolées

## 🚀 Démarrage rapide

### Prérequis

- Nextcloud 31+ (testé avec PHP 8.3.24, Apache 2.4.62)
- Node.js 18+ et npm
- Docker (optionnel, pour l'environnement de développement)

### Installation

1. **Cloner le dépôt dans votre répertoire d'applications Nextcloud :**
   ```bash
   cd nextcloud/custom_apps
   git clone <url-de-votre-repo> crm
   cd crm
   ```

2. **Installer les dépendances :**
   ```bash
   npm install
   ```

3. **Compiler l'application :**
   ```bash
   npm run build
   ```

4. **Activer l'application dans Nextcloud :**
   - Aller dans le panel d'administration Nextcloud → Applications
   - Trouver "CRM" dans la liste
   - Cliquer sur "Activer"

5. **Configurer les chemins (optionnel) :**
   - Aller dans Paramètres → Administration → Paramètres additionnels
   - Trouver la section "Paramètres CRM"
   - Définir vos chemins config et vault
   - Valeurs par défaut : `config_path=/apps/crm/config`, `vault_path=vault`

### Configuration de développement avec Docker

```bash
# Démarrer les conteneurs
docker-compose up -d

# Surveiller les modifications de fichiers
npm run watch

# Accéder à Nextcloud sur http://localhost:8080
```

## 📁 Structure du projet

```
crm/
├── appinfo/
│   ├── info.xml                  # App metadata
│   └── routes.php                # API routes
├── config/                       # YAML class definitions
│   ├── Personne.yaml             # Person class
│   ├── Institution.yaml          # Institution class
│   ├── Lieu.yaml                 # Location class
│   └── ...                       # Custom classes
├── css/
│   ├── crm-main.css             # Main layout styles
│   └── markdown-crm-display.css # Metadata display styles
├── js/
│   ├── main.ts                  # Main application entry point
│   ├── main.js                  # Compiled bundle
│   └── admin-settings.js        # Admin settings bundle
├── lib/
│   ├── AppInfo/
│   │   └── Application.php      # App initialization
│   ├── Controller/
│   │   ├── PageController.php   # Main page controller
│   │   ├── FileController.php   # File API endpoints
│   │   ├── ConfigController.php # Config API
│   │   └── SettingsController.php # Settings API
│   └── Settings/
│       └── AdminSettings.php    # Admin settings page
├── src/
│   ├── App.ts                   # NextcloudApp adapter (IApp)
│   ├── SafeMarkdownCRM.ts       # CSP-safe wrapper
│   └── settings/
│       └── AdminSettings.ts     # Settings UI component
├── templates/
│   ├── index.php                # Main app template
│   └── admin-settings.php       # Admin settings template
├── vault/                       # Example Markdown files
│   ├── Contacts/
│   ├── Institutions/
│   └── Lieux/
└── package.json
```

## 🎯 Utilisation

### Créer des classes d'entités (Configuration YAML)

Définissez la structure de vos entités dans `config/VotreClasse.yaml` :

```yaml
properties:
  - name: email
    type: text
    icon: mail
    
  - name: phone
    type: text
    icon: phone
    
  - name: relation
    type: multi-select
    options: [client, prospect, partner]
    
  - name: rating
    type: select
    options: [1, 2, 3, 4, 5]
    display: star-rating

display:
  - type: line
    properties: [email, phone]
    
  - type: tabs
    tabs:
      - name: Info
        type: fold
        properties: [relation, rating]
```

### Créer des fichiers Markdown

Stockez vos données dans `vault/` avec frontmatter YAML :

```markdown
---
Classe: Personne
email: john.doe@example.com
phone: +33 6 12 34 56 78
relation: [client]
rating: 5
---

# John Doe

Notes et informations supplémentaires sur John Doe...
```

### Utiliser l'application

1. **Naviguer vers l'application CRM** dans Nextcloud
2. **Parcourir les fichiers** dans la barre latérale gauche
3. **Cliquer sur un fichier** pour l'ouvrir
4. **Voir les métadonnées** dans le panneau de gauche (50% de largeur)
5. **Éditer le contenu** dans le panneau de droite (50% de largeur)
6. **Modifier les propriétés** en cliquant sur les icônes ou valeurs des champs
7. **Sauvegarde automatique** déclenchée après 300ms d'inactivité

### Points d'accès API

#### Gestion des fichiers

```typescript
// List all Markdown files
GET /apps/crm/files/md

// Get file content with metadata
GET /apps/crm/files/md?path=/vault/Contacts/John-Doe.md

// Save file
POST /apps/crm/files/md/save
{
  "path": "/vault/Contacts/John-Doe.md",
  "content": "---\nClasse: Personne\n...\n---\n\n# Content"
}
```

#### Configuration

```typescript
// List available class configs
GET /apps/crm/config/list

// Get config content
GET /apps/crm/config/Contact.yaml
```

#### Settings

```typescript
// Get settings
GET /apps/crm/settings/general

// Save settings
POST /apps/crm/settings/general
{
  "config_path": "/apps/crm/config",
  "vault_path": "vault"
}
```

## 🔧 Configuration

### Paramètres administrateur

Accès via : **Paramètres → Administration → Paramètres additionnels → Paramètres CRM**

- **Chemin Config** : Emplacement des définitions de classes YAML
  - Défaut : `/apps/crm/config`
  - Peut être un chemin absolu ou relatif
  
- **Chemin Vault** : Emplacement des fichiers de données Markdown
  - Défaut : `vault`
  - Relatif au répertoire des fichiers de l'utilisateur

### Variables d'environnement (Docker)

Configurer dans `docker-compose.yml` :

```yaml
volumes:
  - ./custom_apps:/var/www/html/custom_apps
  - ./vault:/var/www/html/data/admin/files/vault
```

## 🎨 Personnalisation

### Styles

Modifier les fichiers CSS pour personnaliser l'apparence :

- `css/crm-main.css` : Disposition principale (barre latérale, zone de contenu, éditeur)
- `css/markdown-crm-display.css` : Composants d'affichage des métadonnées

### Ajouter de nouveaux types de propriétés

1. Définir dans la config YAML :
```yaml
properties:
  - name: myfield
    type: custom
    icon: star
```

2. Implémenter la logique d'affichage dans `js/main.ts` ou étendre la bibliothèque Markdown-CRM

### Icônes personnalisées

Les icônes sont mappées vers des emoji dans `src/App.ts` (méthode `setIcon()`) :

```typescript
const iconMap: { [key: string]: string } = {
  'mail': '📧',
  'phone': '📞',
  'star': '⭐',
  // Add your own mappings
};
```

## 🐛 Dépannage

### Les classes ne se chargent pas

- Vérifier `config_path` dans les paramètres admin
- Vérifier que les fichiers YAML existent dans le répertoire config
- Vérifier la console du navigateur pour les erreurs
- Exécuter `docker exec nc_app ls /var/www/html/custom_apps/crm/config`

### Les fichiers ne s'affichent pas

- Vérifier `vault_path` dans les paramètres admin
- S'assurer que le dossier vault existe dans les fichiers de l'utilisateur
- Vérifier les permissions de fichier (doivent être lisibles par www-data)
- Exécuter `docker exec nc_app ls /var/www/html/data/admin/files/vault`

### La sauvegarde automatique ne fonctionne pas

- Vérifier la console du navigateur pour les erreurs
- Vérifier que les chemins de fichiers sont corrects
- Tester le point d'accès de sauvegarde : `POST /apps/crm/files/md/save`
- Vérifier que le fichier n'a pas été déplacé/renommé

### Erreurs CSP

Voir le guide détaillé dans la documentation légacy. Points clés :
- Ne pas utiliser `FormulaProperty` (utilise `new Function()` bloqué par CSP)
- Utiliser le wrapper `SafeMarkdownCRM` pour la conformité CSP
- Vérifier la configuration CSP dans PageController

### Problèmes de performance

- Vérifier que le cache des métadonnées fonctionne (chercher "✅ Using cached metadata" dans la console)
- Réduire le nombre de propriétés affichées simultanément
- Optimiser les configurations YAML pour éviter les structures profondément imbriquées

## 🔒 Sécurité

- **Politique de sécurité du contenu** : CSP stricte appliquée par Nextcloud
- **Isolation des utilisateurs** : Chaque utilisateur a son propre vault, les fichiers sont isolés
- **Protection CSRF** : Tous les points d'accès POST protégés par des jetons CSRF
- **Contrôle d'accès aux fichiers** : Utilise le système de permissions de fichiers de Nextcloud

## 📊 Performance

- **Mise en cache des métadonnées** : Cache TTL de 5 secondes réduit les lectures de fichiers de ~92%
- **Debounce de sauvegarde automatique** : Délai de 300ms évite les sauvegardes excessives
- **Chargement paresseux** : Charge uniquement le contenu des fichiers lorsqu'ils sont ouverts
- **Bundle optimisé** : Build de production Webpack avec minification

## 🧪 Tests

```bash
# Exécuter les tests unitaires
npm test

# Exécuter les tests PHP
docker exec nc_app php occ app:check-code crm

# Linter TypeScript
npm run lint

# Vérification de type
npm run type-check
```

## 📚 Documentation

- **Architecture** : Voir `INTEGRATION_SUMMARY.md` pour un aperçu détaillé de l'intégration
- **Guide CSP** : Voir `CSP_GUIDE.md` pour les détails sur la politique de sécurité du contenu
- **Paramètres** : Voir `SETTINGS_GUIDE.md` pour les options de configuration
- **Utilisation** : Voir `USAGE_GUIDE.md` pour des exemples d'utilisation détaillés

## 🤝 Contribuer

1. Forker le dépôt
2. Créer une branche de fonctionnalité : `git checkout -b feature/ma-fonctionnalite`
3. Commit les modifications : `git commit -am 'Ajout de ma fonctionnalité'`
4. Pousser vers la branche : `git push origin feature/ma-fonctionnalite`
5. Soumettre une pull request

## 📝 Licence

Ce projet est sous licence AGPL-3.0 - voir le fichier LICENSE pour plus de détails.

## 🙏 Remerciements

- [Markdown-CRM](https://github.com/lasagne20/Markdown-CRM) - Bibliothèque principale pour la gestion des métadonnées Markdown
- [Nextcloud](https://nextcloud.com/) - Plateforme cloud auto-hébergée
- [TypeScript](https://www.typescriptlang.org/) - JavaScript type-safe

## 📞 Support

- **Issues** : Signaler les bugs et demandes de fonctionnalités via GitHub Issues
- **Discussions** : Rejoindre les discussions de la communauté sur GitHub
- **Documentation** : Consulter le dossier docs pour les guides détaillés

---

**Conçu avec ❤️ pour la communauté Nextcloud**
