<?php
/**
 * Nahian POS Menu Items Controller & Router
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure extended item columns exist on nrtok_menu.
 */
function nrtok_ensure_menu_item_cols() {
	global $wpdb;
	$t_menu = $wpdb->prefix . 'nrtok_menu';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_menu ) ) !== $t_menu ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$cols = $wpdb->get_col( $wpdb->prepare( 'DESC %i', $t_menu ), 0 );

	if ( is_array( $cols ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! in_array( 'ratio', $cols, true ) ) {
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN ratio varchar(50) DEFAULT "" AFTER price', $t_menu ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! in_array( 'item_order', $cols, true ) ) {
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN item_order int(5) NOT NULL DEFAULT 0 AFTER ratio', $t_menu ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! in_array( 'description', $cols, true ) ) {
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN description text AFTER item_order', $t_menu ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! in_array( 'is_available', $cols, true ) ) {
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN is_available tinyint(1) NOT NULL DEFAULT 1 AFTER extras', $t_menu ) );
		}
	}
}

/**
 * AJAX Handler: Quick-Create Extra directly from Items Form.
 */
add_action( 'wp_ajax_nrtok_quick_add_extra', 'nrtok_ajax_quick_add_extra' );
function nrtok_ajax_quick_add_extra() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Unauthorized access.', 'nahian-restaurant-ordering-system' ), 403 );
	}

	check_ajax_referer( 'nrtok_quick_add_extra_nonce', '_ajax_nonce' );

	global $wpdb;
	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$price = isset( $_POST['price'] ) ? (float) $_POST['price'] : 0.0;

	if ( empty( $name ) ) {
		wp_send_json_error( __( 'Extra name is required.', 'nahian-restaurant-ordering-system' ) );
	}

	$t_extras = $wpdb->prefix . 'nrtok_extras';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$inserted = $wpdb->insert(
		$t_extras,
		array(
			'name'  => $name,
			'price' => $price,
		),
		array( '%s', '%f' )
	);

	if ( false === $inserted ) {
		wp_send_json_error( __( 'Failed to save extra.', 'nahian-restaurant-ordering-system' ) );
	}

	wp_send_json_success(
		array(
			'id'    => absint( $wpdb->insert_id ),
			'name'  => esc_html( $name ),
			'price' => number_format( $price, 2, '.', '' ),
		)
	);
}

/**
 * AJAX Handler: Toggle Item Live Availability (In Stock / Out of Stock).
 */
add_action( 'wp_ajax_nrtok_toggle_item_availability', 'nrtok_ajax_toggle_item_availability' );
function nrtok_ajax_toggle_item_availability() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Unauthorized access.', 'nahian-restaurant-ordering-system' ), 403 );
	}

	check_ajax_referer( 'nrtok_toggle_avail_nonce', '_ajax_nonce' );

	global $wpdb;
	$id        = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$available = ( isset( $_POST['available'] ) && 1 === absint( $_POST['available'] ) ) ? 1 : 0;

	if ( $id <= 0 ) {
		wp_send_json_error( __( 'Invalid item ID.', 'nahian-restaurant-ordering-system' ), 400 );
	}

	$t_menu = $wpdb->prefix . 'nrtok_menu';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->update(
		$t_menu,
		array( 'is_available' => $available ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	);

	wp_send_json_success(
		array(
			'id'           => $id,
			'is_available' => $available,
		)
	);
}

/**
 * Render Menu Items Controller & Sub-Tab Router.
 *
 * @param array $s Plugin settings array.
 */
