import { describe, test, expect, beforeEach } from 'vitest'
import { SessionHandler } from './sessionHandler'
import { MemoryStorage } from '../storage/MemoryStorage'
import { FileStorage } from '../storage/FileStorage'
import { testData } from '@testUtils'
import type { SessionStart } from '../contracts/schemas/toolSchemas'

describe('SessionHandler', () => {
  let storage: MemoryStorage
  let handler: SessionHandler

  beforeEach(() => {
    storage = new MemoryStorage()
    handler = new SessionHandler(storage)
  })

  describe('constructor', () => {
    test('accepts a Storage instance', () => {
      expect(handler['storage']).toBe(storage)
    })

    test('creates a default FileStorage if none provided', () => {
      const customHandler = new SessionHandler()

      expect(customHandler['storage']).toBeInstanceOf(FileStorage)
    })
  })

  describe('processSessionStart', () => {
    describe('transient data clearing', () => {
      beforeEach(async () => {
        await populateAllStorageTypes()
      })

      describe('when SessionStart event is received', () => {
      beforeEach(async () => {
        const sessionStartData = testData.sessionStart()
        await handler.processSessionStart(JSON.stringify(sessionStartData))
      })

      test('clears test data', async () => {
        expect(await storage.getTest()).toBeNull()
      })

      test('clears todo data', async () => {
        expect(await storage.getTodo()).toBeNull()
      })

      test('clears modifications data', async () => {
        expect(await storage.getModifications()).toBeNull()
      })

      test('clears lint data', async () => {
        expect(await storage.getLint()).toBeNull()
      })

      test('preserves config data', async () => {
        expect(await storage.getConfig()).toBe('config data')
      })
    })

    describe('when non-SessionStart event is received', () => {
      beforeEach(async () => {
        const nonSessionStartData = testData.editOperation()
        await handler.processSessionStart(JSON.stringify(nonSessionStartData))
      })

      test('preserves test data', async () => {
        expect(await storage.getTest()).toBe('test data')
      })

      test('preserves todo data', async () => {
        expect(await storage.getTodo()).toBe('todo data')
      })

      test('preserves modifications data', async () => {
        expect(await storage.getModifications()).toBe('modifications data')
      })

      test('preserves lint data', async () => {
        expect(await storage.getLint()).toBe('lint data')
      })

      test('preserves config data', async () => {
        expect(await storage.getConfig()).toBe('config data')
      })
    })
    })

    describe('custom instructions', () => {
      describe('when no instructions exist', () => {
        test.each(['startup', 'resume', 'clear'] as const)(
          'creates default instructions on %s event',
          async (source) => {
            const instructions = await startAndGetInstructions(source)

            expect(instructions).toContain('## TDD Fundamentals')
          }
        )
      })

      describe('when instructions already exist', () => {
        const customInstructions = '## My Custom TDD Rules'
        
        beforeEach(async () => {
          await storage.saveInstructions(customInstructions)
        })

        test.each(['startup', 'resume', 'clear'] as const)(
          'preserves existing instructions on %s event',
          async (source) => {
            const instructions = await startAndGetInstructions(source)
            
            expect(instructions).toBe(customInstructions)
            expect(instructions).not.toContain('## Rules')
          }
        )
      })
    })
  })

  // Test helpers
  async function populateAllStorageTypes() {
    await storage.saveTest('test data')
    await storage.saveTodo('todo data')
    await storage.saveModifications('modifications data')
    await storage.saveLint('lint data')
    await storage.saveConfig('config data')
  }

  async function startAndGetInstructions(source: SessionStart['source']): Promise<string | null> {
    const sessionData = testData.sessionStart({ source })
    await handler.processSessionStart(JSON.stringify(sessionData))
    return storage.getInstructions()
  }
})
