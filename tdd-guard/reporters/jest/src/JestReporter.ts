import { BaseReporter, Config } from '@jest/reporters'
import type { Test, TestResult, AggregatedResult } from '@jest/reporters'
import type { AssertionResult } from '@jest/test-result'
import type { TestContext } from '@jest/test-result'
import { Storage, FileStorage, Config as TDDConfig } from 'tdd-guard'
import type {
  TDDGuardReporterOptions,
  CapturedError,
  CapturedTest,
  CapturedTestRun,
  CapturedUnhandledError,
  CapturedModule,
} from './types'

export class JestReporter extends BaseReporter {
  private readonly storage: Storage
  private readonly testModules: Map<
    string,
    { test: Test; testResult: TestResult }
  > = new Map()

  constructor(
    _globalConfig?: Config.GlobalConfig,
    reporterOptions?: TDDGuardReporterOptions
  ) {
    super()
    this.storage = this.initializeStorage(reporterOptions)
  }

  private initializeStorage(options?: TDDGuardReporterOptions): Storage {
    if (options?.storage) {
      return options.storage
    }

    if (options?.projectRoot) {
      const config = new TDDConfig({ projectRoot: options.projectRoot })
      return new FileStorage(config)
    }

    return new FileStorage()
  }

  override onTestResult(test: Test, testResult: TestResult): void {
    this.testModules.set(test.path, { test, testResult })
  }

  override async onRunComplete(
    _contexts: Set<TestContext>,
    results: AggregatedResult
  ): Promise<void> {
    const output: CapturedTestRun = {
      testModules: this.buildTestModules(),
      unhandledErrors: this.buildUnhandledErrors(results),
      reason: this.determineTestRunReason(results),
    }

    await this.storage.saveTest(JSON.stringify(output, null, 2))
  }

  private determineTestRunReason(
    results: AggregatedResult
  ): 'passed' | 'failed' | 'interrupted' {
    if (results.wasInterrupted) {
      return 'interrupted'
    }

    if (results.numFailedTestSuites === 0 && results.numTotalTestSuites > 0) {
      return 'passed'
    }

    return 'failed'
  }

  private mapTestResult(test: AssertionResult): CapturedTest {
    const result: CapturedTest = {
      name: test.title,
      fullName: test.fullName,
      state: test.status,
    }

    // Process failure details if present
    if (test.failureMessages.length > 0) {
      result.errors = this.processTestErrors(test)
    }

    return result
  }

  private processTestErrors(test: AssertionResult): CapturedError[] {
    if (test.failureDetails.length === 0) {
      return test.failureMessages.map((message) => ({ message }))
    }

    return test.failureDetails.map((detail: unknown, index: number) => {
      const message = test.failureMessages[index] || ''
      const error: CapturedError = { message }

      if (detail && typeof detail === 'object') {
        this.extractErrorDetails(error, detail as Record<string, unknown>)
        this.parseExpectedActualFromMessage(error, message)
      }

      return error
    })
  }

  private extractErrorDetails(
    error: CapturedError,
    obj: Record<string, unknown>
  ): void {
    if ('actual' in obj) error.actual = String(obj.actual)
    if ('expected' in obj) error.expected = String(obj.expected)
    if ('showDiff' in obj) error.showDiff = Boolean(obj.showDiff)
    if ('operator' in obj) error.operator = String(obj.operator)
    if ('diff' in obj) error.diff = String(obj.diff)
    if ('name' in obj) error.name = String(obj.name)
    if ('ok' in obj) error.ok = Boolean(obj.ok)
    if ('stack' in obj) error.stack = String(obj.stack)
  }

  private parseExpectedActualFromMessage(
    error: CapturedError,
    message: string
  ): void {
    if (!error.expected || !error.actual) {
      const expectedMatch = /Expected:\s*(\d+)/.exec(message)
      const receivedMatch = /Received:\s*(\d+)/.exec(message)
      if (expectedMatch && !error.expected) error.expected = expectedMatch[1]
      if (receivedMatch && !error.actual) error.actual = receivedMatch[1]
    }
  }

  private createTestFromExecError(execError: unknown): CapturedTest {
    const errorObj = execError as Record<string, unknown>
    const message = String(errorObj.message ?? 'Unknown error')

    const error: CapturedError = {
      message,
      name: typeof errorObj.name === 'string' ? errorObj.name : 'Error',
      stack: typeof errorObj.stack === 'string' ? errorObj.stack : undefined,
    }

    // Extract additional fields from Jest's SerializableError
    if ('code' in errorObj && errorObj.code !== undefined) {
      error.operator = String(errorObj.code)
    }
    if ('type' in errorObj && typeof errorObj.type === 'string') {
      error.name = errorObj.type
    }

    const errorType = error.name ?? 'Error'
    const testName = `Module failed to load (${errorType})`

    return {
      name: testName,
      fullName: testName,
      state: 'failed',
      errors: [error],
    }
  }

  private buildTestModules(): CapturedModule[] {
    return Array.from(this.testModules.entries()).map(([path, data]) => {
      const { testResult } = data

      // Handle module/import errors
      if (testResult.testExecError && testResult.testResults.length === 0) {
        return {
          moduleId: path,
          tests: [this.createTestFromExecError(testResult.testExecError)],
        }
      }

      return {
        moduleId: path,
        tests: testResult.testResults.map((test: AssertionResult) =>
          this.mapTestResult(test)
        ),
      }
    })
  }

  private buildUnhandledErrors(
    results: AggregatedResult
  ): CapturedUnhandledError[] {
    if (!results.runExecError) {
      return []
    }

    const error = results.runExecError
    const errorObj = error as Record<string, unknown>

    return [
      {
        message: String(errorObj.message ?? 'Unknown error'),
        name: typeof errorObj.name === 'string' ? errorObj.name : 'Error',
        stack: typeof errorObj.stack === 'string' ? errorObj.stack : undefined,
      },
    ]
  }
}
