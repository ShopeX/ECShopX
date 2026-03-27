#!/usr/bin/env node

/**
 * Cursor hook adapter for tdd-guard.
 * Used from .cursor/hooks.json. Normalizes Cursor hook payload to the format
 * tdd-guard expects, invokes tdd-guard, and forwards the result.
 *
 * - Write: if target file exists, converts to Edit/MultiEdit (line diff); new files stay Write.
 * - Expected by tdd-guard: hook_event_name, session_id, transcript_path, tool_name, tool_input
 */

const { execFileSync } = require('child_process')
const fs = require('fs')
const path = require('path')
const DEBUG_LOG = path.join(__dirname, '..', 'debug.log')
function debug(label, obj) {
  const ts = new Date().toISOString()
  fs.appendFileSync(
    DEBUG_LOG,
    `[tdd-guard-adapter ${ts}] ${label}: ${JSON.stringify(obj, null, 2)}\n`
  )
}

const EVENT_MAP = {
  preToolUse: 'PreToolUse',
  afterFileEdit: 'PostToolUse',
  postToolUse: 'PostToolUse',
  beforeSubmitPrompt: 'UserPromptSubmit',
  sessionStart: 'SessionStart',
}

function resolveFilePath(filePath, workspaceRoots) {
  if (path.isAbsolute(filePath)) return filePath
  const root = Array.isArray(workspaceRoots) && workspaceRoots.length > 0
    ? workspaceRoots[0]
    : process.cwd()
  return path.resolve(root, filePath)
}

function diffLinesToEdits(a, b) {
  const ol = a.split(/\n/)
  const nl = b.split(/\n/)
  const N = ol.length
  const M = nl.length
  if (N === 0 && M === 0) return []
  const dp = Array(N + 1)
  for (let i = 0; i <= N; i++) dp[i] = Array(M + 1).fill(0)
  for (let i = 1; i <= N; i++) {
    for (let j = 1; j <= M; j++) {
      dp[i][j] = ol[i - 1] === nl[j - 1] ? dp[i - 1][j - 1] + 1 : Math.max(dp[i - 1][j], dp[i][j - 1])
    }
  }
  const result = []
  let i = N
  let j = M
  const ob = []
  const nb = []
  while (i > 0 || j > 0) {
    if (i > 0 && j > 0 && ol[i - 1] === nl[j - 1]) {
      if (ob.length || nb.length) {
        result.unshift({ old_string: ob.join('\n'), new_string: nb.join('\n') })
        ob.length = 0
        nb.length = 0
      }
      i--
      j--
      continue
    }
    if (j > 0 && (i === 0 || dp[i][j - 1] >= dp[i - 1][j])) {
      nb.unshift(nl[j - 1])
      j--
      continue
    }
    if (i > 0) {
      ob.unshift(ol[i - 1])
      i--
    }
  }
  if (ob.length || nb.length) {
    result.unshift({ old_string: ob.join('\n'), new_string: nb.join('\n') })
  }
  return result
}

function writeToEditOrMultiEdit(data) {
  const ti = data.tool_input
  const filePath = ti.file_path || ti.path
  const newContent = ti.content != null ? String(ti.content) : (ti.contents != null ? String(ti.contents) : '')
  if (!filePath) return null

  const absPath = resolveFilePath(filePath, data.workspace_roots)
  if (!fs.existsSync(absPath)) return null

  let oldContent
  try {
    oldContent = fs.readFileSync(absPath, 'utf8')
  } catch {
    return null
  }

  const edits = diffLinesToEdits(oldContent, newContent)
  if (edits.length === 0) return null

  if (edits.length === 1) {
    return {
      tool_name: 'Edit',
      tool_input: {
        file_path: filePath,
        old_string: edits[0].old_string,
        new_string: edits[0].new_string,
      },
    }
  }
  return {
    tool_name: 'MultiEdit',
    tool_input: {
      file_path: filePath,
      edits,
    },
  }
}

