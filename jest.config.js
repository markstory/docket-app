/*eslint-env node*/
module.exports = {
  verbose: false,
  collectCoverageFrom: [
    'tests/js/spec/**/*.{js,jsx,tsx}',
    'assets/js/**/*.{js,jsx,ts,tsx}',
  ],
  coverageReporters: ['html', 'cobertura'],
  coverageDirectory: '.artifacts/coverage',
  moduleNameMapper: {
    '\\.(css|less|png|jpg|mp4|svg)$': '<rootDir>/tests/js/__mocks__/styleMock.js',
    '^app(.*)$': '<rootDir>/assets/js$1',
  },
  modulePaths: ['<rootDir>/assets/js'],
  testMatch: ['<rootDir>/tests/js/**/*(*.)@(spec|test).(js|ts)?(x)'],

  unmockedModulePathPatterns: ['<rootDir>/node_modules/react'],
  transform: {
    '^.+\\.jsx?$': 'babel-jest',
    '^.+\\.tsx?$': 'babel-jest',
  },
  moduleFileExtensions: ['js', 'ts', 'jsx', 'tsx'],
  globals: {},
  setupFiles: ['<rootDir>/tests/js/setup.ts'],

  reporters: ['default'],
};
