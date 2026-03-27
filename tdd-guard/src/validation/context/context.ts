import { Context, ProcessedLintData } from '../../contracts/types/Context'
import { Config } from '../../config/Config'
import {
  isEditOperation,
  isMultiEditOperation,
  isWriteOperation,
  ToolOperation,
  EditOperation,
  MultiEditOperation,
  WriteOperation,
  Todo,
} from '../../contracts/schemas/toolSchemas'
import { TestResultsProcessor } from '../../processors'
import { formatLintDataForContext } from '../../processors/lintProcessor'

// Import core prompts
import { SYSTEM_PROMPT } from '../prompts/system-prompt'
import { RULES } from '../prompts/rules'
import { FILE_TYPES } from '../prompts/file-types'
import { RESPONSE } from '../prompts/response'

// Import operation-specific context
import { EDIT } from '../prompts/operations/edit'
import { MULTI_EDIT } from '../prompts/operations/multi-edit'
import { WRITE } from '../prompts/operations/write'
import { TODOS } from '../prompts/tools/todos'
import { TEST_OUTPUT } from '../prompts/tools/test-output'
import { LINT_RESULTS } from '../prompts/tools/lint-results'

export function generateDynamicContext(
  context: Context,
  config?: Config
): string {
  const operation: ToolOperation = JSON.parse(context.modifications)
  const effectiveConfig = config ?? new Config()

  const sections: string[] = [
    // 1. Core sections (system prompt only for CLI)
    getSystemPrompt(effectiveConfig),
    context.instructions ?? RULES,
    FILE_TYPES,

    // 2. Operation-specific context and changes
    formatOperation(operation),

    // 3. Additional context
    formatTestSection(context.test),
    formatTodoSection(context.todo),
    formatLintSection(context.lint),

    // 4. Response format
    RESPONSE,
  ]

  return sections.filter(Boolean).join('\n')
}

function formatOperation(operation: ToolOperation): string {
  if (isEditOperation(operation)) {
    return EDIT + formatEditOperation(operation)
  }

  if (isMultiEditOperation(operation)) {
    return MULTI_EDIT + formatMultiEditOperation(operation)
  }

  if (isWriteOperation(operation)) {
    return WRITE + formatWriteOperation(operation)
  }

  return ''
}

function formatEditOperation(operation: EditOperation): string {
  return (
    formatSection('File Path', operation.tool_input.file_path) +
    formatSection('Old Content', operation.tool_input.old_string) +
    formatSection('New Content', operation.tool_input.new_string)
  )
}

function formatMultiEditOperation(operation: MultiEditOperation): string {
  const editsFormatted = operation.tool_input.edits
    .map((edit, index) => formatEdit(edit, index + 1))
    .join('')

  return `${formatSection(
    'File Path',
    operation.tool_input.file_path
  )}\n### Edits\n${editsFormatted}`
}

function formatWriteOperation(operation: WriteOperation): string {
  return (
    formatSection('File Path', operation.tool_input.file_path) +
    formatSection('New File Content', operation.tool_input.content)
  )
}

function formatEdit(
  edit: { old_string: string; new_string: string },
  index: number
): string {
  return (
    `\n#### Edit ${index}:\n` +
    `**Old Content:**\n${codeBlock(edit.old_string)}` +
    `**New Content:**\n${codeBlock(edit.new_string)}`
  )
}

function formatTestSection(testOutput?: string): string {
  if (!testOutput) return ''

  const output = testOutput.trim()
    ? new TestResultsProcessor().process(testOutput)
    : 'No test output available. Tests must be run before implementing.'

  return TEST_OUTPUT + codeBlock(output)
}

function formatTodoSection(todoJson?: string): string {
  if (!todoJson) return ''

  const todoOperation = JSON.parse(todoJson)
  const todos: Todo[] = todoOperation.tool_input?.todos ?? []

  const todoItems = todos
    .map(
      (todo, index) =>
        `${index + 1}. [${todo.status}] ${todo.content} (${todo.priority})`
    )
    .join('\n')

  return `${TODOS}${todoItems}\n`
}

function formatLintSection(lintData?: ProcessedLintData): string {
  if (!lintData) return ''

  const formattedLintData = formatLintDataForContext(lintData)
  return LINT_RESULTS + codeBlock(formattedLintData)
}

function formatSection(title: string, content: string): string {
  return `\n### ${title}\n${codeBlock(content)}`
}

function codeBlock(content: string): string {
  return `\`\`\`\n${content}\n\`\`\`\n`
}

function getSystemPrompt(config: Config): string {
  // CLI-based clients require the system prompt in the query
  return config.validationClient === 'cli' ||
    config.validationClient === 'cursor-cli'
    ? SYSTEM_PROMPT
    : ''
}
