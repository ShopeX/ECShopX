import { describe, test, expect } from 'vitest'
import {
  TodoSchema,
  EditSchema,
  MultiEditSchema,
  WriteSchema,
  TodoWriteSchema,
  EditOperationSchema,
  MultiEditOperationSchema,
  WriteOperationSchema,
  TodoWriteOperationSchema,
  ToolOperationSchema,
  FileModificationSchema,
  isEditOperation,
  isMultiEditOperation,
  isWriteOperation,
  isTodoWriteOperation,
  isFileModification,
  UserPromptSubmitSchema,
  SessionStartSchema,
} from './toolSchemas'
import { testData } from '@testUtils'

describe('Tool-specific schemas', () => {
  describe('SessionStartSchema', () => {
    test.each([
      {
        description: 'with startup source',
        data: testData.sessionStart({ source: 'startup' }),
        expectedSuccess: true,
      },
      {
        description: 'with resume source',
        data: testData.sessionStart({ source: 'resume' }),
        expectedSuccess: true,
      },
      {
        description: 'with clear source',
        data: testData.sessionStart({ source: 'clear' }),
        expectedSuccess: true,
      },
      {
        description: 'with invalid source',
        data: { ...testData.sessionStart(), source: 'invalid' },
        expectedSuccess: false,
      },
      {
        description: 'with wrong hook_event_name',
        data: { ...testData.sessionStart(), hook_event_name: 'PreToolUse' },
        expectedSuccess: false,
      },
      {
        description: 'without source',
        data: testData.sessionStartWithout(['source']),
        expectedSuccess: false,
      },
    ])('$description', ({ data, expectedSuccess }) => {
      const result = SessionStartSchema.safeParse(data)
      expect(result.success).toBe(expectedSuccess)
    })
  })
  describe('UserPromptSubmitSchema', () => {
    test.each([
      {
        description: 'with valid data',
        data: testData.userPromptSubmit(),
        expectedSuccess: true,
      },
      {
        description: 'with different prompt',
        data: testData.userPromptSubmit({ prompt: 'tdd-guard off' }),
        expectedSuccess: true,
      },
      {
        description: 'with wrong hook_event_name',
        data: testData.userPromptSubmit({ hook_event_name: 'PreToolUse' }),
        expectedSuccess: false,
      },
      {
        description: 'without prompt',
        data: testData.userPromptSubmitWithout(['prompt']),
        expectedSuccess: false,
      },
      {
        description: 'without cwd',
        data: testData.userPromptSubmitWithout(['cwd']),
        expectedSuccess: false,
      },
      {
        description: 'without session_id',
        data: testData.userPromptSubmitWithout(['session_id']),
        expectedSuccess: false,
      },
      {
        description: 'without transcript_path',
        data: testData.userPromptSubmitWithout(['transcript_path']),
        expectedSuccess: false,
      },
    ])('$description', ({ data, expectedSuccess }) => {
      const result = UserPromptSubmitSchema.safeParse(data)
      expect(result.success).toBe(expectedSuccess)
    })
  })

  describe('TodoSchema', () => {
    test.each([
      {
        description: 'without content',
        todo: testData.todoWithout(['content']),
        expectedSuccess: false,
      },
      {
        description: 'without status',
        todo: testData.todoWithout(['status']),
        expectedSuccess: false,
      },
      {
        description: 'without priority',
        todo: testData.todoWithout(['priority']),
        expectedSuccess: false,
      },
      {
        description: 'without id',
        todo: testData.todoWithout(['id']),
        expectedSuccess: false,
      },
      {
        description: 'with everything',
        todo: testData.todo(),
        expectedSuccess: true,
      },
    ])('$description', ({ todo, expectedSuccess }) => {
      const result = TodoSchema.safeParse(todo)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(todo)
      }
    })
  })

  describe('EditSchema', () => {
    test.each([
      {
        description: 'without file_path',
        edit: testData.editWithout(['file_path']),
        expectedSuccess: false,
      },
      {
        description: 'without old_string',
        edit: testData.editWithout(['old_string']),
        expectedSuccess: false,
      },
      {
        description: 'without new_string',
        edit: testData.editWithout(['new_string']),
        expectedSuccess: false,
      },
      {
        description: 'with everything',
        edit: testData.edit(),
        expectedSuccess: true,
      },
      {
        description: 'with replace_all true',
        edit: testData.edit({ replace_all: true }),
        expectedSuccess: true,
      },
    ])('$description', ({ edit, expectedSuccess }) => {
      const result = EditSchema.safeParse(edit)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(edit)
      }
    })
  })

  describe('MultiEditSchema', () => {
    test.each([
      {
        description: 'with valid multi-edit',
        multiEdit: testData.multiEdit(),
        expectedSuccess: true,
      },
      {
        description: 'without file_path',
        multiEdit: testData.multiEditWithout(['file_path']),
        expectedSuccess: false,
      },
      {
        description: 'without edits',
        multiEdit: testData.multiEditWithout(['edits']),
        expectedSuccess: false,
      },
      {
        description: 'with empty edits array',
        multiEdit: testData.multiEdit({ edits: [] }),
        expectedSuccess: false,
      },
    ])('$description', ({ multiEdit, expectedSuccess }) => {
      const result = MultiEditSchema.safeParse(multiEdit)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(multiEdit)
      }
    })
  })

  describe('WriteSchema', () => {
    test.each([
      {
        description: 'with valid write',
        write: testData.write(),
        expectedSuccess: true,
      },
      {
        description: 'without file_path',
        write: testData.writeWithout(['file_path']),
        expectedSuccess: false,
      },
      {
        description: 'without content',
        write: testData.writeWithout(['content']),
        expectedSuccess: false,
      },
    ])('$description', ({ write, expectedSuccess }) => {
      const result = WriteSchema.safeParse(write)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(write)
      }
    })
  })

  describe('TodoWriteSchema', () => {
    test.each([
      {
        description: 'with valid todo write',
        todoWrite: testData.todoWrite(),
        expectedSuccess: true,
      },
      {
        description: 'with empty todos array',
        todoWrite: testData.todoWrite({ todos: [] }),
        expectedSuccess: false,
      },
      {
        description: 'without todos',
        todoWrite: testData.todoWriteWithout(['todos']),
        expectedSuccess: false,
      },
    ])('$description', ({ todoWrite, expectedSuccess }) => {
      const result = TodoWriteSchema.safeParse(todoWrite)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(todoWrite)
      }
    })
  })

  describe('EditOperationSchema', () => {
    test.each([
      {
        description: 'with valid edit operation',
        editOperation: testData.editOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with wrong tool_name',
        editOperation: testData.invalidEditOperation({ tool_name: 'Write' }),
        expectedSuccess: false,
      },
      {
        description: 'without tool_input',
        editOperation: testData.editOperationWithout(['tool_input']),
        expectedSuccess: false,
      },
    ])('$description', ({ editOperation, expectedSuccess }) => {
      const result = EditOperationSchema.safeParse(editOperation)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(editOperation)
      }
    })
  })

  describe('MultiEditOperationSchema', () => {
    test.each([
      {
        description: 'with valid multi-edit operation',
        multiEditOperation: testData.multiEditOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with wrong tool_name',
        multiEditOperation: testData.invalidMultiEditOperation({
          tool_name: 'Edit',
        }),
        expectedSuccess: false,
      },
      {
        description: 'without tool_input',
        multiEditOperation: testData.multiEditOperationWithout(['tool_input']),
        expectedSuccess: false,
      },
    ])('$description', ({ multiEditOperation, expectedSuccess }) => {
      const result = MultiEditOperationSchema.safeParse(multiEditOperation)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(multiEditOperation)
      }
    })
  })

  describe('WriteOperationSchema', () => {
    test.each([
      {
        description: 'with valid write operation',
        writeOperation: testData.writeOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with wrong tool_name',
        writeOperation: testData.invalidWriteOperation({ tool_name: 'Edit' }),
        expectedSuccess: false,
      },
      {
        description: 'without tool_input',
        writeOperation: testData.writeOperationWithout(['tool_input']),
        expectedSuccess: false,
      },
    ])('$description', ({ writeOperation, expectedSuccess }) => {
      const result = WriteOperationSchema.safeParse(writeOperation)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(writeOperation)
      }
    })
  })

  describe('TodoWriteOperationSchema', () => {
    test.each([
      {
        description: 'with valid todo write operation',
        todoWriteOperation: testData.todoWriteOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with wrong tool_name',
        todoWriteOperation: testData.invalidTodoWriteOperation({
          tool_name: 'Edit',
        }),
        expectedSuccess: false,
      },
      {
        description: 'without tool_input',
        todoWriteOperation: testData.todoWriteOperationWithout(['tool_input']),
        expectedSuccess: false,
      },
    ])('$description', ({ todoWriteOperation, expectedSuccess }) => {
      const result = TodoWriteOperationSchema.safeParse(todoWriteOperation)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(todoWriteOperation)
      }
    })
  })

  describe('ToolOperationSchema', () => {
    test.each([
      {
        description: 'with valid Edit operation',
        toolOperation: testData.editOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with valid MultiEdit operation',
        toolOperation: testData.multiEditOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with valid Write operation',
        toolOperation: testData.writeOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with valid TodoWrite operation',
        toolOperation: testData.todoWriteOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with unknown tool_name',
        toolOperation: { tool_name: 'Unknown', tool_input: {} },
        expectedSuccess: false,
      },
    ])('$description', ({ toolOperation, expectedSuccess }) => {
      const result = ToolOperationSchema.safeParse(toolOperation)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(toolOperation)
      }
    })
  })

  describe('FileModificationSchema', () => {
    test.each([
      {
        description: 'with valid Edit operation',
        fileModification: testData.editOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with valid MultiEdit operation',
        fileModification: testData.multiEditOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with valid Write operation',
        fileModification: testData.writeOperation(),
        expectedSuccess: true,
      },
      {
        description: 'with TodoWrite operation (should reject)',
        fileModification: testData.todoWriteOperation(),
        expectedSuccess: false,
      },
    ])('$description', ({ fileModification, expectedSuccess }) => {
      const result = FileModificationSchema.safeParse(fileModification)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(fileModification)
      }
    })
  })
})

