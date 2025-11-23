const path = require('path');

module.exports = [
  // Admin settings bundle
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
  }
];

