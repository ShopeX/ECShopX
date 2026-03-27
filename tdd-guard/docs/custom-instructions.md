# Custom TDD Instructions

Customize TDD Guard's validation rules to match your specific TDD practices.

## How It Works

TDD Guard uses validation rules to enforce TDD principles. You can override these default rules by creating a custom instructions file at `.claude/tdd-guard/data/instructions.md`.

## Automatic Setup

If you have the [SessionStart hook](session-management.md) configured, the instructions file is created automatically with default rules when:

- Starting a new Claude Code session
- Resuming a session
- Using the `/clear` command

Your custom instructions are never overwritten - once created, the file remains under your control.

## Creating Custom Instructions

1. Edit `.claude/tdd-guard/data/instructions.md`
2. Adjust or replace the default rules with your TDD requirements
3. Changes take effect immediately - no restart needed

## Updating to Latest Defaults

When updating TDD Guard, you may want the latest default instructions:

1. Delete `.claude/tdd-guard/data/instructions.md`
2. Trigger the SessionStart hook (start new session or use `/clear`)
3. The latest defaults will be created automatically

Alternatively, you can manually copy the default rules from [`src/validation/prompts/rules.ts`](../src/validation/prompts/rules.ts).

## Protecting Your Instructions

Prevent agents from modifying your custom instructions by denying access to TDD Guard data. See [Strengthening TDD Enforcement](enforcement.md) for details.

## Tips

- Start with the default instructions and modify incrementally
- Keep rules clear and actionable for consistent validation
- Share effective customizations with the TDD Guard community in [GitHub Discussions](https://github.com/nizos/tdd-guard/discussions)
