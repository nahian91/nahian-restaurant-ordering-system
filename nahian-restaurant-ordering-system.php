<?php
/**
 * Plugin Name:       Nahian Restaurant Table Order & Kitchen POS
 * Plugin URI:        https://nahian.dev/plugins/nahian-restaurant-ordering-system
 * Description:       Modular Restaurant POS with Separate Admin Panel, Counter Terminal, Live KDS, Customer Tablet UI, Call Waiter & Live Order Status.
 * Version:           1.0.0
 * Author:            Abdullah Nahian
 * Author URI:        https://nahian.dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nahian-restaurant-ordering-system
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package NRTOK_POS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NRTOK_VERSION', '1.2.0' );
define( 'NRTOK_FILE', __FILE__ );
define( 'NRTOK_PATH', plugin_dir_path( __FILE__ ) );
define( 'NRTOK_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin textdomain for translations.
 *
 * @return void
 */
function nrtok_load_textdomain() {
	load_plugin_textdomain(
		'nahian-restaurant-ordering-system',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'nrtok_load_textdomain' );

/**
 * Safely include Core, Pro Operations, Admin, Frontend & AJAX files.
 */
$nrtok_includes = array(
	'includes/database.php',
	'includes/ajax.php',
	'includes/pro-operations.php',
	'admin/admin-panel.php',
	'frontend/customer-tablet.php',
);

foreach ( $nrtok_includes as $nrtok_file ) {
	$nrtok_filepath = NRTOK_PATH . $nrtok_file;
	if ( is_readable( $nrtok_filepath ) ) {
		require_once $nrtok_filepath;
	}
}

/**
 * Register POS staff role and grant manage_nrtok_pos capability to administrators.
 *
 * @return void
 */
function nrtok_setup_roles_and_caps() {
	$admin_role = get_role( 'administrator' );
	if ( $admin_role && ! $admin_role->has_cap( 'manage_nrtok_pos' ) ) {
		$admin_role->add_cap( 'manage_nrtok_pos' );
	}

	if ( null === get_role( 'nrtok_pos_staff' ) ) {
		add_role(
			'nrtok_pos_staff',
			__( 'Restaurant POS Staff', 'nahian-restaurant-ordering-system' ),
			array(
				'read'             => true,
				'manage_nrtok_pos' => true,
			)
		);
	}
}

/**
 * Plugin activation routine: install database tables and register POS staff role/capabilities.
 *
 * @return void
 */
function nrtok_activate_plugin() {
	if ( function_exists( 'nrtok_install_db' ) ) {
		nrtok_install_db();
	}
	nrtok_setup_roles_and_caps();
	update_option( 'nrtok_db_version', NRTOK_VERSION );
	update_option( 'nrtok_caps_version', NRTOK_VERSION );
}
register_activation_hook( __FILE__, 'nrtok_activate_plugin' );

/**
 * Ensure database schema and capabilities stay updated across plugin upgrades.
 *
 * @return void
 */
function nrtok_maybe_upgrade_plugin() {
	if ( get_option( 'nrtok_caps_version' ) !== NRTOK_VERSION ) {
		nrtok_setup_roles_and_caps();
		update_option( 'nrtok_caps_version', NRTOK_VERSION );
	}

	if ( get_option( 'nrtok_db_version' ) !== NRTOK_VERSION && function_exists( 'nrtok_install_db' ) ) {
		nrtok_install_db();
		update_option( 'nrtok_db_version', NRTOK_VERSION );
	}
}
add_action( 'admin_init', 'nrtok_maybe_upgrade_plugin' );

if ( ! function_exists( 'nrtok_get_settings' ) ) {
	/**
	 * Helper function to get plugin settings with robust defaults.
	 *
	 * @return array<string, mixed>
	 */
	function nrtok_get_settings() {
		$defaults = array(
			'restaurant_name'  => 'NAHIAN',
			'phone'            => '',
			'email'            => '',
			'address'          => '',
			'currency'         => '৳',
			'tax_percent'      => 5,
			'service_charge'   => 0,
			'menu_page_url'    => home_url( '/menu/' ),
			'order_prefix'     => 'ORD-',
			'receipt_footer'   => __( 'Thank you for dining with us!', 'nahian-restaurant-ordering-system' ),
			'sound_alerts'     => 1,
			'poll_interval_ms' => 5000,
		);

		$saved = get_option( 'nrtok_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, $defaults );
	}
}

if ( ! function_exists( 'nrtok_current_user_can_pos' ) ) {
	/**
	 * Helper to check whether the current user can access POS operations.
	 *
	 * @return bool
	 */
	function nrtok_current_user_can_pos() {
		return current_user_can( 'manage_nrtok_pos' ) || current_user_can( 'manage_options' );
	}
}

/**
 * Hide the WordPress frontend Admin Bar for POS Staff so tablet/kiosk views stay full-screen.
 *
 * @param bool $show Whether to show the admin bar.
 * @return bool
 */
function nrtok_hide_admin_bar_for_staff( $show ) {
	if ( current_user_can( 'nrtok_pos_staff' ) && ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	return $show;
}
add_filter( 'show_admin_bar', 'nrtok_hide_admin_bar_for_staff' );

/**
 * Auto-redirect POS Staff and Administrators to Nahian POS dashboard after login.
 *
 * @param string           $redirect_to The redirect destination URL.
 * @param string           $request     The requested redirect destination URL passed as a parameter.
 * @param WP_User|WP_Error $user        WP_User object if login was successful, WP_Error object otherwise.
 * @return string
 */
function nrtok_redirect_to_pos_dashboard( $redirect_to, $request, $user ) {
	if ( $user instanceof WP_User ) {
		if ( user_can( $user, 'manage_nrtok_pos' ) || user_can( $user, 'manage_options' ) ) {
			return admin_url( 'admin.php?page=nahian-restaurant-ordering-system&tab=dashboard' );
		}
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'nrtok_redirect_to_pos_dashboard', 10, 3 );

/**
 * Enqueue Custom WordPress Login Page Styling using WordPress Standards.
 *
 * @return void
 */
function nrtok_custom_login_styles() {
	wp_register_style( 'nrtok-login-style', false, array(), NRTOK_VERSION );
	wp_enqueue_style( 'nrtok-login-style' );

	$custom_css = '
		body.login {
			background: linear-gradient(135deg, #090d16 0%, #0f172a 50%, #1e1b4b 100%);
			font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			min-height: 100dvh;
			display: flex;
			align-items: center;
			justify-content: center;
			position: relative;
			overflow-x: hidden;
		}
		body.login::before {
			content: "";
			position: absolute;
			width: 500px;
			height: 500px;
			background: radial-gradient(circle, rgba(249, 115, 22, 0.16) 0%, rgba(0, 0, 0, 0) 70%);
			top: -100px;
			left: 50%;
			transform: translateX(-50%);
			z-index: 0;
			pointer-events: none;
		}
		body.login div#login {
			width: 100%;
			max-width: 420px;
			padding: 32px 20px;
			z-index: 1;
		}
		body.login h1 a {
			display: none;
		}
		.nrtok-login-brand {
			text-align: center;
			margin-bottom: 22px;
			color: #ffffff;
		}
		.nrtok-login-brand h2 {
			margin: 0;
			font-size: 26px;
			font-weight: 900;
			letter-spacing: 1px;
			color: #ffffff;
		}
		.nrtok-login-brand span {
			display: inline-block;
			margin-top: 8px;
			padding: 5px 14px;
			border-radius: 999px;
			background: rgba(249, 115, 22, 0.18);
			border: 1px solid rgba(249, 115, 22, 0.35);
			color: #fb923c;
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.8px;
		}
		body.login form#loginform {
			background: rgba(255, 255, 255, 0.98);
			backdrop-filter: blur(20px);
			-webkit-backdrop-filter: blur(20px);
			border-radius: 24px;
			padding: 36px 32px 32px;
			box-shadow: 0 30px 60px -12px rgba(15, 23, 42, 0.5);
			border: 1px solid rgba(255, 255, 255, 0.85);
			margin-top: 0;
		}
		body.login form#loginform label {
			font-size: 12px;
			font-weight: 800;
			color: #334155;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 7px;
			display: block;
		}
		body.login form#loginform input.input {
			width: 100%;
			height: 48px;
			padding: 12px 16px;
			border: 1.5px solid #cbd5e1;
			border-radius: 12px;
			font-size: 15px;
			font-weight: 600;
			color: #0f172a;
			background: #f8fafc;
			box-shadow: none;
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		}
		body.login form#loginform input.input:focus {
			background: #ffffff;
			border-color: #f97316;
			box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.14);
		}
		body.login .wp-pwd {
			position: relative;
		}
		body.login button.wp-hide-pw {
			top: 4px;
			right: 6px;
			color: #64748b;
		}
		body.login button.wp-hide-pw:hover {
			color: #f97316;
		}
		body.login .submit input#wp-submit {
			width: 100%;
			height: 50px;
			margin-top: 14px;
			border: none;
			border-radius: 12px;
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
			color: #ffffff;
			font-size: 15px;
			font-weight: 800;
			letter-spacing: 0.3px;
			cursor: pointer;
			box-shadow: 0 10px 22px rgba(249, 115, 22, 0.35);
			text-shadow: none;
			float: none;
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		}
		body.login .submit input#wp-submit:hover {
			opacity: 0.96;
			transform: translateY(-1.5px);
			box-shadow: 0 14px 28px rgba(249, 115, 22, 0.45);
		}
		body.login p#nav,
		body.login p.backtohome {
			text-align: center;
			padding: 0;
			margin-top: 18px;
		}
		body.login p#nav a,
		body.login p.backtohome a {
			color: #94a3b8;
			font-weight: 700;
			font-size: 13px;
			text-decoration: none;
			transition: color 0.2s ease;
		}
		body.login p#nav a:hover,
		body.login p.backtohome a:hover {
			color: #f97316;
		}
		.nrtok-login-menu-link {
			display: block;
			text-align: center;
			margin-top: 16px;
			padding: 12px 16px;
			border-radius: 12px;
			background: rgba(255, 255, 255, 0.08);
			border: 1px solid rgba(255, 255, 255, 0.14);
			color: #f8fafc;
			font-size: 13px;
			font-weight: 700;
			text-decoration: none;
			transition: all 0.2s ease;
		}
		.nrtok-login-menu-link:hover {
			background: rgba(249, 115, 22, 0.18);
			border-color: rgba(249, 115, 22, 0.4);
			color: #ffffff;
		}
		body.login .forgetmenot {
			margin-top: 12px;
			font-weight: 600;
			font-size: 13px;
			color: #64748b;
		}
		body.login .forgetmenot input[type="checkbox"] {
			border-radius: 4px;
			border-color: #cbd5e1;
			accent-color: #f97316;
			width: 16px;
			height: 16px;
			margin-right: 6px;
		}
		.login .message,
		.login input.error,
		.login .success {
			border-radius: 12px;
			border-left-width: 4px;
		}
	';

	wp_add_inline_style( 'nrtok-login-style', $custom_css );
}
add_action( 'login_enqueue_scripts', 'nrtok_custom_login_styles' );

