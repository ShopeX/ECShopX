import { describe, it, expect, vi } from 'vitest'
import { handlePostToolLint, PostToolLintHandler, DEFAULT_RESULT } from './postToolLint'
import { testData } from '@testUtils'
import { Linter } from '../linters/Linter'
import { MemoryStorage } from '../storage/MemoryStorage'
import { LintData, LintResult } from '../contracts/schemas/lintSchemas'
import { HookData } from '../contracts/schemas/toolSchemas'
import { TestResult } from '../contracts/schemas/reporterSchemas'
import { ValidationResult } from '../contracts/types/ValidationResult'

describe('postToolLint', () => {
  describe('PostToolLintHandler', () => {
    it('uses null linter by default', () => {
      const storage = new MemoryStorage()
      const handler = new PostToolLintHandler(storage)
      expect(handler['linter']).toBeNull()
    })

    it('accepts Linter instance in constructor', () => {
      const storage = new MemoryStorage()
      const testLinter = new TestLinter()
      const handler = new PostToolLintHandler(storage, testLinter)
      expect(handler['linter']).toBe(testLinter)
    })

    it('accepts null linter in constructor', () => {
      const storage = new MemoryStorage()
      const handler = new PostToolLintHandler(storage, null)
      expect(handler['linter']).toBeNull()
    })
  })

  it('should return default result for PreToolUse hooks', async () => {
    const { result, parsedLint } = await runLintAndGetResult({
      lintResult: testData.lintResultWithoutErrors(),
      operation: { hook_event_name: 'PreToolUse' }
    })

    expect(result).toEqual(DEFAULT_RESULT)
    expect(parsedLint).toBeNull()
  })


  describe('when processing tool operations', () => {
    it('runs lint and saves results for Edit operations', async () => {
      const { parsedLint } = await runLintAndGetResult()

      expect(parsedLint).not.toBeNull()
      expect(parsedLint!.files).toEqual([])
      expect(parsedLint!.errorCount).toBe(0)
      expect(parsedLint!.warningCount).toBe(0)
    })

    it('runs lint and saves results for Write operations', async () => {
      const { parsedLint } = await runLintAndGetResult({
        operation: { tool_name: 'Write' }
      })

      expect(parsedLint).not.toBeNull()
      expect(parsedLint!.hasNotifiedAboutLintIssues).toBe(false)
    })

    it('runs lint and saves results for MultiEdit operations', async () => {
      const { parsedLint } = await runLintAndGetResult({
        operation: { tool_name: 'MultiEdit' }
      })

      expect(parsedLint).not.toBeNull()
      expect(parsedLint!.hasNotifiedAboutLintIssues).toBe(false)
    })

    it('returns default result and does not save lint data when operation has no file paths', async () => {
      const { result, parsedLint } = await runLintAndGetResult({
        operation: {
          tool_name: 'TodoWrite',
          tool_input: { todos: [] }
        }
      })

      expect(result).toEqual(DEFAULT_RESULT)
      expect(parsedLint).toBeNull()
    })

    it('processes MultiEdit operations even when some edits lack file_path', async () => {
      const { parsedLint } = await runLintAndGetResult({
        operation: {
          tool_name: 'MultiEdit',
          tool_input: {
            edits: [
              { old_string: 'a', new_string: 'b' },  // missing file_path
              { file_path: '/test/file.ts', old_string: 'c', new_string: 'd' }
            ]
          }
        }
      })

      // Should still process the edit that has a file_path
      expect(parsedLint).not.toBeNull()
      expect(parsedLint!.hasNotifiedAboutLintIssues).toBe(false)
    })
  })

  it('should always save lint data to storage', async () => {
    const lintData = testData.lintResultWithError()

    const { parsedLint } = await runLintAndGetResult({ lintResult: lintData })

    expect(parsedLint!.issues).toEqual(lintData.issues)
    expect(parsedLint!.errorCount).toBe(1)
  })

  describe('when lint issues exist', () => {
    describe('and notification flag is false', () => {
      it('does not block the operation', async () => {
        const { result } = await runLintAndGetResult({
          lintResult: testData.lintResultWithError()
        })

        expect(result).toEqual(DEFAULT_RESULT)
      })

      it('saves hasNotifiedAboutLintIssues as false', async () => {
        const { parsedLint } = await runLintAndGetResult({
          lintResult: testData.lintResultWithError()
        })

        expect(parsedLint!.hasNotifiedAboutLintIssues).toBe(false)
      })
    })

    describe('and notification flag is true', () => {
      it('blocks with detailed lint errors', async () => {
        const { result } = await runLintAndGetResult({
          lintResult: testData.lintResultWithError(),
          initialLintData: testData.lintDataWithNotificationFlag()
        })

        expect(result.decision).toBe('block')
        expect(result.reason).toContain('Lint issues detected:')
      })

      it('preserves the notification flag', async () => {
        const { parsedLint } = await runLintAndGetResult({
          lintResult: testData.lintResultWithError(),
          initialLintData: testData.lintDataWithNotificationFlag()
        })

        expect(parsedLint!.hasNotifiedAboutLintIssues).toBe(true)
      })
    })

    describe('and tests are failing (red phase)', () => {
      it('saves lint data without blocking', async () => {
        const lintData = testData.lintResultWithError()
        
        const { parsedLint } = await runLintAndGetResult({
          lintResult: lintData,
          initialTestData: testData.failedTestResults()
        })

        expect(parsedLint!.issues).toEqual(lintData.issues)
        expect(parsedLint!.errorCount).toBe(1)
        expect(parsedLint!.hasNotifiedAboutLintIssues).toBe(false)
      })
    })
  })

  describe('when storage errors occur', () => {
    it('should treat storage read errors as no stored lint data', async () => {
      // This test requires manual setup to spy on storage
      const storage = new MemoryStorage()
      const testLinter = new TestLinter(testData.lintResultWithoutErrors())
      
      // Make storage.getLint() throw an error only on the first call
      const getLintSpy = vi.spyOn(storage, 'getLint')
      getLintSpy.mockRejectedValueOnce(new Error('Storage error'))

      const hookData = JSON.stringify({
        ...testData.editOperation(),
        hook_event_name: 'PostToolUse'
      })
      
      const result = await handlePostToolLint(hookData, storage, testLinter)

      // Should not block (treats error as no stored data)
      expect(result).toEqual(DEFAULT_RESULT)

      // Reset the spy and check that storage now contains the new lint data
      getLintSpy.mockRestore()
      const savedLint = await storage.getLint()
      expect(savedLint).toBeTruthy()
      const parsedLint = JSON.parse(savedLint!)
      expect(parsedLint.hasNotifiedAboutLintIssues).toBe(false)
      expect(parsedLint.issues).toEqual([])
    })
  })

  describe('when linter is null', () => {
    it('skips linting and returns default result', async () => {
      const storage = new MemoryStorage()
      const handler = new PostToolLintHandler(storage, null)
      const hookData = {
        ...testData.editOperation(),
        hook_event_name: 'PostToolUse'
      }

      const result = await handler.handle(JSON.stringify(hookData))

      expect(result).toEqual(DEFAULT_RESULT)
      const savedLint = await storage.getLint()
      expect(savedLint).toBeNull()
    })
  })

  describe('when no lint issues exist', () => {
    describe('and previous state had notification flag set', () => {
      it('resets hasNotifiedAboutLintIssues to false', async () => {
        const { parsedLint } = await runLintAndGetResult({
          lintResult: testData.lintResultWithoutErrors(),
          initialLintData: testData.lintDataWithNotificationFlag()
        })

        expect(parsedLint!.hasNotifiedAboutLintIssues).toBe(false)
      })

      describe('and tests are passing', () => {
        it('does not block', async () => {
          const { result } = await runLintAndGetResult({
            lintResult: testData.lintResultWithoutErrors(),
            initialLintData: testData.lintDataWithNotificationFlag(),
            initialTestData: testData.passingTestResults()
          })

          expect(result).toEqual(DEFAULT_RESULT)
        })
      })
    })

    describe('and previous state had issues and notification flag', () => {
      it('resets hasNotifiedAboutLintIssues to false when issues are resolved', async () => {
        const { parsedLint } = await runLintAndGetResult({
          lintResult: testData.lintResultWithoutErrors(),
          initialLintData: testData.lintData({
            files: ['/src/example.ts'],
            issues: [testData.lintIssue({ file: '/src/example.ts', line: 1, column: 1, message: "Error" })],
            hasNotifiedAboutLintIssues: true
          })
        })

        expect(parsedLint!.hasNotifiedAboutLintIssues).toBe(false)
      })
    })

    it('saves clean lint data', async () => {
      const { parsedLint } = await runLintAndGetResult({
        lintResult: testData.lintResultWithoutErrors()
      })

      expect(parsedLint!.issues).toEqual([])
      expect(parsedLint!.errorCount).toBe(0)
      expect(parsedLint!.warningCount).toBe(0)
    })
  })
})

