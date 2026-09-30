const path = require('path');

module.exports = {
  mode: 'production',
  entry: {
    lknCieloDebit: './resources/js/debitCard/lknCieloDebit.js',
    lknCieloCredit: './resources/js/creditCard/lknCieloCredit.js',
    lknCieloAnalytics: './resources/js/analytics/lknCieloAnalytics.tsx',
    // JS do layout compacto (checkout de Blocos). Fica no webpack para ser
    // gerado/minificado pelo `npm run build`.
    lknCieloDebitCompact: './resources/js/debitCard/lkn-wc-gateway-debit-compact-layout.js',
    // JS do layout compacto (checkout clássico/shortcode): animação das bandeiras.
    lknCieloDebitCompactClassic: './resources/js/debitCard/lkn-cielo-debit-compact-classic.js',
  },
  output: {
    filename: (pathData) => {
      const name = pathData.chunk.name;
      const entryPath = module.exports.entry[name];
      const baseName = path.basename(entryPath, path.extname(entryPath));
      return entryPath.replace(
        path.basename(entryPath),
        `${baseName}Compiled.js`
      );
    },
    path: path.resolve(__dirname)
  },
  externals: {
    '@wordpress/i18n': 'wp.i18n',
    '@wordpress/hooks': 'wp.hooks',
    '@wordpress/element': 'wp.element',
    '@woocommerce/components': 'wc.components',
    'react': 'React',
    'react-dom': 'ReactDOM'
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx|ts|tsx)$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env', '@babel/preset-react', '@babel/preset-typescript']
          }
        }
      },
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader']
      }
    ]
  },
  resolve: {
    extensions: ['.tsx', '.ts', '.js', '.jsx']
  }
};