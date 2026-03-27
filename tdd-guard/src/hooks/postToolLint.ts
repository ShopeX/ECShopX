import { ValidationResult } from '../contracts/types/ValidationResult'
import { HookData, HookDataSchema } from '../contracts/schemas/toolSchemas'
import { LintData, LintDataSchema, LintResult } from '../contracts/schemas/lintSchemas'
import { Storage } from '../storage/Storage'
import { Linter } from '../linters/Linter'

export const DEFAULT_RESULT: ValidationResult = {
  decision: undefined,
  reason: ''
}

export class PostToolLintHandler {
  private readonly linter: Linter | null
  private readonly storage: Storage

  constructor(storage: Storage, linter?: Linter | null) {
    this.storage = storage
    this.linter = linter ?? null
  }

  async handle(hookData: string): Promise<ValidationResult> {
    // If no linter is configured, skip linting
    if (!this.linter) {
      return DEFAULT_RESULT
    }
    return handlePostToolLint(hookData, this.storage, this.linter)
  }
}

function parseAndValidateHookData(hookData: string): HookData | null {
  try {
    const parsedData = JSON.parse(hookData)
    const hookResult = HookDataSchema.safeParse(parsedData)
    
    if (!hookResult.success) {
      return null
    }
    
    // Only process PostToolUse hooks
    if (hookResult.data.hook_event_name !== 'PostToolUse') {
      return null
    }
    
    return hookResult.data
  } catch {
    return null
  }
}

async function getStoredLintData(storage: Storage): Promise<LintData | null> {
  try {
    const lintDataStr = await storage.getLint()
    if (lintDataStr) {
      return LintDataSchema.parse(JSON.parse(lintDataStr))
    }
  } catch {
    // Treat any error as no stored data
  }
  return null
}

function createLintData(
  lintResults: LintResult,
  storedLintData: LintData | null
): LintData {
  const hasIssues = lintResults.errorCount > 0 || lintResults.warningCount > 0
  
  return {
    ...lintResults,
    hasNotifiedAboutLintIssues: hasIssues 
      ? (storedLintData?.hasNotifiedAboutLintIssues ?? false)  // Preserve flag when issues exist
      : false  // Reset flag when no issues
  }
}

function createBlockResult(lintData: LintData): ValidationResult {
  const formattedIssues = formatLintIssues(lintData.issues)
  const summary = `\n✖ ${lintData.errorCount + lintData.warningCount} problems (${lintData.errorCount} errors, ${lintData.warningCount} warnings)`
  
  return {
    decision: 'block',
    reason: `Lint issues detected:${formattedIssues}\n${summary}\n\nPlease fix these issues before proceeding.`
  }
}

function formatLintIssues(issues: LintData['issues']): string {
  const issuesByFile = new Map<string, string[]>()
  
  for (const issue of issues) {
    if (!issuesByFile.has(issue.file)) {
      issuesByFile.set(issue.file, [])
    }
    const ruleInfo = issue.rule ? `  ${issue.rule}` : ''
    issuesByFile.get(issue.file)!.push(
      `  ${issue.line}:${issue.column}  ${issue.severity}  ${issue.message}${ruleInfo}`
    )
  }

  let formattedIssues = ''
  for (const [file, fileIssues] of issuesByFile) {
    formattedIssues += `\n${file}\n${fileIssues.join('\n')}`
  }
  
  return formattedIssues
}

export async function handlePostToolLint(
  hookData: string,
  storage: Storage,
  linter: Linter
): Promise<ValidationResult> {
  const validatedHookData = parseAndValidateHookData(hookData)
  if (!validatedHookData) {
    return DEFAULT_RESULT
  }

  // Extract file paths from tool operation
  const filePaths = extractFilePaths(validatedHookData)
  if (filePaths.length === 0) {
    return DEFAULT_RESULT
  }

  // Get current lint data to check hasNotifiedAboutLintIssues state
  const storedLintData = await getStoredLintData(storage)

  // Run linting on the files
  const lintResults = await linter.lint(filePaths)
  
  // Create and save lint data
  const lintData = createLintData(lintResults, storedLintData)
  await storage.saveLint(JSON.stringify(lintData))
  
  const hasIssues = lintResults.errorCount > 0 || lintResults.warningCount > 0

  // Block if:
  // 1. PreToolUse has notified (flag is true)
  // 2. There are still issues
  if (storedLintData?.hasNotifiedAboutLintIssues && hasIssues) {
    return createBlockResult(lintData)
  }

  return DEFAULT_RESULT
}

function extractFilePaths(hookData: HookData): string[] {
  const toolInput = hookData.tool_input
  if (!toolInput || typeof toolInput !== 'object') return []

  const paths: string[] = []

  if ('file_path' in toolInput && typeof toolInput.file_path === 'string') {
    paths.push(toolInput.file_path)
  }

  // Handle multi-edit operations
  if ('edits' in toolInput && Array.isArray(toolInput.edits)) {
    for (const edit of toolInput.edits) {
      if ('file_path' in edit && typeof edit.file_path === 'string') {
        paths.push(edit.file_path)
      }
    }
  }

  return [...new Set(paths)] // Remove duplicates
}