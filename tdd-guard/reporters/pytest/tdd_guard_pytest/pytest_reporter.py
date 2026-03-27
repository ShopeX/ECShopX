"""
TDD Guard pytest plugin - captures test results for TDD validation.

This plugin automatically activates when tdd-guard-pytest is installed.
No configuration needed - it registers via the pytest11 entry point.
"""

import json
import os
from pathlib import Path
import pytest


# Default storage directory relative to project root
DEFAULT_DATA_DIR = Path('.claude/tdd-guard/data')


class TDDGuardPytestPlugin:
    """Pytest plugin that captures test results for TDD Guard"""
    
    def __init__(self, config=None, cwd=None):
        self.test_results = []
        self.storage_dir = self._determine_storage_dir(config, cwd)
    
    def _determine_storage_dir(self, config, cwd):
        """Determine the storage directory based on config and current working directory."""
        current_dir = cwd if cwd is not None else Path.cwd()
        
        # Try to get project root from config
        project_root = self._get_project_root_from_config(config)
        if not project_root:
            return DEFAULT_DATA_DIR
        
        # Validate that it's an absolute path
        if not os.path.isabs(project_root):
            return DEFAULT_DATA_DIR
        
        # Validate that cwd is within project root
        if self._is_cwd_within_project_root(current_dir, project_root):
            return Path(project_root) / DEFAULT_DATA_DIR
        else:
            return DEFAULT_DATA_DIR
    
    def _get_project_root_from_config(self, config):
        """Extract project root from config if available."""
        if config and hasattr(config, 'getini'):
            return config.getini('tdd_guard_project_root')
        return None
    
    def _is_cwd_within_project_root(self, cwd, project_root):
        """Check if current working directory is within the project root."""
        try:
            cwd.relative_to(Path(project_root))
            return True
        except ValueError:
            return False
    
    def pytest_collectreport(self, report):
        """Capture collection errors (import failures, etc.)"""
        if report.failed:
            # Create a synthetic test result for collection failures
            test_result = {
                'name': f"collection_error_{report.nodeid or 'unknown'}",
                'fullName': report.nodeid or 'collection_error',
                'state': 'failed',
                'errors': [{'message': str(report.longrepr)}]
            }
            self.test_results.append(test_result)
    
    def pytest_runtest_logreport(self, report):
        """Collect test results during pytest execution."""
        if report.when == 'call':  # Only capture the main test execution phase
            test_result = {
                'name': report.nodeid.split('::')[-1] if '::' in report.nodeid else report.nodeid,
                'fullName': report.nodeid,
                'state': 'passed' if report.passed else ('failed' if report.failed else 'skipped')
            }
            
            if report.failed and hasattr(report, 'longrepr') and report.longrepr:
                test_result['errors'] = [{'message': str(report.longrepr)}]
            
            self.test_results.append(test_result)
    
    def pytest_sessionfinish(self, session, exitstatus):
        """Save results to TDD Guard storage - equivalent to VitestReporter.onTestRunEnd()"""
        # Group tests by module (same format as VitestReporter)
        modules_map = {}
        for test in self.test_results:
            module_path = test['fullName'].split('::')[0]
            if module_path not in modules_map:
                modules_map[module_path] = {
                    'moduleId': module_path,
                    'tests': []
                }
            modules_map[module_path]['tests'].append(test)
        
        output = {
            'testModules': list(modules_map.values())
        }
        
        # Save to TDD Guard storage (same as VitestReporter.storage.saveTest())
        # Create directory recursively like FileStorage.ensureDirectory()
        self.storage_dir.mkdir(parents=True, exist_ok=True)
        
        # Save as JSON (matching FileStorage.saveTest format)
        storage_path = self.storage_dir / 'test.json'
        with open(storage_path, 'w') as f:
            json.dump(output, f, indent=2)


# Stash key for storing our plugin instance
tdd_guard_stash_key = pytest.StashKey[TDDGuardPytestPlugin]()


def pytest_addoption(parser):
    """Register configuration options"""
    parser.addini(
        "tdd_guard_project_root",
        help="Absolute path to project root for TDD Guard storage",
        default=""
    )

def pytest_configure(config):
    """Register and configure the plugin"""
    plugin = TDDGuardPytestPlugin(config)
    config.stash[tdd_guard_stash_key] = plugin
    config.pluginmanager.register(plugin)


def pytest_unconfigure(config):
    """Unregister the plugin"""
    plugin = config.stash.get(tdd_guard_stash_key, None)
    if plugin is not None:
        config.pluginmanager.unregister(plugin)
