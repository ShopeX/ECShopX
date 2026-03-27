/**
 * Factory functions for creating Edit and EditOperation test data
 */

import type {
  Edit,
  EditOperation,
} from '../../../src/contracts/schemas/toolSchemas'
import { hookDataDefaults, omit } from './helpers'
import { TEST_DEFAULTS } from './testDefaults'

/**
 * Creates a single edit object
 * @param params - Optional parameters for the edit
 */
export const edit = (params?: Partial<Edit>): Edit => {
  const defaults = TEST_DEFAULTS.edit
  const base = params ?? {}

  const result: Edit = {
    file_path: base.file_path ?? defaults.file_path,
    old_string: base.old_string ?? defaults.old_string,
    new_string: base.new_string ?? defaults.new_string,
  }

  if (base.replace_all !== undefined) {
    result.replace_all = base.replace_all
  }

  return result
}

/**
 * Creates an edit object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the edit
 */
export const editWithout = <K extends keyof Edit>(
  keys: K[],
  params?: Partial<Edit>
): Omit<Edit, K> => {
  const fullEdit = edit(params)
  return omit(fullEdit, keys)
}

/**
 * Creates a single edit operation object
 * @param params - Optional parameters for the edit operation
 */
export const editOperation = (
  params?: Partial<EditOperation>
): EditOperation => ({
  ...hookDataDefaults(),
  tool_name: 'Edit',
  tool_input: params?.tool_input ?? edit(),
})

/**
 * Creates an edit operation object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the edit operation
 */
export const editOperationWithout = <K extends keyof EditOperation>(
  keys: K[],
  params?: Partial<EditOperation>
): Omit<EditOperation, K> => {
  const fullEditOperation = editOperation(params)
  return omit(fullEditOperation, keys)
}

/**
 * Creates an invalid edit operation object for testing
 * @param params - Parameters including invalid values
 */
export const invalidEditOperation = (params: {
  tool_name?: string
  tool_input?: unknown
}): Record<string, unknown> => ({
  ...hookDataDefaults(),
  tool_name: params.tool_name ?? 'Edit',
  tool_input: params.tool_input ?? edit(),
})
