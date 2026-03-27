import { spawnSync } from 'node:child_process'
import { writeFileSync } from 'node:fs'
import { join } from 'node:path'
import type { ReporterConfig, TestScenarios } from '../types'
import { copyTestArtifacts, getReporterPath } from './helpers'

export function createVitestReporter(): ReporterConfig {
  const artifactDir = 'vitest'
  const testScenarios = {
    singlePassing: 'single-passing.test.js',
    singleFailing: 'single-failing.test.js',
    singleImportError: 'single-import-error.test.js',
  }

  return {
    name: 'VitestReporter',
    testScenarios,
    run: (tempDir, scenario: keyof TestScenarios) => {
      // Copy test file
      copyTestArtifacts(artifactDir, testScenarios, scenario, tempDir)

      // Write Vitest config
      writeFileSync(
        join(tempDir, 'vitest.config.js'),
        createVitestConfig(tempDir)
      )

      // Run Vitest
      const vitestPath = require.resolve('vitest/vitest.mjs')
      spawnSync(process.execPath, [vitestPath, 'run', '--no-coverage'], {
        cwd: tempDir,
        env: { ...process.env, CI: 'true' },
        stdio: 'pipe',
      })
    },
  }
}

function createVitestConfig(tempDir: string): string {
  const reporterPath = getReporterPath('vitest/dist/index.js')
  return `
export default {
  test: {
    reporters: [
      'default',
      ['${reporterPath}', '${tempDir}']
    ]
  }
};
`
}
