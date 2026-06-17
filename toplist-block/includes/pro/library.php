<?php
/**
 * Premium library: CPT, import/export, REST, metaboxes (ticket 613).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivate lite when premium activates; show one-time notice.
 *
 * @return void
 */
function toplist_block_on_activate(): void {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( is_plugin_active( 'toplist-block-lite/toplist-block-lite.php' ) ) {
		deactivate_plugins( 'toplist-block-lite/toplist-block-lite.php', true );
		set_transient( 'toplist_upgraded_from_lite', 1, WEEK_IN_SECONDS );
	}
}

/**
 * Prevent both plugins from staying active together.
 *
 * @return void
 */
function toplist_block_conflict_guard(): void {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		return;
	}
	$lite = 'toplist-block-lite/toplist-block-lite.php';
	$pro  = 'toplist-block/toplist-block.php';
	if ( is_plugin_active( $lite ) && is_plugin_active( $pro ) ) {
		deactivate_plugins( $lite, true );
		set_transient( 'toplist_upgraded_from_lite', 1, WEEK_IN_SECONDS );
	}
}

/**
 * One-time notice after upgrading from lite.
 *
 * @return void
 */
function toplist_block_upgraded_from_lite_notice(): void {
	if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'toplist_upgraded_from_lite' ) ) {
		return;
	}
	echo '<div class="notice notice-success is-dismissible"><p>';
	echo esc_html__( 'Toplist Block Pro is active. Your existing Toplist blocks are unchanged. Enter your license under Settings → Toplist Block to unlock libraries and import.', 'toplist' );
	echo '</p></div>';
	delete_transient( 'toplist_upgraded_from_lite' );
}

/**
 * Build ItemList schema.org JSON-LD for a toplist.
 *
 * @param array<int, array<string, mixed>> $items Parsed items.
 * @param string                           $heading List heading.
 * @return array<string, mixed>
 */
function toplist_build_itemlist_schema( array $items, string $heading ): array {
	$list = array();
	foreach ( $items as $i => $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$name = toplist_clean_text( $item['product'] ?? '' );
		if ( '' === $name ) {
			$name = toplist_clean_text( $item['operator'] ?? '' );
		}
		if ( '' === $name ) {
			continue;
		}
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $name,
		);
		$url   = toplist_clean_text( $item['href'] ?? '' );
		if ( '' !== $url ) {
			$entry['url'] = $url;
		}
		$list[] = $entry;
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => '' !== $heading ? $heading : __( 'Toplist', 'toplist' ),
		'itemListElement' => $list,
	);
}

/**
 * Register Toplists library custom post type.
 *
 * @return void
 */
