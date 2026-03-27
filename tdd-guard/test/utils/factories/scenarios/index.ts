import * as typescriptScenarios from './languages/typescript'
import * as pythonScenarios from './languages/python'
import { LanguageScenario } from './types'

export { LanguageScenario } from './types'
export { expectDecision } from './utils'

export const typescript: LanguageScenario = {
  language: 'typescript',
  testFile: 'src/Calculator/Calculator.test.ts',
  implementationFile: 'src/Calculator/Calculator.ts',
  ...typescriptScenarios,
}

export const python: LanguageScenario = {
  language: 'python',
  testFile: 'src/calculator/test_calculator.py',
  implementationFile: 'src/calculator/calculator.py',
  ...pythonScenarios,
}

export const languages: LanguageScenario[] = [typescript, python]
