<?php
/**
 * Visual card layout builder (ticket 612).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register card builder hooks.
 *
 * @return void
 */
function toplist_register_card_builder_hooks(): void {
	add_action( 'add_meta_boxes_toplist_list', 'toplist_card_builder_register_metabox', 22 );
	add_action( 'save_post_toplist_list', 'toplist_card_builder_save_layout', 14 );
	add_filter( 'toplist_render_card_css', 'toplist_card_builder_render_css', 10, 2 );
}

/**
 * Return card builder field keys.
 *
 * @return array<int, string>
 */
function toplist_card_builder_field_keys(): array {
	return array(
		'logo',
		'identity',
		'offer',
		'terms',
		'details',
		'play',
	);
}

/**
 * Register the card layout metabox.
 *
 * @return void
 */
function toplist_card_builder_register_metabox(): void {
	add_meta_box(
		'toplist_card_layout_box',
		__( 'Card layout builder', 'toplist' ),
		'toplist_card_builder_render_metabox',
		'toplist_list',
		'normal',
		'default'
	);
}

/**
 * Render the card layout metabox.
 *
 * @param WP_Post $post Post.
 * @return void
 */
function toplist_card_builder_render_metabox( $post ): void {
	wp_nonce_field( 'toplist_save_card_layout', 'toplist_card_layout_nonce' );
	$layout = toplist_card_builder_get_layout( (int) $post->ID );
	$order  = isset( $layout['order'] ) && is_array( $layout['order'] ) ? $layout['order'] : toplist_card_builder_field_keys();
	$labels = array(
		'logo'     => __( 'Logo / identity column', 'toplist' ),
		'identity' => __( 'Title & rating', 'toplist' ),
		'offer'    => __( 'Offer & terms', 'toplist' ),
		'terms'    => __( 'Terms block', 'toplist' ),
		'details'  => __( 'Details accordion', 'toplist' ),
		'play'     => __( 'CTA column', 'toplist' ),
	);
	echo '<p class="description">' . esc_html__( 'Drag to reorder card regions. Saved order applies on the frontend via flexbox.', 'toplist' ) . '</p>';
	echo '<ul id="toplist-card-layout-sortable" style="list-style:none;margin:0;padding:0;max-width:420px;">';
	foreach ( $order as $key ) {
		if ( ! isset( $labels[ $key ] ) ) {
			continue;
		}
		echo '<li style="padding:8px 10px;margin:0 0 6px;border:1px solid #ccd0d4;background:#fff;cursor:grab;" data-field="' . esc_attr( $key ) . '">';
		echo '<input type="hidden" name="toplist_card_layout_order[]" value="' . esc_attr( $key ) . '" />';
		echo esc_html( $labels[ $key ] );
		echo '</li>';
	}
	echo '</ul>';
	echo '<script>(function(){var list=document.getElementById("toplist-card-layout-sortable");if(!list)return;var dragEl=null;list.querySelectorAll("li").forEach(function(li){li.draggable=true;li.addEventListener("dragstart",function(){dragEl=li;});li.addEventListener("dragover",function(e){e.preventDefault();});li.addEventListener("drop",function(e){e.preventDefault();if(!dragEl||dragEl===li)return;list.insertBefore(dragEl,li);});});})();</script>';
}

/**
 * Get saved card layout for a list.
 *
 * @param int $post_id Post ID.
 * @return array<string, mixed>
 */
function toplist_card_builder_get_layout( int $post_id ): array {
	$raw = get_post_meta( $post_id, '_toplist_card_layout', true );
	if ( ! is_string( $raw ) || '' === $raw ) {
		return array( 'order' => toplist_card_builder_field_keys() );
	}
	$decoded = json_decode( $raw, true );
	return is_array( $decoded ) ? $decoded : array( 'order' => toplist_card_builder_field_keys() );
}

/**
 * Save card layout on post save.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function toplist_card_builder_save_layout( int $post_id ): void {
	if ( ! isset( $_POST['toplist_card_layout_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['toplist_card_layout_nonce'] ) ), 'toplist_save_card_layout' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$allowed = toplist_card_builder_field_keys();
	$order   = isset( $_POST['toplist_card_layout_order'] ) && is_array( $_POST['toplist_card_layout_order'] )
		? array_values( array_intersect( array_map( 'sanitize_key', wp_unslash( $_POST['toplist_card_layout_order'] ) ), $allowed ) )
		: $allowed;
	foreach ( $allowed as $key ) {
		if ( ! in_array( $key, $order, true ) ) {
			$order[] = $key;
		}
	}
	update_post_meta( $post_id, '_toplist_card_layout', wp_json_encode( array( 'order' => $order ) ) );
	toplist_bust_list_render_cache( $post_id );
}

/**
 * Append card-builder CSS to existing CSS.
 *
 * @param string $css        Existing CSS.
 * @param int    $context_id List post ID.
 * @return string
 */
function toplist_card_builder_render_css( string $css, int $context_id ): string {
	if ( 0 >= $context_id ) {
		return $css;
	}
	$layout = toplist_card_builder_get_layout( $context_id );
	$order  = isset( $layout['order'] ) && is_array( $layout['order'] ) ? $layout['order'] : array();
	if ( array() === $order ) {
		return $css;
	}
	$map   = array(
		'logo'     => '.toplist .op-left',
		'identity' => '.toplist .operator-title-row-v2',
		'offer'    => '.toplist .operator-column-bonus-v2',
		'terms'    => '.toplist .read-terms-link-v2',
		'details'  => '.toplist .more-info-table',
		'play'     => '.toplist .operator-playnow-column-v2',
	);
	$rules = array();
	foreach ( $order as $i => $key ) {
		if ( ! isset( $map[ $key ] ) ) {
			continue;
		}
		$rules[] = $map[ $key ] . '{order:' . ( $i + 1 ) . ';}';
	}
	$rules[] = '.toplist .op-right{display:flex;flex-direction:column;flex-wrap:wrap;}';
	return trim( $css . "\n" . implode( "\n", $rules ) );
}
