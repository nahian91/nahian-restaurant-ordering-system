<?php
/**
 * Nahian POS Tablet Authentication & Customer UI Shortcode
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper: Get Authenticated Table Row from Secure Cookie
 *
 * @return object|null Table row object or null.
 */
function nrtok_get_authenticated_table() {
	global $wpdb;
	$t_tables = $wpdb->prefix . 'nrtok_tables';

	if ( empty( $_COOKIE['nrtok_table_auth'] ) ) {
		return null;
	}

	$raw   = sanitize_text_field( wp_unslash( $_COOKIE['nrtok_table_auth'] ) );
	$parts = explode( '|', $raw );
	if ( count( $parts ) !== 2 ) {
		return null;
	}

	$table_id = absint( $parts[0] );
	$token    = sanitize_text_field( $parts[1] );

	if ( $table_id <= 0 || empty( $token ) ) {
		return null;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_tables ) ) !== $t_tables ) {
		return null;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$tbl = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_tables, $table_id ) );
	if ( ! $tbl || empty( $tbl->table_login ) ) {
		return null;
	}

	$expected_token = hash_hmac( 'sha256', $tbl->id . '|' . $tbl->table_login . '|' . $tbl->table_password, wp_salt( 'auth' ) );
	if ( ! hash_equals( $expected_token, $token ) ) {
		return null;
	}

	return $tbl;
}

add_action( 'template_redirect', 'nrtok_fullscreen_tablet_mode' );
/**
 * Handle Table Login & Full-Screen Tablet Mode
 */
function nrtok_fullscreen_tablet_mode() {
	global $post, $wpdb;
	if ( ! is_a( $post, 'WP_Post' ) || ( ! has_shortcode( $post->post_content, 'nrtok_restaurant_menu' ) && ! has_shortcode( $post->post_content, 'nrtok_menu' ) && ! has_shortcode( $post->post_content, 'restaurant_menu' ) ) ) {
		return;
	}

	$t_tables = $wpdb->prefix . 'nrtok_tables';
	$page_url = get_permalink( $post->ID );
	$auth_err = '';

	// Handle Table Login Submission.
	if ( isset( $_POST['nrtok_table_login_submit'] ) ) {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'nrtok_table_login_action' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'nahian-restaurant-ordering-system' ) );
		}

		$login_input = sanitize_text_field( wp_unslash( $_POST['nrtok_login'] ?? '' ) );
		$pass_input  = sanitize_text_field( wp_unslash( $_POST['nrtok_pass'] ?? '' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_tables ) ) === $t_tables ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$matched_tbl = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE table_login = %s LIMIT 1',
					$t_tables,
					$login_input
				)
			);

			if ( $matched_tbl && ! empty( $matched_tbl->table_password ) && hash_equals( (string) $matched_tbl->table_password, $pass_input ) ) {
				$token      = hash_hmac( 'sha256', $matched_tbl->id . '|' . $matched_tbl->table_login . '|' . $matched_tbl->table_password, wp_salt( 'auth' ) );
				$cookie_val = $matched_tbl->id . '|' . $token;
				setcookie( 'nrtok_table_auth', $cookie_val, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
				$_COOKIE['nrtok_table_auth'] = $cookie_val;

				wp_safe_redirect( $page_url );
				exit;
			}
		}
		$auth_err = __( 'Incorrect table username or password. Please check and try again.', 'nahian-restaurant-ordering-system' );
	}

	$s               = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
	$restaurant_name = $s['restaurant_name'] ?? 'Nahian Restaurant POS';
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="mobile-web-app-capable" content="yes">
		<title><?php echo esc_html( $restaurant_name ); ?> — <?php esc_html_e( 'Table Menu', 'nahian-restaurant-ordering-system' ); ?></title>
		<?php
		wp_enqueue_script( 'jquery' );
		wp_head();
		?>
		<style>
			html, body {
				margin: 0 !important; padding: 0 !important;
				background: #f1f5f9 !important;
				overflow-x: clip !important;
				-webkit-tap-highlight-color: transparent;
				touch-action: manipulation;
				scroll-behavior: smooth;
			}
			#wpadminbar { display: none !important; }
		</style>
	</head>
	<body>
		<?php
		$GLOBALS['nrtok_login_error'] = $auth_err;
		echo do_shortcode( '[nrtok_restaurant_menu]' );
		wp_footer();
		?>
	</body>
	</html>
	<?php
	exit;
}

add_shortcode( 'nrtok_restaurant_menu', 'nrtok_customer_tablet_ui' );
add_shortcode( 'nrtok_menu', 'nrtok_customer_tablet_ui' );
add_shortcode( 'restaurant_menu', 'nrtok_customer_tablet_ui' );

/**
 * Customer Tablet Shortcode: [nrtok_restaurant_menu]
 *
 * @return string Rendered HTML for tablet login or menu UI.
 */