/**
 * Display clean restaurant title above the login form.
 *
 * @param string $message Existing login message HTML.
 * @return string
 */
function nrtok_custom_login_brand_header( $message ) {
	$s      = nrtok_get_settings();
	$brand  = '<div class="nrtok-login-brand">';
	$brand .= '<h2>' . esc_html( $s['restaurant_name'] ) . '</h2>';
	$brand .= '<span>' . esc_html__( 'Table Order & Kitchen POS', 'nahian-restaurant-ordering-system' ) . '</span>';
	$brand .= '</div>';

	return $brand . $message;
}
add_filter( 'login_message', 'nrtok_custom_login_brand_header' );

/**
 * Add quick link to Customer Menu / Tablet View below the login form.
 *
 * @return void
 */
function nrtok_custom_login_menu_shortcut() {
	$s = nrtok_get_settings();
	if ( ! empty( $s['menu_page_url'] ) ) {
		echo '<a class="nrtok-login-menu-link" href="' . esc_url( $s['menu_page_url'] ) . '">';
		echo esc_html__( 'Open Customer Table Menu →', 'nahian-restaurant-ordering-system' );
		echo '</a>';
	}
}
add_action( 'login_footer', 'nrtok_custom_login_menu_shortcut' );

/**
 * Customize login logo link title.
 *
 * @return string
 */
function nrtok_custom_login_headertext() {
	$s = nrtok_get_settings();
	/* translators: %s: Restaurant name */
	return sprintf( __( '%s POS Portal', 'nahian-restaurant-ordering-system' ), $s['restaurant_name'] );
}
add_filter( 'login_headertext', 'nrtok_custom_login_headertext' );

/**
 * Customize login logo link URL.
 *
 * @return string
 */
function nrtok_custom_login_headerurl() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'nrtok_custom_login_headerurl' );