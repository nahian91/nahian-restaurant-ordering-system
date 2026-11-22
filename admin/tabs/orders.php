<?php
/**
 * Nahian POS Orders & Kitchen Monitor View
 *
 * @package NRTOK_POS
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Orders & Kitchen Monitor inline styles when viewing the orders tab.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function nrtok_orders_enqueue_assets( $hook ) {
	if ( 'toplevel_page_nrtok-pos' !== $hook ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab routing check.
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
	if ( 'orders' !== $tab ) {
		return;
	}

	wp_register_style( 'nrtok-orders-style', false, array(), NRTOK_VERSION );
	wp_enqueue_style( 'nrtok-orders-style' );
	wp_add_inline_style( 'nrtok-orders-style', nrtok_get_orders_inline_css() );
}
add_action( 'admin_enqueue_scripts', 'nrtok_orders_enqueue_assets' );

/**
 * Render the Orders & Kitchen Monitor tab.
 *
 * @param array<string, mixed> $settings Plugin settings array.
 * @return void
 */
function nrtok_view_orders( $settings ) {
	if ( ! nrtok_current_user_can_pos() ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nahian-restaurant-ordering-system' ) );
	}

	global $wpdb;
	$s            = $settings; // Preserve $s alias for included sub-templates.
	$orders_table = $wpdb->prefix . 'nrtok_orders';

	// Include Edit & Delete handlers safely.
	$orders_dir = plugin_dir_path( __FILE__ ) . 'orders/';
	if ( is_readable( $orders_dir . 'order-edit.php' ) ) {
		include_once $orders_dir . 'order-edit.php';
	}
	if ( is_readable( $orders_dir . 'order-delete.php' ) ) {
		include_once $orders_dir . 'order-delete.php';
	}

	// 1. Handle Status Update (Handler must verify nonce via check_admin_referer).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified inside nrtok_handle_order_status_update().
	if ( isset( $_GET['set_status'], $_GET['order_id'] ) && function_exists( 'nrtok_handle_order_status_update' ) ) {
		nrtok_handle_order_status_update( $orders_table );
	}

	// 2. Handle Full Order Edit Form Submission (Handler must verify POST nonce).
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified inside nrtok_handle_full_order_edit().
	if ( isset( $_POST['nrtok_save_order_edit'] ) && function_exists( 'nrtok_handle_full_order_edit' ) ) {
		nrtok_handle_full_order_edit( $orders_table, $s );
	}

	// 3. Handle Delete Order (Handler must verify nonce via check_admin_referer).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified inside nrtok_handle_order_delete().
	if ( isset( $_GET['delete_order'] ) && function_exists( 'nrtok_handle_order_delete' ) ) {
		nrtok_handle_order_delete( $orders_table );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_orders_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders_table ) ) === $orders_table );

	// Check Dedicated Order View Page.
	$view_order = null;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only record view ID.
	$view_order_id = isset( $_GET['view_order'] ) ? absint( $_GET['view_order'] ) : 0;

	if ( $has_orders_table && $view_order_id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$view_order = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$orders_table,
				$view_order_id
			)
		);
	}

	// Filters & Pagination Setup.
	$allowed_filters = array( 'all', 'pending', 'preparing', 'served', 'completed' );
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filter/search/pagination parameters.
	$raw_filter   = isset( $_GET['status_filter'] ) ? sanitize_key( wp_unslash( $_GET['status_filter'] ) ) : 'all';
	$filter       = in_array( $raw_filter, $allowed_filters, true ) ? $raw_filter : 'all';
	$search_query = isset( $_GET['s_ord'] ) ? sanitize_text_field( wp_unslash( $_GET['s_ord'] ) ) : '';
	$paged        = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$is_edit_mode = isset( $_GET['edit_order'] );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$per_page = 10;
	$offset   = ( $paged - 1 ) * $per_page;

	$counts = array(
		'all'       => 0,
		'pending'   => 0,
		'preparing' => 0,
		'served'    => 0,
		'completed' => 0,
	);

	$total_items = 0;
	$total_pages = 1;
	$orders      = array();

	if ( $has_orders_table ) {
		// Single grouped query for all status badge counts.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$status_rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT status, COUNT(*) AS cnt FROM %i GROUP BY status',
				$orders_table
			)
		);

		if ( is_array( $status_rows ) ) {
			foreach ( $status_rows as $st_row ) {
				$st_key = sanitize_key( $st_row->status );
				$st_cnt = (int) $st_row->cnt;
				if ( isset( $counts[ $st_key ] ) ) {
					$counts[ $st_key ] = $st_cnt;
				}
				$counts['all'] += $st_cnt;
			}
		}

		// Parameterized filtering without raw SQL string interpolation.
		$has_filter = ( 'all' !== $filter );
		$has_search = ( '' !== $search_query );
		$like       = $has_search ? '%' . $wpdb->esc_like( $search_query ) . '%' : '';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $has_filter && $has_search ) {
			$total_items = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE status = %s AND (CAST(id AS CHAR) LIKE %s OR table_no LIKE %s)',
					$orders_table,
					$filter,
					$like,
					$like
				)
			);
			$orders      = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE status = %s AND (CAST(id AS CHAR) LIKE %s OR table_no LIKE %s) ORDER BY id DESC LIMIT %d OFFSET %d',
					$orders_table,
					$filter,
					$like,
					$like,
					$per_page,
					$offset
				)
			);
		} elseif ( $has_filter ) {
			$total_items = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE status = %s',
					$orders_table,
					$filter
				)
			);
			$orders      = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d',
					$orders_table,
					$filter,
					$per_page,
					$offset
				)
			);
		} elseif ( $has_search ) {
			$total_items = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE (CAST(id AS CHAR) LIKE %s OR table_no LIKE %s)',
					$orders_table,
					$like,
					$like
				)
			);
			$orders      = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE (CAST(id AS CHAR) LIKE %s OR table_no LIKE %s) ORDER BY id DESC LIMIT %d OFFSET %d',
					$orders_table,
					$like,
					$like,
					$per_page,
					$offset
				)
			);
		} else {
			$total_items = $counts['all'];
			$orders      = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i ORDER BY id DESC LIMIT %d OFFSET %d',
					$orders_table,
					$per_page,
					$offset
				)
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$orders      = is_array( $orders ) ? $orders : array();
		$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
	}
	?>
	<div class="nrtok-ord-full-wrap">
		<?php
		// Load Navigation Header / Tabs.
		if ( is_readable( $orders_dir . 'order-tabs.php' ) ) {
			include $orders_dir . 'order-tabs.php';
		}

		if ( $view_order ) {
			if ( $is_edit_mode && function_exists( 'nrtok_render_order_edit_form' ) ) {
				nrtok_render_order_edit_form( $view_order, $s );
			} elseif ( is_readable( $orders_dir . 'order-view.php' ) ) {
				include $orders_dir . 'order-view.php';
			}
		} elseif ( is_readable( $orders_dir . 'order-list.php' ) ) {
			include $orders_dir . 'order-list.php';
		}
		?>
	</div>
	<?php
}

