import { spawn, spawnSync } from 'node:child_process'
import { writeFileSync, mkdirSync, symlinkSync } from 'node:fs'
import { join } from 'node:path'
import getPort from 'get-port'
import type { ReporterConfig, TestScenarios } from '../types'
import { copyTestArtifacts, getReporterPath } from './helpers'

export function createStorybookReporter(): ReporterConfig {
  const artifactDir = 'storybook'
  const testScenarios = {
    singlePassing: 'single-passing.stories.js',
    singleFailing: 'single-failing.stories.js',
    singleImportError: 'single-import-error.stories.js',
  }

  return {
    name: 'StorybookReporter',
    testScenarios,
    run: async (tempDir, scenario: keyof TestScenarios) => {
      // Get an available port to avoid conflicts when running tests in parallel
      const port = await getPort()
      // Copy Calculator.js (needed by all scenarios)
      copyTestArtifacts(
        artifactDir,
        { common: 'Calculator.js' },
        'common',
        tempDir
      )

      // Copy the specific test scenario story file
      copyTestArtifacts(artifactDir, testScenarios, scenario, tempDir)

      // Create .storybook directory and config
      const storybookDir = join(tempDir, '.storybook')
      mkdirSync(storybookDir, { recursive: true })
      writeFileSync(join(storybookDir, 'main.js'), createStorybookConfig())

      // Note: We don't need test-runner hooks since we're using Jest reporter to capture results

      // Write test-runner config
      writeFileSync(
        join(tempDir, 'test-runner-jest.config.js'),
        createTestRunnerConfig(tempDir)
      )

      // Create minimal package.json
      writeFileSync(
        join(tempDir, 'package.json'),
        JSON.stringify({ name: 'storybook-test', type: 'module' })
      )

      // Create symlink to root node_modules so Vite can resolve dependencies
      const rootNodeModules = join(__dirname, '../../../node_modules')
      const tempNodeModules = join(tempDir, 'node_modules')
      symlinkSync(rootNodeModules, tempNodeModules, 'dir')

      // Ensure cache directory exists and is writable
      const cacheDir = join(tempDir, '.storybook-cache')
      mkdirSync(cacheDir, { recursive: true })

      // Start Storybook dev server from root node_modules (hoisted from workspace)
      const storybookBinPath = join(
        __dirname,
        '../../../node_modules/.bin/storybook'
      )

      const storybookProcess = spawn(
        storybookBinPath,
        ['dev', '--config-dir', '.storybook', '--port', String(port), '--ci'],
        {
          cwd: tempDir,
          env: {
            ...process.env,
            NODE_ENV: 'development',
            PATH: '/usr/local/bin:/usr/bin:/bin',
            // Use custom cache directory to avoid permission issues with symlinked node_modules
            STORYBOOK_CACHE_DIR: join(tempDir, '.storybook-cache'),
          },
          stdio: 'pipe',
        }
      )

      // Wait for Storybook to be ready
      const waitForStorybook = new Promise<void>((resolve, reject) => {
        const timeout = setTimeout(() => {
          storybookProcess.kill()
          reject(new Error('Storybook dev server timed out'))
        }, 60000)

        storybookProcess.stdout!.on('data', (data) => {
          const output = data.toString()
          if (
            output.includes('Local:') ||
            output.includes(`http://localhost:${port}`)
          ) {
            clearTimeout(timeout)
            resolve()
          }
        })

        storybookProcess.stderr!.on('data', () => {
          // Stderr is captured but not logged during normal operation
        })

        storybookProcess.on('error', (err) => {
          clearTimeout(timeout)
          reject(err)
        })

        storybookProcess.on('exit', (code, signal) => {
          clearTimeout(timeout)
          reject(
            new Error(
              `Storybook process exited early with code ${code}, signal ${signal}`
            )
          )
        })
      })

      try {
        await waitForStorybook

        // Run Storybook test-runner
        const testRunnerPath =
          require.resolve('@storybook/test-runner/dist/test-storybook')
        spawnSync(
          process.execPath,
          [
            testRunnerPath,
            '--url',
            `http://localhost:${port}`,
            '--maxWorkers=1',
          ],
          {
            cwd: tempDir,
            env: {
              ...process.env,
              CI: 'true',
              NODE_ENV: 'test',
            },
            stdio: 'pipe',
          }
        )

        // Vitest captures test-runner output automatically
      } finally {
        // Kill Storybook dev server - use SIGKILL to ensure it dies
        storybookProcess.kill('SIGKILL')

        // Wait for process to actually exit to free up port 6006
        await new Promise<void>((resolve) => {
          storybookProcess.once('exit', () => resolve())
          // Fallback timeout in case exit event doesn't fire
          setTimeout(resolve, 1000)
        })
      }
    },
  }
}

function createStorybookConfig(): string {
  return `
module.exports = {
  stories: ['../*.stories.js'],
  framework: '@storybook/react-vite',
  core: {
    disableTelemetry: true,
  },
}
`
}

function createTestRunnerConfig(tempDir: string): string {
  const jestReporterPath = getReporterPath('jest/dist/index.js')
  return `
const { getJestConfig } = require('@storybook/test-runner');

module.exports = {
  // Extend Storybook's default Jest config
  ...getJestConfig(),
  // Set rootDir to temp directory so Jest finds the story files
  rootDir: '${tempDir}',
  // Use our Jest reporter to capture test results from Storybook test-runner
  reporters: [
    'default',  // Keep Jest's default console output
    ['${jestReporterPath}', { projectRoot: '${tempDir}' }]
  ],
  testEnvironmentOptions: {
    'jest-playwright': {
      browsers: ['chromium'],
      launchOptions: {
        headless: true,
      },
    },
  },
}
`
}
