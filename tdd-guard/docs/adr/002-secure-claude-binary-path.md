# ADR-002: Secure Claude Binary Path Configuration

## Status

Accepted

## Context

CodeQL security scanning identified a potential command injection vulnerability in `ClaudeModelClient` where the Claude binary path is taken from an environment variable (`CLAUDE_BINARY_PATH`) and interpolated into a shell command executed via `execSync`.

The vulnerability occurs because:

- Environment variables can be manipulated by attackers
- Shell metacharacters in the path could be interpreted, allowing arbitrary command execution
- For example, setting `CLAUDE_BINARY_PATH="claude; rm -rf /"` would execute both commands

We considered several approaches:

1. **Use execFileSync instead of execSync** - Avoids shell interpretation entirely
2. **Validate/sanitize the binary path** - Check for allowed characters only
3. **Use shell-quote library** - Properly escape shell metacharacters
4. **Boolean flag for predefined paths** - Switch between hardcoded safe paths

## Decision

We will use a boolean environment variable `USE_LOCAL_CLAUDE` to switch between two hardcoded, safe paths:

- When `USE_LOCAL_CLAUDE=true`: Use `$HOME/.claude/local/claude`
- Otherwise: Use system `claude` command

Additionally, we will:

- Implement the path logic in `ClaudeModelClient` rather than `Config` class
- Use `execFileSync` instead of `execSync` to prevent shell interpretation
- Keep the Config class focused on just providing the boolean flag

## Consequences

### Positive

- **Eliminates injection risk** - No user-controlled input in command construction
- **Simple and secure** - Only two possible paths, both hardcoded
- **Clear intent** - Boolean flag clearly indicates local vs system Claude
- **Separation of concerns** - Config provides settings, ModelClient handles implementation
- **Future flexibility** - ModelClient can handle OS-specific paths internally

### Negative

- **Less flexible** - Users cannot specify custom installation paths
- **Requires code changes** - Adding new paths requires updating the code
- **Platform-specific paths** - May need adjustment for different operating systems

### Neutral

- Migration from `CLAUDE_BINARY_PATH` to `USE_LOCAL_CLAUDE` for existing users
- Documentation needs to be updated to reflect the new configuration approach
