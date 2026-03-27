import { Linter } from '../linters/Linter'
import { Config } from '../config/Config'
import { ESLint } from '../linters/eslint/ESLint'
import { GolangciLint } from '../linters/golangci/GolangciLint'

export class LinterProvider {
  getLinter(config?: Config): Linter | null {
    const actualConfig = config ?? new Config()

    switch (actualConfig.linterType) {
      case 'eslint':
        return new ESLint()
      case 'golangci-lint':
        return new GolangciLint()
      case undefined:
      default:
        return null
    }
  }
}
