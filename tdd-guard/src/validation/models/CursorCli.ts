import { execFileSync } from 'child_process'
import { join } from 'path'
import { homedir } from 'os'
import { existsSync, mkdirSync, writeFileSync, unlinkSync } from 'fs'
import { IModelClient } from '../../contracts/types/ModelClient'
import { Config } from '../../config/Config'

/** Marker file content: when this file exists in .cursor, adapter skips SessionStart (from tdd-guard cursor-cli). */
export const TDD_GUARD_VALIDATING_MARKER = 'tdd-guard-validating'

/**
 * Cursor CLI client - invokes the Cursor CLI (agent command) for TDD validation.
 * Uses print mode (-p) with prompt as argument and --output-format json.
 * See: https://cursor.com/docs/cli/overview, https://cursor.com/docs/cli/headless
 */
export class CursorCli implements IModelClient {
  private readonly config: Config

  constructor(config?: Config) {
    this.config = config ?? new Config()
  }

  async ask(prompt: string): Promise<string> {
    const agentBinary = this.getAgentBinary()

    const args = [
      '-p',
      '--trust',
      '--mode',
      'ask',
      '--model',
      'Auto',
      '--output-format',
      'json',
      prompt,
    ]
    const cursorDir = join(process.cwd(), '.cursor')

    if (!existsSync(cursorDir)) {
      mkdirSync(cursorDir, { recursive: true })
    }

    const validatingFlagPath = join(cursorDir, '.tdd-guard-validating')
    try {
      writeFileSync(validatingFlagPath, TDD_GUARD_VALIDATING_MARKER, 'utf8')
      const output = execFileSync(agentBinary, args, {
        encoding: 'utf-8',
        timeout: 60000,
        cwd: cursorDir,
        shell: process.platform === 'win32',
      })

      let parsed: { result?: string; error?: string }
      try {
        parsed = JSON.parse(output)
      } catch {
        const preview = output.trim().slice(0, 200)
        throw new Error(
          `Model returned non-JSON: ${preview}${output.length > 200 ? '...' : ''}`
        )
      }

      if (parsed.error) {
        throw new Error(`Model error: ${parsed.error}`)
      }

      const result = parsed.result ?? ''
      if (
        typeof result === 'string' &&
        result.length > 0 &&
        result.length < 400 &&
        !result.trim().startsWith('{') &&
        !result.trim().startsWith('[') &&
        /^(Invalid|Error|Authentication|API|Unauthorized|Failed|Permission|Unexpected)/i.test(
          result.trim()
        )
      ) {
        throw new Error(`Model error: ${result.trim()}`)
      }
      return result
    } finally {
      try {
        if (existsSync(validatingFlagPath)) {
          unlinkSync(validatingFlagPath)
        }
      } catch {
        // ignore
      }
    }
  }

  private getAgentBinary(): string {
    if (this.config.useSystemCursor) {
      return 'agent'
    }

    return join(homedir(), '.local', 'bin', 'agent')
  }
}
