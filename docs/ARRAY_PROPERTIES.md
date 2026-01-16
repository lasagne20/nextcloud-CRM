#  Array Properties Configuration

##  Overview

The **Array Properties** feature allows you to automatically create calendar events from arrays in YAML metadata of your Markdown files.

### Typical Use Cases

- **Public Events**: List of events with dates, locations, and descriptions
- **Activities**: Activity program with schedules and managers
- **Recurring Tasks**: List of planned tasks with due dates
- **Appointments**: Appointment schedule with participants

##  YAML Metadata Format

### Markdown File Example

```yaml
---
Class: Action
Title: Summer Program 2026
Type: Public
events:
  - date: "2026-07-15 14:00"
    name: Jazz Concert
    location: Central Park
    description: Outdoor concert
    manager: Marie Dupont
  - date: "2026-07-20 18:30"
    name: Movie Night
    location: Community Hall
    description: Animated film screening
    manager: Jean Martin
---

# Summer Program 2026

Find all our summer events...
```

##  Configuration

### Interface Access

1. **Nextcloud Menu**  **Administration**  **CRM**
2. Section ** Calendar Synchronization - Array Properties**
3. Click ** Add configuration**

### Configuration Parameters

#### ℹ General Information

| Field | Description | Example |
|-------|-------------|---------|
| **Configuration Name** | Descriptive name to identify the config | Public Events |
| **Array Property (YAML)** | Name of the array field in your metadata | events, activities, tasks |

####  Destination

| Field | Description | Example |
|-------|-------------|---------|
| **Target User** | User whose calendar will receive events | admin, john |
| **Calendar** | Destination calendar URI | personal, work, events |

####  Filtering

**Metadata Filter (JSON)**: Only files whose metadata exactly match will be processed.

```json
{
  "Type": "Public",
  "Status": "Active"
}
```

####  Event Configuration

| Field | Description | Example |
|-------|-------------|---------|
| **Date Field** | Name of field containing date in each element | date, schedule, deadline |
| **Title Format** | Template to generate event title | {name} - {location} |
| **ID Format** | Template to generate unique ID | event_{index} |
| **Location Field** | Field containing event location | location, address, place |
| **Assigned Field** | Field containing assigned persons | manager, coordinators |
| **Description Fields** | Comma-separated fields for description | description, manager, _root.Title |

### Available Variables

#### In title and ID formats

- `{fieldName}` : Value of array element field (e.g. {name}, {date})
- `{index}` : Element number in array (0, 1, 2...)
- `{filename}` : Markdown filename without extension

#### In description fields

- **Simple fields**: Come directly from each array element
- `_content` : Complete markdown content (without YAML metadata)
- `_root.FieldName` : Access root metadata (e.g. _root.Title, _root.Type)

##  Configuration Examples

### Example 1: Public Events

```json
{
  "id": "config-1",
  "enabled": true,
  "label": "Public Events",
  "user_id": "admin",
  "calendar": "personal",
  "metadata_filter": {
    "Class": "Action",
    "Type": "Public"
  },
  "array_property": "events",
  "date_field": "date",
  "title_format": "{name} - {location}",
  "id_format": "event_{index}",
  "location_field": "location",
  "assigned_field": "manager",
  "description_fields": ["description", "manager", "_root.Title"]
}
```

##  Technical Operation

### Synchronization Process

1. **Detection**: When a .md file is created/modified
2. **Class Verification**: The Class field must match
3. **Filter Application**: Metadata must match JSON filter
4. **Array Extraction**: Reading specified array field
5. **Event Creation**: For each array element - generate title, extract date, build description

### Duplicate Management

- Each event has unique ID generated according to id_format
- If event with same ID exists, it is updated (no duplicate)
- If ID changes, new event is created

##  Best Practices

### 1. Field Naming

- Use consistent field names in all files
- Prefer lowercase without accents: date, location, name
- Avoid spaces: start_date rather than start date

### 2. Date Format

Accepted formats:
- 2026-07-15 14:00 (recommended)
- 2026-07-15
- 15/07/2026

### 3. Configuration Organization

- One configuration per event type
- Use precise filters
- Test first before deploying to production

##  Troubleshooting

### Events are not created

Checks:
1. Configuration is enabled
2. Class field matches
3. Metadata matches JSON filter
4. Array field name is correct
5. Date field exists in elements
6. Date format is valid

### Events are duplicated

Cause: ID format changes with each processing
Solution: Use stable ID format like {name}_{date}

##  See Also

- [SYNC_SETTINGS.md](SYNC_SETTINGS.md) - General sync configuration
- [QUICKSTART_SYNC.md](QUICKSTART_SYNC.md) - Quick start
- [README.md](../README.md) - Plugin overview
