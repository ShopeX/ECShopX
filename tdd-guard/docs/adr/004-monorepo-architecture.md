# ADR-004: Monorepo Architecture for Multi-Language Support

## Status

Accepted

## Context

TDD Guard originally published a single package to both npm and PyPI, with all test framework reporters mixed together in the src directory. This created several problems:

- **Language mixing** - JavaScript and Python code in the same package
- **Publishing complexity** - Single codebase published to multiple package registries
- **Package bloat** - Users installed code for all languages even if using only one
- **Contribution barriers** - Adding new reporters required navigating the entire codebase

We considered several approaches:

1. **Keep monolithic structure** - Continue with mixed languages in one package
2. **Separate repositories** - Create individual repos for each reporter
3. **Monorepo with workspaces** - Keep one repo but separate packages

## Decision

We will restructure TDD Guard as a monorepo using npm workspaces, with each reporter as a separate package.

The new structure:

```
tdd-guard/                  # Main CLI package (npm)
├── src/                    # Core functionality and shared code
└── package.json

reporters/
├── vitest/                 # tdd-guard-vitest package (npm)
│   └── package.json
└── pytest/                 # tdd-guard-pytest package (PyPI)
    └── pyproject.toml
```

Implementation details:

- Main package exports shared functionality (Storage, Config, contracts)
- Each reporter is a standalone package with its own version
- Vitest reporter imports shared code from 'tdd-guard' package
- Python reporter is self-contained (no JavaScript dependencies)

## Consequences

### Positive

- **Clean separation** - Each language has its own package and tooling
- **Smaller packages** - Users only install what they need
- **Independent releases** - Can update reporters without touching others
- **Easier contributions** - Clear boundaries for adding new reporters

### Negative

- **Multiple packages to maintain** - More release overhead
- **Build complexity** - Must ensure correct build order during development

### Neutral

- Users now install two packages (CLI + reporter) instead of one
- Each package has its own documentation and version number
