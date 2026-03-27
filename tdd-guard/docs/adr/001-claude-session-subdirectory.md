# ADR-001: Execute Claude CLI from Subdirectory for Session Management

## Status

Accepted

## Context

The TDD Guard validation system creates a new Claude session for each validation operation when running `claude` commands. This results in session list clutter and makes it difficult to track the actual development sessions.

We considered two approaches:

1. Clear the context before each validation using `/clear` command
2. Run the Claude CLI from a subdirectory to isolate validation sessions

The first approach has limitations:

- Cannot clear and ask a question in the same command
- Would require multiple command executions
- Still shows all sessions in the same directory listing

## Decision

We will execute all Claude validation commands from a `.claude` subdirectory within the project root.

Implementation details:

- Create `.claude` directory if it doesn't exist
- Set the `cwd` option in `execSync` to the subdirectory path
- The `.claude` directory itself is not ignored (contains settings.json which should be tracked)
- User-specific files like `.claude/settings.local.json` should already be in `.gitignore`

## Consequences

### Positive

- Validation sessions are isolated from development sessions
- No cluttering of the main project's session list
- Automatic trust inheritance from parent directory
- Simple implementation with minimal code changes
- No impact on validation functionality

### Negative

- Creates an additional directory in the project
- Slightly increases complexity in the model client
- Sessions are less visible (though this is mostly a benefit)

### Neutral

- All validation sessions will appear in the `.claude` subdirectory listing
- Developers need to know to look in `.claude` for validation session history
