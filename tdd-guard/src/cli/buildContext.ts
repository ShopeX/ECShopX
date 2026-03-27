import { Storage } from '../storage/Storage'
import { LintDataSchema } from '../contracts/schemas/lintSchemas'
import { Context } from '../contracts/types/Context'
import { processLintData } from '../processors/lintProcessor'

export async function buildContext(storage: Storage): Promise<Context> {
  const [modifications, rawTest, todo, lint, instructions] = await Promise.all([
    storage.getModifications(),
    storage.getTest(),
    storage.getTodo(),
    storage.getLint(),
    storage.getInstructions(),
  ])

  let processedLintData
  try {
    if (lint) {
      const rawLintData = LintDataSchema.parse(JSON.parse(lint))
      processedLintData = processLintData(rawLintData)
    } else {
      processedLintData = processLintData()
    }
  } catch {
    processedLintData = processLintData()
  }

  return {
    modifications: formatModifications(modifications ?? ''),
    test: rawTest ?? '',
    todo: todo ?? '',
    lint: processedLintData,
    instructions: instructions ?? undefined,
  }
}

function formatModifications(modifications: string): string {
  if (!modifications) {
    return ''
  }

  try {
    const parsed = JSON.parse(modifications)
    return JSON.stringify(parsed, null, 2)
  } catch {
    // If it's not valid JSON, leave it as is
    return modifications
  }
}
