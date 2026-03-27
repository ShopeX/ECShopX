# Storybook with Vitest Addon

If you're using Storybook 10+ with a Vite-based framework, you can use `@storybook/addon-vitest` instead of `@storybook/test-runner`. This approach uses TDD Guard's existing Vitest reporter to capture Storybook test results.

## Why Use the Vitest Addon?

The Vitest addon offers several advantages:

- **Faster execution** - Uses Vitest's browser mode instead of Playwright
- **Modern tooling** - Built on Vitest, which is faster and more modern than Jest
- **Unified testing** - Run Storybook tests alongside your other Vitest tests
- **Better DX** - Full Storybook Test experience with interaction, accessibility, and visual tests
- **Simpler setup** - Uses your existing Vitest configuration

## Requirements

- Storybook 10+
- Vite-based Storybook framework (React-Vite, Vue-Vite, Svelte-Vite, etc.)
- Vitest

## Setup

### 1. Install the Vitest Addon

```bash
npm install --save-dev @storybook/addon-vitest
```

### 2. Configure Storybook

Add the addon to your `.storybook/main.ts`:

```typescript
import type { StorybookConfig } from '@storybook/react-vite'

const config: StorybookConfig = {
  stories: ['../src/**/*.stories.@(js|jsx|ts|tsx)'],
  addons: ['@storybook/addon-vitest'],
  framework: '@storybook/react-vite',
}

export default config
```

### 3. Configure Vitest Reporter

Since the Vitest addon runs your Storybook tests through Vitest, you use the `tdd-guard-vitest` reporter to capture results:

```bash
npm install --save-dev tdd-guard-vitest
```

Add to your `vitest.config.ts`:

```typescript
import { defineConfig } from 'vitest/config'
import { VitestReporter } from 'tdd-guard-vitest'

export default defineConfig({
  test: {
    reporters: [
      'default',
      new VitestReporter('/Users/username/projects/my-app'),
    ],
  },
})
```

### 4. Run Tests

```bash
npm run test
```

Your Storybook interaction tests will run alongside your regular Vitest tests, and TDD Guard will capture all results.

## Comparison with Test Runner

| Feature            | @storybook/test-runner | @storybook/addon-vitest |
| ------------------ | ---------------------- | ----------------------- |
| Test framework     | Jest + Playwright      | Vitest browser mode     |
| Storybook version  | 6.4+                   | 10+                     |
| Framework support  | All frameworks         | Vite-based only         |
| TDD Guard reporter | tdd-guard-storybook    | tdd-guard-vitest        |
| Speed              | Slower (full browser)  | Faster                  |

## When to Use Each

**Use `@storybook/addon-vitest` when:**

- You're on Storybook 10+
- You're using a Vite-based framework
- You want faster test execution
- You want to unify your testing setup

**Use `@storybook/test-runner` when:**

- You're on Storybook 6.4-9.x
- You need Webpack-based framework support
- You need full Playwright browser testing

## Troubleshooting

### Tests Not Running

1. Verify Storybook is configured with the Vitest addon
2. Check that your stories have `play` functions (interaction tests)
3. Ensure `tdd-guard-vitest` is in your Vitest reporters

### Results Not Captured

1. Verify `tdd-guard-vitest` is installed and configured
2. Check the project root path is correct in the reporter config
3. Look for `.claude/tdd-guard/data/test.json` after running tests

## Further Reading

- [Storybook Vitest Addon Documentation](https://storybook.js.org/docs/writing-tests/integrations/vitest-addon)
- [Vitest Reporter Configuration](../reporters/vitest/README.md)
- [TDD Guard Configuration](configuration.md)
