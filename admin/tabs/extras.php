<?php
/**
 * Nahian POS Extra Add-ons Controller & Router
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Extra Add-ons Controller & Sub-Tab Router.
 *
 * @param array<string, mixed> $s Plugin settings array.
 * @return void
 */
function nrtok_view_extras( $s = array() ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;

	if ( empty( $s ) && function_exists( 'nrtok_get_settings' ) ) {
		$s = nrtok_get_settings();
	}
	$cur      = $s['currency'] ?? '$';
	$t_extras = $wpdb->prefix . 'nrtok_extras';
	$t_menu   = $wpdb->prefix . 'nrtok_menu';

	$svg_check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
	$svg_alert = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

	// Include Delete Handler Safely.
	$extras_dir = plugin_dir_path( __FILE__ ) . 'extras/';
	if ( is_readable( $extras_dir . 'extra-delete.php' ) ) {
		include_once $extras_dir . 'extra-delete.php';
	}

	// 1. Handle Save / Update Extra.
	if ( isset( $_POST['nrtok_save_extra'] ) && ! empty( $_POST['extra_name'] ) ) {
		check_admin_referer( 'nrtok_save_extra_action' );

		$edit_id     = ! empty( $_POST['edit_extra_id'] ) ? absint( $_POST['edit_extra_id'] ) : 0;
		$extra_name  = sanitize_text_field( wp_unslash( $_POST['extra_name'] ) );
		$extra_price = max( 0.0, (float) ( $_POST['extra_price'] ?? 0 ) );
		$extra_image = esc_url_raw( wp_unslash( $_POST['extra_image'] ?? '' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE name = %s AND id != %d LIMIT 1',
				$t_extras,
				$extra_name,
				$edit_id
			)
		);

		if ( $exists ) {
			echo '<div class="nrtok-alert nrtok-ex-alert-flex" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;">' . $svg_alert . '<span>' . sprintf( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				/* translators: %s: extra add-on name */
				esc_html__( 'Extra "%s" already exists!', 'nahian-restaurant-ordering-system' ),
				esc_html( $extra_name )
			) . '</span></div>';
		} else {
			$data    = array(
				'name'  => $extra_name,
				'price' => $extra_price,
				'image' => $extra_image,
			);
			$formats = array( '%s', '%f', '%s' );

			if ( $edit_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $t_extras, $data, array( 'id' => $edit_id ), $formats, array( '%d' ) );
				echo '<div class="nrtok-alert nrtok-ex-alert-flex">' . $svg_check . '<span>' . esc_html__( 'Extra add-on updated successfully!', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert( $t_extras, $data, $formats );
				echo '<div class="nrtok-alert nrtok-ex-alert-flex">' . $svg_check . '<span>' . esc_html__( 'New extra add-on created!', 'nahian-restaurant-ordering-system' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	// Routing: 'all', 'add', or 'view'.
	$allowed_subtabs = array( 'all', 'add', 'view' );
	$raw_subtab      = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
	$subtab          = in_array( $raw_subtab, $allowed_subtabs, true ) ? $raw_subtab : 'all';
	$edit_extra      = null;
	$view_extra      = null;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_extras_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_extras ) ) === $t_extras );
	$has_menu_table   = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_menu ) ) === $t_menu );

	if ( $has_extras_table ) {
		if ( isset( $_GET['edit_extra'] ) ) {
			$edit_extra = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_extras, absint( $_GET['edit_extra'] ) )
			);
			$subtab     = 'add';
		} elseif ( isset( $_GET['view_extra'] ) ) {
			$view_extra = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $t_extras, absint( $_GET['view_extra'] ) )
			);
			$subtab     = 'view';
		}
	}

	$extras    = $has_extras_table ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC', $t_extras ) ) ?: array() ) : array();
	$all_items = $has_menu_table ? ( $wpdb->get_results( $wpdb->prepare( 'SELECT id, name, category, price, image_url, extras FROM %i ORDER BY name ASC', $t_menu ) ) ?: array() ) : array();
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Map full dish objects by extra ID.
	$dishes_by_extra = array();
	foreach ( $all_items as $itm ) {
		if ( ! empty( $itm->extras ) ) {
			$ids = json_decode( $itm->extras, true );
			if ( is_array( $ids ) ) {
				foreach ( $ids as $eid ) {
					$dishes_by_extra[ absint( $eid ) ][] = $itm;
				}
			}
		}
	}
	?>

	<style>
		.nrtok-ex-alert-flex { display: flex; align-items: center; gap: 8px; }
		.nrtok-ex-full-wrap { width: 100%; box-sizing: border-box; }
		.nrtok-full-card { width: 100%; max-width: 100%; box-sizing: border-box; }
		.nrtok-full-table { width: 100%; max-width: 100%; }

		/* Form & Controls */
		.nrtok-form-header { border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 22px; }
		.nrtok-form-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
		.nrtok-form-header p { margin: 5px 0 0; font-size: 13px; color: #64748b; }
		.nrtok-form-group { margin-bottom: 18px; width: 100%; }
		.nrtok-label { display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 7px; }
		.nrtok-label small { font-weight: 500; color: #64748b; font-size: 12px; }
		.nrtok-input-wrap { position: relative; display: flex; align-items: center; width: 100%; }
		.nrtok-input-prefix { position: absolute; left: 14px; font-weight: 800; color: #64748b; font-size: 15px; pointer-events: none; display: inline-flex; align-items: center; }
		.nrtok-input {
			width: 100%; padding: 11px 15px; border: 1.5px solid #e2e8f0; border-radius: 11px;
			font-size: 14px; font-weight: 500; color: #0f172a; background-color: #f8fafc; box-sizing: border-box;
		}
		.nrtok-input.has-prefix { padding-left: 40px; }
		.nrtok-input:focus { outline: none; background-color: #fff; border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.14); }

		.nrtok-ex-form-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; width: 100%; }
		@media (max-width: 768px) { .nrtok-ex-form-grid { grid-template-columns: 1fr; } }
		.nrtok-ex-form-footer { margin-top: 22px; padding-top: 16px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; }

		/* Empty State */
		.nrtok-ex-empty-card { text-align: center; padding: 60px 20px; }
		.nrtok-ex-empty-ico { width: 56px; height: 56px; border-radius: 16px; background: #fff7ed; color: #f97316; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; }
		.nrtok-ex-empty-title { margin: 0 0 8px; font-size: 19px; }
		.nrtok-ex-empty-desc { color: #64748b; margin: 0 0 20px; font-size: 14px; }

		/* Table & Cards */
		.nrtok-col-ex-id { width: 80px; }
		.nrtok-col-ex-price { width: 150px; }
		.nrtok-col-ex-used { width: 180px; }
		.nrtok-col-ex-act { width: 230px; }

		.nrtok-ex-name-link { font-size: 15px; font-weight: 700; color: #0f172a; text-decoration: none; }
		.nrtok-ex-name-link:hover { color: #f97316; }
		.nrtok-ex-price-val { color: #f97316; font-size: 15px; }

		.nrtok-ex-used-badge { padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
		.nrtok-ex-badge-active { background: #ecfdf5; color: #059669; }
		.nrtok-ex-badge-empty { background: #f1f5f9; color: #64748b; }

		.nrtok-ex-act-group { display: flex; gap: 7px; align-items: center; }
		.nrtok-ex-act-btn { padding: 6px 11px; border-radius: 7px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
		.nrtok-act-view { background: #eff6ff; color: #1d4ed8; }
		.nrtok-act-edit { background: #f1f5f9; color: #334155; }
		.nrtok-act-del  { background: #fef2f2; color: #dc2626; }
		.nrtok-ex-btn-icon { display: inline-flex; align-items: center; gap: 7px; }
	</style>

	<div class="nrtok-ex-full-wrap">
		<?php
		if ( is_readable( $extras_dir . 'extra-tabs.php' ) ) {
			include $extras_dir . 'extra-tabs.php';
		}

		if ( 'view' === $subtab && $view_extra && is_readable( $extras_dir . 'extra-view.php' ) ) {
			include $extras_dir . 'extra-view.php';
		} elseif ( 'add' === $subtab && is_readable( $extras_dir . 'extra-add-edit.php' ) ) {
			include $extras_dir . 'extra-add-edit.php';
		} elseif ( is_readable( $extras_dir . 'extra-list.php' ) ) {
			include $extras_dir . 'extra-list.php';
		}
		?>
	</div>
	<?php
}