function transform(data) {
  const originalEvent = data.hook_event_name
  if (originalEvent && EVENT_MAP[originalEvent]) {
    data.hook_event_name = EVENT_MAP[originalEvent]
  }
  if (!data.session_id) {
    data.session_id = data.conversation_id || 'cursor-session'
  }
  if (!data.transcript_path) {
    data.transcript_path = data.transcript_path || ''
  }
  if (data.hook_event_name === 'UserPromptSubmit' && !data.cwd) {
    data.cwd =
      Array.isArray(data.workspace_roots) && data.workspace_roots.length > 0
        ? data.workspace_roots[0]
        : process.cwd()
  }

  // Cursor preToolUse: may use tool/name and arguments/input instead of tool_name/tool_input
  const tool = data.tool_name || data.tool || data.name
  const input = data.tool_input || data.arguments || data.input || {}
  if (tool) data.tool_name = tool
  if (typeof input === 'object' && Object.keys(input).length > 0) {
    data.tool_input = input
  }

  // Write: normalize file_path/content; convert to Edit/MultiEdit when file exists
  if (data.tool_name === 'Write' && data.tool_input) {
    const ti = data.tool_input
    if (!ti.file_path && ti.path) ti.file_path = ti.path
    if (ti.content === undefined && ti.contents !== undefined) {
      ti.content = ti.contents
    }
    const converted = writeToEditOrMultiEdit(data)
    if (converted) {
      data.tool_name = converted.tool_name
      data.tool_input = converted.tool_input
    } else {
      data.tool_input = {
        file_path: ti.file_path || '',
        content: ti.content != null ? String(ti.content) : '',
      }
    }
  }

  if (data.hook_event_name === 'SessionStart' && !data.source) {
    data.source = 'startup'
  }

  return data
}

/** Marker file: must match CursorCli; when present, SessionStart is from tdd-guard cursor-cli and should be skipped. */
const TDD_GUARD_VALIDATING_MARKER = 'tdd-guard-validating'
const VALIDATING_FLAG_PATH = path.join(
  process.cwd(),
  '.cursor',
  '.tdd-guard-validating'
)

function hasValidatingMarker() {
  try {
    if (!fs.existsSync(VALIDATING_FLAG_PATH)) return false
    return (
      fs.readFileSync(VALIDATING_FLAG_PATH, 'utf8').trim() ===
      TDD_GUARD_VALIDATING_MARKER
    )
  } catch {
    return false
  }
}

let input = ''
process.stdin.setEncoding('utf8')
process.stdin.on('data', (chunk) => {
  input += chunk
})

process.stdin.on('end', () => {
  try {
    const data = JSON.parse(input)
    const transformed = transform(data)
    debug('input (to tdd-guard)', transformed)

    if (
      transformed.hook_event_name === 'SessionStart' &&
      hasValidatingMarker()
    ) {
      process.stdout.write(JSON.stringify({ decision: undefined, reason: '' }))
      return
    }

    const payload = JSON.stringify(transformed)

    const output = execFileSync('tdd-guard', [], {
      input: payload,
      encoding: 'utf-8',
      timeout: 300000,
      stdio: ['pipe', 'pipe', 'pipe'],
    })

    process.stdout.write(output)
    try {
      const result = JSON.parse(output)
      debug('output (from tdd-guard)', result)
      if (result.decision === 'block' || result.continue === false) process.exit(2)
    } catch {
      // ignore parse error
    }
  } catch (err) {
    debug('error', {
      message: err.message,
      stdout: err.stdout,
      stderr: err.stderr,
    })
    if (err.stdout) {
      process.stdout.write(err.stdout)
      try {
        const result = JSON.parse(err.stdout)
        if (result.decision === 'block' || result.continue === false) process.exit(2)
      } catch {
        // ignore parse error
      }
    } else {
      process.stdout.write(JSON.stringify({ decision: undefined, reason: '' }))
    }
  }
})
