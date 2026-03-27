import { z } from 'zod'

// Base Hook Context
export const HookContextSchema = z.object({
  session_id: z.string(),
  transcript_path: z.string(),
  hook_event_name: z.string(),
})

export const HookDataSchema = HookContextSchema.extend({
  tool_name: z.string(),
  tool_input: z.unknown(),
})

export type HookData = z.infer<typeof HookDataSchema>

// UserPromptSubmit Schema
export const UserPromptSubmitSchema = HookContextSchema.extend({
  prompt: z.string(),
  cwd: z.string(),
}).refine((data) => data.hook_event_name === 'UserPromptSubmit')

export type UserPromptSubmit = z.infer<typeof UserPromptSubmitSchema>

// SessionStart Schema
export const SessionStartSchema = HookContextSchema.extend({
  hook_event_name: z.literal('SessionStart'),
  source: z.enum(['startup', 'resume', 'clear']),
})

export type SessionStart = z.infer<typeof SessionStartSchema>

// Tool Input Schemas
export const TodoSchema = z.object({
  content: z.string(),
  status: z.enum(['pending', 'in_progress', 'completed']),
  priority: z.enum(['high', 'medium', 'low']),
  id: z.string(),
})

export type Todo = z.infer<typeof TodoSchema>

export const EditSchema = z.object({
  file_path: z.string(),
  old_string: z.string(),
  new_string: z.string(),
  replace_all: z.boolean().optional(),
})

export type Edit = z.infer<typeof EditSchema>

const EditEntrySchema = z.object({
  old_string: z.string(),
  new_string: z.string(),
  replace_all: z.boolean().optional(),
})

export const MultiEditSchema = z.object({
  file_path: z.string(),
  edits: z.array(EditEntrySchema).min(1),
})

export type MultiEdit = z.infer<typeof MultiEditSchema>

export const WriteSchema = z.object({
  file_path: z.string(),
  content: z.string(),
})

export type Write = z.infer<typeof WriteSchema>

export const TodoWriteSchema = z.object({
  todos: z.array(TodoSchema).min(1),
})

export type TodoWrite = z.infer<typeof TodoWriteSchema>

// Tool Operation Schemas
export const EditOperationSchema = HookContextSchema.extend({
  tool_name: z.literal('Edit'),
  tool_input: EditSchema,
})

export const MultiEditOperationSchema = HookContextSchema.extend({
  tool_name: z.literal('MultiEdit'),
  tool_input: MultiEditSchema,
})

export const WriteOperationSchema = HookContextSchema.extend({
  tool_name: z.literal('Write'),
  tool_input: WriteSchema,
})

export const TodoWriteOperationSchema = HookContextSchema.extend({
  tool_name: z.literal('TodoWrite'),
  tool_input: TodoWriteSchema,
})

export type EditOperation = z.infer<typeof EditOperationSchema>
export type MultiEditOperation = z.infer<typeof MultiEditOperationSchema>
export type WriteOperation = z.infer<typeof WriteOperationSchema>
export type TodoWriteOperation = z.infer<typeof TodoWriteOperationSchema>

// Discriminated Unions
export const ToolOperationSchema = z.discriminatedUnion('tool_name', [
  EditOperationSchema,
  MultiEditOperationSchema,
  WriteOperationSchema,
  TodoWriteOperationSchema,
])

export type ToolOperation = z.infer<typeof ToolOperationSchema>

export const FileModificationSchema = z.discriminatedUnion('tool_name', [
  EditOperationSchema,
  MultiEditOperationSchema,
  WriteOperationSchema,
])

export type FileModification = z.infer<typeof FileModificationSchema>

// Type Guards
export const isEditOperation = (op: ToolOperation): op is EditOperation =>
  op.tool_name === 'Edit'

export const isMultiEditOperation = (
  op: ToolOperation
): op is MultiEditOperation => op.tool_name === 'MultiEdit'

export const isWriteOperation = (op: ToolOperation): op is WriteOperation =>
  op.tool_name === 'Write'

export const isTodoWriteOperation = (
  op: ToolOperation
): op is TodoWriteOperation => op.tool_name === 'TodoWrite'

export const isFileModification = (op: ToolOperation): op is FileModification =>
  op.tool_name === 'Edit' ||
  op.tool_name === 'MultiEdit' ||
  op.tool_name === 'Write'
