# Strengthening TDD Enforcement

Ensure consistent TDD validation by preventing agents from modifying guard settings or bypassing file operation hooks.

## Protect Guard Settings

Prevent agents from accessing TDD Guard's configuration and state:

```json
{
  "permissions": {
    "deny": ["Read(.claude/tdd-guard/**)"]
  }
}
```

This protects your custom instructions, guard state, and test results from unintended changes.

## Block File Operation Bypass

If your settings allow shell commands without approval, agents can modify files without triggering TDD validation. Block these commands to maintain enforcement:

```json
{
  "permissions": {
    "deny": [
      "Bash(echo:*)",
      "Bash(printf:*)",
      "Bash(sed:*)",
      "Bash(awk:*)",
      "Bash(perl:*)"
    ]
  }
}
```

**Note:** Only needed if you've configured auto-approval for shell commands. May limit some agent capabilities.

## Where to Apply

Add these settings to your chosen settings file. See [Settings File Locations](configuration.md#settings-file-locations) to choose the appropriate location.

## Need Help?

- Report bypass methods: [GitHub Issues](https://github.com/nizos/tdd-guard/issues)
- Share strategies: [GitHub Discussions](https://github.com/nizos/tdd-guard/discussions)
