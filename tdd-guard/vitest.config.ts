import { defineConfig } from 'vitest/config'
import { VitestReporter } from 'tdd-guard-vitest'
import path from 'path'

const root = path.resolve(__dirname)

// Shared test configuration
const baseTestConfig = {
  globals: true,
  environment: 'node',
  testTimeout: 120000,
  exclude: ['**/node_modules/**', '**/dist/**', '**/artifacts/**'],
}

// Shared resolve configuration
const baseResolveConfig = {
  alias: {
    '@testUtils': path.resolve(__dirname, './test/utils/index.ts'),
  },
}

export default defineConfig({
  test: {
    reporters: ['default', new VitestReporter(root)],
    projects: [
      {
        test: {
          ...baseTestConfig,
          name: 'golangci-lint',
          include: ['src/linters/golangci/**/*.test.ts'],
          pool: 'forks', // Use forks for tests that need process.chdir
        },
        resolve: baseResolveConfig,
      },
      {
        test: {
          ...baseTestConfig,
          name: 'unit',
          include: ['**/*.test.ts'],
          exclude: [
            ...baseTestConfig.exclude,
            'src/linters/golangci/**/*.test.ts', // Handled by golangci-lint project
            '**/test/integration/**',
            '**/reporters/**',
          ],
          pool: 'threads',
        },
        resolve: baseResolveConfig,
      },
      {
        test: {
          ...baseTestConfig,
          name: 'integration',
          include: ['test/integration/**/*.test.ts'],
          pool: 'threads',
        },
        resolve: baseResolveConfig,
      },
      {
        test: {
          ...baseTestConfig,
          name: 'reporters',
          include: ['reporters/**/*.test.ts'],
          pool: 'threads',
        },
        resolve: baseResolveConfig,
      },
      {
        test: {
          ...baseTestConfig,
          name: 'default',
          include: ['**/*.test.ts'],
          exclude: [
            ...baseTestConfig.exclude,
            'src/linters/golangci/**/*.test.ts', // Handled by golangci-lint project
            '**/test/integration/validator.scenarios.test.ts', // Extensive test suite - run separately for faster feedback
          ],
          pool: 'threads',
        },
        resolve: baseResolveConfig,
      },
    ],
  },
  resolve: baseResolveConfig,
})
