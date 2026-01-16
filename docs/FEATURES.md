#  CRM Features for Nextcloud

## Overview

The CRM plugin for Nextcloud allows you to manage your contacts, events, and entities (locations, institutions, etc.) directly from Markdown files with YAML metadata.

---

## 1.  Automatic Contact Synchronization

### Description

Automatic creation of contacts in Nextcloud address book from Markdown files of type "Person".

### How it Works

1. **Detection**: .md files with Class: Person in metadata
2. **Extraction**: Reading fields (Last Name, First Name, Email, Phone, etc.)
3. **Synchronization**: Creation/update in address book

### Configuration

- **Target User**: Choose user whose address book will receive contacts
- **Address Book**: Destination address book name
- **Field Mapping**: Correspondence between metadata and vCard fields
- **Filters**: Criteria to select files to synchronize

### Documentation

- [SYNC_SETTINGS.md](SYNC_SETTINGS.md) - Detailed configuration
- [QUICKSTART_SYNC.md](QUICKSTART_SYNC.md) - Quick start

---

## 2.  Automatic Calendar Synchronization

### Description

Automatic creation of events in Nextcloud calendars from Markdown files of type "Action".

### Operating Modes

#### Simple Mode: One event per file

- One file = one event
- Simple metadata: Date, Title, Description, Location

#### Advanced Mode: Array Properties

- **One file = multiple events**
- YAML arrays with event lists
- Advanced configuration of formats and filters

### Configuration

**Simple Mode:**
- Target user
- Destination calendar
- Field mapping (date, title, description, location)

**Advanced Mode (Array Properties):**
- Array property name
- Title format with variables: {name} - {location}
- Unique ID format
- Custom description fields
- JSON metadata filters

### Documentation

- [ARRAY_PROPERTIES.md](ARRAY_PROPERTIES.md) - Array Properties (advanced mode)
- [SYNC_SETTINGS.md](SYNC_SETTINGS.md) - General configuration
- [QUICKSTART_SYNC.md](QUICKSTART_SYNC.md) - Quick start

---

## 3.  Workflow Filter by Markdown Metadata

### Description

Create Nextcloud workflow rules based on YAML frontmatter metadata in Markdown files.

### Use Cases

- **Access Control**: Block access by type (Location, Person, Institution)
- **Automatic Tags**: Apply tags based on metadata
- **Notifications**: Trigger custom notifications
- **Conditional Sharing**: Restrict sharing based on criteria

### How it Works

1. **Detection**: YAML frontmatter analysis
2. **Extraction**: Reading metadata (Class, Type, Status, etc.)
3. **Evaluation**: Applying configured rules
4. **Action**: Executing workflow action

### Configuration

Via Nextcloud **Workflow** interface:

1. Go to **Administration**  **Workflow**
2. Create new rule
3. Select **"Markdown Metadata"** condition
4. Configure field and value to check
5. Define action to execute

### Example

**Block access to "Private" files:**

Condition: Markdown Metadata
  Field: Type
  Value: Private
Action: Block access

### Documentation

- [INTEGRATION_WORKFLOW.md](INTEGRATION_WORKFLOW.md) - Workflow integration

---

## 4.  Enhanced Metadata Display

### Description

Interactive interface to view and manage Markdown file metadata in Nextcloud.

### Features

- **Tabular View**: List entities with sorting and filtering
- **Dynamic Tabs**: Organization by type (Contacts, Locations, Institutions)
- **Direct Editing**: Modify metadata from interface
- **Preview**: Formatted Markdown content display

### Supported Entity Types

Defined via YAML configuration files:

- **Person**: Contacts with coordinates
- **Location**: Addresses and locations
- **Institution**: Organizations and companies
- **Action**: Events and tasks
- **Project**: Projects with tracking
- **...** Extensible via YAML configuration

### Configuration

Entity types defined in config/ folder

### Documentation

- [README.md](../README.md#yaml-configuration) - YAML configuration

---

## 5.  Administration Interface

### Description

Complete interface to configure all plugin features.

### Available Sections

#### General Configuration

- **Config Path**: YAML definition files folder
- **Vault Path**: Markdown files folder

#### Contact Synchronization

- Enable/disable
- Global configuration
- Multiple configurations (by user/address book)

#### Calendar Synchronization

- Enable/disable
- Global configuration
- Array Properties (advanced configurations)
- Multiple configurations (by user/calendar)

### Access

**Nextcloud Menu**  **Administration**  **CRM**

### Documentation

- [SYNC_SETTINGS.md](SYNC_SETTINGS.md) - Complete configuration
- [INTERFACE_SCREENSHOT.md](INTERFACE_SCREENSHOT.md) - Screenshots

---

## Features Summary

| Feature | Description | Documentation |
|---------|-------------|---------------|
| **Contacts** | Automatic sync to address book | [SYNC_SETTINGS.md](SYNC_SETTINGS.md) |
| **Simple Calendar** | One file = one event | [SYNC_SETTINGS.md](SYNC_SETTINGS.md) |
| **Array Properties** | One file = multiple events | [ARRAY_PROPERTIES.md](ARRAY_PROPERTIES.md) |
| **Workflow** | Rules based on metadata | [INTEGRATION_WORKFLOW.md](INTEGRATION_WORKFLOW.md) |
| **Display** | Visualization interface | [README.md](../README.md) |
| **Configuration** | Administration interface | [SYNC_SETTINGS.md](SYNC_SETTINGS.md) |

---

## Technologies Used

### Backend (PHP)

- **Nextcloud App Framework**: Application structure
- **CalDAV/CardDAV**: Contact/calendar integration
- **Workflow Engine**: Custom filters
- **Sabre/VObject**: vCard/iCal manipulation

### Frontend (TypeScript)

- **TypeScript**: Typed and robust code
- **Webpack**: Assets bundling
- **Markdown-CRM**: Metadata management library

### Tests

- **Jest**: Frontend unit tests
- **PHPUnit**: Backend unit tests
- **Playwright**: E2E tests

---

##  Useful Links

- **[Main README](../README.md)** - Overview
- **[Index Documentation](INDEX_DOCUMENTATION.md)** - Guide by profile
- **[Quick Start](QUICKSTART_SYNC.md)** - 5-minute configuration
- **[Changelog](../CHANGELOG.md)** - Version history
