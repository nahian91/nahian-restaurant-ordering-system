<?php
/**
 * Nahian POS & Receipt Settings View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render POS & Receipt Settings View.
 *
 * @param array<string, mixed> $s Plugin settings array.
 * @return void
 */
function nrtok_view_settings( $s = array() ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	if ( function_exists( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}

	if ( empty( $s ) && function_exists( 'nrtok_get_settings' ) ) {
		$s = nrtok_get_settings();
	}

	$default_site_name = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Nahian POS';

	// Reusable SVG Icons.
	$svg_check    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
	$svg_settings = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>';
	$svg_store    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2v0a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12v0a2 2 0 0 1-2-2V7"/></svg>';
	$svg_pin      = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
	$svg_shield   = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
	$svg_coins    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/></svg>';
	$svg_msg      = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
	$svg_hash     = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>';
	$svg_phone    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
	$svg_email    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>';
	$svg_receipt  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>';
	$svg_print    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>';
	$svg_copy     = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>';
	$svg_image    = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
	$svg_upload   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>';
	$svg_trash    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
	$svg_bell     = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
	$svg_volume   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>';
	$svg_clock    = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
	$svg_wifi     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>';
	$svg_tablet   = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>';
	$svg_link     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
	$svg_card     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>';
	$svg_banknote = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>';
	$svg_wallet   = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>';

	if ( isset( $_POST['nrtok_save_settings'] ) ) {
		check_admin_referer( 'nrtok_save_settings_action' );

		$tax_input     = max( 0.0, min( 100.0, (float) ( $_POST['tax_percent'] ?? 0 ) ) );
		$service_input = max( 0.0, min( 100.0, (float) ( $_POST['service_charge'] ?? 0 ) ) );
		$tax_mode      = isset( $_POST['tax_mode'] ) && 'inclusive' === sanitize_key( wp_unslash( $_POST['tax_mode'] ) ) ? 'inclusive' : 'exclusive';
		$paper_width   = isset( $_POST['paper_width'] ) && '58mm' === sanitize_key( wp_unslash( $_POST['paper_width'] ) ) ? '58mm' : '80mm';

		$poll_ms = absint( $_POST['poll_interval_ms'] ?? 4000 );
		if ( ! in_array( $poll_ms, array( 3000, 4000, 6000, 10000 ), true ) ) {
			$poll_ms = 4000;
		}

		$allowed_methods = array( 'cash', 'card', 'wallet' );
		$posted_methods  = isset( $_POST['payment_methods'] ) && is_array( $_POST['payment_methods'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['payment_methods'] ) ) : array( 'cash' );
		$payment_methods = array_values( array_intersect( $posted_methods, $allowed_methods ) );
		if ( empty( $payment_methods ) ) {
			$payment_methods = array( 'cash' );
		}

		$existing_settings = get_option( 'nrtok_settings', array() );
		if ( ! is_array( $existing_settings ) ) {
			$existing_settings = array();
		}

		$menu_url_raw = esc_url_raw( wp_unslash( $_POST['menu_page_url'] ?? home_url( '/menu/' ) ) );
		if ( empty( $menu_url_raw ) ) {
			$menu_url_raw = home_url( '/menu/' );
		}

		$updated_settings = wp_parse_args(
			array(
				'restaurant_name'  => sanitize_text_field( wp_unslash( $_POST['restaurant_name'] ?? $default_site_name ) ),
				'restaurant_logo'  => esc_url_raw( wp_unslash( $_POST['restaurant_logo'] ?? '' ) ),
				'address'          => sanitize_text_field( wp_unslash( $_POST['address'] ?? '' ) ),
				'bin_number'       => sanitize_text_field( wp_unslash( $_POST['bin_number'] ?? '' ) ),
				'phone'            => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
				'email'            => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
				'currency'         => sanitize_text_field( wp_unslash( $_POST['currency'] ?? '$' ) ),
				'tax_percent'      => $tax_input,
				'tax_mode'         => $tax_mode,
				'service_charge'   => $service_input,
				'paper_width'      => $paper_width,
				'order_prefix'     => sanitize_text_field( wp_unslash( $_POST['order_prefix'] ?? 'ORD-' ) ),
				'receipt_footer'   => sanitize_text_field( wp_unslash( $_POST['receipt_footer'] ?? '' ) ),
				'wifi_ssid'        => sanitize_text_field( wp_unslash( $_POST['wifi_ssid'] ?? '' ) ),
				'wifi_password'    => sanitize_text_field( wp_unslash( $_POST['wifi_password'] ?? '' ) ),
				'menu_page_url'    => $menu_url_raw,
				'payment_methods'  => $payment_methods,
				'enable_ordering'  => isset( $_POST['enable_ordering'] ) ? 1 : 0,
				'enable_waiter'    => isset( $_POST['enable_waiter'] ) ? 1 : 0,
				'sound_alerts'     => isset( $_POST['sound_alerts'] ) ? 1 : 0,
				'poll_interval_ms' => $poll_ms,
			),
			$existing_settings
		);

		update_option( 'nrtok_settings', $updated_settings );

		echo '<div class="nrtok-alert nrtok-set-alert-flex">' . $svg_check . '<span>' . esc_html__( 'POS & Receipt settings saved successfully!', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	if ( function_exists( 'nrtok_get_settings' ) ) {
		$s = nrtok_get_settings();
	}

	$tax_rate        = (float) ( $s['tax_percent'] ?? 0 );
	$tax_mode        = $s['tax_mode'] ?? 'exclusive';
	$service_rate    = (float) ( $s['service_charge'] ?? 0 );
	$paper_width     = $s['paper_width'] ?? '80mm';
	$mock_sub        = 1050.00;
	$service_amt     = ( $mock_sub * $service_rate ) / 100;
	$tax_amt         = 'inclusive' === $tax_mode
		? ( $tax_rate > 0 ? ( $mock_sub - ( $mock_sub / ( 1 + ( $tax_rate / 100 ) ) ) ) : 0.0 )
		: ( ( $mock_sub * $tax_rate ) / 100 );
	$tot_bill        = 'inclusive' === $tax_mode ? ( $mock_sub + $service_amt ) : ( $mock_sub + $service_amt + $tax_amt );
	$ord_prefix      = $s['order_prefix'] ?? 'ORD-';
	$rest_address    = $s['address'] ?? '';
	$rest_bin        = $s['bin_number'] ?? '';
	$rest_phone      = $s['phone'] ?? '';
	$rest_email      = $s['email'] ?? '';
	$rest_logo       = $s['restaurant_logo'] ?? '';
	$wifi_ssid       = $s['wifi_ssid'] ?? '';
	$wifi_pass       = $s['wifi_password'] ?? '';
	$menu_page_url   = $s['menu_page_url'] ?? home_url( '/menu/' );
	$payment_methods = isset( $s['payment_methods'] ) && is_array( $s['payment_methods'] ) ? $s['payment_methods'] : array( 'cash', 'card' );
	$enable_ordering = isset( $s['enable_ordering'] ) ? (int) $s['enable_ordering'] : 1;
	$enable_waiter   = isset( $s['enable_waiter'] ) ? (int) $s['enable_waiter'] : 1;
	$sound_alerts    = isset( $s['sound_alerts'] ) ? (int) $s['sound_alerts'] : 1;
	$poll_ms         = isset( $s['poll_interval_ms'] ) ? (int) $s['poll_interval_ms'] : 4000;
	$currency        = html_entity_decode( $s['currency'] ?? '$', ENT_QUOTES, 'UTF-8' );

	$currencies = array(
		'$'   => __( '$ — US Dollar (USD)', 'nahian-restaurant-ordering-system' ),
		'€'   => __( '€ — Euro (EUR)', 'nahian-restaurant-ordering-system' ),
		'£'   => __( '£ — British Pound (GBP)', 'nahian-restaurant-ordering-system' ),
		'C$'  => __( 'C$ — Canadian Dollar (CAD)', 'nahian-restaurant-ordering-system' ),
		'A$'  => __( 'A$ — Australian Dollar (AUD)', 'nahian-restaurant-ordering-system' ),
		'¥'   => __( '¥ — Japanese Yen / Chinese Yuan (JPY/CNY)', 'nahian-restaurant-ordering-system' ),
		'CHF' => __( 'CHF — Swiss Franc', 'nahian-restaurant-ordering-system' ),
		'AED' => __( 'AED — UAE Dirham', 'nahian-restaurant-ordering-system' ),
		'SAR' => __( 'SAR — Saudi Riyal', 'nahian-restaurant-ordering-system' ),
		'QAR' => __( 'QAR — Qatari Riyal', 'nahian-restaurant-ordering-system' ),
		'OMR' => __( 'OMR — Omani Rial', 'nahian-restaurant-ordering-system' ),
		'KWD' => __( 'KWD — Kuwaiti Dinar', 'nahian-restaurant-ordering-system' ),
		'₹'   => __( '₹ — Indian Rupee (INR)', 'nahian-restaurant-ordering-system' ),
		'৳'   => __( '৳ — Bangladeshi Taka (BDT)', 'nahian-restaurant-ordering-system' ),
		'Rs'  => __( 'Rs — Pakistani / Sri Lankan Rupee', 'nahian-restaurant-ordering-system' ),
		'RM'  => __( 'RM — Malaysian Ringgit (MYR)', 'nahian-restaurant-ordering-system' ),
		'S$'  => __( 'S$ — Singapore Dollar (SGD)', 'nahian-restaurant-ordering-system' ),
		'Rp'  => __( 'Rp — Indonesian Rupiah (IDR)', 'nahian-restaurant-ordering-system' ),
		'฿'   => __( '฿ — Thai Baht (THB)', 'nahian-restaurant-ordering-system' ),
		'₩'   => __( '₩ — South Korean Won (KRW)', 'nahian-restaurant-ordering-system' ),
		'₺'   => __( '₺ — Turkish Lira (TRY)', 'nahian-restaurant-ordering-system' ),
	);

	if ( ! empty( $currency ) && ! isset( $currencies[ $currency ] ) ) {
		$currencies[ $currency ] = $currency . ' — ' . __( 'Custom Currency', 'nahian-restaurant-ordering-system' );
	}

	$available_payments = array(
		'cash'   => array(
			'label' => __( 'Cash', 'nahian-restaurant-ordering-system' ),
			'icon'  => $svg_banknote,
		),
		'card'   => array(
			'label' => __( 'Card / POS', 'nahian-restaurant-ordering-system' ),
			'icon'  => $svg_card,
		),
		'wallet' => array(
			'label' => __( 'Digital Wallet', 'nahian-restaurant-ordering-system' ),
			'icon'  => $svg_wallet,
		),
	);
	?>

	<style>
		.nrtok-set-alert-flex { display: flex; align-items: center; gap: 8px; }
		.nrtok-set-full-wrap { width: 100%; box-sizing: border-box; }
		.nrtok-full-card { width: 100%; max-width: 100%; box-sizing: border-box; }
		.nrtok-set-card-no-margin { margin: 0; }
		.nrtok-set-preview-card { margin: 0; background: #f8fafc; position: sticky; top: 24px; }

		.nrtok-form-header { border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 22px; }
		.nrtok-form-header h3 {
			margin: 0; font-size: 18px; font-weight: 850; color: #0f172a;
			display: flex; align-items: center; gap: 9px; letter-spacing: -0.3px;
		}
		.nrtok-form-header p { margin: 5px 0 0; font-size: 13px; color: #64748b; }
		.nrtok-set-title-ico {
			width: 32px; height: 32px; border-radius: 9px; background: #fff7ed; color: #f97316;
			border: 1px solid #ffedd5; display: inline-flex; align-items: center; justify-content: center;
		}

		.nrtok-set-sec-divider {
			font-size: 11.5px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.8px;
			color: #f97316; margin: 28px 0 16px; padding: 8px 12px; background: #fff7ed;
			border-left: 3.5px solid #f97316; border-radius: 0 10px 10px 0;
			display: flex; align-items: center; gap: 8px;
		}

		.nrtok-form-group { margin-bottom: 18px; width: 100%; }
		.nrtok-label {
			display: flex; align-items: center; justify-content: space-between;
			font-size: 13px; font-weight: 750; color: #1e293b; margin-bottom: 8px; letter-spacing: 0.15px;
		}
		.nrtok-label small { font-weight: 500; color: #64748b; font-size: 12px; }

		.nrtok-input-wrap {
			position: relative; display: flex; align-items: center; width: 100%;
			background: #ffffff; border-radius: 12px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
			border: 1.5px solid #e2e8f0;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
		}
		.nrtok-input-wrap:hover { border-color: #cbd5e1; box-shadow: 0 3px 6px rgba(15, 23, 42, 0.04); }
		.nrtok-input-wrap:focus-within {
			border-color: #f97316; background-color: #fff;
			box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12), 0 2px 4px rgba(0, 0, 0, 0.04);
		}

		.nrtok-input-prefix {
			position: absolute; left: 14px; font-weight: 700; color: #94a3b8; font-size: 14px;
			pointer-events: none; display: flex; align-items: center; justify-content: center;
			transition: color 0.2s ease;
		}
		.nrtok-input-wrap:focus-within .nrtok-input-prefix { color: #f97316; }

		.nrtok-input {
			width: 100%; padding: 12px 16px; border: none !important; border-radius: 12px;
			font-size: 14px; font-weight: 500; color: #0f172a; background: transparent !important;
			box-shadow: none !important; outline: none !important; box-sizing: border-box;
		}
		.nrtok-input.has-prefix { padding-left: 44px; }
		.nrtok-input::placeholder { color: #94a3b8; opacity: 1; font-weight: 400; }

		select.nrtok-input {
			display: block; width: 100% !important; max-width: 100% !important;
			height: auto !important; line-height: 1.5 !important;
			appearance: none; -webkit-appearance: none; padding-right: 40px; cursor: pointer;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
			background-repeat: no-repeat !important; background-position: right 14px center !important; background-size: 16px !important;
		}

		/* Restaurant Logo Media Box */
		.nrtok-logo-upload-card {
			display: flex; align-items: center; gap: 16px; padding: 14px 16px;
			background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 14px;
			transition: all 0.2s ease;
		}
		.nrtok-logo-upload-card:hover { border-color: #f97316; background: #fffbf7; }
		.nrtok-logo-preview-box {
			width: 64px; height: 64px; border-radius: 12px; background: #ffffff;
			border: 1.5px solid #e2e8f0; display: flex; align-items: center; justify-content: center;
			overflow: hidden; flex-shrink: 0; color: #94a3b8; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
		}
		.nrtok-logo-preview-box img { width: 100%; height: 100%; object-fit: contain; display: block; }
		.nrtok-logo-actions { display: flex; flex-direction: column; gap: 7px; }
		.nrtok-logo-btn-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
		.nrtok-logo-hint { font-size: 11.5px; color: #64748b; }
		.nrtok-btn-remove-logo {
			background: #fef2f2; color: #dc2626; border: 1.5px solid #fecaca;
			padding: 8px 13px; border-radius: 10px; font-size: 12.5px; font-weight: 700;
			cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: 0.15s ease;
		}
		.nrtok-btn-remove-logo:hover { background: #fee2e2; }
		.nrtok-hidden { display: none !important; }

		/* Modern Toggle Switch Component */
		.nrtok-switch {
			position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0;
		}
		.nrtok-switch input {
			opacity: 0; width: 0; height: 0; position: absolute; margin: 0;
		}
		.nrtok-switch-slider {
			position: absolute; cursor: pointer; inset: 0;
			background-color: #cbd5e1; border-radius: 999px;
			transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.12);
		}
		.nrtok-switch-slider::before {
			content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px;
			background-color: #ffffff; border-radius: 50%;
			transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			box-shadow: 0 2px 5px rgba(15, 23, 42, 0.22);
		}
		.nrtok-switch input:checked + .nrtok-switch-slider {
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
			box-shadow: 0 2px 8px rgba(249, 115, 22, 0.35);
		}
		.nrtok-switch input:checked + .nrtok-switch-slider::before {
			transform: translateX(20px);
		}
		.nrtok-switch input:focus-visible + .nrtok-switch-slider {
			box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.25);
		}

		/* Payment Methods Switch Grid */
		.nrtok-pay-switch-grid {
			display: grid; grid-template-columns: repeat(auto-fill, minmax(175px, 1fr)); gap: 10px;
		}
		.nrtok-pay-switch-card {
			display: flex; align-items: center; justify-content: space-between; gap: 10px;
			padding: 11px 14px; background: #f8fafc; border: 1.5px solid #e2e8f0;
			border-radius: 13px; cursor: pointer; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
			user-select: none;
		}
		.nrtok-pay-switch-card:hover {
			border-color: #cbd5e1; background: #ffffff; transform: translateY(-1px);
			box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
		}
		.nrtok-pay-switch-card.is-active {
			background: #fff7ed; border-color: #fdba74;
			box-shadow: 0 3px 10px rgba(249, 115, 22, 0.08);
		}
		.nrtok-pay-switch-left {
			display: flex; align-items: center; gap: 9px; font-size: 13px; font-weight: 750; color: #334155;
		}
		.nrtok-pay-switch-card.is-active .nrtok-pay-switch-left { color: #9a3412; }
		.nrtok-pay-ico-badge {
			width: 28px; height: 28px; border-radius: 8px; background: #ffffff;
			border: 1px solid #e2e8f0; color: #64748b; display: inline-flex;
			align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s;
		}
		.nrtok-pay-switch-card.is-active .nrtok-pay-ico-badge {
			background: #f97316; border-color: #f97316; color: #ffffff;
		}

		/* Feature Toggle Switch Cards */
		.nrtok-set-switch-stack { display: flex; flex-direction: column; gap: 11px; }
		.nrtok-set-switch-card {
			display: flex; align-items: center; justify-content: space-between; gap: 16px;
			padding: 14px 16px; background: #f8fafc; border: 1.5px solid #e2e8f0;
			border-radius: 14px; cursor: pointer; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
			user-select: none;
		}
		.nrtok-set-switch-card:hover {
			border-color: #cbd5e1; background: #ffffff;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
		}
		.nrtok-set-switch-card.is-active {
			background: #ffffff; border-color: #fed7aa;
			box-shadow: 0 4px 14px rgba(249, 115, 22, 0.07);
		}
		.nrtok-set-switch-left { display: flex; align-items: center; gap: 12px; }
		.nrtok-set-switch-ico {
			width: 36px; height: 36px; border-radius: 10px; background: #f1f5f9; color: #64748b;
			display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
			transition: all 0.2s ease;
		}
		.nrtok-set-switch-card.is-active .nrtok-set-switch-ico {
			background: #fff7ed; color: #f97316; border: 1px solid #ffedd5;
		}
		.nrtok-set-switch-title { font-size: 13.5px; font-weight: 750; color: #0f172a; display: block; }
		.nrtok-set-switch-desc { font-size: 12px; color: #64748b; display: block; margin-top: 2px; }

		.nrtok-set-layout-grid { display: grid; grid-template-columns: 1.55fr 1fr; gap: 24px; align-items: start; width: 100%; }
		.nrtok-set-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; width: 100%; }
		@media (max-width: 960px) {
			.nrtok-set-layout-grid { grid-template-columns: 1fr; }
			.nrtok-set-preview-card { position: static; }
		}
		@media (max-width: 640px) { .nrtok-set-two-col { grid-template-columns: 1fr; } }

		.nrtok-set-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
		.nrtok-set-form-footer {
			margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9;
			display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
		}
		.nrtok-set-sc-box {
			display: flex; align-items: center; gap: 8px; background: #f8fafc;
			padding: 7px 12px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 12.5px;
		}
		.nrtok-set-sc-lbl { color: #64748b; }
		.nrtok-set-sc-code { font-weight: 700; color: #f97316; }
		.nrtok-set-copy-btn { padding: 5px 10px; font-size: 11.5px; border-radius: 7px; }

		/* Thermal Receipt Preview */
		.nrtok-set-preview-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
		.nrtok-set-preview-badge {
			background: #e2e8f0; color: #334155; padding: 6px 12px; border-radius: 20px;
			font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px;
			display: inline-flex; align-items: center; gap: 6px;
		}
		.nrtok-receipt-mock {
			background: #fff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 26px 22px;
			font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #111;
			box-shadow: 0 12px 30px -6px rgba(15, 23, 42, 0.1); max-width: 330px; margin: 0 auto;
			position: relative; line-height: 1.45; transition: max-width 0.25s ease;
		}
		.nrtok-receipt-mock.is-58mm { max-width: 245px; padding: 20px 14px; font-size: 11px; }
		.nrtok-receipt-mock::after {
			content: ''; display: block; height: 12px; margin-top: 18px;
			background: linear-gradient(-45deg, transparent 33.33%, #cbd5e1 33.33%, #cbd5e1 66.66%, transparent 66.66%),
						linear-gradient(45deg, transparent 33.33%, #cbd5e1 33.33%, #cbd5e1 66.66%, transparent 66.66%);
			background-size: 12px 16px;
		}
		.nrtok-rc-center { text-align: center; }
		.nrtok-rc-logo-wrap { margin-bottom: 8px; }
		.nrtok-rc-logo-wrap img { max-height: 48px; max-width: 140px; object-fit: contain; filter: grayscale(100%) contrast(120%); }
		.nrtok-rc-rest-title { margin: 0 0 3px; font-size: 18px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.8px; color: #000; }
		.nrtok-rc-invoice-sub { font-size: 10px; font-weight: 800; color: #475569; margin: 4px 0 6px; letter-spacing: 0.5px; }
		.nrtok-rc-meta-line { font-size: 11px; color: #334155; }
		.nrtok-rc-contact-line { font-size: 10px; color: #334155; }
		.nrtok-rc-date-line { font-size: 9.5px; color: #555; }
		.nrtok-rc-hr { border: none; border-top: 1px dashed #64748b; margin: 10px 0; }
		.nrtok-rc-table-hdr { display: flex; justify-content: space-between; font-weight: 800; font-size: 11px; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 6px; }
		.nrtok-rc-row { display: flex; justify-content: space-between; }
		.nrtok-rc-row-bold { font-weight: 700; }
		.nrtok-rc-row-mb { margin-bottom: 5px; }
		.nrtok-rc-row-sm { font-size: 11.5px; color: #334155; }
		.nrtok-rc-addon-row { color: #475569; padding-left: 8px; }
		.nrtok-rc-pay-row { border-top: 1px dotted #64748b; padding-top: 4px; margin-top: 4px; }
		.nrtok-rc-change-row { color: #047857; }
		.nrtok-rc-row-grand { font-size: 14.5px; font-weight: 900; margin-top: 8px; border-top: 1.5px solid #000; padding-top: 6px; }
		.nrtok-rc-wifi-box { text-align: center; font-size: 10px; font-weight: 700; color: #1e293b; background: #f8fafc; padding: 5px; border: 1px dashed #94a3b8; border-radius: 6px; margin-top: 8px; }
		.nrtok-rc-footer-txt { text-align: center; font-size: 11px; color: #334155; margin-top: 8px; font-style: italic; }
		.nrtok-required-star { color: #ef4444; font-weight: 800; margin-left: 3px; }
		@media print {
			#nrtok-print-area.is-58mm { width: 54mm !important; font-size: 11px !important; }
		}
	</style>

	<div class="nrtok-set-full-wrap">
		<div class="nrtok-header">
			<div>
				<h1><?php esc_html_e( 'POS & Receipt Settings', 'nahian-restaurant-ordering-system' ); ?></h1>
				<p><?php esc_html_e( 'Configure restaurant branding, tax/service rules, tablet controls, and thermal receipt layout.', 'nahian-restaurant-ordering-system' ); ?></p>
			</div>
		</div>

		<div class="nrtok-set-layout-grid">
			<!-- Left Column: Configuration Form -->
			<div class="nrtok-card nrtok-full-card nrtok-set-card-no-margin">
				<div class="nrtok-form-header">
					<h3>
						<span class="nrtok-set-title-ico"><?php echo $svg_settings; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php esc_html_e( 'General & Billing Configuration', 'nahian-restaurant-ordering-system' ); ?></span>
					</h3>
					<p><?php esc_html_e( 'Update your store preferences below. Changes apply instantly to tablets and printed receipts.', 'nahian-restaurant-ordering-system' ); ?></p>
				</div>

				<form method="post">
					<?php wp_nonce_field( 'nrtok_save_settings_action' ); ?>

					<!-- Section 1: Restaurant Identity & Legal Info -->
					<div class="nrtok-set-sec-divider">
						<?php echo $svg_store; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( '1. Restaurant Identity & Invoice Header', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>

					<div class="nrtok-form-group">
						<label class="nrtok-label">
							<span><?php esc_html_e( 'Restaurant Logo', 'nahian-restaurant-ordering-system' ); ?></span>
							<small><?php esc_html_e( '(Shown on POS & Thermal Receipt)', 'nahian-restaurant-ordering-system' ); ?></small>
						</label>
						<div class="nrtok-logo-upload-card">
							<div class="nrtok-logo-preview-box" id="nrtok-logo-preview-box">
								<?php if ( ! empty( $rest_logo ) ) : ?>
									<img src="<?php echo esc_url( $rest_logo ); ?>" alt="<?php esc_attr_e( 'Restaurant Logo', 'nahian-restaurant-ordering-system' ); ?>">
								<?php else : ?>
									<?php echo $svg_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
							</div>
							<div class="nrtok-logo-actions">
								<input type="hidden" name="restaurant_logo" id="nrtok-set-logo-url" value="<?php echo esc_url( $rest_logo ); ?>">
								<div class="nrtok-logo-btn-row">
									<button type="button" id="nrtok-upload-logo-btn" class="nrtok-btn-secondary nrtok-set-btn-icon">
										<?php echo $svg_upload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<span><?php esc_html_e( 'Upload / Choose Logo', 'nahian-restaurant-ordering-system' ); ?></span>
									</button>
									<button type="button" id="nrtok-remove-logo-btn" class="nrtok-btn-remove-logo <?php echo empty( $rest_logo ) ? 'nrtok-hidden' : ''; ?>">
										<?php echo $svg_trash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<span><?php esc_html_e( 'Remove', 'nahian-restaurant-ordering-system' ); ?></span>
									</button>
								</div>
								<span class="nrtok-logo-hint"><?php esc_html_e( 'Recommended: High-contrast PNG/SVG logo.', 'nahian-restaurant-ordering-system' ); ?></span>
							</div>
						</div>
					</div>

					<div class="nrtok-set-two-col">
						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Restaurant Name', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_store; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="text" name="restaurant_name" id="nrtok-set-rest-name" class="nrtok-input has-prefix" value="<?php echo esc_attr( $s['restaurant_name'] ?? $default_site_name ); ?>" required>
							</div>
						</div>

						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Tax Registration / VAT / EIN', 'nahian-restaurant-ordering-system' ); ?></span>
								<small><?php esc_html_e( '(Optional)', 'nahian-restaurant-ordering-system' ); ?></small>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_shield; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="text" name="bin_number" id="nrtok-set-bin" class="nrtok-input has-prefix" value="<?php echo esc_attr( $rest_bin ); ?>" placeholder="TAX ID: US-987654321">
							</div>
						</div>
					</div>

					<div class="nrtok-form-group">
						<label class="nrtok-label">
							<span><?php esc_html_e( 'Street Address', 'nahian-restaurant-ordering-system' ); ?></span>
							<small><?php esc_html_e( '(Printed below name on invoice)', 'nahian-restaurant-ordering-system' ); ?></small>
						</label>
						<div class="nrtok-input-wrap">
							<span class="nrtok-input-prefix"><?php echo $svg_pin; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<input type="text" name="address" id="nrtok-set-address" class="nrtok-input has-prefix" value="<?php echo esc_attr( $rest_address ); ?>" placeholder="<?php esc_attr_e( 'e.g. 123 Main Street, Suite 100', 'nahian-restaurant-ordering-system' ); ?>">
						</div>
					</div>

					<div class="nrtok-set-two-col">
						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Phone Number', 'nahian-restaurant-ordering-system' ); ?></span>
								<small><?php esc_html_e( '(Optional)', 'nahian-restaurant-ordering-system' ); ?></small>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_phone; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="text" name="phone" id="nrtok-set-phone" class="nrtok-input has-prefix" value="<?php echo esc_attr( $rest_phone ); ?>" placeholder="+1 (555) 234-5678">
							</div>
						</div>

						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Email Address', 'nahian-restaurant-ordering-system' ); ?></span>
								<small><?php esc_html_e( '(Optional)', 'nahian-restaurant-ordering-system' ); ?></small>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_email; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="email" name="email" id="nrtok-set-email" class="nrtok-input has-prefix" value="<?php echo esc_attr( $rest_email ); ?>" placeholder="orders@restaurant.com">
							</div>
						</div>
					</div>

					<!-- Section 2: Tax, Service Charge & Currency -->
					<div class="nrtok-set-sec-divider">
						<?php echo $svg_coins; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( '2. Currency, VAT / Tax & Service Charge', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>

					<div class="nrtok-set-two-col">
						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Currency Symbol', 'nahian-restaurant-ordering-system' ); ?> <span class="nrtok-required-star">*</span></span>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_coins; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<select name="currency" id="nrtok-set-currency" class="nrtok-input has-prefix" required>
									<?php foreach ( $currencies as $sym => $label ) : ?>
										<option value="<?php echo esc_attr( $sym ); ?>" <?php selected( $currency, $sym ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Service Charge (%)', 'nahian-restaurant-ordering-system' ); ?></span>
								<small><?php esc_html_e( '(0 to disable)', 'nahian-restaurant-ordering-system' ); ?></small>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix">%</span>
								<input type="number" step="0.01" min="0" max="100" name="service_charge" id="nrtok-set-service" class="nrtok-input has-prefix" value="<?php echo esc_attr( $service_rate ); ?>">
							</div>
						</div>
					</div>

					<div class="nrtok-set-two-col">
						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'VAT / Sales Tax Rate (%)', 'nahian-restaurant-ordering-system' ); ?></span>
								<small><?php esc_html_e( '(0 for no tax)', 'nahian-restaurant-ordering-system' ); ?></small>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix">%</span>
								<input type="number" step="0.01" min="0" max="100" name="tax_percent" id="nrtok-set-tax" class="nrtok-input has-prefix" value="<?php echo esc_attr( $tax_rate ); ?>">
							</div>
						</div>

						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Tax Calculation Mode', 'nahian-restaurant-ordering-system' ); ?></span>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_receipt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<select name="tax_mode" id="nrtok-set-tax-mode" class="nrtok-input has-prefix">
									<option value="exclusive" <?php selected( $tax_mode, 'exclusive' ); ?>><?php esc_html_e( 'Tax Exclusive (Added at Checkout)', 'nahian-restaurant-ordering-system' ); ?></option>
									<option value="inclusive" <?php selected( $tax_mode, 'inclusive' ); ?>><?php esc_html_e( 'Tax Inclusive (Included in Menu Price)', 'nahian-restaurant-ordering-system' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<div class="nrtok-form-group">
						<label class="nrtok-label">
							<span><?php esc_html_e( 'Accepted Payment Methods', 'nahian-restaurant-ordering-system' ); ?></span>
							<small><?php esc_html_e( '(Toggle enabled channels)', 'nahian-restaurant-ordering-system' ); ?></small>
						</label>
						<div class="nrtok-pay-switch-grid">
							<?php
							foreach ( $available_payments as $pm_key => $pm_data ) :
								$is_checked = in_array( $pm_key, $payment_methods, true );
								?>
								<label class="nrtok-pay-switch-card js-nrtok-switch-card <?php echo $is_checked ? 'is-active' : ''; ?>">
									<span class="nrtok-pay-switch-left">
										<span class="nrtok-pay-ico-badge"><?php echo $pm_data['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span><?php echo esc_html( $pm_data['label'] ); ?></span>
									</span>
									<span class="nrtok-switch">
										<input type="checkbox" name="payment_methods[]" value="<?php echo esc_attr( $pm_key ); ?>" <?php checked( $is_checked ); ?>>
										<span class="nrtok-switch-slider"></span>
									</span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Section 3: Thermal Receipt & Wi-Fi -->
					<div class="nrtok-set-sec-divider">
						<?php echo $svg_receipt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( '3. Thermal Printer & Receipt Footer', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>

					<div class="nrtok-set-two-col">
						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Order ID Prefix', 'nahian-restaurant-ordering-system' ); ?></span>
								<small><?php esc_html_e( '(e.g. ORD-, #)', 'nahian-restaurant-ordering-system' ); ?></small>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_hash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="text" name="order_prefix" id="nrtok-set-prefix" class="nrtok-input has-prefix" value="<?php echo esc_attr( $ord_prefix ); ?>" placeholder="ORD-" required>
							</div>
						</div>

						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Thermal Paper Roll Width', 'nahian-restaurant-ordering-system' ); ?></span>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_print; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<select name="paper_width" id="nrtok-set-paper-width" class="nrtok-input has-prefix">
									<option value="80mm" <?php selected( $paper_width, '80mm' ); ?>><?php esc_html_e( '80mm Standard Thermal Paper', 'nahian-restaurant-ordering-system' ); ?></option>
									<option value="58mm" <?php selected( $paper_width, '58mm' ); ?>><?php esc_html_e( '58mm Compact Thermal Paper', 'nahian-restaurant-ordering-system' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<div class="nrtok-set-two-col">
						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Guest Wi-Fi Name (SSID)', 'nahian-restaurant-ordering-system' ); ?></span>
								<small><?php esc_html_e( '(Optional)', 'nahian-restaurant-ordering-system' ); ?></small>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_wifi; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="text" name="wifi_ssid" id="nrtok-set-wifi-ssid" class="nrtok-input has-prefix" value="<?php echo esc_attr( $wifi_ssid ); ?>" placeholder="Restaurant_Guest_WiFi">
							</div>
						</div>

						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Guest Wi-Fi Password', 'nahian-restaurant-ordering-system' ); ?></span>
								<small><?php esc_html_e( '(Optional)', 'nahian-restaurant-ordering-system' ); ?></small>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_shield; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="text" name="wifi_password" id="nrtok-set-wifi-pass" class="nrtok-input has-prefix" value="<?php echo esc_attr( $wifi_pass ); ?>" placeholder="welcome2026">
							</div>
						</div>
					</div>

					<div class="nrtok-form-group">
						<label class="nrtok-label">
							<span><?php esc_html_e( 'Receipt Footer Message', 'nahian-restaurant-ordering-system' ); ?></span>
						</label>
						<div class="nrtok-input-wrap">
							<span class="nrtok-input-prefix"><?php echo $svg_msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<input type="text" name="receipt_footer" id="nrtok-set-footer" class="nrtok-input has-prefix" value="<?php echo esc_attr( $s['receipt_footer'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Thank you for dining with us!', 'nahian-restaurant-ordering-system' ); ?>">
						</div>
					</div>

					<!-- Section 4: Customer Tablet & Live Sync -->
					<div class="nrtok-set-sec-divider">
						<?php echo $svg_tablet; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( '4. Customer Tablet & Live Sync Controls', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>

					<div class="nrtok-set-two-col">
						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Customer Menu Page URL', 'nahian-restaurant-ordering-system' ); ?></span>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="url" name="menu_page_url" class="nrtok-input has-prefix" value="<?php echo esc_url( $menu_page_url ); ?>" placeholder="<?php echo esc_attr( home_url( '/menu/' ) ); ?>">
							</div>
						</div>

						<div class="nrtok-form-group">
							<label class="nrtok-label">
								<span><?php esc_html_e( 'Live Polling Interval', 'nahian-restaurant-ordering-system' ); ?></span>
								<small><?php esc_html_e( '(Refresh Speed)', 'nahian-restaurant-ordering-system' ); ?></small>
							</label>
							<div class="nrtok-input-wrap">
								<span class="nrtok-input-prefix"><?php echo $svg_clock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<select name="poll_interval_ms" class="nrtok-input has-prefix">
									<option value="3000" <?php selected( $poll_ms, 3000 ); ?>><?php esc_html_e( 'Every 3 Seconds (Fast)', 'nahian-restaurant-ordering-system' ); ?></option>
									<option value="4000" <?php selected( $poll_ms, 4000 ); ?>><?php esc_html_e( 'Every 4 Seconds (Recommended)', 'nahian-restaurant-ordering-system' ); ?></option>
									<option value="6000" <?php selected( $poll_ms, 6000 ); ?>><?php esc_html_e( 'Every 6 Seconds (Balanced)', 'nahian-restaurant-ordering-system' ); ?></option>
									<option value="10000" <?php selected( $poll_ms, 10000 ); ?>><?php esc_html_e( 'Every 10 Seconds (Low Load)', 'nahian-restaurant-ordering-system' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<div class="nrtok-form-group nrtok-set-switch-stack">
						<label class="nrtok-set-switch-card js-nrtok-switch-card <?php echo 1 === $enable_ordering ? 'is-active' : ''; ?>">
							<span class="nrtok-set-switch-left">
								<span class="nrtok-set-switch-ico"><?php echo $svg_tablet; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span>
									<span class="nrtok-set-switch-title"><?php esc_html_e( 'Allow Customer Self-Ordering', 'nahian-restaurant-ordering-system' ); ?></span>
									<span class="nrtok-set-switch-desc"><?php esc_html_e( 'Enable cart & order placement directly from table tablets (turn off for digital menu-only mode).', 'nahian-restaurant-ordering-system' ); ?></span>
								</span>
							</span>
							<span class="nrtok-switch">
								<input type="checkbox" name="enable_ordering" value="1" <?php checked( $enable_ordering, 1 ); ?>>
								<span class="nrtok-switch-slider"></span>
							</span>
						</label>

						<label class="nrtok-set-switch-card js-nrtok-switch-card <?php echo 1 === $enable_waiter ? 'is-active' : ''; ?>">
							<span class="nrtok-set-switch-left">
								<span class="nrtok-set-switch-ico"><?php echo $svg_bell; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span>
									<span class="nrtok-set-switch-title"><?php esc_html_e( 'Tablet "Call Waiter" Button', 'nahian-restaurant-ordering-system' ); ?></span>
									<span class="nrtok-set-switch-desc"><?php esc_html_e( 'Allow seated guests to summon a waiter to their table with one tap.', 'nahian-restaurant-ordering-system' ); ?></span>
								</span>
							</span>
							<span class="nrtok-switch">
								<input type="checkbox" name="enable_waiter" value="1" <?php checked( $enable_waiter, 1 ); ?>>
								<span class="nrtok-switch-slider"></span>
							</span>
						</label>

						<label class="nrtok-set-switch-card js-nrtok-switch-card <?php echo 1 === $sound_alerts ? 'is-active' : ''; ?>">
							<span class="nrtok-set-switch-left">
								<span class="nrtok-set-switch-ico"><?php echo $svg_volume; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span>
									<span class="nrtok-set-switch-title"><?php esc_html_e( 'Audible POS Alert Chimes', 'nahian-restaurant-ordering-system' ); ?></span>
									<span class="nrtok-set-switch-desc"><?php esc_html_e( 'Play an audio chime on the POS dashboard whenever a new waiter request arrives.', 'nahian-restaurant-ordering-system' ); ?></span>
								</span>
							</span>
							<span class="nrtok-switch">
								<input type="checkbox" name="sound_alerts" value="1" <?php checked( $sound_alerts, 1 ); ?>>
								<span class="nrtok-switch-slider"></span>
							</span>
						</label>
					</div>

					<div class="nrtok-set-form-footer">
						<button type="submit" name="nrtok_save_settings" class="nrtok-btn nrtok-set-btn-icon">
							<?php echo $svg_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( 'Save Configuration', 'nahian-restaurant-ordering-system' ); ?></span>
						</button>

						<div class="nrtok-set-sc-box">
							<span class="nrtok-set-sc-lbl"><?php esc_html_e( 'Shortcode:', 'nahian-restaurant-ordering-system' ); ?></span>
							<code id="nrtok-sc-text" class="nrtok-set-sc-code">[nrtok_restaurant_menu]</code>
							<button type="button" id="nrtok-copy-sc" class="nrtok-btn-secondary nrtok-set-btn-icon nrtok-set-copy-btn">
								<?php echo $svg_copy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'Copy', 'nahian-restaurant-ordering-system' ); ?></span>
							</button>
						</div>
					</div>
				</form>
			</div>

			<!-- Right Column: Professional Live Thermal Receipt Preview -->
			<div class="nrtok-card nrtok-full-card nrtok-set-preview-card">
				<div class="nrtok-set-preview-head">
					<span class="nrtok-set-preview-badge">
						<?php echo $svg_receipt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Thermal Receipt Preview', 'nahian-restaurant-ordering-system' ); ?></span>
					</span>
					<button type="button" id="nrtok-test-print-btn" class="nrtok-btn-dark nrtok-set-btn-icon" style="padding:6px 12px; font-size:12px;">
						<?php echo $svg_print; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Test Print', 'nahian-restaurant-ordering-system' ); ?></span>
					</button>
				</div>

				<div class="nrtok-receipt-mock <?php echo '58mm' === $paper_width ? 'is-58mm' : ''; ?>" id="nrtok-receipt-preview-box">
					<div class="nrtok-rc-center">
						<div id="prev-rest-logo-wrap" class="nrtok-rc-logo-wrap <?php echo empty( $rest_logo ) ? 'nrtok-hidden' : ''; ?>">
							<img id="prev-rest-logo-img" src="<?php echo esc_url( $rest_logo ); ?>" alt="<?php esc_attr_e( 'Receipt Logo', 'nahian-restaurant-ordering-system' ); ?>">
						</div>
						<h3 id="prev-rest-name" class="nrtok-rc-rest-title"><?php echo esc_html( $s['restaurant_name'] ?? 'RESTAURANT' ); ?></h3>
						<div id="prev-rest-address" class="nrtok-rc-contact-line <?php echo empty( $rest_address ) ? 'nrtok-hidden' : ''; ?>">
							<?php echo esc_html( $rest_address ); ?>
						</div>
						<div id="prev-rest-contact" class="nrtok-rc-contact-line">
							<?php
							if ( ! empty( $rest_phone ) && ! empty( $rest_email ) ) {
								echo esc_html( 'Phone: ' . $rest_phone . ' | ' . $rest_email );
							} elseif ( ! empty( $rest_phone ) ) {
								echo esc_html( 'Phone: ' . $rest_phone );
							} elseif ( ! empty( $rest_email ) ) {
								echo esc_html( $rest_email );
							}
							?>
						</div>
						<div id="prev-rest-bin" class="nrtok-rc-contact-line <?php echo empty( $rest_bin ) ? 'nrtok-hidden' : ''; ?>">
							<?php echo esc_html( $rest_bin ); ?>
						</div>
						<div class="nrtok-rc-invoice-sub"><?php esc_html_e( '*** CUSTOMER TAX INVOICE ***', 'nahian-restaurant-ordering-system' ); ?></div>
						<div class="nrtok-rc-meta-line">
							<?php esc_html_e( 'Table:', 'nahian-restaurant-ordering-system' ); ?> <strong>Table 01</strong> | <?php esc_html_e( 'Order:', 'nahian-restaurant-ordering-system' ); ?> <strong id="prev-order-id"><?php echo esc_html( $ord_prefix ); ?>104</strong>
						</div>
						<div class="nrtok-rc-date-line"><?php echo esc_html( wp_date( 'M d, Y • h:i A', time() ) ); ?></div>
					</div>

					<hr class="nrtok-rc-hr">

					<div class="nrtok-rc-table-hdr">
						<span><?php esc_html_e( 'ITEM DESCRIPTION', 'nahian-restaurant-ordering-system' ); ?></span>
						<span><?php esc_html_e( 'TOTAL', 'nahian-restaurant-ordering-system' ); ?></span>
					</div>

					<div class="nrtok-rc-row nrtok-rc-row-mb">
						<span>2x Adana Kebab</span>
						<span><span class="prev-cur"><?php echo esc_html( $currency ); ?></span>900.00</span>
					</div>
					<div class="nrtok-rc-row nrtok-rc-row-mb nrtok-rc-row-sm nrtok-rc-addon-row">
						<span>&nbsp;&nbsp;- Extra Cheese</span>
						<span><span class="prev-cur"><?php echo esc_html( $currency ); ?></span>50.00</span>
					</div>
					<div class="nrtok-rc-row">
						<span>1x Garlic Naan</span>
						<span><span class="prev-cur"><?php echo esc_html( $currency ); ?></span>100.00</span>
					</div>

					<hr class="nrtok-rc-hr">

					<div class="nrtok-rc-row nrtok-rc-row-sm nrtok-rc-row-mb">
						<span><?php esc_html_e( 'Subtotal:', 'nahian-restaurant-ordering-system' ); ?></span>
						<span><span class="prev-cur"><?php echo esc_html( $currency ); ?></span>1,050.00</span>
					</div>
					<div id="prev-service-row" class="nrtok-rc-row nrtok-rc-row-sm nrtok-rc-row-mb <?php echo $service_rate <= 0 ? 'nrtok-hidden' : ''; ?>">
						<span><?php esc_html_e( 'Service Charge', 'nahian-restaurant-ordering-system' ); ?> (<span id="prev-service-rate"><?php echo esc_html( $service_rate ); ?></span>%):</span>
						<span><span class="prev-cur"><?php echo esc_html( $currency ); ?></span><span id="prev-service-amt"><?php echo esc_html( number_format( $service_amt, 2 ) ); ?></span></span>
					</div>
					<div class="nrtok-rc-row nrtok-rc-row-sm nrtok-rc-row-mb">
						<span id="prev-tax-label">
							<?php if ( 'inclusive' === $tax_mode ) : ?>
								<?php /* translators: %s: VAT/tax percentage */ ?>
								<?php echo esc_html( sprintf( __( 'VAT / Tax (Incl. %s%%):', 'nahian-restaurant-ordering-system' ), $tax_rate ) ); ?>
							<?php else : ?>
								<?php /* translators: %s: VAT/tax percentage */ ?>
								<?php echo esc_html( sprintf( __( 'VAT / Tax (%s%%):', 'nahian-restaurant-ordering-system' ), $tax_rate ) ); ?>
							<?php endif; ?>
						</span>
						<span><span class="prev-cur"><?php echo esc_html( $currency ); ?></span><span id="prev-tax-amt"><?php echo esc_html( number_format( $tax_amt, 2 ) ); ?></span></span>
					</div>

					<div class="nrtok-rc-row nrtok-rc-row-grand nrtok-rc-row-mb">
						<span><?php esc_html_e( 'TOTAL PAYABLE:', 'nahian-restaurant-ordering-system' ); ?></span>
						<span><span class="prev-cur"><?php echo esc_html( $currency ); ?></span><span id="prev-grand-total"><?php echo esc_html( number_format( $tot_bill, 2 ) ); ?></span></span>
					</div>
					<div class="nrtok-rc-row nrtok-rc-row-sm nrtok-rc-row-mb nrtok-rc-pay-row">
						<span><?php esc_html_e( 'Payment Method:', 'nahian-restaurant-ordering-system' ); ?></span>
						<strong>CASH</strong>
					</div>
					<div class="nrtok-rc-row nrtok-rc-row-sm nrtok-rc-row-mb">
						<span><?php esc_html_e( 'Cash Tendered:', 'nahian-restaurant-ordering-system' ); ?></span>
						<span><span class="prev-cur"><?php echo esc_html( $currency ); ?></span>1,200.00</span>
					</div>
					<div class="nrtok-rc-row nrtok-rc-row-sm nrtok-rc-row-bold nrtok-rc-change-row">
						<span><?php esc_html_e( 'CHANGE / RETURN:', 'nahian-restaurant-ordering-system' ); ?></span>
						<span><span class="prev-cur"><?php echo esc_html( $currency ); ?></span><span id="prev-change-amt"><?php echo esc_html( number_format( max( 0, 1200.00 - $tot_bill ), 2 ) ); ?></span></span>
					</div>

					<hr class="nrtok-rc-hr">

					<div id="prev-wifi-box" class="nrtok-rc-wifi-box <?php echo empty( $wifi_ssid ) ? 'nrtok-hidden' : ''; ?>">
						Wi-Fi: <span id="prev-wifi-ssid"><?php echo esc_html( $wifi_ssid ); ?></span>
						<span id="prev-wifi-pass-wrap" class="<?php echo empty( $wifi_pass ) ? 'nrtok-hidden' : ''; ?>">
							| Pass: <span id="prev-wifi-pass"><?php echo esc_html( $wifi_pass ); ?></span>
						</span>
					</div>

					<div id="prev-footer" class="nrtok-rc-footer-txt">
						<?php echo esc_html( $s['receipt_footer'] ?? '' ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		const copySvg        = <?php echo wp_json_encode( $svg_copy ); ?>;
		const checkSvg       = <?php echo wp_json_encode( $svg_check ); ?>;
		const imgPlaceholder = <?php echo wp_json_encode( $svg_image ); ?>;
		const copiedTxt      = <?php echo wp_json_encode( __( 'Copied', 'nahian-restaurant-ordering-system' ) ); ?>;
		const copyTxt        = <?php echo wp_json_encode( __( 'Copy', 'nahian-restaurant-ordering-system' ) ); ?>;
		const mediaTitle     = <?php echo wp_json_encode( __( 'Select or Upload Restaurant Logo', 'nahian-restaurant-ordering-system' ) ); ?>;
		const mediaBtnTxt    = <?php echo wp_json_encode( __( 'Use This Logo', 'nahian-restaurant-ordering-system' ) ); ?>;

		// Sync active highlight state on all switch cards
		$(document).on('change', '.js-nrtok-switch-card input[type="checkbox"]', function() {
			$(this).closest('.js-nrtok-switch-card').toggleClass('is-active', this.checked);
		});

		// WordPress Media Uploader for Restaurant Logo.
		let logoMediaFrame;
		$('#nrtok-upload-logo-btn').on('click', function(e) {
			e.preventDefault();
			if (typeof wp === 'undefined' || !wp.media) {
				return;
			}
			if (logoMediaFrame) {
				logoMediaFrame.open();
				return;
			}
			logoMediaFrame = wp.media({
				title: mediaTitle,
				button: { text: mediaBtnTxt },
				library: { type: 'image' },
				multiple: false
			});

			logoMediaFrame.on('select', function() {
				const attachment = logoMediaFrame.state().get('selection').first().toJSON();
				if (attachment && attachment.url) {
					$('#nrtok-set-logo-url').val(attachment.url);
					$('#nrtok-logo-preview-box').html($('<img>', { src: attachment.url, alt: 'Logo' }));
					$('#nrtok-remove-logo-btn').removeClass('nrtok-hidden');
					$('#prev-rest-logo-img').attr('src', attachment.url);
					$('#prev-rest-logo-wrap').removeClass('nrtok-hidden');
				}
			});

			logoMediaFrame.open();
		});

		// Remove Logo.
		$('#nrtok-remove-logo-btn').on('click', function() {
			$('#nrtok-set-logo-url').val('');
			$('#nrtok-logo-preview-box').html(imgPlaceholder);
			$(this).addClass('nrtok-hidden');
			$('#prev-rest-logo-img').attr('src', '');
			$('#prev-rest-logo-wrap').addClass('nrtok-hidden');
		});

		// Copy Shortcode.
		$('#nrtok-copy-sc').on('click', function() {
			const scText = $('#nrtok-sc-text').text();
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(scText);
			} else {
				const $temp = $('<input>');
				$('body').append($temp);
				$temp.val(scText).select();
				document.execCommand('copy');
				$temp.remove();
			}
			const $btn = $(this);
			$btn.html(checkSvg + '<span>' + copiedTxt + '</span>');
			if (window.nrtokToast) {
				window.nrtokToast(<?php echo wp_json_encode( __( 'Shortcode copied to clipboard', 'nahian-restaurant-ordering-system' ) ); ?>, 'success');
			}
			setTimeout(function() {
				$btn.html(copySvg + '<span>' + copyTxt + '</span>');
			}, 1800);
		});

		// Test Print Thermal Receipt.
		$('#nrtok-test-print-btn').on('click', function() {
			const html = $('#nrtok-receipt-preview-box').html();
			const is58 = $('#nrtok-set-paper-width').val() === '58mm';
			if ($('#nrtok-print-area').length === 0) {
				$('body').append('<div id="nrtok-print-area"></div>');
			}
			$('#nrtok-print-area').toggleClass('is-58mm', is58).html(html);
			window.print();
		});

		// Live Receipt Totals Recalculation (Tax + Service Charge + Mode).
		function recalcPreviewReceipt() {
			const sub     = 1050.00;
			const taxRate = parseFloat($('#nrtok-set-tax').val()) || 0;
			const srvRate = parseFloat($('#nrtok-set-service').val()) || 0;
			const mode    = $('#nrtok-set-tax-mode').val();

			const srvAmt = (sub * srvRate) / 100;
			const taxAmt = (mode === 'inclusive')
				? (taxRate > 0 ? (sub - (sub / (1 + (taxRate / 100)))) : 0)
				: ((sub * taxRate) / 100);
			const tot    = (mode === 'inclusive') ? (sub + srvAmt) : (sub + srvAmt + taxAmt);
			const change = Math.max(0, 1200.00 - tot);

			if (srvRate > 0) {
				$('#prev-service-row').removeClass('nrtok-hidden');
				$('#prev-service-rate').text(srvRate);
				$('#prev-service-amt').text(srvAmt.toFixed(2));
			} else {
				$('#prev-service-row').addClass('nrtok-hidden');
			}

			if (mode === 'inclusive') {
				$('#prev-tax-label').text('VAT / Tax (Incl. ' + taxRate + '%):');
			} else {
				$('#prev-tax-label').text('VAT / Tax (' + taxRate + '%):');
			}

			$('#prev-tax-amt').text(taxAmt.toFixed(2));
			$('#prev-grand-total').text(tot.toFixed(2));
			$('#prev-change-amt').text(change.toFixed(2));
		}

		// Live Receipt Text Bindings.
		$('#nrtok-set-rest-name').on('input', function() {
			$('#prev-rest-name').text($(this).val() || 'RESTAURANT');
		});
		$('#nrtok-set-address').on('input', function() {
			const val = $(this).val().trim();
			$('#prev-rest-address').text(val).toggleClass('nrtok-hidden', !val);
		});
		$('#nrtok-set-bin').on('input', function() {
			const val = $(this).val().trim();
			$('#prev-rest-bin').text(val).toggleClass('nrtok-hidden', !val);
		});
		$('#nrtok-set-phone, #nrtok-set-email').on('input', function() {
			const ph = $('#nrtok-set-phone').val().trim();
			const em = $('#nrtok-set-email').val().trim();
			let contactStr = '';
			if (ph && em) contactStr = 'Phone: ' + ph + ' | ' + em;
			else if (ph) contactStr = 'Phone: ' + ph;
			else if (em) contactStr = em;
			$('#prev-rest-contact').text(contactStr);
		});
		$('#nrtok-set-currency').on('change', function() {
			$('.prev-cur').text($(this).val() || '$');
		});
		$('#nrtok-set-prefix').on('input', function() {
			$('#prev-order-id').text(($(this).val() || 'ORD-') + '104');
		});
		$('#nrtok-set-paper-width').on('change', function() {
			$('#nrtok-receipt-preview-box').toggleClass('is-58mm', $(this).val() === '58mm');
		});
		$('#nrtok-set-wifi-ssid, #nrtok-set-wifi-pass').on('input', function() {
			const ssid = $('#nrtok-set-wifi-ssid').val().trim();
			const pass = $('#nrtok-set-wifi-pass').val().trim();
			$('#prev-wifi-ssid').text(ssid);
			$('#prev-wifi-box').toggleClass('nrtok-hidden', !ssid);
			$('#prev-wifi-pass').text(pass);
			$('#prev-wifi-pass-wrap').toggleClass('nrtok-hidden', !pass);
		});
		$('#nrtok-set-tax, #nrtok-set-service, #nrtok-set-tax-mode').on('input change', recalcPreviewReceipt);
		$('#nrtok-set-footer').on('input', function() {
			$('#prev-footer').text($(this).val() || 'Thank you!');
		});
	});
	</script>
	<?php
}