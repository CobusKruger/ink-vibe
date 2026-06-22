<?php
/**
 * PHPStan-only bootstrap.
 *
 * Declares the `ink-core` plugin constants that are defined at runtime in the
 * plugin's main file (`wp-content/plugins/ink-core/ink-core.php`) — which sits
 * OUTSIDE the analysed `src/` path. Without these declarations PHPStan reports
 * `INK_CORE_PATH` / `INK_CORE_FILE` / `INK_CORE_VERSION` / `INK_CORE_URL` as
 * undefined (`constant.notFound`) wherever `src/` references them.
 *
 * This file is loaded only via `bootstrapFiles` in `phpstan.neon`. It is never
 * shipped or loaded at runtime — WordPress loads `ink-core.php`, which defines
 * the real values. The values here exist purely so static analysis resolves the
 * symbols and their (string) types.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

defined( 'INK_CORE_VERSION' ) || define( 'INK_CORE_VERSION', '0.1.0' );
defined( 'INK_CORE_FILE' ) || define( 'INK_CORE_FILE', __DIR__ . '/wp-content/plugins/ink-core/ink-core.php' );
defined( 'INK_CORE_PATH' ) || define( 'INK_CORE_PATH', __DIR__ . '/wp-content/plugins/ink-core/' );
defined( 'INK_CORE_URL' ) || define( 'INK_CORE_URL', 'https://example.test/wp-content/plugins/ink-core/' );
