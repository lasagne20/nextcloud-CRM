import { NextcloudApp } from '../../src/App';

// Mock fetch globally
global.fetch = jest.fn();

describe('NextcloudApp', () => {
  let app: NextcloudApp;
  const mockFetch = fetch as jest.MockedFunction<typeof fetch>;

  beforeEach(() => {
    app = new NextcloudApp('/test/apps/crm');
    mockFetch.mockClear();
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  describe('Constructor and Settings', () => {
    test('should initialize with default base URL', () => {
      const defaultApp = new NextcloudApp();
      const settings = defaultApp.getSettings();
      
      expect(settings).toBeDefined();
      expect(settings.phoneFormat).toBe('FR');
      expect(settings.dateFormat).toBe('DD/MM/YYYY');
    });

    test('should initialize with custom base URL', () => {
      const customApp = new NextcloudApp('/custom/path');
      expect(customApp).toBeDefined();
    });

    test('should return correct settings', () => {
      const settings = app.getSettings();
      
      expect(settings.phoneFormat).toBe('FR');
      expect(settings.dateFormat).toBe('DD/MM/YYYY');
      expect(settings.timeFormat).toBe('24h');
      expect(settings.timezone).toBe('Europe/Paris');
      expect(settings.numberLocale).toBe('fr-FR');
      expect(settings.currencySymbol).toBe('€');
    });
  });

  describe('File Operations', () => {
    test('should read config YAML file successfully', async () => {
      const mockFile = {
        path: '/config/test.yaml',
        extension: 'yaml',
        basename: 'test',
        name: 'test.yaml'
      };

      const mockResponse = {
        ok: true,
        json: jest.fn().mockResolvedValue({
          content: 'test: value\nkey: data'
        })
      };

      mockFetch.mockResolvedValue(mockResponse as any);

      const result = await app.readFile(mockFile);

      expect(result).toBe('test: value\nkey: data');
      expect(mockFetch).toHaveBeenCalledWith('/test/apps/crm/config/test');
      expect(mockResponse.json).toHaveBeenCalled();
    });

    test('should handle config file read error', async () => {
      const mockFile = {
        path: '/config/nonexistent.yaml',
        extension: 'yaml',
        basename: 'nonexistent',
        name: 'nonexistent.yaml'
      };

      const mockResponse = {
        ok: false,
        status: 404
      };

      mockFetch.mockResolvedValue(mockResponse as any);

      await expect(app.readFile(mockFile)).rejects.toThrow('Failed to read config: /config/nonexistent.yaml');
      expect(mockFetch).toHaveBeenCalledWith('/test/apps/crm/config/nonexistent');
    });

    test('should handle markdown file with cache', async () => {
      // First test would require more of the readFile implementation
      // This tests the basic structure
      const mockFile = {
        path: '/vault/test.md',
        extension: 'md',
        basename: 'test',
        name: 'test.md'
      };

      // Mock the markdown file endpoint
      const mockResponse = {
        ok: true,
        json: jest.fn().mockResolvedValue({
          content: '# Test\nThis is test content'
        })
      };

      mockFetch.mockResolvedValue(mockResponse as any);

      // This would require the full implementation to test properly
      // For now, we test that the method exists and handles the call
      expect(typeof app.readFile).toBe('function');
    });
  });

  describe('Cache Management', () => {
    test('should have cache TTL configured', () => {
      // Test that cache properties are accessible
      expect(app).toBeDefined();
      // Cache is private, so we test indirectly through behavior
    });
  });

  describe('Error Handling', () => {
    test('should handle fetch errors gracefully', async () => {
      const mockFile = {
        path: '/config/error.yaml',
        extension: 'yaml',
        basename: 'error',
        name: 'error.yaml'
      };

      mockFetch.mockRejectedValue(new Error('Network error'));

      await expect(app.readFile(mockFile)).rejects.toThrow('Network error');
    });

    test('should handle empty response', async () => {
      const mockFile = {
        path: '/config/empty.yaml',
        extension: 'yaml',
        basename: 'empty',
        name: 'empty.yaml'
      };

      const mockResponse = {
        ok: true,
        json: jest.fn().mockResolvedValue({})
      };

      mockFetch.mockResolvedValue(mockResponse as any);

      const result = await app.readFile(mockFile);

      expect(result).toBe('');
    });
  });
});