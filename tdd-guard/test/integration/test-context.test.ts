import { describe, test, expect, beforeEach } from 'vitest'
import { VitestReporter } from 'tdd-guard-vitest'
import { TestResultsProcessor } from '../../src/processors'
import { MemoryStorage } from '../../src/storage/MemoryStorage'
import { testData } from '../utils'

describe('Test Context', () => {
  describe('reporter and processor handle module import errors correctly', () => {
    let storage: MemoryStorage
    let reporter: VitestReporter
    let processor: TestResultsProcessor
    let result: string

    beforeEach(async () => {
      // Setup
      storage = new MemoryStorage()
      reporter = new VitestReporter(storage)
      processor = new TestResultsProcessor()

      // Given a module with import error
      const moduleWithError = testData.testModule({
        moduleId: '/src/utils/helpers.test.ts',
      })

      // And an import error
      const importError = testData.createUnhandledError()

      // When the reporter processes the module with error
      reporter.onTestModuleCollected(moduleWithError)
      await reporter.onTestRunEnd([], [importError])

      // And the processor formats the stored data
      const storedData = await storage.getTest()
      expect(storedData).toBeTruthy()
      result = processor.process(storedData!)
    })

    test('shows the module as passed with 0 tests', () => {
      expect(result).toContain('✓ /src/utils/helpers.test.ts (0 tests)')
    })

    test('displays unhandled errors section', () => {
      expect(result).toContain('Unhandled Errors:')
    })

    test('shows error name and message', () => {
      expect(result).toContain('× Error: Cannot find module "./helpers"')
    })

    test('includes stack trace header', () => {
      expect(result).toContain('Stack:')
    })

    test('shows import location in stack trace', () => {
      expect(result).toContain('imported from')
    })

    test('summary shows 1 passed test file', () => {
      expect(result).toContain('Test Files  1 passed (1)')
    })

    test('summary shows 0 tests', () => {
      expect(result).toContain('Tests  0 passed (0)')
    })
  })
})
