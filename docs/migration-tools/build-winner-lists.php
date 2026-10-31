<?php
// Keeps the reviewed winner lists in the migration-data folder up to date. The lists are the source
// of truth: hand edits stand. Each run only adds and fills, and reads the database only:
//   - result posts not yet in result-posts.csv are read (parser + July list) and their wins appended;
//   - a win with no post, or no account, gets one if it can now be found;
//   - challenge ids, activity and counts are refreshed; review lists are rewritten.
// It never changes a filled post, account, category, grade or position, and it never rebuilds. To change those, edit the CSV.
//   wp eval-file <repo>/docs/migration-tools/build-winner-lists.php
// Set WL_OUT to work on a copy of the lists. The previous lists are kept in backup/.
use Ink\Content\ChallengeResultTitle as T;
use Ink\Content\ChallengeRound;
use Ink\Kernel\MigrationData;
use Ink\Migration\LegacyResultsParser as P;
use Ink\Migration\TextKey;
use Ink\Migration\TierDeriver;
use Ink\Migration\WriterResolver;
use Ink\Tiers\Api;
use Ink\Tiers\PromotionEngine;

const WL_ENTRY_WINDOW  = 150 * DAY_IN_SECONDS;  // A result follows closing by at most about 3 months (owner); rounds run about a month.
const WL_ACTIVE_WINDOW = YEAR_IN_SECONDS;       // Nothing posted for a year: best effort, nobody reviews it (owner, 2026-10-31).
const WL_JULY_END      = '2026-07-01';          // Later result posts are new, so the July list cannot have them.
const WL_SAME_PERSON   = array( 168 => array( 69 ) ); // Palet, and her second account (owner, 2026-10-31).
const WL_FRANCOIS      = 46;                    // Only ever posted the 13 posts up to 2019-09-10 (owner).
const WL_GEDAGTES      = 66;                    // A different writer, who also appears as "Francois Meyer".
const WL_FRANCOIS_LAST = '2019-09-10';
const WL_SITE_OWNER    = 36;                    // Anzé removed her own posts and that is expected (owner).

global $wpdb;

$data = MigrationData::dir();
$out  = getenv( 'WL_OUT' ) ?: $data;
if ( '' === $out ) {
	WP_CLI::error( 'INK_MIGRATION_DATA_DIR is not set.' );
}

function wl_read( string $path ): array {
	if ( ! is_readable( $path ) ) { return array(); }
	$f    = fopen( $path, 'r' );
	$head = fgetcsv( $f );
	$head[0] = ltrim( (string) $head[0], "\xEF\xBB\xBF" );
	$rows = array();
	while ( false !== ( $l = fgetcsv( $f ) ) ) {
		if ( count( $l ) === count( $head ) ) { $rows[] = array_combine( $head, $l ); }
	}
	fclose( $f );
	return $rows;
}
$cur_wins = wl_read( "$out/wenners.csv" );
$cur_rp   = array_column( wl_read( "$out/result-posts.csv" ), null, 'post_id' );
$cur_ch   = wl_read( "$out/challenges.csv" );
if ( ! $cur_wins || ! $cur_rp || ! $cur_ch ) {
	WP_CLI::error( "wenners.csv, result-posts.csv and challenges.csv must exist in $out. They are curated and are never rebuilt: restore them from backup/." );
}
$deleted = array();
foreach ( wl_read( "$data/writer-deleted.csv" ) as $r ) { $deleted[ P::nameKey( $r['name'] ) ] = true; }
// Questions the owner has answered are not asked again.
$answered = array();
foreach ( glob( "$out/review/accounts-confirmed-*.csv" ) ?: array() as $file ) {
	foreach ( wl_read( $file ) as $r ) { $answered[ P::nameKey( $r['byline'] ) . '|' . TextKey::title( $r['title'] ) ] = true; }
}

