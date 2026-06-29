/**
 * Playwright config (NFR-9, Story 18.8) — thin E2E layer over the wp-env stack.
 *
 * The critical-journey smoke spec exercises register -> buy via PayFast SANDBOX ->
 * submit -> publish -> read/react -> renew. PayFast is ALWAYS the sandbox here, never
 * the live ZAR gateway (project-context). Runs against wp-env locally / staging in CI.
 */

// eslint-disable-next-line @typescript-eslint/no-var-requires
const { defineConfig, devices } = require( '@playwright/test' );

const baseURL = process.env.WP_BASE_URL || 'http://localhost:8888';

module.exports = defineConfig( {
	testDir: './tests/e2e',
	timeout: 60_000,
	expect: { timeout: 10_000 },
	// CI runs the smoke journey; full suite on demand. Risk-based depth (NFR-9).
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI ? [ [ 'github' ], [ 'list' ] ] : 'list',
	use: {
		baseURL,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
