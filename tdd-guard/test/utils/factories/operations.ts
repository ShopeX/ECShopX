import type {
  Write,
  Edit,
  MultiEdit,
  TodoWrite,
} from '../../../src/contracts/schemas/toolSchemas'

type ToolInput = Write | Edit | MultiEdit | TodoWrite

export function createOperation(
  toolName: string,
  toolInput: ToolInput
): string {
  return JSON.stringify({
    tool_name: toolName,
    session_id: 'test-session',
    transcript_path: '/test/transcript',
    hook_event_name: 'tool_use',
    tool_input: toolInput,
  })
}

export function createWriteOperation(
  filePath: string,
  content: string
): string {
  return createOperation('Write', {
    file_path: filePath,
    content,
  })
}

export function createEditOperation(
  filePath: string,
  oldString: string,
  newString: string
): string {
  return createOperation('Edit', {
    file_path: filePath,
    old_string: oldString,
    new_string: newString,
  })
}

export function createMultiEditOperation(
  filePath: string,
  edits: Array<{
    old_string: string
    new_string: string
    replace_all?: boolean
  }>
): string {
  return createOperation('MultiEdit', {
    file_path: filePath,
    edits,
  })
}
