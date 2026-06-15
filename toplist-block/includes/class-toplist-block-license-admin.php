<?php
/**
 * License settings UI and admin-post handlers.
 *
 * Premium-only: excluded from the lite build.
 *
 * @package Toplist_Block
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin license UI for Toplist Block settings.
 */
class Toplist_Block_License_Admin {

	/**
	 * @return void
	 */
	public static function init(): void {
		add_action('admin_post_toplist_license_save', array(__CLASS__, 'handle_save'));
		add_action('admin_post_toplist_license_recheck', array(__CLASS__, 'handle_recheck'));
		add_action('admin_notices', array(__CLASS__, 'render_global_notice'));
		add_action('wp_ajax_toplist_license_dismiss_notice', array(__CLASS__, 'dismiss_notice'));
	}

	/**
	 * @return string
	 */
	public static function settings_url(): string {
		return admin_url('options-general.php?page=toplist-settings');
	}

	/**
	 * @return void
	 */
	public static function handle_save(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to do that.', 'toplist'));
		}
		check_admin_referer('toplist_license_save');

		if (!empty($_POST['toplist_license_remove'])) {
			Toplist_Block_License::clear();
			wp_safe_redirect(add_query_arg('toplist_license_status', 'cleared', self::settings_url()));
			exit;
		}

		$key = isset($_POST['toplist_license_key']) ? sanitize_text_field((string) wp_unslash($_POST['toplist_license_key'])) : '';

		if ($key === '') {
			$existing = Toplist_Block_License::get_key();
			if ($existing !== '') {
				$result = Toplist_Block_License::validate();
				Toplist_Block_License::schedule_from_cache();
				self::redirect_with_result($result);
			}
			Toplist_Block_License::clear();
			wp_safe_redirect(add_query_arg('toplist_license_status', 'cleared', self::settings_url()));
			exit;
		}

