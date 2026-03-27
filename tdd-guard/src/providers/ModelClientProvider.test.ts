import { describe, test, expect } from 'vitest'
import { ModelClientProvider } from './ModelClientProvider'
import { Config } from '../config/Config'
import { ClaudeCli } from '../validation/models/ClaudeCli'
import { CursorCli } from '../validation/models/CursorCli'
import { AnthropicApi } from '../validation/models/AnthropicApi'
import { ClaudeAgentSdk } from '../validation/models/ClaudeAgentSdk'

describe('ModelClientProvider', () => {
  test('uses default config when no config is provided', () => {
    const provider = new ModelClientProvider()
    const client = provider.getModelClient()

    expect(client['config']).toBeDefined()
  })

  test('returns ClaudeCli when config validationClient is cli', () => {
    const config = new Config({ validationClient: 'cli' })

    const provider = new ModelClientProvider()
    const client = provider.getModelClient(config)

    expect(client).toBeInstanceOf(ClaudeCli)
  })

  test('returns AnthropicApi when config validationClient is api', () => {
    const config = new Config({ validationClient: 'api' })

    const provider = new ModelClientProvider()
    const client = provider.getModelClient(config)

    expect(client).toBeInstanceOf(AnthropicApi)
  })

  test('returns ClaudeAgentSdk when config validationClient is sdk', () => {
    const config = new Config({ validationClient: 'sdk' })

    const provider = new ModelClientProvider()
    const client = provider.getModelClient(config)

    expect(client).toBeInstanceOf(ClaudeAgentSdk)
  })

  test('returns CursorCli when config validationClient is cursor-cli', () => {
    const config = new Config({ validationClient: 'cursor-cli' })

    const provider = new ModelClientProvider()
    const client = provider.getModelClient(config)

    expect(client).toBeInstanceOf(CursorCli)
  })

  test('passes config with API key to AnthropicApi client', () => {
    const config = new Config({
      validationClient: 'api',
      anthropicApiKey: 'test-api-key-123',
    })

    const provider = new ModelClientProvider()
    const client = provider.getModelClient(config)

    expect(client).toBeInstanceOf(AnthropicApi)
    expect(client['config'].anthropicApiKey).toBe('test-api-key-123')
  })

  test('passes config with useSystemClaude to ClaudeCli client', () => {
    const config = new Config({
      validationClient: 'cli',
      useSystemClaude: true,
    })

    const provider = new ModelClientProvider()
    const client = provider.getModelClient(config)

    expect(client).toBeInstanceOf(ClaudeCli)
    expect(client['config'].useSystemClaude).toBe(true)
  })

  test('passes config with useSystemCursor to CursorCli client', () => {
    const config = new Config({
      validationClient: 'cursor-cli',
      useSystemCursor: true,
    })

    const provider = new ModelClientProvider()
    const client = provider.getModelClient(config)

    expect(client).toBeInstanceOf(CursorCli)
    expect(client['config'].useSystemCursor).toBe(true)
  })
})
