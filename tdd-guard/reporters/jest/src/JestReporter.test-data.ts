import type { Test, TestResult, AggregatedResult } from '@jest/reporters'
import type { Config } from '@jest/types'

// Create a minimal snapshot object that satisfies the type requirements
const createSnapshot = (): TestResult['snapshot'] =>
  ({
    added: 0,
    didUpdate: false,
    failure: false,
    filesAdded: 0,
    filesRemoved: 0,
    filesRemovedList: [],
    filesUnmatched: 0,
    filesUpdated: 0,
    matched: 0,
    total: 0,
    unchecked: 0,
    uncheckedKeysByFile: [],
    unmatched: 0,
    updated: 0,
    // Additional properties that might be required by different versions
    fileDeleted: false,
    uncheckedKeys: [],
  }) as TestResult['snapshot']

// Create a minimal snapshot summary for AggregatedResult
const createSnapshotSummary = (): AggregatedResult['snapshot'] =>
  ({
    added: 0,
    didUpdate: false,
    failure: false,
    filesAdded: 0,
    filesRemoved: 0,
    filesRemovedList: [],
    filesUnmatched: 0,
    filesUpdated: 0,
    matched: 0,
    total: 0,
    unchecked: 0,
    uncheckedKeysByFile: [],
    unmatched: 0,
    updated: 0,
  }) as AggregatedResult['snapshot']

// Create a minimal Test object
export function createTest(overrides?: Partial<Test>): Test {
  // For test purposes, we create minimal mock implementations
  const mockContext = {
    config: {} as Config.ProjectConfig,
    hasteFS: {} as never, // Using never since we don't access these properties
    moduleMap: {} as never, // Using never since we don't access these properties
    resolver: {} as never, // Using never since we don't access these properties
  }

  return {
    context: mockContext,
    duration: 100,
    path: '/test/example.test.ts',
    ...overrides,
  } as Test
}

// Create a minimal TestResult object
export function createTestResult(overrides?: Partial<TestResult>): TestResult {
  const base: TestResult = {
    leaks: false,
    numFailingTests: 0,
    numPassingTests: 1,
    numPendingTests: 0,
    numTodoTests: 0,
    openHandles: [],
    perfStats: {
      end: 1000,
      runtime: 100,
      slow: false,
      start: 900,
      loadTestEnvironmentEnd: 950,
      loadTestEnvironmentStart: 920,
      setupAfterEnvEnd: 980,
      setupAfterEnvStart: 960,
      setupFilesEnd: 940,
      setupFilesStart: 930,
    },
    skipped: false,
    snapshot: createSnapshot(),
    testExecError: undefined,
    testFilePath: '/test/example.test.ts',
    testResults: [
      {
        ancestorTitles: ['Example Suite'],
        duration: 5,
        failureDetails: [],
        failureMessages: [],
        fullName: 'Example Suite should pass',
        invocations: 1,
        location: undefined,
        numPassingAsserts: 0,
        retryReasons: [],
        status: 'passed',
        title: 'should pass',
      },
    ],
    ...overrides,
  }

  // If test is failing, update the test results
  if (overrides?.numFailingTests && overrides.numFailingTests > 0) {
    base.testResults = [
      {
        ancestorTitles: ['Example Suite'],
        duration: 5,
        failureDetails: [{}],
        failureMessages: ['expected 2 to be 3'],
        fullName: 'Example Suite should fail',
        invocations: 1,
        location: undefined,
        numPassingAsserts: 0,
        retryReasons: [],
        status: 'failed',
        title: 'should fail',
      },
    ]
  }

  return base
}

// Create a minimal AggregatedResult object
export function createAggregatedResult(
  overrides?: Partial<AggregatedResult>
): AggregatedResult {
  return {
    numFailedTestSuites: 0,
    numFailedTests: 0,
    numPassedTestSuites: 1,
    numPassedTests: 1,
    numPendingTestSuites: 0,
    numPendingTests: 0,
    numRuntimeErrorTestSuites: 0,
    numTodoTests: 0,
    numTotalTestSuites: 1,
    numTotalTests: 1,
    openHandles: [],
    runExecError: undefined,
    snapshot: createSnapshotSummary(),
    startTime: Date.now(),
    success: true,
    testResults: [],
    wasInterrupted: false,
    ...overrides,
  }
}

export function createUnhandledError(
  overrides: Partial<{ name: string; message: string; stack: string }> = {}
): AggregatedResult['runExecError'] {
  return {
    message: overrides.message ?? 'Cannot find module "./helpers"',
    stack:
      overrides.stack ??
      "Error: Cannot find module './helpers' imported from '/src/example.test.ts'",
    ...(overrides.name && { name: overrides.name }),
    // SerializableError might have additional properties but these are the required ones
  }
}

// Create a module error (testExecError) for import failures
export function createModuleError(
  overrides: Partial<{
    name: string
    message: string
    stack: string
    type: string
    code: string
  }> = {}
): TestResult['testExecError'] {
  return {
    message: overrides.message ?? "Cannot find module './non-existent-module'",
    stack:
      overrides.stack ??
      "Error: Cannot find module './non-existent-module'\n    at Resolver.resolveModule",
    ...(overrides.name && { name: overrides.name }),
    ...(overrides.type && { type: overrides.type }),
    ...(overrides.code && { code: overrides.code }),
  }
}

// Create a TestResult with module import error
export function createTestResultWithModuleError(
  overrides?: Partial<TestResult>
): TestResult {
  return createTestResult({
    testExecError: createModuleError(),
    testResults: [], // No test results when module fails to load
    numFailingTests: 0,
    numPassingTests: 0,
    ...overrides,
  })
}
