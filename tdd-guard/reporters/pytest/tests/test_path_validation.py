"""Test path validation for project root configuration"""
from pathlib import Path
from tdd_guard_pytest.pytest_reporter import TDDGuardPytestPlugin, DEFAULT_DATA_DIR
from .helpers import create_config


def test_plugin_only_accepts_absolute_paths():
    """Test that plugin ignores relative paths"""
    config = create_config("../some/relative/path")
    plugin = TDDGuardPytestPlugin(config)
    
    # Should fall back to default when relative path is provided
    assert plugin.storage_dir == DEFAULT_DATA_DIR


def test_plugin_rejects_project_root_when_cwd_is_outside():
    """Test that plugin rejects project root if cwd is not within it"""
    project_root = Path("/other/project")
    cwd = Path("/test/current")
    
    config = create_config(str(project_root))
    plugin = TDDGuardPytestPlugin(config, cwd=cwd)
    
    # Should fall back to default since cwd is not within project_root
    assert plugin.storage_dir == DEFAULT_DATA_DIR


def test_plugin_accepts_project_root_same_as_cwd():
    """Test that plugin accepts project root when it equals cwd"""
    cwd = Path("/test/project")
    
    config = create_config(str(cwd))
    plugin = TDDGuardPytestPlugin(config, cwd=cwd)
    
    # Should use the configured directory since cwd == project root
    expected_storage = cwd / DEFAULT_DATA_DIR
    assert plugin.storage_dir == expected_storage


def test_plugin_accepts_project_root_when_cwd_is_child():
    """Test that plugin accepts project root when cwd is a child of it"""
    project_root = Path("/test/project")
    cwd = project_root / "subdir"
    
    config = create_config(str(project_root))
    plugin = TDDGuardPytestPlugin(config, cwd=cwd)
    
    # Should use the configured directory since cwd is within parent
    expected_storage = project_root / DEFAULT_DATA_DIR
    assert plugin.storage_dir == expected_storage


def test_plugin_handles_empty_config_value():
    """Test that plugin uses default when config returns empty string"""
    config = create_config("")
    plugin = TDDGuardPytestPlugin(config)
    
    # Should fall back to default when config is empty
    assert plugin.storage_dir == DEFAULT_DATA_DIR