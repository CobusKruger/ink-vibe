<?php
/**
 * Test-data helper for tools/fidelity/lees-interactions.mjs (run through
 * `node tools/local.mjs wp eval-file`). Every action prints `key=value` lines.
 *
 * Actions:
 *   temp-create <post>           a response by an administrator, dated first, type insig
 *   temp-remove <comment>        delete it (its upvote rows go with it)
 *   dedication-get <post>        print the stored dedication
 *   dedication-set <post> <text> save it through the real admin meta-box path (FieldSets::save)
 *   dedication-clear <post>      remove it
 *   draft-find <title>           the newest post with that exact title: id, type, status, dedication
 *   draft-remove <post>          delete a post for good
 *
 * @package Ink\Core
 */

$action = $args[0] ?? '';

switch ( $action ) {
	case 'temp-create':
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
			)
		);
		$id     = \Ink\Engagement\ResponseStore::add( (int) $args[1], (int) $admins[0]->ID, \Ink\Kernel\ResponseType::Insig, 'TEMP interaction check response' );
		wp_update_comment(
			array(
				'comment_ID'       => $id,
				'comment_date'     => '2020-01-01 00:00:00',
				'comment_date_gmt' => '2020-01-01 00:00:00',
			)
		);
		echo 'id=' . $id . "\n";
		break;

	case 'temp-remove':
		wp_delete_comment( (int) $args[1], true );
		echo 'removed=' . ( get_comment( (int) $args[1] ) ? 'no' : 'yes' ) . "\n";
		break;

	case 'dedication-get':
		echo 'value=' . (string) get_post_meta( (int) $args[1], 'ink_gedig_toewyding', true ) . "\n";
		break;

	case 'dedication-set':
		$post_id = (int) $args[1];
		$admins  = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
			)
		);
		wp_set_current_user( (int) $admins[0]->ID );
		$_POST['ink_content_fieldsets_nonce'] = wp_create_nonce( 'ink_content_fieldsets_save' );
		$_POST['ink_gedig_toewyding']         = wp_slash( (string) $args[2] );
		( new \Ink\Content\FieldSets() )->save( $post_id, get_post( $post_id ) );
		echo 'value=' . (string) get_post_meta( $post_id, 'ink_gedig_toewyding', true ) . "\n";
		break;

	case 'dedication-clear':
		delete_post_meta( (int) $args[1], 'ink_gedig_toewyding' );
		echo "cleared=yes\n";
		break;

	case 'draft-find':
		$found = get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => 'any',
				'title'          => (string) $args[1],
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'DESC',
			)
		);
		if ( array() === $found ) {
			echo "id=0\n";
			break;
		}
		echo 'id=' . $found[0]->ID . "\n";
		echo 'type=' . $found[0]->post_type . "\n";
		echo 'status=' . $found[0]->post_status . "\n";
		echo 'dedication=' . (string) get_post_meta( $found[0]->ID, 'ink_gedig_toewyding', true ) . "\n";
		break;

	case 'draft-remove':
		wp_delete_post( (int) $args[1], true );
		echo 'removed=' . ( get_post( (int) $args[1] ) ? 'no' : 'yes' ) . "\n";
		break;
}
