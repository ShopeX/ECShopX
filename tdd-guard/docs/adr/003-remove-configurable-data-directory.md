# ADR-003: Remove Configurable Data Directory

## Status

Accepted

## Context

A security review identified a potential path traversal vulnerability in TDD Guard where the data directory path is taken from an environment variable (`TDD_DATA_DIR`) and used directly for file system operations without validation.

The vulnerability occurs because:

- Environment variables can be manipulated by attackers
- Path traversal sequences (`../`) in the path could escape the intended directory
- For example, setting `TDD_DATA_DIR="../../../../etc"` would write files to system directories
- The application writes files like `test.txt`, `todo.json`, and `modifications.json` to this directory

We considered several approaches:

1. **Validate and sanitize the path** - Check for `../` sequences and resolve to absolute paths
2. **Restrict to project subdirectories** - Ensure the path stays within the project root
3. **Use a whitelist of allowed paths** - Only allow specific predefined directories
4. **Remove the configuration entirely** - Hardcode the data directory path

## Decision

We will remove the `TDD_DATA_DIR` environment variable and hardcode the data directory path to `.claude/tdd-guard/data` in the Config class.

The implementation will:

- Remove `TDD_DATA_DIR` from environment variable processing
- Hardcode `dataDir` to `.claude/tdd-guard/data` in the Config constructor
- Keep the existing Config class interface unchanged for dependent code
- Remove documentation about `TDD_DATA_DIR` from `.env.example`, README, and CLAUDE.md

## Consequences

### Positive

- **Eliminates path traversal risk** - No user-controlled input for file paths
- **Simpler implementation** - No validation or sanitization code needed
- **Consistent data location** - All TDD Guard data in a predictable location
- **Better security posture** - Follows principle of least privilege
- **No breaking changes for code** - Config class interface remains the same

### Negative

- **Less flexible** - Users cannot customize where TDD Guard stores its data
- **Potential disk space issues** - Users cannot redirect to different drives/partitions
- **Testing limitations** - Integration tests cannot use isolated data directories

### Neutral

- The data stored (test results, todos, modifications) is operational/temporary
- Most users likely never customized this path anyway
- Follows the same security-first approach as ADR-002
