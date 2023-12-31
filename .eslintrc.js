module.exports = {
  parser: '@typescript-eslint/parser',
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: 'module',
    ecmaFeatures: {
      jsx: true,
    },
  },
  plugins: ['jest'],
  extends: [
    // Prettier rules need to be at the end as they tweak other plugins.
    'prettier',
    'plugin:prettier/recommended',
    'prettier/@typescript-eslint',
  ],
  rules: {},
};
