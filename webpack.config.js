// webpack.config.js
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: './src/index.js', // your JS entry point
  output: {
    filename: 'index.js',
    path: path.resolve(__dirname, 'build'),
    clean: true, // cleans dist folder before build
  },
  module: {
    rules: [
      {
        test: /\.scss$/i,
        use: [
          MiniCssExtractPlugin.loader, // extract CSS into separate file
          'css-loader',               // turn CSS into JS
          'sass-loader'               // compile SCSS to CSS
        ],
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'style.css', // name of the compiled CSS file
    }),
  ],
  mode: 'production', // change to 'development' when testing
};
