import { z } from 'zod'
import { TestModuleSchema } from './reporterSchemas'

// Pytest uses same structure as Vitest
export const PytestResultSchema = z.object({
  testModules: z.array(TestModuleSchema),
})
