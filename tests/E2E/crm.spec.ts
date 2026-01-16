import { test, expect } from '@playwright/test';

test.describe('CRM Application', () => {
  test.beforeEach(async ({ page }) => {
    // Navigation vers la page CRM
    await page.goto('/apps/crm');
  });

  test('should load the main CRM page', async ({ page }) => {
    // Vérifier que la page CRM charge correctement
    await expect(page).toHaveTitle(/CRM/);
    
    // Vérifier la présence d'éléments de base
    const mainContent = page.locator('#app-content');
    await expect(mainContent).toBeVisible();
  });

  test('should display API response', async ({ page }) => {
    // Tester l'endpoint API
    const response = await page.request.get('/apps/crm/api');
    expect(response.ok()).toBeTruthy();
    
    const data = await response.json();
    expect(data).toHaveProperty('message');
    expect(data.message).toBe('Hello world!');
  });

  test('should handle file operations', async ({ page }) => {
    // Tester la liste des fichiers markdown
    const response = await page.request.get('/apps/crm/files/markdown');
    expect(response.ok()).toBeTruthy();
    
    const files = await response.json();
    expect(Array.isArray(files)).toBeTruthy();
  });
});

test.describe('Admin Settings', () => {
  test('should load admin settings page', async ({ page }) => {
    // Navigation vers les paramètres d'administration
    await page.goto('/settings/admin/crm');
    
    // Vérifier la présence des champs de configuration
    const configPath = page.locator('#crm-config-path');
    const vaultPath = page.locator('#crm-vault-path');
    const saveButton = page.locator('#crm-save-settings');
    
    await expect(configPath).toBeVisible();
    await expect(vaultPath).toBeVisible();
    await expect(saveButton).toBeVisible();
  });

  test('should save general settings', async ({ page }) => {
    await page.goto('/settings/admin/crm');
    
    // Remplir les champs
    await page.fill('#crm-config-path', '/test/config');
    await page.fill('#crm-vault-path', 'test-vault');
    
    // Cliquer sur sauvegarder
    await page.click('#crm-save-settings');
    
    // Vérifier le message de succès
    const status = page.locator('#crm-save-status');
    await expect(status).toContainText('succès');
  });
});

test.describe('File Management', () => {
  test('should list markdown files', async ({ page }) => {
    await page.goto('/apps/crm');
    
    // Simuler un clic sur le bouton de liste des fichiers
    const listButton = page.locator('[data-testid="list-files"]');
    if (await listButton.isVisible()) {
      await listButton.click();
      
      // Vérifier que la liste apparaît
      const fileList = page.locator('[data-testid="file-list"]');
      await expect(fileList).toBeVisible();
    }
  });

  test('should handle file operations gracefully', async ({ page }) => {
    // Tester la gestion d'erreur pour fichier inexistant
    const response = await page.request.get('/apps/crm/files/nonexistent.md');
    expect(response.status()).toBe(404);
    
    const error = await response.json();
    expect(error).toHaveProperty('error');
  });
});

test.describe('API Integration', () => {
  test('should handle API errors gracefully', async ({ page }) => {
    // Tester la robustesse de l'API
    const endpoints = [
      '/apps/crm/api',
      '/apps/crm/files/configs',
      '/apps/crm/settings/general'
    ];
    
    for (const endpoint of endpoints) {
      const response = await page.request.get(endpoint);
      
      // L'endpoint doit soit réussir, soit retourner une erreur gérée
      expect([200, 404, 500].includes(response.status())).toBeTruthy();
      
      if (!response.ok()) {
        const error = await response.json();
        expect(error).toHaveProperty('error');
      }
    }
  });
});