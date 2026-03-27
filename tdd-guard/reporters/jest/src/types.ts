import type { Storage } from 'tdd-guard'

export interface TDDGuardReporterOptions {
  storage?: Storage
  projectRoot?: string
}

export interface CapturedError {
  message: string
  actual?: string
  expected?: string
  showDiff?: boolean
  operator?: string
  diff?: string
  name?: string
  ok?: boolean
  stack?: string
}

export interface CapturedTest {
  name: string
  fullName: string
  state: string
  errors?: CapturedError[]
}

export interface CapturedModule {
  moduleId: string
  tests: CapturedTest[]
}

export interface CapturedUnhandledError {
  message: string
  name: string
  stack?: string
}

export interface CapturedTestRun {
  testModules: CapturedModule[]
  unhandledErrors?: CapturedUnhandledError[]
  reason?: 'passed' | 'failed' | 'interrupted'
}
