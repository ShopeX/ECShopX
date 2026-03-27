import { describe, it, expect, beforeEach } from 'vitest'
import { JestReporter } from './JestReporter'
import {
  FileStorage,
  MemoryStorage,
  Config as TDDConfig,
  DEFAULT_DATA_DIR,
} from 'tdd-guard'
import path from 'node:path'
import {
  createTest,
  createTestResult,
  createAggregatedResult,
  createUnhandledError,
  createModuleError,
  createTestResultWithModuleError,
} from './JestReporter.test-data'

describe('JestReporter', () => {
  let sut: ReturnType<typeof setupJestReporter>

  beforeEach(() => {
    sut = setupJestReporter()
  })

  describe('constructor', () => {
    it('uses FileStorage by default', () => {
      const reporter = new JestReporter()
      expect(reporter['storage']).toBeInstanceOf(FileStorage)
    })

    it('accepts Storage instance in reporterOptions', () => {
      const storage = new MemoryStorage()
      const globalConfig = undefined
      const reporterOptions = { storage }
      const reporter = new JestReporter(globalConfig, reporterOptions)
      expect(reporter['storage']).toBe(storage)
    })

    it('accepts projectRoot string in reporterOptions', () => {
      const rootPath = '/some/project/root'
      const globalConfig = undefined
      const reporterOptions = { projectRoot: rootPath }
      const reporter = new JestReporter(globalConfig, reporterOptions)

      // Verify the storage is configured with the correct path
      const fileStorage = reporter['storage'] as FileStorage
      const config = fileStorage['config'] as TDDConfig
      const expectedDataDir = path.join(
        rootPath,
        ...DEFAULT_DATA_DIR.split('/')
      )
      expect(config.dataDir).toBe(expectedDataDir)
    })
  })

  describe('onTestResult', () => {
    it('collects test results', () => {
      const test = createTest()
      const testResult = createTestResult()

      sut.reporter.onTestResult(test, testResult)

      expect(sut.reporter['testModules'].size).toBe(1)
    })
  })

  describe('onRunComplete', () => {
    it('saves test results to storage', async () => {
      const test = createTest()
      const testResult = createTestResult()
      const aggregatedResult = createAggregatedResult()

      // Collect test results first
      sut.reporter.onTestResult(test, testResult)

      // Run complete
      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      // Verify results were saved
      const parsed = await sut.getParsedData()
      expect(parsed).toBeTruthy()
      expect(parsed.testModules).toHaveLength(1)
    })

    it('includes test case details in output', async () => {
      const test = createTest()
      const testResult = createTestResult()
      const aggregatedResult = createAggregatedResult()

      // Collect test results
      sut.reporter.onTestResult(test, testResult)

      // Run complete
      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      // Verify test details are included
      const parsed = await sut.getParsedData()
      const module = parsed.testModules[0]
      expect(module.tests).toHaveLength(1)
      expect(module.tests[0].name).toBe('should pass')
      expect(module.tests[0].fullName).toBe('Example Suite should pass')
      expect(module.tests[0].state).toBe('passed')
    })

    it('includes error details for failed tests', async () => {
      const test = createTest()
      const failedTestResult = createTestResult({ numFailingTests: 1 })
      const aggregatedResult = createAggregatedResult()

      // Collect test results
      sut.reporter.onTestResult(test, failedTestResult)

      // Run complete
      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      // Verify error details are included
      const parsed = await sut.getParsedData()
      const module = parsed.testModules[0]
      const failedTest = module.tests[0]
      expect(failedTest.state).toBe('failed')
      expect(failedTest.errors).toBeDefined()
      expect(failedTest.errors).toHaveLength(1)
      expect(failedTest.errors[0].message).toBe('expected 2 to be 3')
    })

    it('handles empty test runs', async () => {
      // Run complete without any tests
      await sut.reporter.onRunComplete(new Set(), createAggregatedResult())

      // Verify empty output
      const parsed = await sut.getParsedData()
      expect(parsed.testModules).toEqual([])
    })

    it('includes unhandled errors in output', async () => {
      const error = createUnhandledError()
      const aggregatedResult = createAggregatedResult({
        runExecError: error,
      })

      // Run complete with unhandled error
      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      // Verify unhandled errors are included
      const parsed = await sut.getParsedData()
      expect(parsed.unhandledErrors).toBeDefined()
      expect(parsed.unhandledErrors).toHaveLength(1)
      expect(parsed.unhandledErrors[0].message).toBe(
        'Cannot find module "./helpers"'
      )
      expect(parsed.unhandledErrors[0].name).toBe('Error')
      expect(parsed.unhandledErrors[0].stack).toContain('imported from')
    })

    it('includes test run reason when tests pass', async () => {
      const test = createTest()
      const testResult = createTestResult()
      const aggregatedResult = createAggregatedResult({
        success: true,
        numFailedTests: 0,
      })

      sut.reporter.onTestResult(test, testResult)
      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      const parsed = await sut.getParsedData()
      expect(parsed.reason).toBe('passed')
    })

    it('handles SerializableError without name property', async () => {
      const aggregatedResult = createAggregatedResult({
        runExecError: {
          message: 'Module not found',
          stack: 'at test.js:1:1',
        },
      })

      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      const parsed = await sut.getParsedData()
      expect(parsed.unhandledErrors[0].message).toBe('Module not found')
      expect(parsed.unhandledErrors[0].name).toBe('Error')
      expect(parsed.unhandledErrors[0].stack).toBe('at test.js:1:1')
    })

    it('includes module import errors as failed tests', async () => {
      const test = createTest()
      const testResult = createTestResultWithModuleError()
      const aggregatedResult = createAggregatedResult()

      sut.reporter.onTestResult(test, testResult)
      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      const parsed = await sut.getParsedData()
      const module = parsed.testModules[0]
      expect(module.tests).toHaveLength(1)

      const importErrorTest = module.tests[0]
      expect(importErrorTest.name).toBe('Module failed to load (Error)')
      expect(importErrorTest.fullName).toBe('Module failed to load (Error)')
      expect(importErrorTest.state).toBe('failed')
      expect(importErrorTest.errors).toHaveLength(1)
      expect(importErrorTest.errors[0].message).toBe(
        "Cannot find module './non-existent-module'"
      )
    })

    it('preserves error stack trace from module import errors', async () => {
      const test = createTest()
      const moduleError = createModuleError({
        message: "Cannot find module './helpers'",
        stack:
          "Error: Cannot find module './helpers'\n    at Function.Module._resolveFilename",
        name: 'Error',
      })
      const testResult = createTestResultWithModuleError({
        testExecError: moduleError,
      })
      const aggregatedResult = createAggregatedResult()

      sut.reporter.onTestResult(test, testResult)
      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      const parsed = await sut.getParsedData()
      const importErrorTest = parsed.testModules[0].tests[0]

      expect(importErrorTest.errors[0].stack).toBe(
        "Error: Cannot find module './helpers'\n    at Function.Module._resolveFilename"
      )
      expect(importErrorTest.errors[0].name).toBe('Error')
    })

    it('uses error type in test name for module import errors', async () => {
      const test = createTest()
      const testResult = createTestResultWithModuleError({
        testExecError: createModuleError({
          message: 'Module parse failed',
          stack: 'SyntaxError: Unexpected token',
          name: 'SyntaxError',
        }),
      })
      const aggregatedResult = createAggregatedResult()

      sut.reporter.onTestResult(test, testResult)
      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      const parsed = await sut.getParsedData()
      const importErrorTest = parsed.testModules[0].tests[0]

      expect(importErrorTest.name).toBe('Module failed to load (SyntaxError)')
      expect(importErrorTest.fullName).toBe(
        'Module failed to load (SyntaxError)'
      )
    })

    it('handles SerializableError with type field for module import errors', async () => {
      const test = createTest()
      const testResult = createTestResultWithModuleError({
        testExecError: createModuleError({
          message: 'Module error',
          stack: 'at test.js:1',
          type: 'ReferenceError',
          code: 'ERR_MODULE_NOT_FOUND',
        }),
      })
      const aggregatedResult = createAggregatedResult()

      sut.reporter.onTestResult(test, testResult)
      await sut.reporter.onRunComplete(new Set(), aggregatedResult)

      const parsed = await sut.getParsedData()
      const importErrorTest = parsed.testModules[0].tests[0]

      expect(importErrorTest.name).toBe(
        'Module failed to load (ReferenceError)'
      )
      expect(importErrorTest.errors[0].name).toBe('ReferenceError')
      expect(importErrorTest.errors[0].operator).toBe('ERR_MODULE_NOT_FOUND')
    })
  })
})

// Test setup helper function
function setupJestReporter() {
  const storage = new MemoryStorage()
  const globalConfig = undefined
  const reporterOptions = { storage }
  const reporter = new JestReporter(globalConfig, reporterOptions)

  // Helper to get parsed test data
  const getParsedData = async () => {
    const savedData = await storage.getTest()
    return savedData ? JSON.parse(savedData) : null
  }

  return {
    reporter,
    storage,
    getParsedData,
  }
}
