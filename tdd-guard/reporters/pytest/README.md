# TDD Guard Pytest Reporter

Pytest plugin that captures test results for TDD Guard validation.

## Requirements

- Python 3.8+
- pytest 6.0+
- [TDD Guard](https://github.com/nizos/tdd-guard) installed globally

## Installation

```bash
pip install tdd-guard-pytest
```

The plugin activates automatically when installed.

## Configuration

### Project Root Configuration

Set `tdd_guard_project_root` to your project root using any ONE of these methods:

**Option 1: pyproject.toml**

```toml
[tool.pytest.ini_options]
tdd_guard_project_root = "/absolute/path/to/project/root"
```

**Option 2: pytest.ini**

```ini
[pytest]
tdd_guard_project_root = /absolute/path/to/project/root
```

**Option 3: setup.cfg**

```ini
[tool:pytest]
tdd_guard_project_root = /absolute/path/to/project/root
```

### Configuration Rules

- Path must be absolute
- Current directory must be within the configured project root
- Falls back to current directory if configuration is invalid

## Development

When developing the pytest reporter, you need to configure the project root to ensure test results are saved to the correct location:

1. Copy the example configuration:

   ```bash
   cp pytest.ini.example pytest.ini
   ```

2. Edit `pytest.ini` and set the absolute path to your TDD Guard project root:
   ```ini
   [pytest]
   tdd_guard_project_root = /absolute/path/to/tdd-guard
   ```

**Note:** `pytest.ini` is gitignored to avoid committing machine-specific paths.

## More Information

- Test results are saved to `.claude/tdd-guard/data/test.json`
- See [TDD Guard documentation](https://github.com/nizos/tdd-guard) for complete setup

## License

MIT
