import { describe, test, expect } from 'vitest'
import { LinterProvider } from './LinterProvider'
import { Config } from '../config/Config'
import { ESLint } from '../linters/eslint/ESLint'
import { GolangciLint } from '../linters/golangci/GolangciLint'

describe('LinterProvider', () => {
  test('returns ESLint when config linterType is eslint', () => {
    const config = new Config({ linterType: 'eslint' })

    const provider = new LinterProvider()
    const linter = provider.getLinter(config)

    expect(linter).toBeInstanceOf(ESLint)
  })

  test('returns GolangciLint when config linterType is golangci-lint', () => {
    const config = new Config({ linterType: 'golangci-lint' })

    const provider = new LinterProvider()
    const linter = provider.getLinter(config)

    expect(linter).toBeInstanceOf(GolangciLint)
  })

  test('returns null when config linterType is explicitly undefined', () => {
    const config = new Config({ linterType: undefined })

    const provider = new LinterProvider()
    const linter = provider.getLinter(config)

    expect(linter).toBeNull()
  })

  test('returns null when config linterType is unknown value', () => {
    const config = new Config({ linterType: 'unknown-linter' })

    const provider = new LinterProvider()
    const linter = provider.getLinter(config)

    expect(linter).toBeNull()
  })
})
