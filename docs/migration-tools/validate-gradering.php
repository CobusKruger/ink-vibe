<?php
// Derives gradering with the migration's own TierDeriver (read-only: derive() writes nothing).
// Run from the WordPress root of a migrated copy:
//   wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/validate-gradering.php
//
// The classes are loaded straight from this repo, so it works even when the site runs older code.

$plugin = dirname( __DIR__, 2 ) . '/wp-content/plugins/ink-core/src/';

// Load any Ink\ class from the repo on demand (a hand-kept file list went stale when TextKey was added).
spl_autoload_register(
	static function ( string $class ) use ( $plugin ): void {
		if ( 0 !== strpos( $class, 'Ink\\' ) ) {
			return;
		}
		$file = $plugin . str_replace( '\\', '/', substr( $class, 4 ) ) . '.php';
		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
);

$report = ( new Ink\Migration\TierDeriver() )->derive();

printf( "result posts read: %d | writers graded: %d (brons %d / silwer %d / goud %d) | unparsed lines: %d\n", $report['posts'], count( $report['standings'] ), $report['by_tier']['brons'], $report['by_tier']['silwer'], $report['by_tier']['goud'], $report['unparsed'] );

echo "\nUNPARSED lines (look like entries, skipped; their wins are NOT counted):\n";
foreach ( $report['unparsed_lines'] as $missed ) {
	echo "  [post {$missed['post']}] {$missed['line']}\n";
}

echo "\nFUZZY matches (check these by eye):\n";
foreach ( $report['fuzzy'] as $writer => $matched ) {
	echo "  $writer -> $matched\n";
}

echo "\nUNRESOLVED by-lines (their wins are NOT counted):\n";
foreach ( array_slice( $report['unresolved'], 0, 30, true ) as $writer => $count ) {
	echo "  $writer = $count\n";
}
