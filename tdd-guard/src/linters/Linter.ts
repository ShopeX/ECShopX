import { LintResult } from '../contracts/schemas/lintSchemas'

export interface Linter {
  lint(filePaths: string[], configPath?: string): Promise<LintResult>
}
