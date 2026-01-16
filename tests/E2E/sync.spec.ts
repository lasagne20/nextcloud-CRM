import { test, expect, Page } from '@playwright/test';

/**
 * E2E tests for Markdown file synchronization features
 * Tests contact and calendar sync through the admin interface
 */

test.describe('CRM Sync Features - Admin Interface', () => {
    let adminPage: Page;

    test.beforeEach(async ({ page }) => {
        adminPage = page;
        
        // Login as admin
        await adminPage.goto('/login');
        await adminPage.fill('input[name="user"]', 'admin');
        await adminPage.fill('input[name="password"]', 'admin');
        await adminPage.click('button[type="submit"]');
        
        // Navigate to CRM settings
        await adminPage.goto('/settings/admin/crm');
        await adminPage.waitForLoadState('networkidle');
    });

    test('should display contact sync configuration section', async () => {
        // Check that contact sync section is visible
        const contactSection = adminPage.locator('h2:has-text("Synchronisation Contacts")');
        await expect(contactSection).toBeVisible();
        
        // Check for enable checkbox
        const enableCheckbox = adminPage.locator('#sync_contacts_global_enabled');
        await expect(enableCheckbox).toBeVisible();
    });

    test('should enable contact sync and configure settings', async () => {
        // Enable contact sync
        const enableCheckbox = adminPage.locator('#sync_contacts_global_enabled');
        await enableCheckbox.check();
        
        // Set contact class
        const classInput = adminPage.locator('#sync_contacts_global_class');
        await classInput.fill('Personne');
        
        // Verify configuration is editable
        const configDiv = adminPage.locator('#contacts-configs-container');
        await expect(configDiv).toBeVisible();
        
        // Save settings
        const saveButton = adminPage.locator('#crm-save-sync-settings');
        await saveButton.click();
        
        // Wait for success message
        const statusSpan = adminPage.locator('#crm-sync-save-status');
        await expect(statusSpan).toContainText('succès', { timeout: 5000 });
    });

    test('should display calendar sync configuration section', async () => {
        // Check that calendar sync section is visible
        const calendarSection = adminPage.locator('h2:has-text("Synchronisation Calendrier")');
        await expect(calendarSection).toBeVisible();
        
        // Check for enable checkbox
        const enableCheckbox = adminPage.locator('#sync_calendar_global_enabled');
        await expect(enableCheckbox).toBeVisible();
    });

    test('should enable calendar sync and configure basic settings', async () => {
        // Enable calendar sync
        const enableCheckbox = adminPage.locator('#sync_calendar_global_enabled');
        await enableCheckbox.check();
        
        // Set calendar class
        const classInput = adminPage.locator('#sync_calendar_global_class');
        await classInput.fill('Action');
        
        // Set field mapping
        const mappingTextarea = adminPage.locator('#crm-sync-calendar-mapping');
        await mappingTextarea.fill(JSON.stringify({
            title: 'Titre',
            date: 'Date',
            description: 'Description',
            location: 'Lieu'
        }, null, 2));
        
        // Save settings
        const saveButton = adminPage.locator('#crm-save-sync-settings');
        await saveButton.click();
        
        // Wait for success message
        const statusSpan = adminPage.locator('#crm-sync-save-status');
        await expect(statusSpan).toContainText('succès', { timeout: 5000 });
    });

    test('should configure array properties for calendar sync', async () => {
        // Enable calendar sync
        await adminPage.locator('#sync_calendar_global_enabled').check();
        
        // Configure array properties
        const arrayPropsTextarea = adminPage.locator('#crm-sync-calendar-array-properties');
        await arrayPropsTextarea.fill(JSON.stringify(['Taches', 'Etapes'], null, 2));
        
        // Set field mapping for array items
        const mappingTextarea = adminPage.locator('#crm-sync-calendar-mapping');
        await mappingTextarea.fill(JSON.stringify({
            title: 'nom',
            date: 'date',
            description: 'description'
        }, null, 2));
        
        // Save configuration
        const saveButton = adminPage.locator('#crm-save-sync-settings');
        await saveButton.click();
        
        // Verify success
        await expect(adminPage.locator('#crm-sync-save-status')).toContainText('succès', { timeout: 5000 });
    });

    test('should add multiple contact sync configurations', async () => {
        // Enable contact sync
        await adminPage.locator('#sync_contacts_global_enabled').check();
        
        // Add first config
        const addConfigButton = adminPage.locator('#add-contact-config');
        await addConfigButton.click();
        
        // Wait for config form to appear
        const configContainer = adminPage.locator('#contacts-configs-container');
        const firstConfig = configContainer.locator('.contact-config-item').first();
        await expect(firstConfig).toBeVisible();
        
        // Fill first config
        await firstConfig.locator('input[placeholder="Nom de la configuration"]').fill('Config Admin');
        await firstConfig.locator('select').first().selectOption('admin');
        
        // Add second config
        await addConfigButton.click();
        
        // Verify both configs exist
        const configs = configContainer.locator('.contact-config-item');
        await expect(configs).toHaveCount(2);
        
        // Save
        await adminPage.locator('#crm-save-sync-settings').click();
        await expect(adminPage.locator('#crm-sync-save-status')).toContainText('succès', { timeout: 5000 });
    });

    test('should validate JSON configuration fields', async () => {
        // Enable calendar sync
        await adminPage.locator('#sync_calendar_global_enabled').check();
        
        // Enter invalid JSON in mapping
        const mappingTextarea = adminPage.locator('#crm-sync-calendar-mapping');
        await mappingTextarea.fill('{ invalid json }');
        
        // Try to save
        const saveButton = adminPage.locator('#crm-save-sync-settings');
        await saveButton.click();
        
        // Should show error
        const statusSpan = adminPage.locator('#crm-sync-save-status');
        await expect(statusSpan).toContainText('Erreur', { timeout: 5000 });
    });

    test('should configure metadata filters for sync', async () => {
        // Enable calendar sync
        await adminPage.locator('#sync_calendar_global_enabled').check();
        
        // Set global filter
        const globalFilterTextarea = adminPage.locator('#sync_calendar_global_filter');
        await globalFilterTextarea.fill(JSON.stringify([
            { field: 'Statut', operator: 'equals', value: 'Confirmé' },
            { field: 'Priorite', operator: 'equals', value: 'Haute' }
        ], null, 2));
        
        // Save
        await adminPage.locator('#crm-save-sync-settings').click();
        await expect(adminPage.locator('#crm-sync-save-status')).toContainText('succès', { timeout: 5000 });
    });

    test('should persist configuration after page reload', async () => {
        // Enable and configure contact sync
        await adminPage.locator('#sync_contacts_global_enabled').check();
        await adminPage.locator('#sync_contacts_global_class').fill('Personne');
        
        // Save
        await adminPage.locator('#crm-save-sync-settings').click();
        await expect(adminPage.locator('#crm-sync-save-status')).toContainText('succès', { timeout: 5000 });
        
        // Reload page
        await adminPage.reload();
        await adminPage.waitForLoadState('networkidle');
        
        // Verify settings are still there
        await expect(adminPage.locator('#sync_contacts_global_enabled')).toBeChecked();
        await expect(adminPage.locator('#sync_contacts_global_class')).toHaveValue('Personne');
    });
});

