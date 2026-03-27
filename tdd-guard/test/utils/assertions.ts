/**
 * Test assertion helpers for linter tests
 */

import type { LintIssue } from '../../src/contracts/schemas/lintSchemas'

/**
 * Check if issues contain a specific rule
 */
export const hasRule = (issues: LintIssue[], rule: string): boolean => {
  return issues.some((issue) => issue.rule === rule)
}

/**
 * Check if issues contain all specified rules
 */
export const hasRules = (issues: LintIssue[], rules: string[]): boolean[] => {
  return rules.map((rule) => hasRule(issues, rule))
}

/**
 * Filter issues from a specific file
 */
export const issuesFromFile = (
  issues: LintIssue[],
  filename: string
): LintIssue[] => {
  return issues.filter((issue) => issue.file.includes(filename))
}