function toplist_register_cpt() {
	register_post_type(
		'toplist_list',
		array(
			'labels'          => array(
				'name'          => __( 'Toplists', 'toplist' ),
				'singular_name' => __( 'Toplist', 'toplist' ),
				'add_new_item'  => __( 'Add New Toplist', 'toplist' ),
				'edit_item'     => __( 'Edit Toplist', 'toplist' ),
				'new_item'      => __( 'New Toplist', 'toplist' ),
				'view_item'     => __( 'View Toplist', 'toplist' ),
				'search_items'  => __( 'Search Toplists', 'toplist' ),
				'not_found'     => __( 'No toplists found', 'toplist' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => true,
			'has_archive'     => false,
			'rewrite'         => false,
			'menu_icon'       => 'dashicons-list-view',
			'supports'        => array( 'title', 'revisions' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}

/**
 * Keep Toplist library in classic editor textarea mode for raw pipe content.
 *
 * @param bool   $use_block_editor Whether block editor is enabled.
 * @param string $post_type Post type name.
 * @return bool
 */
function toplist_disable_block_editor_for_toplists( $use_block_editor, $post_type ) {
	if ( 'toplist_list' === $post_type ) {
		return false;
	}
	return $use_block_editor;
}

/**
 * Add ID and modified columns to Toplists admin list.
 *
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function toplist_toplists_admin_columns( array $columns ): array {
	return array(
		'cb'         => $columns['cb'] ?? '',
		'title'      => __( 'Name', 'toplist' ),
		'toplist_id' => __( 'Shortcode / ID', 'toplist' ),
		'modified'   => __( 'Last Modified', 'toplist' ),
		'date'       => $columns['date'] ?? __( 'Date', 'toplist' ),
	);
}

/**
 * Render custom Toplists admin columns.
 *
 * @param string $column Column key.
 * @param int    $post_id Post ID.
 * @return void
 */
function toplist_toplists_admin_column_content( $column, $post_id ) {
	if ( 'toplist_id' === $column ) {
		echo '<code>[toplist id=&quot;' . esc_html( (string) $post_id ) . '&quot;]</code><br>';
		echo '<small>#' . esc_html( (string) $post_id ) . '</small>';
		return;
	}
	if ( 'modified' === $column ) {
		$modified = get_the_modified_date( '', $post_id );
		echo esc_html( is_string( $modified ) ? $modified : '' );
	}
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

/**
 * Render plain textarea metabox for toplist raw content.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function toplist_render_raw_content_metabox( $post ) {
	wp_nonce_field( 'toplist_save_raw_content', 'toplist_raw_content_nonce' );
	$content = $post->post_content;
	$fields  = toplist_supported_fields();
	echo '<div data-toplist-spreadsheet="1">';
	echo '<h2 class="nav-tab-wrapper" style="margin-bottom:12px;">';
	echo '<a href="#" class="nav-tab nav-tab-active" data-toplist-tab="spreadsheet">' . esc_html__( 'Spreadsheet', 'toplist' ) . '</a>';
	echo '<a href="#" class="nav-tab" data-toplist-tab="raw">' . esc_html__( 'Raw pipes', 'toplist' ) . '</a>';
	echo '</h2>';
	echo '<div data-toplist-panel="spreadsheet">';
	echo '<p class="description">' . esc_html__( 'Edit rows in a table. Changes sync to pipe content on save.', 'toplist' ) . '</p>';
	echo '<div style="overflow-x:auto;max-height:480px;margin-bottom:8px;">';
	echo '<table class="widefat striped toplist-spreadsheet-table"><thead><tr>';
	foreach ( $fields as $field ) {
		echo '<th>' . esc_html( $field ) . '</th>';
	}
	echo '<th></th></tr></thead><tbody id="toplist-spreadsheet-body"></tbody></table>';
	echo '</div>';
	echo '<p><button type="button" class="button" id="toplist-spreadsheet-add-row">' . esc_html__( 'Add row', 'toplist' ) . '</button></p>';
	echo '</div>';
	echo '<div data-toplist-panel="raw" style="display:none;">';
	echo '<p>' . esc_html__( 'Use one line per item. Pipe-delimited format is supported, including optional first-row header directives.', 'toplist' ) . '</p>';
	echo '<textarea name="toplist_raw_content" id="toplist_raw_content" style="width:100%;min-height:320px;font-family:monospace;" spellcheck="false">' . esc_textarea( $content ) . '</textarea>';
	echo '</div></div>';
}

/**
 * Render CSV / JSON import/export metabox for a single toplist.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function toplist_render_csv_tools_metabox( $post ) {
	$pid             = (int) $post->ID;
	$export_url      = wp_nonce_url(
		admin_url( 'admin-post.php?action=toplist_export_csv&post_id=' . $pid ),
		'toplist_export_csv_' . $pid
	);
	$export_json_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=toplist_export_json&post_id=' . $pid ),
		'toplist_export_json_' . $pid
	);
	$csv_form_id     = 'toplist-import-csv-form-' . $pid;
	$json_form_id    = 'toplist-import-json-form-' . $pid;
	$file_id         = 'toplist-import-file-' . $pid;
	$json_file_id    = 'toplist-json-import-file-' . $pid;
	$csv_btn_id      = 'toplist-import-submit-csv-' . $pid;
	$json_btn_id     = 'toplist-import-submit-json-' . $pid;

	echo '<p><strong>' . esc_html__( 'CSV', 'toplist' ) . '</strong></p>';
	echo '<p><a class="button button-secondary" href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export as CSV', 'toplist' ) . '</a></p>';
	echo '<p><label for="' . esc_attr( $file_id ) . '"><strong>' . esc_html__( 'Import CSV', 'toplist' ) . '</strong></label></p>';
	echo '<input type="file" id="' . esc_attr( $file_id ) . '" name="toplist_csv_file" form="' . esc_attr( $csv_form_id ) . '" accept=".csv,text/csv" />';
	echo '<p style="margin-top:10px;"><button type="button" class="button button-primary" id="' . esc_attr( $csv_btn_id ) . '">' . esc_html__( 'Import CSV', 'toplist' ) . '</button></p>';
	echo '<p class="description">' . esc_html__( 'CSV header can use field names: operator, product, offer, href, logo, year, ctaText, terms, bullets, payout, code, rating, regulator, payments, games, liveGames, smallPrint, readReviewHref, readReviewText, withdrawals.', 'toplist' ) . '</p>';

	echo '<hr><p><strong>' . esc_html__( 'JSON (toplist.json)', 'toplist' ) . '</strong></p>';
	echo '<p><a class="button button-secondary" href="' . esc_url( $export_json_url ) . '">' . esc_html__( 'Export as JSON', 'toplist' ) . '</a></p>';
	echo '<p><label for="' . esc_attr( $json_file_id ) . '"><strong>' . esc_html__( 'Import JSON', 'toplist' ) . '</strong></label></p>';
	echo '<input type="file" id="' . esc_attr( $json_file_id ) . '" name="toplist_json_file" form="' . esc_attr( $json_form_id ) . '" accept=".json,application/json" />';
	echo '<p style="margin-top:10px;"><button type="button" class="button button-primary" id="' . esc_attr( $json_btn_id ) . '">' . esc_html__( 'Import JSON', 'toplist' ) . '</button></p>';
	echo '<p class="description">' . esc_html__( 'Array of objects: name, rating, launched, regulator, bonus, bonus_link, payout_time, features, games, live_games, withdrawals, code, image_url, visit_link, review_link, payments (see repo toplist/toplist.json).', 'toplist' ) . '</p>';
}

/**
 * Print import &lt;form&gt; tags outside #post (valid HTML; metabox controls use form="…").
 *
 * @return void
 */
function toplist_print_import_forms_in_footer() {
	if ( ! is_admin() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'post' !== $screen->base || 'toplist_list' !== $screen->post_type ) {
		return;
	}
	global $post;
	if ( ! ( $post instanceof WP_Post ) || 'toplist_list' !== $post->post_type ) {
		return;
	}
	$pid     = (int) $post->ID;
	$action  = admin_url( 'admin-post.php' );
	$csv_id  = 'toplist-import-csv-form-' . $pid;
	$json_id = 'toplist-import-json-form-' . $pid;

	echo '<div class="toplist-import-forms" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">';
	echo '<form id="' . esc_attr( $csv_id ) . '" method="post" action="' . esc_url( $action ) . '" enctype="multipart/form-data">';
	wp_nonce_field( 'toplist_import_csv_' . $pid, 'toplist_import_csv_nonce' );
	echo '<input type="hidden" name="action" value="toplist_import_csv" />';
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $pid ) . '" />';
	echo '</form>';
	echo '<form id="' . esc_attr( $json_id ) . '" method="post" action="' . esc_url( $action ) . '" enctype="multipart/form-data">';
	wp_nonce_field( 'toplist_import_json_' . $pid, 'toplist_import_json_nonce' );
	echo '<input type="hidden" name="action" value="toplist_import_json" />';
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $pid ) . '" />';
	echo '</form>';
	echo '</div>';
	echo '<script>(function(){var p=' . (int) $pid . ';var $=function(i){return document.getElementById(i);};';
	echo 'var cf=$("toplist-import-csv-form-"+p),jf=$("toplist-import-json-form-"+p),cb=$("toplist-import-submit-csv-"+p),jb=$("toplist-import-submit-json-"+p),ci=$("toplist-import-file-"+p),ji=$("toplist-json-import-file-"+p);';
	echo 'if(cb&&cf){cb.addEventListener("click",function(){if(!ci||!ci.files||!ci.files.length){window.alert(' . wp_json_encode( __( 'Choose a CSV file first.', 'toplist' ) ) . ');return;}cf.submit();});}';
	echo 'if(jb&&jf){jb.addEventListener("click",function(){if(!ji||!ji.files||!ji.files.length){window.alert(' . wp_json_encode( __( 'Choose a JSON file first.', 'toplist' ) ) . ');return;}jf.submit();});}';
	echo '})();</script>';
}

/**
 * Render GUI row builder metabox for adding one row at a time.
 *
 * @param WP_Post $_post Current post.
 * @return void
 */
function toplist_render_row_builder_metabox( $_post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	$field_labels = array(
		'operator'       => __( 'Operator', 'toplist' ),
		'product'        => __( 'Product', 'toplist' ),
		'offer'          => __( 'Offer', 'toplist' ),
		'href'           => __( 'Primary URL (href)', 'toplist' ),
		'logo'           => __( 'Logo URL', 'toplist' ),
		'year'           => __( 'Launch Year', 'toplist' ),
		'ctaText'        => __( 'CTA Text', 'toplist' ),
		'terms'          => __( 'Terms', 'toplist' ),
		'bullets'        => __( 'Bullets (; separated)', 'toplist' ),
		'payout'         => __( 'Payout', 'toplist' ),
		'code'           => __( 'Code', 'toplist' ),
		'rating'         => __( 'Rating', 'toplist' ),
		'regulator'      => __( 'Regulator', 'toplist' ),
		'payments'       => __( 'Payments (; separated)', 'toplist' ),
		'games'          => __( 'Games (; separated)', 'toplist' ),
		'liveGames'      => __( 'Live Games', 'toplist' ),
		'smallPrint'     => __( 'Small Print', 'toplist' ),
		'readReviewHref' => __( 'Read Review URL', 'toplist' ),
		'readReviewText' => __( 'Read Review Text', 'toplist' ),
		'withdrawals'    => __( 'Withdrawals (; separated)', 'toplist' ),
	);

	echo '<p>' . esc_html__( 'Fill fields below and click "Add Row to Toplist". The row is appended to the raw content textarea.', 'toplist' ) . '</p>';
	echo '<div id="toplist-row-builder" style="display:grid;gap:10px;grid-template-columns:repeat(2,minmax(0,1fr));max-width:980px;">';
	foreach ( $field_labels as $field => $label ) {
		echo '<label style="display:grid;gap:4px;">';
		echo '<span><strong>' . esc_html( $label ) . '</strong></span>';
		echo '<input type="text" data-toplist-field="' . esc_attr( $field ) . '" class="regular-text" style="width:100%;">';
		echo '</label>';
	}
	echo '</div>';
	echo '<p style="margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">';
	echo '<button type="button" class="button" id="toplist-logo-upload">' . esc_html__( 'Upload/Select Logo', 'toplist' ) . '</button>';
	echo '<button type="button" class="button button-primary" id="toplist-add-row">' . esc_html__( 'Add Row to Toplist', 'toplist' ) . '</button>';
	echo '<button type="button" class="button" id="toplist-clear-row-builder">' . esc_html__( 'Clear Builder', 'toplist' ) . '</button>';
	echo '</p>';
	echo '<p class="description">' . esc_html__( 'Logo uploader sets the Logo URL field automatically. Lists should use semicolons between values.', 'toplist' ) . '</p>';
	echo '<script>(function(){';
	echo 'var builder=document.getElementById("toplist-row-builder");';
	echo 'var raw=document.getElementById("toplist_raw_content");';
	echo 'if(!builder||!raw){return;}';
	echo 'function getField(name){return builder.querySelector(\'[data-toplist-field="\'+name+\'"]\');}';
	echo 'function val(name){var i=getField(name);return i?String(i.value||"").trim():"";}';
	echo 'function setVal(name,v){var i=getField(name);if(i){i.value=v||"";}}';
	echo 'var order=' . wp_json_encode( array_values( toplist_supported_fields() ) ) . ';';
	echo 'document.getElementById("toplist-add-row").addEventListener("click",function(){';
	echo 'var parts=[];order.forEach(function(f){parts.push(val(f));});';
	echo 'var row=parts.join("|");';
	echo 'if(!row.replace(/\\|/g,"").trim()){window.alert(' . wp_json_encode( __( 'Please fill at least one field before adding a row.', 'toplist' ) ) . ');return;}';
	echo 'raw.value=(raw.value?raw.value.replace(/\\s+$/,"")+"\\n":"")+row;';
	echo 'raw.dispatchEvent(new Event("change",{bubbles:true}));';
	echo '});';
	echo 'document.getElementById("toplist-clear-row-builder").addEventListener("click",function(){';
	echo 'order.forEach(function(f){setVal(f,"");});';
	echo '});';
	echo 'var uploadBtn=document.getElementById("toplist-logo-upload");';
	echo 'if(uploadBtn){uploadBtn.addEventListener("click",function(e){e.preventDefault();var mediaApi=(window.wp&&window.wp.media)?window.wp.media:null;if(!mediaApi){window.alert(' . wp_json_encode( __( 'Media uploader is not available on this screen. Please refresh and try again.', 'toplist' ) ) . ');return;}var frame=mediaApi({title:' . wp_json_encode( __( 'Select Logo', 'toplist' ) ) . ',button:{text:' . wp_json_encode( __( 'Use this image', 'toplist' ) ) . '},multiple:false});frame.on("select",function(){var selection=frame.state().get("selection");var first=selection&&selection.first?selection.first():null;var attachment=first&&first.toJSON?first.toJSON():null;if(attachment&&attachment.url){setVal("logo",attachment.url);}});frame.open();});}';
	echo '})();</script>';
}

/**
 * Register metaboxes for Toplists CPT edit screen.
 *
 * @return void
 */
function toplist_register_toplist_metaboxes() {
	add_meta_box(
		'toplist_raw_content_box',
		__( 'Toplist Content', 'toplist' ),
		'toplist_render_raw_content_metabox',
		'toplist_list',
		'normal',
		'high'
	);

	add_meta_box(
		'toplist_csv_tools_box',
		__( 'Import / Export', 'toplist' ),
		'toplist_render_csv_tools_metabox',
		'toplist_list',
		'side',
		'default'
	);

	add_meta_box(
		'toplist_row_builder_box',
		__( 'Add Row (GUI Builder)', 'toplist' ),
		'toplist_render_row_builder_metabox',
		'toplist_list',
		'normal',
		'default'
	);
}

/**
 * Save plain textarea content back into post_content for toplist_list.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function toplist_save_toplist_raw_content( $post_id ) {
	if ( ! isset( $_POST['toplist_raw_content_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['toplist_raw_content_nonce'] ) ), 'toplist_save_raw_content' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['toplist_raw_content'] ) ) {
		return;
	}

	$raw_content = sanitize_textarea_field( wp_unslash( $_POST['toplist_raw_content'] ) );

	remove_action( 'save_post_toplist_list', 'toplist_save_toplist_raw_content' );
	wp_update_post(
		array(
			'ID'           => (int) $post_id,
			'post_content' => $raw_content,
		)
	);
	add_action( 'save_post_toplist_list', 'toplist_save_toplist_raw_content' );
}

/**
 * Normalize CSV header to canonical field key.
 *
 * @param string $value Header cell.
 * @return string
 */
function toplist_normalize_csv_header( $value ) {
	$normalized = strtolower( trim( (string) $value ) );
	$normalized = preg_replace( '/^\xEF\xBB\xBF/u', '', $normalized );
	$lookup     = array();
	foreach ( toplist_supported_fields() as $field ) {
		$lookup[ strtolower( $field ) ] = $field;
	}
	return $lookup[ $normalized ] ?? '';
}

/**
 * Export one saved toplist as CSV.
 *
 * @return void
 */
function toplist_handle_export_csv() {
	$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to export this toplist.', 'toplist' ) );
	}
	check_admin_referer( 'toplist_export_csv_' . $post_id );

	$post = get_post( $post_id );
	if ( ! $post || 'toplist_list' !== $post->post_type ) {
		wp_die( esc_html__( 'Toplist not found.', 'toplist' ) );
	}

	$parsed = toplist_parse_lines_to_items( (string) $post->post_content, array() );
	$items  = is_array( $parsed['items'] ?? null ) ? $parsed['items'] : array();
	$fields = toplist_supported_fields();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=toplist-' . $post_id . '.csv' );

	$out = fopen( 'php://output', 'w' );
	if ( false !== $out ) {
		fputcsv( $out, $fields );

		foreach ( $items as $item ) {
			$row = array();
			foreach ( $fields as $field ) {
				$value = $item[ $field ] ?? '';
				if ( in_array( $field, array( 'bullets', 'payments', 'games', 'withdrawals' ), true ) ) {
					$value = is_array( $value ) ? implode( ';', $value ) : toplist_clean_text( is_scalar( $value ) ? (string) $value : '' );
				}
				$row[] = is_scalar( $value ) ? (string) $value : '';
			}
			fputcsv( $out, $row );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing an output stream handle created for CSV export.
	}
	exit;
}

/**
 * Export one saved toplist as JSON (toplist.json schema).
 *
 * @return void
 */
function toplist_handle_export_json() {
	$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to export this toplist.', 'toplist' ) );
	}
	check_admin_referer( 'toplist_export_json_' . $post_id );

	$post = get_post( $post_id );
	if ( ! $post || 'toplist_list' !== $post->post_type ) {
		wp_die( esc_html__( 'Toplist not found.', 'toplist' ) );
	}

	$parsed = toplist_parse_lines_to_items( (string) $post->post_content, array() );
	$items  = is_array( $parsed['items'] ?? null ) ? $parsed['items'] : array();
	$rows   = toplist_items_to_external_json_rows( $items );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=toplist-' . $post_id . '.json' );

	echo wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}

/**
 * Whether an upload filename matches the expected extension and MIME map.
 *
 * @param string $filename Original upload name.
 * @param string $extension Expected extension without dot (json|csv).
 * @return bool
 */
function toplist_upload_filename_matches_extension( string $filename, string $extension ): bool {
	$extension = strtolower( $extension );
	if ( '' === $filename || ! in_array( $extension, array( 'json', 'csv' ), true ) ) {
		return false;
	}

	$ext = strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );
	if ( $ext !== $extension ) {
		return false;
	}

	$mimes   = 'json' === $extension
		? array( 'json' => 'application/json' )
		: array(
			'csv' => 'text/csv',
			'txt' => 'text/plain',
		);
	$checked = wp_check_filetype( $filename, $mimes );
	if ( ! empty( $checked['ext'] ) && $checked['ext'] === $ext ) {
		return true;
	}

	// Excel and some hosts label CSV uploads as text/plain.
	if ( 'csv' === $extension ) {
		$plain = wp_check_filetype( $filename, array( 'csv' => 'text/plain' ) );
		return ! empty( $plain['ext'] ) && 'csv' === $plain['ext'];
	}

	return false;
}

/**
 * Read sanitized upload metadata for import handlers after caller nonce checks.
 *
 * @param string $files_key $_FILES key.
 * @return array{name:string,tmp_name:string,error:int}
 */
function toplist_get_uploaded_import_file( string $files_key ): array {
	if ( '' === $files_key ) {
		return array(
			'name'     => '',
			'tmp_name' => '',
			'error'    => UPLOAD_ERR_NO_FILE,
		);
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Callers verify nonces before reading upload metadata.
	if ( ! isset( $_FILES[ $files_key ] ) || ! is_array( $_FILES[ $files_key ] ) ) {
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return array(
			'name'     => '',
			'tmp_name' => '',
			'error'    => UPLOAD_ERR_NO_FILE,
		);
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Callers verify nonces before reading upload metadata.
	$file = $_FILES[ $files_key ];
	// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	return array(
		'name'     => isset( $file['name'] ) ? sanitize_file_name( (string) wp_unslash( $file['name'] ) ) : '',
		'tmp_name' => isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '',
		'error'    => isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE,
	);
}

/**
 * Validate a $_FILES upload entry for import handlers.
 *
 * @param string $files_key $_FILES key.
 * @param string $extension Expected extension without dot (json|csv).
 * @return string Empty when valid; otherwise error code for redirects.
 */
function toplist_validate_uploaded_import_file( $files_key, $extension ) {
	$file = toplist_get_uploaded_import_file( $files_key );
	if ( UPLOAD_ERR_NO_FILE === $file['error'] && '' === $file['tmp_name'] && '' === $file['name'] ) {
		return 'empty';
	}

	$tmp = $file['tmp_name'];
	if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
		return 'empty';
	}

	$error = $file['error'];
	if ( UPLOAD_ERR_INI_SIZE === $error || UPLOAD_ERR_FORM_SIZE === $error ) {
		return 'upload_too_large';
	}
	if ( UPLOAD_ERR_OK !== $error ) {
		return 'failed';
	}

	$name = $file['name'];
	if ( ! toplist_upload_filename_matches_extension( $name, $extension ) ) {
		return 'invalid_type';
	}

	return '';
}

/**
 * Import JSON (toplist.json schema) into one saved toplist.
 *
 * @return void
 */
function toplist_handle_import_json() {
	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to import into this toplist.', 'toplist' ) );
	}
	if ( ! isset( $_POST['toplist_import_json_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['toplist_import_json_nonce'] ) ), 'toplist_import_json_' . $post_id ) ) {
		wp_die( esc_html__( 'Invalid JSON import request.', 'toplist' ) );
	}
	$upload_status = toplist_validate_uploaded_import_file( 'toplist_json_file', 'json' );
	if ( '' !== $upload_status ) {
		wp_safe_redirect( add_query_arg( 'toplist_json_import', $upload_status, get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$upload = toplist_get_uploaded_import_file( 'toplist_json_file' );
	$tmp    = $upload['tmp_name'];
	$raw    = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a verified local upload temp file, not a remote URL.
	if ( false === $raw ) {
		wp_safe_redirect( add_query_arg( 'toplist_json_import', 'failed', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$result = toplist_decode_external_toplist_json( $raw );
	if ( 'invalid' === $result['error'] ) {
		wp_safe_redirect( add_query_arg( 'toplist_json_import', 'invalid', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}
	if ( 'empty' === $result['error'] ) {
		wp_safe_redirect( add_query_arg( 'toplist_json_import', 'empty', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$items       = is_array( $result['items'] ?? null ) ? $result['items'] : array();
	$new_content = toplist_items_to_pipe_content( $items, array() );
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $new_content,
		)
	);

	wp_safe_redirect( add_query_arg( 'toplist_json_import', 'success', get_edit_post_link( $post_id, '' ) ) );
	exit;
}

/**
 * Export all saved toplists as a single CSV.
 *
 * @return void
 */
function toplist_handle_export_all_csv() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to export toplists.', 'toplist' ) );
	}
	check_admin_referer( 'toplist_export_all_csv' );

	$posts = get_posts(
		array(
			'post_type'      => 'toplist_list',
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	$fields      = toplist_supported_fields();
	$csv_headers = array_merge( array( 'toplist', 'toplist_id' ), $fields );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=toplists-all.csv' );

	$out = fopen( 'php://output', 'w' );
	if ( false !== $out ) {
		fputcsv( $out, $csv_headers );

		foreach ( $posts as $post ) {
			if ( ! ( $post instanceof WP_Post ) ) {
				continue;
			}

			$parsed = toplist_parse_lines_to_items( (string) $post->post_content, array() );
			$items  = is_array( $parsed['items'] ?? null ) ? $parsed['items'] : array();

			foreach ( $items as $item ) {
				$row = array( (string) $post->post_title, (string) $post->ID );
				foreach ( $fields as $field ) {
					$value = $item[ $field ] ?? '';
					if ( in_array( $field, array( 'bullets', 'payments', 'games', 'withdrawals' ), true ) ) {
						$value = is_array( $value ) ? implode( ';', $value ) : toplist_clean_text( is_scalar( $value ) ? (string) $value : '' );
					}
					$row[] = is_scalar( $value ) ? (string) $value : '';
				}
				fputcsv( $out, $row );
			}
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing an output stream handle created for CSV export.
	}
	exit;
}

/**
 * Parse a bulk CSV file and create/update multiple toplists.
 *
 * @param string $file Path to uploaded CSV temporary file.
 * @return array{status:string,updated:int,created:int,rows:int,groups:int}
 */
function toplist_process_bulk_import_csv_file( string $file ): array {
	if ( '' === $file ) {
		return array(
			'status'  => 'empty',
			'updated' => 0,
			'created' => 0,
			'rows'    => 0,
			'groups'  => 0,
		);
	}

	$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading a verified local CSV upload temp file.
	if ( ! $handle ) {
		return array(
			'status'  => 'failed',
			'updated' => 0,
			'created' => 0,
			'rows'    => 0,
			'groups'  => 0,
		);
	}

	$header_row = fgetcsv( $handle );
	if ( ! is_array( $header_row ) || empty( $header_row ) ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.
		return array(
			'status'  => 'empty',
			'updated' => 0,
			'created' => 0,
			'rows'    => 0,
			'groups'  => 0,
		);
	}

	$name_index    = -1;
	$id_index      = -1;
	$field_indexes = array();
	$header_fields = array();
	foreach ( $header_row as $i => $cell ) {
		$raw = strtolower( trim( (string) $cell ) );
		$raw = preg_replace( '/^\xEF\xBB\xBF/u', '', $raw );
		if ( in_array( $raw, array( 'toplist', 'toplist_name', 'name', 'list' ), true ) ) {
			$name_index = (int) $i;
			continue;
		}
		if ( in_array( $raw, array( 'toplist_id', 'list_id', 'id' ), true ) ) {
			$id_index = (int) $i;
			continue;
		}
		$field = toplist_normalize_csv_header( (string) $cell );
		if ( '' === $field ) {
			continue;
		}
		$field_indexes[ (int) $i ] = $field;
		if ( ! in_array( $field, $header_fields, true ) ) {
			$header_fields[] = $field;
		}
	}

	if ( ( $name_index < 0 && $id_index < 0 ) || empty( $header_fields ) ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.
		return array(
			'status'  => 'bad_header',
			'updated' => 0,
			'created' => 0,
			'rows'    => 0,
			'groups'  => 0,
		);
	}

	$groups    = array();
	$rows_read = 0;
	$csv_row   = fgetcsv( $handle );
	while ( false !== $csv_row ) {
		++$rows_read;
		$name    = $name_index >= 0 ? trim( (string) ( $csv_row[ $name_index ] ?? '' ) ) : '';
		$raw_id  = $id_index >= 0 ? trim( (string) ( $csv_row[ $id_index ] ?? '' ) ) : '';
		$post_id = '' !== $raw_id ? (int) $raw_id : 0;

		// Fallback for exports where name/id headers are present but not recognized as expected.
		if ( '' === $name ) {
			$fallback_name = trim( (string) ( $csv_row[0] ?? '' ) );
			if ( '' !== $fallback_name && ! is_numeric( $fallback_name ) ) {
				$name = $fallback_name;
			}
		}
		if ( $post_id <= 0 && '' === $raw_id ) {
			$fallback_id = trim( (string) ( $csv_row[1] ?? '' ) );
			if ( '' !== $fallback_id && ctype_digit( $fallback_id ) ) {
				$post_id = (int) $fallback_id;
			}
		}

		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			if ( ! $post || 'toplist_list' !== $post->post_type ) {
				$post_id = 0;
			}
		}

		if ( $post_id <= 0 && '' === $name ) {
			continue;
		}

		$key = $post_id > 0 ? 'id:' . $post_id : 'name:' . strtolower( $name );
		if ( ! isset( $groups[ $key ] ) ) {
			$groups[ $key ] = array(
				'post_id' => $post_id,
				'name'    => $name,
				'rows'    => array(),
			);
		}

		$row_values = array();
		foreach ( $field_indexes as $index => $field ) {
			$row_values[ $field ] = trim( (string) ( $csv_row[ $index ] ?? '' ) );
		}

		$line_parts = array();
		foreach ( $header_fields as $field ) {
			$line_parts[] = $row_values[ $field ] ?? '';
		}
		$line = implode( '|', $line_parts );
		if ( trim( $line ) !== '' ) {
			$groups[ $key ]['rows'][] = $line;
		}
		$csv_row = fgetcsv( $handle );
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.

	if ( empty( $groups ) ) {
		return array(
			'status'  => 'empty',
			'updated' => 0,
			'created' => 0,
			'rows'    => $rows_read,
			'groups'  => 0,
		);
	}

	$updated = 0;
	$created = 0;
	foreach ( $groups as $group ) {
		if ( empty( $group['rows'] ) ) {
			continue;
		}

		$lines   = array( implode( '|', $header_fields ) );
		$lines   = array_merge( $lines, $group['rows'] );
		$content = implode( "\n", $lines );

		$post_id = (int) $group['post_id'];
		$name    = trim( (string) $group['name'] );

		if ( $post_id > 0 ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $content,
				)
			);
			++$updated;
			continue;
		}

		$existing = array();
		if ( '' !== $name ) {
			$existing = get_posts(
				array(
					'post_type'      => 'toplist_list',
					'post_status'    => array( 'publish', 'private', 'draft' ),
					'title'          => $name,
					'posts_per_page' => 1,
					'no_found_rows'  => true,
				)
			);
		}

		if ( ! empty( $existing ) && $existing[0] instanceof WP_Post ) {
			wp_update_post(
				array(
					'ID'           => (int) $existing[0]->ID,
					'post_content' => $content,
				)
			);
			++$updated;
			continue;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'toplist_list',
				'post_title'   => '' !== $name ? $name : __( 'Imported Toplist', 'toplist' ),
				'post_content' => $content,
				'post_status'  => 'publish',
			),
			true
		);

		if ( ! is_wp_error( $new_id ) && is_int( $new_id ) ) {
			++$created;
		}
	}

	return array(
		'status'  => ( $updated + $created ) > 0 ? 'success' : 'no_changes',
		'updated' => $updated,
		'created' => $created,
		'rows'    => $rows_read,
		'groups'  => count( $groups ),
	);
}

/**
 * Import a single CSV and create/update multiple toplists.
 *
 * Expected CSV columns:
 * - `toplist` (or `name`) and/or `toplist_id`
 * - standard toplist fields (operator, product, offer, etc.)
 *
 * @return void
 */
function toplist_handle_import_all_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to bulk import toplists.', 'toplist' ) );
	}

	check_admin_referer( 'toplist_import_all_csv', 'toplist_import_all_csv_nonce' );

	$redirect_url  = admin_url( 'options-general.php?page=toplist-settings' );
	$upload_status = toplist_validate_uploaded_import_file( 'toplist_bulk_csv_file', 'csv' );
	$upload        = toplist_get_uploaded_import_file( 'toplist_bulk_csv_file' );
	if ( '' !== $upload_status ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'toplist_bulk_import'       => $upload_status,
					'toplist_bulk_updated'      => 0,
					'toplist_bulk_created'      => 0,
					'toplist_bulk_rows'         => 0,
					'toplist_bulk_groups'       => 0,
					'toplist_bulk_upload_error' => $upload['error'],
				),
				$redirect_url
			)
		);
		exit;
	}

	$result       = toplist_process_bulk_import_csv_file( $upload['tmp_name'] );
	$upload_error = $upload['error'];
	wp_safe_redirect(
		add_query_arg(
			array(
				'toplist_bulk_import'       => $result['status'],
				'toplist_bulk_updated'      => (int) $result['updated'],
				'toplist_bulk_created'      => (int) $result['created'],
				'toplist_bulk_rows'         => (int) $result['rows'],
				'toplist_bulk_groups'       => (int) $result['groups'],
				'toplist_bulk_upload_error' => $upload_error,
			),
			$redirect_url
		)
	);
	exit;
}

/**
 * Download a CSV template for bulk import.
 *
 * @return void
 */
function toplist_handle_export_bulk_template_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export the bulk template.', 'toplist' ) );
	}
	check_admin_referer( 'toplist_export_bulk_template_csv' );

	$fields  = toplist_supported_fields();
	$headers = array_merge( array( 'toplist', 'toplist_id' ), $fields );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=toplist-bulk-import-template.csv' );

	$out = fopen( 'php://output', 'w' );
	if ( false !== $out ) {
		fputcsv( $out, $headers );

		$sample     = array_fill( 0, count( $headers ), '' );
		$sample_map = array(
			'toplist'        => 'Example Toplist',
			'operator'       => 'Operator Name',
			'product'        => 'Casino Brand',
			'offer'          => '100% Bonus + 50 Free Spins',
			'href'           => 'https://example.com',
			'logo'           => 'https://example.com/logo.png',
			'year'           => '2026',
			'ctaText'        => 'Visit Casino',
			'terms'          => '18+ T&Cs apply',
			'bullets'        => 'Fast payouts;Welcome bonus',
			'payout'         => 'Instant',
			'code'           => 'WELCOME50',
			'rating'         => '4.8',
			'regulator'      => 'MGA',
			'payments'       => 'Visa;Mastercard;PayPal',
			'games'          => 'Slots;Live Casino',
			'liveGames'      => 'Yes',
			'smallPrint'     => 'Wagering requirements apply',
			'readReviewHref' => 'https://example.com/review',
			'readReviewText' => 'Read Review',
			'withdrawals'    => 'Bank Transfer;Skrill',
		);

		foreach ( $headers as $i => $header ) {
			$sample[ $i ] = (string) ( $sample_map[ $header ] ?? '' );
		}
		fputcsv( $out, $sample );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing an output stream handle created for CSV export.
	}
	exit;
}

/**
 * Import CSV into one saved toplist.
 *
 * @return void
 */
function toplist_handle_import_csv() {
	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to import into this toplist.', 'toplist' ) );
	}
	if ( ! isset( $_POST['toplist_import_csv_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['toplist_import_csv_nonce'] ) ), 'toplist_import_csv_' . $post_id ) ) {
		wp_die( esc_html__( 'Invalid CSV import request.', 'toplist' ) );
	}
	$upload_status = toplist_validate_uploaded_import_file( 'toplist_csv_file', 'csv' );
	if ( '' !== $upload_status ) {
		wp_safe_redirect( add_query_arg( 'toplist_import', $upload_status, get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$upload = toplist_get_uploaded_import_file( 'toplist_csv_file' );
	$file   = $upload['tmp_name'];
	$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading a verified local CSV upload temp file.
	if ( ! $handle ) {
		wp_safe_redirect( add_query_arg( 'toplist_import', 'failed', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$header_row = fgetcsv( $handle );
	if ( ! is_array( $header_row ) ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.
		wp_safe_redirect( add_query_arg( 'toplist_import', 'empty', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$normalized_headers = array();
	foreach ( $header_row as $header_cell ) {
		$normalized_headers[] = toplist_normalize_csv_header( $header_cell );
	}

	$recognized_headers = array_values( array_filter( $normalized_headers ) );
	$use_index_mapping  = count( $recognized_headers ) < 3;
	$fields             = toplist_supported_fields();
	$columns            = $use_index_mapping ? $fields : $normalized_headers;
	$header_for_lines   = $use_index_mapping ? $fields : array_values( array_filter( $normalized_headers ) );

	$lines = array();
	if ( ! empty( $header_for_lines ) ) {
		$lines[] = implode( '|', $header_for_lines );
	}

	$csv_row = fgetcsv( $handle );
	while ( false !== $csv_row ) {
		$values_by_field = array();
		foreach ( $fields as $field ) {
			$values_by_field[ $field ] = '';
		}

		$i_count = count( $columns );
		for ( $i = 0; $i < $i_count; ++$i ) {
			$field = $columns[ $i ] ?? '';
			if ( '' === $field || ! in_array( $field, $fields, true ) ) {
				continue;
			}
			$values_by_field[ $field ] = trim( (string) ( $csv_row[ $i ] ?? '' ) );
		}

		$line_parts = array();
		foreach ( $header_for_lines as $field ) {
			$line_parts[] = $values_by_field[ $field ] ?? '';
		}
		$line = implode( '|', $line_parts );
		if ( trim( $line ) !== '' ) {
			$lines[] = $line;
		}
		$csv_row = fgetcsv( $handle );
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.

	$new_content = implode( "\n", $lines );
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $new_content,
		)
	);

	wp_safe_redirect( add_query_arg( 'toplist_import', 'success', get_edit_post_link( $post_id, '' ) ) );
	exit;
}

/**
 * Show import status notices on toplist edit screen.
 *
 * @return void
 */
function toplist_import_admin_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->base || 'toplist_list' !== $screen->post_type ) {
		return;
	}
	$status_param = filter_input( INPUT_GET, 'toplist_import', FILTER_UNSAFE_RAW );
	$status       = is_string( $status_param ) ? sanitize_key( $status_param ) : '';

	if ( '' !== $status ) {
		$message = '';
		$class   = 'notice notice-info';
		if ( 'success' === $status ) {
			$message = __( 'CSV imported successfully.', 'toplist' );
			$class   = 'notice notice-success';
		} elseif ( 'empty' === $status ) {
			$message = __( 'CSV import failed: file was empty or invalid.', 'toplist' );
			$class   = 'notice notice-warning';
		} elseif ( 'failed' === $status ) {
			$message = __( 'CSV import failed: unable to read the uploaded file.', 'toplist' );
			$class   = 'notice notice-error';
		} elseif ( 'invalid_type' === $status ) {
			$message = __( 'CSV import failed: upload must be a .csv file.', 'toplist' );
			$class   = 'notice notice-error';
		} elseif ( 'upload_too_large' === $status ) {
			$message = __( 'CSV import failed: uploaded file is too large for current PHP upload limits.', 'toplist' );
			$class   = 'notice notice-error';
		}

		if ( '' !== $message ) {
			echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	$json_status_param = filter_input( INPUT_GET, 'toplist_json_import', FILTER_UNSAFE_RAW );
	$json_status       = is_string( $json_status_param ) ? sanitize_key( $json_status_param ) : '';
	if ( '' !== $json_status ) {
		$json_message = '';
		$json_class   = 'notice notice-info';
		if ( 'success' === $json_status ) {
			$json_message = __( 'JSON imported successfully.', 'toplist' );
			$json_class   = 'notice notice-success';
		} elseif ( 'empty' === $json_status ) {
			$json_message = __( 'JSON import failed: file was empty or no valid rows.', 'toplist' );
			$json_class   = 'notice notice-warning';
		} elseif ( 'failed' === $json_status ) {
			$json_message = __( 'JSON import failed: unable to read the uploaded file.', 'toplist' );
			$json_class   = 'notice notice-error';
		} elseif ( 'invalid' === $json_status ) {
			$json_message = __( 'JSON import failed: invalid JSON (expected a JSON array).', 'toplist' );
			$json_class   = 'notice notice-error';
		} elseif ( 'invalid_type' === $json_status ) {
			$json_message = __( 'JSON import failed: upload must be a .json file.', 'toplist' );
			$json_class   = 'notice notice-error';
		} elseif ( 'upload_too_large' === $json_status ) {
			$json_message = __( 'JSON import failed: uploaded file is too large for current PHP upload limits.', 'toplist' );
			$json_class   = 'notice notice-error';
		}

		if ( '' !== $json_message ) {
			echo '<div class="' . esc_attr( $json_class ) . '"><p>' . esc_html( $json_message ) . '</p></div>';
		}
	}
}

/**
 * Enqueue media uploader for toplist edit screen row builder.
 *
 * @param string $hook_suffix Admin hook.
 * @return void
 */
function toplist_enqueue_toplist_admin_assets( $hook_suffix ) {
	if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
		return;
	}
	$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$post_type = '';
	if ( $screen && ! empty( $screen->post_type ) ) {
		$post_type = (string) $screen->post_type;
	} else {
		$post_type_param = filter_input( INPUT_GET, 'post_type', FILTER_UNSAFE_RAW );
		if ( is_string( $post_type_param ) && '' !== $post_type_param ) {
			$post_type = sanitize_key( $post_type_param );
		} else {
			$post_id_param = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
			if ( is_string( $post_id_param ) && '' !== $post_id_param ) {
				$post_type_value = get_post_type( (int) $post_id_param );
				if ( is_string( $post_type_value ) ) {
					$post_type = $post_type_value;
				}
			}
		}
	}
	if ( 'toplist_list' !== $post_type ) {
		return;
	}
	wp_enqueue_media();
	$fields = toplist_supported_fields();
	wp_enqueue_script(
		'toplist-admin-spreadsheet',
		plugins_url( 'assets/admin-spreadsheet.js', TOPLIST_BLOCK_PATH . '/toplist-block.php' ),
		array(),
		defined( 'TOPLIST_BLOCK_VERSION' ) ? TOPLIST_BLOCK_VERSION : '1.0',
		true
	);
	wp_add_inline_script(
		'toplist-admin-spreadsheet',
		'window.toplistSpreadsheetFields = ' . wp_json_encode( array_values( $fields ) ) . ';',
		'before'
	);
}

/**
 * Register library/import features only when license is valid.
 *
 * @return void
 */
function toplist_boot_premium_features() {
	if ( ! class_exists( 'Toplist_Block_License' ) || ! Toplist_Block_License::is_valid() ) {
		return;
	}

	add_action( 'init', 'toplist_register_cpt' );
	add_filter( 'use_block_editor_for_post_type', 'toplist_disable_block_editor_for_toplists', 10, 2 );
	add_filter( 'manage_toplist_list_posts_columns', 'toplist_toplists_admin_columns' );
	add_action( 'manage_toplist_list_posts_custom_column', 'toplist_toplists_admin_column_content', 10, 2 );
	add_action( 'rest_api_init', 'toplist_register_rest_routes' );
	add_action( 'add_meta_boxes_toplist_list', 'toplist_register_toplist_metaboxes' );
	add_action( 'save_post_toplist_list', 'toplist_save_toplist_raw_content' );
	add_action( 'admin_post_toplist_export_csv', 'toplist_handle_export_csv' );
	add_action( 'admin_post_toplist_export_json', 'toplist_handle_export_json' );
	add_action( 'admin_post_toplist_export_all_csv', 'toplist_handle_export_all_csv' );
	add_action( 'admin_post_toplist_export_bulk_template_csv', 'toplist_handle_export_bulk_template_csv' );
	add_action( 'admin_post_toplist_import_csv', 'toplist_handle_import_csv' );
	add_action( 'admin_post_toplist_import_json', 'toplist_handle_import_json' );
	add_action( 'admin_post_toplist_import_all_csv', 'toplist_handle_import_all_csv' );
	add_action( 'admin_notices', 'toplist_import_admin_notice' );
	add_action( 'admin_enqueue_scripts', 'toplist_enqueue_toplist_admin_assets' );
	add_action( 'admin_footer', 'toplist_print_import_forms_in_footer', 5 );

	if ( function_exists( 'toplist_register_editor_ux_hooks' ) ) {
		toplist_register_editor_ux_hooks();
	}
	if ( function_exists( 'toplist_register_geo_hooks' ) ) {
		toplist_register_geo_hooks();
	}
	if ( function_exists( 'toplist_register_click_tracking_hooks' ) ) {
		toplist_register_click_tracking_hooks();
	}
	if ( function_exists( 'toplist_register_card_builder_hooks' ) ) {
		toplist_register_card_builder_hooks();
	}
	add_action( 'save_post_toplist_list', 'toplist_save_remote_source_meta' );
}

add_action( 'plugins_loaded', 'toplist_boot_premium_features', 20 );
