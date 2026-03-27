import { Storage, FileStorage, Config } from 'tdd-guard'
import type {
  TestContext,
  TestRunOutput,
  StoryTest,
  StoryModule,
  StoryError,
} from './types'

export class StorybookReporter {
  private readonly storage: Storage
  private readonly collectedTests: Map<string, StoryTest[]> = new Map()

  constructor(storageOrRoot?: Storage | string) {
    this.storage = this.initializeStorage(storageOrRoot)
  }

  private initializeStorage(storageOrRoot?: Storage | string): Storage {
    if (!storageOrRoot) {
      return new FileStorage()
    }

    if (typeof storageOrRoot === 'string') {
      const config = new Config({ projectRoot: storageOrRoot })
      return new FileStorage(config)
    }

    return storageOrRoot
  }

  async onStoryResult(
    context: TestContext,
    status: 'passed' | 'failed' | 'skipped' = 'passed',
    errors?: unknown[]
  ): Promise<void> {
    const moduleId = context.id
    const test: StoryTest = {
      name: context.name,
      fullName: `${context.title} > ${context.name}`,
      state: status,
    }

    // Add errors if present
    if (errors && errors.length > 0) {
      test.errors = errors.map((err: unknown): StoryError => {
        const errorObj = err as Record<string, unknown>
        const message = errorObj.message
        return {
          message: typeof message === 'string' ? message : String(err),
          stack: errorObj.stack as string | undefined,
        }
      })
    }

    if (!this.collectedTests.has(moduleId)) {
      this.collectedTests.set(moduleId, [])
    }
    this.collectedTests.get(moduleId)!.push(test)
  }

  async onComplete(): Promise<void> {
    const testModules: StoryModule[] = Array.from(
      this.collectedTests.entries()
    ).map(([moduleId, tests]) => ({
      moduleId,
      tests,
    }))

    const output: TestRunOutput = {
      testModules,
      unhandledErrors: [],
      reason: this.determineReason(testModules),
    }

    await this.storage.saveTest(JSON.stringify(output, null, 2))
  }

  private determineReason(
    testModules: StoryModule[]
  ): 'passed' | 'failed' | undefined {
    const allTests = testModules.flatMap((m) => m.tests)
    if (allTests.length === 0) {
      return undefined
    }
    const hasFailures = allTests.some((t) => t.state === 'failed')
    return hasFailures ? 'failed' : 'passed'
  }
}