		$result = Toplist_Block_License::activate($key);
		self::redirect_with_result($result);
	}

	/**
	 * @return void
	 */
	public static function handle_recheck(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to do that.', 'toplist'));
		}
		check_admin_referer('toplist_license_recheck');

		$result = Toplist_Block_License::validate();
		Toplist_Block_License::schedule_from_cache();
		self::redirect_with_result($result);
	}

	/**
	 * @param array<string, mixed> $result Validation result.
	 * @return void
	 */
	private static function redirect_with_result(array $result): void {
		$status = (string) ($result['status'] ?? 'invalid');
		if (in_array($status, array('active', 'grace'), true)) {
			$status = 'active';
		}
		$args = array('toplist_license_status' => $status);
		if ($status !== 'active' && !empty($result['message'])) {
			$args['toplist_license_msg'] = rawurlencode((string) $result['message']);
		}
		wp_safe_redirect(add_query_arg($args, self::settings_url()));
		exit;
	}

	/**
	 * Dismissible notice when license is missing/invalid (non-blocking for local blocks).
	 *
	 * @return void
	 */
	public static function render_global_notice(): void {
		if (!current_user_can('manage_options')) {
			return;
		}
		if (Toplist_Block_License::is_valid()) {
			return;
		}
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if ($screen && $screen->id === 'settings_page_toplist-settings') {
			return;
		}
		if (get_user_meta(get_current_user_id(), 'toplist_license_notice_dismissed', true)) {
			return;
		}
		$url = self::settings_url();
		echo '<div class="notice notice-warning is-dismissible" data-toplist-license-notice="1"><p>';
		echo esc_html__('Toplist Block Pro features are inactive until you enter a valid license key.', 'toplist');
		echo ' <a href="' . esc_url($url) . '">' . esc_html__('Enter license', 'toplist') . '</a>';
		echo '</p></div>';
		echo '<script>(function(){var n=document.querySelector("[data-toplist-license-notice]");if(!n||!window.jQuery)return;jQuery(n).on("click",".notice-dismiss",function(){jQuery.post(ajaxurl,{action:"toplist_license_dismiss_notice",_ajax_nonce:"' . esc_js(wp_create_nonce('toplist_license_dismiss')) . '"});});})();</script>';
	}

	/**
	 * @return void
	 */
	public static function dismiss_notice(): void {
		check_ajax_referer('toplist_license_dismiss');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(null, 403);
		}
		update_user_meta(get_current_user_id(), 'toplist_license_notice_dismissed', '1');
		wp_send_json_success();
	}

	/**
	 * Render license panel on settings screen.
	 *
	 * @return void
	 */
	public static function render_settings_panel(): void {
		if (!class_exists('Toplist_Block_License')) {
			return;
		}

		if (isset($_GET['toplist_license_status'])) {
			$lstat = sanitize_key((string) wp_unslash($_GET['toplist_license_status']));
			$lmsg = isset($_GET['toplist_license_msg'])
				? sanitize_text_field((string) urldecode(wp_unslash($_GET['toplist_license_msg'])))
				: '';
			if ($lstat === 'active') {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('License verified. Pro features are active.', 'toplist') . '</p></div>';
			} elseif ($lstat === 'cleared') {
				echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('License key removed.', 'toplist') . '</p></div>';
			} elseif (in_array($lstat, array('invalid', 'expired', 'unreachable'), true)) {
				$class = $lstat === 'unreachable' ? 'notice-warning' : 'notice-error';
				$text = $lmsg !== '' ? $lmsg : __('License could not be verified for this site.', 'toplist');
				echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($text) . '</p></div>';
			}
		}

		$cache = Toplist_Block_License::get_cache();
		$status = (string) ($cache['status'] ?? '');
		$valid = Toplist_Block_License::is_valid();
		$next = wp_next_scheduled(Toplist_Block_License::CRON_HOOK);
		?>
		<div class="toplist-license-panel" style="background:#f6f7f7;border:1px solid #c3c4c7;padding:14px 16px;margin:0 0 20px;border-radius:4px;">
			<h2 style="margin-top:0;"><?php esc_html_e('Toplist Block Pro License', 'toplist'); ?></h2>
			<?php if (Toplist_Block_License::api_url() === '') : ?>
				<div class="notice notice-error inline" style="margin:0 0 12px;padding:8px 12px;">
					<p style="margin:0;">
						<?php esc_html_e('License API URL is not configured. Add TOPLIST_BLOCK_LICENSE_API_URL (and usually TOPLIST_BLOCK_LICENSE_API_KEY) to wp-config.php before keys can be verified.', 'toplist'); ?>
					</p>
				</div>
			<?php endif; ?>
			<p style="margin-top:0;">
				<?php if ($valid) : ?>
					<strong style="color:#1d7e2c;">&#9679; <?php esc_html_e('Pro active', 'toplist'); ?></strong>
				<?php elseif ($status === 'unreachable' && !empty($cache['last_valid_at'])) : ?>
					<strong style="color:#b45309;">&#9679; <?php esc_html_e('Pro (grace — API unreachable)', 'toplist'); ?></strong>
				<?php else : ?>
					<strong style="color:#b32d2e;">&#9675; <?php esc_html_e('Pro inactive', 'toplist'); ?></strong>
				<?php endif; ?>
				<?php if (!empty($cache['key_last4'])) : ?>
					<span>…<?php echo esc_html((string) $cache['key_last4']); ?></span>
				<?php endif; ?>
				<?php if (!empty($cache['expires_at'])) : ?>
					<span><?php printf(esc_html__('Expires %s', 'toplist'), esc_html(gmdate('Y-m-d', (int) strtotime((string) $cache['expires_at'])))); ?></span>
				<?php elseif (!empty($cache['billing_period'])) : ?>
					<span><?php echo esc_html(ucfirst((string) $cache['billing_period'])); ?></span>
				<?php endif; ?>
			</p>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:8px;">
				<?php wp_nonce_field('toplist_license_save'); ?>
				<input type="hidden" name="action" value="toplist_license_save">
				<input type="text" name="toplist_license_key" value="" class="regular-text" placeholder="<?php esc_attr_e('xxxx-xxxx-xxxx-xxxx', 'toplist'); ?>" autocomplete="off" style="min-width:260px;">
				<button type="submit" class="button button-primary"><?php esc_html_e('Save & verify', 'toplist'); ?></button>
				<?php if (Toplist_Block_License::get_key() !== '') : ?>
					<button type="submit" name="toplist_license_remove" value="1" class="button"><?php esc_html_e('Remove key', 'toplist'); ?></button>
				<?php endif; ?>
			</form>
			<?php if (Toplist_Block_License::get_key() !== '') : ?>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
					<?php wp_nonce_field('toplist_license_recheck'); ?>
					<input type="hidden" name="action" value="toplist_license_recheck">
					<button type="submit" class="button button-secondary"><?php esc_html_e('Check now', 'toplist'); ?></button>
				</form>
			<?php endif; ?>
			<?php if ($next) : ?>
				<p class="description" style="margin:8px 0 0;">
					<?php printf(esc_html__('Next scheduled recheck: %s UTC', 'toplist'), esc_html(gmdate('Y-m-d H:i', $next))); ?>
				</p>
			<?php endif; ?>
			<p class="description" style="margin-bottom:0;">
				<?php esc_html_e('Local toplist blocks keep working without a license. Library, import, and linked lists require Pro.', 'toplist'); ?>
			</p>
		</div>
		<?php
	}
}