describe('Type guards', () => {
  // Create test data for all operation types
  const editOperation = testData.editOperation()
  const multiEditOperation = testData.multiEditOperation()
  const writeOperation = testData.writeOperation()
  const todoWriteOperation = testData.todoWriteOperation()

  describe('isEditOperation', () => {
    test.each([
      { operation: editOperation, operationType: 'Edit', expected: true },
      {
        operation: multiEditOperation,
        operationType: 'MultiEdit',
        expected: false,
      },
      { operation: writeOperation, operationType: 'Write', expected: false },
      {
        operation: todoWriteOperation,
        operationType: 'TodoWrite',
        expected: false,
      },
    ])(
      'returns $expected for $operationType operation',
      ({ operation, expected }) => {
        expect(isEditOperation(operation)).toBe(expected)
      }
    )
  })

  describe('isMultiEditOperation', () => {
    test.each([
      { operation: editOperation, operationType: 'Edit', expected: false },
      {
        operation: multiEditOperation,
        operationType: 'MultiEdit',
        expected: true,
      },
      { operation: writeOperation, operationType: 'Write', expected: false },
      {
        operation: todoWriteOperation,
        operationType: 'TodoWrite',
        expected: false,
      },
    ])(
      'returns $expected for $operationType operation',
      ({ operation, expected }) => {
        expect(isMultiEditOperation(operation)).toBe(expected)
      }
    )
  })

  describe('isWriteOperation', () => {
    test.each([
      { operation: editOperation, operationType: 'Edit', expected: false },
      {
        operation: multiEditOperation,
        operationType: 'MultiEdit',
        expected: false,
      },
      { operation: writeOperation, operationType: 'Write', expected: true },
      {
        operation: todoWriteOperation,
        operationType: 'TodoWrite',
        expected: false,
      },
    ])(
      'returns $expected for $operationType operation',
      ({ operation, expected }) => {
        expect(isWriteOperation(operation)).toBe(expected)
      }
    )
  })

  describe('isTodoWriteOperation', () => {
    test.each([
      { operation: editOperation, operationType: 'Edit', expected: false },
      {
        operation: multiEditOperation,
        operationType: 'MultiEdit',
        expected: false,
      },
      { operation: writeOperation, operationType: 'Write', expected: false },
      {
        operation: todoWriteOperation,
        operationType: 'TodoWrite',
        expected: true,
      },
    ])(
      'returns $expected for $operationType operation',
      ({ operation, expected }) => {
        expect(isTodoWriteOperation(operation)).toBe(expected)
      }
    )
  })

  describe('isFileModification', () => {
    test.each([
      { operation: editOperation, operationType: 'Edit', expected: true },
      {
        operation: multiEditOperation,
        operationType: 'MultiEdit',
        expected: true,
      },
      { operation: writeOperation, operationType: 'Write', expected: true },
      {
        operation: todoWriteOperation,
        operationType: 'TodoWrite',
        expected: false,
      },
    ])(
      'returns $expected for $operationType operation',
      ({ operation, expected }) => {
        expect(isFileModification(operation)).toBe(expected)
      }
    )
  })
})
