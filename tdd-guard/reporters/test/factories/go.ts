import { spawnSync, execFileSync } from 'node:child_process'
import { join } from 'node:path'
import { existsSync } from 'node:fs'
import type { ReporterConfig, TestScenarios } from '../types'
import { copyTestArtifacts } from './helpers'

export function createGoReporter(): ReporterConfig {
  // Use hardcoded absolute path for security when available, fall back to PATH for CI environments
  const goBinary = existsSync('/usr/local/go/bin/go') ? '/usr/local/go/bin/go' : 'go'
  const artifactDir = 'go'
  const testScenarios = {
    singlePassing: 'passing',
    singleFailing: 'failing',
    singleImportError: 'import',
  }

  return {
    name: 'GoReporter',
    testScenarios,
    run: (tempDir, scenario: keyof TestScenarios) => {
      // Copy the test module directory to temp
      copyTestArtifacts(artifactDir, testScenarios, scenario, tempDir)

      const reporterPath = join(__dirname, '../../go')
      const binaryPath = join(tempDir, 'tdd-guard-go')

      // Build the reporter binary, output to temp directory
      spawnSync(goBinary, ['build', '-o', binaryPath, './cmd/tdd-guard-go'], {
        cwd: reporterPath,
        stdio: 'pipe',
      })

      // Run go test with JSON output
      const goTestResult = spawnSync(goBinary, ['test', '-json', '.'], {
        cwd: tempDir,
        stdio: 'pipe',
        encoding: 'utf8',
      })
      
      // Combine stdout and stderr for processing
      const testOutput = (goTestResult.stdout || '') + (goTestResult.stderr || '')
      
      // Pipe test output to our reporter
      try {
        execFileSync(binaryPath, ['-project-root', tempDir], {
          cwd: tempDir,
          input: testOutput,
          stdio: 'pipe',
          encoding: 'utf8',
        })
      } catch (error) {
        // Return the error for test verification
        return error as ReturnType<typeof spawnSync>
      }
      
      return goTestResult
    },
  }
}
