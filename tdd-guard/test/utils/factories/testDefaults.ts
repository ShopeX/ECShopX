/**
 * Default values for test data used across factories
 */

const session_id = 'test-session-id'
const transcript_path = '/path/to/transcript.jsonl'
const hook_event_name = 'PreToolUse'

const todo = {
  content: 'Implement feature',
  status: 'pending' as const,
  priority: 'high' as const,
  id: '123',
}
const write = {
  file_path: '/test/file.ts',
  content: 'file content to write',
}

const edit = {
  file_path: '/test/file.ts',
  old_string: 'old content;',
  new_string: 'old content; new content',
}

const multiEdit = {
  file_path: '/test/file.ts',
  edits: [
    {
      old_string: 'first old content',
      new_string: 'first new content',
    },
    {
      old_string: 'second old content',
      new_string: 'second new content',
    },
  ],
}

export const TEST_DEFAULTS = {
  hookData: {
    session_id,
    transcript_path,
    hook_event_name,
  },
  todo,
  todoWriteOperation: {
    tool_name: 'TodoWrite',
    tool_input: {
      todos: [todo],
    },
  },
  write,
  writeOperation: {
    tool_name: 'Write',
    tool_input: {
      ...write,
    },
  },
  edit,
  editOperation: {
    tool_name: 'Edit',
    tool_input: {
      ...edit,
    },
  },
  multiEdit,
  multiEditOperation: {
    tool_name: 'MultiEdit',
    tool_input: {
      ...multiEdit,
    },
  },
  userPromptSubmit: {
    session_id,
    transcript_path,
    hook_event_name: 'UserPromptSubmit',
    prompt: 'tdd-guard on',
    cwd: '/current/working/directory',
  },
  sessionStart: {
    session_id,
    transcript_path,
    hook_event_name: 'SessionStart',
    source: 'startup' as const,
  },
  // Context defaults
  modifications: 'Test modifications',
  test: 'Test results',
} as const
