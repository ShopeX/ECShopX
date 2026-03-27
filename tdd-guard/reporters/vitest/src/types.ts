import type {
  TestState,
  TestRunEndReason,
  TestModule,
  TestCase,
} from 'vitest/node'
import type { SerializedError } from '@vitest/utils'

export type ModuleDataMap = Map<string, CollectedModuleData>

export type CollectedModuleData = {
  module: TestModule
  tests: TestCase[]
}

export type FormattedError = {
  message: string
  stack?: string
  expected?: unknown
  actual?: unknown
}

export type FormattedTest = {
  name: string
  fullName: string
  state: TestState
  errors?: FormattedError[]
}

export type ModuleResult = {
  moduleId: string
  tests: FormattedTest[]
}

export type TestRunOutput = {
  testModules: ModuleResult[]
  unhandledErrors: readonly SerializedError[]
  reason?: TestRunEndReason
}
