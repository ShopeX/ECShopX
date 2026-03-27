/**
 * Helper functions for object manipulation in test factories
 */

import { TEST_DEFAULTS } from './testDefaults'

/**
 * Default hook data fields for all operations
 */
export const hookDataDefaults = (): {
  session_id: string
  transcript_path: string
  hook_event_name: string
} => ({
  session_id: TEST_DEFAULTS.hookData.session_id,
  transcript_path: TEST_DEFAULTS.hookData.transcript_path,
  hook_event_name: TEST_DEFAULTS.hookData.hook_event_name,
})

/**
 * Creates a new object with only the specified properties
 * @param obj - The source object
 * @param keys - Array of property keys to include
 * @returns A new object with only the specified properties
 */
export const pick = <T, K extends keyof T>(obj: T, keys: K[]): Pick<T, K> => {
  const result = {} as Pick<T, K>
  keys.forEach((key) => {
    result[key] = obj[key]
  })
  return result
}

/**
 * Creates a new object with specified properties omitted
 * @param obj - The source object
 * @param keys - Array of property keys to omit
 * @returns A new object without the specified properties
 */
export const omit = <T, K extends keyof T>(obj: T, keys: K[]): Omit<T, K> => {
  const result = { ...obj }
  keys.forEach((key) => {
    delete result[key]
  })
  return result as Omit<T, K>
}