/**
 * Return Orders & Kitchen Monitor inline CSS.
 *
 * @return string
 */
function nrtok_get_orders_inline_css() {
	return '
		.nrtok-ord-alert-flex { display: flex; align-items: center; gap: 8px; }
		.nrtok-ord-full-wrap { width: 100%; box-sizing: border-box; }
		.nrtok-full-card { width: 100%; max-width: 100%; box-sizing: border-box; }
		.nrtok-full-table { width: 100%; max-width: 100%; }
		.nrtok-table-responsive { width: 100%; overflow-x: auto; border-radius: 18px; }

		.nrtok-ord-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
		.nrtok-ord-back-btn { display: inline-flex; align-items: center; gap: 6px; }

		.nrtok-ord-toolbar {
			display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
			gap: 14px; margin-bottom: 22px; width: 100%;
		}
		.nrtok-ord-pills { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
		.nrtok-ord-pills-no-margin { margin: 0; }
		.nrtok-pill {
			display: inline-flex; align-items: center; padding: 8px 14px; border-radius: 11px;
			background: #fff; border: 1.5px solid #e2e8f0; color: #475569; font-size: 13px;
			font-weight: 700; text-decoration: none; transition: all 0.18s ease;
		}
		.nrtok-pill:hover { border-color: #cbd5e1; background: #f8fafc; color: #0f172a; }
		.nrtok-pill.active {
			background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
			border-color: #f97316; color: #fff; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.28);
		}

		.nrtok-ord-search-box { position: relative; width: 280px; max-width: 100%; }
		.nrtok-ord-search-box svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #64748b; }
		.nrtok-ord-search-input {
			width: 100%; padding: 11px 16px 11px 42px; border: 1.5px solid #cbd5e1; border-radius: 12px;
			font-size: 14px; font-weight: 500; background: #fff; color: #0f172a; box-sizing: border-box;
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
			box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
		}
		.nrtok-ord-search-input:hover { border-color: #94a3b8; }
		.nrtok-ord-search-input:focus {
			outline: none; border-color: #f97316;
			box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.14);
		}

		.nrtok-autoprint-box {
			background: #0f172a; color: #f8fafc; padding: 10px 16px; border-radius: 12px;
			font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 10px;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
		}
		.nrtok-autoprint-toggle-btn {
			background: #334155; color: #fff; border: none; padding: 5px 11px;
			border-radius: 7px; font-size: 11.5px; font-weight: 700; cursor: pointer; transition: 0.15s;
		}
		.nrtok-autoprint-toggle-btn:hover { background: #475569; }
		.nrtok-ap-dot-paused { background-color: #ef4444 !important; }

		.nrtok-pill-count {
			background: rgba(0, 0, 0, 0.08); padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 5px; font-weight: 750;
		}
		.nrtok-pill.active .nrtok-pill-count { background: rgba(255, 255, 255, 0.22); color: #fff; }
		.nrtok-st-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
		.nrtok-dot-pending { background: #f59e0b; }
		.nrtok-dot-preparing { background: #3b82f6; }
		.nrtok-dot-served { background: #a855f7; }
		.nrtok-dot-completed { background: #10b981; }

		/* Table Column Widths & Cell Styles */
		.nrtok-col-ord-id { width: 95px; }
		.nrtok-col-ord-tbl { width: 120px; }
		.nrtok-col-ord-status { width: 165px; }
		.nrtok-col-ord-actions { width: 370px; }

		.nrtok-ord-empty-cell { text-align: center; padding: 48px; color: #64748b; font-weight: 500; }
		.nrtok-ord-id-link { font-size: 15px; font-weight: 850; color: #0f172a; text-decoration: none; transition: color 0.15s; }
		.nrtok-ord-id-link:hover { color: #f97316; }
		.nrtok-ord-time-sm { color: #64748b; font-size: 12px; }
		.nrtok-ord-table-badge {
			background: #fff7ed; color: #ea580c; border: 1.5px solid #fed7aa;
			padding: 5px 12px; border-radius: 9px; font-weight: 800; font-size: 13px; display: inline-block;
		}

		/* Status Select Dropdown */
		.nrtok-ord-select-wrapper { position: relative; display: block; width: 100%; }
		.nrtok-ord-status-select {
			appearance: none; -webkit-appearance: none; -moz-appearance: none;
			width: 100%; display: block; padding: 9px 36px 9px 14px; border-radius: 11px;
			font-weight: 800; font-size: 12.5px; text-transform: uppercase; letter-spacing: 0.4px;
			border: 1.5px solid transparent; cursor: pointer; box-sizing: border-box;
			background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'3\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'6 9 12 15 18 9\'%3E%3C/polyline%3E%3C/svg%3E");
			background-repeat: no-repeat; background-position: right 12px center; background-size: 14px;
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
			box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
		}
		.nrtok-ord-status-select:hover { filter: brightness(0.97); transform: translateY(-1px); }
		.nrtok-ord-status-select:focus { outline: none; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.14); }

		.nrtok-ord-st-pending { background-color: #fef3c7; color: #b45309; border-color: #fde68a; }
		.nrtok-ord-st-preparing { background-color: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
		.nrtok-ord-st-served { background-color: #faf5ff; color: #7e22ce; border-color: #e9d5ff; }
		.nrtok-ord-st-completed { background-color: #ecfdf5; color: #047857; border-color: #a7f3d0; }

		/* Action Buttons */
		.nrtok-ord-action-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
		.nrtok-ord-act-btn {
			padding: 7px 11px; border-radius: 8px; font-size: 12px; font-weight: 750; text-decoration: none;
			display: inline-flex; align-items: center; gap: 5px; transition: all 0.18s ease; border: none; cursor: pointer;
		}
		.nrtok-act-view { background: #eff6ff; color: #1d4ed8; }
		.nrtok-act-view:hover { background: #dbeafe; }
		.nrtok-act-edit { background: #f1f5f9; color: #334155; }
		.nrtok-act-edit:hover { background: #e2e8f0; color: #0f172a; }
		.nrtok-step-cook { background: #dbeafe; color: #1d4ed8; }
		.nrtok-step-cook:hover { background: #bfdbfe; color: #1e40af; }
		.nrtok-step-serve { background: #f3e8ff; color: #7e22ce; }
		.nrtok-step-serve:hover { background: #e9d5ff; color: #6b21a8; }
		.nrtok-step-pay { background: #d1fae5; color: #059669; }
		.nrtok-step-pay:hover { background: #a7f3d0; color: #047857; }
		.nrtok-act-del { background: #fef2f2; color: #dc2626; }
		.nrtok-act-del:hover { background: #fee2e2; }
		.nrtok-ord-print-kot-btn { display: inline-flex; align-items: center; gap: 5px; }
		.nrtok-ord-print-bill-btn { padding: 7px 11px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }

		/* Pagination Bar */
		.nrtok-ord-pagination {
			display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
			gap: 12px; margin-top: 22px; padding: 14px 18px; background: #fff; border-radius: 14px;
			border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
		}
		.nrtok-ord-pag-info { font-size: 13px; color: #64748b; font-weight: 650; }
		.nrtok-ord-pag-links { display: flex; gap: 6px; align-items: center; }
		.nrtok-ord-pag-btn {
			padding: 7px 13px; border-radius: 9px; font-size: 12.5px; font-weight: 750;
			text-decoration: none; background: #f8fafc; color: #334155; border: 1px solid #cbd5e1;
			transition: all 0.18s ease;
		}
		.nrtok-ord-pag-btn:hover { background: #e2e8f0; color: #0f172a; }
		.nrtok-ord-pag-btn.current { background: #f97316; color: #fff; border-color: #f97316; box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3); }
		.nrtok-ord-pag-disabled { opacity: 0.4; pointer-events: none; }
	';
}