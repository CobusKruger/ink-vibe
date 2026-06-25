<?php
/**
 * Afrikaans copy-placeholder leak scan (static subset of the NFR-1 leak gate).
 *
 * Fails the build when LIVE code (theme patterns/templates + ink-core source)
 * contains *more* unauthored-Afrikaans placeholder markers than the committed
 * baseline. It is a ratchet: the count can only go down. Adding new placeholder
 * debt fails CI; authoring copy (removing markers) is rewarded with a prompt to
 * lower the baseline so the gate keeps tightening toward zero at launch.
 *
 * This is the static, no-WordPress subset of the full NFR-1 English-leak gate
 * (page crawl + `wp i18n` untranslated counts) that Story 17.4 / Epic 18 build
 * on top. It is intentionally runnable today, in unit-stage CI, without wp-env.
 *
 * Markers scanned (the project's human-copy-pending tokens):
 *   [NEEDS HUMAN AFRIKAANS]   — UI/template copy awaiting human authoring
 *   [WAG OP MENSLIKE KOPIE]   — email/notice copy awaiting human authoring
 *   ink-needs-human-af        — the hidden-span CSS hook flagging the same
 *
 * Usage:
 *   php tools/leak-scan/scan-placeholders.php            # scan + gate (CI)
 *   php tools/leak-scan/scan-placeholders.php --update-baseline
 *   composer copy:scan
 *
 * Exit codes: 0 = within baseline · 2 = NEW debt (gate fails) · 1 = IO/usage.
 */

declare(strict_types=1);

const MARKERS = [
	'[NEEDS HUMAN AFRIKAANS]',
	'[WAG OP MENSLIKE KOPIE]',
	'ink-needs-human-af',
];

// Live-code roots only — docs/, _bmad-output/, tests/, vendor/, node_modules/
// are deliberately excluded: those legitimately *document* the debt or are
// fixtures, and are never rendered to a visitor.
const SCAN_ROOTS = [
	'wp-content/themes/ink-foundation',
	'wp-content/plugins/ink-core/src',
];

const SCAN_EXTENSIONS = ['php', 'html'];

const EXCLUDE_FRAGMENTS = ['/vendor/', '/node_modules/', '/tests/'];

$projectRoot = dirname(__DIR__, 2);
$baselinePath = __DIR__ . '/placeholder-baseline.json';
$updateBaseline = in_array('--update-baseline', $argv, true);

/**
 * Walk the scan roots and return [ relativePath => markerCount ] for every
 * live-code file that contains at least one placeholder marker.
 *
 * @return array<string,int>
 */
