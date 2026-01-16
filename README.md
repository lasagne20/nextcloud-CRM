# CRM for Nextcloud

Custom CRM application for Nextcloud with advanced workflow integration.

## 🎯 Main Features

### 1. Automatic Contacts & Calendar Synchronization

Automatic synchronization of contacts and events from Markdown files to Nextcloud.

**Features:**
- Automatic contact creation (Class: Person) in address book
- Automatic event creation (Class: Action) in calendar
- **Array Properties**: Create multiple events from a single Markdown file
- Centralized or decentralized configuration
- Choice of target user and address book/calendar

**Documentation:**
- [📖 Synchronization Configuration](docs/SYNC_SETTINGS.md)
- [📋 Array Properties](docs/ARRAY_PROPERTIES.md)
- [🚀 Quick Start](docs/QUICKSTART_SYNC.md)

### 2. Workflow Filter by Markdown Metadata

Create workflow rules based on YAML frontmatter metadata in markdown files.

**Metadata Example:**
```yaml
---
Class: Location
City: Paris
Type: Restaurant
---
```

**Use Cases:**
- Block file access based on type (Location, Person, Institution...)
- Apply automatic tags
- Trigger custom notifications
- Restrict sharing based on metadata

### 3. Enhanced Metadata Display

- Interactive interface with tabs and dynamic views
- Manage contacts, institutions, locations via Markdown files
- YAML configuration to define your own entity types

## 🧪 Tests

The project includes a complete test suite:
- **Unit tests** (Jest + PHP)
- **Integration tests**
- **E2E tests** (Playwright)

**Test Documentation:**
- [VS Code Tests Guide](docs/VSCODE_TESTS_GUIDE.md)
- [Starting Tests](docs/DEMARRAGE_TESTS_VSCODE.md)
- [Troubleshooting](docs/DEPANNAGE_TESTS_VSCODE.md)
- [Tests Summary](docs/TESTS_SUMMARY.md)

**Run Tests:**

```bash
# All tests
npm run test:all

# Frontend tests only
npm test

# PHP tests only
npm run test:php

# Tests with coverage
npm run test:coverage
```

## 📚 Complete Documentation

- **[docs/](docs/)** - All project documentation
- **[docs/README.md](docs/README.md)** - Documentation index
- **[docs/INDEX_DOCUMENTATION.md](docs/INDEX_DOCUMENTATION.md)** - User profile guide
- **[CHANGELOG.md](CHANGELOG.md)** - Version history

## 📦 Installation

### Prerequisites
- Nextcloud 31.0+
- PHP 8.1+
- Node.js 18+ (for build)

### Steps

1. **Clone into apps folder**
   ```bash
   cd /var/www/html/custom_apps
   git clone https://github.com/your-repo/crm.git
   ```

2. **Install dependencies**
   ```bash
   cd crm
   npm install
   composer install
   ```

3. **Build assets**
   ```bash
   npm run build
   ```

4. **Enable the application**
   ```bash
   php occ app:enable crm
   ```

5. **Patch Manager.php** (required once)
   
   Edit `/var/www/html/apps/workflowengine/lib/Manager.php`
   
   In the `getBuildInChecks()` method, add after `UserGroupMembership::class`:
   ```php
   // CRM: Markdown Metadata Check
   $this->container->query(\OCA\CRM\Flow\MarkdownMetadataCheck::class),
   ```

## 🚀 Usage

### Create a Workflow Rule

1. Go to **Settings** → **Administration** → **Workflow**
2. Select the trigger event (e.g., "File is accessed")
3. Add a **"Markdown Metadata"** filter
4. Choose the operator:
   - `matches` → Exact match: `Class:Location`
   - `does not match` → Inverse
   - `matches expression` → Regex: `/Class:(Location|City)/`
5. Configure the action (Block, Tag, Notify...)

### Value Format

- **Simple:** `Class:Location`
- **Regex:** `/Class:(Location|City)/` (with `/` delimiters)
- **Multiple keys:** `Class:Location` then add another filter

### Create CRM Markdown Files

Store your data in `vault/` with YAML frontmatter:

```markdown
---
Class: Person
email: john.doe@example.com
phone: +33 6 12 34 56 78
relation: [client]
---

# John Doe

Additional notes and information...
```

## 📚 Documentation

### Main Documentation
- **[INDEX_DOCUMENTATION.md](docs/INDEX_DOCUMENTATION.md)** - 🗺️ Complete documentation index

