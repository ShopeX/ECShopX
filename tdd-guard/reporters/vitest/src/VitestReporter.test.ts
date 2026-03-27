import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import type { TestModule, TestCase } from 'vitest/node'
import { VitestReporter } from './VitestReporter'
import {
  MemoryStorage,
  FileStorage,
  Storage,
  Config,
  DEFAULT_DATA_DIR,
  isFailingTest,
  isPassingTest,
  TestResult,
  Test,
} from 'tdd-guard'
import {
  testModule,
  failedTestCase,
  createTestCase,
  createUnhandledError,
  createTestResult,
} from './VitestReporter.test-data'
import type { FormattedError } from './types'
import { rmSync, mkdtempSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'

describe('VitestReporter', () => {
  let sut: Awaited<ReturnType<typeof setupVitestReporter>>
  const module = testModule()
  const passedTest = createTestCase()
  const failedTest = failedTestCase()

  beforeEach(() => {
    sut = setupVitestReporter()
  })

  afterEach(() => {
    sut.cleanup()
  })

  it('uses FileStorage by default', () => {
    const reporter = new VitestReporter()
    expect(reporter['storage']).toBeInstanceOf(FileStorage)
  })

  it('uses FileStorage when no storage provided', async () => {
    const localSut = setupVitestReporter({ type: 'file' })

    expect(localSut.reporter['storage']).toBeInstanceOf(FileStorage)

    const result = await localSut.collectAndGetSaved([
      testModule(),
      createTestCase(),
    ])

    expect(result).toBeTruthy()
    expect(result).toContain('testModules')

    localSut.cleanup()
  })

  it('accepts Storage instance in constructor', () => {
    const storage = new MemoryStorage()
    const reporter = new VitestReporter(storage)
    expect(reporter['storage']).toBe(storage)
  })

  it('accepts root path string in constructor', () => {
    const rootPath = '/some/project/root'
    const reporter = new VitestReporter(rootPath)
    expect(reporter['storage']).toBeInstanceOf(FileStorage)
    // Verify the storage is configured with the correct path
    const fileStorage = reporter['storage'] as FileStorage
    const config = fileStorage['config'] as Config
    const expectedDataDir = join(rootPath, ...DEFAULT_DATA_DIR.split('/'))
    expect(config.dataDir).toBe(expectedDataDir)
  })

  describe('when collecting test data', () => {
    beforeEach(async () => {
      sut.reporter.onTestModuleCollected(module)
      sut.reporter.onTestCaseResult(passedTest)
      sut.reporter.onTestCaseResult(failedTest)
      await sut.reporter.onTestRunEnd()
    })

    it('saves output as valid JSON', async () => {
      const parsed = await sut.getParsedData()
      expect(parsed).toBeDefined()
    })

    it('includes test modules', async () => {
      const parsed = await sut.getParsedData()

      expect(parsed).not.toBeNull()
      expect(parsed?.testModules).toHaveLength(1)
      expect(parsed?.testModules[0].moduleId).toBe(module.moduleId)
    })

    it('includes test cases', async () => {
      const tests = await sut.getTests()
      expect(tests).toHaveLength(2)
    })

    it('captures test states', async () => {
      const passedTests = await sut.getPassedTests()
      const failedTests = await sut.getFailedTests()

      expect(passedTests).toHaveLength(1)
      expect(failedTests).toHaveLength(1)
    })

    it('includes error information for failed tests', async () => {
      const failedTests = await sut.getFailedTests()
      const failedTestData = failedTests[0]

      expect(failedTestData).toBeDefined()
      expect(failedTestData.state).toBe('failed')
      expect(failedTestData.errors).toBeDefined()
      expect(failedTestData.errors?.length).toBeGreaterThan(0)
    })
  })

  describe('test state mapping', () => {
    it.each([
      ['passed', 'passed'],
      ['failed', 'failed'],
      ['skipped', 'skipped'],
      ['pending', 'skipped'], // pending gets mapped to skipped
    ] as const)('maps %s to %s', async (vitestState, expected) => {
      // Given a test with the specified state
      const testCase = createTestCase({
        result: () => createTestResult(vitestState),
      })

      // When we process the test
      sut.reporter.onTestModuleCollected(module)
      sut.reporter.onTestCaseResult(testCase)
      await sut.reporter.onTestRunEnd()

      // Then it should be mapped correctly
      const tests = await sut.getTests()
      expect(tests[0]?.state).toBe(expected)
    })
  })

  describe('error expected and actual values', () => {
    let error: FormattedError | undefined

    beforeEach(async () => {
      // Given a test with an assertion error
      sut.reporter.onTestModuleCollected(module)
      sut.reporter.onTestCaseResult(failedTest)
      await sut.reporter.onTestRunEnd()

      // When we get the failed test errors
      const failedTests = await sut.getFailedTests()
      error = failedTests[0]?.errors?.[0]
    })

    it('includes expected value in error when available', () => {
      expect(error).toHaveProperty('expected')
      expect(error?.expected).toBe('3')
    })

    it('includes actual value in error when available', () => {
      expect(error).toHaveProperty('actual')
      expect(error?.actual).toBe('2')
    })
  })

  it('handles empty test runs', async () => {
    // When no tests are collected
    await sut.reporter.onTestRunEnd()

    // Then output should be valid JSON with empty modules
    const parsed = await sut.getParsedData()

    expect(parsed).not.toBeNull()
    expect(parsed).toEqual({ testModules: [], unhandledErrors: [] })
  })

  describe('storage integration', () => {
    it('saves test output to storage', async () => {
      const result = await sut.collectAndGetSaved([
        testModule(),
        createTestCase(),
      ])

      expect(result).toBeTruthy()
      expect(result).toContain('testModules')
      expect(result).toContain('passed')
    })

    it('accumulates multiple test results in storage', async () => {
      const result = await sut.collectAndGetSaved([
        module,
        passedTest,
        failedTest,
      ])

      const parsed = JSON.parse(result!)
      expect(parsed.testModules[0].tests).toHaveLength(2)
    })
  })

  describe('stores import errors as unhandled errors', () => {
    let parsed: TestResult | null

    beforeEach(async () => {
      // Given a module that was collected but has no tests due to import error
      const moduleWithImportError = testModule({
        moduleId: '/src/example.test.ts',
        errors: () => [createUnhandledError()],
      })

      // When the test run ends
      sut.reporter.onTestModuleCollected(moduleWithImportError)
      await sut.reporter.onTestRunEnd()

      parsed = await sut.getParsedData()
    })

    it('includes the module in test modules', () => {
      expect(parsed?.testModules).toHaveLength(1)
    })

    it('shows module with one synthetic failed test', () => {
      expect(parsed?.testModules[0].tests).toHaveLength(1)
      expect(parsed?.testModules[0].tests[0].state).toBe('failed')
    })

    it('uses module filename as test name', () => {
      const syntheticTest = parsed?.testModules[0].tests[0]
      expect(syntheticTest?.name).toBe('example.test.ts')
      expect(syntheticTest?.fullName).toBe('/src/example.test.ts')
    })

    it('includes import error details in synthetic test', () => {
      const syntheticTest = parsed?.testModules[0].tests[0]
      expect(syntheticTest?.errors).toHaveLength(1)
      expect(syntheticTest?.errors?.[0].message).toBe(
        'Cannot find module "./helpers"'
      )
    })

    it('includes empty unhandled errors', () => {
      expect(parsed?.unhandledErrors).toHaveLength(0)
    })

    it('preserves error message in synthetic test', () => {
      const error = parsed?.testModules[0].tests[0].errors?.[0]
      expect(error?.message).toBe('Cannot find module "./helpers"')
    })

    it('preserves error stack trace in synthetic test', () => {
      expect(parsed?.testModules[0].tests[0].errors?.[0].stack).toContain(
        'imported from'
      )
    })
  })

  describe('handles module errors from testModule.errors()', () => {
    it('creates synthetic test when module has errors', async () => {
      // Given a module with its own errors (like import errors)
      const moduleWithErrors = testModule({
        moduleId: '/src/import-error.test.ts',
        errors: () => [createUnhandledError()],
      })

      // When the test run ends
      sut.reporter.onTestModuleCollected(moduleWithErrors)
      await sut.reporter.onTestRunEnd([], [], 'failed')

      // Then a synthetic failed test should be created
      const parsed = await sut.getParsedData()
      expect(parsed?.testModules[0].tests).toHaveLength(1)
      expect(parsed?.testModules[0].tests[0].state).toBe('failed')
    })
  })

  describe('handles testModules parameter', () => {
    it('receives test modules in onTestRunEnd', async () => {
      const module1 = testModule({ moduleId: '/test1.ts' })
      const module2 = testModule({ moduleId: '/test2.ts' })

      await sut.reporter.onTestRunEnd([module1, module2])

      const parsed = await sut.getParsedData()
      expect(parsed?.testModules).toEqual([]) // Empty because we didn't collect them
    })
  })

  describe('handles unhandled errors', () => {
    it('includes unhandled errors in output when provided', async () => {
      const unhandledError = createUnhandledError({
        message: 'Connection failed',
        name: 'NetworkError',
      })

      await sut.reporter.onTestRunEnd([], [unhandledError])

      const parsed = await sut.getParsedData()
      expect(parsed?.unhandledErrors).toEqual([
        expect.objectContaining({
          message: 'Connection failed',
          name: 'NetworkError',
        }),
      ])
    })

    it('returns empty array when no errors provided', async () => {
      await sut.reporter.onTestRunEnd()

      const parsed = await sut.getParsedData()
      expect(parsed?.unhandledErrors).toEqual([])
    })
  })

  describe('when test run ends with reason', () => {
    it('captures "failed" reason in output', async () => {
      const moduleWithImportError = testModule({
        moduleId: '/src/linters/eslint/helpers.test.ts',
      })

      sut.reporter.onTestModuleCollected(moduleWithImportError)
      await sut.reporter.onTestRunEnd([], [], 'failed')

      const parsed = await sut.getParsedData()

      expect(parsed?.reason).toBe('failed')
      // When no errors are provided, module should have no tests
      expect(parsed?.testModules[0].tests).toHaveLength(0)
    })

    it('creates synthetic test when module fails with errors', async () => {
      const moduleWithImportError = testModule({
        moduleId: '/src/failing.test.ts',
        errors: () => [createUnhandledError()],
      })

      sut.reporter.onTestModuleCollected(moduleWithImportError)
      await sut.reporter.onTestRunEnd([], [], 'failed')

      const parsed = await sut.getParsedData()

      expect(parsed?.reason).toBe('failed')
      expect(parsed?.testModules[0].tests).toHaveLength(1)
      expect(parsed?.testModules[0].tests[0].state).toBe('failed')
    })

    it('captures "interrupted" reason in output', async () => {
      await sut.reporter.onTestRunEnd([], [], 'interrupted')

      const parsed = await sut.getParsedData()
      expect(parsed?.reason).toBe('interrupted')
    })

    it('captures "passed" reason in output', async () => {
      sut.reporter.onTestModuleCollected(module)
      sut.reporter.onTestCaseResult(passedTest)
      await sut.reporter.onTestRunEnd([], [], 'passed')

      const parsed = await sut.getParsedData()
      expect(parsed?.reason).toBe('passed')
    })
  })
})

function setupVitestReporter(options?: { type: 'file' | 'memory' }) {
  const { storage, cleanup } = createTestStorage(options?.type)
  const reporter = new VitestReporter(storage)

  const collectAndGetSaved = async (
    items: Array<TestModule | TestCase>
  ): Promise<string | null> => {
    collectTestData(reporter, items)
    await reporter.onTestRunEnd()
    return storage.getTest()
  }

  const getParsedData = async (): Promise<TestResult | null> => {
    const content = await storage.getTest()
    return content ? JSON.parse(content) : null
  }

  const getTests = async (): Promise<Test[]> => {
    return getTestsFromStorage(storage)
  }

  const getPassedTests = async (): Promise<(Test & { state: 'passed' })[]> => {
    const tests = await getTests()
    return tests.filter(isPassingTest)
  }

  const getFailedTests = async (): Promise<(Test & { state: 'failed' })[]> => {
    const tests = await getTests()
    return tests.filter(isFailingTest)
  }

  return {
    reporter,
    storage,
    collectAndGetSaved,
    getParsedData,
    getTests,
    getPassedTests,
    getFailedTests,
    cleanup,
  }
}

function createTestStorage(type: 'file' | 'memory' = 'memory'): {
  storage: Storage
  cleanup: () => void
} {
  if (type === 'file') {
    const projectRoot = mkdtempSync(join(tmpdir(), 'vitest-reporter-test-'))
    const config = new Config({ projectRoot })
    const storage = new FileStorage(config)
    const cleanup = () => rmSync(projectRoot, { recursive: true, force: true })
    return { storage, cleanup }
  }

  return { storage: new MemoryStorage(), cleanup: () => {} }
}

function collectTestData(
  reporter: VitestReporter,
  items: Array<TestModule | TestCase>
): void {
  for (const item of items) {
    if ('moduleId' in item && !('module' in item)) {
      reporter.onTestModuleCollected(item as TestModule)
    } else {
      reporter.onTestCaseResult(item as TestCase)
    }
  }
}

async function getTestsFromStorage(storage: Storage): Promise<Test[]> {
  const content = await storage.getTest()
  if (!content) return []
  const parsed: TestResult = JSON.parse(content)
  return parsed.testModules[0]?.tests ?? []
}
