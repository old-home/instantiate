# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands
- Build: `composer build`
- Test: `composer test`
- Test without coverage: `composer test:no-report`
- Run single test: `./vendor/bin/phpunit tests/path/to/TestFile.php`
- Lint: `composer lint` (runs PHPStan and PHPCBF)
- PHPStan: `composer lint:phpstan`
- Code formatting: `composer lint:phpcbf`

## Code Style
- Follow PSR-4 autoloading standard
- Use strict typing with `declare(strict_types=1);`
- Namespace: `Graywings\Instantiate` for src, `Graywings\Instantiate\Tests\{Type}` for tests
- Tests: Use PHPUnit attributes for coverage (`#[CoversClass]`) and data providers
- DocBlocks: Document method purpose in English comments
- Type declarations: Use PHP 8 type hints for parameters and return types
- Error handling: Throw typed exceptions with descriptive messages
