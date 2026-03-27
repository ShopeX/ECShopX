import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { Storage } from './Storage'
import { MemoryStorage } from './MemoryStorage'
import { FileStorage } from './FileStorage'
import { Config } from '../config/Config'
import fs from 'fs/promises'
import path from 'path'
import os from 'os'

// Test content constants
const FIRST_CONTENT = 'first content'
const SECOND_CONTENT = 'second content'

describe.each(getStorageImplementations())('%s', (_name, setupStorage) => {
  let storage: Storage
  let cleanup: (() => Promise<void>) | undefined

  beforeEach(async () => {
    const setup = await setupStorage()
    storage = setup.storage
    cleanup = setup.cleanup
  })

  afterEach(async () => {
    if (cleanup) {
      await cleanup()
    }
  })

  describe('saveTest and getTest', () => {
    it('should store content that can be retrieved', async () => {
      const content = 'test content'

      await storage.saveTest(content)
      expect(await storage.getTest()).toBe(content)
    })
  })

  describe('saveTodo and getTodo', () => {
    it('should store content that can be retrieved', async () => {
      const content = 'todo content'

      await storage.saveTodo(content)
      expect(await storage.getTodo()).toBe(content)
    })
  })

  describe('saveModifications and getModifications', () => {
    it('should store content that can be retrieved', async () => {
      const content = 'modifications content'

      await storage.saveModifications(content)
      expect(await storage.getModifications()).toBe(content)
    })
  })

  describe('saveLint and getLint', () => {
    it('should store content that can be retrieved', async () => {
      const content = 'lint content'

      await storage.saveLint(content)
      expect(await storage.getLint()).toBe(content)
    })
  })

  describe('saveConfig and getConfig', () => {
    it('should store content that can be retrieved', async () => {
      const content = 'config content'

      await storage.saveConfig(content)
      expect(await storage.getConfig()).toBe(content)
    })
  })

  describe('saveInstructions and getInstructions', () => {
    it('should store content that can be retrieved', async () => {
      const content = 'instructions content'

      await storage.saveInstructions(content)
      expect(await storage.getInstructions()).toBe(content)
    })
  })

  describe('get methods when no data exists', () => {
    it('should return null when no test data exists', async () => {
      expect(await storage.getTest()).toBeNull()
    })

    it('should return null when no todo data exists', async () => {
      expect(await storage.getTodo()).toBeNull()
    })

    it('should return null when no modifications data exists', async () => {
      expect(await storage.getModifications()).toBeNull()
    })

    it('should return null when no lint data exists', async () => {
      expect(await storage.getLint()).toBeNull()
    })

    it('should return null when no config data exists', async () => {
      expect(await storage.getConfig()).toBeNull()
    })

    it('should return null when no instructions exist', async () => {
      expect(await storage.getInstructions()).toBeNull()
    })
  })

  describe('save methods overwrite existing content', () => {
    beforeEach(async () => {
      await storage.saveTest(FIRST_CONTENT)
      await storage.saveTodo(FIRST_CONTENT)
      await storage.saveModifications(FIRST_CONTENT)
      await storage.saveLint(FIRST_CONTENT)
      await storage.saveConfig(FIRST_CONTENT)
      await storage.saveInstructions(FIRST_CONTENT)
      await storage.saveTest(SECOND_CONTENT)
      await storage.saveTodo(SECOND_CONTENT)
      await storage.saveModifications(SECOND_CONTENT)
      await storage.saveLint(SECOND_CONTENT)
      await storage.saveConfig(SECOND_CONTENT)
      await storage.saveInstructions(SECOND_CONTENT)
    })

    it('should overwrite existing test content', async () => {
      expect(await storage.getTest()).toBe(SECOND_CONTENT)
    })

    it('should overwrite existing todo content', async () => {
      expect(await storage.getTodo()).toBe(SECOND_CONTENT)
    })

    it('should overwrite existing modifications content', async () => {
      expect(await storage.getModifications()).toBe(SECOND_CONTENT)
    })

    it('should overwrite existing lint content', async () => {
      expect(await storage.getLint()).toBe(SECOND_CONTENT)
    })

    it('should overwrite existing config content', async () => {
      expect(await storage.getConfig()).toBe(SECOND_CONTENT)
    })

    it('should overwrite existing instructions content', async () => {
      expect(await storage.getInstructions()).toBe(SECOND_CONTENT)
    })
  })

  describe('clearTransientData', () => {
    it('should clear test data', async () => {
      await storage.saveTest('test content')
      await storage.clearTransientData()

      expect(await storage.getTest()).toBeNull()
    })

    it('should clear todo data', async () => {
      await storage.saveTodo('todo content')
      await storage.clearTransientData()

      expect(await storage.getTodo()).toBeNull()
    })

    it('should clear modifications data', async () => {
      await storage.saveModifications('modifications content')
      await storage.clearTransientData()

      expect(await storage.getModifications()).toBeNull()
    })

    it('should clear lint data', async () => {
      await storage.saveLint('lint content')
      await storage.clearTransientData()

      expect(await storage.getLint()).toBeNull()
    })

    it('should NOT clear config data', async () => {
      await storage.saveConfig('config content')
      await storage.clearTransientData()

      expect(await storage.getConfig()).toBe('config content')
    })

    it('should NOT clear instructions data', async () => {
      await storage.saveInstructions('instructions content')
      await storage.clearTransientData()

      expect(await storage.getInstructions()).toBe('instructions content')
    })
  })
})

function getStorageImplementations(): Array<
  [string, () => Promise<{ storage: Storage; cleanup?: () => Promise<void> }>]
> {
  return [
    [
      'MemoryStorage',
      async (): Promise<{
        storage: Storage
        cleanup?: () => Promise<void>
      }> => ({
        storage: new MemoryStorage(),
      }),
    ],
    [
      'FileStorage',
      async (): Promise<{
        storage: Storage
        cleanup?: () => Promise<void>
      }> => {
        const projectRoot = await fs.mkdtemp(
          path.join(os.tmpdir(), 'storage-test-')
        )
        return {
          storage: new FileStorage(new Config({ projectRoot })),
          cleanup: async (): Promise<void> => {
            await fs.rm(projectRoot, { recursive: true, force: true })
          },
        }
      },
    ],
  ]
}
