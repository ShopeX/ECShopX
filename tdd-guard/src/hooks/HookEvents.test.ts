import { describe, test, expect, beforeEach } from 'vitest'
import { HookEvents } from './HookEvents'
import { MemoryStorage } from '../storage/MemoryStorage'
import { testData } from '@testUtils'

describe('HookEvents', () => {
  let sut: Awaited<ReturnType<typeof setupHookEvents>>

  beforeEach(async () => {
    sut = await setupHookEvents()
  })

  describe('Write operation', () => {
    const writeOp = testData.writeOperation()

    beforeEach(async () => {
      await sut.processEvent(writeOp)
    })

    test('stores expected data', async () => {
      const data = await sut.readModifications()
      expect(JSON.parse(data)).toStrictEqual(writeOp)
    })
  })

  describe('Edit operation', () => {
    const editOp = testData.editOperation()
    beforeEach(async () => {
      await sut.processEvent(editOp)
    })

    test('stores expected data', async () => {
      const data = await sut.readModifications()
      expect(JSON.parse(data)).toStrictEqual(editOp)
    })
  })

  describe('MultiEdit operation', () => {
    const multiEditOp = testData.multiEditOperation()
    beforeEach(async () => {
      await sut.processEvent(multiEditOp)
    })

    test('stores expected data', async () => {
      const data = await sut.readModifications()
      expect(JSON.parse(data)).toStrictEqual(multiEditOp)
    })
  })

  describe('TodoWrite operation', () => {
    const todoWriteOp = testData.todoWriteOperation()
    beforeEach(async () => {
      await sut.processEvent(todoWriteOp)
    })

    test('stores expected data', async () => {
      const data = await sut.readTodos()
      expect(JSON.parse(data)).toStrictEqual(todoWriteOp)
    })
  })

  describe('overwrite behavior', () => {
    test('overwrites previous content instead of appending', async () => {
      // First write
      await sut.processEvent(
        testData.writeOperation({ tool_input: { file_path: '/path', content: 'first content' }})
      )

      // Second write should overwrite
      await sut.processEvent(
        testData.writeOperation({ tool_input: { file_path: '/path', content: 'second content' }})
      )

      const logContent = await sut.readModifications()
      const parsed = JSON.parse(logContent)
      expect(parsed.tool_input.content).toStrictEqual('second content')
    })
  })

  // Test helper
  async function setupHookEvents(): Promise<{
    readModifications: () => Promise<string>
    readTodos: () => Promise<string>
    processEvent: (data: unknown) => Promise<void>
  }> {
    const storage = new MemoryStorage()
    const hookEvents = new HookEvents(storage)

    const readModifications = async (): Promise<string> => {
      const modifications = await storage.getModifications()
      if (modifications === null) throw new Error('No modifications content')
      return modifications
    }

    const readTodos = async (): Promise<string> => {
      const todo = await storage.getTodo()
      if (todo === null) throw new Error('No todo content')
      return todo
    }

    return {
      readModifications,
      readTodos,
      processEvent: (data: unknown): Promise<void> => hookEvents.processEvent(data),
    }
  }
})
