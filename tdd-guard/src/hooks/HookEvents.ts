import {
  HookData,
  HookDataSchema,
  isTodoWriteOperation,
  ToolOperation,
  ToolOperationSchema,
} from '../contracts/schemas/toolSchemas'
import { Storage } from '../storage/Storage'

export type { HookData }

export class HookEvents {
  constructor(private readonly storage: Storage) {}

  async processEvent(event: unknown): Promise<void> {
    const hookResult = HookDataSchema.safeParse(event)
    if (!hookResult.success) return

    const operation = this.extractToolOperation(hookResult.data)
    if (!operation) return

    await this.persistOperation(operation)
  }

  private extractToolOperation(hook: HookData): ToolOperation | null {
    const result = ToolOperationSchema.safeParse(hook)
    return result.success ? result.data : null
  }

  private async persistOperation(operation: ToolOperation): Promise<void> {
    const content = JSON.stringify(operation, null, 2)
    
    if (isTodoWriteOperation(operation)) {
      await this.storage.saveTodo(content)
    } else {
      await this.storage.saveModifications(content)
    }
  }
}