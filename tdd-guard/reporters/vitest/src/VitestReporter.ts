import { Reporter, TestModule, TestCase, TestRunEndReason } from 'vitest/node'
import type { SerializedError } from '@vitest/utils'
import { Storage, FileStorage, Config } from 'tdd-guard'
import { basename } from 'node:path'
import type {
  CollectedModuleData,
  FormattedError,
  FormattedTest,
  ModuleDataMap,
  ModuleResult,
  TestRunOutput,
} from './types'

export class VitestReporter implements Reporter {
  private readonly storage: Storage
  private readonly collectedData: ModuleDataMap = new Map()

  constructor(storageOrRoot?: Storage | string) {
    this.storage =
      typeof storageOrRoot === 'string'
        ? new FileStorage(new Config({ projectRoot: storageOrRoot }))
        : (storageOrRoot ?? new FileStorage())
  }

  onTestModuleCollected(testModule: TestModule): void {
    this.collectedData.set(testModule.moduleId, {
      module: testModule,
      tests: [],
    })
  }

  onTestCaseResult(testCase: TestCase): void {
    const moduleId = testCase.module.moduleId
    if (!moduleId) return

    this.collectedData.get(moduleId)?.tests.push(testCase)
  }

  async onTestRunEnd(
    _testModules?: ReadonlyArray<TestModule>,
    unhandledErrors?: ReadonlyArray<SerializedError>,
    reason?: TestRunEndReason
  ): Promise<void> {
    // _testModules contains only module metadata, we use collected data from callbacks
    const formattedModules = formatAllModuleResults(this.collectedData)
    const output = createTestRunOutput(
      formattedModules,
      unhandledErrors,
      reason
    )
    await this.storage.saveTest(JSON.stringify(output, null, 2))
  }
}

function createTestRunOutput(
  testModules: ModuleResult[],
  unhandledErrors?: ReadonlyArray<SerializedError>,
  reason?: TestRunEndReason
): TestRunOutput {
  return {
    testModules,
    unhandledErrors: unhandledErrors ?? [],
    ...(reason && { reason }),
  }
}

function formatAllModuleResults(collectedData: ModuleDataMap): ModuleResult[] {
  return Array.from(collectedData.values()).map((data) => ({
    moduleId: data.module.moduleId,
    tests: moduleFailedToLoad(data)
      ? createTestForFailedModule(data)
      : formatNormalTests(data),
  }))
}

function moduleFailedToLoad(data: CollectedModuleData): boolean {
  return data.module.errors().length > 0 && data.tests.length === 0
}

function createTestForFailedModule(data: CollectedModuleData): FormattedTest[] {
  return [
    {
      name: basename(data.module.moduleId),
      fullName: data.module.moduleId,
      state: 'failed',
      errors: data.module.errors().map(formatError),
    },
  ]
}

function formatNormalTests(data: CollectedModuleData): FormattedTest[] {
  return data.tests.map((test) => {
    const result = test.result()
    return {
      name: test.name,
      fullName: test.fullName,
      state: result.state === 'pending' ? 'skipped' : result.state,
      errors: result.errors?.map(formatError),
    }
  })
}

function formatError(error: SerializedError): FormattedError {
  return {
    message: error.message,
    stack: error.stack,
    expected: error.expected,
    actual: error.actual,
  }
}