function nrtok_view_items( $s ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;
	nrtok_ensure_menu_item_cols();

	if ( empty( $s ) && function_exists( 'nrtok_get_settings' ) ) {
		$s = nrtok_get_settings();
	}
	$cur      = $s['currency'] ?? '৳';
	$t_menu   = $wpdb->prefix . 'nrtok_menu';
	$t_cats   = $wpdb->prefix . 'nrtok_categories';
	$t_extras = $wpdb->prefix . 'nrtok_extras';

	$svg_check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

	// Include Delete Handler Safely.
	$items_dir = plugin_dir_path( __FILE__ ) . 'items/';
	if ( is_readable( $items_dir . 'item-delete.php' ) ) {
		include_once $items_dir . 'item-delete.php';
	}

	// 1. Handle Save / Update Dish.
	if ( isset( $_POST['nrtok_save_item'] ) ) {
		check_admin_referer( 'nrtok_save_item_action' );

		$item_name = sanitize_text_field( wp_unslash( $_POST['item_name'] ?? '' ) );
		if ( ! empty( $item_name ) ) {
			$raw_extras      = isset( $_POST['item_extras'] ) && is_array( $_POST['item_extras'] ) ? wp_unslash( $_POST['item_extras'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$selected_extras = array_map( 'absint', $raw_extras );

			$data    = array(
				'name'        => $item_name,
				'category'    => sanitize_text_field( wp_unslash( $_POST['item_cat'] ?? '' ) ),
				'price'       => (float) ( $_POST['item_price'] ?? 0 ),
				'ratio'       => sanitize_text_field( wp_unslash( $_POST['item_ratio'] ?? '' ) ),
				'item_order'  => absint( $_POST['item_order'] ?? 0 ),
				'description' => sanitize_textarea_field( wp_unslash( $_POST['item_description'] ?? '' ) ),
				'image_url'   => esc_url_raw( wp_unslash( $_POST['item_img'] ?? '' ) ),
				'extras'      => wp_json_encode( $selected_extras ),
			);
			$formats = array( '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s' );

			$edit_id = ! empty( $_POST['edit_id'] ) ? absint( $_POST['edit_id'] ) : 0;
			if ( $edit_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $t_menu, $data, array( 'id' => $edit_id ), $formats, array( '%d' ) );
				echo '<div class="nrtok-alert nrtok-itm-alert-flex">' . $svg_check . '<span>' . esc_html__( 'Dish updated successfully!', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert( $t_menu, $data, $formats );
				echo '<div class="nrtok-alert nrtok-itm-alert-flex">' . $svg_check . '<span>' . esc_html__( 'New dish added to menu!', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	// Routing: 'all', 'add', or 'view'.
	$allowed_subtabs = array( 'all', 'add', 'view' );
	$raw_subtab      = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
	$subtab          = in_array( $raw_subtab, $allowed_subtabs, true ) ? $raw_subtab : 'all';
	$edit_item       = null;
	$view_item       = null;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_menu_table   = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_menu ) ) === $t_menu );
	$has_cats_table   = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_cats ) ) === $t_cats );
	$has_extras_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_extras ) ) === $t_extras );

	if ( $has_menu_table ) {
		if ( isset( $_GET['edit_item'] ) ) {
			$edit_item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_menu, absint( $_GET['edit_item'] ) ) );
			$subtab    = 'add';
		} elseif ( isset( $_GET['view_item'] ) ) {
			$view_item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_menu, absint( $_GET['view_item'] ) ) );
			$subtab    = 'view';
		}
	}

	$edit_extras = array();
	if ( $edit_item && ! empty( $edit_item->extras ) ) {
		$decoded_ex = json_decode( $edit_item->extras, true );
		if ( is_array( $decoded_ex ) ) {
			$edit_extras = $decoded_ex;
		}
	}

	$cats       = $has_cats_table ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY category_order ASC, name ASC', $t_cats ) ) ?: array() ) : array();
	$extras     = $has_extras_table ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY name ASC', $t_extras ) ) ?: array() ) : array();
	$extras_map = $has_extras_table ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $t_extras ), OBJECT_K ) ?: array() ) : array();
	$items      = $has_menu_table ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY item_order ASC, id DESC', $t_menu ) ) ?: array() ) : array();
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	?>

	<style>
		.nrtok-itm-alert-flex { display: flex; align-items: center; gap: 8px; }
		.nrtok-itm-full-wrap { width: 100%; box-sizing: border-box; }
		.nrtok-full-card { width: 100%; max-width: 100%; box-sizing: border-box; }
		.nrtok-full-table { width: 100%; max-width: 100%; }

		/* Form Styles */
		.nrtok-form-header { border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 22px; }
		.nrtok-form-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
		.nrtok-form-header p { margin: 5px 0 0; font-size: 13px; color: #64748b; }
		.nrtok-form-group { margin-bottom: 18px; width: 100%; }
		.nrtok-label { display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 7px; }
		.nrtok-label-mb { margin-bottom: 8px; }
		.nrtok-label small { font-weight: 500; color: #64748b; font-size: 12px; }
		.nrtok-input-wrap { position: relative; display: flex; align-items: center; width: 100%; }
		.nrtok-input-prefix { position: absolute; left: 14px; font-weight: 800; color: #64748b; font-size: 14.5px; pointer-events: none; display: inline-flex; align-items: center; }
		.nrtok-input {
			width: 100%; padding: 11px 15px; border: 1.5px solid #e2e8f0; border-radius: 11px;
			font-size: 14px; font-weight: 500; color: #0f172a; background-color: #f8fafc; box-sizing: border-box;
		}
		.nrtok-input.has-prefix { padding-left: 40px; }
		.nrtok-input:focus { outline: none; background-color: #fff; border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.14); }
		select.nrtok-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; }

		.nrtok-itm-form-grid-top { display: grid; grid-template-columns: 1.3fr 1fr; gap: 20px; width: 100%; }
		.nrtok-itm-form-grid-bot { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; width: 100%; }
		@media (max-width: 768px) { .nrtok-itm-form-grid-top, .nrtok-itm-form-grid-bot { grid-template-columns: 1fr; } }

		.nrtok-itm-photo-row { display: flex; gap: 10px; align-items: center; }
		.nrtok-itm-photo-preview { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; border: 1.5px solid #e2e8f0; flex-shrink: 0; }
		.nrtok-itm-upload-btn { height: 44px; white-space: nowrap; }

		.nrtok-itm-ex-controls { display: flex; gap: 8px; align-items: center; }
		.nrtok-itm-sel-all-btn { padding: 4px 10px; font-size: 11.5px; }
		.nrtok-itm-quick-ex-trigger { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; padding: 5px 11px; border-radius: 7px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }

		.nrtok-quick-extra-box { display: none; background: #fff7ed; border: 1.5px solid #fed7aa; padding: 16px; border-radius: 12px; margin-bottom: 12px; }
		.nrtok-quick-extra-title { font-size: 13px; font-weight: 700; color: #c2410c; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
		.nrtok-quick-extra-row { display: grid; grid-template-columns: 1.6fr 1fr auto auto; gap: 10px; align-items: center; }
		@media (max-width: 640px) { .nrtok-quick-extra-row { grid-template-columns: 1fr; } }

		.nrtok-itm-form-footer { margin-top: 22px; padding-top: 16px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; }
		.nrtok-col-itm-act { width: 235px; }
		.nrtok-itm-act-btn { padding: 6px 11px; border-radius: 7px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
		.nrtok-act-view { background: #eff6ff; color: #1d4ed8; }
		.nrtok-act-edit { background: #f1f5f9; color: #334155; }
		.nrtok-act-del  { background: #fef2f2; color: #dc2626; }
		.nrtok-itm-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
	</style>

	<div class="nrtok-itm-full-wrap">
		<?php
		if ( is_readable( $items_dir . 'item-tabs.php' ) ) {
			include $items_dir . 'item-tabs.php';
		}

		if ( 'view' === $subtab && $view_item && is_readable( $items_dir . 'item-view.php' ) ) {
			include $items_dir . 'item-view.php';
		} elseif ( 'add' === $subtab && is_readable( $items_dir . 'item-add-edit.php' ) ) {
			include $items_dir . 'item-add-edit.php';
		} elseif ( is_readable( $items_dir . 'item-list.php' ) ) {
			include $items_dir . 'item-list.php';
		}
		?>
	</div>
	<?php
}