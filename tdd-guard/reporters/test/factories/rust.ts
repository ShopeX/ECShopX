// test/factories/rust.ts - Update required for the simplified Rust reporter

import { spawnSync, execFileSync } from 'node:child_process'
import { join } from 'node:path'
import { existsSync, writeFileSync, copyFileSync } from 'node:fs'
import type { ReporterConfig, TestScenarios } from '../types'
import { copyTestArtifacts } from './helpers'

export function createRustReporter(): ReporterConfig {
  // Use hardcoded absolute path for security when available, fall back to PATH for CI environments
  const rustBinary = existsSync('/usr/local/bin/cargo')
    ? '/usr/local/bin/cargo'
    : 'cargo'
  const artifactDir = 'rust'
  const testScenarios = {
    singlePassing: 'passing',
    singleFailing: 'failing',
    singleImportError: 'import',
  }

  return {
    name: 'RustReporter',
    testScenarios,
    run: (tempDir, scenario: keyof TestScenarios) => {
      // Copy the test module directory to temp
      copyTestArtifacts(artifactDir, testScenarios, scenario, tempDir)

      // Clean any cached build artifacts
      spawnSync(rustBinary, ['clean'], {
        cwd: tempDir,
        stdio: 'pipe',
      })

      const reporterPath = join(__dirname, '../../rust')
      const binaryPath = join(tempDir, 'tdd-guard-rust')

      // Build the reporter binary
      const buildResult = spawnSync(rustBinary, ['build', '--release'], {
        cwd: reporterPath,
        stdio: 'pipe',
      })
      if (buildResult.status !== 0) {
        throw new Error(`Failed to build rust reporter: ${buildResult.stderr}`)
      }

      // Copy the binary to temp dir using Node.js fs (portable, no shell dependency)
      const sourcePath = join(reporterPath, 'target/release/tdd-guard-rust')
      copyFileSync(sourcePath, binaryPath)

      // Check if nextest is available
      const hasNextest =
        spawnSync(rustBinary, ['nextest', '--version'], {
          stdio: 'pipe',
        }).status === 0

      // Run cargo test with JSON output (best effort)
      let cargoTestResult

      if (hasNextest) {
        // Try nextest first
        cargoTestResult = spawnSync(
          rustBinary,
          [
            'nextest',
            'run',
            '--message-format',
            'libtest-json',
            '--no-fail-fast',
          ],
          {
            cwd: tempDir,
            stdio: 'pipe',
            encoding: 'utf8',
            env: { ...process.env, NEXTEST_EXPERIMENTAL_LIBTEST_JSON: '1' },
          }
        )
      }

      // Fall back to cargo test if nextest not available
      if (!hasNextest || cargoTestResult?.status === 127) {
        cargoTestResult = spawnSync(
          rustBinary,
          [
            '+nightly',
            'test',
            '--no-fail-fast',
            '--',
            '-Z',
            'unstable-options',
            '--format',
            'json',
          ],
          {
            cwd: tempDir,
            stdio: 'pipe',
            encoding: 'utf8',
          }
        )
      }

      // For import error scenario, capture compilation errors
      if (scenario === 'singleImportError') {
        const buildTestResult = spawnSync(rustBinary, ['build', '--tests'], {
          cwd: tempDir,
          stdio: 'pipe',
          encoding: 'utf8',
        })

        // Combine build stderr with any test output
        const combinedOutput = `${buildTestResult.stderr}\n${cargoTestResult?.stdout}${cargoTestResult?.stderr}`

        // Write debug output
        const debugFile = join(tempDir, 'debug-output.txt')
        writeFileSync(debugFile, combinedOutput)

        // Process with reporter in passthrough mode
        try {
          execFileSync(
            binaryPath,
            ['--project-root', tempDir, '--passthrough'],
            {
              cwd: tempDir,
              input: combinedOutput,
              stdio: 'pipe',
              encoding: 'utf8',
            }
          )
        } catch (error) {
          // Reporter should handle errors gracefully
          console.debug('Reporter processing compilation error:', error)
        }

        return buildTestResult
      }

      // For normal test scenarios, process output
      const testOutput = `${cargoTestResult.stdout}${cargoTestResult.stderr}`

      // Write debug output
      const debugFile = join(tempDir, 'debug-output.txt')
      writeFileSync(debugFile, testOutput)

      // Run reporter in passthrough mode
      try {
        execFileSync(binaryPath, ['--project-root', tempDir, '--passthrough'], {
          cwd: tempDir,
          input: testOutput,
          stdio: 'pipe',
          encoding: 'utf8',
        })
      } catch (error) {
        console.debug('Reporter error:', error)
      }

      return cargoTestResult
    },
  }
}
