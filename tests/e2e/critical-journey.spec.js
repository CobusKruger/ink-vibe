/**
 * Critical-journey E2E smoke (NFR-9, Story 18.8).
 *
 * The one thin end-to-end journey the architecture names:
 *   register -> buy lidmaatskap via PayFast SANDBOX -> submit a work ->
 *   publish -> read/react -> renew.
 *
 * PayFast is ALWAYS the sandbox (project-context: never the live ZAR gateway).
 * Runs against wp-env locally / staging in CI. The steps are structured so the
 * journey reads as the spec; assertions deepen as the front-end routes settle
 * (some legs are marked test.fixme where they depend on the PayFast sandbox
 * round-trip + seeded membership product on the running site).
 *
 * @see playwright.config.js
 */

// eslint-disable-next-line @typescript-eslint/no-var-requires
const { test, expect } = require( '@playwright/test' );

test.describe( 'INK critical journey @smoke', () => {
	test( 'front-end is reachable and Afrikaans-first', async ( { page } ) => {
		const response = await page.goto( '/' );
		expect( response?.status() ).toBeLessThan( 400 );

		// Afrikaans-first: the document language is `af` (Quality Gate D / NFR-1).
		await expect( page.locator( 'html' ) ).toHaveAttribute( 'lang', /af/ );
	} );

	test( 'a visitor can reach registration', async ( { page } ) => {
		await page.goto( '/registreer' );
		// The custom ink-core registration form is present (not Youzify — retired).
		await expect( page.locator( 'form' ) ).toBeVisible();
	} );

	// The paid legs need a seeded membership product + the PayFast SANDBOX
	// round-trip on the running site; they are wired here as the journey contract
	// and enabled once staging seeds the product (PayFast sandbox creds in CI env).
	test( 'register -> buy (PayFast sandbox) -> submit -> publish -> read -> renew', async ( {
		page,
	} ) => {
		test.fixme(
			true,
			'Enable once staging seeds the lidmaatskap product + PayFast SANDBOX creds are in CI env. PayFast sandbox only — never the live gateway.'
		);

		// 1. Register a new lid.
		// 2. Buy a lidmaatskap term via WooCommerce + PayFast SANDBOX (redirect + return).
		// 3. As an active member, submit a work (gedig/storie/artikel) via Skryf.
		// 4. Publish; the work appears on Ontdek.
		// 5. Read the work + leave a structured reaction (Gemeenskapsreaksie).
		// 6. Renew the membership before expiry.
		await page.goto( '/' );
	} );
} );
