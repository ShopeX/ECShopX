# TDD Guard Quick Commands

TDD Guard can be quickly enabled or disabled using simple commands in your Claude Code session.
This is particularly useful when you need to temporarily disable TDD enforcement during prototyping or exploration phases.

## Usage

Simply type one of these commands in your Claude Code prompt:

- `tdd-guard on` - Enables TDD Guard enforcement
- `tdd-guard off` - Disables TDD Guard enforcement

The commands are case-insensitive, so `TDD-Guard OFF`, `tdd-guard off`, and `Tdd-Guard Off` all work the same way.

## Setup

To enable the quick commands feature, you need to add the UserPromptSubmit hook to your Claude Code configuration.
You can set this up either through the interactive `/hooks` command or by manually editing your settings file. See [Settings File Locations](configuration.md#settings-file-locations) to choose the appropriate location.

### Using Interactive Setup (Recommended)

1. Type `/hooks` in Claude Code
2. Select `UserPromptSubmit - When the user submits a prompt`
3. Select `+ Add new hook...`
4. Enter command: `tdd-guard`
5. Choose where to save

### Manual Configuration (Alternative)

Add the following to your chosen settings file:

```json
{
  "hooks": {
    "userpromptsubmit": [
      {
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
Simply add the `userpromptsubmit` section to your existing hooks object.

**Tip**: To prevent agents from modifying the TDD Guard state, see [Strengthening TDD Enforcement](enforcement.md).
