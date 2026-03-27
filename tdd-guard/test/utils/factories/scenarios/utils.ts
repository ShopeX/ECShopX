import { ValidationResult } from '../../../../src/contracts/types/ValidationResult'
import { expect } from 'vitest'

export function expectDecision(
  result: ValidationResult,
  expectedDecision: ValidationResult['decision']
): void {
  if (result.decision !== expectedDecision) {
    console.error(
      `\nTest failed - expected decision: ${expectedDecision}, but got: ${result.decision}`
    )
    console.error(`Reason: ${result.reason}\n`)
  }
  expect(result.decision).toBe(expectedDecision)
}
