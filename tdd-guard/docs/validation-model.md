# Validation Model Configuration

TDD Guard validates changes using AI. Configure both the validation client (SDK or API) and the Claude model version.

## Claude Agent SDK (Default)

The recommended approach. Uses the Claude Agent SDK to communicate with Claude directly.

```bash
VALIDATION_CLIENT=sdk  # Default, can be omitted
```

**Features:**

- Works automatically with your Claude Code installation
- Uses your Claude Code subscription (no extra charges)
- Requires Claude Code to be installed and authenticated

**Important:** If you have `ANTHROPIC_API_KEY` set in your environment, Claude Code may use it for billing instead of your subscription. To avoid unexpected charges:

```bash
# Check if API key is set
echo $ANTHROPIC_API_KEY

# Unset it if present
unset ANTHROPIC_API_KEY
```

If you've never created an API key, you can ignore this warning.

## Anthropic API

For CI/CD environments or when you need faster validation. Requires separate billing from Claude Code.

```bash
VALIDATION_CLIENT=api
TDD_GUARD_ANTHROPIC_API_KEY=your_api_key_here
```

Get your API key from [console.anthropic.com](https://console.anthropic.com/)

**Notes:**

- Charges separately from your Claude Code subscription ([pricing](https://www.anthropic.com/pricing))
- We use `TDD_GUARD_ANTHROPIC_API_KEY` (not `ANTHROPIC_API_KEY`) to prevent accidental charges. If you used the regular `ANTHROPIC_API_KEY`, Claude Code might use it for all your normal coding tasks, charging your API account instead of using your subscription.

## Model Selection

Configure which Claude model to use for validation (default: `claude-sonnet-4-0`):

```bash
# Fastest but unreliable results
TDD_GUARD_MODEL_VERSION=claude-3-5-haiku-20241022

# Best results but slowest
TDD_GUARD_MODEL_VERSION=claude-opus-4-1
```

See [Claude model overview](https://docs.anthropic.com/en/docs/about-claude/models/overview) for available models and pricing. Note: pricing only applies to API users - SDK uses your Claude Code subscription by default. Balance model capability with [custom instructions](custom-instructions.md) to optimize for your needs.

## Migration from Legacy Configuration

If you're using the old `MODEL_TYPE` configuration, see the [Configuration Migration Guide](config-migration.md) for detailed instructions.
