"""Test plugin configuration functionality"""
from pathlib import Path
from tdd_guard_pytest.pytest_reporter import TDDGuardPytestPlugin, DEFAULT_DATA_DIR
from .helpers import create_config


def test_plugin_accepts_config_parameter():
    """Test that TDDGuardPytestPlugin can be initialized with a config parameter"""
    # Create a minimal config object
    class MinimalConfig:
        pass
    
    config = MinimalConfig()
    
    # Plugin should accept config parameter without error
    plugin = TDDGuardPytestPlugin(config)
    
    # Default behavior - should still use default storage dir
    assert plugin.storage_dir == DEFAULT_DATA_DIR


def test_plugin_uses_configured_project_root():
    """Test that plugin uses tdd_guard_project_root from config"""
    project_root = Path("/test/project")
    cwd = project_root / "subdir"
    
    config = create_config(str(project_root))
    plugin = TDDGuardPytestPlugin(config, cwd=cwd)
    
    # Plugin should use the configured directory
    expected_storage = project_root / DEFAULT_DATA_DIR
    assert plugin.storage_dir == expected_storage