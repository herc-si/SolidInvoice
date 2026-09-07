# Code Quality & Standards

## Required File Header

Augias forks SolidInvoice, so the tree carries two copyrights. **Which one a file
gets depends on which bundle it is in, not on who typed it.**

Bundles written for Augias — `AccountingBundle`, `BillBundle`, `CatalogBundle`,
`ElectronicInvoicingBundle`, `SupplierBundle`:

```php
<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
```

Everywhere else — bundles inherited from upstream, including new files added to
them, and `migrations/`:

```php
/*
 * This file is part of Augias project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
```

Do not hand-edit headers — both are stamped automatically. A new file simply
needs `declare(strict_types=1);`, and the fix command adds the right one.

## ECS (Easy Coding Standard)

Configs: `ecs.php` (everything) and `ecs-herc.php` (headers for the bundles
above). Two are needed because `HeaderCommentFixer` takes a single header
string. **Always run both** — the composer scripts do:

```bash
composer cs        # Check
composer cs-fix    # Fix
```

Standards: PSR-12, Symfony, PHPUnit, clean code principles

Running `bin/ecs check --fix` alone still works, but skips the header check on
those five bundles.

## PHPStan (Static Analysis)

Config: `phpstan.neon`
Level: 6
Baseline: `phpstan-baseline.neon`

```bash
bin/phpstan analyse              # With baseline
bin/phpstan analyse --no-baseline # Without baseline
```

## Rector (Refactoring)

Config: `rector.php`

Rules: PHP upgrades, Symfony best practices, Doctrine improvements, PHPUnit modernization

```bash
bin/rector process --dry-run  # Preview
bin/rector process            # Apply
```

## Pre-commit Checklist

1. `composer cs-fix`
2. `bin/phpstan analyse`
3. `bin/phpunit`
4. File header present
5. `declare(strict_types=1);`

## PHP Standards

- Always use strict types
- Always specify parameter and return types
- Prefer `final` classes
- Always specify visibility
- **Use PHP 8.1+ backed enums for fixed sets of values (status, type, etc.), NEVER class constants**
- Use class constants for configuration values, not for enum-like values

## CI/CD Checks

Every PR triggers:
- Unit tests (PHP 8.4/8.5, MySQL 8.0)
- ECS + Super-Linter
- PHPStan + Qodana
- Security checks (Composer, CodeQL)
