<?php
/**
 * Premium library: Toplists CPT edit-screen metaboxes and raw content save.
 *
 * Extracted from library.php (ticket: library split).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
 * Enqueue media uploader and spreadsheet assets for toplist edit screen.
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