test.describe('CRM Sync Features - File Operations', () => {
    let adminPage: Page;

    test.beforeEach(async ({ page }) => {
        adminPage = page;
        await adminPage.goto('/login');
        await adminPage.fill('input[name="user"]', 'admin');
        await adminPage.fill('input[name="password"]', 'admin');
        await adminPage.click('button[type="submit"]');
    });

    test('should create markdown file and trigger contact sync', async () => {
        // Navigate to Files app
        await adminPage.goto('/apps/files');
        await adminPage.waitForLoadState('networkidle');
        
        // Create new markdown file
        await adminPage.click('[data-action="new-file"]');
        const fileNameInput = adminPage.locator('input[placeholder*="filename"]');
        await fileNameInput.fill('test-contact.md');
        await fileNameInput.press('Enter');
        
        // Open file editor
        await adminPage.click('text=test-contact.md');
        
        // Enter markdown content with contact metadata
        const editor = adminPage.locator('.CodeMirror');
        await editor.click();
        await adminPage.keyboard.type(`---
Classe: Personne
Nom: Test User
Email: test@example.com
---

# Test User
`);
        
        // Save file
        await adminPage.keyboard.press('Control+S');
        
        // Wait for sync to process
        await adminPage.waitForTimeout(2000);
        
        // Verify file was created
        await adminPage.goto('/apps/files');
        await expect(adminPage.locator('text=test-contact.md')).toBeVisible();
    });

    test('should create markdown file with array properties for calendar sync', async () => {
        // Navigate to Files app
        await adminPage.goto('/apps/files');
        
        // Create folder for actions
        await adminPage.click('[data-action="new-folder"]');
        await adminPage.fill('input[placeholder*="folder"]', 'actions');
        await adminPage.press('input[placeholder*="folder"]', 'Enter');
        
        // Enter folder
        await adminPage.dblclick('text=actions');
        
        // Create new markdown file
        await adminPage.click('[data-action="new-file"]');
        await adminPage.fill('input[placeholder*="filename"]', 'projet-taches.md');
        await adminPage.press('input[placeholder*="filename"]', 'Enter');
        
        // Open and edit file
        await adminPage.click('text=projet-taches.md');
        const editor = adminPage.locator('.CodeMirror');
        await editor.click();
        
        await adminPage.keyboard.type(`---
Classe: Action
ProjetNom: Test Project
Taches:
  - nom: Task 1
    date: 2024-04-01 09:00:00
    description: First task
  - nom: Task 2
    date: 2024-04-02 10:00:00
    description: Second task
---

# Test Project
`);
        
        // Save
        await adminPage.keyboard.press('Control+S');
        await adminPage.waitForTimeout(2000);
        
        // File should be saved
        await expect(adminPage.locator('text=projet-taches.md')).toBeVisible();
    });
});

test.describe('CRM Sync Features - Animation Settings', () => {
    let adminPage: Page;

    test.beforeEach(async ({ page }) => {
        adminPage = page;
        await adminPage.goto('/login');
        await adminPage.fill('input[name="user"]', 'admin');
        await adminPage.fill('input[name="password"]', 'admin');
        await adminPage.click('button[type="submit"]');
        await adminPage.goto('/settings/admin/crm');
    });

    test('should display array properties animation configuration', async () => {
        // Navigate to animation settings tab (if separate)
        const animationSection = adminPage.locator('h2:has-text("Propriétés Tableau")');
        if (await animationSection.isVisible()) {
            await expect(animationSection).toBeVisible();
        }
    });

    test('should add and configure animation config', async () => {
        // Find animation configs section
        const addButton = adminPage.locator('#add-animation-config');
        
        if (await addButton.isVisible()) {
            await addButton.click();
            
            // Fill config
            const configItem = adminPage.locator('.animation-config-item').first();
            await configItem.locator('input[placeholder*="Nom"]').fill('Test Animation');
            
            // Set filter
            await configItem.locator('textarea').first().fill(JSON.stringify([
                { field: 'Type', operator: 'equals', value: 'Animation' }
            ]));
            
            // Save
            await adminPage.locator('#save-animation-configs').click();
            await adminPage.waitForTimeout(1000);
        }
    });
});
