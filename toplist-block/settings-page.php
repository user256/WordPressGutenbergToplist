<?php
/**
 * Global CSS Settings Page for Toplist Block
 * 
 * This file adds a settings page where users can define
 * global CSS that applies to ALL toplist blocks on the site.
 */

if (!defined('ABSPATH'))
    exit;

// Add settings page to admin menu
add_action('admin_menu', 'toplist_add_settings_page');

function toplist_add_settings_page()
{
    add_options_page(
        'Toplist Block Settings',
        'Toplist Block',
        'manage_options',
        'toplist-settings',
        'toplist_settings_page'
    );
}

// Register settings
add_action('admin_init', 'toplist_register_settings');

function toplist_register_settings()
{
    register_setting('toplist_settings', 'toplist_global_css', array(
        'type' => 'string',
        'sanitize_callback' => 'toplist_sanitize_css',
        'default' => ''
    ));
    register_setting('toplist_settings', 'toplist_global_enable_default_header');
    register_setting('toplist_settings', 'toplist_global_default_header_row');
    register_setting('toplist_settings', 'toplist_global_heading_text');
    register_setting('toplist_settings', 'toplist_global_default_cta_text');
    register_setting('toplist_settings', 'toplist_global_default_read_review_text');
    register_setting('toplist_settings', 'toplist_global_show_logo');
    register_setting('toplist_settings', 'toplist_global_show_year');
    register_setting('toplist_settings', 'toplist_global_show_offer');
    register_setting('toplist_settings', 'toplist_global_show_terms');
    register_setting('toplist_settings', 'toplist_global_show_bullets');
    register_setting('toplist_settings', 'toplist_global_show_payout');
    register_setting('toplist_settings', 'toplist_global_show_code');
    register_setting('toplist_settings', 'toplist_global_show_rating');
    register_setting('toplist_settings', 'toplist_global_show_regulator');
    register_setting('toplist_settings', 'toplist_global_show_payments');
    register_setting('toplist_settings', 'toplist_global_show_games');
    register_setting('toplist_settings', 'toplist_global_show_live_games');
    register_setting('toplist_settings', 'toplist_global_show_small_print');
    register_setting('toplist_settings', 'toplist_global_show_read_review');
    register_setting('toplist_settings', 'toplist_global_show_withdrawals');
}

// Sanitize CSS input
function toplist_sanitize_css($css)
{
    // Remove any <script> tags or dangerous content
    $css = strip_tags($css);
    return $css;
}

