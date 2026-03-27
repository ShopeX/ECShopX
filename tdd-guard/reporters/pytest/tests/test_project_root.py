"""Integration tests for tdd_guard_project_root configuration.

Tests the pytest reporter's ability to save test results at a configured
project root directory, regardless of where pytest is executed from.
"""

import textwrap

import pytest

from tdd_guard_pytest.pytest_reporter import DEFAULT_DATA_DIR
from .helpers import (
    assert_json_file_exists,
    assert_json_file_not_exists,
    copy_project_and_update_root,
    create_multiple_test_files,
    create_project_structure,
    create_pytest_ini,
    create_simple_passing_test,
    create_test_file,
    extract_all_test_names,
    get_test_names_from_file,
    load_test_results,
    verify_test_results,
)


class TestProjectRootConfiguration:
    """Test suite for tdd_guard_project_root configuration feature."""

    def test_default_behavior_without_configuration(self, pytester):
        """When no project root is configured, saves to default relative path."""
        create_simple_passing_test(pytester)
        
        result = pytester.runpytest()
        result.assert_outcomes(passed=1)
        
        assert_json_file_exists(pytester.path)

    def test_relative_path_falls_back_to_default(self, pytester):
        """Relative paths in configuration are ignored, falling back to default."""
        create_pytest_ini(pytester, "./relative/path")
        create_simple_passing_test(pytester)
        
        result = pytester.runpytest()
        result.assert_outcomes(passed=1)
        
        # Should use default location, not the relative path
        assert_json_file_exists(pytester.path)

    def test_absolute_path_outside_current_directory(self, pytester, tmp_path):
        """Absolute path is rejected when current directory is not within it."""
        external_root = tmp_path / "external_project"
        external_root.mkdir()
        
        create_pytest_ini(pytester, external_root)
        create_simple_passing_test(pytester)
        
        result = pytester.runpytest()
        result.assert_outcomes(passed=1)
        
        # Should fall back to default location
        assert_json_file_exists(pytester.path)
        
        # Should NOT create file in the external root
        assert_json_file_not_exists(external_root)

    @pytest.mark.parametrize("config_file,content", [
        ("pyproject.toml", """
            [tool.pytest.ini_options]
            tdd_guard_project_root = "{root}"
        """),
        ("setup.cfg", """
            [tool:pytest]
            tdd_guard_project_root = {root}
        """),
        ("pytest.ini", """
            [pytest]
            tdd_guard_project_root = {root}
        """),
    ])
    def test_configuration_methods(self, pytester, tmp_path, config_file, content):
        """Project root can be configured via pyproject.toml, setup.cfg, or pytest.ini."""
        # Create external directory for testing fallback
        external_root = tmp_path / "external"
        external_root.mkdir()
        
        # Configure with external path (will fall back since cwd not within it)
        if config_file == "pyproject.toml":
            pytester.makefile(".toml", pyproject=content.format(root=external_root))
        else:
            config_content = textwrap.dedent(content.format(root=external_root)).strip()
            pytester.makeini(config_content)
        
        create_simple_passing_test(pytester)
        
        result = pytester.runpytest()
        result.assert_outcomes(passed=1)
        
        # Should use default since we're not within external_root
        assert_json_file_exists(pytester.path)

    def test_saves_to_project_root_from_subdirectory(self, pytester, tmp_path, monkeypatch):
        """When running from subdirectory, saves results at project root."""
        # Set up project with custom test content
        project_root = tmp_path / "my_project"
        structure = {
            "src": {"__init__.py": ""},
            "tests": {
                "unit": {
                    "test_user_service.py": """
def test_create_user():
    user = {"name": "Alice", "age": 30}
    assert user["name"] == "Alice"

def test_validate_age():
    age = 25
    assert age >= 18, "User must be an adult"
                    """.strip()
                }
            },
            "pytest.ini": f"""
[pytest]
tdd_guard_project_root = {project_root}
            """.strip()
        }
        
        project_root.mkdir()
        create_project_structure(project_root, structure)
        
        # Copy and update paths
        copied_project = copy_project_and_update_root(pytester, project_root)
        
        # Run pytest from subdirectory
        monkeypatch.chdir(copied_project / "tests" / "unit")
        result = pytester.runpytest("test_user_service.py")
        result.assert_outcomes(passed=2)
        
        # Verify file created at project root, not in subdirectory
        json_file = assert_json_file_exists(copied_project)
        assert_json_file_not_exists(copied_project / "tests" / "unit")
        
        # Verify JSON content
        verify_test_results(json_file, expected_tests=[
            ("test_create_user", "passed"),
            ("test_validate_age", "passed")
        ])

    def test_deeply_nested_directory_structure(self, pytester, tmp_path, monkeypatch):
        """Handles deeply nested directory structures correctly."""
        # Create complex project structure
        project_root = tmp_path / "complex_project"
        structure = {
            "test_integration.py": """
def test_system_integration():
    components = ["auth", "database", "api"]
    assert len(components) == 3
            """.strip(),
            "src": {
                "components": {
                    "widgets": {
                        "tests": {
                            "test_button_widget.py": """
def test_button_click():
    clicked = True
    assert clicked

def test_button_disabled_state():
    button = {"enabled": False}
    assert not button["enabled"]
                            """.strip()
                        }
                    }
                }
            },
            "pytest.ini": f"""
[pytest]
tdd_guard_project_root = {project_root}
            """.strip()
        }
        
        project_root.mkdir()
        create_project_structure(project_root, structure)
        
        # Copy and update paths
        copied_project = copy_project_and_update_root(pytester, project_root)
        
        # Run from deep directory
        monkeypatch.chdir(copied_project / "src" / "components" / "widgets" / "tests")
        result = pytester.runpytest(str(copied_project))
        result.assert_outcomes(passed=3)
        
        # Verify results saved at project root
        json_file = assert_json_file_exists(copied_project)
        
        # Verify all tests are captured
        all_test_names = get_test_names_from_file(json_file)
        assert "test_system_integration" in all_test_names
        assert "test_button_click" in all_test_names
        assert "test_button_disabled_state" in all_test_names

    def test_current_directory_as_project_root(self, pytester):
        """Tests using '.' (current directory) as project root."""
        create_pytest_ini(pytester, ".")
        create_simple_passing_test(pytester)
        
        result = pytester.runpytest()
        result.assert_outcomes(passed=1)
        
        # "." should be treated as relative path and fall back to default
        assert_json_file_exists(pytester.path)

    def test_json_output_structure(self, pytester):
        """Verifies the JSON output format matches expected structure."""
        create_test_file(pytester)  # Uses default pass/fail tests
        
        result = pytester.runpytest()
        result.assert_outcomes(passed=1, failed=1)
        
        json_file = assert_json_file_exists(pytester.path)
        data = load_test_results(json_file)
        
        # Verify structure
        assert "testModules" in data
        assert len(data["testModules"]) == 1
        
        module = data["testModules"][0]
        assert "moduleId" in module
        assert "tests" in module
        assert len(module["tests"]) == 2
        
        # Verify each test has required fields
        for test in module["tests"]:
            assert "name" in test
            assert "fullName" in test
            assert "state" in test
            assert test["state"] in ["passed", "failed"]
            
            if test["state"] == "failed":
                assert "errors" in test
                assert len(test["errors"]) > 0
                assert "message" in test["errors"][0]

    def test_multiple_test_modules(self, pytester):
        """Correctly handles multiple test modules in a single run."""
        # Create multiple test files
        create_multiple_test_files(pytester, {
            "test_auth": [
                ("test_login", 'len("abc123") > 0'),
                ("test_logout", 'not False')
            ],
            "test_database": [
                ("test_connection", 'True'),
                ("test_query_performance", '0.05 < 1.0')
            ]
        })
        
        result = pytester.runpytest()
        result.assert_outcomes(passed=4)
        
        json_file = pytester.path / DEFAULT_DATA_DIR / "test.json"
        data = load_test_results(json_file)
        
        # Should have 2 modules
        assert len(data["testModules"]) == 2
        
        # Verify all tests are present
        all_test_names = extract_all_test_names(data)
        assert len(all_test_names) == 4
        assert all(name in all_test_names for name in [
            "test_login", "test_logout", "test_connection", "test_query_performance"
        ])

    def test_clears_results_between_sessions(self, pytester):
        """Each test session should start fresh, not accumulate results."""
        # First test run
        create_simple_passing_test(pytester, "test_first_run")
        
        result = pytester.runpytest()
        result.assert_outcomes(passed=1)
        
        json_file = assert_json_file_exists(pytester.path)
        test_names = get_test_names_from_file(json_file)
        assert len(test_names) == 1
        
        # Second test run with different test
        create_simple_passing_test(pytester, "test_second_run")
        
        result = pytester.runpytest()
        result.assert_outcomes(passed=1)
        
        # Should only have the second test, not both
        test_names = get_test_names_from_file(json_file)
        assert len(test_names) == 1
        assert "test_second_run" in test_names
        assert "test_first_run" not in test_names