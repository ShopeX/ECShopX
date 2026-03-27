/**
 * Factory functions for creating SessionStart test data
 */

import type { SessionStart } from '../../../src/contracts/schemas/toolSchemas'
import { omit } from './helpers'
import { TEST_DEFAULTS } from './testDefaults'

/**
 * Creates a SessionStart object
 * @param params - Optional parameters for the SessionStart
 */
export const sessionStart = (params?: Partial<SessionStart>): SessionStart => {
  const defaults = TEST_DEFAULTS.sessionStart
  const base = params ?? {}

  return {
    session_id: base.session_id ?? defaults.session_id,
    transcript_path: base.transcript_path ?? defaults.transcript_path,
    hook_event_name: base.hook_event_name ?? defaults.hook_event_name,
    source: base.source ?? defaults.source,
  }
}

/**
 * Creates a SessionStart object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the SessionStart
 */
export const sessionStartWithout = <K extends keyof SessionStart>(
  keys: K[],
  params?: Partial<SessionStart>
): Omit<SessionStart, K> => {
  const fullSessionStart = sessionStart(params)
  return omit(fullSessionStart, keys)
}
