# Development Guide

## Prerequisites

### Main Tests

- Node.js 22+ and npm

### Reporter Tests

- Node.js 22+ and npm
- Python 3.8+ (for pytest reporter)
- PHP 8.1+ and Composer (for PHPUnit reporter)
- Go 1.21+ (for Go reporter)
- Ruby 2.7+ and Bundler (for RSpec reporter)
- Rust 1.70+ and Cargo (for Rust reporter)
- Storybook <= 9.x (for Storybook reporter)

## Using Dev Containers

For a consistent development environment with all dependencies pre-installed, see the [devcontainer setup guide](.devcontainer/README.md).

## Building

Before running tests, install dependencies and build the TypeScript packages:

```bash
# Install dependencies
npm install

# Build the main package and all workspaces
npm run build
```

## Running Main Tests

The main test suite covers the core TDD Guard functionality:

```bash
# Run all tests
npm test

# Run unit tests only (faster)
npm run test:unit

# Run integration tests
npm run test:integration
```

## Running Reporter Tests

Reporter tests verify the language-specific test result collectors.

### Setup

First, install the language-specific dependencies:

```bash
# Install PHPUnit dependencies
composer install -d reporters/phpunit

# Set up Python virtual environment and install pytest
python3 -m venv reporters/pytest/.venv
reporters/pytest/.venv/bin/pip install -e reporters/pytest pytest

# Build Go reporter
go build -C reporters/go ./cmd/tdd-guard-go

# Install RSpec dependencies
bundle install --gemfile=reporters/rspec/Gemfile

# Build Rust reporter
cargo build --release --manifest-path reporters/rust/Cargo.toml
```

### Running Tests

```bash
# Run all reporter tests
npm run test:reporters
```

## Code Quality

The project uses ESLint, Prettier, and TypeScript for code quality:

```bash
# Run all checks (typecheck, lint, format, test)
npm run checks

# Individual commands
npm run typecheck      # Type checking
npm run lint           # Lint and auto-fix
npm run format         # Format code with Prettier
```

## Troubleshooting

### PHPUnit Issues

If you get composer errors:

- Ensure PHP 8.1+ is installed: `php --version`
- Ensure Composer is installed: `composer --version`

### Python/pytest Issues

If you get Python errors:

- Ensure Python 3.8+ is installed: `python3 --version`
- On some systems, you may need to install python3-venv: `sudo apt install python3-venv`

### Ruby/RSpec Issues

If you get Ruby errors:

- Ensure Ruby 2.7+ is installed: `ruby --version`
- Ensure Bundler is installed: `bundle --version`
- If Bundler is missing: `gem install bundler`

### Rust Issues

If you get Rust errors:

- Ensure Rust 1.70+ is installed: `rustc --version`
- Ensure Cargo is installed: `cargo --version`
- If Rust is missing: Install from [rustup.rs](https://rustup.rs/)
