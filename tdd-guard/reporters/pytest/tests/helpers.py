"""Shared test helpers for pytest reporter tests"""
import json
import shutil
import tempfile
import subprocess
import sys
from pathlib import Path

from tdd_guard_pytest.pytest_reporter import DEFAULT_DATA_DIR


def create_config(project_root_value):
    """Helper to create a config object that returns a specific project root value"""
    class TestConfig:
        def getini(self, name):
            if name == 'tdd_guard_project_root':
                return project_root_value
            return ""
    return TestConfig()


def check_pytest_accepts_config(config_content, config_filename="pytest.ini"):
    """Check that pytest accepts tdd_guard_project_root in a config file
    
    Args:
        config_content: The configuration file content
        config_filename: Name of the config file (pytest.ini, pyproject.toml, setup.cfg)
    
    Returns:
        subprocess result
    """
    with tempfile.TemporaryDirectory() as tmpdir:
        # Create a test file
        test_file = Path(tmpdir) / "test_dummy.py"
        test_file.write_text("def test_dummy(): pass")
        
        # Create config file
        config_file = Path(tmpdir) / config_filename
        config_file.write_text(config_content)
        
        # Run pytest with the config
        result = subprocess.run(
            [sys.executable, "-m", "pytest", "--version", "-c", str(config_file)],
            cwd=tmpdir,
            capture_output=True,
            text=True
        )
        
        return result


# Integration test helpers
def create_test_file(pytester, filename=None, content=None):
    """Create a test file with default content if not specified."""
    if content is None:
        content = """
def test_example_pass():
    assert True

def test_example_fail():
    assert False
        """.strip()
    
    if filename:
        return pytester.makepyfile(**{filename: content})
    else:
        return pytester.makepyfile(content)


def create_simple_passing_test(pytester, test_name="test_simple", filename=None):
    """Create a simple passing test file."""
    content = f"""
def {test_name}():
    assert True
    """.strip()
    
    return create_test_file(pytester, filename, content)


def create_pytest_ini(pytester, project_root=None):
    """Create pytest.ini with optional project root configuration."""
    if project_root:
        content = f"""
[pytest]
tdd_guard_project_root = {project_root}
        """.strip()
    else:
        content = """
[pytest]
        """.strip()
    
    return pytester.makeini(content)


def create_project_structure(base_path, structure):
    """Create a directory structure from a dict specification.
    
    Example:
        create_project_structure(tmp_path, {
            "src": {
                "models": {},
                "tests": {
                    "test_models.py": "def test_model():\\n    assert True"
                }
            },
            "pytest.ini": "[pytest]\\naddopts = -v"
        })
    """
    for name, content in structure.items():
        path = base_path / name
        if isinstance(content, dict):
            # It's a directory
            path.mkdir(exist_ok=True)
            create_project_structure(path, content)
        else:
            # It's a file
            path.write_text(content)


def verify_test_results(json_file, expected_tests):
    """Verify that test results match expected outcomes."""
    with open(json_file) as f:
        data = json.load(f)
    
    all_tests = []
    for module in data["testModules"]:
        if isinstance(module, dict) and "tests" in module:
            all_tests.extend(module["tests"])
        elif isinstance(module, dict):
            # Handle nested structure
            for module_data in module.values():
                if isinstance(module_data, dict) and "tests" in module_data:
                    all_tests.extend(module_data["tests"])
    
    assert len(all_tests) == len(expected_tests)
    
    for test_name, expected_state in expected_tests:
        matching_test = next((t for t in all_tests if t["name"] == test_name), None)
        assert matching_test is not None, f"Test '{test_name}' not found"
        assert matching_test["state"] == expected_state, \
            f"Test '{test_name}' has state '{matching_test['state']}', expected '{expected_state}'"


def extract_all_test_names(data):
    """Extract all test names from the JSON data structure."""
    test_names = []
    for module in data["testModules"]:
        if isinstance(module, dict) and "tests" in module:
            test_names.extend(t["name"] for t in module["tests"])
        elif isinstance(module, dict):
            # Handle nested structure
            for module_data in module.values():
                if isinstance(module_data, dict) and "tests" in module_data:
                    test_names.extend(t["name"] for t in module_data["tests"])
    return test_names


def assert_json_file_exists(path, data_dir=DEFAULT_DATA_DIR):
    """Assert that the JSON test results file exists at the given path."""
    json_file = path / data_dir / "test.json"
    assert json_file.exists(), f"Expected test results at {json_file}"
    return json_file


def assert_json_file_not_exists(path, data_dir=DEFAULT_DATA_DIR):
    """Assert that the JSON test results file does NOT exist at the given path."""
    json_file = path / data_dir / "test.json"
    assert not json_file.exists(), f"Unexpected test results at {json_file}"


def create_multiple_test_files(pytester, test_specs):
    """Create multiple test files from a dictionary specification.
    
    Args:
        pytester: The pytester fixture
        test_specs: Dict mapping filename to test content or list of (test_name, should_pass) tuples
        
    Example:
        create_multiple_test_files(pytester, {
            "test_auth": [("test_login", True), ("test_logout", True)],
            "test_db": "def test_connection():\\n    assert True"
        })
    """
    files = {}
    for filename, spec in test_specs.items():
        if isinstance(spec, str):
            # Direct content provided
            files[filename] = spec
        elif isinstance(spec, list):
            # Generate tests from (name, should_pass) tuples
            test_lines = []
            for test_name, should_pass in spec:
                test_lines.append(f"""
def {test_name}():
    assert {should_pass}
""".strip())
            files[filename] = "\n\n".join(test_lines)
        else:
            raise ValueError(f"Invalid spec type for {filename}: {type(spec)}")
    
    return pytester.makepyfile(**files)


def copy_project_and_update_root(pytester, source_project, project_name=None):
    """Copy a project to pytester directory and update its pytest.ini with new path.
    
    Args:
        pytester: The pytester fixture
        source_project: Path to the source project
        project_name: Optional name for the copied project (defaults to source name)
        
    Returns:
        Path to the copied project
    """
    if project_name is None:
        project_name = source_project.name
    
    copied_project = pytester.path / project_name
    shutil.copytree(source_project, copied_project)
    
    # Update pytest.ini with new path
    ini_file = copied_project / "pytest.ini"
    if ini_file.exists():
        ini_file.write_text(f"""
[pytest]
tdd_guard_project_root = {copied_project}
""".strip())
    
    return copied_project


def create_passing_test(test_name="test_example", content=None):
    """Generate a simple passing test function.
    
    Args:
        test_name: Name of the test function
        content: Optional custom assertion content
        
    Returns:
        String containing the test function
    """
    if content is None:
        content = "assert True"
    
    return f"""
def {test_name}():
    {content}
""".strip()


def create_failing_test(test_name="test_example", content=None):
    """Generate a simple failing test function.
    
    Args:
        test_name: Name of the test function
        content: Optional custom assertion content
        
    Returns:
        String containing the test function
    """
    if content is None:
        content = "assert False"
    
    return f"""
def {test_name}():
    {content}
""".strip()


def load_test_results(json_file):
    """Load test results from JSON file.
    
    Args:
        json_file: Path to the JSON file
        
    Returns:
        Dict containing the test results data
    """
    with open(json_file) as f:
        return json.load(f)


def get_test_names_from_file(json_file):
    """Load JSON file and extract all test names.
    
    Args:
        json_file: Path to the JSON file
        
    Returns:
        List of test names
    """
    data = load_test_results(json_file)
    return extract_all_test_names(data)