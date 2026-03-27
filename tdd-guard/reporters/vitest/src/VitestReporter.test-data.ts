import type { TestModule, TestCase, TestResult } from 'vitest/node'
import type { SerializedError } from '@vitest/utils'

const DEFAULT_MODULE_ID = '/test/example.test.ts'
const DEFAULT_TEST_NAME = 'should pass'
const DEFAULT_TEST_FULL_NAME = 'Example Suite > should pass'

// Helper to create a valid TestResult for a given state
export function createTestResult(state: TestResult['state']): TestResult {
  switch (state) {
    case 'failed':
      return { state: 'failed', errors: [] }
    case 'passed':
      return { state: 'passed', errors: undefined }
    case 'skipped':
      return { state: 'skipped', errors: undefined, note: undefined }
    case 'pending':
      return { state: 'pending', errors: undefined }
  }
}

// Creates a minimal TestModule mock for testing
function createTestModule(props: {
  moduleId: string
  errors?: () => SerializedError[]
}): TestModule {
  return {
    moduleId: props.moduleId,
    errors: props.errors ?? ((): SerializedError[] => []),
  } as TestModule
}

export function testModule(overrides?: {
  moduleId?: string
  errors?: () => SerializedError[]
}): TestModule {
  return createTestModule({
    moduleId: overrides?.moduleId ?? DEFAULT_MODULE_ID,
    errors: overrides?.errors,
  })
}

export function createTestCase(overrides?: Partial<TestCase>): TestCase {
  const defaultModule = createTestModule({ moduleId: DEFAULT_MODULE_ID })

  return {
    name: DEFAULT_TEST_NAME,
    fullName: DEFAULT_TEST_FULL_NAME,
    module: defaultModule,
    result: () => ({ state: 'passed', errors: [] }) as TestResult,
    ...overrides,
  } as TestCase
}

export function failedTestCase(overrides?: Partial<TestCase>): TestCase {
  return createTestCase({
    name: 'should fail',
    fullName: 'Example Suite > should fail',
    result: () =>
      ({
        state: 'failed',
        errors: [
          {
            message: 'expected 2 to be 3',
            stack: 'Error: expected 2 to be 3\n    at test.ts:7:19',
            expected: '3',
            actual: '2',
          },
        ],
      }) as TestResult,
    ...overrides,
  })
}

export function createUnhandledError(
  overrides: Partial<SerializedError> = {}
): SerializedError {
  return {
    name: 'Error',
    message: 'Cannot find module "./helpers"',
    stack:
      "Error: Cannot find module './helpers' imported from '/src/example.test.ts'",
    ...overrides,
  }
}
