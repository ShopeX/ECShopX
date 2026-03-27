# TDD Guard Jest Reporter

Jest reporter that captures test results for TDD Guard validation.

## Requirements

- Node.js 18+
- Jest 30.0.5+
- [TDD Guard](https://github.com/nizos/tdd-guard) installed globally

## Installation

```bash
npm install --save-dev tdd-guard-jest
```

## Configuration

### Jest Configuration

Add the reporter to your `jest.config.js`:

```javascript
module.exports = {
  reporters: [
    'default',
    [
      'tdd-guard-jest',
      {
        projectRoot: __dirname,
      },
    ],
  ],
}
```

Or in `jest.config.ts`:

```typescript
import type { Config } from 'jest'
import path from 'path'

const config: Config = {
  reporters: [
    'default',
    [
      'tdd-guard-jest',
      {
        projectRoot: path.resolve(__dirname),
      },
    ],
  ],
}

export default config
```

### Workspace/Monorepo Configuration

For workspaces or monorepos, pass the project root path to the reporter:

```javascript
// jest.config.js in project root
const path = require('path')

module.exports = {
  reporters: [
    'default',
    [
      'tdd-guard-jest',
      {
        projectRoot: path.resolve(__dirname),
      },
    ],
  ],
}
```

If your jest config is in a workspace subdirectory, pass the absolute path to your project root:

```javascript
module.exports = {
  reporters: [
    'default',
    [
      'tdd-guard-jest',
      {
        projectRoot: '/Users/username/projects/my-app',
      },
    ],
  ],
}
```

## More Information

- Test results are saved to `.claude/tdd-guard/data/test.json`
- See [TDD Guard documentation](https://github.com/nizos/tdd-guard) for complete setup

## License

MIT
