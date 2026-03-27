"""Test configuration option for project root"""
from .helpers import check_pytest_accepts_config


def test_pytest_ini_accepts_project_root():
    """Test that pytest.ini accepts tdd_guard_project_root configuration"""
    result = check_pytest_accepts_config("""
[pytest]
tdd_guard_project_root = /some/path
""", "pytest.ini")
    
    # Should succeed without "unknown config option" error
    assert result.returncode == 0
    assert "unknown config option" not in result.stderr.lower()


def test_pyproject_toml_accepts_project_root():
    """Test that pyproject.toml accepts tdd_guard_project_root configuration"""
    result = check_pytest_accepts_config("""
[tool.pytest.ini_options]
tdd_guard_project_root = "/some/path"
""", "pyproject.toml")
    
    # Should succeed without "unknown config option" error
    assert result.returncode == 0
    assert "unknown config option" not in result.stderr.lower()


def test_setup_cfg_accepts_project_root():
    """Test that setup.cfg accepts tdd_guard_project_root configuration"""
    result = check_pytest_accepts_config("""
[tool:pytest]
tdd_guard_project_root = /some/path
""", "setup.cfg")
    
    # Should succeed without "unknown config option" error
    assert result.returncode == 0
    assert "unknown config option" not in result.stderr.lower()