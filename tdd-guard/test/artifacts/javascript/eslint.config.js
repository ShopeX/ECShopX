module.exports = [
  {
    files: ['**/*.js'],
    rules: {
      'no-unused-vars': 'error',
      'no-var': 'error',
      'semi': ['error', 'always'],
      'quotes': ['error', 'single'],
      'no-duplicate-string': 'off',
      'max-params': ['error', 5]
    },
    languageOptions: {
      ecmaVersion: 2020,
      sourceType: 'module'
    }
  },
  {
    ignores: ['node_modules/**', 'dist/**', 'coverage/**']
  }
];