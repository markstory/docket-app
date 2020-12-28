/*eslint-env node*/
module.exports = {
  presets: ['@babel/react', '@babel/env', '@babel/preset-typescript'],
  plugins: ['@babel/plugin-transform-runtime'],
  env: {
    test: {
      // Required, see https://github.com/facebook/jest/issues/9430
      plugins: ['dynamic-import-node'],
    },
  },
};