function scanLiveCode(string $projectRoot): array {
	$counts = [];

	foreach (SCAN_ROOTS as $root) {
		$absRoot = $projectRoot . '/' . $root;
		if (!is_dir($absRoot)) {
			continue;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($absRoot, FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			$path = $file->getPathname();
			$ext = strtolower($file->getExtension());
			if (!in_array($ext, SCAN_EXTENSIONS, true)) {
				continue;
			}
			foreach (EXCLUDE_FRAGMENTS as $fragment) {
				if (str_contains($path, $fragment)) {
					continue 2;
				}
			}

			$contents = file_get_contents($path);
			if (false === $contents) {
				continue;
			}

			$count = 0;
			foreach (MARKERS as $marker) {
				$count += substr_count($contents, $marker);
			}

			if ($count > 0) {
				$relative = ltrim(str_replace($projectRoot, '', $path), '/');
				$counts[$relative] = $count;
			}
		}
	}

	ksort($counts);
	return $counts;
}

/**
 * Per-marker hits with line numbers for one file (used in the detail report).
 *
 * @return list<array{line:int,marker:string,text:string}>
 */
function fileHits(string $absPath): array {
	$hits = [];
	$lines = file($absPath, FILE_IGNORE_NEW_LINES) ?: [];
	foreach ($lines as $i => $line) {
		foreach (MARKERS as $marker) {
			if (str_contains($line, $marker)) {
				$hits[] = [
					'line' => $i + 1,
					'marker' => $marker,
					'text' => trim($line),
				];
			}
		}
	}
	return $hits;
}

$found = scanLiveCode($projectRoot);
$foundTotal = array_sum($found);

if ($updateBaseline) {
	$payload = [
		'_comment' => 'Per-file Afrikaans copy-placeholder counts. Ratchet baseline: '
			. 'the scan FAILS if any file exceeds its count here or a new file appears. '
			. 'When you author copy and remove markers, lower these numbers (or rerun '
			. '--update-baseline) so the gate keeps tightening. Launch target: empty.',
		'_generated_total' => $foundTotal,
		'files' => $found,
	];
	$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	file_put_contents($baselinePath, $json . "\n");
	echo "Baseline updated: {$foundTotal} markers across " . count($found) . " files.\n";
	echo "Wrote {$baselinePath}\n";
	exit(0);
}

if (!is_file($baselinePath)) {
	fwrite(STDERR, "ERROR: baseline missing at {$baselinePath}. Run with --update-baseline to create it.\n");
	exit(1);
}

$baselineData = json_decode((string) file_get_contents($baselinePath), true);
if (!is_array($baselineData) || !isset($baselineData['files']) || !is_array($baselineData['files'])) {
	fwrite(STDERR, "ERROR: baseline at {$baselinePath} is malformed.\n");
	exit(1);
}
/** @var array<string,int> $baseline */
$baseline = $baselineData['files'];
$baselineTotal = array_sum($baseline);

// Classify each live-code file against the baseline.
$regressions = []; // file => [from, to]  (count went UP, or new file) -> FAIL
$resolved = [];    // file => [from, to]  (count went DOWN or to zero)  -> tighten

foreach ($found as $file => $count) {
	$base = $baseline[$file] ?? 0;
	if ($count > $base) {
		$regressions[$file] = [$base, $count];
	} elseif ($count < $base) {
		$resolved[$file] = [$base, $count];
	}
}
// Files in baseline that no longer have any markers at all.
foreach ($baseline as $file => $base) {
	if (!isset($found[$file]) && $base > 0) {
		$resolved[$file] = [$base, 0];
	}
}

echo "Afrikaans copy-placeholder scan (static NFR-1 subset)\n";
echo str_repeat('-', 56) . "\n";
echo sprintf("Live-code markers found : %d (across %d files)\n", $foundTotal, count($found));
echo sprintf("Baseline                : %d (across %d files)\n", $baselineTotal, count(array_filter($baseline)));
echo "\n";

if ($resolved) {
	echo "✓ Progress — copy authored / markers removed since baseline:\n";
	foreach ($resolved as $file => [$from, $to]) {
		echo sprintf("    %s: %d -> %d\n", $file, $from, $to);
	}
	echo "  Lower the baseline to lock in this progress:\n";
	echo "    composer copy:scan -- --update-baseline\n\n";
}

if ($regressions) {
	echo "✗ NEW placeholder debt detected (gate FAILS):\n";
	foreach ($regressions as $file => [$from, $to]) {
		$label = 0 === $from ? 'new file' : "was {$from}";
		echo sprintf("    %s: %d -> %d  (%s)\n", $file, $from, $to, $label);
		foreach (fileHits($projectRoot . '/' . $file) as $hit) {
			echo sprintf("        L%d  %s\n", $hit['line'], $hit['marker']);
		}
	}
	echo "\n";
	echo "Author the Afrikaans (human-only — see docs/afrikaans-copy-worklist.md),\n";
	echo "or, if this marker is legitimate new tracked debt, raise the baseline\n";
	echo "deliberately with --update-baseline and say so in the commit.\n";
	exit(2);
}

echo "✓ No new placeholder debt. " . ($foundTotal > 0
	? "{$foundTotal} known gaps remain (pre-launch content gate — see docs/afrikaans-copy-worklist.md).\n"
	: "Zero placeholders — Afrikaans copy debt is fully cleared.\n");
exit(0);
