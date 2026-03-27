# ADR-007: Golangci-lint Path and Working Directory Support

## Status

Proposed

## Context

The golangci-lint integration in TDD Guard faced two specific challenges that required architectural changes to work correctly.

Key problems encountered:

1. **File-based linting limitations** - TDD Guard's default linter interface passes absolute file paths to be linted. When golangci-lint receives individual file paths, it cannot resolve other functions and types in the same Go package, leading to false undefined variable/function errors. golangci-lint is designed to work at the package/directory level, not individual files.

2. **Working directory dependency** - golangci-lint uses the current working directory to locate the project's `go.mod` file for module resolution. This TDD Guard repository is primarily TypeScript with Go test artifacts in subdirectories (`test/artifacts/go/`). When tests run from the project root, golangci-lint fails to find the correct `go.mod` files in the test artifact directories.

We considered several approaches:

1. **Add module resolution to linter interface** - Make the core linter system aware of Go modules and working directories
2. **File-based workarounds** - Continue trying to make golangci-lint work with individual files
3. **Directory-based linting with test isolation** - Use golangci-lint's natural directory mode and isolate tests that need working directory changes
4. **Separate Go project structure** - Move Go artifacts to a different repository

## Decision

We will adapt golangci-lint to work within TDD Guard's file-based linter interface using directory-based execution and test isolation, avoiding changes to the core linter architecture.

Implementation details:

**Directory-based linting:**

- Extract unique directories from provided file paths using `dirname()`
- Pass directories to golangci-lint instead of individual files
- Use `--path-mode=abs` flag to support absolute path arguments
- This allows golangci-lint to resolve Go packages correctly while maintaining the existing linter interface

**Test isolation:**

- Configure golangci-lint tests to run in Vitest's fork pool instead of threads pool
- Use `process.chdir()` in tests with proper cleanup via beforeEach/afterEach hooks
- This allows tests to change working directory to Go test artifacts without affecting other tests

Key changes:

```typescript
// Directory-based argument building
const directories = [...new Set(filePaths.map((file) => dirname(file)))]
const args = [
  'run',
  '--output.json.path=stdout',
  '--path-mode=abs',
  ...directories,
]

// Simplified issue parsing (no path resolution needed)
const issues = results.flatMap(toIssue)
```

**Rationale:** This approach was chosen because the situation (non-Go project testing Go linters) is rare and doesn't warrant adding brittle module resolution logic to the core linter system. The isolated test approach keeps the complexity contained to where it's needed.

## Consequences

### Positive

- **Go language linter support** - TDD Guard now supports the most popular Go linting solution
- **Proper package resolution** - Directory-based linting allows golangci-lint to resolve Go types and functions correctly
- **Alignment with tooling** - Uses golangci-lint as intended (package-level analysis)
- **Comprehensive validation** - Go developers get the same TDD enforcement as other languages
- **Maintainable architecture** - Avoided adding Go-specific complexity to the core linter interface

### Negative

- **Test complexity** - Requires separate Vitest project configuration and fork pool for isolation
- **Different execution model** - Golangci-lint behaves differently than file-based linters (ESLint)
- **Working directory sensitivity** - Tests must manage working directory changes carefully

### Neutral

- **Directory-based results** - May report issues in files not explicitly requested (entire package)

## Security Considerations

The golangci-lint integration performs path manipulation by extracting directories from file paths using `dirname()` and passing them as command line arguments.
