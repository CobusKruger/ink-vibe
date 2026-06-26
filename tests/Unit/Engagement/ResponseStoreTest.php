<?php
/**
 * Unit tests for the Gemeenskapsreaksie store (Story 7.4, FR-27, AD-5a).
 *
 * Target: {@see \Ink\Engagement\ResponseStore} — the WP-comment substrate
 * (comment_type='ink_reaksie' + comment-meta ink_response_type). Brain Monkey
 * mocks the comment APIs. The "no untyped Gemeenskapsreaksie" guarantee (AC #4)
 * and the "filtered count, not comment_count" guardrail (AD-5a) are asserted.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ResponseStore;
use Ink\Kernel\ResponseType;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'add inserts a sanctioned ink_reaksie comment and tags it with the response type', function (): void {
	Functions\when( 'get_userdata' )->justReturn( new \WP_User( 7, 'lid@ink.test', 'Lid Naam' ) );

	Functions\expect( 'wp_insert_comment' )
		->once()
		->with( Mockery::on( static function ( array $args ): bool {
			return 42 === $args['comment_post_ID']
				&& 7 === $args['user_id']
				&& 'Pragtige beeldspraak.' === $args['comment_content']
				&& 'ink_reaksie' === $args['comment_type']
				&& 1 === $args['comment_approved'];
		} ) )
		->andReturn( 101 );

	Functions\expect( 'add_comment_meta' )
		->once()
		->with( 101, ResponseStore::META_TYPE, 'lof', true )
		->andReturn( 1 );

	$id = ResponseStore::add( 42, 7, ResponseType::Lof, 'Pragtige beeldspraak.' );

	expect( $id )->toBe( 101 );
} );

test( 'add returns 0 when the comment insert fails', function (): void {
	Functions\when( 'get_userdata' )->justReturn( new \WP_User( 7 ) );
	Functions\when( 'wp_insert_comment' )->justReturn( 0 );
	Functions\expect( 'add_comment_meta' )->never();

	expect( ResponseStore::add( 42, 7, ResponseType::Lof, 'x' ) )->toBe( 0 );
} );

test( 'forPost maps typed responses and SKIPS a row with a missing/invalid type', function (): void {
	$rows = array(
		(object) array(
			'comment_ID'      => 1,
			'comment_content' => 'Lof-teks',
			'comment_author'  => 'Een',
			'comment_date'    => '2026-06-26 10:00:00',
		),
		(object) array(
			'comment_ID'      => 2,
			'comment_content' => 'Geen tipe',
			'comment_author'  => 'Twee',
			'comment_date'    => '2026-06-26 11:00:00',
		),
	);

	Functions\when( 'get_comments' )->justReturn( $rows );
	Functions\when( 'get_comment_meta' )->alias(
		static fn ( int $id ): string => 1 === $id ? 'insig' : 'rommel' // row 2 has an invalid type
	);

	$responses = ResponseStore::forPost( 42 );

	expect( $responses )->toHaveCount( 1 );           // the invalid-type row is skipped (AC #4)
	expect( $responses[0]['id'] )->toBe( 1 );
	expect( $responses[0]['type'] )->toBe( ResponseType::Insig );
	expect( $responses[0]['content'] )->toBe( 'Lof-teks' );
} );

test( 'countForPost asks for the FILTERED ink_reaksie count, not comment_count (AD-5a)', function (): void {
	Functions\expect( 'get_comments' )
		->once()
		->with( Mockery::on( static function ( array $args ): bool {
			return 42 === $args['post_id']
				&& 'ink_reaksie' === $args['type']
				&& 'approve' === $args['status']
				&& true === $args['count'];
		} ) )
		->andReturn( 5 );

	expect( ResponseStore::countForPost( 42 ) )->toBe( 5 );
} );
