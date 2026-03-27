import { spawnSync } from 'node:child_process'
import { symlinkSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import type { ReporterConfig, TestScenarios } from '../types'
import { copyTestArtifacts } from './helpers'

export function createPhpunitReporter(): ReporterConfig {
  const artifactDir = 'phpunit'
  const testScenarios = {
    singlePassing: 'SinglePassingTest.php',
    singleFailing: 'SingleFailingTest.php',
    singleImportError: 'SingleImportErrorTest.php',
  }

  return {
    name: 'PHPUnitReporter',
    testScenarios,
    run: (tempDir, scenario: keyof TestScenarios) => {
      // Copy test file to tests subdirectory
      copyTestArtifacts(artifactDir, testScenarios, scenario, tempDir, {
        targetSubdir: 'tests',
      })

      // Write PHPUnit config
      writeFileSync(join(tempDir, 'phpunit.xml'), createPhpunitConfig(tempDir))

      // Create symlink to vendor directory
      const reporterVendorPath = join(__dirname, '../../phpunit/vendor')
      const tempVendorPath = join(tempDir, 'vendor')
      symlinkSync(reporterVendorPath, tempVendorPath)

      // Run PHPUnit
      const phpunitPath = join(__dirname, '../../phpunit/vendor/bin/phpunit')
      spawnSync(phpunitPath, ['-c', 'phpunit.xml'], {
        cwd: tempDir,
        env: { ...process.env },
        stdio: 'pipe',
      })
    },
  }
}

function createPhpunitConfig(tempDir: string): string {
  return `<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Integration Tests">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    
    <extensions>
        <bootstrap class="TddGuard\\PHPUnit\\TddGuardExtension">
            <parameter name="projectRoot" value="${tempDir}"/>
        </bootstrap>
    </extensions>
</phpunit>
`
}
