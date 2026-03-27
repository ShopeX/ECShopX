import { spawnSync } from 'node:child_process'
import { writeFileSync } from 'node:fs'
import { join } from 'node:path'
import type { ReporterConfig, TestScenarios } from '../types'
import { copyTestArtifacts } from './helpers'

export function createPytestReporter(): ReporterConfig {
  const artifactDir = 'pytest'
  const testScenarios = {
    singlePassing: 'test_single_passing.py',
    singleFailing: 'test_single_failing.py',
    singleImportError: 'test_single_import_error.py',
  }

  return {
    name: 'PytestReporter',
    testScenarios,
    run: (tempDir, scenario: keyof TestScenarios) => {
      // Copy test file
      copyTestArtifacts(artifactDir, testScenarios, scenario, tempDir)

      // Write pytest config
      writeFileSync(join(tempDir, 'pytest.ini'), createPytestConfig(tempDir))

      // Run pytest
      const pytestPath = join(__dirname, '../../pytest/.venv/bin/pytest')
      const testFile = testScenarios[scenario]
      spawnSync(pytestPath, [testFile, '-c', 'pytest.ini'], {
        cwd: tempDir,
        stdio: 'pipe',
        encoding: 'utf8',
      })
    },
  }
}

function createPytestConfig(tempDir: string): string {
  return `[pytest]
tdd_guard_project_root = ${tempDir}
`
}
