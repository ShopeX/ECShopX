export interface TestScenarios {
  singlePassing: string
  singleFailing: string
  singleImportError: string
}

export interface ReporterConfig {
  name: string
  testScenarios: TestScenarios
  run: (tempDir: string, scenario: keyof TestScenarios) => void | Promise<void>
}

export interface TestResultData {
  testModules: Array<{
    moduleId: string
    tests: Array<{
      name: string
      fullName: string
      state: string
      errors?: Array<{
        message: string
        expected?: string
        actual?: string
      }>
    }>
  }>
  reason: string
}
