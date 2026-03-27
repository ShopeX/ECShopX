/**
 * Factory functions for creating Lint test data
 */

import type {
  LintIssue,
  LintData,
  LintResult,
  ESLintMessage,
  ESLintResult,
  GolangciLintPosition,
  GolangciLintIssue,
  GolangciLintResult,
} from '../../../src/contracts/schemas/lintSchemas'
import { omit } from './helpers'

/**
 * Creates a single lint issue object
 * @param params - Optional parameters for the lint issue
 */
export const lintIssue = (params?: Partial<LintIssue>): LintIssue => {
  const defaults: LintIssue = {
    file: '/src/example.ts',
    line: 15,
    column: 5,
    severity: 'error',
    message: 'Function has too high complexity',
    rule: 'complexity',
  }

  return {
    ...defaults,
    ...params,
  }
}

/**
 * Creates a lint issue object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the lint issue
 */
export const lintIssueWithout = <K extends keyof LintIssue>(
  keys: K[],
  params?: Partial<LintIssue>
): Omit<LintIssue, K> => {
  const fullLintIssue = lintIssue(params)
  return omit(fullLintIssue, keys)
}

/**
 * Creates a lint result object
 * @param params - Optional parameters for the lint result
 */
export const lintResult = (params?: Partial<LintResult>): LintResult => {
  const defaults: LintResult = {
    timestamp: '2024-01-01T00:00:00Z',
    files: ['/src/example.ts'],
    issues: [lintIssue()],
    errorCount: 1,
    warningCount: 0,
  }

  return {
    ...defaults,
    ...params,
  }
}

/**
 * Creates a lint result object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the lint result
 */
export const lintResultWithout = <K extends keyof LintResult>(
  keys: K[],
  params?: Partial<LintResult>
): Omit<LintResult, K> => {
  const fullLintResult = lintResult(params)
  return omit(fullLintResult, keys)
}

/**
 * Creates a lint data object
 * @param params - Optional parameters for the lint data
 */
export const lintData = (params?: Partial<LintData>): LintData => {
  const defaults: LintData = {
    timestamp: '2024-01-01T00:00:00Z',
    files: ['/src/example.ts'],
    issues: [lintIssue()],
    errorCount: 1,
    warningCount: 0,
    hasNotifiedAboutLintIssues: false,
  }

  return {
    ...defaults,
    ...params,
  }
}

/**
 * Creates a lint data object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the lint data
 */
export const lintDataWithout = <K extends keyof LintData>(
  keys: K[],
  params?: Partial<LintData>
): Omit<LintData, K> => {
  const fullLintData = lintData(params)
  return omit(fullLintData, keys)
}

/**
 * Creates an ESLint message object
 * @param params - Optional parameters for the ESLint message
 */
export const eslintMessage = (
  params?: Partial<ESLintMessage>
): ESLintMessage => {
  const defaults: ESLintMessage = {
    line: 10,
    column: 5,
    severity: 2,
    message: 'Unexpected var, use let or const instead',
    ruleId: 'no-var',
  }

  return {
    ...defaults,
    ...params,
  }
}

/**
 * Creates an ESLint message object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the ESLint message
 */
export const eslintMessageWithout = <K extends keyof ESLintMessage>(
  keys: K[],
  params?: Partial<ESLintMessage>
): Omit<ESLintMessage, K> => {
  const fullMessage = eslintMessage(params)
  return omit(fullMessage, keys)
}

/**
 * Creates an ESLint result object
 * @param params - Optional parameters for the ESLint result
 */
export const eslintResult = (params?: Partial<ESLintResult>): ESLintResult => {
  const defaults: ESLintResult = {
    filePath: '/src/example.ts',
    messages: [eslintMessage()],
  }

  return {
    ...defaults,
    ...params,
  }
}

/**
 * Creates an ESLint result object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the ESLint result
 */
export const eslintResultWithout = <K extends keyof ESLintResult>(
  keys: K[],
  params?: Partial<ESLintResult>
): Omit<ESLintResult, K> => {
  const fullResult = eslintResult(params)
  return omit(fullResult, keys)
}

/**
 * Creates a lint result object with no errors
 * @param params - Optional parameters to override defaults
 */
