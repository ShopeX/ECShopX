import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest'
import { CursorCli, TDD_GUARD_VALIDATING_MARKER } from './CursorCli'
import { execFileSync } from 'child_process'
import { homedir } from 'os'
import { join } from 'path'
import * as fs from 'fs'
import { Config } from '../../config/Config'

vi.mock('child_process')
vi.mock('fs', { spy: true })

const mockExecFileSync = vi.mocked(execFileSync)

const DEFAULT_TEST_PROMPT = 'test prompt'

describe('CursorCli', () => {
  let sut: Awaited<ReturnType<typeof createSut>>
  let client: CursorCli

  beforeEach(() => {
    sut = createSut()
    client = sut.client
  })

  describe('command construction', () => {
    test('uses agent command (Cursor CLI)', async () => {
      const call = await sut.askAndGetCall()
      expect(call.command).toContain('agent')
    })

    test('uses -p, --trust, --model Auto, --output-format json with prompt as argument', async () => {
      const prompt = 'Does this follow TDD?'
      const call = await sut.askAndGetCall(prompt)
      expect(call.args[0]).toBe('-p')
      expect(call.args[1]).toBe('--trust')
      expect(call.args[2]).toBe('--mode')
      expect(call.args[3]).toBe('ask')
      expect(call.args[4]).toBe('--model')
      expect(call.args[5]).toBe('Auto')
      expect(call.args[6]).toBe('--output-format')
      expect(call.args[7]).toBe('json')
      expect(call.args[8]).toBe(prompt)
    })
  })

  describe('subprocess configuration', () => {
    test('passes prompt as last argument (Cursor CLI print mode)', async () => {
      const prompt = 'Does this follow TDD?'
      const call = await sut.askAndGetCall(prompt)
      expect(call.args[call.args.length - 1]).toBe(prompt)
    })

    test('uses utf-8 encoding', async () => {
      const call = await sut.askAndGetCall()
      expect(call.options.encoding).toBe('utf-8')
    })

    test('sets timeout to 5 minutes', async () => {
      const call = await sut.askAndGetCall()
      expect(call.options.timeout).toBe(300000)
    })

    test('executes cursor command from .cursor subdirectory', async () => {
      const call = await sut.askAndGetCall()
      expect(call.options.cwd).toContain('.cursor')
    })

    test('writes validating marker file before invoking agent and removes it in finally', async () => {
      const writeFileSync = vi.spyOn(fs, 'writeFileSync')
      const unlinkSync = vi.spyOn(fs, 'unlinkSync')

      await client.ask(DEFAULT_TEST_PROMPT)

      const writeCalls = writeFileSync.mock.calls.filter((c) =>
        (c[0] as string).endsWith('.tdd-guard-validating')
      )
      expect(writeCalls.length).toBe(1)
      expect(writeCalls[0][1]).toBe(TDD_GUARD_VALIDATING_MARKER)

      const unlinkCalls = unlinkSync.mock.calls.filter((c) =>
        (c[0] as string).endsWith('.tdd-guard-validating')
      )
      expect(unlinkCalls.length).toBe(1)

      writeFileSync.mockRestore()
      unlinkSync.mockRestore()
    })

    test('creates .cursor directory if it does not exist', async () => {
      const mockMkdirSync = vi.spyOn(fs, 'mkdirSync')
      const mockExistsSync = vi.spyOn(fs, 'existsSync')

      mockExistsSync.mockReturnValue(false)

      await client.ask('test')

      expect(mockMkdirSync).toHaveBeenCalledWith(
        expect.stringContaining('.cursor'),
        { recursive: true }
      )

      mockMkdirSync.mockRestore()
      mockExistsSync.mockRestore()
    })
  })

  describe('platform-specific shell option', () => {
    let originalPlatform: PropertyDescriptor | undefined

    beforeEach(() => {
      originalPlatform = Object.getOwnPropertyDescriptor(process, 'platform')
    })

    afterEach(() => {
      if (originalPlatform) {
        Object.defineProperty(process, 'platform', originalPlatform)
      }
    })

    test.each([
      ['win32', true],
      ['darwin', false],
      ['linux', false],
    ])(
      'platform %s sets shell option to %s',
      async (platform, expectedShell) => {
        Object.defineProperty(process, 'platform', { value: platform })

        const call = await sut.askAndGetCall()

        expect(call.options.shell).toBe(expectedShell)
      }
    )
  })

  describe('response handling', () => {
    test('extracts and returns the result field from CLI output', async () => {
      const modelResponse = '```json\n{"approved": true}\n```'
      const cliOutput = JSON.stringify({ result: modelResponse })
      mockExecFileSync.mockReturnValue(cliOutput)

      const result = await client.ask(DEFAULT_TEST_PROMPT)

      expect(result).toBe(modelResponse)
    })

    test('extracts result field from complex CLI responses', async () => {
      const modelResponse =
        'Here is the analysis:\n```json\n{"approved": true}\n```\nThat concludes the review.'
      const cliOutput = JSON.stringify({
        result: modelResponse,
        metadata: { model: 'cursor' },
      })
      mockExecFileSync.mockReturnValue(cliOutput)

      const result = await client.ask(DEFAULT_TEST_PROMPT)

      expect(result).toBe(modelResponse)
    })

    test('throws when response has error field', async () => {
      const cliOutput = JSON.stringify({ error: 'No result' })
      mockExecFileSync.mockReturnValue(cliOutput)

      await expect(client.ask(DEFAULT_TEST_PROMPT)).rejects.toThrow(
        'Model error: No result'
      )
    })

    test('returns empty string when result field is missing and no error', async () => {
      const cliOutput = JSON.stringify({})
      mockExecFileSync.mockReturnValue(cliOutput)

      const result = await client.ask(DEFAULT_TEST_PROMPT)

      expect(result).toBe('')
    })

    test('throws when result looks like error message (e.g. Invalid API key)', async () => {
      const cliOutput = JSON.stringify({
        result: 'Invalid API key · Please run /login',
      })
      mockExecFileSync.mockReturnValue(cliOutput)

      await expect(client.ask(DEFAULT_TEST_PROMPT)).rejects.toThrow(
        'Model error: Invalid API key · Please run /login'
      )
    })
  })

  describe('error handling', () => {
    test('throws error when execFileSync fails', async () => {
      mockExecFileSync.mockImplementation(() => {
        throw new Error('Command failed')
      })

      await expect(client.ask('test')).rejects.toThrow('Command failed')
    })

    test('throws error when CLI output is not valid JSON', async () => {
      const rawOutput = 'invalid json or error message'
      mockExecFileSync.mockReturnValue(rawOutput)

      await expect(client.ask('test')).rejects.toThrow()
    })
  })

  describe('security', () => {
    test('uses agent from PATH when useSystemCursor is true', async () => {
      const localSut = createSut({ useSystemCursor: true })
      await localSut.client.ask(DEFAULT_TEST_PROMPT)

      const call = localSut.getLastCall()
      expect(call.command).toBe('agent')
    })

    test('uses local agent path when useSystemCursor is false', async () => {
      const localSut = createSut({ useSystemCursor: false })
      await localSut.client.ask(DEFAULT_TEST_PROMPT)

      const call = localSut.getLastCall()
      expect(call.command).toBe(join(homedir(), '.local', 'bin', 'agent'))
    })
  })
})

function createSut(options: { useSystemCursor?: boolean } = {}) {
  vi.clearAllMocks()
  mockExecFileSync.mockReturnValue(JSON.stringify({ result: 'test' }))
  vi.spyOn(fs, 'existsSync').mockReturnValue(true)

  const config = new Config({
    validationClient: 'cursor-cli',
    useSystemCursor: options.useSystemCursor,
  })
  const client = new CursorCli(config)

  const getLastCall = (): {
    command: string
    args: string[]
    options: Record<string, unknown>
  } => {
    const lastCall =
      mockExecFileSync.mock.calls[mockExecFileSync.mock.calls.length - 1]
    return {
      command: lastCall[0] as string,
      args: lastCall[1] as string[],
      options: lastCall[2] as Record<string, unknown>,
    }
  }

  const askAndGetCall = async (
    prompt = DEFAULT_TEST_PROMPT
  ): Promise<{
    command: string
    args: string[]
    options: Record<string, unknown>
  }> => {
    await client.ask(prompt)
    return getLastCall()
  }

  return {
    client,
    getLastCall,
    askAndGetCall,
  }
}
