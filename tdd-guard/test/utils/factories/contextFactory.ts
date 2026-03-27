import { Context } from '../../../src/contracts/types/Context'
import { TEST_DEFAULTS } from './testDefaults'

/**
 * Creates a test Context object with sensible defaults
 */
export function context(overrides?: Partial<Context>): Context {
  return {
    modifications: TEST_DEFAULTS.modifications,
    todo: JSON.stringify([TEST_DEFAULTS.todo]),
    test: TEST_DEFAULTS.test,
    ...overrides,
  }
}

/**
 * Creates a test Context object without specific fields
 */
export function contextWithout<K extends keyof Context>(
  ...omitFields: K[]
): Omit<Context, K> {
  const fullContext = context()
  const result = { ...fullContext }

  for (const field of omitFields) {
    delete result[field]
  }

  return result as Omit<Context, K>
}
