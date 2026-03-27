import { describe, test, expect, beforeEach } from 'vitest'
import { testData } from '@testUtils'
import { generateDynamicContext } from './context'
import { ToolOperation } from '../../contracts/schemas/toolSchemas'
import { SYSTEM_PROMPT } from '../prompts/system-prompt'
import { RULES } from '../prompts/rules'
import { FILE_TYPES } from '../prompts/file-types'
import { RESPONSE } from '../prompts/response'
import { EDIT } from '../prompts/operations/edit'
import { MULTI_EDIT } from '../prompts/operations/multi-edit'
import { WRITE } from '../prompts/operations/write'
import { TODOS } from '../prompts/tools/todos'
import { TEST_OUTPUT } from '../prompts/tools/test-output'
import { Config } from '../../config/Config'

describe('generateDynamicContext', () => {
  test('uses default Config when config parameter is omitted', () => {
    const editOperation = testData.editOperation()
    const context = {
      modifications: JSON.stringify(editOperation),
    }

    // Call without config parameter
    const result = generateDynamicContext(context)

    // Should return valid result
    expect(result).toContain('## TDD Fundamentals')
  })

  describe('when Edit operation', () => {
    let editOperation: ReturnType<typeof testData.editOperation>
    let result: string

    beforeEach(() => {
      editOperation = testData.editOperation()
      result = generateContextResult(editOperation)
    })

    test('should include core prompts', () => {
      expectCorePrompts(result)
    })

    test('should include Edit operation context', () => {
      expect(EDIT.length).toBeGreaterThan(0)
      expect(result).toContain(EDIT)
    })

    test('should format file path section', () => {
      expect(result).toContain('### File Path')
      expect(result).toContain(editOperation.tool_input.file_path)
    })

    test('should format old content section', () => {
      expect(result).toContain('### Old Content')
      expect(result).toContain(editOperation.tool_input.old_string)
    })

    test('should format new content section', () => {
      expect(result).toContain('### New Content')
      expect(result).toContain(editOperation.tool_input.new_string)
    })
  })

  describe('when MultiEdit operation', () => {
    let multiEditOperation: ReturnType<typeof testData.multiEditOperation>
    let result: string

    beforeEach(() => {
      multiEditOperation = testData.multiEditOperation()
      result = generateContextResult(multiEditOperation)
    })

    test('should include core prompts', () => {
      expectCorePrompts(result)
    })

    test('should include MultiEdit operation context', () => {
      expect(MULTI_EDIT.length).toBeGreaterThan(0)
      expect(result).toContain(MULTI_EDIT)
    })

    test('should format file path section', () => {
      expect(result).toContain('### File Path')
      expect(result).toContain(multiEditOperation.tool_input.file_path)
    })

    test('should format edits section', () => {
      expect(result).toContain('### Edits')
      expect(result).toContain('#### Edit 1:')
    })

    test('should format first edit with old and new content', () => {
      expect(result).toContain('**Old Content:**')
      expect(result).toContain(
        multiEditOperation.tool_input.edits[0].old_string
      )
      expect(result).toContain('**New Content:**')
      expect(result).toContain(
        multiEditOperation.tool_input.edits[0].new_string
      )
    })
  })

  describe('when Write operation', () => {
    let writeOperation: ReturnType<typeof testData.writeOperation>
    let result: string

    beforeEach(() => {
      writeOperation = testData.writeOperation()
      result = generateContextResult(writeOperation)
    })

    test('should include core prompts', () => {
      expectCorePrompts(result)
    })

    test('should include Write operation context', () => {
      expect(WRITE.length).toBeGreaterThan(0)
      expect(result).toContain(WRITE)
    })

    test('should format file path section', () => {
      expect(result).toContain('### File Path')
      expect(result).toContain(writeOperation.tool_input.file_path)
    })

    test('should format new file content section', () => {
      expect(result).toContain('### New File Content')
      expect(result).toContain(writeOperation.tool_input.content)
    })
  })

  describe('when test output is provided', () => {
    let editOperation: ReturnType<typeof testData.editOperation>
    let testResults: ReturnType<typeof testData.failedTestResults>
    let result: string

    beforeEach(() => {
      editOperation = testData.editOperation()
      testResults = testData.failedTestResults()
      result = generateContextResult(editOperation, {
        test: JSON.stringify(testResults),
      })
    })

    test('should include test output context', () => {
      expect(TEST_OUTPUT.length).toBeGreaterThan(0)
      expect(result).toContain(TEST_OUTPUT)
    })

    test('should format test output using TestResultsProcessor', () => {
      expect(result).toContain(' ❯ /src/example.test.ts (1 tests | 1 failed)')
      expect(result).toContain('   × Calculator > should calculate sum')
      expect(result).toContain('     → expected 5 to be 6')
    })

    test('should include test summary', () => {
      expect(result).toContain(' Test Files  1 failed (1)')
      expect(result).toContain('      Tests  1 failed (1)')
    })

    test('should not contain raw JSON', () => {
      expect(result).not.toContain('"testModules"')
      expect(result).not.toContain('"moduleId"')
    })
  })

  describe('when todo is provided', () => {
    let editOperation: ReturnType<typeof testData.editOperation>
    let todoWriteOperation: ReturnType<typeof testData.todoWriteOperation>
    let result: string

    beforeEach(() => {
      editOperation = testData.editOperation()
      todoWriteOperation = testData.todoWriteOperation()
      result = generateContextResult(editOperation, {
        todo: JSON.stringify(todoWriteOperation),
      })
    })

    test('should include todo context', () => {
      expect(TODOS.length).toBeGreaterThan(0)
      expect(result).toContain(TODOS)
    })

    test('should format todo items', () => {
      expect(result).toContain('[pending] Implement feature (high)')
    })
  })

  describe('when todo is not provided', () => {
    let result: string

    beforeEach(() => {
      const editOperation = testData.editOperation()
      result = generateContextResult(editOperation)
    })

    test('should not include todo context', () => {
      expect(result).not.toContain(TODOS)
    })
  })

  describe('prompt ordering', () => {
    test('should assemble prompts in correct order', () => {
      const editOperation = testData.editOperation()
      const result = generateContextResult(editOperation)

      // Verify order by checking indexOf
      const systemIndex = result.indexOf('# TDD-Guard')
      const rulesIndex = result.indexOf('## TDD Fundamentals')
      const fileRulesIndex = result.indexOf('## File Type Specific Rules')
      const operationIndex = result.indexOf('## Analyzing Edit Operations')
      const changesIndex = result.indexOf('### File Path')
      const responseIndex = result.indexOf('## Your Response')

      expect(systemIndex).toBeLessThan(rulesIndex)
      expect(rulesIndex).toBeLessThan(fileRulesIndex)
      expect(fileRulesIndex).toBeLessThan(operationIndex)
      expect(operationIndex).toBeLessThan(changesIndex)
      expect(changesIndex).toBeLessThan(responseIndex)
    })
  })

  describe('system prompt', () => {
    test.each([
      ['cli', true],
      ['cursor-cli', true],
      ['sdk', false],
      ['api', false],
    ] as const)(
      '%s client includes system prompt: %s',
      (client, shouldInclude) => {
        const config = new Config({ validationClient: client })
        const editOperation = testData.editOperation()
        const context = {
          modifications: JSON.stringify(editOperation),
        }

        const result = generateDynamicContext(context, config)

        const containsSystemPrompt = result.includes(SYSTEM_PROMPT)
        expect(containsSystemPrompt).toBe(shouldInclude)
      }
    )
  })

  describe('custom instructions', () => {
    const customInstructions =
      '## Custom TDD Rules\n1. Always test first\n2. Keep it simple'

    describe('when custom instructions provided', () => {
      let result: string

      beforeEach(() => {
        result = generateContextResult(testData.editOperation(), {
          instructions: customInstructions,
        })
      })

      test('should use custom instructions', () => {
        expect(result).toContain(customInstructions)
      })

      test('should not include default rules', () => {
        expect(result).not.toContain(RULES)
      })
    })

    describe('when custom instructions not provided', () => {
      let result: string

      beforeEach(() => {
        result = generateContextResult(testData.editOperation())
      })

      test('should use default TDD rules', () => {
        expect(result).toContain(RULES)
      })
    })
  })

  describe('operation-specific context inclusion', () => {
    describe('for Edit operations', () => {
      let result: string

      beforeEach(() => {
        const editOperation = testData.editOperation()
        result = generateContextResult(editOperation)
      })

      test('should include Edit context', () => {
        expect(result).toContain(EDIT)
      })

      test('should not include MultiEdit context', () => {
        expect(result).not.toContain(MULTI_EDIT)
      })

      test('should not include Write context', () => {
        expect(result).not.toContain(WRITE)
      })
    })

    describe('for MultiEdit operations', () => {
      let result: string

      beforeEach(() => {
        const multiEditOperation = testData.multiEditOperation()
        result = generateContextResult(multiEditOperation)
      })

      test('should include MultiEdit context', () => {
        expect(result).toContain(MULTI_EDIT)
      })

      test('should not include Edit context', () => {
        expect(result).not.toContain(EDIT)
      })

      test('should not include Write context', () => {
        expect(result).not.toContain(WRITE)
      })
    })

    describe('for Write operations', () => {
      let result: string

      beforeEach(() => {
        const writeOperation = testData.writeOperation()
        result = generateContextResult(writeOperation)
      })

      test('should include Write context', () => {
        expect(result).toContain(WRITE)
      })

      test('should not include Edit context', () => {
        expect(result).not.toContain(EDIT)
      })

      test('should not include MultiEdit context', () => {
        expect(result).not.toContain(MULTI_EDIT)
      })
    })
  })
})

// Test helpers
function generateContextResult(
  operation: ToolOperation,
  additionalContext?: { test?: string; todo?: string; instructions?: string }
) {
  const context = {
    modifications: JSON.stringify(operation),
    ...additionalContext,
  }
  return generateDynamicContext(context)
}

function expectCorePrompts(result: string) {
  expect(result).toContain(RULES)
  expect(result).toContain(FILE_TYPES)
  expect(result).toContain(RESPONSE)
}