// Settings page HTML
function toplist_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $toggle_groups = array(
        'Identity & Trust' => array(
            'toplist_global_show_logo' => 'Show Logo',
            'toplist_global_show_year' => 'Show Launch Year',
            'toplist_global_show_rating' => 'Show Rating',
            'toplist_global_show_regulator' => 'Show Regulator',
        ),
        'Offer & Terms' => array(
            'toplist_global_show_offer' => 'Show Offer',
            'toplist_global_show_terms' => 'Show Terms & Conditions',
            'toplist_global_show_bullets' => 'Show Bullet Points',
            'toplist_global_show_payout' => 'Show Payout',
            'toplist_global_show_small_print' => 'Show Small Print',
        ),
        'Actions & Payments' => array(
            'toplist_global_show_code' => 'Show Code',
            'toplist_global_show_read_review' => 'Show Read Review',
            'toplist_global_show_payments' => 'Show Payments',
        ),
        'Game Details' => array(
            'toplist_global_show_games' => 'Show Games',
            'toplist_global_show_live_games' => 'Show Live Games',
            'toplist_global_show_withdrawals' => 'Show Withdrawals',
        ),
    );
    $toggle_fields = array();
    foreach ($toggle_groups as $group_fields) {
        foreach ($group_fields as $option_name => $label) {
            $toggle_fields[$option_name] = $label;
        }
    }

    $local_bulk_result = null;
    $posted_action = isset($_POST['action']) ? sanitize_key((string) wp_unslash($_POST['action'])) : '';
    $bulk_import_requested = isset($_POST['toplist_bulk_import_submit'])
        || $posted_action === 'toplist_import_all_csv';
    if ($bulk_import_requested) {
        if (!isset($_POST['toplist_import_all_csv_nonce']) || !wp_verify_nonce(sanitize_text_field((string) wp_unslash($_POST['toplist_import_all_csv_nonce'])), 'toplist_import_all_csv')) {
            $local_bulk_result = array(
                'status' => 'bad_nonce',
                'updated' => 0,
                'created' => 0,
                'rows' => 0,
                'groups' => 0,
            );
        } else {
            $upload_error = isset($_FILES['toplist_bulk_csv_file']['error']) ? (int) $_FILES['toplist_bulk_csv_file']['error'] : UPLOAD_ERR_NO_FILE;
            if ($upload_error !== UPLOAD_ERR_OK) {
                $status = 'failed';
                if ($upload_error === UPLOAD_ERR_NO_FILE) {
                    $status = 'empty';
                } elseif ($upload_error === UPLOAD_ERR_INI_SIZE || $upload_error === UPLOAD_ERR_FORM_SIZE) {
                    $status = 'upload_too_large';
                }
                $local_bulk_result = array(
                    'status' => $status,
                    'updated' => 0,
                    'created' => 0,
                    'rows' => 0,
                    'groups' => 0,
                    'upload_error' => $upload_error,
                );
            } else {
                $tmp_file = $_FILES['toplist_bulk_csv_file']['tmp_name'] ?? '';
                if (function_exists('toplist_process_bulk_import_csv_file')) {
                    $local_bulk_result = toplist_process_bulk_import_csv_file($tmp_file);
                } else {
                    $local_bulk_result = array(
                        'status' => 'failed',
                        'updated' => 0,
                        'created' => 0,
                        'rows' => 0,
                        'groups' => 0,
                    );
                }
            }
        }
    }

    // Save settings
    if (isset($_POST['toplist_save_settings'])) {
        check_admin_referer('toplist_settings_nonce');
        update_option('toplist_global_css', toplist_sanitize_css(wp_unslash($_POST['toplist_global_css'] ?? '')));
        update_option('toplist_global_enable_default_header', !empty($_POST['toplist_global_enable_default_header']) ? '1' : '0');
        update_option('toplist_global_default_header_row', sanitize_text_field(wp_unslash($_POST['toplist_global_default_header_row'] ?? '')));
        update_option('toplist_global_heading_text', sanitize_text_field(wp_unslash($_POST['toplist_global_heading_text'] ?? '')));
        update_option('toplist_global_default_cta_text', sanitize_text_field(wp_unslash($_POST['toplist_global_default_cta_text'] ?? 'Visit')));
        update_option('toplist_global_default_read_review_text', sanitize_text_field(wp_unslash($_POST['toplist_global_default_read_review_text'] ?? 'Read Review')));

        foreach ($toggle_fields as $option_name => $label) {
            update_option($option_name, !empty($_POST[$option_name]) ? '1' : '0');
        }

        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    $global_css = get_option('toplist_global_css', '');
    $global_enable_default_header = get_option('toplist_global_enable_default_header', '0');
    $global_default_header_row = get_option('toplist_global_default_header_row', '');
    $global_heading_text = get_option('toplist_global_heading_text', '');
    $global_default_cta_text = get_option('toplist_global_default_cta_text', 'Visit');
    $global_default_read_review_text = get_option('toplist_global_default_read_review_text', 'Read Review');
    $bulk_export_url = wp_nonce_url(
        admin_url('admin-post.php?action=toplist_export_all_csv'),
        'toplist_export_all_csv'
    );
    $bulk_template_url = wp_nonce_url(
        admin_url('admin-post.php?action=toplist_export_bulk_template_csv'),
        'toplist_export_bulk_template_csv'
    );
    ?>
    <div class="wrap">
        <h1>Toplist Block - Global Settings</h1>

        <?php
        $bulk_import_status = '';
        $bulk_updated = 0;
        $bulk_created = 0;
        $bulk_rows = 0;
        $bulk_groups = 0;
        $bulk_upload_error = 0;
        if (is_array($local_bulk_result)) {
            $bulk_import_status = sanitize_key((string) ($local_bulk_result['status'] ?? ''));
            $bulk_updated = (int) ($local_bulk_result['updated'] ?? 0);
            $bulk_created = (int) ($local_bulk_result['created'] ?? 0);
            $bulk_rows = (int) ($local_bulk_result['rows'] ?? 0);
            $bulk_groups = (int) ($local_bulk_result['groups'] ?? 0);
            $bulk_upload_error = (int) ($local_bulk_result['upload_error'] ?? 0);
        } else {
            $bulk_import_status = isset($_GET['toplist_bulk_import']) ? sanitize_key((string) wp_unslash($_GET['toplist_bulk_import'])) : '';
            $bulk_updated = isset($_GET['toplist_bulk_updated']) ? (int) $_GET['toplist_bulk_updated'] : 0;
            $bulk_created = isset($_GET['toplist_bulk_created']) ? (int) $_GET['toplist_bulk_created'] : 0;
            $bulk_rows = isset($_GET['toplist_bulk_rows']) ? (int) $_GET['toplist_bulk_rows'] : 0;
            $bulk_groups = isset($_GET['toplist_bulk_groups']) ? (int) $_GET['toplist_bulk_groups'] : 0;
            $bulk_upload_error = isset($_GET['toplist_bulk_upload_error']) ? (int) $_GET['toplist_bulk_upload_error'] : 0;
        }
        if ($bulk_import_status !== ''):
            $notice_class = 'notice notice-info';
            $notice_message = '';
            if ($bulk_import_status === 'success') {
                $notice_class = 'notice notice-success';
                $notice_message = sprintf('Bulk CSV import complete. Updated %d toplist(s), created %d toplist(s). Rows read: %d. Groups: %d.', $bulk_updated, $bulk_created, $bulk_rows, $bulk_groups);
            } elseif ($bulk_import_status === 'no_changes') {
                $notice_class = 'notice notice-warning';
                $notice_message = sprintf('Bulk CSV import ran but no toplists were updated or created. Rows read: %d. Groups: %d.', $bulk_rows, $bulk_groups);
            } elseif ($bulk_import_status === 'empty') {
                $notice_class = 'notice notice-warning';
                $notice_message = sprintf('Bulk CSV import failed: file was empty or no valid rows were found. Rows read: %d. Groups: %d. Upload error: %d.', $bulk_rows, $bulk_groups, $bulk_upload_error);
            } elseif ($bulk_import_status === 'bad_header') {
                $notice_class = 'notice notice-error';
                $notice_message = 'Bulk CSV import failed: CSV needs toplist/toplist_id column plus at least one supported field column.';
            } elseif ($bulk_import_status === 'failed') {
                $notice_class = 'notice notice-error';
                $notice_message = 'Bulk CSV import failed: unable to read uploaded file.';
            } elseif ($bulk_import_status === 'upload_too_large') {
                $notice_class = 'notice notice-error';
                $notice_message = 'Bulk CSV import failed: uploaded file is too large for current PHP upload limits.';
            } elseif ($bulk_import_status === 'bad_nonce') {
                $notice_class = 'notice notice-error';
                $notice_message = 'Bulk CSV import failed: security token mismatch. Refresh this page and try again.';
            }
            if ($notice_message !== ''):
                ?>
                <div class="<?php echo esc_attr($notice_class); ?>">
                    <p><?php echo esc_html($notice_message); ?></p>
                </div>
                <?php
            endif;
        endif;
        ?>

        <style>
            .toplist-settings-tabs {
                margin-top: 18px;
            }

            .toplist-settings-panel[hidden] {
                display: none !important;
            }

            .toplist-settings-panel {
                margin-top: 16px;
            }

            .toplist-panel-note {
                background: #f0f6fc;
                border: 1px solid #c5d9ed;
                border-radius: 6px;
                margin: 0 0 14px;
                padding: 10px 12px;
            }

            .toplist-panel-note p {
                margin: 0 0 8px;
            }

            .toplist-panel-note p:last-child {
                margin-bottom: 0;
            }

            .toplist-toggle-groups {
                display: grid;
                gap: 12px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                max-width: 980px;
            }

            .toplist-toggle-card {
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 6px;
                padding: 12px;
            }

            .toplist-toggle-card h3 {
                margin: 0 0 10px;
            }

            .toplist-toggle-card label {
                display: block;
                margin: 0 0 8px;
            }

            @media (max-width: 1024px) {
                .toplist-toggle-groups {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="nav-tab-wrapper toplist-settings-tabs" role="tablist" aria-label="Toplist settings tabs">
            <button type="button" class="nav-tab nav-tab-active" data-tab-target="toplist-tab-defaults" aria-selected="true">
                Defaults
            </button>
            <button type="button" class="nav-tab" data-tab-target="toplist-tab-theme" aria-selected="false">
                Theme
            </button>
            <button type="button" class="nav-tab" data-tab-target="toplist-tab-toggle" aria-selected="false">
                Toggle
            </button>
        </div>

        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('toplist_settings_nonce'); ?>

            <section id="toplist-tab-theme" class="toplist-settings-panel" role="tabpanel" hidden>
                <div class="toplist-panel-note">
                    <p><strong>Global CSS:</strong> Styles defined here will apply to <strong>ALL toplist blocks</strong> across
                        your entire site. This is perfect for maintaining consistent branding.</p>
                    <p><strong>Per-Block CSS:</strong> Individual blocks can still override these styles using their own "Custom
                        CSS" field in the block settings.</p>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="toplist_global_css">Global CSS</label>
                        </th>
                        <td>
                            <textarea name="toplist_global_css" id="toplist_global_css" rows="20" class="large-text code"
                                style="font-family: monospace; font-size: 13px;"><?php echo esc_textarea($global_css); ?></textarea>
                            <p class="description">
                                Add custom CSS to style all toplist blocks. Use <code>.toplist</code> as the parent
                                selector.
                            </p>
                        </td>
                    </tr>
                </table>

                <hr>

                <h2>Quick Theme Examples</h2>
                <p>Click a theme to apply it to the Global CSS field. You can also generate a theme using your own colours.</p>

                <div id="toplist-theme-tools" style="display:grid; gap:16px; max-width: 900px;">

                    <!-- Theme buttons -->
                    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                        <button type="button" class="button toplist-theme-btn"
                            data-css=".toplist .operator-column-ranking-v2{background:linear-gradient(135deg,#10b981 0%,#059669 100%);} .toplist .operator-playnow-column-v2 .button-blue-v2{background:linear-gradient(135deg,#10b981 0%,#059669 100%);} .toplist .operator-item:hover{border-color:#10b981;}">
                            🟢 Apply Green
                        </button>

                        <button type="button" class="button toplist-theme-btn"
                            data-css=".toplist .operator-column-ranking-v2{background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);} .toplist .operator-playnow-column-v2 .button-blue-v2{background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);} .toplist .operator-item:hover{border-color:#fbbf24;}">
                            🟠 Apply Orange/Gold
                        </button>

                        <button type="button" class="button toplist-theme-btn"
                            data-css=".toplist .operator-column-ranking-v2{background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);} .toplist .operator-playnow-column-v2 .button-blue-v2{background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);} .toplist .operator-item:hover{border-color:#f87171;}">
                            🔴 Apply Red/Pink
                        </button>

                        <button type="button" class="button toplist-theme-btn"
                            data-css=".toplist .operator-column-ranking-v2{background:linear-gradient(135deg,#14b8a6 0%,#0d9488 100%);} .toplist .operator-playnow-column-v2 .button-blue-v2{background:linear-gradient(135deg,#14b8a6 0%,#0d9488 100%);} .toplist .operator-item:hover{border-color:#5eead4;}">
                            🌊 Apply Teal/Cyan
                        </button>

                        <button type="button" class="button toplist-theme-btn"
                            data-css=".toplist .operator-item{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);border-color:#334155;color:#e2e8f0;} .toplist .offer-description{color:#f1f5f9;} .toplist .more-info-table{background:#0f172a;} .toplist .attribute-list-item{color:#cbd5e1;}">
                            🌙 Apply Dark Mode
                        </button>

                        <button type="button" class="button" id="toplist-clear-css">
                            Clear
                        </button>
                    </div>

                    <!-- Colour picker generator -->
                    <div style="border:1px solid #ddd; border-radius:8px; padding:16px;">
                        <h3 style="margin-top:0;">Build your own colours</h3>

                        <div style="display:grid; grid-template-columns: repeat(5, minmax(140px, 1fr)); gap:12px; align-items:end;">
                            <label style="display:grid; gap:6px;">
                                <span>Primary</span>
                                <input type="color" id="toplist-color-primary" value="#10b981">
                            </label>

                            <label style="display:grid; gap:6px;">
                                <span>Secondary</span>
                                <input type="color" id="toplist-color-secondary" value="#059669">
                            </label>

                            <label style="display:grid; gap:6px;">
                                <span>Hover Border</span>
                                <input type="color" id="toplist-color-hover" value="#10b981">
                            </label>

                            <label style="display:grid; gap:6px;">
                                <span>Card BG</span>
                                <input type="color" id="toplist-color-cardbg" value="#ffffff">
                            </label>

                            <label style="display:grid; gap:6px;">
                                <span>Text</span>
                                <input type="color" id="toplist-color-text" value="#0f172a">
                            </label>
                        </div>

                        <div style="display:flex; gap:10px; margin-top:12px; align-items:center;">
                            <button type="button" class="button button-primary" id="toplist-apply-custom">
                                Apply Custom Colours
                            </button>

                            <label style="display:flex; gap:8px; align-items:center;">
                                <input type="checkbox" id="toplist-append-mode" checked>
                                Append instead of replace
                            </label>

                            <span id="toplist-theme-msg" style="opacity:.8;"></span>
                        </div>

                        <p class="description" style="margin-bottom:0;">
                            This will generate a simple gradient badge + gradient button + hover border, and optionally set card
                            background/text.
                        </p>
                    </div>
                </div>

                <hr>

                <h2>CSS Class Reference</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>CSS Class</th>
                            <th>Element</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>.toplist .operator-item</code></td>
                            <td>Card container</td>
                        </tr>
                        <tr>
                            <td><code>.toplist .operator-column-ranking-v2</code></td>
                            <td>Ranking number badge</td>
                        </tr>
                        <tr>
                            <td><code>.toplist .operator-playnow-column-v2 .button-blue-v2</code></td>
                            <td>CTA button</td>
                        </tr>
                        <tr>
                            <td><code>.toplist .offer-description</code></td>
                            <td>Bonus offer text</td>
                        </tr>
                        <tr>
                            <td><code>.toplist .more-info-table</code></td>
                            <td>Details section</td>
                        </tr>
                        <tr>
                            <td><code>.toplist .attribute-list-item</code></td>
                            <td>Bullet points</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section id="toplist-tab-defaults" class="toplist-settings-panel" role="tabpanel">
                <table class="form-table">
                    <tr>
                        <th scope="row">Bulk CSV Export</th>
                        <td>
                            <a href="<?php echo esc_url($bulk_export_url); ?>" class="button button-secondary">Export All Toplists as CSV</a>
                            <a href="<?php echo esc_url($bulk_template_url); ?>" class="button">Download Bulk Import Template</a>
                            <p class="description">Downloads one CSV with each row tagged by toplist name and toplist ID.</p>
                            <hr>
                            <label for="toplist_bulk_csv_file"><strong>Import all toplists from CSV</strong></label><br>
                            <?php wp_nonce_field('toplist_import_all_csv', 'toplist_import_all_csv_nonce'); ?>
                            <input type="file" id="toplist_bulk_csv_file" name="toplist_bulk_csv_file" accept=".csv,text/csv">
                            <p style="margin-top:10px;">
                                <button type="submit" id="toplist-bulk-import-btn" class="button button-primary" name="toplist_bulk_import_submit" value="1">
                                    Import Bulk CSV
                                </button>
                            </p>
                            <p class="description">CSV should include <code>toplist</code> or <code>toplist_id</code> plus field columns (for example: <code>operator</code>, <code>offer</code>, <code>href</code>).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="toplist_global_heading_text">Global Toplist H2 Heading</label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="toplist_global_heading_text"
                                name="toplist_global_heading_text"
                                value="<?php echo esc_attr($global_heading_text); ?>"
                                placeholder="Top 10 Casinos">
                            <p class="description">Shown as an &lt;h2&gt; above the toplist when block heading mode is set to global.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Header Row</th>
                        <td>
                            <label style="display:block; margin-bottom:8px;">
                                <input type="checkbox" name="toplist_global_enable_default_header" value="1" <?php checked($global_enable_default_header, '1'); ?>>
                                Enable global default toplist header
                            </label>
                            <input type="text" class="large-text code" id="toplist_global_default_header_row"
                                name="toplist_global_default_header_row"
                                value="<?php echo esc_attr($global_default_header_row); ?>"
                                placeholder="operator|product|offer|href|logo|year|ctaText|terms|bullets">
                            <p class="description">Applied when a toplist line set has no explicit header row. Per-block settings can override this.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="toplist_global_default_cta_text">Global Default CTA Text</label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="toplist_global_default_cta_text"
                                name="toplist_global_default_cta_text"
                                value="<?php echo esc_attr($global_default_cta_text); ?>">
                            <p class="description">Fallback CTA label used when row ctaText is empty.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="toplist_global_default_read_review_text">Global Default Read Review Text</label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="toplist_global_default_read_review_text"
                                name="toplist_global_default_read_review_text"
                                value="<?php echo esc_attr($global_default_read_review_text); ?>">
                            <p class="description">Fallback label used when row readReviewText is empty.</p>
                        </td>
                    </tr>
                </table>
            </section>

            <section id="toplist-tab-toggle" class="toplist-settings-panel" role="tabpanel" hidden>
                <table class="form-table">
                    <tr>
                        <th scope="row">Global Visibility Toggles</th>
                        <td>
                            <div style="display:flex; gap:8px; margin-bottom:10px;">
                                <button type="button" class="button" id="toplist-toggle-enable-all">Enable all</button>
                                <button type="button" class="button" id="toplist-toggle-disable-all">Disable all</button>
                            </div>
                            <div class="toplist-toggle-groups">
                                <?php foreach ($toggle_groups as $group_name => $group_fields): ?>
                                    <fieldset class="toplist-toggle-card">
                                        <h3><?php echo esc_html($group_name); ?></h3>
                                        <?php foreach ($group_fields as $option_name => $label): ?>
                                            <label>
                                                <input type="checkbox" class="toplist-toggle-checkbox" name="<?php echo esc_attr($option_name); ?>" value="1"
                                                    <?php checked(get_option($option_name, '1'), '1'); ?>>
                                                <?php echo esc_html($label); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </fieldset>
                                <?php endforeach; ?>
                            </div>
                            <p class="description" style="margin-top:8px;">
                                These are global switches. If disabled here, a field stays hidden site-wide even if enabled per block.
                            </p>
                        </td>
                    </tr>
                </table>
            </section>

            <p class="submit">
                <input type="submit" name="toplist_save_settings" class="button button-primary" value="Save Settings">
            </p>
        </form>

        <script>
            (function () {
                const tabButtons = document.querySelectorAll('[data-tab-target]');
                const tabPanels = document.querySelectorAll('.toplist-settings-panel');
                const textarea = document.getElementById('toplist_global_css');
                const msg = document.getElementById('toplist-theme-msg');
                const appendBox = document.getElementById('toplist-append-mode');

                function setActiveTab(targetId) {
                    tabButtons.forEach((button) => {
                        const isActive = button.getAttribute('data-tab-target') === targetId;
                        button.classList.toggle('nav-tab-active', isActive);
                        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    tabPanels.forEach((panel) => {
                        panel.hidden = panel.id !== targetId;
                    });
                }

                tabButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const targetId = button.getAttribute('data-tab-target');
                        setActiveTab(targetId);
                    });
                });

                if (tabButtons.length) {
                    setActiveTab(tabButtons[0].getAttribute('data-tab-target'));
                }

                const toggleCheckboxes = document.querySelectorAll('.toplist-toggle-checkbox');
                const enableAllBtn = document.getElementById('toplist-toggle-enable-all');
                const disableAllBtn = document.getElementById('toplist-toggle-disable-all');

                if (enableAllBtn) {
                    enableAllBtn.addEventListener('click', () => {
                        toggleCheckboxes.forEach((cb) => cb.checked = true);
                    });
                }

                if (disableAllBtn) {
                    disableAllBtn.addEventListener('click', () => {
                        toggleCheckboxes.forEach((cb) => cb.checked = false);
                    });
                }

                if (!textarea) {
                    return;
                }

                function setMessage(text) {
                    if (!msg) return;
                    msg.textContent = text;
                    window.clearTimeout(setMessage._t);
                    setMessage._t = window.setTimeout(() => msg.textContent = '', 2500);
                }

                function applyCss(css, { append = false } = {}) {
                    const trimmed = css.trim();
                    if (!trimmed) return;

                    if (append && textarea.value.trim()) {
                        textarea.value = textarea.value.replace(/\s+$/, '') + "\n\n" + trimmed + "\n";
                    } else {
                        textarea.value = trimmed + "\n";
                    }
                    textarea.focus();
                    setMessage(append ? 'Appended CSS ✅' : 'Applied CSS ✅');
                }

                // Click-to-apply themes
                document.querySelectorAll('.toplist-theme-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const css = btn.getAttribute('data-css') || '';
                        applyCss(css, { append: false });
                    });
                });

                // Clear button
                const clearBtn = document.getElementById('toplist-clear-css');
                if (clearBtn) {
                    clearBtn.addEventListener('click', () => {
                        textarea.value = '';
                        setMessage('Cleared ✅');
                        textarea.focus();
                    });
                }

                // Custom colour generator
                function val(id) {
                    const el = document.getElementById(id);
                    return el ? el.value : '';
                }

                const applyCustomBtn = document.getElementById('toplist-apply-custom');
                if (applyCustomBtn) {
                    applyCustomBtn.addEventListener('click', () => {
                        const primary = val('toplist-color-primary');
                        const secondary = val('toplist-color-secondary');
                        const hover = val('toplist-color-hover');
                        const cardbg = val('toplist-color-cardbg');
                        const text = val('toplist-color-text');

                        // Keep it simple + safe: generate only the selectors you already documented.
                        const css =
                            `.toplist .operator-column-ranking-v2{
        background: linear-gradient(135deg, ${primary} 0%, ${secondary} 100%);
        }

        .toplist .operator-playnow-column-v2 .button-blue-v2{
        background: linear-gradient(135deg, ${primary} 0%, ${secondary} 100%);
        }

        .toplist .operator-item:hover{
        border-color: ${hover};
        }

        .toplist .operator-item{
        background: ${cardbg};
        color: ${text};
        }`;

                        applyCss(css, { append: !!appendBox && appendBox.checked });
                    });
                }
            })();
        </script>

    </div>
    <?php
}

// Output global CSS in frontend and editor
// Enqueue global CSS using wp_add_inline_style
add_action('enqueue_block_assets', function () {
    $global_css = get_option('toplist_global_css', '');
    if ($global_css === '')
        return;

    wp_register_style('toplist-global-css', false);
    wp_enqueue_style('toplist-global-css');
    wp_add_inline_style('toplist-global-css', $global_css);
});
