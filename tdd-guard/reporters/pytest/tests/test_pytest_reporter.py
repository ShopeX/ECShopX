"""Tests for TDD Guard pytest reporter."""

import json
from unittest.mock import Mock, patch, mock_open

from tdd_guard_pytest.pytest_reporter import TDDGuardPytestPlugin, DEFAULT_DATA_DIR


class TestTDDGuardPytestPlugin:
    """Test the TDD Guard pytest plugin."""

    def test_init_creates_empty_test_results(self):
        """Plugin should initialize with empty test results."""
        plugin = TDDGuardPytestPlugin()
        assert plugin.test_results == []
        assert plugin.storage_dir == DEFAULT_DATA_DIR

    def test_pytest_runtest_logreport_captures_passed_test(self):
        """Should capture passed test results."""
        plugin = TDDGuardPytestPlugin()
        report = Mock()
        report.when = 'call'
        report.nodeid = 'test_module.py::test_example'
        report.passed = True
        report.failed = False
        
        plugin.pytest_runtest_logreport(report)
        
        assert len(plugin.test_results) == 1
        assert plugin.test_results[0] == {
            'name': 'test_example',
            'fullName': 'test_module.py::test_example',
            'state': 'passed'
        }

    def test_pytest_runtest_logreport_captures_failed_test(self):
        """Should capture failed test results with error message."""
        plugin = TDDGuardPytestPlugin()
        report = Mock()
        report.when = 'call'
        report.nodeid = 'test_module.py::TestClass::test_method'
        report.passed = False
        report.failed = True
        report.longrepr = 'AssertionError: Expected True'
        
        plugin.pytest_runtest_logreport(report)
        
        assert len(plugin.test_results) == 1
        assert plugin.test_results[0] == {
            'name': 'test_method',
            'fullName': 'test_module.py::TestClass::test_method',
            'state': 'failed',
            'errors': [{'message': 'AssertionError: Expected True'}]
        }

    def test_pytest_runtest_logreport_ignores_non_call_phase(self):
        """Should only capture 'call' phase, not setup or teardown."""
        plugin = TDDGuardPytestPlugin()
        report = Mock()
        report.when = 'setup'
        report.nodeid = 'test_module.py::test_example'
        
        plugin.pytest_runtest_logreport(report)
        
        assert len(plugin.test_results) == 0

    def test_pytest_collectreport_captures_collection_errors(self):
        """Should capture import/collection failures."""
        plugin = TDDGuardPytestPlugin()
        report = Mock()
        report.failed = True
        report.nodeid = 'test_broken.py'
        report.longrepr = 'ImportError: No module named missing_module'
        
        plugin.pytest_collectreport(report)
        
        assert len(plugin.test_results) == 1
        assert plugin.test_results[0] == {
            'name': 'collection_error_test_broken.py',
            'fullName': 'test_broken.py',
            'state': 'failed',
            'errors': [{'message': 'ImportError: No module named missing_module'}]
        }

    @patch('builtins.open', new_callable=mock_open)
    @patch('pathlib.Path.mkdir')
    def test_pytest_sessionfinish_saves_results(self, mock_mkdir, mock_file):
        """Should save test results to JSON file in correct format."""
        plugin = TDDGuardPytestPlugin()
        
        # Add some test results
        plugin.test_results = [
            {
                'name': 'test_one',
                'fullName': 'test_module.py::test_one',
                'state': 'passed'
            },
            {
                'name': 'test_two',
                'fullName': 'test_module.py::test_two',
                'state': 'failed',
                'errors': [{'message': 'Error'}]
            },
            {
                'name': 'test_other',
                'fullName': 'other_module.py::test_other',
                'state': 'passed'
            }
        ]
        
        session = Mock()
        plugin.pytest_sessionfinish(session, 0)
        
        # Verify directory was created
        mock_mkdir.assert_called_once_with(parents=True, exist_ok=True)
        
        # Verify file was opened for writing
        mock_file.assert_called_once_with(DEFAULT_DATA_DIR / 'test.json', 'w')
        
        # Get the data that was written
        written_data = ''.join(call.args[0] for call in mock_file().write.call_args_list)
        parsed_data = json.loads(written_data)
        
        # Verify the structure matches what TDD Guard expects
        assert 'testModules' in parsed_data
        assert len(parsed_data['testModules']) == 2
        
        # Check first module
        module1 = next(m for m in parsed_data['testModules'] if m['moduleId'] == 'test_module.py')
        assert len(module1['tests']) == 2
        assert module1['tests'][0]['name'] == 'test_one'
        assert module1['tests'][1]['name'] == 'test_two'
        
        # Check second module
        module2 = next(m for m in parsed_data['testModules'] if m['moduleId'] == 'other_module.py')
        assert len(module2['tests']) == 1
        assert module2['tests'][0]['name'] == 'test_other'