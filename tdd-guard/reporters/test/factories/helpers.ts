import { copyFileSync, mkdirSync, cpSync, statSync } from 'node:fs'
import { join } from 'node:path'
import type { TestScenarios } from '../types'

/**
 * Copy test artifacts from the artifacts directory to the temp directory
 * Handles both files and directories automatically
 */
export function copyTestArtifacts(
  artifactDir: string,
  testScenarios: TestScenarios,
  scenario: keyof TestScenarios,
  tempDir: string,
  options?: {
    targetSubdir?: string // For PHPUnit which needs files in 'tests/'
  }
): void {
  const artifactName = testScenarios[scenario]
  const sourcePath = join(__dirname, '../artifacts', artifactDir, artifactName)

  // Check if source is a directory or file
  const stats = statSync(sourcePath)

  if (stats.isDirectory()) {
    // Copy entire directory contents
    cpSync(sourcePath, tempDir, { recursive: true })
  } else {
    // Copy single file
    const targetDir = options?.targetSubdir
      ? join(tempDir, options.targetSubdir)
      : tempDir

    // Create target directory if needed
    if (options?.targetSubdir) {
      mkdirSync(targetDir, { recursive: true })
    }

    const destPath = join(targetDir, artifactName)
    copyFileSync(sourcePath, destPath)
  }
}

/**
 * Get the path to a reporter module
 */
export function getReporterPath(reporterModule: string): string {
  return join(__dirname, '../..', reporterModule).replace(/\\/g, '/')
}
