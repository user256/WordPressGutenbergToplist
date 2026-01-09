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

    // Save settings
    if (isset($_POST['toplist_save_settings'])) {
        check_admin_referer('toplist_settings_nonce');
        update_option('toplist_global_css', $_POST['toplist_global_css']);
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    $global_css = get_option('toplist_global_css', '');
    ?>
    <div class="wrap">
        <h1>Toplist Block - Global CSS Settings</h1>

        <div class="notice notice-info">
            <p><strong>Global CSS:</strong> Styles defined here will apply to <strong>ALL toplist blocks</strong> across
                your entire site. This is perfect for maintaining consistent branding.</p>
            <p><strong>Per-Block CSS:</strong> Individual blocks can still override these styles using their own "Custom
                CSS" field in the block settings.</p>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('toplist_settings_nonce'); ?>

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

            <p class="submit">
                <input type="submit" name="toplist_save_settings" class="button button-primary" value="Save Settings">
            </p>
        </form>

        <hr>

        <h2>Quick Theme Examples</h2>
        <p>Copy and paste one of these themes into the Global CSS field above:</p>

        <details style="margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
            <summary style="cursor: pointer; font-weight: 600; font-size: 15px;">🟢 Green Theme</summary>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; margin-top: 10px;"><code>.toplist .operator-column-ranking-v2 {
                                                                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                                }

                                                                .toplist .operator-playnow-column-v2 .button-blue-v2 {
                                                                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                                }

                                                                .toplist .operator-item:hover {
                                                                    border - color: #10b981;
                                }</code></pre>
        </details>

        <details style="margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
            <summary style="cursor: pointer; font-weight: 600; font-size: 15px;">🟠 Orange/Gold Theme</summary>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; margin-top: 10px;"><code>.toplist .operator-column-ranking-v2 {
                                                                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                                }

                                                                .toplist .operator-playnow-column-v2 .button-blue-v2 {
                                                                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                                }

                                                                .toplist .operator-item:hover {
                                                                    border - color: #fbbf24;
                                }</code></pre>
        </details>

        <details style="margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
            <summary style="cursor: pointer; font-weight: 600; font-size: 15px;">🔴 Red/Pink Theme</summary>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; margin-top: 10px;"><code>.toplist .operator-column-ranking-v2 {
                                                                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                                }

                                                                .toplist .operator-playnow-column-v2 .button-blue-v2 {
                                                                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                                }

                                                                .toplist .operator-item:hover {
                                                                    border - color: #f87171;
                                }</code></pre>
        </details>

        <details style="margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
            <summary style="cursor: pointer; font-weight: 600; font-size: 15px;">🌊 Teal/Cyan Theme</summary>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; margin-top: 10px;"><code>.toplist .operator-column-ranking-v2 {
                                                                background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
                                }

                                                                .toplist .operator-playnow-column-v2 .button-blue-v2 {
                                                                    background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
                                }

                                                                .toplist .operator-item:hover {
                                                                    border - color: #5eead4;
                                }</code></pre>
        </details>

        <details style="margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
            <summary style="cursor: pointer; font-weight: 600; font-size: 15px;">🌙 Dark Mode</summary>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; margin-top: 10px;"><code>.toplist .operator-item {
                                                                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                                                                border-color: #334155;
                                                                color: #e2e8f0;
                                }

                                                                .toplist .offer-description {
                                                                    color: #f1f5f9;
                                }

                                                                .toplist .more-info-table {
                                                                    background: #0f172a;
                                }

                                                                .toplist .attribute-list-item {
                                                                    color: #cbd5e1;
                                }</code></pre>
        </details>

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

    </div>
    <?php
}

// Output global CSS in frontend and editor
// Enqueue global CSS using wp_add_inline_style
add_action('enqueue_block_assets', function () {
    $global_css = get_option('toplist_playground_global_css', '');
    if ($global_css === '')
        return;

    wp_register_style('toplist-global-css', false);
    wp_enqueue_style('toplist-global-css');
    wp_add_inline_style('toplist-global-css', $global_css);
});