// Test helper functions
interface LintTestOptions {
  lintResult?: Omit<LintData, 'hasNotifiedAboutLintIssues'>
  operation?: Partial<HookData>
  storage?: MemoryStorage
  initialLintData?: LintData
  initialTestData?: TestResult
}

async function runLintAndGetResult(options: LintTestOptions = {}): Promise<{
  result: ValidationResult,
  parsedLint: LintData | null,
  storage: MemoryStorage
}> {
  const { lintResult, operation = {}, storage: existingStorage, initialLintData, initialTestData } = options
  const storage = existingStorage ?? new MemoryStorage()
  
  // Set up initial storage state if provided
  if (initialLintData) {
    await storage.saveLint(JSON.stringify(initialLintData))
  }
  if (initialTestData) {
    await storage.saveTest(JSON.stringify(initialTestData))
  }
  
  const testLinter = new TestLinter(lintResult ?? testData.lintResultWithoutErrors())
  
  let baseOperation
  if (operation.tool_name === 'Write') {
    baseOperation = testData.writeOperation()
  } else if (operation.tool_name === 'MultiEdit') {
    baseOperation = testData.multiEditOperation()
  } else {
    baseOperation = testData.editOperation()
  }
  
  const hookData = JSON.stringify({
    ...baseOperation,
    hook_event_name: 'PostToolUse',
    ...operation
  })
  
  const result = await handlePostToolLint(hookData, storage, testLinter)
  
  const savedLint = await storage.getLint()
  const parsedLint = savedLint ? JSON.parse(savedLint) : null
  
  return { result, parsedLint, storage }
}

// Test Linter implementation with configurable behavior
class TestLinter implements Linter {
  private readonly lintResult: LintResult
  
  constructor(lintResult?: LintResult) {
    this.lintResult = lintResult ?? testData.lintResultWithoutErrors()
  }
  
  async lint(): Promise<LintResult> {
    return this.lintResult
  }
}