# Intégration Workflow - Filtre par Métadonnées Markdown

## Vue d'ensemble

Cette application CRM ajoute un filtre personnalisé **"Markdown Metadata"** au système de workflow de Nextcloud, permettant de filtrer les fichiers markdown par leurs métadonnées YAML frontmatter.

## Fonctionnalités

### Filtre Markdown Metadata

Permet de créer des règles de workflow basées sur les métadonnées YAML en en-tête des fichiers markdown.

**Format des métadonnées :**
```yaml
---
Classe: Lieu
Ville: Paris
---
```

**Opérateurs disponibles :**
- `correspond` (is) - Correspondance exacte : `Classe:Lieu`
- `ne correspond pas` (!is) - Non correspondance : `Classe:Personne`
- `correspond à l'expression` (matches) - Expression régulière : `/Classe:(Lieu|Ville)/`
- `ne correspond pas à l'expression` (!matches) - Regex inverse : `/Classe:Personne/`

### Exemple d'utilisation

1. **Bloquer l'accès aux fichiers "Lieu"**
   - Quand : Le fichier est consulté
   - et : Markdown Metadata → correspond → `Classe:Lieu`
   - Alors : Bloquer l'accès à un fichier

2. **Autoriser uniquement les "Personne"**
   - Quand : Le fichier est consulté
   - et : Markdown Metadata → ne correspond pas → `Classe:Personne`
   - Alors : Bloquer l'accès à un fichier

## Architecture technique

### 1. Backend (PHP)

#### MarkdownMetadataCheck (`lib/Flow/MarkdownMetadataCheck.php`)

Classe principale qui implémente le check de workflow :
- Hérite de `AbstractStringCheck` pour la gestion des opérateurs
- Implémente `IFileCheck` pour l'accès aux fichiers
- Utilise le trait `TFileCheck` pour les opérations fichiers

**Méthodes clés :**
```php
public function executeCheck($operation, $entity, $matches, $metadata = [])
// Extrait les métadonnées YAML et compare avec la règle

protected function getActualValue(ISystemTag $entity, ICheck $check)
// Retourne les métadonnées du fichier au format "Clé:Valeur"
```

**Intégration dans Manager.php :**
Le check est ajouté directement dans la méthode `getBuildInChecks()` du WorkflowEngine :
```php
// Patch appliqué sur /var/www/html/apps/workflowengine/lib/Manager.php
$this->container->query(\OCA\CRM\Flow\MarkdownMetadataCheck::class),
```

### 2. Frontend (JavaScript)

#### workflowengine-check.js (`src/workflowengine-check.js`)

Enregistre l'interface utilisateur du check dans l'interface workflow :

```javascript
window.OCA.WorkflowEngine.registerCheck({
    class: 'OCA\\CRM\\Flow\\MarkdownMetadataCheck',
    name: t('crm', 'Markdown Metadata'),
    operators: [...],
    placeholder: (check) => { ... },
    validate: (check) => { ... }
});
```

**Compilation :** Webpack compile le fichier source vers `js/workflowengine-check.js`

#### LoadWorkflowScriptsListener (`lib/Listener/LoadWorkflowScriptsListener.php`)

Charge le JavaScript uniquement sur la page workflow :
```php
if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/settings/admin/workflow')) {
    Util::addScript('crm', 'workflowengine-check');
}
```

### 3. Flux de données

```
┌─────────────────────────────────────────────────────────────┐
│  1. Chargement page workflow (/settings/admin/workflow)    │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  2. LoadWorkflowScriptsListener charge workflowengine-check.js │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  3. registerCheck() ajoute "Markdown Metadata" dans l'UI    │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  4. Utilisateur configure : Classe:Lieu + opérateur         │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  5. Règle sauvegardée en base de données                    │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  6. À chaque accès fichier, executeCheck() vérifie la règle │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  7. Si match → action workflow (bloquer, tag, notification)│
└─────────────────────────────────────────────────────────────┘
```

## Installation

### 1. Déployer l'application

```bash
# Copier dans le dossier apps de Nextcloud
cp -r crm /var/www/html/custom_apps/

# Activer l'application
docker exec nc_app php occ app:enable crm
```

### 2. Patcher Manager.php

Le fichier `/var/www/html/apps/workflowengine/lib/Manager.php` doit être patché pour inclure le check.

**Localisation :** Méthode `getBuildInChecks()`, après `UserGroupMembership::class`

```php
protected function getBuildInChecks() {
    return [
        // ... checks existants
        $this->container->query(UserGroupMembership::class),
        // CRM: Markdown Metadata Check
        $this->container->query(\OCA\CRM\Flow\MarkdownMetadataCheck::class),
    ];
}
```

### 3. Compiler les assets

```bash
cd custom_apps/crm
npm install
npm run build
```

## Dépannage

### Le filtre n'apparaît pas dans l'interface

1. Vérifier que le script JS est chargé (F12 → Console) :
   ```
   [CRM] Registering Markdown Metadata check...
   [CRM] Markdown Metadata check registered successfully
   ```

2. Vérifier Manager.php :
   ```bash
   docker exec nc_app php occ -c 'print_r((new \OCA\WorkflowEngine\Manager())->getBuildInChecks());'
   # Doit afficher MarkdownMetadataCheck en dernière position
   ```

### Erreur "preg_match(): Delimiter must not be alphanumeric"

**Cause :** L'opérateur "matches" attend une regex avec délimiteurs (`/pattern/`)

**Solutions :**
- Utiliser l'opérateur "correspond" (is) avec `Classe:Lieu`
- OU ajouter les délimiteurs : `/Classe:Lieu/`

### Le workflow ne bloque pas l'accès

1. Vérifier les logs :
   ```bash
   docker exec nc_app tail -f /var/www/html/data/nextcloud.log
   ```

2. Vérifier que le fichier a des métadonnées YAML valides :
   ```yaml
   ---
   Classe: Lieu
   ---
   ```

3. Tester la valeur retournée :
   ```php
   docker exec nc_app php -r '
   $file = file_get_contents("/var/www/html/data/admin/files/vault/Lieux/Test.md");
   preg_match("/^---\s*\n(.*?)\n---/s", $file, $matches);
   print_r($matches[1]);
   '
   ```

## Limitations connues

1. **Format strict :** Les métadonnées doivent être en début de fichier avec `---`
2. **Performance :** Chaque accès fichier déclenche la lecture du contenu
3. **Regex uniquement :** Le matching utilise `preg_match()`, pas de recherche floue
4. **Pas de cache :** Les métadonnées sont extraites à chaque vérification

## Évolutions possibles

- [ ] Cache des métadonnées en base de données
- [ ] Support de plusieurs valeurs (OR) : `Classe:Lieu|Classe:Ville`
- [ ] Interface graphique pour sélectionner les clés disponibles
- [ ] Indexation automatique des métadonnées
- [ ] Support JSON en plus de YAML

## Références

- [Nextcloud Workflow Engine](https://docs.nextcloud.com/server/latest/admin_manual/workflow/index.html)
- [Flow API Documentation](https://github.com/nextcloud/server/tree/master/apps/workflowengine)
- [YAML Frontmatter](https://jekyllrb.com/docs/front-matter/)
