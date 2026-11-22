<?php
/**
 * Nahian POS Database Setup & Management
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NRTOK_DB_VERSION', '1.1.0' );

/**
 * Install & Upgrade POS Database Tables.
 *
 * @return void
 */
function nrtok_install_db() {
	global $wpdb;
	$charset = $wpdb->get_charset_collate();

	$t_menu   = $wpdb->prefix . 'nrtok_menu';
	$t_cats   = $wpdb->prefix . 'nrtok_categories';
	$t_extras = $wpdb->prefix . 'nrtok_extras';
	$t_orders = $wpdb->prefix . 'nrtok_orders';
	$t_staff  = $wpdb->prefix . 'nrtok_staff';
	$t_tables = $wpdb->prefix . 'nrtok_tables';
	$t_calls  = $wpdb->prefix . 'nrtok_waiter_calls';

	$sql1 = "CREATE TABLE `{$t_menu}` (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(150) NOT NULL,
		category varchar(100) NOT NULL,
		price decimal(10,2) NOT NULL DEFAULT 0.00,
		ratio varchar(50) DEFAULT '',
		item_order int(5) NOT NULL DEFAULT 0,
		description text,
		image_url text,
		extras text,
		stock_qty int(9) DEFAULT NULL,
		is_available tinyint(1) NOT NULL DEFAULT 1,
		PRIMARY KEY (id),
		KEY category (category)
	) {$charset};";

	$sql2 = "CREATE TABLE `{$t_cats}` (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		category_order int(5) NOT NULL DEFAULT 0,
		image text,
		PRIMARY KEY (id)
	) {$charset};";

	$sql3 = "CREATE TABLE `{$t_extras}` (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		price decimal(10,2) NOT NULL DEFAULT 0.00,
		image text,
		PRIMARY KEY (id)
	) {$charset};";

	$sql4 = "CREATE TABLE `{$t_orders}` (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		order_type varchar(20) NOT NULL DEFAULT 'dine_in',
		table_no varchar(30) NOT NULL,
		customer_name varchar(100) DEFAULT '',
		customer_phone varchar(30) DEFAULT '',
		items longtext NOT NULL,
		subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
		discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
		tax_amount decimal(10,2) NOT NULL DEFAULT 0.00,
		total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
		payment_method varchar(30) NOT NULL DEFAULT 'Cash',
		paid_amount decimal(10,2) NOT NULL DEFAULT 0.00,
		note text,
		status varchar(20) NOT NULL DEFAULT 'pending',
		is_printed tinyint(1) NOT NULL DEFAULT 0,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY order_type (order_type),
		KEY table_no (table_no),
		KEY status (status),
		KEY created_at (created_at)
	) {$charset};";

	$sql5 = "CREATE TABLE `{$t_staff}` (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		role varchar(50) NOT NULL,
		email varchar(150) NOT NULL,
		password varchar(255) NOT NULL,
		phone varchar(30),
		status varchar(20) NOT NULL DEFAULT 'Active',
		permissions longtext,
		PRIMARY KEY (id),
		KEY email (email)
	) {$charset};";

	$sql6 = "CREATE TABLE `{$t_tables}` (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		table_name varchar(50) NOT NULL,
		seats int(5) NOT NULL DEFAULT 4,
		table_login varchar(150) DEFAULT '',
		table_password varchar(150) DEFAULT '',
		PRIMARY KEY (id)
	) {$charset};";

	$sql7 = "CREATE TABLE `{$t_calls}` (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		table_no varchar(30) NOT NULL,
		status varchar(20) NOT NULL DEFAULT 'active',
		dismissed_at datetime DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY status (status)
	) {$charset};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql1 );
	dbDelta( $sql2 );
	dbDelta( $sql3 );
	dbDelta( $sql4 );
	dbDelta( $sql5 );
	dbDelta( $sql6 );
	dbDelta( $sql7 );

	// Initialize base plugin settings with global standard defaults if not already configured.
	if ( ! get_option( 'nrtok_settings' ) ) {
		$default_site_name = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Nahian POS';
		update_option(
			'nrtok_settings',
			array(
				'restaurant_name'  => $default_site_name,
				'currency'         => '$',
				'currency_pos'     => 'left',
				'decimals'         => 2,
				'tax_percent'      => 0,
				'tax_mode'         => 'exclusive',
				'service_charge'   => 0,
				'paper_width'      => '80mm',
				'payment_methods'  => array( 'cash', 'card' ),
				'menu_page_url'    => home_url( '/menu/' ),
				'order_prefix'     => 'ORD-',
				'receipt_footer'   => __( 'Thank you for dining with us!', 'nahian-restaurant-ordering-system' ),
				'sound_alerts'     => 1,
				'poll_interval_ms' => 4000,
			)
		);
	}

	update_option( 'nrtok_db_version', NRTOK_DB_VERSION );
}

/**
 * Auto-run DB check if plugin version updates without reactivation.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( get_option( 'nrtok_db_version' ) !== NRTOK_DB_VERSION ) {
			nrtok_install_db();
		}
	}
);

if ( ! function_exists( 'nrtok_get_settings' ) ) {
	/**
	 * Retrieve POS Settings with Safe Global Defaults.
	 *
	 * @return array<string, mixed> Settings array.
	 */
	function nrtok_get_settings() {
		$default_site_name = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Nahian POS';
		return wp_parse_args(
			get_option( 'nrtok_settings', array() ),
			array(
				'restaurant_name'  => $default_site_name,
				'currency'         => '$',
				'currency_pos'     => 'left',
				'decimals'         => 2,
				'tax_percent'      => 0,
				'tax_mode'         => 'exclusive',
				'service_charge'   => 0,
				'paper_width'      => '80mm',
				'payment_methods'  => array( 'cash', 'card' ),
				'menu_page_url'    => home_url( '/menu/' ),
				'order_prefix'     => 'ORD-',
				'receipt_footer'   => __( 'Thank you for dining with us!', 'nahian-restaurant-ordering-system' ),
				'sound_alerts'     => 1,
				'poll_interval_ms' => 4000,
			)
		);
	}
}