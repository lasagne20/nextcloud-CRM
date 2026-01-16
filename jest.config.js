module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'jsdom',
  rootDir: '.',
  
  // Test file patterns
  testMatch: [
    '**/tests/Frontend/**/*.test.{js,ts}',
    '**/tests/Frontend/**/*.spec.{js,ts}'
  ],

  // Module file extensions
  moduleFileExtensions: ['js', 'ts', 'json', 'vue'],

  // Transform files
  transform: {
    '^.+\\.tsx?$': ['ts-jest', {
      useESM: false,
      isolatedModules: true
    }],
    '^.+\\.vue$': '@vue/vue3-jest',
    '^.+\\.js$': 'babel-jest'
  },

  // Module name mapping for aliases and mocking
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
    '^~/(.*)$': '<rootDir>/$1',
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
    '\\.(gif|ttf|eot|svg|png)$': '<rootDir>/tests/Frontend/__mocks__/fileMock.js'
  },

  // Setup files to run before tests
  setupFilesAfterEnv: [
    '<rootDir>/tests/Frontend/setup.ts'
  ],

  // Coverage configuration
  collectCoverageFrom: [
    'src/**/*.{js,ts,vue}',
    '!src/**/*.d.ts',
    '!src/main.ts'
  ],

  coverageDirectory: '<rootDir>/tests/coverage',
  coverageReporters: ['text', 'lcov', 'html'],

  // Ignore patterns
  testPathIgnorePatterns: [
    '/node_modules/',
    '/vendor/',
    '/coverage/'
  ],
  
  transformIgnorePatterns: [
    'node_modules/(?!(markdown-crm)/)'
  ],

  // Global variables
  globals: {},

  // Mock CSS and static assets
  moduleNameMapper: {
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
    '\\.(gif|ttf|eot|svg|png)$': '<rootDir>/tests/Frontend/__mocks__/fileMock.js'
  }
};