export const lintResultWithoutErrors = (
  params?: Partial<LintResult>
): LintResult => {
  return {
    timestamp: new Date().toISOString(),
    files: params?.files ?? [],
    issues: [],
    errorCount: 0,
    warningCount: 0,
    ...params,
  }
}

/**
 * Creates a lint result object with errors
 * @param params - Optional parameters to override defaults
 */
export const lintResultWithError = (
  params?: Partial<LintResult>
): LintResult => {
  const defaultIssue = lintIssue()
  return {
    timestamp: new Date().toISOString(),
    files: params?.files ?? ['/src/example.ts'],
    issues: params?.issues ?? [defaultIssue],
    errorCount: params?.errorCount ?? 1,
    warningCount: params?.warningCount ?? 0,
    ...params,
  }
}

/**
 * Creates a lint data object with notification flag set (for testing notification state)
 * @param params - Optional parameters to override defaults
 */
export const lintDataWithNotificationFlag = (
  params?: Partial<LintData>
): LintData => {
  return {
    timestamp: new Date().toISOString(),
    files: [],
    issues: [],
    errorCount: 0,
    warningCount: 0,
    hasNotifiedAboutLintIssues: true,
    ...params,
  }
}

/**
 * Creates a lint data object with errors
 * @param params - Optional parameters to override defaults
 */
export const lintDataWithError = (params?: Partial<LintData>): LintData => {
  const baseLintResult = lintResultWithError()
  return {
    ...baseLintResult,
    hasNotifiedAboutLintIssues: false,
    ...params,
  }
}

/**
 * Creates a lint data object without errors
 * @param params - Optional parameters to override defaults
 */
export const lintDataWithoutErrors = (params?: Partial<LintData>): LintData => {
  const baseLintResult = lintResultWithoutErrors()
  return {
    ...baseLintResult,
    hasNotifiedAboutLintIssues: false,
    ...params,
  }
}

/**
 * Creates a GolangciLint position object
 * @param params - Optional parameters for the position
 */
export const golangciLintPosition = (
  params?: Partial<GolangciLintPosition>
): GolangciLintPosition => {
  const defaults: GolangciLintPosition = {
    Filename: '/path/to/file.go',
    Line: 10,
    Column: 5,
  }

  return {
    ...defaults,
    ...params,
  }
}

/**
 * Creates a GolangciLint position object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the position
 */
export const golangciLintPositionWithout = <
  K extends keyof GolangciLintPosition,
>(
  keys: K[],
  params?: Partial<GolangciLintPosition>
): Omit<GolangciLintPosition, K> => {
  const fullPosition = golangciLintPosition(params)
  return omit(fullPosition, keys)
}

/**
 * Creates a GolangciLint issue object
 * @param params - Optional parameters for the issue
 */
export const golangciLintIssue = (
  params?: Partial<GolangciLintIssue>
): GolangciLintIssue => {
  const defaults: GolangciLintIssue = {
    FromLinter: 'typecheck',
    Text: 'undefined: variable',
    Severity: 'error',
    Pos: golangciLintPosition(),
  }

  return {
    ...defaults,
    ...params,
  }
}

/**
 * Creates a GolangciLint issue object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the issue
 */
export const golangciLintIssueWithout = <K extends keyof GolangciLintIssue>(
  keys: K[],
  params?: Partial<GolangciLintIssue>
): Omit<GolangciLintIssue, K> => {
  const fullIssue = golangciLintIssue(params)
  return omit(fullIssue, keys)
}

/**
 * Creates a GolangciLint result object
 * @param params - Optional parameters for the result
 */
export const golangciLintResult = (
  params?: Partial<GolangciLintResult>
): GolangciLintResult => {
  const defaults: GolangciLintResult = {
    Issues: [golangciLintIssue()],
  }

  return {
    ...defaults,
    ...params,
  }
}

/**
 * Creates a GolangciLint result object with specified properties omitted
 * @param keys - Array of property keys to omit
 * @param params - Optional parameters for the result
 */
export const golangciLintResultWithout = <K extends keyof GolangciLintResult>(
  keys: K[],
  params?: Partial<GolangciLintResult>
): Omit<GolangciLintResult, K> => {
  const fullResult = golangciLintResult(params)
  return omit(fullResult, keys)
}