function nrtok_customer_tablet_ui() {
	global $wpdb;
	$s               = function_exists( 'nrtok_get_settings' ) ? nrtok_get_settings() : array();
	$cur             = esc_html( html_entity_decode( $s['currency'] ?? '$', ENT_QUOTES, 'UTF-8' ) );
	$tax_rate        = (float) ( $s['tax_percent'] ?? 0 );
	$auth_tbl        = nrtok_get_authenticated_table();
	$login_err       = $GLOBALS['nrtok_login_error'] ?? '';
	$restaurant_name = ! empty( $s['restaurant_name'] ) ? $s['restaurant_name'] : 'Nahian Restaurant POS';
	$restaurant_logo = ! empty( $s['restaurant_logo'] ) ? $s['restaurant_logo'] : '';

	// Reusable SVG Icons.
	$nrtok_svg_lock     = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
	$nrtok_svg_user     = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
	$nrtok_svg_key      = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>';
	$nrtok_svg_eye      = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
	$nrtok_svg_bell     = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>';
	$nrtok_svg_receipt  = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>';
	$nrtok_svg_table    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>';
	$nrtok_svg_search   = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
	$nrtok_svg_close    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
	$nrtok_svg_utensils = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>';
	$nrtok_svg_bag      = '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>';
	$nrtok_svg_sparkles = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>';
	$nrtok_svg_check    = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
	$nrtok_svg_send     = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>';
	$nrtok_svg_plus     = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
	$nrtok_svg_radio    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/></svg>';
	$nrtok_svg_sidebar  = '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/></svg>';
	$nrtok_svg_grid     = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>';
	$nrtok_svg_list     = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>';

	// =========================================================================
	// STEP 1: TABLE LOGIN GATE
	// =========================================================================
	if ( ! $auth_tbl ) {
		$prefill_login = '';
		if ( ! empty( $_POST['nrtok_login'] ) ) {
			$prefill_login = sanitize_text_field( wp_unslash( $_POST['nrtok_login'] ) );
		} elseif ( ! empty( $_GET['table'] ) ) {
			$t_tables_tbl = $wpdb->prefix . 'nrtok_tables';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_tables_tbl ) ) === $t_tables_tbl ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$q_tbl = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT table_login FROM %i WHERE table_name = %s LIMIT 1',
						$t_tables_tbl,
						sanitize_text_field( wp_unslash( $_GET['table'] ) )
					)
				);
				if ( $q_tbl && ! empty( $q_tbl->table_login ) ) {
					$prefill_login = $q_tbl->table_login;
				}
			}
		}

		ob_start();
		?>
		<style id="nrtok-login-styles">
			.nrt-login-screen {
				min-height: 100vh; width: 100vw; display: flex; align-items: center; justify-content: center;
				background: #f1f5f9; padding: 24px; box-sizing: border-box;
				font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			}
			.nrt-login-card {
				background: #ffffff; width: 100%; max-width: 440px; border-radius: 28px;
				padding: 42px 38px; box-shadow: 0 20px 50px -12px rgba(15, 23, 42, 0.12);
				box-sizing: border-box; border: 1.5px solid #e2e8f0;
			}
			.nrt-login-logo {
				width: 70px; height: 70px; border-radius: 20px; margin: 0 auto 20px;
				background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
				color: #fff; font-size: 32px; font-weight: 900;
				display: flex; align-items: center; justify-content: center;
				box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35); overflow: hidden;
			}
			.nrt-login-logo.has-img {
				background: #ffffff; border: 1.5px solid #e2e8f0; padding: 6px; box-sizing: border-box;
			}
			.nrt-login-logo img {
				width: 100%; height: 100%; object-fit: contain; border-radius: 14px;
			}
			.nrt-login-title { margin: 0; text-align: center; font-size: 26px; font-weight: 900; color: #0f172a; }
			.nrt-login-sub { margin: 8px 0 28px; text-align: center; font-size: 14px; color: #64748b; font-weight: 500; line-height: 1.5; }
			.nrt-login-err { background: #fef2f2; border: 1.5px solid #fecaca; color: #dc2626; padding: 13px 18px; border-radius: 14px; font-size: 13.5px; font-weight: 700; margin-bottom: 20px; text-align: center; }
			.nrt-l-group { margin-bottom: 20px; }
			.nrt-l-label { display: block; font-size: 13px; font-weight: 800; color: #334155; margin-bottom: 8px; }
			.nrt-l-wrap { position: relative; display: flex; align-items: center; }
			.nrt-l-ico { position: absolute; left: 16px; display: flex; align-items: center; pointer-events: none; }
			.nrt-l-input {
				width: 100%; height: 54px; padding: 14px 48px 14px 46px; border: 1.5px solid #cbd5e1;
				border-radius: 16px; font-size: 16px; font-weight: 600; color: #0f172a; background: #f8fafc; box-sizing: border-box;
			}
			.nrt-l-input:focus { outline: none; background: #fff; border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.15); }
			.nrt-l-eye-btn { position: absolute; right: 12px; background: none; border: none; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; cursor: pointer; border-radius: 10px; }
			.nrt-l-submit {
				width: 100%; height: 56px; margin-top: 12px; border: none; border-radius: 16px;
				background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #fff; font-size: 16px; font-weight: 900; cursor: pointer;
				display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
			}
		</style>

		<div class="nrt-login-screen">
			<div class="nrt-login-card">
				<?php if ( ! empty( $restaurant_logo ) ) : ?>
					<div class="nrt-login-logo has-img">
						<img src="<?php echo esc_url( $restaurant_logo ); ?>" alt="<?php echo esc_attr( $restaurant_name ); ?>">
					</div>
				<?php else : ?>
					<div class="nrt-login-logo"><?php echo esc_html( mb_substr( $restaurant_name, 0, 1 ) ); ?></div>
				<?php endif; ?>
				<h1 class="nrt-login-title"><?php echo esc_html( $restaurant_name ); ?></h1>
				<p class="nrt-login-sub"><?php esc_html_e( 'Table Setup', 'nahian-restaurant-ordering-system' ); ?><br><?php esc_html_e( 'Sign in once with your table username and password.', 'nahian-restaurant-ordering-system' ); ?></p>

				<?php if ( ! empty( $login_err ) ) : ?>
					<div class="nrt-login-err"><?php echo esc_html( $login_err ); ?></div>
				<?php endif; ?>

				<form method="post">
					<?php wp_nonce_field( 'nrtok_table_login_action' ); ?>
					<div class="nrt-l-group">
						<label class="nrt-l-label"><?php esc_html_e( 'Table Username', 'nahian-restaurant-ordering-system' ); ?></label>
						<div class="nrt-l-wrap">
							<span class="nrt-l-ico"><?php echo $nrtok_svg_user; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<input type="text" name="nrtok_login" class="nrt-l-input" placeholder="e.g. table_01" value="<?php echo esc_attr( $prefill_login ); ?>" required autocomplete="username">
						</div>
					</div>

					<div class="nrt-l-group">
						<label class="nrt-l-label"><?php esc_html_e( 'Password', 'nahian-restaurant-ordering-system' ); ?></label>
						<div class="nrt-l-wrap">
							<span class="nrt-l-ico"><?php echo $nrtok_svg_key; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<input type="password" name="nrtok_pass" id="nrt-login-pass-inp" class="nrt-l-input" placeholder="<?php esc_attr_e( 'Enter password', 'nahian-restaurant-ordering-system' ); ?>" required autocomplete="current-password">
							<button type="button" id="nrt-toggle-pass" class="nrt-l-eye-btn" title="<?php esc_attr_e( 'Show or Hide Password', 'nahian-restaurant-ordering-system' ); ?>"><?php echo $nrtok_svg_eye; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						</div>
					</div>

					<button type="submit" name="nrtok_table_login_submit" class="nrt-l-submit">
						<?php echo $nrtok_svg_lock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Open Table Menu', 'nahian-restaurant-ordering-system' ); ?></span>
					</button>
				</form>
			</div>
		</div>
		<script id="nrtok-login-scripts">
			document.addEventListener('DOMContentLoaded', function() {
				const toggleBtn = document.getElementById('nrt-toggle-pass');
				if (toggleBtn) {
					toggleBtn.addEventListener('click', function() {
						const inp = document.getElementById('nrt-login-pass-inp');
						if (inp) {
							inp.type = (inp.type === 'password') ? 'text' : 'password';
						}
					});
				}
			});
		</script>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// STEP 2: AUTHENTICATED TABLET MENU UI
	// =========================================================================
	$table_no    = $auth_tbl->table_name;
	$table_seats = absint( $auth_tbl->seats );

	$t_menu_tbl   = $wpdb->prefix . 'nrtok_menu';
	$t_extras_tbl = $wpdb->prefix . 'nrtok_extras';

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$items      = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_menu_tbl ) ) === $t_menu_tbl ) ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY category ASC, id DESC', $t_menu_tbl ) ) ?: array() ) : array();
	$all_extras = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_extras_tbl ) ) === $t_extras_tbl ) ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY name ASC', $t_extras_tbl ), OBJECT_K ) ?: array() ) : array();
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$cat_counts = array();
	foreach ( $items as $it ) {
		$cat_counts[ $it->category ] = ( $cat_counts[ $it->category ] ?? 0 ) + 1;
	}

	$js_all_extras = array();
	foreach ( $all_extras as $ex ) {
		$js_all_extras[] = array(
			'id'    => absint( $ex->id ),
			'name'  => $ex->name,
			'price' => (float) $ex->price,
		);
	}

	ob_start();
	?>
	<style id="nrtok-tablet-styles">
		.nrt-wrapper {
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			width: 100%; max-width: 1680px; margin: 0 auto;
			padding: 18px 22px 96px; color: #0f172a; box-sizing: border-box;
			touch-action: manipulation;
		}

		/* Toast Notification */
		.nrt-toast-container {
			position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 999999;
			display: flex; flex-direction: column; gap: 8px; pointer-events: none; width: max-content; max-width: 92vw;
		}
		.nrt-toast {
			background: #0f172a; color: #fff; padding: 13px 26px; border-radius: 50px;
			font-size: 14px; font-weight: 800; box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25);
			display: flex; align-items: center; gap: 9px; animation: nrtSlideDown 0.28s cubic-bezier(0.16, 1, 0.3, 1);
			border: 1px solid rgba(255,255,255,0.12);
		}
		.nrt-toast.warn { background: #dc2626; }
		.nrt-toast.success { background: #059669; }
		@keyframes nrtSlideDown {
			from { opacity: 0; transform: translateY(-16px) scale(0.95); }
			to { opacity: 1; transform: translateY(0) scale(1); }
		}

		/* Top Clean Header */
		.nrt-header {
			background: #ffffff; color: #0f172a; padding: 16px 22px; border-radius: 22px;
			margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;
			flex-wrap: wrap; gap: 14px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
			border: 1.5px solid #e2e8f0; box-sizing: border-box;
		}
		.nrt-brand-wrap { display: flex; align-items: center; gap: 14px; }

		/* Left Sidebar Toggle Button */
		.nrt-sidebar-toggle-btn {
			width: 44px; height: 44px; border-radius: 13px;
			background: #f8fafc; border: 1.5px solid #cbd5e1; color: #334155;
			display: inline-flex; align-items: center; justify-content: center;
			cursor: pointer; transition: all 0.18s ease; flex-shrink: 0;
		}
		.nrt-sidebar-toggle-btn:hover { background: #fff7ed; border-color: #f97316; color: #f97316; }
		.nrt-sidebar-toggle-btn:active { transform: scale(0.94); }

		.nrt-brand-icon {
			width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
			display: flex; align-items: center; justify-content: center;
			font-size: 22px; font-weight: 900; color: #fff; box-shadow: 0 6px 18px rgba(249, 115, 22, 0.35);
			overflow: hidden;
		}
		.nrt-brand-icon.has-img {
			background: #ffffff; border: 1.5px solid #e2e8f0; padding: 4px; box-sizing: border-box;
		}
		.nrt-brand-icon img {
			width: 100%; height: 100%; object-fit: contain; border-radius: 10px;
		}
		.nrt-header h2 { margin: 0; font-size: 20px; font-weight: 900; letter-spacing: -0.3px; color: #0f172a; }
		.nrt-header-sub { font-size: 12.5px; color: #64748b; margin-top: 2px; font-weight: 600; }
		.nrt-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

		/* Top Right Live Cart & Order Status Pill Button */
		.nrt-tray-toggle-btn {
			background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; border: none;
			padding: 0 18px; height: 46px; border-radius: 30px; font-weight: 800; font-size: 13.5px; cursor: pointer;
			display: inline-flex; align-items: center; gap: 9px; box-shadow: 0 6px 16px rgba(15, 23, 42, 0.25);
			transition: all 0.18s ease;
		}
		.nrt-tray-toggle-btn:hover { background: #f97316; }
		.nrt-tray-toggle-btn:active { transform: scale(0.95); }
		.nrt-tray-toggle-btn.is-active { background: #f97316; box-shadow: 0 6px 16px rgba(249, 115, 22, 0.35); }
		.nrt-btn-badge {
			background: #f97316; color: #fff; font-size: 11.5px; font-weight: 900;
			padding: 2px 8px; border-radius: 12px; transition: all 0.2s ease;
		}
		.nrt-btn-total-pill {
			font-family: 'SFMono-Regular', Consolas, monospace;
			background: rgba(255, 255, 255, 0.16); color: #fff;
			padding: 3px 9px; border-radius: 10px; font-size: 13px; font-weight: 900;
		}
		.nrt-tray-toggle-btn.is-active .nrt-btn-badge, .nrt-tray-toggle-btn:hover .nrt-btn-badge {
			background: #fff; color: #f97316;
		}

		.nrt-waiter-btn {
			background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #fff; border: none;
			padding: 0 18px; height: 46px; border-radius: 30px; font-weight: 800; font-size: 13px; cursor: pointer;
			display: inline-flex; align-items: center; gap: 7px; box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
			transition: all 0.18s ease;
		}
		.nrt-waiter-btn:active { transform: scale(0.95); }
		.nrt-waiter-btn:disabled {
			background: #94a3b8; color: #f8fafc; box-shadow: none; cursor: not-allowed; opacity: 0.85;
		}

		.nrt-bill-btn {
			background: #eff6ff; color: #1d4ed8; border: 1.5px solid #bfdbfe;
			padding: 0 16px; height: 46px; border-radius: 30px; font-weight: 800; font-size: 13px; cursor: pointer;
			display: inline-flex; align-items: center; gap: 7px; transition: all 0.18s ease;
		}
		.nrt-bill-btn:hover:not(:disabled) { background: #dbeafe; border-color: #93c5fd; }
		.nrt-bill-btn:active:not(:disabled) { transform: scale(0.95); }
		.nrt-bill-btn:disabled {
			background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; cursor: not-allowed; opacity: 0.7;
		}

		.nrt-table-pill {
			background: #fff7ed; border: 1.5px solid #fed7aa; color: #ea580c;
			padding: 0 16px; height: 44px; border-radius: 30px; font-weight: 900; font-size: 13.5px;
			display: inline-flex; align-items: center; gap: 6px;
		}

		/* Main Split App Layout */
		.nrt-app { display: flex; gap: 18px; align-items: flex-start; width: 100%; box-sizing: border-box; position: relative; }

		/* Left Sidebar Category Menu */
		.nrt-sidebar-cats {
			width: 195px; min-width: 195px; flex-shrink: 0; background: #fff; border: 1.5px solid #e2e8f0;
			border-radius: 20px; padding: 10px; display: flex; flex-direction: column; gap: 6px;
			position: -webkit-sticky; position: sticky; top: 14px; box-sizing: border-box;
			box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
			transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.nrt-sidebar-cats.sidebar-hidden {
			display: none !important;
		}
		.nrt-scat-title {
			font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8;
			letter-spacing: 0.6px; padding: 4px 10px 2px;
		}
		.nrt-scat-btn {
			width: 100%; background: #f8fafc; border: 1.5px solid #e2e8f0; padding: 11px 13px; border-radius: 12px;
			font-weight: 800; font-size: 13px; color: #475569; cursor: pointer; text-align: left;
			display: flex; justify-content: space-between; align-items: center; gap: 6px; transition: all 0.18s ease;
			box-sizing: border-box; white-space: normal !important; height: auto !important;
		}
		.nrt-scat-btn span:first-child {
			white-space: normal !important; overflow: visible !important; text-overflow: unset !important; word-break: break-word;
		}
		.nrt-scat-label-wrap { display: flex; align-items: center; gap: 7px; min-width: 0; }
		.nrt-scat-btn:hover { background: #fff7ed; border-color: #f97316; color: #f97316; }
		.nrt-scat-btn.active {
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #fff; border-color: transparent;
			box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
		}
		.nrt-scat-btn .scnt {
			background: #e2e8f0; color: #475569; font-size: 11px; padding: 2px 7px; border-radius: 8px; font-weight: 800; flex-shrink: 0;
		}
		.nrt-scat-btn.active .scnt { background: rgba(255,255,255,0.25); color: #fff; }

		.nrt-middle { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 16px; box-sizing: border-box; }

		/* Search Bar, View Switcher & Quick Tip */
		.nrt-toolbar {
			background: #fff; padding: 12px 16px; border-radius: 18px; border: 1.5px solid #e2e8f0;
			box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03); display: flex; align-items: center; gap: 12px; flex-wrap: wrap; box-sizing: border-box;
		}
		.nrt-search-wrap { position: relative; flex: 1; min-width: 220px; box-sizing: border-box; }
		.nrt-search {
			width: 100%; height: 46px; padding: 11px 44px 11px 42px; border: 1.5px solid #cbd5e1; border-radius: 13px;
			font-size: 14.5px; font-weight: 600; color: #0f172a; background: #f8fafc; box-sizing: border-box;
			transition: all 0.2s ease;
		}
		.nrt-search:focus { outline: none; background: #fff; border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.15); }
		.nrt-search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); display: flex; align-items: center; pointer-events: none; }
		.nrt-search-clear {
			display: none; position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
			background: #e2e8f0; border: none; width: 28px; height: 28px; border-radius: 50%;
			color: #475569; cursor: pointer; align-items: center; justify-content: center;
		}
		.nrt-search-clear:hover { background: #cbd5e1; color: #0f172a; }

		/* Grid / List View Switcher */
		.nrt-view-switcher {
			display: inline-flex; align-items: center; background: #f1f5f9;
			padding: 4px; border-radius: 13px; border: 1.5px solid #e2e8f0; gap: 4px; flex-shrink: 0;
		}
		.nrt-view-btn {
			display: inline-flex; align-items: center; gap: 6px; padding: 8px 13px;
			border-radius: 9px; border: none; background: transparent; color: #64748b;
			font-size: 13px; font-weight: 800; cursor: pointer; transition: all 0.18s ease;
		}
		.nrt-view-btn:hover { color: #0f172a; }
		.nrt-view-btn.active {
			background: #ffffff; color: #f97316; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
		}

		.nrt-quick-guide {
			font-size: 12.5px; font-weight: 700; color: #64748b; background: #f8fafc;
			padding: 8px 14px; border-radius: 11px; border: 1.5px solid #e2e8f0;
		}

		/* Empty & No Results Box */
		.nrt-empty-state-box {
			text-align: center; padding: 60px 20px; color: #64748b;
			background: #fff; border-radius: 22px; border: 1.5px solid #e2e8f0;
		}
		.nrt-empty-state-box h3 { margin: 0 0 6px; color: #0f172a; font-size: 20px; }
		.nrt-empty-state-box p { margin: 0; font-size: 14.5px; }
		.nrt-hidden { display: none; }

		/* Food Menu Grid (Column View - Default) */
		.nrt-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; width: 100%; box-sizing: border-box; }
		.nrt-card {
			background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 14px;
			box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03); display: flex; flex-direction: column;
			justify-content: space-between; box-sizing: border-box; min-width: 0;
			transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s ease, border-color 0.22s ease;
		}
		.nrt-card:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(15, 23, 42, 0.08); border-color: #cbd5e1; }
		.nrt-card.in-cart { border-color: #f97316; box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.2); }
		.nrt-img-box { position: relative; overflow: hidden; border-radius: 14px; height: 150px; background: #f8fafc; width: 100%; box-sizing: border-box; flex-shrink: 0; }
		.nrt-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.35s ease; }
		.nrt-card:hover img { transform: scale(1.05); }
		.nrt-cat-tag {
			position: absolute; top: 9px; left: 9px; background: rgba(15, 23, 42, 0.8); color: #fff;
			font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 20px; backdrop-filter: blur(4px);
		}
		.nrt-in-tray-badge {
			display: none; position: absolute; top: 9px; right: 9px;
			background: #10b981; color: #fff; font-size: 11px; font-weight: 900;
			padding: 4px 10px; border-radius: 20px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.35);
		}
		.nrt-card-content { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; }
		.nrt-card-body { flex: 1; min-width: 0; width: 100%; }
		.nrt-card h4 {
			margin: 11px 0 6px; font-size: 15.5px; font-weight: 900; color: #0f172a; line-height: 1.35; width: 100%;
			white-space: normal !important; overflow: visible !important; text-overflow: unset !important; word-break: break-word;
		}
		.nrt-card .price { color: #f97316; font-weight: 900; font-size: 18px; margin-bottom: 10px; width: 100%; }

		/* Touch-Friendly Extras Box on Card */
		.nrt-extras-box {
			background: #f8fafc; padding: 8px; border-radius: 13px; margin-bottom: 12px;
			border: 1.5px solid #e2e8f0; display: flex; flex-direction: column; gap: 7px; box-sizing: border-box;
		}
		.nrt-extras-hint { font-size: 11px; font-weight: 700; color: #64748b; padding: 0 4px; }
		.nrt-extra-item {
			display: flex; align-items: center; justify-content: space-between; gap: 8px;
			cursor: pointer; padding: 8px 11px; border-radius: 10px; background: #fff;
			border: 1.5px solid #e2e8f0; font-size: 12.5px; font-weight: 700; color: #334155;
			transition: all 0.16s ease; user-select: none; box-sizing: border-box;
			height: auto !important; min-height: 26px;
		}
		.nrt-extra-item:hover { border-color: #f97316; color: #0f172a; }
		.nrt-extra-item:active { transform: scale(0.98); }
		.nrt-extra-item:has(input:checked) { background: #fff7ed; border-color: #f97316; color: #c2410c; }
		.nrt-extra-left {
			display: flex; align-items: center; gap: 8px; min-width: 0; overflow: visible !important; text-overflow: unset !important; white-space: normal !important; word-break: break-word;
		}
		.nrt-extra-item input { accent-color: #f97316; width: 16px; height: 16px; margin: 0; cursor: pointer; flex-shrink: 0; }
		.nrt-extra-price-tag { color: #f97316; }

		/* Card Action Row */
		.nrt-card-actions { display: flex; align-items: center; gap: 8px; width: 100%; box-sizing: border-box; }
		.nrt-card-qty-ctrl {
			display: inline-flex; align-items: center; background: #f1f5f9;
			border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 3px; height: 46px; box-sizing: border-box; flex-shrink: 0;
		}
		.nrt-cq-btn {
			width: 32px; height: 36px; border: none; background: #fff; border-radius: 9px;
			font-weight: 900; font-size: 16px; color: #0f172a; cursor: pointer;
			display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.06);
		}
		.nrt-cq-btn:active { background: #e2e8f0; transform: scale(0.93); }
		.nrt-cq-val { min-width: 24px; text-align: center; font-weight: 900; font-size: 14.5px; color: #0f172a; }

		.nrt-add {
			background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; border: none; padding: 0 12px; height: 46px; flex: 1; border-radius: 13px;
			font-weight: 900; font-size: 14px; cursor: pointer; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2); box-sizing: border-box;
			transition: transform 0.14s cubic-bezier(0.16, 1, 0.3, 1); white-space: nowrap;
		}
		.nrt-add:hover { background: #f97316; }
		.nrt-add:active { transform: scale(0.96); }

		/* =========================================================
		   ROW / LIST VIEW OVERRIDES (.nrt-grid.is-list-view)
		   Order: 1. Title (Full Width) -> 2. Price -> 3. Qty & Add
		   ========================================================= */
		.nrt-grid.is-list-view {
			grid-template-columns: 1fr !important;
			gap: 12px;
		}
		.nrt-grid.is-list-view .nrt-card {
			flex-direction: row;
			align-items: flex-start;
			justify-content: flex-start;
			gap: 16px;
			padding: 14px 16px;
		}
		.nrt-grid.is-list-view .nrt-card:hover {
			transform: translateY(-1px);
		}
		.nrt-grid.is-list-view .nrt-img-box {
			width: 118px;
			height: 118px;
			border-radius: 14px;
		}
		.nrt-grid.is-list-view .nrt-cat-tag {
			font-size: 9.5px;
			padding: 3px 8px;
			top: 6px;
			left: 6px;
		}
		.nrt-grid.is-list-view .nrt-in-tray-badge {
			font-size: 9.5px;
			padding: 3px 8px;
			top: 6px;
			right: 6px;
		}
		.nrt-grid.is-list-view .nrt-card-content {
			flex: 1;
			min-width: 0;
			display: flex;
			flex-direction: column;
			align-items: stretch;
			width: 100%;
		}
		.nrt-grid.is-list-view .nrt-card h4 {
			width: 100%;
			margin: 0 0 4px;
			font-size: 16.5px;
		}
		.nrt-grid.is-list-view .nrt-card .price {
			width: 100%;
			margin-bottom: 10px;
			font-size: 17.5px;
		}
		.nrt-grid.is-list-view .nrt-extras-box {
			flex-direction: row;
			flex-wrap: wrap;
			align-items: center;
			padding: 7px 9px;
			margin-bottom: 10px;
			gap: 6px;
			width: 100%;
		}
		.nrt-grid.is-list-view .nrt-extras-hint {
			width: 100%;
			font-size: 11px;
		}
		.nrt-grid.is-list-view .nrt-extra-item {
			padding: 5px 10px;
			font-size: 12px;
			min-height: 24px;
		}
		.nrt-grid.is-list-view .nrt-card-actions {
			width: 100%;
			max-width: 340px;
		}
		@media (max-width: 540px) {
			.nrt-grid.is-list-view .nrt-img-box {
				width: 92px;
				height: 92px;
			}
			.nrt-grid.is-list-view .nrt-card-actions {
				max-width: 100%;
			}
		}

		/* Right Column Tray & Live Tracker */
		.nrt-right-col {
			display: none; flex: 1; min-width: 0; flex-direction: column; gap: 18px; box-sizing: border-box;
			animation: nrtFadeInPanel 0.25s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.nrt-right-col.panel-open { display: flex; }
		@keyframes nrtFadeInPanel {
			from { opacity: 0; transform: translateY(10px); }
			to { opacity: 1; transform: translateY(0); }
		}

		.nrt-panel-top-bar {
			display: flex; justify-content: space-between; align-items: center;
			background: #fff; border: 1.5px solid #e2e8f0; border-radius: 18px;
			padding: 14px 20px; box-shadow: 0 4px 12px rgba(15,23,42,0.03);
		}
		.nrt-panel-top-title { font-size: 16px; color: #0f172a; display: flex; align-items: center; gap: 8px; }
		.nrt-back-menu-btn {
			background: #f1f5f9; border: 1px solid #cbd5e1; padding: 7px 15px; border-radius: 20px;
			display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155; font-weight: 800; font-size: 13px;
		}
		.nrt-back-menu-btn:hover { background: #e2e8f0; color: #0f172a; }

		.nrt-box { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 22px; padding: 22px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04); box-sizing: border-box; }

		/* Live Order Tracker */
		.nrt-live-tracker {
			display: none; flex-shrink: 0; box-sizing: border-box;
			background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
			border: 1.5px solid #334155; border-radius: 22px; padding: 22px; color: #fff;
			box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
		}
		.nrt-live-tracker-header {
			display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);
		}
		.nrt-live-tracker-title { margin: 0; font-size: 15.5px; color: #fff; font-weight: 900; display: flex; align-items: center; gap: 8px; }
		.nrt-running-total-pill {
			background: rgba(59, 130, 246, 0.25); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.5);
			padding: 5px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 900;
		}
		.nrt-order-track-card {
			background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 16px;
			padding: 16px; margin-bottom: 12px; box-sizing: border-box;
		}
		.nrt-order-track-card:last-child { margin-bottom: 0; }
		.nrt-track-top {
			display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed rgba(255,255,255,0.1);
		}
		.nrt-track-badge {
			display: inline-flex; align-items: center; gap: 6px; background: rgba(249, 115, 22, 0.2); color: #fdba74;
			padding: 3px 9px; border-radius: 8px; font-size: 12.5px; font-weight: 900; border: 1px solid rgba(249, 115, 22, 0.4);
		}
		.nrt-track-time { color: #94a3b8; font-weight: 600; }
		.nrt-track-amt { font-size: 14px; font-weight: 900; color: #fff; font-family: monospace; }
		.nrt-track-breakdown {
			background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 11px;
			padding: 8px 10px; margin-bottom: 12px; box-sizing: border-box; font-size: 12px; color: #cbd5e1;
		}
		.nrt-tb-row { display: flex; flex-direction: column; gap: 2px; padding: 5px 0; border-bottom: 1px dashed rgba(255,255,255,0.08); }
		.nrt-tb-row:last-of-type { border-bottom: none; }
		.nrt-tb-item-head { display: flex; justify-content: space-between; align-items: flex-start; width: 100%; gap: 8px; }
		.nrt-tb-item-name { color: #fff; }
		.nrt-tb-item-sub { color: #94a3b8; font-size: 11px; font-family: monospace; }
		.nrt-tb-item-total { font-family: monospace; white-space: nowrap; color: #fff; }
		.nrt-tb-totals { margin-top: 6px; padding-top: 6px; border-top: 1px dashed rgba(255,255,255,0.15); display: flex; flex-direction: column; gap: 2px; font-size: 11.5px; color: #94a3b8; font-weight: 700; }
		.nrt-tb-sum-line { display: flex; justify-content: space-between; }
		.nrt-tb-sum-val { font-family: monospace; color: #cbd5e1; }
		.nrt-tb-grand-line { display: flex; justify-content: space-between; color: #fff; font-size: 12.5px; font-weight: 900; padding-top: 3px; border-top: 1px dashed #475569; }
		.nrt-tb-grand-val { font-family: monospace; color: #60a5fa; }

		/* Progress Stepper */
		.nrt-stepper-wrap { display: flex; align-items: flex-start; justify-content: space-between; position: relative; margin-bottom: 12px; padding: 0 4px; box-sizing: border-box; }
		.nrt-step-node { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2; text-align: center; }
		.nrt-step-node:not(:last-child)::after { content: ''; position: absolute; top: 14px; left: 50%; width: 100%; height: 3px; background: #334155; z-index: -1; }
		.nrt-step-node.done:not(:last-child)::after { background: #3b82f6; }
		.nrt-step-node.served-done:not(:last-child)::after { background: #10b981; }
		.nrt-step-circle { width: 30px; height: 30px; border-radius: 50%; background: #1e293b; border: 2px solid #475569; color: #94a3b8; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
		.nrt-step-node.done .nrt-step-circle { background: #1e293b; border-color: #3b82f6; color: #60a5fa; }
		.nrt-step-node.current .nrt-step-circle { background: #f97316; border-color: #f97316; color: #fff; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.3); transform: scale(1.06); }
		.nrt-step-node.current.is-served .nrt-step-circle, .nrt-step-node.served-done .nrt-step-circle { background: #10b981; border-color: #10b981; color: #fff; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.3); }
		.nrt-step-lbl { font-size: 11px; font-weight: 800; color: #94a3b8; margin-top: 5px; }
		.nrt-step-node.done .nrt-step-lbl { color: #93c5fd; }
		.nrt-step-node.current .nrt-step-lbl { color: #fb923c; font-weight: 900; }
		.nrt-step-node.current.is-served .nrt-step-lbl { color: #34d399; font-weight: 900; }

		.nrt-step-msg-box { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 10px; font-size: 12.5px; font-weight: 700; line-height: 1.4; box-sizing: border-box; }
		.nrt-step-msg-box.msg-pending { background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: #93c5fd; }
		.nrt-step-msg-box.msg-preparing { background: rgba(249, 115, 22, 0.2); border: 1px solid rgba(249, 115, 22, 0.4); color: #fdba74; }
		.nrt-step-msg-box.msg-served { background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; }
		.nrt-served-check { display: inline-flex; color: #059669; }
		.nrt-pulse-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; animation: nrtPulse 1.5s infinite; }
		.nrt-pulse-blue { background: #2563eb; }
		.nrt-pulse-orange { background: #f97316; }
		@keyframes nrtPulse { 0% { transform: scale(0.95); opacity: 1; } 50% { transform: scale(1.4); opacity: 0.5; } 100% { transform: scale(0.95); opacity: 1; } }

		/* Receipt Box */
		.nrt-receipt-box { background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 20px 20px 10px 10px; padding: 24px 24px 28px; box-shadow: 0 15px 35px -8px rgba(15, 23, 42, 0.12); position: relative; color: #0f172a; box-sizing: border-box; }
		.nrt-receipt-box::after { content: ''; position: absolute; left: 0; right: 0; bottom: -8px; height: 9px; background: linear-gradient(-45deg, transparent 33.33%, #ffffff 33.33%, #ffffff 66.66%, transparent 66.66%), linear-gradient(45deg, transparent 33.33%, #ffffff 33.33%, #ffffff 66.66%, transparent 66.66%); background-size: 14px 16px; }
		.nrt-rc-head { text-align: center; padding-bottom: 14px; border-bottom: 1.5px dashed #cbd5e1; }
		.nrt-rc-brand { font-size: 17px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; color: #0f172a; }
		.nrt-rc-meta { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 700; }
		.nrt-rc-subbar { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 10px; border-top: 1px dashed #e2e8f0; }
		.nrt-cart-count { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 900; display: inline-block; }
		.nrt-cart-count.bump { transform: scale(1.18); background: #f97316; color: #fff; }
		.nrt-clear-btn { background: none; border: none; color: #94a3b8; font-size: 12.5px; font-weight: 800; cursor: pointer; text-decoration: underline; padding: 3px; display: none; }
		.nrt-clear-btn:hover { color: #ef4444; }

		.nrt-rc-cols { display: grid; grid-template-columns: 1fr auto; font-family: 'SFMono-Regular', Consolas, monospace; font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #64748b; padding: 10px 0 8px; border-bottom: 1.5px dashed #cbd5e1; }
		.nrt-cart-list { margin: 6px 0 12px; max-height: 360px; overflow-y: auto; -webkit-overflow-scrolling: touch; box-sizing: border-box; }
		.nrt-empty-cart-msg { color: #94a3b8; text-align: center; padding: 35px 0; font-size: 14px; line-height: 1.5; margin: 0; }
		.nrt-rc-item { padding: 12px 0; border-bottom: 1px dashed #e2e8f0; box-sizing: border-box; }
		.nrt-rc-item:last-child { border-bottom: none; }
		.nrt-rc-item-main { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
		.nrt-rc-item-left { display: flex; align-items: flex-start; gap: 9px; min-width: 0; }
		.nrt-rc-qty-badge { font-family: 'SFMono-Regular', Consolas, monospace; background: #0f172a; color: #fff; font-size: 12.5px; font-weight: 900; padding: 2px 8px; border-radius: 7px; flex-shrink: 0; }
		.nrt-rc-name { font-size: 14.5px; font-weight: 900; color: #0f172a; line-height: 1.35; }
		.nrt-rc-unit-sub { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 11.5px; color: #64748b; margin-top: 3px; font-weight: 600; }
		.nrt-rc-line-amt { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 15px; font-weight: 900; color: #0f172a; white-space: nowrap; }

		.nrt-rc-extras-list { margin: 6px 0 4px 12px; padding-left: 10px; border-left: 2px solid #fed7aa; display: flex; flex-direction: column; gap: 3px; }
		.nrt-rc-ex-line { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #c2410c; font-weight: 700; }
		.nrt-rc-ex-price { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 11.5px; color: #ea580c; }
		.nrt-rc-item-controls { display: flex; justify-content: space-between; align-items: center; margin-top: 9px; padding-top: 7px; }
		.nrt-edit-ex-trigger { display: inline-flex; align-items: center; gap: 5px; background: #fff7ed; color: #ea580c; border: 1.5px solid #fed7aa; padding: 5px 11px; border-radius: 8px; font-size: 12px; font-weight: 800; cursor: pointer; }
		.nrt-qty-box { display: flex; align-items: center; gap: 8px; background: #f1f5f9; padding: 3px 6px; border-radius: 10px; border: 1.5px solid #e2e8f0; }
		.nrt-qty-btn { background: #fff; border: 1px solid #cbd5e1; width: 32px; height: 32px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #0f172a; }
		.nrt-qty-num { font-weight: 900; min-width: 20px; text-align: center; font-size: 14px; }

		.nrt-rc-summary { border-top: 1.5px dashed #94a3b8; border-bottom: 1.5px dashed #94a3b8; padding: 14px; margin-top: 8px; background: #f8fafc; border-radius: 12px; box-sizing: border-box; }
		.nrt-rc-sum-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #475569; margin-bottom: 6px; font-weight: 700; }
		.nrt-rc-sum-row span:last-child { font-family: 'SFMono-Regular', Consolas, monospace; font-weight: 800; color: #0f172a; }
		.nrt-rc-grand-row { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1.5px dashed #cbd5e1; font-size: 17.5px; font-weight: 900; color: #0f172a; }
		.nrt-rc-grand-amt { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 22px; color: #f97316; }

		.nrt-note-label { display: block; font-size: 12px; font-weight: 800; color: #475569; margin-top: 14px; margin-bottom: 6px; }
		.nrt-note-chips { display: flex; flex-wrap: wrap; gap: 7px; }
		.nrt-note-chip { background: #f8fafc; border: 1.5px solid #e2e8f0; color: #475569; padding: 6px 13px; border-radius: 20px; font-size: 12.5px; font-weight: 700; cursor: pointer; }
		.nrt-note-chip:hover { background: #fff7ed; border-color: #f97316; color: #ea580c; }
		.nrt-note-textarea { width: 100%; margin-top: 10px; padding: 11px 14px; border-radius: 12px; border: 1.5px solid #cbd5e1; font-size: 14px; box-sizing: border-box; background: #f8fafc; font-family: inherit; }
		.nrt-note-textarea:focus { outline: none; border-color: #f97316; background: #fff; }

		.nrt-submit { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border: none; width: 100%; height: 56px; padding: 0 16px; font-size: 16.5px; font-weight: 900; border-radius: 16px; cursor: pointer; margin-top: 14px; box-shadow: 0 10px 24px rgba(16, 185, 129, 0.35); display: flex; align-items: center; justify-content: center; gap: 10px; box-sizing: border-box; }

		/* Modals */
		.nrt-modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 99999; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; }
		.nrt-modal { background: #fff; border-radius: 26px; padding: 28px; max-width: 480px; width: 100%; box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.4); position: relative; max-height: 90vh; overflow-y: auto; color: #0f172a; box-sizing: border-box; }
		.nrt-modal-title { margin: 0 0 4px; font-size: 20px; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: 8px; }
		.nrt-modal-sparkle { color: #f97316; display: inline-flex; }
		.nrt-modal-subtitle { margin: 0 0 18px; font-size: 14px; color: #64748b; font-weight: 700; }
		.nrt-modal-scroll { max-height: 290px; overflow-y: auto; margin-bottom: 20px; }
		.nrt-modal-actions { display: flex; gap: 11px; }
		.nrt-modal-save-btn { flex: 1; height: 50px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #fff; border: none; padding: 13px; border-radius: 14px; font-weight: 900; font-size: 15.5px; cursor: pointer; }
		.nrt-modal-cancel-btn { height: 50px; background: #f1f5f9; color: #334155; border: none; padding: 13px 20px; border-radius: 14px; font-weight: 800; cursor: pointer; }

		.nrt-cart-ex-pill { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1.5px solid #cbd5e1; border-radius: 14px; margin-bottom: 10px; cursor: pointer; font-weight: 700; font-size: 14.5px; box-sizing: border-box; }
		.nrt-cart-ex-pill:has(input:checked) { border-color: #f97316; background: #fff7ed; color: #c2410c; }
		.nrt-cart-ex-pill-left { display: flex; align-items: center; }
		.nrt-cart-ex-pill input { width: 20px; height: 20px; accent-color: #f97316; margin-right: 12px; }
		.nrt-cart-ex-pill-price { color: #f97316; font-weight: 900; }

		/* Order Status Popup Modal */
		.nrt-sp-center { text-align: center; }
		.nrt-sp-icon-circle {
			width: 66px; height: 66px; border-radius: 50%;
			background: #ffedd5; color: #ea580c;
			display: flex; align-items: center; justify-content: center;
			margin: 0 auto 14px; box-shadow: 0 8px 20px rgba(249, 115, 22, 0.18);
		}
		.nrt-sp-icon-circle.st-pending { background: #dbeafe; color: #1d4ed8; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.18); }
		.nrt-sp-icon-circle.st-preparing { background: #ffedd5; color: #ea580c; box-shadow: 0 8px 20px rgba(249, 115, 22, 0.2); }
		.nrt-sp-icon-circle.st-served { background: #d1fae5; color: #059669; box-shadow: 0 8px 20px rgba(5, 150, 105, 0.2); }
		.nrt-sp-tag {
			display: inline-block; padding: 4px 12px; border-radius: 20px;
			font-size: 11.5px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.6px;
			background: #f1f5f9; color: #475569; margin-bottom: 8px;
		}
		.nrt-sp-tag.st-pending { background: #eff6ff; color: #1d4ed8; }
		.nrt-sp-tag.st-preparing { background: #fff7ed; color: #ea580c; }
		.nrt-sp-tag.st-served { background: #ecfdf5; color: #059669; }
		.nrt-sp-heading { margin: 0 0 8px; font-size: 22px; font-weight: 900; color: #0f172a; }
		.nrt-sp-msg {
			margin: 0 0 18px; font-size: 14.5px; line-height: 1.55; color: #475569; font-weight: 600;
			background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 14px 16px;
		}
		.nrt-sp-summary-box {
			background: #fff7ed; border: 1.5px dashed #fed7aa; border-radius: 16px;
			padding: 14px 16px; margin-bottom: 18px;
		}
		.nrt-sp-sum-grid {
			display: flex; justify-content: space-between; align-items: center; gap: 12px;
		}
		.nrt-sp-sum-lbl { font-size: 12px; font-weight: 800; color: #9a3412; text-transform: uppercase; }
		.nrt-sp-sum-val { font-size: 18px; font-weight: 900; color: #0f172a; font-family: 'SFMono-Regular', Consolas, monospace; margin-top: 2px; }
		.nrt-sp-sum-val.amt { color: #ea580c; font-size: 20px; }
		.nrt-sp-actions { display: flex; flex-direction: column; gap: 10px; }
		.nrt-sp-primary-btn {
			height: 50px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
			color: #fff; border: none; border-radius: 15px; font-weight: 900; font-size: 15px;
			cursor: pointer; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
		}
		.nrt-sp-close-btn {
			height: 44px; background: #f1f5f9; color: #334155; border: none;
			border-radius: 13px; font-weight: 800; font-size: 14px; cursor: pointer; width: 100%;
		}

		/* Confirmation Modal */
		.nrt-conf-center { text-align: center; }
		.nrt-conf-check-circle { width: 64px; height: 64px; border-radius: 50%; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; box-shadow: 0 8px 20px rgba(5,150,105,0.2); }
		.nrt-conf-heading { margin: 0 0 5px; font-size: 23px; color: #0f172a; font-weight: 900; }
		.nrt-conf-desc { color: #64748b; font-size: 14px; margin: 0; font-weight: 500; }
		.nrt-conf-receipt { background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 18px; padding: 16px 18px; margin: 16px 0 20px; text-align: left; box-sizing: border-box; }
		.nrt-conf-head { display: flex; justify-content: space-between; align-items: center; padding-bottom: 9px; margin-bottom: 9px; border-bottom: 1.5px dashed #cbd5e1; font-size: 12.5px; font-weight: 900; color: #475569; text-transform: uppercase; }
		.nrt-conf-badge-cnt { color: #f97316; }
		.nrt-conf-items-scroll { max-height: 250px; overflow-y: auto; padding-right: 4px; box-sizing: border-box; }
		.nrt-conf-item-block { padding: 10px 0; border-bottom: 1px dashed #e2e8f0; box-sizing: border-box; }
		.nrt-conf-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; font-size: 14px; }
		.nrt-conf-item-title { color: #0f172a; }
		.nrt-conf-sub-line { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 12px; color: #64748b; margin-top: 3px; font-weight: 600; }
		.nrt-conf-item-amt { font-family: 'SFMono-Regular', Consolas, monospace; color: #0f172a; font-size: 14.5px; white-space: nowrap; }
		.nrt-conf-ex-breakdown { margin: 6px 0 0 12px; padding-left: 10px; border-left: 2px solid #fed7aa; display: flex; flex-direction: column; gap: 3px; }
		.nrt-conf-ex-row { display: flex; justify-content: space-between; font-size: 12px; color: #c2410c; font-weight: 700; }
		.nrt-conf-note { display: none; margin-top: 10px; padding: 8px 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; font-size: 12.5px; color: #92400e; font-weight: 700; }
		.nrt-conf-totals { margin-top: 12px; font-size: 13.5px; color: #475569; background: #fff; padding: 12px 14px; border-radius: 12px; border: 1.5px solid #cbd5e1; box-sizing: border-box; }
		.nrt-conf-totals strong, .nrt-conf-mono { font-family: 'SFMono-Regular', Consolas, monospace; }
		.nrt-conf-tot-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
		.nrt-conf-tax-row { display: flex; justify-content: space-between; margin-bottom: 7px; }
		.nrt-conf-grand-row { display: flex; justify-content: space-between; font-size: 17px; font-weight: 900; color: #0f172a; padding-top: 9px; border-top: 1.5px dashed #cbd5e1; }
		.nrt-conf-grand-val { color: #059669; }
		.nrt-conf-btn-stack { display: flex; flex-direction: column; gap: 10px; }
		.nrt-conf-more-btn { height: 52px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #fff; border: none; padding: 0 22px; border-radius: 15px; font-weight: 900; font-size: 15.5px; cursor: pointer; width: 100%; display: flex; align-items: center; justify-content: center; gap: 9px; }
		.nrt-conf-track-btn { height: 46px; background: #f1f5f9; color: #334155; border: none; padding: 0 20px; border-radius: 13px; font-weight: 800; font-size: 14.5px; cursor: pointer; width: 100%; }

		/* Bottom Left Sticky Total Order Pill (Appears on Scroll) */
		.nrt-sticky-left-bar {
			position: fixed;
			bottom: 22px;
			left: 22px;
			background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
			color: #fff;
			padding: 10px 16px;
			border-radius: 50px;
			display: inline-flex;
			align-items: center;
			gap: 11px;
			z-index: 992;
			box-shadow: 0 14px 32px rgba(15, 23, 42, 0.35);
			border: 1.5px solid rgba(255, 255, 255, 0.16);
			cursor: pointer;
			opacity: 0;
			visibility: hidden;
			transform: translateY(18px) scale(0.96);
			transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			box-sizing: border-box;
		}
		.nrt-sticky-left-bar.is-visible {
			opacity: 1;
			visibility: visible;
			transform: translateY(0) scale(1);
		}
		.nrt-sticky-left-bar:hover {
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
			border-color: #fdba74;
			transform: translateY(-2px);
		}
		.nrt-sticky-left-bar:active {
			transform: scale(0.96);
		}
		.nrt-sticky-left-ico {
			width: 34px;
			height: 34px;
			border-radius: 50%;
			background: rgba(249, 115, 22, 0.22);
			color: #fb923c;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}
		.nrt-sticky-left-bar:hover .nrt-sticky-left-ico {
			background: rgba(255, 255, 255, 0.22);
			color: #fff;
		}
		.nrt-sticky-left-meta {
			display: flex;
			flex-direction: column;
			line-height: 1.2;
			text-align: left;
		}
		.nrt-sticky-left-lbl {
			font-size: 11px;
			font-weight: 800;
			color: #94a3b8;
			text-transform: uppercase;
			letter-spacing: 0.4px;
		}
		.nrt-sticky-left-bar:hover .nrt-sticky-left-lbl {
			color: #ffedd5;
		}
		.nrt-sticky-left-cnt {
			font-size: 13px;
			font-weight: 900;
			color: #fff;
		}
		.nrt-sticky-left-price {
			background: #f97316;
			color: #fff;
			padding: 6px 13px;
			border-radius: 24px;
			font-family: 'SFMono-Regular', Consolas, monospace;
			font-size: 14.5px;
			font-weight: 900;
			box-shadow: 0 4px 10px rgba(249, 115, 22, 0.35);
		}
		.nrt-sticky-left-bar:hover .nrt-sticky-left-price {
			background: #ffffff;
			color: #ea580c;
		}

		.nrt-drawer-backdrop { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 995; }

		@media (max-width: 739px) {
			.nrt-wrapper { padding: 12px 12px 96px; }
			.nrt-header-right-stack { align-items: flex-start; width: 100%; }
			.nrt-app { flex-direction: column; }
			.nrt-sidebar-cats { width: 100%; min-width: 0; position: relative; top: 0; flex-direction: row; overflow-x: auto; padding: 8px; }
			.nrt-scat-title { display: none; }
			.nrt-scat-btn { width: auto; padding: 10px 14px; white-space: nowrap; }
			.nrt-grid:not(.is-list-view) { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 12px; }
			.nrt-right-col {
				display: none; position: fixed; left: 0; right: 0; bottom: 0; top: auto;
				width: 100%; max-width: 100%; max-height: 88vh; background: #f8fafc; z-index: 998;
				border-radius: 28px 28px 0 0; padding: 18px 18px 30px;
				box-shadow: 0 -20px 50px rgba(15, 23, 42, 0.35); overflow-y: auto; box-sizing: border-box;
			}
			.nrt-right-col.panel-open { display: flex; }
			.nrt-sticky-left-bar {
				bottom: 14px;
				left: 14px;
				padding: 9px 14px;
			}
		}
	</style>

	<div class="nrt-toast-container" id="nrt-toast-box"></div>
	<div class="nrt-drawer-backdrop" id="nrt-drawer-backdrop"></div>

	<div class="nrt-wrapper">
		<div class="nrt-header" id="nrt-top-header">
			<div class="nrt-brand-wrap">
				<button type="button" id="nrt-toggle-cat-sidebar" class="nrt-sidebar-toggle-btn" title="<?php esc_attr_e( 'Show or Hide Food Categories', 'nahian-restaurant-ordering-system' ); ?>">
					<?php echo $nrtok_svg_sidebar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>

				<?php if ( ! empty( $restaurant_logo ) ) : ?>
					<div class="nrt-brand-icon has-img">
						<img src="<?php echo esc_url( $restaurant_logo ); ?>" alt="<?php echo esc_attr( $restaurant_name ); ?>">
					</div>
				<?php else : ?>
					<div class="nrt-brand-icon"><?php echo esc_html( mb_substr( $restaurant_name, 0, 1 ) ); ?></div>
				<?php endif; ?>
				<div>
					<h2><?php echo esc_html( $restaurant_name ); ?></h2>
					<div class="nrt-header-sub">
						<?php
						printf(
							/* translators: 1: table number, 2: seat count */
							esc_html__( 'Welcome to Table #%1$s (%2$d Seats)', 'nahian-restaurant-ordering-system' ),
							esc_html( $table_no ),
							absint( $table_seats )
						);
						?>
					</div>
				</div>
			</div>

			<div class="nrt-header-actions">
				<button type="button" id="nrt-toggle-tray-panel" class="nrt-tray-toggle-btn" title="<?php esc_attr_e( 'View Your Order Cart & Live Food Status', 'nahian-restaurant-ordering-system' ); ?>">
					<?php echo $nrtok_svg_bag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Order', 'nahian-restaurant-ordering-system' ); ?></span>
					<span class="nrt-btn-badge" id="nrt-header-badge">0</span>
					<span class="nrt-btn-total-pill"><?php echo $cur; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span id="nrt-header-total">0.00</span></span>
				</button>

				<button type="button" id="nrt-call-waiter-btn" class="nrt-waiter-btn" title="<?php esc_attr_e( 'Tap to call staff to your table', 'nahian-restaurant-ordering-system' ); ?>">
					<?php echo $nrtok_svg_bell; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Call Waiter', 'nahian-restaurant-ordering-system' ); ?></span>
				</button>
				<button type="button" id="nrt-request-bill-btn" class="nrt-bill-btn" disabled title="<?php esc_attr_e( 'Available once your order is served', 'nahian-restaurant-ordering-system' ); ?>">
					<?php echo $nrtok_svg_receipt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Ask for Bill', 'nahian-restaurant-ordering-system' ); ?></span>
				</button>
				<span class="nrt-table-pill">
					<?php echo $nrtok_svg_table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span>
						<?php
						printf(
							/* translators: %s: table name/number */
							esc_html__( 'Table #%s', 'nahian-restaurant-ordering-system' ),
							esc_html( $table_no )
						);
						?>
					</span>
				</span>
				<input type="hidden" id="nrt-selected-table" value="<?php echo esc_attr( $table_no ); ?>">
			</div>
		</div>

		<div class="nrt-app" id="nrt-pos-app">

			<!-- Left Sidebar Categories Menu -->
			<div class="nrt-sidebar-cats" id="nrt-cat-sidebar">
				<div class="nrt-scat-title"><?php esc_html_e( 'Categories', 'nahian-restaurant-ordering-system' ); ?></div>
				<button class="nrt-scat-btn active" data-cat="all">
					<span class="nrt-scat-label-wrap"><?php echo $nrtok_svg_utensils; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'All Dishes', 'nahian-restaurant-ordering-system' ); ?></span></span>
					<span class="scnt"><?php echo absint( count( $items ) ); ?></span>
				</button>
				<?php foreach ( $cat_counts as $cat_name => $cnt ) : ?>
					<button class="nrt-scat-btn" data-cat="<?php echo esc_attr( $cat_name ); ?>">
						<span><?php echo esc_html( $cat_name ); ?></span>
						<span class="scnt"><?php echo absint( $cnt ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<!-- Middle Menu Grid / List -->
			<div class="nrt-middle" id="nrt-menu-section">
				<div class="nrt-toolbar">
					<div class="nrt-search-wrap">
						<span class="nrt-search-icon"><?php echo $nrtok_svg_search; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<input type="text" id="nrt-search-input" class="nrt-search" placeholder="<?php esc_attr_e( 'Type dish name to search (e.g. Burger, Pizza, Drinks)...', 'nahian-restaurant-ordering-system' ); ?>">
						<button type="button" id="nrt-search-clear" class="nrt-search-clear" title="<?php esc_attr_e( 'Clear search', 'nahian-restaurant-ordering-system' ); ?>"><?php echo $nrtok_svg_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					</div>

					<!-- Column (Grid) / Row (List) View Switcher -->
					<div class="nrt-view-switcher" role="group" aria-label="<?php esc_attr_e( 'Switch Menu Layout', 'nahian-restaurant-ordering-system' ); ?>">
						<button type="button" class="nrt-view-btn active" data-view="grid" title="<?php esc_attr_e( 'Column / Grid View', 'nahian-restaurant-ordering-system' ); ?>">
							<?php echo $nrtok_svg_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'Grid', 'nahian-restaurant-ordering-system' ); ?></span>
						</button>
						<button type="button" class="nrt-view-btn" data-view="list" title="<?php esc_attr_e( 'Row / List View', 'nahian-restaurant-ordering-system' ); ?>">
							<?php echo $nrtok_svg_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'List', 'nahian-restaurant-ordering-system' ); ?></span>
						</button>
					</div>

					<div class="nrt-quick-guide">
						<?php esc_html_e( 'Step 1: Choose dishes • Step 2: Tap Order above to send to kitchen', 'nahian-restaurant-ordering-system' ); ?>
					</div>
				</div>

				<?php if ( empty( $items ) ) : ?>
					<div class="nrt-empty-state-box">
						<h3><?php esc_html_e( 'Menu is Being Updated', 'nahian-restaurant-ordering-system' ); ?></h3>
						<p>
							<?php
							printf(
								/* translators: %s: Call Waiter button text */
								esc_html__( 'Please check back in a moment or tap "%s" above.', 'nahian-restaurant-ordering-system' ),
								esc_html__( 'Call Waiter', 'nahian-restaurant-ordering-system' )
							);
							?>
						</p>
					</div>
				<?php else : ?>
					<div class="nrt-grid" id="nrt-menu-grid">
						<?php
						foreach ( $items as $item ) :
							$item_id        = absint( $item->id );
							$item_name      = $item->name ?? '';
							$item_cat       = $item->category ?? '';
							$item_extra_ids = ! empty( $item->extras ) ? json_decode( $item->extras, true ) : array();
							if ( ! is_array( $item_extra_ids ) ) {
								$item_extra_ids = array();
							}
							$base_price = (float) ( $item->price ?? 0 );
							$img_url    = ! empty( $item->image_url ) ? $item->image_url : '';
							?>
							<div class="nrt-card"
								data-item-id="<?php echo absint( $item_id ); ?>"
								data-cat="<?php echo esc_attr( $item_cat ); ?>"
								data-name="<?php echo esc_attr( strtolower( $item_name ) ); ?>">
								<div class="nrt-img-box">
									<span class="nrt-cat-tag"><?php echo esc_html( $item_cat ); ?></span>
									<span class="nrt-in-tray-badge">0 added</span>
									<?php if ( ! empty( $img_url ) ) : ?>
										<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $item_name ); ?>">
									<?php else : ?>
										<div style="width:100%; height:100%; background:#f1f5f9; color:#94a3b8; display:flex; align-items:center; justify-content:center;"><?php echo $nrtok_svg_utensils; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
									<?php endif; ?>
								</div>

								<div class="nrt-card-content">
									<div class="nrt-card-body">
										<h4><?php echo esc_html( $item_name ); ?></h4>
										<div class="price"><?php echo $cur . number_format( $base_price, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

										<?php if ( ! empty( $item_extra_ids ) && ! empty( $all_extras ) ) : ?>
											<div class="nrt-extras-box">
												<div class="nrt-extras-hint"><?php esc_html_e( 'Optional Add-ons:', 'nahian-restaurant-ordering-system' ); ?></div>
												<?php
												foreach ( $item_extra_ids as $eid ) :
													if ( isset( $all_extras[ $eid ] ) ) :
														$ex       = $all_extras[ $eid ];
														$ex_name  = $ex->name ?? '';
														$ex_price = (float) ( $ex->price ?? 0 );
														?>
													<label class="nrt-extra-item">
														<span class="nrt-extra-left">
															<input type="checkbox" class="nrt-ex-chk" data-name="<?php echo esc_attr( $ex_name ); ?>" data-price="<?php echo (float) $ex_price; ?>">
															<span><?php echo esc_html( $ex_name ); ?></span>
														</span>
														<strong class="nrt-extra-price-tag">+<?php echo $cur . number_format( $ex_price, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
													</label>
														<?php
													endif;
												endforeach;
												?>
											</div>
										<?php endif; ?>
									</div>

									<div class="nrt-card-actions">
										<div class="nrt-card-qty-ctrl" title="<?php esc_attr_e( 'Select Quantity', 'nahian-restaurant-ordering-system' ); ?>">
											<button type="button" class="nrt-cq-btn nrt-cq-dec" aria-label="<?php esc_attr_e( 'Decrease quantity', 'nahian-restaurant-ordering-system' ); ?>">−</button>
											<span class="nrt-cq-val">1</span>
											<button type="button" class="nrt-cq-btn nrt-cq-inc" aria-label="<?php esc_attr_e( 'Increase quantity', 'nahian-restaurant-ordering-system' ); ?>">+</button>
										</div>
										<button type="button" class="nrt-add"
											data-id="<?php echo absint( $item_id ); ?>"
											data-name="<?php echo esc_attr( $item_name ); ?>"
											data-price="<?php echo (float) $base_price; ?>"
											data-allowed-extras='<?php echo esc_attr( wp_json_encode( array_map( 'absint', $item_extra_ids ) ) ); ?>'>
											<?php
											printf(
												/* translators: %s: formatted price */
												esc_html__( 'Add • %s', 'nahian-restaurant-ordering-system' ),
												$cur . number_format( $base_price, 0 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											);
											?>
										</button>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<div id="nrt-no-results" class="nrt-empty-state-box nrt-hidden">
						<h3><?php esc_html_e( 'No Dishes Found', 'nahian-restaurant-ordering-system' ); ?></h3>
						<p><?php esc_html_e( 'Try searching with a different word or select "All Dishes" on the left.', 'nahian-restaurant-ordering-system' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Right Column: Combined Live Status & Receipt Order Tray -->
			<div class="nrt-right-col" id="nrt-tray-section">
				<div class="nrt-panel-top-bar">
					<strong class="nrt-panel-top-title">
						<?php echo $nrtok_svg_bag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span>
							<?php
							printf(
								/* translators: %s: table name/number */
								esc_html__( 'Table #%s — Review Order & Live Status', 'nahian-restaurant-ordering-system' ),
								esc_html( $table_no )
							);
							?>
						</span>
					</strong>
					<button type="button" id="nrt-close-drawer" class="nrt-back-menu-btn" title="<?php esc_attr_e( 'Return to Food Menu', 'nahian-restaurant-ordering-system' ); ?>">
						<?php echo $nrtok_svg_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Back to Menu', 'nahian-restaurant-ordering-system' ); ?></span>
					</button>
				</div>

				<!-- 1. Live Order Status Card -->
				<div class="nrt-box nrt-live-tracker" id="nrt-live-tracker">
					<div class="nrt-live-tracker-header">
						<h4 class="nrt-live-tracker-title">
							<?php echo $nrtok_svg_radio; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Live Kitchen Progress', 'nahian-restaurant-ordering-system' ); ?></span>
						</h4>
						<span class="nrt-running-total-pill">
							<?php
							printf(
								/* translators: %s: running bill amount */
								esc_html__( 'Total Ordered: %s', 'nahian-restaurant-ordering-system' ),
								$cur . '<span id="nrt-running-bill">0.00</span>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							);
							?>
						</span>
					</div>
					<div id="nrt-live-orders-list"></div>
				</div>

				<!-- 2. Print-Style Order Receipt Box -->
				<div class="nrt-receipt-box">
					<div class="nrt-rc-head">
						<div class="nrt-rc-brand"><?php echo esc_html( $restaurant_name ); ?></div>
						<div class="nrt-rc-meta">
							<?php
							printf(
								/* translators: %s: table name/number */
								esc_html__( 'TABLE #%s • YOUR CURRENT TRAY', 'nahian-restaurant-ordering-system' ),
								esc_html( $table_no )
							);
							?>
						</div>
						<div class="nrt-rc-subbar">
							<span class="nrt-cart-count" id="nrt-cart-badge"><span id="nrt-item-count">0</span> <?php esc_html_e( 'Item(s) Selected', 'nahian-restaurant-ordering-system' ); ?></span>
							<button type="button" id="nrt-clear-cart" class="nrt-clear-btn"><?php esc_html_e( 'Remove All', 'nahian-restaurant-ordering-system' ); ?></button>
						</div>
					</div>

					<div class="nrt-rc-cols">
						<span><?php esc_html_e( 'Selected Dishes', 'nahian-restaurant-ordering-system' ); ?></span>
						<span><?php esc_html_e( 'Price', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>

					<div class="nrt-cart-list" id="nrt-cart-list">
						<p class="nrt-empty-cart-msg">
							<?php esc_html_e( 'Your tray is currently empty.', 'nahian-restaurant-ordering-system' ); ?><br>
							<small>
								<?php
								printf(
									/* translators: 1: Back to Menu button, 2: Add button */
									esc_html__( 'Tap "%1$s" and click "%2$s" on any dish you want!', 'nahian-restaurant-ordering-system' ),
									esc_html__( 'Back to Menu', 'nahian-restaurant-ordering-system' ),
									esc_html__( 'Add', 'nahian-restaurant-ordering-system' )
								);
								?>
							</small>
						</p>
					</div>

					<div class="nrt-rc-summary">
						<div class="nrt-rc-sum-row">
							<span><?php esc_html_e( 'Subtotal', 'nahian-restaurant-ordering-system' ); ?></span>
							<span><?php echo $cur; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span id="nrt-sub">0.00</span></span>
						</div>
						<?php if ( $tax_rate > 0 ) : ?>
							<div class="nrt-rc-sum-row">
								<span>
									<?php
									printf(
										/* translators: %s: tax percentage */
										esc_html__( 'VAT / Tax (%s%%)', 'nahian-restaurant-ordering-system' ),
										esc_html( $tax_rate )
									);
									?>
								</span>
								<span><?php echo $cur; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span id="nrt-tax">0.00</span></span>
							</div>
						<?php endif; ?>
						<div class="nrt-rc-grand-row">
							<span><?php esc_html_e( 'TOTAL TO PAY', 'nahian-restaurant-ordering-system' ); ?></span>
							<span class="nrt-rc-grand-amt"><?php echo $cur; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span id="nrt-total">0.00</span></span>
						</div>
					</div>

					<label class="nrt-note-label"><?php esc_html_e( 'Quick Kitchen Preferences (Optional):', 'nahian-restaurant-ordering-system' ); ?></label>
					<div class="nrt-note-chips">
						<span class="nrt-note-chip" data-note="Less spicy"><?php esc_html_e( 'Less spicy', 'nahian-restaurant-ordering-system' ); ?></span>
						<span class="nrt-note-chip" data-note="Extra spicy"><?php esc_html_e( 'Extra spicy', 'nahian-restaurant-ordering-system' ); ?></span>
						<span class="nrt-note-chip" data-note="No onion"><?php esc_html_e( 'No onion', 'nahian-restaurant-ordering-system' ); ?></span>
						<span class="nrt-note-chip" data-note="Serve together"><?php esc_html_e( 'Serve all together', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>

					<textarea id="nrt-note" class="nrt-note-textarea" rows="2" placeholder="<?php esc_attr_e( 'Write any special request for the chef here (optional)...', 'nahian-restaurant-ordering-system' ); ?>"></textarea>

					<button type="button" class="nrt-submit" id="nrt-submit-order">
						<?php echo $nrtok_svg_send; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'SEND ORDER TO KITCHEN', 'nahian-restaurant-ordering-system' ); ?></span>
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Bottom Left Sticky Total Order Button (Shows on Scroll & Opens Status Popup) -->
	<button type="button" class="nrt-sticky-left-bar" id="nrt-open-drawer-btn" title="<?php esc_attr_e( 'View Total Order & Status Popup', 'nahian-restaurant-ordering-system' ); ?>">
		<span class="nrt-sticky-left-ico"><?php echo $nrtok_svg_bag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span class="nrt-sticky-left-meta">
			<span class="nrt-sticky-left-lbl"><?php esc_html_e( 'Total Order', 'nahian-restaurant-ordering-system' ); ?></span>
			<span class="nrt-sticky-left-cnt"><span id="nrt-mob-count">0</span> <?php esc_html_e( 'Item(s)', 'nahian-restaurant-ordering-system' ); ?></span>
		</span>
		<span class="nrt-sticky-left-price"><?php echo $cur; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span id="nrt-mob-total">0.00</span></span>
	</button>

	<!-- Order Status & Total Popup Modal -->
	<div class="nrt-modal-backdrop" id="nrt-status-popup-modal">
		<div class="nrt-modal">
			<div class="nrt-sp-center">
				<div class="nrt-sp-icon-circle" id="sp-icon-circle">
					<?php echo $nrtok_svg_bag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="nrt-sp-tag" id="sp-status-tag"><?php esc_html_e( 'Order Status', 'nahian-restaurant-ordering-system' ); ?></div>
				<h3 class="nrt-sp-heading" id="sp-heading"><?php esc_html_e( 'Your Order Overview', 'nahian-restaurant-ordering-system' ); ?></h3>
				<p class="nrt-sp-msg" id="sp-nice-msg"><?php esc_html_e( 'Select dishes from the menu to build your order.', 'nahian-restaurant-ordering-system' ); ?></p>
			</div>

			<div class="nrt-sp-summary-box">
				<div class="nrt-sp-sum-grid">
					<div>
						<div class="nrt-sp-sum-lbl"><?php esc_html_e( 'Cart Items', 'nahian-restaurant-ordering-system' ); ?></div>
						<div class="nrt-sp-sum-val"><span id="sp-cart-count">0</span> <?php esc_html_e( 'Item(s)', 'nahian-restaurant-ordering-system' ); ?></div>
					</div>
					<div style="text-align:right;">
						<div class="nrt-sp-sum-lbl"><?php esc_html_e( 'Total Amount', 'nahian-restaurant-ordering-system' ); ?></div>
						<div class="nrt-sp-sum-val amt"><?php echo $cur; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span id="sp-total-amt">0.00</span></div>
					</div>
				</div>
			</div>

			<div class="nrt-sp-actions">
				<button type="button" id="sp-open-full-tray-btn" class="nrt-sp-primary-btn">
					<?php echo $nrtok_svg_bag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Review Tray & Live Tracker', 'nahian-restaurant-ordering-system' ); ?></span>
				</button>
				<button type="button" id="sp-close-popup-btn" class="nrt-sp-close-btn">
					<?php esc_html_e( 'Continue Browsing Menu', 'nahian-restaurant-ordering-system' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Post-Add Extras Editor Modal -->
	<div class="nrt-modal-backdrop" id="nrt-cart-extras-modal">
		<div class="nrt-modal">
			<h3 class="nrt-modal-title">
				<span class="nrt-modal-sparkle"><?php echo $nrtok_svg_sparkles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span><?php esc_html_e( 'Customize Add-ons', 'nahian-restaurant-ordering-system' ); ?></span>
			</h3>
			<p id="cem-dish-title" class="nrt-modal-subtitle"></p>
			<div id="cem-extras-list" class="nrt-modal-scroll"></div>
			<div class="nrt-modal-actions">
				<button type="button" id="cem-save-btn" class="nrt-modal-save-btn"><?php esc_html_e( 'Save Changes', 'nahian-restaurant-ordering-system' ); ?></button>
				<button type="button" id="cem-cancel-btn" class="nrt-modal-cancel-btn"><?php esc_html_e( 'Cancel', 'nahian-restaurant-ordering-system' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Order Confirmation Modal -->
	<div class="nrt-modal-backdrop" id="nrt-success-modal">
		<div class="nrt-modal">
			<div class="nrt-conf-center">
				<div class="nrt-conf-check-circle"><?php echo $nrtok_svg_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<h3 class="nrt-conf-heading"><?php esc_html_e( 'Order Sent to Kitchen!', 'nahian-restaurant-ordering-system' ); ?></h3>
				<p class="nrt-conf-desc">
					<?php
					printf(
						/* translators: %s: table name/number */
						esc_html__( 'Our chefs have received your order for Table #%s.', 'nahian-restaurant-ordering-system' ),
						esc_html( $table_no )
					);
					?>
				</p>
			</div>

			<div class="nrt-conf-receipt">
				<div class="nrt-conf-head">
					<span id="conf-order-id"><?php esc_html_e( 'Order Summary', 'nahian-restaurant-ordering-system' ); ?></span>
					<span id="conf-item-count" class="nrt-conf-badge-cnt">0 Item(s)</span>
				</div>
				<div class="nrt-conf-items-scroll" id="conf-items-list"></div>
				<div id="conf-note-box" class="nrt-conf-note"></div>
				<div class="nrt-conf-totals">
					<div class="nrt-conf-tot-row"><span><?php esc_html_e( 'Subtotal', 'nahian-restaurant-ordering-system' ); ?></span><strong id="conf-sub"></strong></div>
					<div id="conf-tax-row" class="nrt-conf-tax-row">
						<span>
							<?php
							printf(
								/* translators: %s: tax percentage */
								esc_html__( 'VAT / Tax (%s%%)', 'nahian-restaurant-ordering-system' ),
								esc_html( $tax_rate )
							);
							?>
						</span>
						<strong id="conf-tax"></strong>
					</div>
					<div class="nrt-conf-grand-row"><span><?php esc_html_e( 'Total Amount', 'nahian-restaurant-ordering-system' ); ?></span><span id="conf-total" class="nrt-conf-mono nrt-conf-grand-val"></span></div>
				</div>
			</div>

			<div class="nrt-conf-btn-stack">
				<button type="button" id="nrt-add-another-order-btn" class="nrt-conf-more-btn"><?php echo $nrtok_svg_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Order More Food', 'nahian-restaurant-ordering-system' ); ?></span></button>
				<button type="button" id="nrt-modal-track-btn" class="nrt-conf-track-btn"><?php esc_html_e( 'Watch Live Kitchen Status', 'nahian-restaurant-ordering-system' ); ?></button>
			</div>
		</div>
	</div>

	<!-- JavaScript Block -->
	<script id="nrtok-tablet-scripts">
	(function($) {
		if (typeof $ === 'undefined') {
			console.error('Nahian POS: jQuery is not loaded.');
			return;
		}

		$(document).ready(function() {
			const ajaxUrl          = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			const cur              = <?php echo wp_json_encode( html_entity_decode( $s['currency'] ?? '$', ENT_QUOTES, 'UTF-8' ) ); ?>;
			const taxRate          = <?php echo (float) $tax_rate; ?>;
			const allExtrasCatalog = <?php echo wp_json_encode( $js_all_extras ); ?>;
			const sparklesSvg      = <?php echo wp_json_encode( $nrtok_svg_sparkles ); ?>;
			const bellSvg          = <?php echo wp_json_encode( $nrtok_svg_bell ); ?>;
			const receiptSvg       = <?php echo wp_json_encode( $nrtok_svg_receipt ); ?>;
			const sendSvg          = <?php echo wp_json_encode( $nrtok_svg_send ); ?>;

			// Localized JS Strings
			const i18n = {
				waitingStaff:   <?php echo wp_json_encode( __( 'Waiting for Staff...', 'nahian-restaurant-ordering-system' ) ); ?>,
				callWaiter:     <?php echo wp_json_encode( __( 'Call Waiter', 'nahian-restaurant-ordering-system' ) ); ?>,
				tableMissing:   <?php echo wp_json_encode( __( 'Table number not found!', 'nahian-restaurant-ordering-system' ) ); ?>,
				callingStaff:   <?php echo wp_json_encode( __( 'Calling Staff...', 'nahian-restaurant-ordering-system' ) ); ?>,
				staffNotified:  <?php echo wp_json_encode( __( 'Staff notified! Please wait for assistance at Table #', 'nahian-restaurant-ordering-system' ) ); ?>,
				askingBill:     <?php echo wp_json_encode( __( 'Asking for Bill...', 'nahian-restaurant-ordering-system' ) ); ?>,
				billRequested:  <?php echo wp_json_encode( __( 'Bill requested! Our staff will bring your receipt shortly.', 'nahian-restaurant-ordering-system' ) ); ?>,
				billBtnDone:    <?php echo wp_json_encode( __( 'Bill Requested!', 'nahian-restaurant-ordering-system' ) ); ?>,
				askForBill:     <?php echo wp_json_encode( __( 'Ask for Bill', 'nahian-restaurant-ordering-system' ) ); ?>,
				msgPending:     <?php echo wp_json_encode( __( 'We received your order! Our kitchen will start preparing it soon.', 'nahian-restaurant-ordering-system' ) ); ?>,
				msgPreparing:   <?php echo wp_json_encode( __( 'Cooking now! Our chef is preparing your fresh meal.', 'nahian-restaurant-ordering-system' ) ); ?>,
				msgServed:      <?php echo wp_json_encode( __( 'Served at your table! Enjoy your meal.', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupTitlePending:   <?php echo wp_json_encode( __( 'Order Received by Kitchen', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupMsgPending:     <?php echo wp_json_encode( __( 'Sit back and relax! Your order is in the queue and our kitchen team will start preparing it shortly.', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupTagPending:     <?php echo wp_json_encode( __( 'Status: Received', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupTitlePreparing: <?php echo wp_json_encode( __( 'Freshly Cooking Now', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupMsgPreparing:   <?php echo wp_json_encode( __( 'Good news! Our chef is actively cooking your delicious dishes right now.', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupTagPreparing:   <?php echo wp_json_encode( __( 'Status: Cooking', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupTitleServed:    <?php echo wp_json_encode( __( 'Your Meal is Served', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupMsgServed:      <?php echo wp_json_encode( __( 'Bon appétit! Your food has been served—let us know if you would like anything else.', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupTagServed:      <?php echo wp_json_encode( __( 'Status: Served', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupTitleCart:      <?php echo wp_json_encode( __( 'Your Order Tray', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupMsgCart:        <?php echo wp_json_encode( __( 'Ready when you are! Review your selected items or send your order straight to the kitchen.', 'nahian-restaurant-ordering-system' ) ); ?>,
				popupTagCart:        <?php echo wp_json_encode( __( 'Tray Ready', 'nahian-restaurant-ordering-system' ) ); ?>,
				emptyTrayHtml:  <?php echo wp_json_encode( __( 'Your tray is currently empty.<br><small>Tap <strong>"Back to Menu"</strong> and click <strong>"Add"</strong> on any dish you want!</small>', 'nahian-restaurant-ordering-system' ) ); ?>,
				confirmClear:   <?php echo wp_json_encode( __( 'Remove all selected dishes from your tray?', 'nahian-restaurant-ordering-system' ) ); ?>,
				emptyCartWarn:  <?php echo wp_json_encode( __( 'Your tray is empty! Please add at least one dish first.', 'nahian-restaurant-ordering-system' ) ); ?>,
				sendingKitchen: <?php echo wp_json_encode( __( 'Sending to Kitchen...', 'nahian-restaurant-ordering-system' ) ); ?>,
				orderErr:       <?php echo wp_json_encode( __( 'Could not send order. Please try again.', 'nahian-restaurant-ordering-system' ) ); ?>,
				sendOrderBtn:   <?php echo wp_json_encode( __( 'SEND ORDER TO KITCHEN', 'nahian-restaurant-ordering-system' ) ); ?>
			};

			const tableKey      = $('#nrt-selected-table').val() || 'default';
			const storageKey    = 'nrtok_cart_' + tableKey;
			const waiterLockKey = 'nrtok_waiter_locked_' + tableKey;
			const viewModeKey   = 'nrtok_menu_view_mode';
			let cart            = [];
			let currentOrderStatus = 'cart';
			let currentRunningBill = '0.00';

			function renderOrderStatusPopup() {
				const $circle = $('#sp-icon-circle');
				const $tag    = $('#sp-status-tag');
				const $head   = $('#sp-heading');
				const $msg    = $('#sp-nice-msg');

				$circle.removeClass('st-pending st-preparing st-served');
				$tag.removeClass('st-pending st-preparing st-served');

				const hasCartItems = cart.length > 0;
				const cartCountText = $('#nrt-item-count').text() || '0';
				const displayTotal  = hasCartItems ? ($('#nrt-total').text() || '0.00') : currentRunningBill;

				$('#sp-cart-count').text(cartCountText);
				$('#sp-total-amt').text(displayTotal);

				if (currentOrderStatus === 'preparing') {
					$circle.addClass('st-preparing');
					$tag.addClass('st-preparing').text(i18n.popupTagPreparing);
					$head.text(i18n.popupTitlePreparing);
					$msg.text(i18n.popupMsgPreparing);
				} else if (currentOrderStatus === 'served') {
					$circle.addClass('st-served');
					$tag.addClass('st-served').text(i18n.popupTagServed);
					$head.text(i18n.popupTitleServed);
					$msg.text(i18n.popupMsgServed);
				} else if (currentOrderStatus === 'pending') {
					$circle.addClass('st-pending');
					$tag.addClass('st-pending').text(i18n.popupTagPending);
					$head.text(i18n.popupTitlePending);
					$msg.text(i18n.popupMsgPending);
				} else {
					$tag.text(i18n.popupTagCart);
					$head.text(i18n.popupTitleCart);
					$msg.text(i18n.popupMsgCart);
				}

				$('#nrt-status-popup-modal').css('display', 'flex').hide().fadeIn(180);
			}

			function handleStickyOrderVisibility() {
				const isTrayOpen = $('#nrt-tray-section').hasClass('panel-open');
				const scrollTop  = $(window).scrollTop();
				const $stickyBtn = $('#nrt-open-drawer-btn');

				if (!isTrayOpen && scrollTop > 90) {
					$stickyBtn.addClass('is-visible');
				} else {
					$stickyBtn.removeClass('is-visible');
				}
			}

			$(window).on('scroll resize', handleStickyOrderVisibility);
			handleStickyOrderVisibility();

			function applyMenuViewMode(mode) {
				const $grid = $('#nrt-menu-grid');
				$('.nrt-view-btn').removeClass('active');
				$('.nrt-view-btn[data-view="' + mode + '"]').addClass('active');
				if (mode === 'list') {
					$grid.addClass('is-list-view');
				} else {
					$grid.removeClass('is-list-view');
				}
				try { localStorage.setItem(viewModeKey, mode); } catch(e) {}
			}

			try {
				const savedView = localStorage.getItem(viewModeKey);
				if (savedView === 'list' || savedView === 'grid') {
					applyMenuViewMode(savedView);
				}
			} catch(e) {}

			$('.nrt-view-btn').on('click', function() {
				const mode = $(this).data('view') || 'grid';
				applyMenuViewMode(mode);
			});

			try {
				const saved = localStorage.getItem(storageKey);
				if (saved) cart = JSON.parse(saved) || [];
			} catch(e) {}

			function saveCartState() {
				try { localStorage.setItem(storageKey, JSON.stringify(cart)); } catch(e) {}
			}

			function showToast(msg, type = 'default') {
				const $t = $(`<div class="nrt-toast ${type}">${msg}</div>`);
				$('#nrt-toast-box').append($t);
				setTimeout(() => { $t.fadeOut(250, () => $t.remove()); }, 2800);
			}

			function playSuccessChime() {
				try {
					const ctx = new (window.AudioContext || window.webkitAudioContext)();
					const osc = ctx.createOscillator();
					osc.type = 'triangle';
					osc.frequency.setValueAtTime(660, ctx.currentTime);
					osc.frequency.setValueAtTime(880, ctx.currentTime + 0.12);
					osc.connect(ctx.destination);
					osc.start();
					osc.stop(ctx.currentTime + 0.28);
				} catch(e) {}
			}

			function setWaiterButtonLocked(isLocked) {
				const $btn = $('#nrt-call-waiter-btn');
				if (isLocked) {
					localStorage.setItem(waiterLockKey, '1');
					$btn.prop('disabled', true).html(bellSvg + '<span>' + i18n.waitingStaff + '</span>');
				} else {
					localStorage.removeItem(waiterLockKey);
					$btn.prop('disabled', false).html(bellSvg + '<span>' + i18n.callWaiter + '</span>');
				}
			}

			if (localStorage.getItem(waiterLockKey) === '1') {
				setWaiterButtonLocked(true);
			}

			$('#nrt-toggle-cat-sidebar').on('click', function() {
				$('#nrt-cat-sidebar').toggleClass('sidebar-hidden');
			});

			function toggleTrayPanel(forceOpen = null) {
				const $panel   = $('#nrt-tray-section');
				const $menuSec = $('#nrt-menu-section');
				const $btn     = $('#nrt-toggle-tray-panel');
				const isOpen   = $panel.hasClass('panel-open');

				if (forceOpen === true || (!isOpen && forceOpen === null)) {
					$panel.addClass('panel-open');
					$btn.addClass('is-active');
					$menuSec.hide();
					if ($(window).width() < 740) {
						$('#nrt-drawer-backdrop').fadeIn(180);
					}
					pollTableOrderStatus();
				} else {
					$panel.removeClass('panel-open');
					$btn.removeClass('is-active');
					$menuSec.show();
					$('#nrt-drawer-backdrop').fadeOut(180);
				}
				handleStickyOrderVisibility();
			}

			$('#nrt-toggle-tray-panel').on('click', function() { toggleTrayPanel(); });
			$('#nrt-open-drawer-btn').on('click', function() { renderOrderStatusPopup(); });
			$('#sp-close-popup-btn').on('click', function() { $('#nrt-status-popup-modal').fadeOut(150); });
			$('#nrt-status-popup-modal').on('click', function(e) { if (e.target === this) $(this).fadeOut(150); });
			$('#sp-open-full-tray-btn').on('click', function() {
				$('#nrt-status-popup-modal').fadeOut(150);
				toggleTrayPanel(true);
			});

			$('#nrt-close-drawer, #nrt-drawer-backdrop').on('click', function() { toggleTrayPanel(false); });
			$('#nrt-modal-track-btn').on('click', function() {
				$('#nrt-success-modal').fadeOut(160);
				toggleTrayPanel(true);
			});

			$('#nrt-add-another-order-btn').on('click', function() {
				$('#nrt-success-modal').fadeOut(160);
				toggleTrayPanel(false);
				const menuEl = document.getElementById('nrt-menu-section');
				if (menuEl) menuEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});

			$('#nrt-call-waiter-btn').on('click', function() {
				const tableNo = $('#nrt-selected-table').val();
				if (!tableNo) return showToast(i18n.tableMissing, 'warn');
				const $btn = $(this);
				$btn.prop('disabled', true).text(i18n.callingStaff);
				$.post(ajaxUrl, { action: 'nrtok_call_waiter', table_no: tableNo }, function(res) {
					if (res.success) {
						showToast(i18n.staffNotified + tableNo + '.', 'success');
						setWaiterButtonLocked(true);
					} else {
						setWaiterButtonLocked(false);
					}
				}).fail(function() {
					setWaiterButtonLocked(false);
				});
			});

			$('#nrt-request-bill-btn').on('click', function() {
				const tableNo = $('#nrt-selected-table').val();
				if (!tableNo) return showToast(i18n.tableMissing, 'warn');
				const $btn = $(this);
				$btn.prop('disabled', true).text(i18n.askingBill);
				$.post(ajaxUrl, { action: 'nrtok_call_waiter', table_no: tableNo + ' (BILL REQUEST)' }, function(res) {
					if (res.success) {
						showToast(i18n.billRequested, 'success');
						$btn.text(i18n.billBtnDone);
						setTimeout(() => { $btn.prop('disabled', false).html(receiptSvg + '<span>' + i18n.askForBill + '</span>'); }, 20000);
					}
				});
			});

			function pollTableOrderStatus() {
				const tableNo = $('#nrt-selected-table').val();
				if (!tableNo) return;

				const iconReceived = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>';
				const iconCooking  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>';
				const iconServed   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>';
				const iconCheckSm  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

				$.post(ajaxUrl, { action: 'nrtok_get_table_orders', table_no: tableNo }, function(res) {
					if (res && typeof res.waiter_pending !== 'undefined' && !res.waiter_pending) {
						if (localStorage.getItem(waiterLockKey) === '1') {
							setWaiterButtonLocked(false);
						}
					}

					if (res.success && res.data.length > 0) {
						let html = '', runningBill = 0, hasServedOrder = false;

						res.data.forEach((o, index) => {
							runningBill += parseFloat(String(o.total_amount).replace(/,/g, '')) || 0;
							const isServed = (o.status === 'served'), isCooking = (o.status === 'preparing');
							if (isServed) hasServedOrder = true;
							if (o.status) currentOrderStatus = o.status;

							const s1Class = isServed ? 'served-done' : (isCooking ? 'done' : 'current');
							const s2Class = isServed ? 'served-done' : (isCooking ? 'current' : '');
							const s3Class = isServed ? 'current is-served' : '';

							let stepMsgHtml = '';
							if (o.status === 'pending') {
								stepMsgHtml = `<div class="nrt-step-msg-box msg-pending"><span class="nrt-pulse-dot nrt-pulse-blue"></span><span>${i18n.msgPending}</span></div>`;
							} else if (o.status === 'preparing') {
								stepMsgHtml = `<div class="nrt-step-msg-box msg-preparing"><span class="nrt-pulse-dot nrt-pulse-orange"></span><span>${i18n.msgPreparing}</span></div>`;
							} else {
								stepMsgHtml = `<div class="nrt-step-msg-box msg-served"><span class="nrt-served-check">${iconCheckSm}</span><span>${i18n.msgServed}</span></div>`;
							}

							const roundLabel = res.data.length > 1 ? ('Order #' + (index + 1) + ' • ') : '';
							const timeStr = o.time ? (' (' + o.time + ')') : '';

							let breakdownHtml = '';
							if (Array.isArray(o.items_detail) && o.items_detail.length > 0) {
								breakdownHtml += '<div class="nrt-track-breakdown">';
								o.items_detail.forEach(d => {
									let exLines = '';
									if (Array.isArray(d.extras_details) && d.extras_details.length > 0) {
										d.extras_details.forEach(exObj => {
											exLines += `<div style="display:flex; justify-content:space-between; font-size:11px; color:#fdba74; font-weight:700; padding-left:10px;"><span>+ ${exObj.name}</span><span style="font-family:monospace;">+${cur}${exObj.price}</span></div>`;
										});
									}
									breakdownHtml += `
										<div class="nrt-tb-row">
											<div class="nrt-tb-item-head">
												<div>
													<strong class="nrt-tb-item-name">${d.qty}× ${d.name}</strong>
													<div class="nrt-tb-item-sub">Unit: ${cur}${d.unit_price} × ${d.qty}</div>
												</div>
												<strong class="nrt-tb-item-total">${cur}${d.line_total}</strong>
											</div>
											${exLines}
										</div>`;
								});
								breakdownHtml += `
									<div class="nrt-tb-totals">
										<div class="nrt-tb-sum-line"><span>Subtotal</span><span class="nrt-tb-sum-val">${cur}${o.subtotal || o.total_amount}</span></div>
										${parseFloat(o.tax_amount || 0) > 0 ? `<div class="nrt-tb-sum-line"><span>VAT / Tax</span><span class="nrt-tb-sum-val">${cur}${o.tax_amount}</span></div>` : ''}
										<div class="nrt-tb-grand-line"><span>Order Total</span><span class="nrt-tb-grand-val">${cur}${o.total_amount}</span></div>
									</div></div>`;
							}

							html += `
								<div class="nrt-order-track-card">
									<div class="nrt-track-top">
										<span class="nrt-track-badge">${roundLabel}Ticket #${o.id}<small class="nrt-track-time">${timeStr}</small></span>
										<span class="nrt-track-amt">${cur}${o.total_amount}</span>
									</div>
									${breakdownHtml}
									<div class="nrt-stepper-wrap">
										<div class="nrt-step-node ${s1Class}"><div class="nrt-step-circle">${(isCooking || isServed) ? iconCheckSm : iconReceived}</div><div class="nrt-step-lbl">1. Received</div></div>
										<div class="nrt-step-node ${s2Class}"><div class="nrt-step-circle">${isServed ? iconCheckSm : iconCooking}</div><div class="nrt-step-lbl">2. Cooking</div></div>
										<div class="nrt-step-node ${s3Class}"><div class="nrt-step-circle">${iconServed}</div><div class="nrt-step-lbl">3. Served</div></div>
									</div>
									${stepMsgHtml}
								</div>`;
						});

						currentRunningBill = runningBill.toFixed(2);

						const $billBtn = $('#nrt-request-bill-btn');
						if (hasServedOrder) {
							if ($billBtn.text().indexOf('Requested') === -1 && $billBtn.text().indexOf('Asking') === -1) {
								$billBtn.prop('disabled', false).attr('title', 'Ready to pay? Tap to request your bill');
							}
						} else {
							$billBtn.prop('disabled', true).attr('title', 'Available once your order is served');
						}

						$('#nrt-running-bill').text(currentRunningBill);
						$('#nrt-live-orders-list').html(html);
						$('#nrt-live-tracker').show();
					} else {
						currentOrderStatus = 'cart';
						currentRunningBill = '0.00';
						$('#nrt-live-tracker').hide();
						$('#nrt-request-bill-btn').prop('disabled', true).attr('title', 'Available once your order is served');
					}
				});
			}
			pollTableOrderStatus();
			setInterval(pollTableOrderStatus, 5000);

			let activeCategory = 'all';
			function filterMenuCards() {
				const q = $('#nrt-search-input').val().toLowerCase().trim();
				$('#nrt-search-clear').css('display', q.length > 0 ? 'flex' : 'none');
				let visibleCount = 0;

				$('.nrt-card').each(function() {
					const matchCat = (activeCategory === 'all') || ($(this).data('cat') === activeCategory);
					const matchText = String($(this).data('name') || '').indexOf(q) > -1;
					const show = matchCat && matchText;
					$(this).toggle(show);
					if (show) visibleCount++;
				});

				$('#nrt-no-results').toggle(visibleCount === 0);
			}

			$('.nrt-scat-btn').on('click', function() {
				$('.nrt-scat-btn').removeClass('active');
				$(this).addClass('active');
				activeCategory = $(this).data('cat');
				if ($('#nrt-tray-section').hasClass('panel-open')) {
					toggleTrayPanel(false);
				}
				filterMenuCards();
			});

			$('#nrt-search-input').on('keyup input', filterMenuCards);
			$('#nrt-search-clear').on('click', function() {
				$('#nrt-search-input').val('');
				filterMenuCards();
			});

			function updateCardButtonPrice($card) {
				const $btn = $card.find('.nrt-add');
				const basePrice = parseFloat($btn.data('price')) || 0;
				const qty = parseInt($card.find('.nrt-cq-val').text()) || 1;
				let extraSum = 0;

				$card.find('.nrt-ex-chk:checked').each(function() {
					extraSum += parseFloat($(this).data('price')) || 0;
				});

				const totalUnit = (basePrice + extraSum) * qty;
				$btn.text('Add • ' + cur + totalUnit.toFixed(0));
			}

			$(document).on('click', '.nrt-cq-inc', function() {
				const $card = $(this).closest('.nrt-card');
				const $valEl = $card.find('.nrt-cq-val');
				const curQ = parseInt($valEl.text()) || 1;
				$valEl.text(curQ + 1);
				updateCardButtonPrice($card);
			});

			$(document).on('click', '.nrt-cq-dec', function() {
				const $card = $(this).closest('.nrt-card');
				const $valEl = $card.find('.nrt-cq-val');
				const curQ = parseInt($valEl.text()) || 1;
				if (curQ > 1) {
					$valEl.text(curQ - 1);
					updateCardButtonPrice($card);
				}
			});

			$(document).on('change', '.nrt-ex-chk', function() {
				const $card = $(this).closest('.nrt-card');
				updateCardButtonPrice($card);
			});

			function syncCardTrayBadges() {
				const qtyById = {};
				cart.forEach(it => { qtyById[it.id] = (qtyById[it.id] || 0) + (parseInt(it.qty) || 0); });
				$('.nrt-card').each(function() {
					const itemId = parseInt($(this).data('item-id')) || 0;
					const count = qtyById[itemId] || 0;
					const $badge = $(this).find('.nrt-in-tray-badge');
					if (count > 0) { $(this).addClass('in-cart'); $badge.text(count + ' in tray').show(); }
					else { $(this).removeClass('in-cart'); $badge.hide(); }
				});
			}

			$('.nrt-add').on('click', function() {
				const $btn = $(this), $card = $btn.closest('.nrt-card');
				const id = $btn.data('id'), name = $btn.data('name');
				const basePrice = parseFloat($btn.data('price')) || 0;
				const allowedEx = $btn.data('allowed-extras') || [];
				const qtyToAdd = parseInt($card.find('.nrt-cq-val').text()) || 1;

				let extras = [], extraCost = 0;
				$card.find('.nrt-ex-chk:checked').each(function() {
					extras.push($(this).data('name'));
					extraCost += parseFloat($(this).data('price')) || 0;
				});

				$card.find('.nrt-ex-chk').prop('checked', false);
				$card.find('.nrt-cq-val').text('1');
				$btn.text('Add • ' + cur + basePrice.toFixed(0));

				const unitPrice = basePrice + extraCost;
				const key = id + '_' + extras.slice().sort().join('_');
				const found = cart.find(i => i.key === key);
				if (found) found.qty += qtyToAdd;
				else cart.push({ key, id, name, basePrice, unitPrice, extras, allowedEx, qty: qtyToAdd });

				updateCartUI(true);
				showToast(qtyToAdd + '× ' + name + ' added to your order!', 'success');
				$btn.text('Added!').css('background', '#10b981');
				setTimeout(() => { $btn.text('Add • ' + cur + basePrice.toFixed(0)).css('background', ''); }, 650);
			});

			function updateCartUI(animateBump = false) {
				saveCartState();
				syncCardTrayBadges();

				let html = '', subTotal = 0, totalQty = 0;
				if (cart.length === 0) {
					$('#nrt-cart-list').html('<p class="nrt-empty-cart-msg">' + i18n.emptyTrayHtml + '</p>');
					$('#nrt-sub, #nrt-tax, #nrt-total, #nrt-mob-total, #nrt-header-total').text('0.00');
					$('#nrt-item-count, #nrt-mob-count, #nrt-header-badge').text('0');
					$('#nrt-clear-cart').hide();
					return;
				}
				$('#nrt-clear-cart').show();

				cart.forEach((item, idx) => {
					const lineTotal = item.unitPrice * item.qty;
					subTotal += lineTotal;
					totalQty += item.qty;

					let baseVal = parseFloat(item.basePrice);
					if (isNaN(baseVal)) {
						let sumEx = 0;
						(item.extras || []).forEach(exName => {
							const foundEx = allExtrasCatalog.find(e => e.name === exName);
							if (foundEx) sumEx += parseFloat(foundEx.price) || 0;
						});
						baseVal = Math.max(0, item.unitPrice - sumEx);
					}

					let exLinesHtml = '';
					if (item.extras && item.extras.length > 0) {
						exLinesHtml += '<div class="nrt-rc-extras-list">';
						item.extras.forEach(exName => {
							const foundEx = allExtrasCatalog.find(e => e.name === exName);
							const exPriceStr = foundEx ? `+${cur}${parseFloat(foundEx.price).toFixed(2)}` : '';
							exLinesHtml += `<div class="nrt-rc-ex-line"><span>+ ${exName}</span><span class="nrt-rc-ex-price">${exPriceStr}</span></div>`;
						});
						exLinesHtml += '</div>';
					}

					const editExBtn = allExtrasCatalog.length > 0 ? `<button type="button" class="nrt-edit-ex-trigger" data-idx="${idx}">${sparklesSvg}<span>${item.extras.length ? 'Change Add-ons' : '+ Add Extras'}</span></button>` : '<span></span>';

					html += `
						<div class="nrt-rc-item">
							<div class="nrt-rc-item-main">
								<div class="nrt-rc-item-left">
									<span class="nrt-rc-qty-badge">${item.qty}×</span>
									<div>
										<div class="nrt-rc-name">${item.name}</div>
										<div class="nrt-rc-unit-sub">${cur}${item.unitPrice.toFixed(2)} each</div>
									</div>
								</div>
								<div class="nrt-rc-line-amt">${cur}${lineTotal.toFixed(2)}</div>
							</div>
							${exLinesHtml}
							<div class="nrt-rc-item-controls">
								${editExBtn}
								<div class="nrt-qty-box">
									<button type="button" class="nrt-qty-btn nrt-dec" data-idx="${idx}" title="Decrease">−</button>
									<span class="nrt-qty-num">${item.qty}</span>
									<button type="button" class="nrt-qty-btn nrt-inc" data-idx="${idx}" title="Increase">+</button>
								</div>
							</div>
						</div>`;
				});

				const taxAmt = (subTotal * taxRate) / 100;
				const grandTotal = subTotal + taxAmt;

				$('#nrt-cart-list').html(html);
				$('#nrt-item-count, #nrt-mob-count, #nrt-header-badge').text(totalQty);
				$('#nrt-sub').text(subTotal.toFixed(2));
				$('#nrt-tax').text(taxAmt.toFixed(2));
				$('#nrt-total, #nrt-mob-total, #nrt-header-total').text(grandTotal.toFixed(2));

				if (animateBump) {
					const $badge = $('#nrt-cart-badge');
					$badge.addClass('bump');
					setTimeout(() => $badge.removeClass('bump'), 220);
					const listEl = document.getElementById('nrt-cart-list');
					if (listEl) listEl.scrollTop = listEl.scrollHeight;
				}
			}

			let editingCartIdx = null;
			$(document).on('click', '.nrt-edit-ex-trigger', function() {
				editingCartIdx = parseInt($(this).data('idx'));
				const item = cart[editingCartIdx];
				if (!item) return;

				$('#cem-dish-title').text(item.name + ' (Base price: ' + cur + parseFloat(item.basePrice ?? item.unitPrice).toFixed(2) + ')');

				let listToShow = allExtrasCatalog;
				if (Array.isArray(item.allowedEx) && item.allowedEx.length > 0) {
					const assigned = allExtrasCatalog.filter(e => item.allowedEx.includes(e.id));
					const others = allExtrasCatalog.filter(e => !item.allowedEx.includes(e.id));
					listToShow = assigned.concat(others);
				}

				let exHtml = '';
				listToShow.forEach(ex => {
					const isChecked = item.extras.includes(ex.name) ? 'checked' : '';
					exHtml += `
						<label class="nrt-cart-ex-pill">
							<span class="nrt-cart-ex-pill-left"><input type="checkbox" class="cem-chk" data-name="${ex.name}" data-price="${ex.price}" ${isChecked}>${ex.name}</span>
							<span class="nrt-cart-ex-pill-price">+${cur}${ex.price.toFixed(2)}</span>
						</label>`;
				});

				$('#cem-extras-list').html(exHtml);
				$('#nrt-cart-extras-modal').css('display', 'flex').hide().fadeIn(180);
			});

			$('#cem-save-btn').on('click', function() {
				if (editingCartIdx === null || !cart[editingCartIdx]) return;
				const item = cart[editingCartIdx];
				const base = parseFloat(item.basePrice ?? item.unitPrice) || 0;
				let newExtras = [], extraCost = 0;

				$('#cem-extras-list .cem-chk:checked').each(function() {
					newExtras.push($(this).data('name'));
					extraCost += parseFloat($(this).data('price')) || 0;
				});

				item.basePrice = base;
				item.extras = newExtras;
				item.unitPrice = base + extraCost;
				item.key = item.id + '_' + newExtras.slice().sort().join('_');

				updateCartUI();
				$('#nrt-cart-extras-modal').fadeOut(150);
				showToast('Updated add-ons for ' + item.name, 'success');
			});

			$('#cem-cancel-btn').on('click', function() {
				$('#nrt-cart-extras-modal').fadeOut(150);
			});

			$('#nrt-cart-extras-modal').on('click', function(e) { if (e.target === this) $(this).fadeOut(150); });

			updateCartUI();

			$('#nrt-clear-cart').on('click', function() {
				if (confirm(i18n.confirmClear)) { cart = []; updateCartUI(); }
			});

			$(document).on('click', '.nrt-inc', function() { cart[$(this).data('idx')].qty++; updateCartUI(); });
			$(document).on('click', '.nrt-dec', function() {
				const idx = $(this).data('idx');
				if (cart[idx].qty > 1) cart[idx].qty--; else cart.splice(idx, 1);
				updateCartUI();
			});

			$('.nrt-note-chip').on('click', function() {
				const chip = $(this).data('note');
				const curNote = $('#nrt-note').val().trim();
				if (curNote.indexOf(chip) === -1) {
					$('#nrt-note').val(curNote ? (curNote + ', ' + chip) : chip);
				}
			});

			$('#nrt-submit-order').on('click', function() {
				const tableNo = $('#nrt-selected-table').val();
				if (!tableNo) return showToast(i18n.tableMissing, 'warn');
				if (cart.length === 0) return showToast(i18n.emptyCartWarn, 'warn');

				const submittedCart = JSON.parse(JSON.stringify(cart));
				const subStr = $('#nrt-sub').text();
				const taxStr = $('#nrt-tax').text() || '0.00';
				const totStr = $('#nrt-total').text();
				const noteStr = $('#nrt-note').val().trim();

				const $btn = $(this);
				$btn.prop('disabled', true).text(i18n.sendingKitchen);

				$.post(ajaxUrl, {
					action: 'nrtok_place_order',
					table_no: tableNo,
					items: submittedCart,
					subtotal: subStr,
					tax: taxStr,
					total: totStr,
					note: noteStr
				}, function(res) {
					if (res.success) {
						const orderId = (res.data && res.data.id) ? res.data.id : '';
						let totalItemsCount = 0, confHtml = '';

						submittedCart.forEach(item => {
							const qty = parseInt(item.qty) || 1;
							const unitPrice = parseFloat(item.unitPrice) || 0;
							const lineTotal = (unitPrice * qty).toFixed(2);
							totalItemsCount += qty;

							let exBreakdownHtml = '';
							if (item.extras && item.extras.length > 0) {
								exBreakdownHtml += '<div class="nrt-conf-ex-breakdown">';
								item.extras.forEach(exName => {
									const foundEx = allExtrasCatalog.find(e => e.name === exName);
									const exPriceText = foundEx ? `+${cur}${parseFloat(foundEx.price).toFixed(2)}` : '';
									exBreakdownHtml += `<div class="nrt-conf-ex-row"><span>+ ${exName}</span><span class="nrt-conf-mono">${exPriceText}</span></div>`;
								});
								exBreakdownHtml += '</div>';
							}

							confHtml += `
								<div class="nrt-conf-item-block">
									<div class="nrt-conf-row">
										<div>
											<strong class="nrt-conf-item-title">${qty}× ${item.name}</strong>
											<div class="nrt-conf-sub-line">${cur}${unitPrice.toFixed(2)} each</div>
										</div>
										<strong class="nrt-conf-mono nrt-conf-item-amt">${cur}${lineTotal}</strong>
									</div>
									${exBreakdownHtml}
								</div>`;
						});

						$('#conf-order-id').text(orderId ? ('Ticket #' + orderId) : 'Order Summary');
						$('#conf-item-count').text(totalItemsCount + ' Item(s)');
						$('#conf-items-list').html(confHtml);
						$('#conf-sub').text(cur + subStr);
						$('#conf-total').text(cur + totStr);

						if (taxRate > 0) { $('#conf-tax').text(cur + taxStr); $('#conf-tax-row').show(); }
						else { $('#conf-tax-row').hide(); }

						if (noteStr) { $('#conf-note-box').text('Chef Note: ' + noteStr).show(); }
						else { $('#conf-note-box').hide(); }

						cart = [];
						updateCartUI();
						$('#nrt-note').val('');
						toggleTrayPanel(false);
						playSuccessChime();
						$('#nrt-success-modal').css('display', 'flex').hide().fadeIn(200);
						pollTableOrderStatus();
					} else {
						showToast(i18n.orderErr, 'warn');
					}
					$btn.prop('disabled', false).html(sendSvg + '<span>' + i18n.sendOrderBtn + '</span>');
				});
			});
		});
	})(jQuery);
	</script>
	<?php
	return ob_get_clean();
}