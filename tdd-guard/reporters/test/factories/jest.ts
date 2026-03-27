import { spawnSync } from 'node:child_process'
import { writeFileSync } from 'node:fs'
import { join } from 'node:path'
import type { ReporterConfig, TestScenarios } from '../types'
import { copyTestArtifacts, getReporterPath } from './helpers'

export function createJestReporter(): ReporterConfig {
  const artifactDir = 'jest'
  const testScenarios = {
    singlePassing: 'single-passing.test.js',
    singleFailing: 'single-failing.test.js',
    singleImportError: 'single-import-error.test.js',
  }

  return {
    name: 'JestReporter',
    testScenarios,
    run: (tempDir, scenario: keyof TestScenarios) => {
      // Copy test file
      copyTestArtifacts(artifactDir, testScenarios, scenario, tempDir)

      // Write Jest config
      writeFileSync(join(tempDir, 'jest.config.js'), createJestConfig(tempDir))

      // Run Jest
      const jestCliPath = require.resolve('jest-cli/bin/jest')
      spawnSync(process.execPath, [jestCliPath, '--no-cache'], {
        cwd: tempDir,
        env: { ...process.env, CI: 'true' },
        stdio: 'pipe',
      })
    },
  }
}

function createJestConfig(tempDir: string): string {
  const reporterPath = getReporterPath('jest/dist/index.js')
  return `
const path = require('path');

module.exports = {
  testMatch: ['**/*.test.js'],
  reporters: [
    'default',
    ['${reporterPath}', {
      projectRoot: '${tempDir}'
    }]
  ]
};
`
}
