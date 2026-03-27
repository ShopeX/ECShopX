# TDD Guard Storybook Reporter

Storybook test-runner reporter that captures test results for TDD Guard validation.

## Requirements

- Node.js 18+
- Storybook 7.x - 9.x
- @storybook/test-runner 0.19.0+
- [TDD Guard](https://github.com/nizos/tdd-guard) installed globally

> [!IMPORTANT]
> For Storybook v10.x, use the `tdd-guard-vitest` reporter w/ Vitest

## Installation

```bash
npm install --save-dev tdd-guard-storybook
```

## Configuration

### Basic Configuration

Add the reporter to your `.storybook/test-runner.ts`:

```typescript
import { StorybookReporter } from 'tdd-guard-storybook'

const reporter = new StorybookReporter()

module.exports = {
  async postVisit(page, context) {
    await reporter.onStoryResult(context)
  },
}

process.on('exit', () => {
  reporter.onComplete()
})
```

### Workspace/Monorepo Configuration

For workspaces or monorepos, pass the project root path to the reporter:

```typescript
import { StorybookReporter } from 'tdd-guard-storybook'
import path from 'path'

const reporter = new StorybookReporter(path.resolve(__dirname, '../..'))

module.exports = {
  async postVisit(page, context) {
    await reporter.onStoryResult(context)
  },
}

process.on('exit', () => {
  reporter.onComplete()
})
```

If your test-runner config is in a subdirectory, pass the absolute path to your project root:

```typescript
new StorybookReporter('/Users/username/projects/my-app')
```

## How It Works

The reporter captures results from stories that have `play` functions (interaction tests):

- Stories with `play` functions are reported as tests
- Stories without `play` functions are ignored
- Test results are saved to `.claude/tdd-guard/data/test.json`

## Output Format

The reporter writes test results in a format compatible with TDD Guard validation:

```json
{
  "testModules": [
    {
      "moduleId": "button--primary",
      "tests": [
        {
          "name": "Primary",
          "fullName": "Button > Primary",
          "state": "passed"
        }
      ]
    }
  ],
  "unhandledErrors": [],
  "reason": "passed"
}
```

## More Information

- Test results are saved to `.claude/tdd-guard/data/test.json`
- See [TDD Guard documentation](https://github.com/nizos/tdd-guard) for complete setup

## License

MIT
