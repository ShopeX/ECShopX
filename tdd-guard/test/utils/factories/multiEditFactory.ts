/**
 * Factory functions for creating MultiEdit and MultiEditOperation test data
 */

import type {
  MultiEdit,
  MultiEditOperation,
} from '../../../src/contracts/schemas/toolSchemas'
import { hookDataDefaults, omit } from './helpers'
import { TEST_DEFAULTS } from './testDefaults'

/**
 * Creates a single multi-edit object
 * @param params - Optional parameters for the multi-edit
 */
export const multiEdit = (params?: Partial<MultiEdit>): MultiEdit => ({
  file_path: params?.file_path ?? TEST_DEFAULTS.multiEdit.file_path,
  edits: params?.edits ?? [
    {
      old_string: TEST_DEFAULTS.multiEdit.edits[0].old_string,
      new_string: TEST_DEFAULTS.multiEdit.edits[0].new_string,
    },
    {
      old_string: TEST_DEFAULTS.multiEdit.edits[1].old_string,
      new_string: TEST_DEFAULTS.multiEdit.edits[1].new_string,
    },
  ],
})

/**
 * Creates a multi-edit object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the multi-edit
 */
export const multiEditWithout = <K extends keyof MultiEdit>(
  keys: K[],
  params?: Partial<MultiEdit>
): Omit<MultiEdit, K> => {
  const fullMultiEdit = multiEdit(params)
  return omit(fullMultiEdit, keys)
}

/**
 * Creates a single multi-edit operation object
 * @param params - Optional parameters for the multi-edit operation
 */
export const multiEditOperation = (
  params?: Partial<MultiEditOperation>
): MultiEditOperation => ({
  ...hookDataDefaults(),
  tool_name: 'MultiEdit',
  tool_input: params?.tool_input ?? multiEdit(),
})

/**
 * Creates a multi-edit operation object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the multi-edit operation
 */
export const multiEditOperationWithout = <K extends keyof MultiEditOperation>(
  keys: K[],
  params?: Partial<MultiEditOperation>
): Omit<MultiEditOperation, K> => {
  const fullMultiEditOperation = multiEditOperation(params)
  return omit(fullMultiEditOperation, keys)
}

/**
 * Creates an invalid multi-edit operation object for testing
 * @param params - Parameters including invalid values
 */
export const invalidMultiEditOperation = (params: {
  tool_name?: string
  tool_input?: unknown
}): Record<string, unknown> => ({
  ...hookDataDefaults(),
  tool_name: params.tool_name ?? 'MultiEdit',
  tool_input: params.tool_input ?? multiEdit(),
})