### Contacts & Calendar Synchronization
- **[QUICKSTART_SYNC.md](docs/QUICKSTART_SYNC.md)** - 🚀 Quick start guide (5 min)
- **[SYNC_SETTINGS.md](docs/SYNC_SETTINGS.md)** - 📖 Complete synchronization documentation
- **[FEATURE_SYNC.md](docs/FEATURE_SYNC.md)** - ✨ Feature presentation
- **[INTERFACE_SCREENSHOT.md](docs/INTERFACE_SCREENSHOT.md)** - 🎨 Interface overview

### Development & Technical
- **[CHANGELOG_SYNC.md](docs/CHANGELOG_SYNC.md)** - 📝 Detailed technical changes
- **[SUMMARY_IMPLEMENTATION.md](docs/SUMMARY_IMPLEMENTATION.md)** - 🔧 Implementation summary
- **[INTEGRATION_WORKFLOW.md](./INTEGRATION_WORKFLOW.md)** - 🔄 Workflow filter documentation
- **[CHANGELOG.md](./CHANGELOG.md)** - 📅 Version history

## 🏗️ Architecture

```
crm/
├── lib/
│   ├── AppInfo/
│   │   └── Application.php               # App bootstrap
│   ├── Flow/
│   │   └── MarkdownMetadataCheck.php     # Workflow filter logic
│   ├── Listener/
│   │   └── LoadWorkflowScriptsListener.php # Loads workflow JS
│   ├── Controller/
│   │   ├── PageController.php            # Main page
│   │   ├── FileController.php            # Files API
│   │   └── SettingsController.php        # Admin settings
│   └── Settings/
│       └── AdminSettings.php             # Settings interface
├── src/
│   ├── App.ts                            # Main application
│   ├── SafeMarkdownCRM.ts                # CSP-safe wrapper
│   └── workflowengine-check.js           # Workflow interface
├── js/
│   ├── main.js                           # Main bundle
│   └── workflowengine-check.js           # Workflow bundle
├── config/                               # YAML class definitions
└── vault/                                # Example data files
```

## 🔧 Development

### Build in watch mode
```bash
npm run dev
```

### Lint & format
```bash
npm run lint:fix
npm run stylelint:fix
```

### Check workflow integration
```bash
# Verify that the check is registered
docker exec nc_app php -r '
  $manager = \OC::$server->get(\OCA\WorkflowEngine\Manager::class);
  $checks = $manager->getBuildInChecks();
  echo "Number of checks: " . count($checks) . "\n";
  foreach($checks as $check) {
    echo get_class($check) . "\n";
  }
'
```

## 🐛 Troubleshooting

### Workflow filter doesn't appear

**Check JavaScript console (F12):**
```
[CRM] Registering Markdown Metadata check...
[CRM] Markdown Metadata check registered successfully
```

**If nothing appears:**
- Clear cache: `php occ maintenance:repair`
- Reload app: `php occ app:disable crm && php occ app:enable crm`
- Verify that `js/workflowengine-check.js` exists
- Check Manager.php patch

### Error "preg_match(): Delimiter must not be alphanumeric"

**Cause:** The "matches" operator expects a regex with `/pattern/` delimiters

**Solutions:**
- ✅ Use the **"matches"** operator with `Class:Location`
- ✅ Use the **"matches expression"** operator with `/Class:Location/`
- ❌ DO NOT use "matches expression" with `Class:Location`

### Workflow doesn't block access

1. **Check the file's YAML metadata:**
   ```yaml
   ---
   Class: Location
   ---
   # File content
   ```

2. **Check logs:**
   ```bash
   docker exec nc_app tail -f /var/www/html/data/nextcloud.log | grep -i workflow
   ```

3. **Test the rule:**
   - Create a simple rule: `Class:Location` with "matches" operator
   - Try to access a file with `Class: Location` in the frontmatter
   - Verify that the workflow action triggers

### CRM files don't display

- Verify that `vault/` exists in user files
- Check permissions: `docker exec nc_app ls -la /var/www/html/data/admin/files/vault`
- Check admin settings: Settings → Administration → Additional settings → CRM

## 📄 License

AGPL-3.0-or-later

## 👥 Contributing

Contributions welcome! Create an issue or pull request.

---

**Developed for CRM usage with Nextcloud** 🚀
