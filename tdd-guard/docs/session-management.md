# TDD Guard Session Management

The SessionStart hook manages TDD Guard's session data and ensures a clean slate for each Claude Code session.

## What It Does

### Clears Transient Data

- Test results from previous sessions
- Lint reports and code quality checks
- Other temporary validation data

### Sets Up Validation Rules

- Creates the customizable instructions file if it doesn't exist
- Preserves your custom rules if already configured
- See [Custom Instructions](custom-instructions.md) for details

**Note:** The guard's enabled/disabled state is preserved across sessions.

## Setup

To enable session management, you need to add the SessionStart hook to your Claude Code configuration.
You can set this up either through the interactive `/hooks` command or by manually editing your settings file. See [Settings File Locations](configuration.md#settings-file-locations) to choose the appropriate location.

### Using Interactive Setup (Recommended)

1. Type `/hooks` in Claude Code
2. Select `SessionStart - When a new session is started`
3. Select `+ Add new matcher…`
4. Enter matcher: `startup|resume|clear`
5. Select `+ Add new hook…`
6. Enter command: `tdd-guard`
7. Choose where to save

### Manual Configuration (Alternative)

Add the following to your chosen settings file:

```json
{
  "hooks": {
    "SessionStart": [
      {
        "matcher": "startup|resume|clear",
        "hooks": [
          {
            "type": "command",
            "command": "tdd-guard"
          }
        ]
      }
    ]
  }
}
```

Note: Your configuration file may already have other hooks configured.
Simply add the `SessionStart` section to your existing hooks object.

## How It Works

The SessionStart hook triggers when:

- Claude Code starts up (`startup`)
- A session is resumed (`resume`)
- The `/clear` command is used (`clear`)

When triggered, TDD Guard clears all transient data while preserving the guard state and your custom validation rules.

## Tips

- No manual intervention needed - clearing happens automatically
- To toggle the guard on/off, use the [quick commands](quick-commands.md)
- For debugging, check `.claude/tdd-guard/` to see stored data
