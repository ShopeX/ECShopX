# Configuration Migration Guide

This guide helps you migrate from the legacy `MODEL_TYPE` configuration to the new `VALIDATION_CLIENT` system.

## What Changed

The old configuration system required:

- `MODEL_TYPE` to choose between `claude_cli` or `anthropic_api`
- `USE_SYSTEM_CLAUDE` (true/false) to choose between system or local Claude binary
- Complex setup for finding and configuring the Claude binary

The new system simplifies this with:

- `VALIDATION_CLIENT` to choose between `sdk`, `api`, or `cli` (deprecated)
- No binary configuration needed for SDK (the default)

## Migration Instructions

### From Claude CLI to SDK

**Old configuration:**

```bash
MODEL_TYPE=claude_cli
USE_SYSTEM_CLAUDE=true  # or false
```

**New configuration:**

```bash
VALIDATION_CLIENT=sdk  # Or omit entirely, as SDK is the default
```

The SDK client eliminates the need for:

- Finding the Claude binary location
- Setting `USE_SYSTEM_CLAUDE`
- Dealing with symlinks or PATH configuration

### From Anthropic API

**Old configuration:**

```bash
MODEL_TYPE=anthropic_api
TDD_GUARD_ANTHROPIC_API_KEY=your-api-key-here
```

**New configuration:**

```bash
VALIDATION_CLIENT=api
TDD_GUARD_ANTHROPIC_API_KEY=your-api-key-here  # Same key variable
```

The API configuration remains similar, just with a clearer variable name.

## Legacy CLI Client (Deprecated)

If you must continue using the CLI client (not recommended):

```bash
VALIDATION_CLIENT=cli  # Deprecated - use sdk instead
```

You'll still need to configure the Claude binary location as described in the [CLI Binary Configuration](#cli-binary-configuration) section below.

### Why CLI is Deprecated

The SDK client is easier to work with and requires less configuration for different setups and operating systems.

## Deprecated Variables

| Variable            | Replacement         | Notes                                             |
| ------------------- | ------------------- | ------------------------------------------------- |
| `MODEL_TYPE`        | `VALIDATION_CLIENT` | Map `claude_cli` → `sdk`, `anthropic_api` → `api` |
| `USE_SYSTEM_CLAUDE` | None                | No longer needed with SDK                         |
| `TEST_MODEL_TYPE`   | None                | Use consistent configuration                      |

## Common Migration Issues

### API Key Conflicts

For information about API key conflicts and billing, see the [Validdation Model Configuration](validation-model.md) documentation.

## CLI Binary Configuration

If you're still using the deprecated CLI client (`VALIDATION_CLIENT=cli`), you need to help TDD Guard find your Claude installation.

### Finding Your Claude Installation

```bash
# Check system-wide installation
which claude

# Check local installation
ls ~/.claude/local/claude
```

### Configuration Options

**Option 1: Environment Variable**

If Claude is in your PATH:

```bash
USE_SYSTEM_CLAUDE=true
```

**Option 2: Symlink**

Point to your Claude installation:

```bash
# Create directory if needed
mkdir -p ~/.claude/local

# Create symlink to your Claude binary
ln -s /path/to/your/claude ~/.claude/local/claude
```

Example for Homebrew on macOS:

```bash
ln -s /opt/homebrew/bin/claude ~/.claude/local/claude
```

**Option 3: Migrate Installation**

Use Claude Code's built-in command:

```bash
/migrate-installer
```

## Getting Help

If you encounter issues during migration:

1. Check the [main configuration guide](configuration.md)
2. Review the [Validdation Model Configuration](validation-model.md)
3. Open an issue at [github.com/nizos/tdd-guard/issues](https://github.com/nizos/tdd-guard/issues)
