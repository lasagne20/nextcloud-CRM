// Jest setup file for frontend tests

// Extend global interface to avoid TypeScript errors
export {};

declare global {
  var OC: any;
  var OCA: any;
  var t: any;
  var n: any;
}

// Mock Nextcloud globals
(global as any).OC = {
  generateUrl: (url: string) => `/apps/crm${url}`,
  requestToken: 'test-token',
  webroot: '',
  appswebroots: {
    crm: '/apps/crm'
  }
};

(global as any).OCA = {
  CRM: {}
};

(global as any).t = (app: string, text: string) => text;
(global as any).n = (app: string, singular: string, plural: string, count: number) => 
  count === 1 ? singular : plural;

// Mock axios
jest.mock('axios', () => ({
  default: {
    get: jest.fn(),
    post: jest.fn(),
    put: jest.fn(),
    delete: jest.fn(),
    create: jest.fn(() => ({
      get: jest.fn(),
      post: jest.fn(),
      put: jest.fn(),
      delete: jest.fn()
    }))
  }
}));

// Setup DOM environment
if (typeof document !== 'undefined') {
  document.body.innerHTML = '<div id="app"></div>';
}