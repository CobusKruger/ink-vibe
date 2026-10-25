<?php
// Derives gradering with the migration's own TierDeriver (read-only: derive() writes nothing) and,
// when docs/data-cleanup/winners/tier-import.csv exists on this machine, diffs it against that file.
// Run from the WordPress root of a migrated copy:
//   wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/validate-gradering.php
//
// The classes are loaded straight from this repo, so it works even when the site runs older code.

$plugin = dirname( __DIR__, 2 ) . '/wp-content/plugins/ink-core/src/';

foreach ( array( 'Kernel/Tier.php', 'Kernel/Scalar.php', 'Tiers/Api.php', 'Tiers/PromotionEngine.php', 'Content/ChallengeResultTitle.php', 'Content/ResultPostAuthors.php', 'Migration/LegacyResultsParser.php', 'Migration/WriterResolver.php', 'Migration/TierDeriver.php' ) as $file ) {
	require_once $plugin . $file;
}

$report = ( new Ink\Migration\TierDeriver() )->derive();

printf( "result posts read: %d | writers graded: %d (brons %d / silwer %d / goud %d) | unparsed lines: %d\n", $report['posts'], count( $report['standings'] ), $report['by_tier']['brons'], $report['by_tier']['silwer'], $report['by_tier']['goud'], $report['unparsed'] );

echo "\nFUZZY matches (check these by eye):\n";
foreach ( $report['fuzzy'] as $writer => $matched ) {
	echo "  $writer -> $matched\n";
}

echo "\nUNRESOLVED by-lines (their wins are NOT counted):\n";
foreach ( array_slice( $report['unresolved'], 0, 30, true ) as $writer => $count ) {
	echo "  $writer = $count\n";
}

$csv_path = dirname( __DIR__ ) . '/data-cleanup/winners/tier-import.csv';

if ( ! is_readable( $csv_path ) ) {
	echo "\n(no tier-import.csv on this machine: nothing to compare with)\n";
	return;
}

$csv   = array();
$handle = fopen( $csv_path, 'r' );
$head  = fgetcsv( $handle );

while ( ( $row = fgetcsv( $handle ) ) !== false ) {
	if ( count( $row ) === count( $head ) ) {
		$line = array_combine( $head, $row );
		$csv[ strtolower( trim( $line['email'] ) ) ] = strtolower( trim( $line['gradering'] ) );
	}
}

$derived = array();

foreach ( $report['standings'] as $user_id => $standing ) {
	$user = get_userdata( (int) $user_id );

	if ( $user ) {
		$derived[ strtolower( $user->user_email ) ] = $standing['tier']->value;
	}
}

$same = 0;
$diff = array();

foreach ( $derived as $email => $tier ) {
	if ( ! isset( $csv[ $email ] ) ) {
		$diff[] = "$email: derived $tier, not in the CSV";
	} elseif ( $csv[ $email ] === $tier ) {
		++$same;
	} else {
		$diff[] = "$email: CSV {$csv[$email]} vs derived $tier";
	}
}

foreach ( $csv as $email => $tier ) {
	if ( ! isset( $derived[ $email ] ) ) {
		$diff[] = "$email: CSV $tier, derived none (default brons)";
	}
}

printf( "\nagainst tier-import.csv: %d identical, %d differ\n", $same, count( $diff ) );
echo '  ' . implode( "\n  ", $diff ) . "\n";
