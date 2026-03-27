# ADR-006: Separate Repository for PHPUnit Reporter

## Status

Accepted

## Context

We received a contribution for a PHPUnit reporter that allows PHP developers to use TDD Guard with their PHPUnit test suites. However, when attempting to publish this package to Packagist (the PHP package registry), we encountered a fundamental limitation: Packagist requires the `composer.json` file to be at the root of the repository.

TDD Guard is organized as a monorepo containing:

- The main CLI tool (TypeScript/npm)
- Vitest reporter (TypeScript/npm)
- Pytest reporter (Python/PyPI)
- PHPUnit reporter (PHP/Packagist)

This structure works well for npm (which supports workspaces) and PyPI (which can build from subdirectories), but Packagist does not support monorepos or packages in subdirectories.

### Options Considered

1. **Move composer.json to repository root**
   - Would make the entire TDD Guard project appear as a PHP package
   - Users would download all code (TypeScript, Python, etc.) just to get the PHPUnit reporter
   - Conflicts with the project's primary identity as a CLI tool

2. **Private Packagist subscription**
   - Supports monorepos but requires paid subscription
   - Adds ongoing cost for an open-source project
   - Creates barrier for community adoption

3. **Manual installation instructions**
   - Users would need to configure Composer to use VCS repository
   - Poor developer experience
   - Reduces discoverability and adoption

4. **Separate repository with automated synchronization**
   - Maintains single source of truth in main repository
   - Provides standard Packagist installation experience
   - Can be automated with GitHub Actions

## Decision

We will create a separate repository (`tdd-guard-phpunit`) that mirrors the `reporters/phpunit` directory from the main repository. This mirror will be automatically synchronized using GitHub Actions whenever changes are pushed to the PHPUnit reporter in the main repository.

### Implementation Plan

1. **Initial Setup**
   - Create `tdd-guard-phpunit` repository
   - Use `git subtree split` to extract PHPUnit reporter history
   - Push to new repository maintaining commit history
   - Submit to Packagist for PHP package distribution

2. **Automated Synchronization**
   - GitHub Action triggered on pushes to `reporters/phpunit/**`
   - Uses git subtree to maintain clean history
   - Force pushes to mirror repository to ensure consistency
   - Syncs relevant tags (e.g., `phpunit-v*`)

3. **Clear Communication**
   - Mirror repository README clearly states it's read-only
   - Directs issues and PRs to main repository
   - Explains the monorepo structure and rationale

## Consequences

### Positive

- **Standard installation**: `composer require --dev tdd-guard/phpunit`
- **Packagist compatibility**: Full integration with PHP ecosystem
- **Automated updates**: No manual synchronization needed
- **Clean history**: Git subtree preserves relevant commit history
- **Single source of truth**: All development happens in main repository

### Negative

- **Additional complexity**: Must maintain synchronization workflow
- **Delayed updates**: Packagist updates depend on GitHub Action execution
- **Repository proliferation**: Additional repository to manage
- **Potential confusion**: Contributors might submit PRs to wrong repository

### Security Considerations

- Uses GitHub Personal Access Token (PAT) with minimal required permissions
- Token stored as repository secret, only accessible to repository admins
- Automated workflow reduces human error in synchronization
- Mirror repository can be made explicitly read-only if needed

## Future Considerations

If Packagist adds monorepo support in the future, we could deprecate the mirror repository and publish directly from the main repository. Until then, this approach provides the best balance of maintainability and user experience.

The same pattern could be applied if we need to publish other language-specific packages that don't support monorepos (e.g., RubyGems, CPAN).
