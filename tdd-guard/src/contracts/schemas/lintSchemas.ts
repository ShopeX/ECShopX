import { z } from 'zod'

export const ESLintMessageSchema = z.object({
  line: z.number().optional(),
  column: z.number().optional(),
  severity: z.number(),
  message: z.string(),
  ruleId: z.string().optional(),
})

export const ESLintResultSchema = z.object({
  filePath: z.string(),
  messages: z.array(ESLintMessageSchema).optional(),
})

export const GolangciLintPositionSchema = z.object({
  Filename: z.string(),
  Line: z.number(),
  Column: z.number(),
})

export const GolangciLintIssueSchema = z.object({
  FromLinter: z.string(),
  Text: z.string(),
  Severity: z.string(),
  Pos: GolangciLintPositionSchema,
})

export const GolangciLintResultSchema = z.object({
  Issues: z.array(GolangciLintIssueSchema).optional(),
})

export const LintIssueSchema = z.object({
  file: z.string(),
  line: z.number(),
  column: z.number(),
  severity: z.enum(['error', 'warning']),
  message: z.string(),
  rule: z.string().optional(),
})

export const LintResultSchema = z.object({
  timestamp: z.string(),
  files: z.array(z.string()),
  issues: z.array(LintIssueSchema),
  errorCount: z.number(),
  warningCount: z.number(),
})

export const LintDataSchema = LintResultSchema.extend({
  hasNotifiedAboutLintIssues: z.boolean(),
})

export type ESLintMessage = z.infer<typeof ESLintMessageSchema>
export type ESLintResult = z.infer<typeof ESLintResultSchema>
export type GolangciLintPosition = z.infer<typeof GolangciLintPositionSchema>
export type GolangciLintIssue = z.infer<typeof GolangciLintIssueSchema>
export type GolangciLintResult = z.infer<typeof GolangciLintResultSchema>
export type LintData = z.infer<typeof LintDataSchema>
export type LintIssue = z.infer<typeof LintIssueSchema>
export type LintResult = z.infer<typeof LintResultSchema>
