<?php
/**
 * Premium library: REST endpoints for the saved toplists library.
 *
 * Extracted from library.php (ticket: library split).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST endpoints for saved toplists library.
 *
 * @return void
 */
function toplist_register_rest_routes() {
	register_rest_route(
		'toplist-block/v1',
		'/toplists',
		array(
			'methods'             => 'GET',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'callback'            => function () {
				$posts = get_posts(
					array(
						'post_type'      => 'toplist_list',
						'post_status'    => array( 'publish', 'private', 'draft' ),
						'posts_per_page' => -1,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				);
				$data = array();
				foreach ( $posts as $post ) {
					if ( ! current_user_can( 'edit_post', $post->ID ) ) {
						continue;
					}
					$data[] = array(
						'id'       => (int) $post->ID,
						'name'     => get_the_title( $post ),
						'modified' => get_post_modified_time( DATE_ATOM, false, $post ),
					);
				}
				return rest_ensure_response( $data );
			},
		)
	);

	register_rest_route(
		'toplist-block/v1',
		'/toplists/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => function ( $request ) {
				$post_id = (int) $request['id'];
				return current_user_can( 'edit_post', $post_id );
			},
			'callback'            => function ( $request ) {
				$post_id = (int) $request['id'];
				$post    = get_post( $post_id );
				if ( ! $post || 'toplist_list' !== $post->post_type ) {
					return new WP_Error( 'toplist_not_found', __( 'Toplist not found.', 'toplist' ), array( 'status' => 404 ) );
				}
				return rest_ensure_response(
					array(
						'id'       => (int) $post->ID,
						'name'     => get_the_title( $post ),
						'content'  => (string) $post->post_content,
						'modified' => get_post_modified_time( DATE_ATOM, false, $post ),
					)
				);
			},
		)
	);

	register_rest_route(
		'toplist-block/v1',
		'/toplists',
		array(
			'methods'             => 'POST',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'name'    => array(
					'required' => true,
					'type'     => 'string',
				),
				'content' => array(
					'required' => true,
					'type'     => 'string',
				),
			),
			'callback'            => function ( $request ) {
				$name = sanitize_text_field( (string) $request->get_param( 'name' ) );
				$content = wp_kses_post( (string) $request->get_param( 'content' ) );
				if ( '' === $name ) {
					return new WP_Error( 'toplist_invalid_name', __( 'Toplist name is required.', 'toplist' ), array( 'status' => 400 ) );
				}

				$post_id = wp_insert_post(
					array(
						'post_type'    => 'toplist_list',
						'post_title'   => $name,
						'post_content' => $content,
						'post_status'  => current_user_can( 'publish_posts' ) ? 'publish' : 'draft',
					),
					true
				);

				if ( is_wp_error( $post_id ) ) {
					return $post_id;
				}

				return rest_ensure_response(
					array(
						'id'   => (int) $post_id,
						'name' => $name,
					)
				);
			},
		)
	);
}
