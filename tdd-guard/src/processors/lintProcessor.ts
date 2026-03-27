import { ProcessedLintData } from '../contracts/types/Context'
import { LintData } from '../contracts/schemas/lintSchemas'

/**
 * Processes lint data into a presentable format for validation context
 */
export function processLintData(lintData?: LintData): ProcessedLintData {
  if (!lintData) {
    return {
      hasIssues: false,
      summary: 'No lint data available',
      issuesByFile: new Map(),
      totalIssues: 0,
      errorCount: 0,
      warningCount: 0,
    }
  }

  const hasIssues = lintData.errorCount > 0 || lintData.warningCount > 0
  const totalIssues = lintData.errorCount + lintData.warningCount

  if (!hasIssues) {
    return {
      hasIssues: false,
      summary: 'No lint issues found',
      issuesByFile: new Map(),
      totalIssues: 0,
      errorCount: 0,
      warningCount: 0,
    }
  }

  // Group issues by file
  const issuesByFile = new Map<string, string[]>()
  for (const issue of lintData.issues) {
    if (!issuesByFile.has(issue.file)) {
      issuesByFile.set(issue.file, [])
    }

    const ruleInfo = issue.rule ? ` (${issue.rule})` : ''
    const formattedIssue = `  Line ${issue.line}:${issue.column} - ${issue.severity}: ${issue.message}${ruleInfo}`
    issuesByFile.get(issue.file)!.push(formattedIssue)
  }

  const summary = `${totalIssues} lint ${totalIssues === 1 ? 'issue' : 'issues'} found (${lintData.errorCount} ${lintData.errorCount === 1 ? 'error' : 'errors'}, ${lintData.warningCount} ${lintData.warningCount === 1 ? 'warning' : 'warnings'})`

  return {
    hasIssues: true,
    summary,
    issuesByFile,
    totalIssues,
    errorCount: lintData.errorCount,
    warningCount: lintData.warningCount,
  }
}

/**
 * Formats processed lint data as a readable string for AI context
 */
export function formatLintDataForContext(
  processedLint: ProcessedLintData
): string {
  if (!processedLint.hasIssues) {
    return processedLint.summary
  }

  let formatted = `Code Quality Status: ${processedLint.summary}\n`

  if (processedLint.issuesByFile.size > 0) {
    formatted += '\nLint Issues by File:\n'
    for (const [file, issues] of processedLint.issuesByFile) {
      formatted += `\n${file}:\n${issues.join('\n')}\n`
    }
  }

  return formatted.trim()
}
