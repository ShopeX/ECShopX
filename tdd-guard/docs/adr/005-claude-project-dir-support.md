# ADR-005: Support CLAUDE_PROJECT_DIR for Consistent Data Storage

## Status

Accepted

## Context

TDD Guard stores data in `.claude/tdd-guard/data` relative to the current working directory. This creates issues when:

- Users run commands from subdirectories (e.g., `cd src && npm test`)
- Claude Code executes commands from different locations within a project
- Multiple `.claude` directories are created at different levels

Previously, users had to configure `CLAUDE_BASH_MAINTAIN_PROJECT_WORKING_DIR` in Claude Code to ensure commands always run from the project root. This required additional configuration and restricted how developers could use Claude Code.

Claude Code provides the `CLAUDE_PROJECT_DIR` environment variable that always points to the project root, regardless of where commands are executed. This is part of Claude Code's [security best practices](https://docs.anthropic.com/en/docs/claude-code/hooks#security-best-practices).

## Decision

We will use `CLAUDE_PROJECT_DIR` when available to determine the base path for TDD Guard's data directory.

The implementation:

- Check if `CLAUDE_PROJECT_DIR` is set and valid
- Use it as the base path for `.claude/tdd-guard/data` if available
- Fall back to current working directory if not set
- Apply security validations to prevent path traversal attacks
- Reporter-provided `projectRoot` takes precedence over `CLAUDE_PROJECT_DIR`

Security validations include:

- Validate `CLAUDE_PROJECT_DIR` is an absolute path
- Prevent path traversal by checking for `..` sequences
- Ensure current working directory is within `CLAUDE_PROJECT_DIR`

When validation fails, TDD Guard throws a descriptive error and the operation is blocked, preventing any file system access with invalid paths.

## Consequences

### Positive

- **Consistent data location** - Data is always stored at the project root
- **No user configuration needed** - Works automatically with Claude Code
- **Better developer experience** - Can run commands from any project subdirectory
- **Maintains security** - Path validation prevents directory traversal attacks

### Negative

- **Additional validation code** - Security checks add complexity, but this is centralized in the Config class

### Neutral

- Falls back gracefully when environment variable is not present
- Replaces the need for `CLAUDE_BASH_MAINTAIN_PROJECT_WORKING_DIR` configuration