function wl_base( string $s ): string {
	$s = mb_strtolower( html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$s = (string) preg_replace( '/\(?\d+\s*%\)?/u', ' ', $s );
	$s = (string) preg_replace( '/^\s*(gedig|verhaal|storie|artikel|rubriek|prosa|mening|meningstuk)\s*[:\-–]\s*/u', ' ', $s );
	return TextKey::title( $s );
}
function wl_close( string $a, string $b ): bool {
	if ( '' === $a || '' === $b ) { return false; }
	$long = max( strlen( $a ), strlen( $b ) );
	if ( min( strlen( $a ), strlen( $b ) ) >= 6 && ( str_contains( $b, $a ) || str_contains( $a, $b ) ) && min( strlen( $a ), strlen( $b ) ) >= 0.6 * $long ) { return true; }
	return $long >= 5 && $long <= 250 && levenshtein( $a, $b ) <= max( 1, (int) floor( $long * 0.2 ) );
}
function wl_write( string $path, array $head, array $rows ): void {
	$f = fopen( $path, 'w' );
	fwrite( $f, "\xEF\xBB\xBF" ); // Excel needs the BOM for Afrikaans characters.
	fputcsv( $f, $head );
	foreach ( $rows as $r ) {
		fputcsv( $f, array_map( static fn( $k ) => $r[ $k ] ?? '', $head ) );
	}
	fclose( $f );
}

$td = new class() extends TierDeriver {
	public function d() { return $this->directory(); }
	public function a() { return $this->aliasMap(); }
	public function p() { return $this->resultPosts(); }
};
$dir      = $td->d();
$aliases  = $td->a();
$resolver = new WriterResolver( $dir['users'], $aliases );
$known    = array_merge( $dir['names'], array_keys( $aliases ) );

// Rounds, and which round each post was entered in. A round's id changes with every migration; its prod category does not.
$rounds = array(); $src_of = array(); $id_of = array();
foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_title, p.post_date, m.meta_value src FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key=%s WHERE p.post_type='uitdaging' AND p.post_status <> 'auto-draft' ORDER BY p.post_date", 'ink_uitdaging_source_category' ) ) as $u ) {
	$u->title = html_entity_decode( $u->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$u->t     = strtotime( $u->post_date );
	// Competitions are not challenges (owner, 2026-10-31), so they are not listed or matched.
	if ( ChallengeRound::isCompetition( $u->title ) ) { continue; }
	$u->kind  = 1 === preg_match( '/slypskool/iu', $u->title ) ? 'slypskool' : 'monthly';
	$rounds[ (int) $u->ID ] = $u;
	$src_of[ (int) $u->ID ] = (string) $u->src;
	$id_of[ (string) $u->src ] = (int) $u->ID;
}
$with_round = static function ( array $r ) use ( $src_of, $id_of, $rounds ): array {
	$src = (string) ( $r['source_category'] ?? '' );
	if ( '' === $src && '' !== (string) ( $r['challenge_id'] ?? '' ) ) { $src = $src_of[ (int) $r['challenge_id'] ] ?? ''; }
	$r['source_category'] = $src;
	$id = $id_of[ $src ] ?? 0;
	$r['challenge_id'] = $id ?: '';
	if ( $id ) { $r['challenge'] = $rounds[ $id ]->title; }
	return $r;
};
$cur_wins = array_map( $with_round, $cur_wins );
$cur_rp   = array_map( $with_round, $cur_rp );
$post_rounds = array();
$round_posts = array();
foreach ( $wpdb->get_results( "SELECT tr.object_id o, t.slug FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt USING (term_taxonomy_id) JOIN {$wpdb->terms} t USING (term_id) WHERE tt.taxonomy='uitdagingsrondte'" ) as $r ) {
	if ( 1 === preg_match( '/^uitdaging-(\d+)$/', $r->slug, $m ) ) {
		$post_rounds[ (int) $r->o ][ (int) $m[1] ] = true;
		$round_posts[ (int) $m[1] ][]              = (int) $r->o;
	}
}
$links = array();
foreach ( $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key='ink_wenneraankondiging_uitdaging'" ) as $m ) {
	$links[ (int) $m->post_id ] = (int) $m->meta_value;
}

// Result posts, read in date order.
$result_posts = array();
foreach ( $td->p() as $rp ) {
	$p = get_post( $rp->id );
	$result_posts[ (int) $rp->id ] = (object) array(
		'id'    => (int) $rp->id,
		'html'  => $rp->html,
		'title' => html_entity_decode( $p->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		'date'  => substr( $p->post_date, 0, 10 ),
		't'     => strtotime( $p->post_date ),
		'slug'  => $p->post_name,
	);
}
uasort( $result_posts, static fn( $a, $b ) => $a->t <=> $b->t );

// All writing that could have been entered.
$skip     = "'revision','attachment','nav_menu_item','uitdaging','page','wp_template','wp_template_part','wp_global_styles','wp_navigation','custom_css','customize_changeset','oembed_cache','user_request','wp_block'";
$by_owner = array();
foreach ( $wpdb->get_results( "SELECT ID, post_author, post_title, post_status, post_date FROM {$wpdb->posts} WHERE post_type NOT IN ($skip) AND post_title <> ''" ) as $p ) {
	if ( isset( $result_posts[ (int) $p->ID ] ) ) { continue; }
	$p->k = wl_base( $p->post_title );
	$p->t = strtotime( $p->post_date );
	$by_owner[ (int) $p->post_author ][] = $p;
}
$login = static function ( int $id ) { $u = get_userdata( $id ); return $u ? $u->user_login : ''; };

// An uploaded image can carry the win's title while the poem itself has a longer one ("Karma (Limeriek)").
$attached = array();
foreach ( $wpdb->get_results( "SELECT a.post_author, a.post_title, a.post_date, p.ID, p.post_author AS parent_author, p.post_title AS parent_title, p.post_status, p.post_date AS parent_date FROM {$wpdb->posts} a JOIN {$wpdb->posts} p ON p.ID = a.post_parent WHERE a.post_type='attachment' AND a.post_title <> '' AND p.post_status='publish' AND p.post_type NOT IN ($skip)" ) as $a ) {
	if ( (int) $a->post_author !== (int) $a->parent_author ) { continue; }
	$attached[ (int) $a->post_author ][ wl_base( $a->post_title ) ][] = (object) array( 'ID' => $a->ID, 'post_author' => $a->post_author, 'post_title' => $a->parent_title, 'post_status' => $a->post_status, 't' => strtotime( $a->parent_date ) );
}
// Published writing by title, for wins whose own by-line finds nothing.
$by_title = array();
foreach ( $by_owner as $posts ) {
	foreach ( $posts as $p ) { if ( 'publish' === $p->post_status && '' !== $p->k ) { $by_title[ $p->k ][] = $p; } }
}

// The July list, by result post.
$july_path = dirname( __DIR__, 3 ) . '/data-cleanup/winners/winner-list.csv';
if ( ! is_readable( $july_path ) ) {
	WP_CLI::error( "July list not found: $july_path" );
}
$h    = fopen( $july_path, 'r' );
$head = fgetcsv( $h );
$head[0] = ltrim( (string) $head[0], "\xEF\xBB\xBF" );
$july = array();
while ( false !== ( $l = fgetcsv( $h ) ) ) {
	$e = array_combine( $head, $l );
	$july[ wl_base( $e['Heading'] ) ][] = $e;
}
fclose( $h );

// Writing that a by-line could belong to.
$authors_for = static function ( int $uid ): array {
	if ( WL_FRANCOIS === $uid ) { return array( WL_FRANCOIS, WL_GEDAGTES ); }
	return array_merge( array( $uid ), WL_SAME_PERSON[ $uid ] ?? array() );
};
$find = static function ( int $uid, string $title, int $when ) use ( $by_owner, $authors_for ): array {
	$exact = array(); $typo = array();
	$key   = wl_base( $title );
	foreach ( $authors_for( $uid ) as $a ) {
		foreach ( $by_owner[ $a ] ?? array() as $p ) {
			if ( $p->t > $when + DAY_IN_SECONDS || $when - $p->t > WL_ENTRY_WINDOW ) { continue; }
			// The writer's post may add to the title: "Die onderhoud (Geskiedenisfiksie)".
			if ( $key === $p->k ) { $exact[] = $p; } elseif ( wl_close( $key, $p->k ) || ( strlen( $key ) >= 6 && str_starts_with( $p->k, $key ) ) ) { $typo[] = $p; }
		}
	}
	return array( $exact, $typo );
};

// First pass: combine parser and July list per post; winners' posts vote for the round.
$wins = array(); $post_info = array(); $votes = array();
foreach ( $result_posts as $id => $rp ) {
	if ( isset( $cur_rp[ $id ] ) || ChallengeRound::isCompetition( $rp->title ) ) { continue; }
	$parsed  = P::parse( $rp->html, $known );
	$listed  = $july[ wl_base( $rp->title ) ] ?? array();
	$on_july = array() !== $listed;
	$post_info[ $id ] = array( 'parsed' => count( $parsed['rows'] ), 'unparsed' => count( $parsed['unparsed'] ), 'july' => count( $listed ) );

	$used = array();
	foreach ( $parsed['rows'] as $row ) {
		$pair = null;
		$key  = wl_base( $row['title'] );
		foreach ( array( 'exact', 'close' ) as $mode ) {
			foreach ( $listed as $i => $e ) {
				if ( isset( $used[ $i ] ) ) { continue; }
				$jk = wl_base( $e['Title'] );
				if ( 'exact' === $mode ? $jk === $key : wl_close( $jk, $key ) ) { $pair = $i; break 2; }
			}
		}
		$win = array(
			'rp' => $id, 'category' => $row['category'], 'grade' => $row['grading'], 'position' => $row['position'],
			'byline' => $row['writer'], 'title' => $row['title'], 'source' => 'parser', 'notes' => array(), 'diff' => array(),
		);
		if ( null !== $pair ) {
			$used[ $pair ] = true;
			$e             = $listed[ $pair ];
			$win['source'] = 'both';
			foreach ( array( 'category' => 'Category', 'grade' => 'Grading', 'position' => 'Position' ) as $k => $j ) {
				if ( (string) $win[ $k ] !== (string) $e[ $j ] ) { $win['diff'][] = "$k: July \"{$e[$j]}\", parser \"{$win[$k]}\""; $win[ $k ] = $e[ $j ]; }
			}
			if ( P::nameKey( $win['byline'] ) !== P::nameKey( $e['Writer'] ) ) { $win['diff'][] = "writer: July \"{$e['Writer']}\", parser \"{$win['byline']}\""; $win['byline'] = $e['Writer']; }
			if ( wl_base( $win['title'] ) !== wl_base( $e['Title'] ) ) { $win['diff'][] = "title: July \"{$e['Title']}\", parser \"{$win['title']}\""; $win['title'] = $e['Title']; }
		}
		$wins[] = $win;
	}
	foreach ( $listed as $i => $e ) {
		if ( ! isset( $used[ $i ] ) ) {
			// A tie the parser ran together ("Hartsdief EN No 17 – X met Hok") is replaced by the list's rows.
			$jk = wl_base( $e['Title'] );
			foreach ( $wins as $n => $w ) {
				if ( $id === $w['rp'] && 'parser' === $w['source'] && strlen( $jk ) >= 3 && strlen( wl_base( $w['title'] ) ) > strlen( $jk ) && str_contains( wl_base( $w['title'] ), $jk ) ) {
					unset( $wins[ $n ] );
				}
			}
			$wins[] = array(
				'rp' => $id, 'category' => $e['Category'], 'grade' => $e['Grading'], 'position' => $e['Position'],
				'byline' => $e['Writer'], 'title' => $e['Title'], 'source' => 'july', 'notes' => array(), 'diff' => array(),
			);
		}
	}
	$post_info[ $id ]['on_july'] = $on_july;
}
foreach ( $wins as $w ) {
	$hit = $resolver->resolve( $w['byline'] );
	if ( ! $hit ) { continue; }
	list( $exact, $typo ) = $find( (int) $hit['user'], $w['title'], $result_posts[ $w['rp'] ]->t );
	foreach ( array_merge( $exact, $typo ) as $p ) {
		foreach ( array_keys( $post_rounds[ (int) $p->ID ] ?? array() ) as $r ) {
			$votes[ $w['rp'] ][ $r ] = ( $votes[ $w['rp'] ][ $r ] ?? 0 ) + 1;
		}
	}
}

// Each result post's round: winners' posts, title month and the date rule must agree.
$chosen = array(); $rp_rows = array_values( $cur_rp ); $new_rp = 0;
foreach ( $cur_rp as $id => $r ) { $chosen[ (int) $id ] = (int) $r['challenge_id']; }
foreach ( $result_posts as $id => $rp ) {
	if ( isset( $cur_rp[ $id ] ) ) { continue; }
	++$new_rp;
	$url = 'https://ink.org.za/' . $rp->slug . '/';
	// Competitions are not challenges (owner): listed once as outside, so they are not read again.
	if ( ChallengeRound::isCompetition( $rp->title ) ) {
		$rp_rows[] = array( 'post_id' => $id, 'date' => $rp->date, 'title' => $rp->title, 'url' => $url, 'status' => 'outside', 'notes' => 'Competition: not a challenge, not counted.' );
		continue;
	}
	$info = $post_info[ $id ];
	$row  = array(
		'post_id' => $id, 'date' => $rp->date, 'title' => $rp->title, 'url' => $url,
		'existing_link' => $links[ $id ] ?? '', 'parsed_rows' => $info['parsed'], 'july_rows' => $info['july'], 'unparsed_lines' => $info['unparsed'],
	);
	$eligible = array_filter( $rounds, static fn( $u ) => $u->t <= $rp->t && $rp->t - $u->t <= WL_ENTRY_WINDOW );
	$v        = array_intersect_key( $votes[ $id ] ?? array(), $eligible );
	arsort( $v );
	$top      = array_key_first( $v );
	$tied     = count( $v ) > 1 && array_values( $v )[0] === array_values( $v )[1];

	// A year-less title takes the year of the most recent such month before the post.
	$month = null;
	foreach ( array( 'januarie' => 1, 'februarie' => 2, 'maart' => 3, 'april' => 4, 'mei' => 5, 'junie' => 6, 'julie' => 7, 'augustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12 ) as $name => $n ) {
		if ( 1 === preg_match( '/\b' . $name . '\b/iu', $rp->title ) ) { $month = $n; break; }
	}
	$by_month = array();
	if ( null !== $month ) {
		$y = (int) gmdate( 'Y', $rp->t );
		if ( $month > (int) gmdate( 'n', $rp->t ) ) { --$y; }
		foreach ( $eligible as $u ) {
			if ( 'monthly' !== $u->kind ) { continue; }
			$my = T::monthYear( $u->title );
			if ( $my && $my[0] === $month && $my[1] === $y ) { $by_month[] = (int) $u->ID; continue; }
			$mo = (int) gmdate( 'n', $u->t + 5 * DAY_IN_SECONDS );
			if ( ( ! $my ) && $mo === $month && (int) gmdate( 'Y', $u->t + 5 * DAY_IN_SECONDS ) === $y ) { $by_month[] = (int) $u->ID; }
		}
	}
	$existing = (int) ( $links[ $id ] ?? 0 );
	$notes    = array();
	$pick     = 0;
	if ( null !== $top && ! $tied ) { $pick = (int) $top; }
	elseif ( 1 === count( $by_month ) ) { $pick = $by_month[0]; }
	elseif ( $existing ) { $pick = $existing; }

	$status = 'ok';
	if ( ! $pick ) { $status = 'review'; $notes[] = 'No round found.'; }
	if ( $pick && $existing && $pick !== $existing ) { $status = 'review'; $notes[] = 'Winners\' posts point to a different round than the current link (' . html_entity_decode( get_the_title( $existing ) ) . ').'; }
	if ( $pick && $by_month && ! in_array( $pick, $by_month, true ) ) { $status = 'review'; $notes[] = 'Round differs from the month in the title.'; }
	if ( $pick && ! $existing && ! $by_month && ( $v[ $pick ] ?? 0 ) < 2 ) { $status = 'review'; $notes[] = 'Only weak evidence for this round.'; }
	if ( 0 === $info['parsed'] + $info['july'] ) { $status = 'review'; $notes[] = 'No winners read from this post.'; }
	if ( ! $info['july'] && $rp->date < WL_JULY_END ) { $notes[] = 'Not on the July list.'; }
	$chosen[ $id ] = $pick;
	$row += array(
		'challenge_id' => $pick ?: '', 'source_category' => $src_of[ $pick ] ?? '', 'challenge' => $pick ? $rounds[ $pick ]->title : '',
		'how' => ! $pick ? '' : ( $pick === $existing ? 'existing link' : 'proposed' ),
		'winner_post_votes' => $v[ $pick ] ?? 0, 'status' => $status, 'notes' => implode( ' ', $notes ),
	);
	$rp_rows[] = $row;
}

// A win already listed but missing its post or account gets another look.
foreach ( $cur_wins as $i => $r ) {
	if ( ( '' === $r['post_id'] || '' === $r['user_id'] ) && isset( $result_posts[ (int) $r['result_post'] ] ) ) {
		$wins[] = array(
			'rp' => (int) $r['result_post'], 'category' => $r['category'], 'grade' => $r['grade'], 'position' => $r['position'],
			'byline' => $r['byline'], 'title' => $r['title'], 'source' => $r['source'], 'notes' => array(), 'diff' => array(), 'cur' => $i,
		);
	}
}

$now       = time();
$is_active = static function ( int $uid ) use ( $by_owner, $authors_for, $now ): bool {
	foreach ( $authors_for( $uid ) as $a ) {
		foreach ( $by_owner[ $a ] ?? array() as $p ) { if ( 'publish' === $p->post_status && $now - $p->t <= WL_ACTIVE_WINDOW ) { return true; } }
	}
	return false;
};
$old_mismatch = wl_read( "$out/review/july-list-mismatches.csv" );
$disputed     = array();
foreach ( $old_mismatch as $r ) { $disputed[ $r['result_date'] . '|' . TextKey::title( $r['title'] ) ] = true; }

// Second pass: settle each win's account and post.
$rows = array(); $mismatch = $old_mismatch; $confirm = array(); $filled = array(); $new_posts = 0; $new_accounts = 0;
foreach ( $wins as $w ) {
	$rp    = $result_posts[ $w['rp'] ];
	$round = $chosen[ $w['rp'] ] ?? 0;
	$hit   = $resolver->resolve( $w['byline'] );
	$listed_uid = isset( $w['cur'] ) ? (int) $cur_wins[ $w['cur'] ]['user_id'] : 0;
	$uid   = $listed_uid ?: ( $hit ? (int) $hit['user'] : 0 );
	$notes = $w['notes'];
	$status = 'ok';
	$post  = null;
	$match = 'none';
	$suggest = array();

	if ( $uid ) {
		list( $exact, $typo ) = $find( $uid, $w['title'], $rp->t );
		$live = static fn( array $l ) => array_values( array_filter( $l, static fn( $p ) => 'publish' === $p->post_status ) );
		$in   = static fn( array $l ) => array_values( array_filter( $l, static fn( $p ) => isset( $post_rounds[ (int) $p->ID ][ $round ] ) ) );
		$lx   = $live( $exact );
		$lt   = $live( $typo );
		if ( 1 === count( $in( $lx ) ) ) { $post = $in( $lx )[0]; $match = 'title+round'; }
		elseif ( 1 === count( $lx ) ) { $post = $lx[0]; $match = 'title'; }
		elseif ( count( $lx ) > 1 ) { $match = 'several'; $status = 'review'; $suggest = $lx; $notes[] = 'Several posts fit; pick one.'; }
		elseif ( 1 === count( $in( $lt ) ) ) { $post = $in( $lt )[0]; $match = 'close-title+round'; }
		elseif ( 1 === count( $lt ) ) { $post = $lt[0]; $match = 'close-title'; $status = 'review'; $notes[] = 'Title only close, and the post is not filed under this round.'; }
		elseif ( count( $lt ) > 1 ) { $match = 'several'; $status = 'review'; $suggest = $lt; $notes[] = 'Several posts fit; pick one.'; }
		elseif ( $exact || $typo ) { $dead = array_merge( $exact, $typo )[0]; $notes[] = "Post is not published: #{$dead->ID} ({$dead->post_status})."; }

		// The writer's own post in this round, with one title inside the other: "Villanelle: Na jou asem weg is".
		if ( ! $post && 'several' !== $match && $round ) {
			$key  = wl_base( $w['title'] );
			$held = array();
			foreach ( $authors_for( $uid ) as $a ) {
				foreach ( $by_owner[ $a ] ?? array() as $p ) {
					if ( 'publish' === $p->post_status && isset( $post_rounds[ (int) $p->ID ][ $round ] ) && min( strlen( $key ), strlen( $p->k ) ) >= 6 && ( str_contains( $p->k, $key ) || str_contains( $key, $p->k ) ) ) { $held[] = $p; }
				}
			}
			if ( 1 === count( $held ) ) { $post = $held[0]; $match = 'contained+round'; }
		}

		// Nothing in the window. The writer's own exact title still identifies the post: some were reposted later (owner, 2026-10-31).
		if ( ! $post && 'several' !== $match ) {
			$key = wl_base( $w['title'] );
			$own = array();
			foreach ( $authors_for( $uid ) as $a ) {
				foreach ( $attached[ $a ][ $key ] ?? array() as $p ) { $own[ (int) $p->ID ] = $p; }
			}
			if ( 1 === count( $own ) ) {
				$post  = reset( $own );
				$match = 'attachment-parent';
				$notes[] = 'Matched through the image attached to the post.';
			} else {
				$own = array();
				foreach ( $authors_for( $uid ) as $a ) {
					foreach ( $by_owner[ $a ] ?? array() as $p ) { if ( $key === $p->k && 'publish' === $p->post_status ) { $own[] = $p; } }
				}
				if ( 1 === count( $own ) ) {
					$post  = $own[0];
					$match = 'title-any-date';
					$notes[] = 'Post dated ' . gmdate( 'Y-m-d', $post->t ) . ', outside this round; probably reposted.';
				} elseif ( count( $own ) > 1 ) {
					$match   = 'several';
					$status  = 'review';
					$suggest = $own;
					$notes[] = 'Several posts fit; pick one.';
				} elseif ( isset( $dead ) && $key === $dead->k ) {
					$post  = $dead;
					$match = 'title-unpublished';
				}
			}
		}
		unset( $dead );
	}

	// A by-line with no post of its own: the same title in the window, by any account, may name the writer.
	$same_title = array();
	if ( ! $post && 'several' !== $match ) {
		foreach ( isset( $answered[ P::nameKey( $w['byline'] ) . '|' . TextKey::title( $w['title'] ) ] ) ? array() : ( $by_title[ wl_base( $w['title'] ) ] ?? array() ) as $p ) {
			if ( $p->t <= $rp->t + DAY_IN_SECONDS && $rp->t - $p->t <= WL_ENTRY_WINDOW ) { $same_title[] = $p; }
		}
		if ( $same_title ) {
			$notes[] = 'Same title in this round\'s dates, by another account: ' . implode( ', ', array_map( static fn( $p ) => "#{$p->ID} by " . $login( (int) $p->post_author ) . ' (#' . (int) $p->post_author . ')', array_slice( $same_title, 0, 3 ) ) ) . '.';
		}
	}

	// The by-line "Francois Meyer" covers two writers (owner): the post decides, else the date.
	if ( WL_FRANCOIS === $uid && ! $listed_uid ) {
		if ( $post ) { $uid = (int) $post->post_author; }
		else {
			$uid      = $rp->date > WL_FRANCOIS_LAST ? WL_GEDAGTES : WL_FRANCOIS;
			$status   = 'review';
			$notes[]  = 'By-line "Francois Meyer" and no post: account chosen by date (Francois Meyer stopped posting ' . WL_FRANCOIS_LAST . ').';
		}
	}
	foreach ( WL_SAME_PERSON as $main => $others ) { if ( in_array( (int) ( $post->post_author ?? 0 ), $others, true ) ) { $notes[] = "Post sits on the account being merged into #$main."; } }

	if ( ! $uid ) {
		if ( isset( $deleted[ P::nameKey( $w['byline'] ) ] ) ) { $notes[] = 'Account deleted (owner): recorded, nothing to promote.'; }
		else { $status = 'review'; $notes[] = 'No account for this by-line.'; }
	}
	if ( ! $post && 'several' !== $match ) {
		if ( WL_SITE_OWNER === $uid ) { $notes[] = 'Site owner; removed own posts (owner).'; }
		elseif ( $uid ) { $status = 'review'; }
		if ( $round && $uid ) {
			foreach ( $round_posts[ $round ] ?? array() as $pid ) {
				foreach ( $authors_for( $uid ) as $a ) {
					foreach ( $by_owner[ $a ] ?? array() as $p ) {
						if ( (int) $p->ID === $pid && 'publish' === $p->post_status ) { $suggest[] = $p; }
					}
				}
			}
		}
	}
	if ( (int) $w['position'] > 3 ) { $status = 'review'; $notes[] = 'Position below 3.'; }
	if ( $w['diff'] ) { $status = 'review'; }
	if ( 'july' === $w['source'] || ( 'parser' === $w['source'] && ! empty( $post_info[ $w['rp'] ]['on_july'] ) && $rp->date < WL_JULY_END ) ) { $status = 'review'; }

	if ( WL_SITE_OWNER !== $uid ) {
		foreach ( $same_title as $p ) {
			if ( ! $is_active( $uid ?: (int) $p->post_author ) ) { continue; }
			$confirm[] = array(
				'same_person' => '', 'byline' => $w['byline'], 'by_line_account' => $uid ? $login( $uid ) : '', 'post_account' => $login( (int) $p->post_author ), 'post_account_id' => (int) $p->post_author,
				'post_account_name' => get_userdata( (int) $p->post_author )->display_name ?? '', 'title' => $w['title'], 'post_id' => $p->ID, 'post_date' => gmdate( 'Y-m-d', $p->t ), 'category' => $w['category'],
				'result_date' => $rp->date, 'challenge' => $round ? $rounds[ $round ]->title : '', 'result_url' => 'https://ink.org.za/' . $rp->slug . '/',
			);
		}
	}

	$sg = implode( ' ; ', array_map( static fn( $p ) => "#{$p->ID} " . html_entity_decode( $p->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), array_slice( $suggest, 0, 6 ) ) );

	// A listed win only gains what it lacked; everything else on the row stands.
	if ( isset( $w['cur'] ) ) {
		$c = &$cur_wins[ $w['cur'] ];
		if ( '' === $c['user_id'] && $uid ) {
			$c['user_id']    = $uid;
			$c['user_login'] = $login( $uid );
			++$new_accounts;
		}
		if ( '' === $c['post_id'] ) {
			if ( $post ) {
				$c['post_id']     = $post->ID;
				$c['post_title']  = html_entity_decode( $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$c['match']       = $match;
				$c['notes']       = implode( ' ', $notes );
				$c['suggestions'] = '';
				++$new_posts;
				if ( 'ok' === $status ) { $filled[ $w['cur'] ] = true; }
			} elseif ( '' !== $sg ) {
				$c['suggestions'] = $sg;
			}
		}
		unset( $c );
		continue;
	}

	$row = array(
		'status' => $status, 'notes' => implode( ' ', $notes ), 'result_post' => $w['rp'], 'result_date' => $rp->date,
		'challenge_id' => $round ?: '', 'source_category' => $src_of[ $round ] ?? '', 'challenge' => $round ? $rounds[ $round ]->title : '',
		'category' => $w['category'], 'grade' => $w['grade'], 'position' => $w['position'], 'byline' => $w['byline'],
		'user_id' => $uid ?: '', 'user_login' => $uid ? $login( $uid ) : '', 'title' => $w['title'],
		'post_id' => $post ? $post->ID : '', 'post_title' => $post ? html_entity_decode( $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '',
		'match' => $match, 'source' => $w['source'], 'suggestions' => $sg, 'result_url' => 'https://ink.org.za/' . $rp->slug . '/',
	);
	$rows[] = $row;

	if ( $rp->date < WL_JULY_END ) {
		$type = '';
		if ( 'july' === $w['source'] ) { $type = 'On the July list, parser did not read it'; }
		elseif ( 'parser' === $w['source'] ) { $type = empty( $post_info[ $w['rp'] ]['on_july'] ) ? 'Result post not on the July list at all' : 'Parser read it, not on the July list'; }
		elseif ( $w['diff'] ) { $type = 'On both, details differ'; }
		if ( '' !== $type ) { $mismatch[] = array( 'type' => $type, 'differences' => implode( ' | ', $w['diff'] ) ) + $row; }
	}
}

// Listed and new wins together. Only the people still writing get reviewed (owner): for anyone else,
// a win whose post can't be identified stays counted and we move on. A status someone set to ok stands.
$new_wins = count( $rows );
foreach ( $cur_wins as $i => $r ) {
	if ( isset( $filled[ $i ] ) && ! isset( $disputed[ $r['result_date'] . '|' . TextKey::title( $r['title'] ) ] ) && (int) $r['position'] <= 3 ) { $r['status'] = 'ok'; }
	$rows[] = $r;
}
$a_year_ago = gmdate( 'Y-m-d', $now - WL_ACTIVE_WINDOW );
foreach ( $rows as &$r ) {
	$r['activity'] = '' === (string) $r['user_id'] ? 'no-account' : ( $is_active( (int) $r['user_id'] ) ? 'active' : 'inactive' );
	if ( 'review' === $r['status'] || 'best-effort' === $r['status'] ) {
		$live = 'active' === $r['activity'] || ( 'no-account' === $r['activity'] && $r['result_date'] >= $a_year_ago );
		$r['status'] = $live ? 'review' : 'best-effort';
	}
}
unset( $r );

$sort = static fn( $a, $b ) => array( $a['result_date'], $a['category'], $a['grade'], (int) $a['position'] ) <=> array( $b['result_date'], $b['category'], $b['grade'], (int) $b['position'] );
usort( $rows, $sort );
$missing = array_values( array_filter( $rows, static fn( $r ) => '' === (string) $r['post_id'] && 'review' === $r['status'] ) );
$to_check = array();
foreach ( $rows as $r ) { $to_check[ $r['result_date'] . '|' . TextKey::title( $r['title'] ) ] = $r; }
foreach ( $mismatch as &$m ) {
	$r = $to_check[ $m['result_date'] . '|' . TextKey::title( $m['title'] ) ] ?? null;
	$m['status']   = $r['status'] ?? '';
	$m['activity'] = $r['activity'] ?? '';
	$m['post_id']  = $r['post_id'] ?? $m['post_id'];
}
unset( $m );
usort( $mismatch, static fn( $a, $b ) => array( 'review' === $a['status'] ? 0 : 1, $a['type'], $a['result_date'] ) <=> array( 'review' === $b['status'] ? 0 : 1, $b['type'], $b['result_date'] ) );
usort( $rp_rows, static fn( $a, $b ) => $a['date'] <=> $b['date'] );

// Challenges: counts are refreshed; a status or note already in the list stands.
$cur_ch_by = array();
foreach ( $cur_ch as $r ) {
	$src = (string) ( $r['source_category'] ?? '' );
	if ( '' === $src && '' !== (string) ( $r['challenge_id'] ?? '' ) ) { $src = $src_of[ (int) $r['challenge_id'] ] ?? ''; }
	$cur_ch_by[ $src ] = $r;
}
$ch_rows = array();
$by_round = array();
foreach ( $rp_rows as $r ) { if ( ! empty( $r['challenge_id'] ) ) { $by_round[ (int) $r['challenge_id'] ][] = $r['post_id']; } }
$win_count = array();
foreach ( $rows as $r ) { if ( '' !== $r['challenge_id'] ) { $win_count[ (int) $r['challenge_id'] ] = ( $win_count[ (int) $r['challenge_id'] ] ?? 0 ) + 1; } }
foreach ( $rounds as $id => $u ) {
	$has    = $by_round[ $id ] ?? array();
	$status = 'ok';
	$notes  = '';
	if ( 'monthly' === $u->kind && ! $has && $u->t < strtotime( '-3 months' ) ) { $status = 'review'; $notes = 'Monthly round with no result post.'; }
	if ( count( $has ) > 1 ) { $status = 'review'; $notes = 'More than one result post.'; }
	if ( isset( $cur_ch_by[ $src_of[ $id ] ] ) ) { $status = $cur_ch_by[ $src_of[ $id ] ]['status']; $notes = $cur_ch_by[ $src_of[ $id ] ]['notes']; }
	$ch_rows[] = array(
		'challenge_id' => $id, 'source_category' => $src_of[ $id ], 'title' => $u->title, 'opened' => substr( $u->post_date, 0, 10 ), 'kind' => $u->kind,
		'entries' => count( $round_posts[ $id ] ?? array() ), 'result_posts' => implode( ' ', $has ), 'wins' => $win_count[ $id ] ?? 0,
		'status' => $status, 'notes' => $notes,
	);
}

// Grades, if every win in this draft stands.
$totals = array();
foreach ( $rows as $r ) { if ( 'Gedigte' === $r['category'] && '' !== $r['user_id'] ) { $totals[ (int) $r['user_id'] ] = ( $totals[ (int) $r['user_id'] ] ?? 0 ) + 1; } }
$grade_rows = array();
$now_users  = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key=%s AND meta_value<>''", 'ink_tier_win_count' ) );
foreach ( array_unique( array_merge( array_keys( $totals ), array_map( 'intval', $now_users ) ) ) as $uid ) {
	$now  = Api::forUser( $uid )->value;
	$next = isset( $totals[ $uid ] ) ? PromotionEngine::standingForTotalWins( $totals[ $uid ] )['tier']->value : 'none';
	$cur  = (int) get_user_meta( $uid, 'ink_tier_win_count', true );
	if ( $next !== $now ) {
		$grade_rows[] = array( 'user_id' => $uid, 'user_login' => $login( $uid ), 'display_name' => get_userdata( $uid )->display_name ?? '', 'poetry_wins' => $totals[ $uid ] ?? 0, 'grade_now' => $now, 'grade_new' => $next );
	}
}
usort( $grade_rows, static fn( $a, $b ) => $b['poetry_wins'] <=> $a['poetry_wins'] );

foreach ( array( '', 'backup', 'review' ) as $sub ) { if ( ! is_dir( "$out/$sub" ) ) { mkdir( "$out/$sub" ); } }
foreach ( array( 'wenners', 'result-posts', 'challenges' ) as $name ) { copy( "$out/$name.csv", "$out/backup/$name.csv" ); }
wl_write( "$out/wenners.csv", array( 'status', 'activity', 'notes', 'result_post', 'result_date', 'challenge_id', 'source_category', 'challenge', 'category', 'grade', 'position', 'byline', 'user_id', 'user_login', 'title', 'post_id', 'post_title', 'match', 'source', 'suggestions', 'result_url' ), $rows );
wl_write( "$out/result-posts.csv", array( 'status', 'notes', 'post_id', 'date', 'title', 'challenge_id', 'source_category', 'challenge', 'how', 'existing_link', 'winner_post_votes', 'parsed_rows', 'july_rows', 'unparsed_lines', 'url' ), $rp_rows );
wl_write( "$out/challenges.csv", array( 'status', 'notes', 'challenge_id', 'source_category', 'title', 'opened', 'kind', 'entries', 'result_posts', 'wins' ), $ch_rows );
wl_write( "$out/review/wins-without-a-post.csv", array( 'notes', 'result_date', 'challenge', 'category', 'grade', 'position', 'byline', 'user_login', 'title', 'suggestions', 'result_url' ), $missing );
wl_write( "$out/review/july-list-mismatches.csv", array( 'status', 'activity', 'type', 'differences', 'result_date', 'challenge', 'category', 'grade', 'position', 'byline', 'user_login', 'title', 'post_id', 'result_url' ), $mismatch );
usort( $confirm, static fn( $a, $b ) => array( '' !== $a['by_line_account'], $a['byline'], $a['result_date'] ) <=> array( '' !== $b['by_line_account'], $b['byline'], $b['result_date'] ) );
wl_write( "$out/review/accounts-to-confirm.csv", array( 'same_person', 'byline', 'by_line_account', 'post_account', 'post_account_id', 'post_account_name', 'title', 'post_id', 'post_date', 'category', 'result_date', 'challenge', 'result_url' ), $confirm );
wl_write( "$out/review/grade-changes.csv", array( 'user_id', 'user_login', 'display_name', 'poetry_wins', 'grade_now', 'grade_new' ), $grade_rows );

$tally = static function ( array $l, string $k ) { $c = array(); foreach ( $l as $r ) { $c[ (string) ( $r[ $k ] ?? '' ) ] = ( $c[ (string) ( $r[ $k ] ?? '' ) ] ?? 0 ) + 1; } ksort( $c ); return wp_json_encode( $c, JSON_UNESCAPED_UNICODE ); };
echo "added: $new_rp result posts, $new_wins wins. Filled: $new_posts posts, $new_accounts accounts.\n";
echo 'wins ' . count( $rows ) . ' status ' . $tally( $rows, 'status' ) . ' activity ' . $tally( $rows, 'activity' ) . ' match ' . $tally( $rows, 'match' ) . "\n";
echo 'result posts ' . count( $rp_rows ) . ' status ' . $tally( $rp_rows, 'status' ) . "\n";
echo 'challenges ' . count( $ch_rows ) . ' status ' . $tally( $ch_rows, 'status' ) . "\n";
echo 'to review: no post ' . count( $missing ) . ', mismatches ' . count( array_filter( $mismatch, static fn( $m ) => 'review' === $m['status'] ) ) . ' of ' . count( $mismatch ) . ', accounts ' . count( $confirm ) . "\n";
echo "grade changes:\n";
foreach ( $grade_rows as $g ) { echo "  {$g['display_name']} ({$g['user_login']}) wins={$g['poetry_wins']} {$g['grade_now']} -> {$g['grade_new']}\n"; }
