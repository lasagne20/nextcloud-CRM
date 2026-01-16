const path = require('path');

module.exports = [
  // Animation settings bundle
  {
    entry: './src/settings/AnimationSettings.ts',
    output: {
      filename: 'animation-settings.js',
      path: path.resolve(__dirname, 'js'),
    },
    resolve: {
      extensions: ['.ts', '.js'],
    },
    module: {
      rules: [
        {
          test: /\.ts$/,
          use: {
            loader: 'ts-loader',
            options: {
              transpileOnly: true,
            },
          },
          exclude: /node_modules/,
        },
      ],
    },
    mode: 'development',
  },
  // Multi-sync settings bundle
  {
    entry: './src/settings/MultiSyncSettings.ts',
    output: {
      filename: 'multi-sync-settings.js',
      path: path.resolve(__dirname, 'js'),
    },
    resolve: {
      extensions: ['.ts', '.js'],
    },
    module: {
      rules: [
        {
          test: /\.ts$/,
          use: {
            loader: 'ts-loader',
            options: {
              transpileOnly: true,
            },
          },
          exclude: /node_modules/,
        },
      ],
    },
    mode: 'development',
  },
  // Admin settings bundle (legacy)
  {
    entry: './src/settings/AdminSettings.ts',
    output: {
      filename: 'admin-settings.js',
      path: path.resolve(__dirname, 'js'),
    },
    resolve: {
      extensions: ['.ts', '.js'],
    },
    module: {
      rules: [
        {
          test: /\.ts$/,
          use: {
            loader: 'ts-loader',
            options: {
              transpileOnly: true,
              compilerOptions: {
                skipLibCheck: true,
              }
            }
          },
          exclude: /node_modules/,
        },
      ],
    },
    mode: 'development',
    devtool: 'source-map',
  },
  // Main application bundle
  {
    entry: './js/main.ts',
    output: {
      filename: 'main.js',
      path: path.resolve(__dirname, 'js'),
    },
    resolve: {
      extensions: ['.ts', '.js'],
      fallback: {
        "path": false,
        "fs": false,
        "crypto": false,
      }
    },
    module: {
      rules: [
        {
          test: /\.ts$/,
          use: {
            loader: 'ts-loader',
            options: {
              transpileOnly: true,
              compilerOptions: {
                skipLibCheck: true,
              }
            }
          },
        },
      ],
    },
    mode: 'development',
    devtool: 'source-map',
  },
  // Workflow Engine check registration
  {
    entry: './src/workflowengine-check.js',
    output: {
      filename: 'workflowengine-check.js',
      path: path.resolve(__dirname, 'js'),
    },
    mode: 'development',
    devtool: 'source-map',
  }
];

