import { describe, it, expect, beforeEach } from 'vitest'
import { StorybookReporter } from './StorybookReporter'
import { MemoryStorage, FileStorage, Config, DEFAULT_DATA_DIR } from 'tdd-guard'
import {
  createStoryContext,
  passedStoryContext,
  failedStoryContext,
} from './StorybookReporter.test-data'
import { join } from 'node:path'
import type { StoryTest } from './types'

describe('StorybookReporter', () => {
  it('uses FileStorage by default', () => {
    const reporter = new StorybookReporter()
    expect(reporter['storage']).toBeInstanceOf(FileStorage)
  })

  it('accepts Storage instance in constructor', () => {
    const storage = new MemoryStorage()
    const reporter = new StorybookReporter(storage)
    expect(reporter['storage']).toBe(storage)
  })

  it('accepts root path string in constructor', () => {
    const rootPath = '/some/project/root'
    const reporter = new StorybookReporter(rootPath)
    expect(reporter['storage']).toBeInstanceOf(FileStorage)
    const fileStorage = reporter['storage'] as FileStorage
    const config = fileStorage['config'] as Config
    const expectedDataDir = join(rootPath, ...DEFAULT_DATA_DIR.split('/'))
    expect(config.dataDir).toBe(expectedDataDir)
  })

  describe('when collecting story results', () => {
    let storage: MemoryStorage
    let reporter: StorybookReporter

    beforeEach(() => {
      storage = new MemoryStorage()
      reporter = new StorybookReporter(storage)
    })

    it('saves output as valid JSON', async () => {
      const context = passedStoryContext()
      await reporter.onStoryResult(context)
      await reporter.onComplete()

      const saved = await storage.getTest()
      expect(saved).toBeTruthy()
      const parsed = JSON.parse(saved!)
      expect(parsed).toBeDefined()
    })

    it('includes test modules', async () => {
      const context = passedStoryContext()
      await reporter.onStoryResult(context)
      await reporter.onComplete()

      const saved = await storage.getTest()
      const parsed = JSON.parse(saved!)
      expect(parsed.testModules).toHaveLength(1)
    })

    it('includes test cases', async () => {
      const context1 = passedStoryContext()
      const context2 = passedStoryContext({
        storyExport: { name: 'Secondary' },
      })
      await reporter.onStoryResult(context1)
      await reporter.onStoryResult(context2)
      await reporter.onComplete()

      const saved = await storage.getTest()
      const parsed = JSON.parse(saved!)
      expect(parsed.testModules[0].tests).toHaveLength(2)
    })

    it('captures test states (passed/failed)', async () => {
      const passedContext = passedStoryContext()
      const failedContext = failedStoryContext()
      await reporter.onStoryResult(passedContext, 'passed')
      await reporter.onStoryResult(failedContext, 'failed', [
        {
          message: 'expected button to have aria-label',
          stack:
            'Error: expected button to have aria-label\n    at test.ts:7:19',
        },
      ])
      await reporter.onComplete()

      const saved = await storage.getTest()
      const parsed = JSON.parse(saved!)
      const tests = parsed.testModules[0].tests as StoryTest[]
      expect(tests.find((t) => t.state === 'passed')).toBeDefined()
      expect(tests.find((t) => t.state === 'failed')).toBeDefined()
    })

    it('includes error information for failed tests', async () => {
      const context = failedStoryContext()
      await reporter.onStoryResult(context, 'failed', [
        {
          message: 'expected button to have aria-label',
          stack:
            'Error: expected button to have aria-label\n    at test.ts:7:19',
        },
      ])
      await reporter.onComplete()

      const saved = await storage.getTest()
      const parsed = JSON.parse(saved!)
      const failedTest = parsed.testModules[0].tests[0]
      expect(failedTest.state).toBe('failed')
      expect(failedTest.errors).toBeDefined()
      expect(failedTest.errors.length).toBeGreaterThan(0)
    })
  })

  describe('test state mapping', () => {
    let storage: MemoryStorage
    let reporter: StorybookReporter

    beforeEach(() => {
      storage = new MemoryStorage()
      reporter = new StorybookReporter(storage)
    })

    it.each([
      ['passed', 'passed'],
      ['failed', 'failed'],
      ['skipped', 'skipped'],
    ] as const)('maps %s to %s', async (status, expected) => {
      const context = createStoryContext()
      await reporter.onStoryResult(context, status)
      await reporter.onComplete()

      const saved = await storage.getTest()
      const parsed = JSON.parse(saved!)
      expect(parsed.testModules[0].tests[0].state).toBe(expected)
    })
  })

  it('handles empty test runs', async () => {
    const storage = new MemoryStorage()
    const reporter = new StorybookReporter(storage)

    await reporter.onComplete()

    const saved = await storage.getTest()
    const parsed = JSON.parse(saved!)
    expect(parsed).toEqual({ testModules: [], unhandledErrors: [] })
  })

  describe('overall test run status', () => {
    let storage: MemoryStorage
    let reporter: StorybookReporter

    beforeEach(() => {
      storage = new MemoryStorage()
      reporter = new StorybookReporter(storage)
    })

    it('reports "passed" when all tests pass', async () => {
      const context = passedStoryContext()
      await reporter.onStoryResult(context)
      await reporter.onComplete()

      const saved = await storage.getTest()
      const parsed = JSON.parse(saved!)
      expect(parsed.reason).toBe('passed')
    })

    it('reports "failed" when any test fails', async () => {
      const passedContext = passedStoryContext()
      const failedContext = failedStoryContext()
      await reporter.onStoryResult(passedContext, 'passed')
      await reporter.onStoryResult(failedContext, 'failed', [
        {
          message: 'expected button to have aria-label',
          stack:
            'Error: expected button to have aria-label\n    at test.ts:7:19',
        },
      ])
      await reporter.onComplete()

      const saved = await storage.getTest()
      const parsed = JSON.parse(saved!)
      expect(parsed.reason).toBe('failed')
    })
  })
})
