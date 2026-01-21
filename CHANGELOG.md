# Changelog - English version

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

### Changed

### Fixed

## [0.1.0] - 2026-01-16

### Added

- **Array Properties**: Automatic creation of multiple events from a single Markdown file
  - Configuration interface with filter management, title and description formats
  - Support for dynamic variables: `{fieldName}`, `{index}`, `{filename}`
  - Access to root metadata via `_root.FieldName` and content via `_content`
  - Smart duplicate management with unique IDs
  - Complete documentation in [docs/ARRAY_PROPERTIES.md](docs/ARRAY_PROPERTIES.md)

- **Enhanced admin interface**: Dark interface with better readability for array properties configuration

- **Contacts & Calendar Synchronization**: Automatic synchronization from Markdown files to Nextcloud
  - Support for multiple sync configurations per type
  - Metadata mapping and filtering
  - Array properties for bulk event creation

- **Workflow filter by metadata**: Create workflow rules based on YAML metadata

- **Comprehensive test suite**: 48 tests covering Unit, Integration, and E2E scenarios
  - PHPUnit tests for backend logic
  - Jest tests for frontend components
  - Playwright tests for end-to-end workflows

### Fixed

- Fixed array properties configuration save (added @CSRFCheck, using FormData)
- Improved configuration persistence after Nextcloud restart
- PHP 8.3 compatibility improvements
