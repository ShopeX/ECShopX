import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { FileStorage } from './FileStorage'
import { Config, DEFAULT_DATA_DIR } from '../config/Config'
import fs from 'fs/promises'
import path from 'path'
import os from 'os'

describe('FileStorage', () => {
  let projectRoot: string
  let config: Config
  let storage: FileStorage

  beforeEach(async () => {
    projectRoot = await fs.mkdtemp(path.join(os.tmpdir(), 'filestorage-test-'))
    config = new Config({ projectRoot })
    storage = new FileStorage(config)
  })

  afterEach(async () => {
    await fs.rm(projectRoot, { recursive: true, force: true })
  })

  describe('constructor', () => {
    it('initializes with default configuration when instantiated without parameters', () => {
      const defaultStorage = new FileStorage()

      expect(defaultStorage).toBeDefined()
      expect(defaultStorage).toBeInstanceOf(FileStorage)
    })

    it('should accept a Config instance', async () => {
      await storage.saveTest('test content')
      const retrieved = await storage.getTest()
      expect(retrieved).toBe('test content')
    })
  })

  it('creates directory if it does not exist', async () => {
    const newProjectRoot = path.join(projectRoot, 'new-project')
    const nonExistentPath = path.join(
      newProjectRoot,
      ...DEFAULT_DATA_DIR.split('/')
    )
    const customConfig = new Config({ projectRoot: newProjectRoot })
    const customStorage = new FileStorage(customConfig)

    await expect(fs.access(nonExistentPath)).rejects.toThrow()

    await customStorage.saveTest('content')

    await expect(fs.access(nonExistentPath)).resolves.toBeUndefined()
  })

  describe('save and get operations', () => {
    it('saves and retrieves test content', async () => {
      await storage.saveTest('test content')

      const retrieved = await storage.getTest()
      expect(retrieved).toBe('test content')
    })

    it('saves and retrieves todo content', async () => {
      await storage.saveTodo('todo content')

      const retrieved = await storage.getTodo()
      expect(retrieved).toBe('todo content')
    })

    it('saves and retrieves modifications content', async () => {
      await storage.saveModifications('modifications content')

      const retrieved = await storage.getModifications()
      expect(retrieved).toBe('modifications content')
    })
  })
})
