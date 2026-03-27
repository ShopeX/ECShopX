import { z } from 'zod'

export const TestErrorSchema = z.object({
  message: z.string(),
  stack: z.string().optional(),
})

export const UnhandledErrorSchema = z.object({
  name: z.string(),
  message: z.string(),
  stack: z.string().optional(),
})

export const TestSchema = z.object({
  name: z.string(),
  fullName: z.string(),
  state: z.enum(['passed', 'failed', 'skipped']),
  errors: z.array(TestErrorSchema).optional(),
})

export const TestModuleSchema = z.object({
  moduleId: z.string(),
  tests: z.array(TestSchema),
})

export const TestResultSchema = z.object({
  testModules: z.array(TestModuleSchema),
  unhandledErrors: z.array(UnhandledErrorSchema).optional(),
  reason: z.enum(['passed', 'failed', 'interrupted']).optional(),
})

export type TestError = z.infer<typeof TestErrorSchema>
export type UnhandledError = z.infer<typeof UnhandledErrorSchema>
export type Test = z.infer<typeof TestSchema>
export type TestModule = z.infer<typeof TestModuleSchema>
export type TestResult = z.infer<typeof TestResultSchema>

export function isTestModule(value: unknown): value is TestModule {
  return TestModuleSchema.safeParse(value).success
}

export function isTestCase(value: unknown): value is Test {
  return TestSchema.safeParse(value).success
}

export function isFailingTest(
  value: unknown
): value is Test & { state: 'failed' } {
  return isTestCase(value) && value.state === 'failed'
}

export function isPassingTest(
  value: unknown
): value is Test & { state: 'passed' } {
  return isTestCase(value) && value.state === 'passed'
}

export function isTestPassing(testResult: TestResult): boolean {
  // No tests means the test suite is not passing
  if (testResult.testModules.length === 0) {
    return false
  }

  // Check if any tests exist
  const hasTests = testResult.testModules.some(
    (module) => module.tests.length > 0
  )
  if (!hasTests) {
    return false
  }

  return testResult.testModules.every((module) =>
    module.tests.every((test) => test.state !== 'failed')
  )
}
