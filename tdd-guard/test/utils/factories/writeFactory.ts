/**
 * Factory functions for creating Write and WriteOperation test data
 */

import type {
  Write,
  WriteOperation,
} from '../../../src/contracts/schemas/toolSchemas'
import { hookDataDefaults, omit } from './helpers'
import { TEST_DEFAULTS } from './testDefaults'

/**
 * Creates a single write object
 * @param params - Optional parameters for the write
 */
export const write = (params?: Partial<Write>): Write => ({
  file_path: params?.file_path ?? TEST_DEFAULTS.write.file_path,
  content: params?.content ?? TEST_DEFAULTS.write.content,
})

/**
 * Creates a write object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the write
 */
export const writeWithout = <K extends keyof Write>(
  keys: K[],
  params?: Partial<Write>
): Omit<Write, K> => {
  const fullWrite = write(params)
  return omit(fullWrite, keys)
}

/**
 * Creates a single write operation object
 * @param params - Optional parameters for the write operation
 */
export const writeOperation = (
  params?: Partial<WriteOperation>
): WriteOperation => ({
  ...hookDataDefaults(),
  tool_name: 'Write',
  tool_input: params?.tool_input ?? write(),
})

/**
 * Creates a write operation object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the write operation
 */
export const writeOperationWithout = <K extends keyof WriteOperation>(
  keys: K[],
  params?: Partial<WriteOperation>
): Omit<WriteOperation, K> => {
  const fullWriteOperation = writeOperation(params)
  return omit(fullWriteOperation, keys)
}

/**
 * Creates an invalid write operation object for testing
 * @param params - Parameters including invalid values
 */
export const invalidWriteOperation = (params: {
  tool_name?: string
  tool_input?: unknown
}): Record<string, unknown> => ({
  ...hookDataDefaults(),
  tool_name: params.tool_name ?? 'Write',
  tool_input: params.tool_input ?? write(),
})
