/**
 * Factory functions for creating Todo, TodoWrite, and TodoWriteOperation test data
 */

import type {
  Todo,
  TodoWrite,
  TodoWriteOperation,
} from '../../../src/contracts/schemas/toolSchemas'
import { hookDataDefaults, omit } from './helpers'
import { TEST_DEFAULTS } from './testDefaults'

/**
 * Creates a single todo object
 * @param params - Optional parameters for the todo
 */
export const todo = (params?: Partial<Todo>): Todo => {
  const defaults = TEST_DEFAULTS.todo
  const base = params ?? {}

  return {
    content: base.content ?? defaults.content,
    status: base.status ?? defaults.status,
    priority: base.priority ?? defaults.priority,
    id: base.id ?? defaults.id,
  }
}

/**
 * Creates a todo object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the todo
 */
export const todoWithout = <K extends keyof Todo>(
  keys: K[],
  params?: Partial<Todo>
): Omit<Todo, K> => {
  const fullTodo = todo(params)
  return omit(fullTodo, keys)
}

/**
 * Creates a single todo write object
 * @param params - Optional parameters for the todo write
 */
export const todoWrite = (params?: { todos?: Todo[] }): TodoWrite => ({
  todos: params?.todos ?? [todo()],
})

/**
 * Creates a todo write object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the todo write
 */
export const todoWriteWithout = <K extends keyof TodoWrite>(
  keys: K[],
  params?: { todos?: Todo[] }
): Omit<TodoWrite, K> => {
  const fullTodoWrite = todoWrite(params)
  return omit(fullTodoWrite, keys)
}

/**
 * Creates a single todo write operation object
 * @param params - Optional parameters for the todo write operation
 */
export const todoWriteOperation = (
  params?: Partial<TodoWriteOperation>
): TodoWriteOperation => ({
  ...hookDataDefaults(),
  tool_name: 'TodoWrite',
  tool_input: params?.tool_input ?? todoWrite(),
})

/**
 * Creates a todo write operation object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the todo write operation
 */
export const todoWriteOperationWithout = <K extends keyof TodoWriteOperation>(
  keys: K[],
  params?: Partial<TodoWriteOperation>
): Omit<TodoWriteOperation, K> => {
  const fullTodoWriteOperation = todoWriteOperation(params)
  return omit(fullTodoWriteOperation, keys)
}

/**
 * Creates an invalid todo write operation object for testing
 * @param params - Parameters including invalid values
 */
export const invalidTodoWriteOperation = (params: {
  tool_name?: string
  tool_input?: unknown
}): Record<string, unknown> => ({
  ...hookDataDefaults(),
  tool_name: params.tool_name ?? 'TodoWrite',
  tool_input: params.tool_input ?? todoWrite(),
})
