/**
 * Factory functions for creating UserPromptSubmit test data
 */

import type { UserPromptSubmit } from '../../../src/contracts/schemas/toolSchemas'
import { omit } from './helpers'
import { TEST_DEFAULTS } from './testDefaults'

/**
 * Creates a UserPromptSubmit object
 * @param params - Optional parameters for the UserPromptSubmit
 */
export const userPromptSubmit = (
  params?: Partial<UserPromptSubmit>
): UserPromptSubmit => {
  const defaults = TEST_DEFAULTS.userPromptSubmit
  const base = params ?? {}

  return {
    session_id: base.session_id ?? defaults.session_id,
    transcript_path: base.transcript_path ?? defaults.transcript_path,
    hook_event_name: base.hook_event_name ?? defaults.hook_event_name,
    prompt: base.prompt ?? defaults.prompt,
    cwd: base.cwd ?? defaults.cwd,
  }
}

/**
 * Creates a UserPromptSubmit object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the UserPromptSubmit
 */
export const userPromptSubmitWithout = <K extends keyof UserPromptSubmit>(
  keys: K[],
  params?: Partial<UserPromptSubmit>
): Omit<UserPromptSubmit, K> => {
  const fullUserPromptSubmit = userPromptSubmit(params)
  return omit(fullUserPromptSubmit, keys)
}
