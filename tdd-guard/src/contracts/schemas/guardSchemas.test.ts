import { describe, test, expect } from 'vitest'
import { GuardConfigSchema } from './guardSchemas'

describe('GuardConfigSchema', () => {
  test.each([
    {
      description: 'valid config with all fields',
      config: {
        guardEnabled: true,
        ignorePatterns: ['*.md', '**/*.test.ts', 'dist/**'],
      },
      expectedSuccess: true,
    },
    {
      description: 'config with only guardEnabled',
      config: {
        guardEnabled: false,
      },
      expectedSuccess: true,
    },
    {
      description: 'config with only ignorePatterns',
      config: {
        ignorePatterns: ['*.log', 'node_modules/**'],
      },
      expectedSuccess: true,
    },
    {
      description: 'empty config object',
      config: {},
      expectedSuccess: true,
    },
    {
      description: 'invalid guardEnabled type (string)',
      config: {
        guardEnabled: 'true',
      },
      expectedSuccess: false,
    },
    {
      description: 'invalid ignorePatterns type (string)',
      config: {
        ignorePatterns: '*.md',
      },
      expectedSuccess: false,
    },
    {
      description: 'non-string items in ignorePatterns array',
      config: {
        ignorePatterns: ['*.md', 123, true],
      },
      expectedSuccess: false,
    },
  ])('$description', ({ config, expectedSuccess }) => {
    const result = GuardConfigSchema.safeParse(config)
    expect(result.success).toBe(expectedSuccess)
  })
})
